<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class FileManagerController extends Controller
{
    /**
     * Resolve the authorized base path for the authenticated client.
     */
    protected function getUserBaseDirectory(?int $subscriptionId = null): array
    {
        $userId = auth()->id();
        $user = auth()->user();

        if ($user && $user->role === 'admin') {
            if ($subscriptionId) {
                $subscription = Subscription::find($subscriptionId);
            } else {
                $subscription = Subscription::where('user_id', $userId)->where('status', 'active')->first()
                    ?: Subscription::where('status', 'active')->first()
                    ?: Subscription::first();
            }
        } else {
            $query = Subscription::where('user_id', $userId)->where('status', 'active');
            if ($subscriptionId) {
                $subscription = $query->where('id', $subscriptionId)->first();
            } else {
                $subscription = $query->first();
            }
        }

        if (!$subscription) {
            if ($user && $user->role === 'admin') {
                $base = '/var/www/vhosts';
                return [$base, null];
            }
            abort(403, 'No active hosting subscription found.');
        }

        $base = "/var/www/vhosts/{$subscription->username}";
        if (!is_dir($base)) {
            @mkdir($base, 0775, true);
            @chown($base, 'www-data');
        }

        return [$base, $subscription];
    }

    /**
     * Resolve and validate a subpath inside the user jail.
     */
    protected function resolveSafePath(string $basePath, string $subPath): string
    {
        $cleanSubPath = '/' . ltrim(str_replace(['../', '..\\'], '', $subPath), '/');
        $fullPath = $basePath . $cleanSubPath;

        // Check if path is safely inside basePath
        $realBase = realpath($basePath) ?: $basePath;
        $realFull = realpath($fullPath);

        if ($realFull && !str_starts_with($realFull, $realBase)) {
            abort(403, 'Unauthorized directory traversal attempt.');
        }

        return $fullPath;
    }

    /**
     * Display the main web file manager view or return JSON list if requested.
     */
    public function browse(Request $request): Response|JsonResponse
    {
        $userId = auth()->id();
        $user = auth()->user();

        if ($user && $user->role === 'admin') {
            $subscriptions = Subscription::with('websites')->latest()->get();
        } else {
            $subscriptions = Subscription::where('user_id', $userId)
                ->where('status', 'active')
                ->with('websites')
                ->get();
        }

        $subId = $request->get('subscription_id', $subscriptions->first()?->id);
        [$basePath, $activeSub] = $this->getUserBaseDirectory($subId ? (int)$subId : null);

        // Requested path relative to user's root
        $rawPath = $request->get('path', '');
        
        // If no path specified, default directly into primary website's public_html
        if (empty($rawPath) && $activeSub) {
            $rawPath = "/{$activeSub->domain}/public_html";
        }

        $fullPath = $this->resolveSafePath($basePath, $rawPath);

        if (!File::exists($fullPath) || !is_dir($fullPath)) {
            // Fallback to base
            $fullPath = $basePath;
            $rawPath = '/';
        }

        // Always Return Inertia View for browse page requests
        return Inertia::render('Client/FileManager', [
            'subscriptions' => $subscriptions->map(fn($s) => [
                'id' => $s->id,
                'domain' => $s->domain,
                'username' => $s->username,
                'document_root' => $s->document_root,
                'websites' => $s->websites->pluck('domain'),
            ]),
            'currentSubscriptionId' => $activeSub?->id,
            'initialPath' => $rawPath,
            'initialFiles' => $this->scanDirectory($basePath, $fullPath, $rawPath),
        ]);
    }

    /**
     * Dedicated JSON endpoint for AJAX directory traversal.
     */
    public function listFiles(Request $request): JsonResponse
    {
        $subId = $request->get('subscription_id');
        [$basePath, $activeSub] = $this->getUserBaseDirectory($subId ? (int)$subId : null);

        $rawPath = $request->get('path', '');
        if (empty($rawPath) && $activeSub) {
            $rawPath = "/{$activeSub->domain}/public_html";
        }

        $fullPath = $this->resolveSafePath($basePath, $rawPath);

        if (!File::exists($fullPath) || !is_dir($fullPath)) {
            $fullPath = $basePath;
            $rawPath = '/';
        }

        return response()->json($this->scanDirectory($basePath, $fullPath, $rawPath));
    }

    /**
     * Helper to scan a directory and format items with permissions, sizes, and types.
     */
    protected function scanDirectory(string $basePath, string $fullPath, string $relativePath): array
    {
        $items = [];
        
        $directories = iterator_to_array(
            Finder::create()->directories()->ignoreDotFiles(false)->in($fullPath)->depth(0)->sortByName(),
            false
        );
        $files = iterator_to_array(
            Finder::create()->files()->ignoreDotFiles(false)->in($fullPath)->depth(0)->sortByName(),
            false
        );

        // Directories first
        foreach ($directories as $dir) {
            $name = $dir->getFilename();
            $itemRelPath = rtrim($relativePath, '/') . '/' . $name;
            $perms = substr(sprintf('%o', fileperms($dir->getPathname())), -4);

            $items[] = [
                'name' => $name,
                'path' => $itemRelPath,
                'type' => 'dir',
                'size' => 0,
                'permissions' => $perms,
                'modified' => $dir->getMTime(),
                'is_editable' => false,
                'is_archive' => false,
            ];
        }

        // Files
        $editableExtensions = ['html', 'htm', 'php', 'css', 'js', 'json', 'env', 'txt', 'sql', 'xml', 'htaccess', 'ini', 'md', 'yaml', 'yml', 'ts', 'vue', 'scss', 'sh', 'conf'];
        $archiveExtensions = ['zip', 'tar', 'gz', 'bz2', 'tgz', 'rar', '7z'];
        $imageExtensions = ['png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'ico', 'bmp'];
        $mediaExtensions = ['mp4', 'webm', 'mp3', 'wav', 'ogg', 'mov', 'pdf'];

        $totalBytes = 0;
        foreach ($files as $file) {
            $name = $file->getFilename();
            $ext = strtolower($file->getExtension());
            $itemRelPath = rtrim($relativePath, '/') . '/' . $name;
            $perms = substr(sprintf('%o', fileperms($file->getPathname())), -4);
            $size = $file->getSize();
            $totalBytes += $size;

            $items[] = [
                'name' => $name,
                'path' => $itemRelPath,
                'type' => 'file',
                'extension' => $ext,
                'size' => $size,
                'permissions' => $perms,
                'modified' => $file->getMTime(),
                'is_editable' => in_array($ext, $editableExtensions) || str_starts_with($name, '.'),
                'is_archive' => in_array($ext, $archiveExtensions),
                'is_image' => in_array($ext, $imageExtensions),
                'is_media' => in_array($ext, $mediaExtensions),
            ];
        }

        return [
            'current_path' => $relativePath ?: '/',
            'total_items' => count($items),
            'total_dirs' => count($directories),
            'total_files' => count($files),
            'total_size' => $totalBytes,
            'items' => $items,
        ];
    }

    /**
     * Upload single or multiple files into current directory.
     */
    public function upload(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'files' => 'nullable|array',
                'files.*' => 'file|max:524288', // Up to 512MB per file
                'file' => 'nullable|file|max:524288',
                'path' => 'required|string',
                'subscription_id' => 'nullable|integer',
            ]);

            [$basePath, $activeSub] = $this->getUserBaseDirectory($request->input('subscription_id'));
            $targetDir = $this->resolveSafePath($basePath, $request->input('path'));

            if (!is_dir($targetDir)) {
                @mkdir($targetDir, 0775, true);
                @chown($targetDir, 'www-data');
            }

            $uploadedFiles = [];
            if ($request->hasFile('files')) {
                $uploadedFiles = $request->file('files');
            } elseif ($request->hasFile('file')) {
                $uploadedFiles = [$request->file('file')];
            }

            if (empty($uploadedFiles)) {
                return response()->json([
                    'success' => false,
                    'error' => 'No files were received by the server. Please check file size limits.',
                ], 422);
            }

            $count = 0;
            foreach ($uploadedFiles as $uploaded) {
                if (!$uploaded->isValid()) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Upload error: ' . $uploaded->getErrorMessage(),
                    ], 422);
                }

                $originalName = $uploaded->getClientOriginalName();
                $uploaded->move($targetDir, $originalName);
                @chmod($targetDir . '/' . $originalName, 0664);
                @chown($targetDir . '/' . $originalName, 'www-data');
                $count++;
            }

            return response()->json([
                'success' => true,
                'message' => "Successfully uploaded {$count} file(s).",
            ]);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            $errors = collect($ve->errors())->flatten()->join(', ');
            return response()->json([
                'success' => false,
                'error' => $errors ?: 'Validation failed.',
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => 'Upload failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a new folder.
     */
    public function createFolder(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'path' => 'required|string',
            'subscription_id' => 'nullable|integer',
        ]);

        [$basePath] = $this->getUserBaseDirectory($request->input('subscription_id'));
        $parentDir = $this->resolveSafePath($basePath, $request->input('path'));
        $folderName = trim(preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $request->input('name')));

        if (empty($folderName)) {
            return response()->json(['success' => false, 'error' => 'Invalid folder name.'], 422);
        }

        $newDir = $parentDir . '/' . $folderName;
        if (File::exists($newDir)) {
            return response()->json(['success' => false, 'error' => 'Folder already exists.'], 422);
        }

        File::makeDirectory($newDir, 0775, true, true);
        @chown($newDir, 'www-data');

        return response()->json(['success' => true, 'message' => "Folder `{$folderName}` created."]);
    }

    /**
     * Create a new file (e.g. index.php, .htaccess).
     */
    public function createFile(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'path' => 'required|string',
            'subscription_id' => 'nullable|integer',
        ]);

        [$basePath] = $this->getUserBaseDirectory($request->input('subscription_id'));
        $parentDir = $this->resolveSafePath($basePath, $request->input('path'));
        $fileName = trim($request->input('name'));

        if (empty($fileName)) {
            return response()->json(['success' => false, 'error' => 'Invalid file name.'], 422);
        }

        $newFilePath = $parentDir . '/' . $fileName;
        if (File::exists($newFilePath)) {
            return response()->json(['success' => false, 'error' => 'File already exists.'], 422);
        }

        File::put($newFilePath, '');
        @chmod($newFilePath, 0664);
        @chown($newFilePath, 'www-data');

        return response()->json(['success' => true, 'message' => "File `{$fileName}` created."]);
    }

    /**
     * Rename a file or folder.
     */
    public function rename(Request $request): JsonResponse
    {
        $request->validate([
            'old_path' => 'required|string',
            'new_name' => 'required|string|max:100',
            'subscription_id' => 'nullable|integer',
        ]);

        [$basePath] = $this->getUserBaseDirectory($request->input('subscription_id'));
        $oldFull = $this->resolveSafePath($basePath, $request->input('old_path'));
        $newName = trim($request->input('new_name'));

        $newFull = dirname($oldFull) . '/' . $newName;

        if (File::exists($newFull)) {
            return response()->json(['success' => false, 'error' => 'Target name already exists.'], 422);
        }

        rename($oldFull, $newFull);

        return response()->json(['success' => true, 'message' => 'Renamed successfully.']);
    }

    /**
     * Delete file or directory.
     */
    public function delete(Request $request): JsonResponse
    {
        $request->validate([
            'path' => 'required|string',
            'subscription_id' => 'nullable|integer',
        ]);

        [$basePath] = $this->getUserBaseDirectory($request->input('subscription_id'));
        $target = $this->resolveSafePath($basePath, $request->input('path'));

        // Prevent deleting user base root
        if (realpath($target) === realpath($basePath)) {
            return response()->json(['success' => false, 'error' => 'Cannot delete root directory.'], 403);
        }

        if (is_dir($target)) {
            File::deleteDirectory($target);
        } else {
            File::delete($target);
        }

        return response()->json(['success' => true, 'message' => 'Item deleted.']);
    }

    /**
     * Read content of a file for editing.
     */
    public function edit(Request $request): JsonResponse
    {
        $request->validate([
            'path' => 'required|string',
            'subscription_id' => 'nullable|integer',
        ]);

        [$basePath] = $this->getUserBaseDirectory($request->input('subscription_id'));
        $target = $this->resolveSafePath($basePath, $request->input('path'));

        if (!File::exists($target) || is_dir($target)) {
            return response()->json(['success' => false, 'error' => 'File not found.'], 404);
        }

        // Limit file read to 5MB to prevent memory exhaustion
        if (File::size($target) > 5 * 1024 * 1024) {
            return response()->json(['success' => false, 'error' => 'File is too large to open in the browser editor (Max 5MB).'], 422);
        }

        $content = File::get($target);

        return response()->json([
            'success' => true,
            'name' => basename($target),
            'content' => $content,
        ]);
    }

    /**
     * Save updated code content back to file.
     */
    public function saveFile(Request $request): JsonResponse
    {
        $request->validate([
            'path' => 'required|string',
            'content' => 'present|string',
            'subscription_id' => 'nullable|integer',
        ]);

        [$basePath] = $this->getUserBaseDirectory($request->input('subscription_id'));
        $target = $this->resolveSafePath($basePath, $request->input('path'));

        File::put($target, $request->input('content'));
        @chmod($target, 0664);
        @chown($target, 'www-data');

        return response()->json(['success' => true, 'message' => 'File saved successfully.']);
    }

    /**
     * Download a file.
     */
    public function download(Request $request): BinaryFileResponse
    {
        $request->validate([
            'path' => 'required|string',
            'subscription_id' => 'nullable|integer',
        ]);

        [$basePath] = $this->getUserBaseDirectory($request->input('subscription_id'));
        $target = $this->resolveSafePath($basePath, $request->input('path'));

        if (!File::exists($target) || is_dir($target)) {
            abort(404, 'File not found');
        }

        return response()->download($target, basename($target));
    }

    /**
     * Extract a ZIP archive into the current directory.
     */
    public function unzip(Request $request): JsonResponse
    {
        $request->validate([
            'path' => 'required|string',
            'subscription_id' => 'nullable|integer',
        ]);

        [$basePath] = $this->getUserBaseDirectory($request->input('subscription_id'));
        $zipFile = $this->resolveSafePath($basePath, $request->input('path'));

        if (!File::exists($zipFile) || is_dir($zipFile)) {
            return response()->json(['success' => false, 'error' => 'Archive not found.'], 404);
        }

        $destDir = dirname($zipFile);

        $zip = new ZipArchive();
        if ($zip->open($zipFile) === true) {
            $zip->extractTo($destDir);
            $zip->close();
            return response()->json(['success' => true, 'message' => 'Archive extracted successfully.']);
        }

        return response()->json(['success' => false, 'error' => 'Failed to extract ZIP archive.'], 500);
    }

    /**
     * Compress a file or folder into a ZIP archive.
     */
    public function zip(Request $request): JsonResponse
    {
        $request->validate([
            'path' => 'required|string',
            'subscription_id' => 'nullable|integer',
        ]);

        [$basePath] = $this->getUserBaseDirectory($request->input('subscription_id'));
        $target = $this->resolveSafePath($basePath, $request->input('path'));

        $zipName = basename($target) . '.zip';
        $zipPath = dirname($target) . '/' . $zipName;

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return response()->json(['success' => false, 'error' => 'Cannot create ZIP archive.'], 500);
        }

        if (is_dir($target)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($target, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($files as $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relative = substr($filePath, strlen($target) + 1);
                    $zip->addFile($filePath, $relative);
                }
            }
        } else {
            $zip->addFile($target, basename($target));
        }

        $zip->close();

        return response()->json(['success' => true, 'message' => "Created archive `{$zipName}`."]);
    }

    /**
     * Change permissions (chmod) on file or folder.
     */
    public function chmodItem(Request $request): JsonResponse
    {
        $request->validate([
            'path' => 'required|string',
            'permissions' => 'required|string',
            'subscription_id' => 'nullable|integer',
            'recursive' => 'nullable|boolean',
        ]);

        [$basePath] = $this->getUserBaseDirectory($request->input('subscription_id'));
        $target = $this->resolveSafePath($basePath, $request->input('path'));
        $permsStr = ltrim($request->input('permissions'), '0');
        if (strlen($permsStr) < 3) {
            $permsStr = str_pad($permsStr, 3, '0', STR_PAD_LEFT);
        }
        $mode = octdec($permsStr);

        if (!File::exists($target)) {
            return response()->json(['success' => false, 'error' => 'Item not found.'], 404);
        }

        if ($request->boolean('recursive') && is_dir($target)) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($target, \FilesystemIterator::SKIP_DOTS));
            foreach ($iterator as $item) {
                @chmod($item->getRealPath(), $mode);
            }
        }
        @chmod($target, $mode);

        return response()->json(['success' => true, 'message' => "Permissions updated to `0{$permsStr}`."]);
    }

    /**
     * Move file or folder.
     */
    public function moveItem(Request $request): JsonResponse
    {
        $request->validate([
            'source_path' => 'required|string',
            'target_dir' => 'required|string',
            'subscription_id' => 'nullable|integer',
        ]);

        [$basePath] = $this->getUserBaseDirectory($request->input('subscription_id'));
        $source = $this->resolveSafePath($basePath, $request->input('source_path'));
        $destDir = $this->resolveSafePath($basePath, $request->input('target_dir'));

        if (!File::exists($source) || !is_dir($destDir)) {
            return response()->json(['success' => false, 'error' => 'Invalid source or destination directory.'], 404);
        }

        $dest = rtrim($destDir, '/') . '/' . basename($source);
        if (File::exists($dest)) {
            return response()->json(['success' => false, 'error' => 'Destination item already exists.'], 422);
        }

        rename($source, $dest);
        return response()->json(['success' => true, 'message' => "Moved to `{$request->input('target_dir')}` successfully."]);
    }

    /**
     * Copy file or folder.
     */
    public function copyItem(Request $request): JsonResponse
    {
        $request->validate([
            'source_path' => 'required|string',
            'target_dir' => 'required|string',
            'subscription_id' => 'nullable|integer',
        ]);

        [$basePath] = $this->getUserBaseDirectory($request->input('subscription_id'));
        $source = $this->resolveSafePath($basePath, $request->input('source_path'));
        $destDir = $this->resolveSafePath($basePath, $request->input('target_dir'));

        if (!File::exists($source) || !is_dir($destDir)) {
            return response()->json(['success' => false, 'error' => 'Invalid source or destination.'], 404);
        }

        $dest = rtrim($destDir, '/') . '/' . basename($source);
        if (File::exists($dest)) {
            $dest = rtrim($destDir, '/') . '/copy_' . basename($source);
        }

        if (is_dir($source)) {
            File::copyDirectory($source, $dest);
        } else {
            File::copy($source, $dest);
        }
        @chown($dest, 'www-data');

        return response()->json(['success' => true, 'message' => "Copied to `{$request->input('target_dir')}` successfully."]);
    }

    /**
     * Bulk Delete multiple files / folders.
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate([
            'paths' => 'required|array',
            'paths.*' => 'string',
            'subscription_id' => 'nullable|integer',
        ]);

        [$basePath] = $this->getUserBaseDirectory($request->input('subscription_id'));
        $deletedCount = 0;

        foreach ($request->input('paths') as $relPath) {
            $target = $this->resolveSafePath($basePath, $relPath);
            if (realpath($target) === realpath($basePath)) {
                continue; // Prevent deleting root
            }
            if (is_dir($target)) {
                File::deleteDirectory($target);
                $deletedCount++;
            } elseif (File::exists($target)) {
                File::delete($target);
                $deletedCount++;
            }
        }

        return response()->json(['success' => true, 'message' => "{$deletedCount} item(s) deleted permanently."]);
    }

    /**
     * Bulk Zip multiple files / folders into one archive.
     */
    public function bulkZip(Request $request): JsonResponse
    {
        $request->validate([
            'paths' => 'required|array',
            'paths.*' => 'string',
            'archive_name' => 'nullable|string|max:100',
            'current_path' => 'required|string',
            'subscription_id' => 'nullable|integer',
        ]);

        [$basePath] = $this->getUserBaseDirectory($request->input('subscription_id'));
        $currentDir = $this->resolveSafePath($basePath, $request->input('current_path'));
        $archiveName = trim($request->input('archive_name') ?: 'archive_' . date('Ymd_His')) . '.zip';
        $zipPath = rtrim($currentDir, '/') . '/' . $archiveName;

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return response()->json(['success' => false, 'error' => 'Cannot create ZIP archive.'], 500);
        }

        foreach ($request->input('paths') as $relPath) {
            $target = $this->resolveSafePath($basePath, $relPath);
            if (is_dir($target)) {
                $files = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($target, \FilesystemIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::LEAVES_ONLY
                );
                foreach ($files as $file) {
                    if (!$file->isDir()) {
                        $filePath = $file->getRealPath();
                        $relative = basename($target) . '/' . substr($filePath, strlen($target) + 1);
                        $zip->addFile($filePath, $relative);
                    }
                }
            } elseif (File::exists($target)) {
                $zip->addFile($target, basename($target));
            }
        }

        $zip->close();
        @chown($zipPath, 'www-data');
        @chmod($zipPath, 0664);

        return response()->json(['success' => true, 'message' => "Archive `{$archiveName}` created successfully."]);
    }
}
