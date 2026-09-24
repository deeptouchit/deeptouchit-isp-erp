<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Services\Files\AdminFileManagerService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdminFileManagerController extends Controller
{
    protected AdminFileManagerService $fileManagerService;

    public function __construct(AdminFileManagerService $fileManagerService)
    {
        $this->fileManagerService = $fileManagerService;
    }

    /**
     * Display Administrator File Manager Console.
     */
    public function index(Request $request): Response
    {
        $scope = $request->query('scope', 'vhosts');
        $path = $request->query('path', '');

        $data = $this->fileManagerService->listDirectory($scope, $path);

        $availableScopes = [
            ['key' => 'vhosts', 'name' => 'Virtual Hosts Root (/var/www/vhosts)'],
            ['key' => 'deeptouchhost', 'name' => 'DeepTouch Host Panel App (/var/www/deeptouchhost)'],
            ['key' => 'logs', 'name' => 'System Logs (/var/log)'],
        ];

        $subscriptions = Subscription::select('id', 'domain', 'username')->orderBy('domain')->get();

        return Inertia::render('Admin/Files/Manager', [
            'scope' => $data['scope'],
            'currentPath' => $data['current_path'],
            'breadcrumbs' => $data['breadcrumbs'],
            'items' => $data['items'],
            'stats' => $data['stats'],
            'availableScopes' => $availableScopes,
            'subscriptions' => $subscriptions,
        ]);
    }

    /**
     * Create Folder.
     */
    public function createFolder(Request $request)
    {
        $validated = $request->validate([
            'scope' => 'required|string',
            'path' => 'nullable|string',
            'name' => 'required|string|max:255',
        ]);

        try {
            $res = $this->fileManagerService->createFolder(
                $validated['scope'],
                $validated['path'] ?? '',
                $validated['name'],
                auth()->id()
            );
            return redirect()->back()->with('success', $res['message']);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Create File.
     */
    public function createFile(Request $request)
    {
        $validated = $request->validate([
            'scope' => 'required|string',
            'path' => 'nullable|string',
            'name' => 'required|string|max:255',
            'content' => 'nullable|string',
        ]);

        try {
            $res = $this->fileManagerService->createFile(
                $validated['scope'],
                $validated['path'] ?? '',
                $validated['name'],
                $validated['content'] ?? '',
                auth()->id()
            );
            return redirect()->back()->with('success', $res['message']);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Upload Files.
     */
    public function upload(Request $request)
    {
        $request->validate([
            'scope' => 'required|string',
            'path' => 'nullable|string',
            'files' => 'required|array',
            'files.*' => 'required|file',
        ]);

        try {
            $res = $this->fileManagerService->uploadFiles(
                $request->input('scope'),
                $request->input('path') ?? '',
                $request->file('files'),
                auth()->id()
            );
            return redirect()->back()->with('success', $res['message']);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Rename File or Directory.
     */
    public function rename(Request $request)
    {
        $validated = $request->validate([
            'scope' => 'required|string',
            'old_path' => 'required|string',
            'new_name' => 'required|string|max:255',
        ]);

        try {
            $res = $this->fileManagerService->renameItem(
                $validated['scope'],
                $validated['old_path'],
                $validated['new_name'],
                auth()->id()
            );
            return redirect()->back()->with('success', $res['message']);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Delete File or Directory.
     */
    public function destroy(Request $request)
    {
        $validated = $request->validate([
            'scope' => 'required|string',
            'path' => 'required|string',
        ]);

        try {
            $res = $this->fileManagerService->deleteItem(
                $validated['scope'],
                $validated['path'],
                auth()->id()
            );
            return redirect()->back()->with('success', $res['message']);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Read File for Code Editor (AJAX JSON).
     */
    public function edit(Request $request): JsonResponse
    {
        $request->validate([
            'scope' => 'required|string',
            'path' => 'required|string',
        ]);

        try {
            $data = $this->fileManagerService->readFileContent($request->input('scope'), $request->input('path'));
            return response()->json(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Save File Content from Code Editor (AJAX JSON).
     */
    public function save(Request $request): JsonResponse
    {
        $request->validate([
            'scope' => 'required|string',
            'path' => 'required|string',
            'content' => 'present|string',
        ]);

        try {
            $res = $this->fileManagerService->saveFileContent(
                $request->input('scope'),
                $request->input('path'),
                $request->input('content', ''),
                auth()->id()
            );
            return response()->json(['success' => true, 'message' => $res['message']]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Change File Permissions (chmod).
     */
    public function permissions(Request $request)
    {
        $validated = $request->validate([
            'scope' => 'required|string',
            'path' => 'required|string',
            'mode' => 'required|string|regex:/^[0-7]{3,4}$/',
        ]);

        try {
            $res = $this->fileManagerService->changePermissions(
                $validated['scope'],
                $validated['path'],
                $validated['mode'],
                auth()->id()
            );
            return redirect()->back()->with('success', $res['message']);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Compress items into Zip archive.
     */
    public function compress(Request $request)
    {
        $validated = $request->validate([
            'scope' => 'required|string',
            'path' => 'nullable|string',
            'items' => 'required|array',
            'zip_name' => 'required|string|max:255',
        ]);

        try {
            $res = $this->fileManagerService->compressZip(
                $validated['scope'],
                $validated['path'] ?? '',
                $validated['items'],
                $validated['zip_name'],
                auth()->id()
            );
            return redirect()->back()->with('success', $res['message']);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Extract Zip archive.
     */
    public function extract(Request $request)
    {
        $validated = $request->validate([
            'scope' => 'required|string',
            'path' => 'required|string',
        ]);

        try {
            $res = $this->fileManagerService->extractZip(
                $validated['scope'],
                $validated['path'],
                auth()->id()
            );
            return redirect()->back()->with('success', $res['message']);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Download File.
     */
    public function download(Request $request): BinaryFileResponse
    {
        $scope = $request->query('scope', 'vhosts');
        $path = $request->query('path', '');

        $fullPath = $this->fileManagerService->resolveSafePath($scope, $path);

        return response()->download($fullPath);
    }
}
