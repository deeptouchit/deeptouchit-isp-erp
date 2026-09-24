<?php

namespace App\Services\Files;

use App\Models\ActivityLog;
use App\Traits\CommandExecutor;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use ZipArchive;

class AdminFileManagerService
{
    use CommandExecutor;

    protected array $allowedRoots = [
        'vhosts' => '/var/www/vhosts',
        'deeptouchhost' => '/var/www/deeptouchhost',
        'logs' => '/var/log',
    ];

    /**
     * Get root path from scope key.
     */
    public function getRootPath(string $scope = 'vhosts'): string
    {
        return $this->allowedRoots[$scope] ?? $this->allowedRoots['vhosts'];
    }

    /**
     * Resolve and validate full safe path preventing directory traversal.
     */
    public function resolveSafePath(string $scope, string $subPath = ''): string
    {
        $base = $this->getRootPath($scope);
        $cleanSubPath = str_replace(['../', '..\\', '..'], '', ltrim($subPath, '/\\'));
        $target = $base . ($cleanSubPath ? '/' . $cleanSubPath : '');

        $realBase = realpath($base) ?: $base;
        $realTarget = realpath($target);

        if ($realTarget !== false && !str_starts_with($realTarget, $realBase)) {
            throw new Exception("Access Denied: Path traversal detected outside root directory.");
        }

        return $target;
    }

    /**
     * List files and folders with rich metadata.
     */
    public function listDirectory(string $scope = 'vhosts', string $subPath = ''): array
    {
        $fullPath = $this->resolveSafePath($scope, $subPath);

        if (!File::exists($fullPath) || !is_dir($fullPath)) {
            // If subPath does not exist, fallback to base
            $fullPath = $this->getRootPath($scope);
            $subPath = '';
            if (!File::exists($fullPath)) {
                File::makeDirectory($fullPath, 0755, true, true);
            }
        }

        $base = $this->getRootPath($scope);
        $items = [];
        $totalBytes = 0;

        $directories = File::directories($fullPath);
        $files = File::files($fullPath);

        // Process Directories First
        foreach ($directories as $dir) {
            $name = basename($dir);
            $relPath = trim(str_replace($base, '', $dir), '/');
            $perms = substr(sprintf('%o', fileperms($dir)), -4);
            $ownerInfo = posix_getpwuid(fileowner($dir))['name'] ?? 'www-data';
            $groupInfo = posix_getgrgid(filegroup($dir))['name'] ?? 'www-data';
            $modified = filemtime($dir);
            $dirSize = $this->getDirectorySize($dir);
            $totalBytes += $dirSize;

            $items[] = [
                'name' => $name,
                'path' => $relPath,
                'type' => 'dir',
                'extension' => null,
                'size' => $dirSize,
                'size_formatted' => $this->formatFileSize($dirSize),
                'permissions' => $perms,
                'owner' => "{$ownerInfo}:{$groupInfo}",
                'modified_at' => $modified,
                'modified_formatted' => date('Y-m-d H:i:s', $modified),
                'is_editable' => false,
                'is_archive' => false,
                'is_image' => false,
            ];
        }

        // Process Files
        foreach ($files as $file) {
            $name = $file->getFilename();
            $pathName = $file->getPathname();
            $relPath = trim(str_replace($base, '', $pathName), '/');
            $size = $file->getSize();
            $totalBytes += $size;
            $ext = strtolower($file->getExtension());
            $perms = substr(sprintf('%o', $file->getPerms()), -4);
            $ownerInfo = posix_getpwuid($file->getOwner())['name'] ?? 'www-data';
            $groupInfo = posix_getgrgid($file->getGroup())['name'] ?? 'www-data';
            $modified = $file->getMTime();

            $isEditable = in_array($ext, [
                'php', 'html', 'htm', 'js', 'json', 'css', 'scss', 'vue',
                'txt', 'env', 'conf', 'config', 'ini', 'sql', 'md', 'xml',
                'htaccess', 'yaml', 'yml', 'sh', 'log'
            ]) || empty($ext);

            $isArchive = in_array($ext, ['zip', 'tar', 'gz', 'tgz', 'rar', '7z']);
            $isImage = in_array($ext, ['png', 'jpg', 'jpeg', 'svg', 'webp', 'gif', 'ico']);

            $items[] = [
                'name' => $name,
                'path' => $relPath,
                'type' => 'file',
                'extension' => $ext,
                'size' => $size,
                'size_formatted' => $this->formatFileSize($size),
                'permissions' => $perms,
                'owner' => "{$ownerInfo}:{$groupInfo}",
                'modified_at' => $modified,
                'modified_formatted' => date('Y-m-d H:i:s', $modified),
                'is_editable' => $isEditable,
                'is_archive' => $isArchive,
                'is_image' => $isImage,
            ];
        }

        // Generate Breadcrumb Trail
        $breadcrumbs = [['name' => 'Root', 'path' => '']];
        if (!empty($subPath)) {
            $parts = explode('/', trim($subPath, '/'));
            $accum = '';
            foreach ($parts as $part) {
                $accum .= ($accum ? '/' : '') . $part;
                $breadcrumbs[] = [
                    'name' => $part,
                    'path' => $accum,
                ];
            }
        }

        return [
            'scope' => $scope,
            'current_path' => trim($subPath, '/'),
            'breadcrumbs' => $breadcrumbs,
            'items' => $items,
            'stats' => [
                'total_items' => count($items),
                'directories_count' => count($directories),
                'files_count' => count($files),
                'total_size' => $totalBytes,
                'total_size_formatted' => $this->formatFileSize($totalBytes),
                'base_path' => $base,
            ],
        ];
    }

    /**
     * Create a new folder.
     */
    public function createFolder(string $scope, string $subPath, string $folderName, ?int $adminId = null): array
    {
        $folderName = trim($folderName);
        if (empty($folderName) || str_contains($folderName, '/') || str_contains($folderName, '\\')) {
            throw new Exception("Invalid folder name.");
        }

        $targetDir = $this->resolveSafePath($scope, $subPath) . '/' . $folderName;

        if (File::exists($targetDir)) {
            throw new Exception("Directory `{$folderName}` already exists.");
        }

        File::makeDirectory($targetDir, 0755, true, true);
        @chown($targetDir, 'www-data');
        @chgrp($targetDir, 'www-data');

        ActivityLog::create([
            'user_id' => $adminId ?: auth()->id() ?: 1,
            'action' => 'folder_created',
            'description' => "Created folder `{$folderName}` in `{$scope}:/{$subPath}`.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => ['scope' => $scope, 'sub_path' => $subPath, 'folder' => $folderName],
        ]);

        return ['success' => true, 'message' => "Folder `{$folderName}` created successfully."];
    }

    /**
     * Create a new file.
     */
    public function createFile(string $scope, string $subPath, string $fileName, string $content = '', ?int $adminId = null): array
    {
        $fileName = trim($fileName);
        if (empty($fileName) || str_contains($fileName, '/') || str_contains($fileName, '\\')) {
            throw new Exception("Invalid file name.");
        }

        $targetFile = $this->resolveSafePath($scope, $subPath) . '/' . $fileName;

        if (File::exists($targetFile)) {
            throw new Exception("File `{$fileName}` already exists.");
        }

        File::put($targetFile, $content);
        @chmod($targetFile, 0644);
        @chown($targetFile, 'www-data');
        @chgrp($targetFile, 'www-data');

        ActivityLog::create([
            'user_id' => $adminId ?: auth()->id() ?: 1,
            'action' => 'file_created',
            'description' => "Created file `{$fileName}` in `{$scope}:/{$subPath}`.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => ['scope' => $scope, 'sub_path' => $subPath, 'file' => $fileName],
        ]);

        return ['success' => true, 'message' => "File `{$fileName}` created successfully."];
    }

    /**
     * Upload uploaded files.
     */
    public function uploadFiles(string $scope, string $subPath, array $files, ?int $adminId = null): array
    {
        $targetDir = $this->resolveSafePath($scope, $subPath);
        $uploadedNames = [];

        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $name = $file->getClientOriginalName();
                $file->move($targetDir, $name);
                $uploadedNames[] = $name;
                @chmod($targetDir . '/' . $name, 0644);
                @chown($targetDir . '/' . $name, 'www-data');
                @chgrp($targetDir . '/' . $name, 'www-data');
            }
        }

        ActivityLog::create([
            'user_id' => $adminId ?: auth()->id() ?: 1,
            'action' => 'files_uploaded',
            'description' => "Uploaded " . count($uploadedNames) . " files to `{$scope}:/{$subPath}`.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => ['scope' => $scope, 'sub_path' => $subPath, 'files' => $uploadedNames],
        ]);

        return ['success' => true, 'message' => count($uploadedNames) . " file(s) uploaded successfully."];
    }

    /**
     * Rename file or folder.
     */
    public function renameItem(string $scope, string $oldPath, string $newName, ?int $adminId = null): array
    {
        $newName = trim($newName);
        if (empty($newName) || str_contains($newName, '/') || str_contains($newName, '\\')) {
            throw new Exception("Invalid new name.");
        }

        $source = $this->resolveSafePath($scope, $oldPath);
        if (!File::exists($source)) {
            throw new Exception("Target item not found.");
        }

        $destination = dirname($source) . '/' . $newName;

        if (File::exists($destination)) {
            throw new Exception("An item named `{$newName}` already exists.");
        }

        rename($source, $destination);

        ActivityLog::create([
            'user_id' => $adminId ?: auth()->id() ?: 1,
            'action' => 'file_renamed',
            'description' => "Renamed `{$oldPath}` to `{$newName}` in `{$scope}`.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => ['old_path' => $oldPath],
            'new_values' => ['new_name' => $newName],
        ]);

        return ['success' => true, 'message' => "Renamed to `{$newName}` successfully."];
    }

    /**
     * Delete file or directory.
     */
    public function deleteItem(string $scope, string $path, ?int $adminId = null): array
    {
        $target = $this->resolveSafePath($scope, $path);

        if (!File::exists($target)) {
            throw new Exception("Item does not exist.");
        }

        if (is_dir($target)) {
            File::deleteDirectory($target);
        } else {
            File::delete($target);
        }

        ActivityLog::create([
            'user_id' => $adminId ?: auth()->id() ?: 1,
            'action' => 'file_deleted',
            'description' => "Deleted `{$path}` in `{$scope}`.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => ['path' => $path],
            'new_values' => [],
        ]);

        return ['success' => true, 'message' => "Item deleted successfully."];
    }

    /**
     * Read file content for code editor.
     */
    public function readFileContent(string $scope, string $path): array
    {
        $target = $this->resolveSafePath($scope, $path);

        if (!File::exists($target) || is_dir($target)) {
            throw new Exception("File not found.");
        }

        if (filesize($target) > 5 * 1024 * 1024) {
            throw new Exception("File size exceeds 5MB limit for online editing.");
        }

        $content = File::get($target);
        $ext = strtolower(pathinfo($target, PATHINFO_EXTENSION));

        return [
            'name' => basename($target),
            'path' => $path,
            'extension' => $ext,
            'content' => $content,
            'size' => filesize($target),
            'size_formatted' => $this->formatFileSize(filesize($target)),
        ];
    }

    /**
     * Save updated file content.
     */
    public function saveFileContent(string $scope, string $path, string $content, ?int $adminId = null): array
    {
        $target = $this->resolveSafePath($scope, $path);

        if (!File::exists($target) || is_dir($target)) {
            throw new Exception("Target file does not exist.");
        }

        File::put($target, $content);

        ActivityLog::create([
            'user_id' => $adminId ?: auth()->id() ?: 1,
            'action' => 'file_saved',
            'description' => "Saved edits to `{$path}` in `{$scope}`.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => ['path' => $path, 'size' => strlen($content)],
        ]);

        return ['success' => true, 'message' => "File saved successfully."];
    }

    /**
     * Change permissions (chmod).
     */
    public function changePermissions(string $scope, string $path, string $mode, ?int $adminId = null): array
    {
        $target = $this->resolveSafePath($scope, $path);

        if (!File::exists($target)) {
            throw new Exception("Target item does not exist.");
        }

        $octal = octdec($mode);
        chmod($target, $octal);

        ActivityLog::create([
            'user_id' => $adminId ?: auth()->id() ?: 1,
            'action' => 'permissions_changed',
            'description' => "Changed permissions of `{$path}` to `{$mode}` in `{$scope}`.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => ['path' => $path, 'mode' => $mode],
        ]);

        return ['success' => true, 'message' => "Permissions changed to {$mode}."];
    }

    /**
     * Compress items into Zip archive.
     */
    public function compressZip(string $scope, string $subPath, array $itemNames, string $zipName, ?int $adminId = null): array
    {
        $dir = $this->resolveSafePath($scope, $subPath);
        $zipName = trim($zipName);
        if (!str_ends_with(strtolower($zipName), '.zip')) {
            $zipName .= '.zip';
        }

        $zipPath = $dir . '/' . $zipName;
        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception("Cannot create zip archive.");
        }

        foreach ($itemNames as $name) {
            $itemPath = $dir . '/' . $name;
            if (is_dir($itemPath)) {
                $files = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($itemPath, \RecursiveDirectoryIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::SELF_FIRST
                );
                foreach ($files as $f) {
                    $localName = $name . '/' . substr($f->getPathname(), strlen($itemPath) + 1);
                    if ($f->isDir()) {
                        $zip->addEmptyDir($localName);
                    } else {
                        $zip->addFile($f->getPathname(), $localName);
                    }
                }
            } elseif (is_file($itemPath)) {
                $zip->addFile($itemPath, $name);
            }
        }

        $zip->close();
        @chmod($zipPath, 0644);
        @chown($zipPath, 'www-data');
        @chgrp($zipPath, 'www-data');

        ActivityLog::create([
            'user_id' => $adminId ?: auth()->id() ?: 1,
            'action' => 'archive_created',
            'description' => "Compressed " . count($itemNames) . " items into `{$zipName}` in `{$scope}:/{$subPath}`.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => ['zip' => $zipName, 'items' => $itemNames],
        ]);

        return ['success' => true, 'message' => "Archive `{$zipName}` created successfully."];
    }

    /**
     * Extract Zip archive.
     */
    public function extractZip(string $scope, string $zipPath, ?int $adminId = null): array
    {
        $fullZip = $this->resolveSafePath($scope, $zipPath);
        if (!File::exists($fullZip)) {
            throw new Exception("Archive file not found.");
        }

        $destDir = dirname($fullZip);

        $zip = new ZipArchive();
        if ($zip->open($fullZip) === true) {
            $zip->extractTo($destDir);
            $zip->close();
        } else {
            throw new Exception("Failed to open archive for extraction.");
        }

        ActivityLog::create([
            'user_id' => $adminId ?: auth()->id() ?: 1,
            'action' => 'archive_extracted',
            'description' => "Extracted archive `{$zipPath}` in `{$scope}`.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => ['zip' => $zipPath],
        ]);

        return ['success' => true, 'message' => "Archive extracted successfully."];
    }

    /**
     * Compute recursive size of a directory in bytes.
     */
    public function getDirectorySize(string $path): int
    {
        $size = 0;
        try {
            if (!is_dir($path)) {
                return 0;
            }
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($iterator as $item) {
                if ($item->isFile()) {
                    $size += $item->getSize();
                }
            }
        } catch (\Throwable $e) {
            // fallback
        }
        return $size;
    }

    /**
     * Format bytes into human readable size.
     */
    protected function formatFileSize(int $bytes): string
    {
        if ($bytes <= 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = floor(log($bytes, 1024));
        return round($bytes / pow(1024, $i), 2) . ' ' . ($units[$i] ?? 'B');
    }
}
