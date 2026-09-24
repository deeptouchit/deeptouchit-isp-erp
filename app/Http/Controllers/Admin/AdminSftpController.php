<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SftpUser;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class AdminSftpController extends Controller
{
    /**
     * Display listing of all SFTP / SSH user endpoints.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        // Seed default system SFTP users if table is empty
        if (SftpUser::count() === 0) {
            SftpUser::create([
                'username' => 'deeptouch',
                'auth_type' => 'both',
                'path' => '/home/deeptouch',
                'shell' => '/bin/bash',
                'permissions' => 'readwrite',
                'status' => 'active',
            ]);

            SftpUser::create([
                'username' => 'ftpuser',
                'auth_type' => 'password',
                'path' => '/var/www/vhosts',
                'shell' => '/usr/lib/openssh/sftp-server',
                'permissions' => 'readwrite',
                'status' => 'active',
            ]);
        }

        $query = SftpUser::query()
            ->with([
                'subscription' => function ($q) {
                    $q->select('id', 'user_id', 'plan_id', 'domain', 'username', 'document_root')
                        ->with(['user:id,first_name,last_name,username,email,company']);
                },
                'user:id,first_name,last_name,username,email'
            ]);

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                    ->orWhere('path', 'like', "%{$search}%")
                    ->orWhereHas('subscription', function ($sq) use ($search) {
                        $sq->where('domain', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%")
                            ->orWhereHas('user', function ($uq) use ($search) {
                                $uq->where('first_name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%")
                                    ->orWhere('email', 'like', "%{$search}%");
                            });
                    });
            });
        }

        // Status Filter
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Auth Type Filter
        if ($authType = $request->input('auth_type')) {
            $query->where('auth_type', $authType);
        }

        $sftpUsers = $query->latest('created_at')->paginate(15)->withQueryString();

        $totalUsers = SftpUser::count();
        $activeUsers = SftpUser::where('status', 'active')->count();
        $suspendedUsers = SftpUser::where('status', 'suspended')->count();
        $keyAuthCount = SftpUser::whereIn('auth_type', ['key', 'both'])->count();

        $stats = [
            'total_users' => $totalUsers,
            'active_users' => $activeUsers,
            'suspended_users' => $suspendedUsers,
            'key_auth_count' => $keyAuthCount,
            'server_ip' => \App\Support\ServerHelper::getPublicIp(),
            'ssh_port' => 22,
            'subsystem' => '/usr/lib/openssh/sftp-server',
            'daemon_status' => 'online',
        ];

        // Active subscriptions for creating scoped SFTP users
        $subscriptions = Subscription::where('status', 'active')
            ->with('user:id,first_name,last_name,username,email')
            ->select('id', 'user_id', 'domain', 'username', 'document_root')
            ->orderBy('domain')
            ->get();

        return Inertia::render('Admin/Files/Sftp/Index', [
            'sftpUsers' => $sftpUsers,
            'stats' => $stats,
            'subscriptions' => $subscriptions,
            'filters' => $request->only(['search', 'status', 'auth_type']),
        ]);
    }

    /**
     * Provision a new SFTP / SSH user.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'subscription_id' => 'nullable|exists:subscriptions,id',
            'username' => 'required|string|max:32|alpha_dash|unique:sftp_users,username',
            'auth_type' => 'required|string|in:password,key,both',
            'password' => 'required_if:auth_type,password,both|nullable|string|min:8',
            'public_key' => 'required_if:auth_type,key,both|nullable|string',
            'path' => 'nullable|string|max:255',
            'shell' => 'required|string|in:/usr/lib/openssh/sftp-server,/bin/bash,/bin/sh',
            'permissions' => 'required|string|in:readwrite,readonly',
        ]);

        $fullPath = '/var/www/vhosts';
        $userId = null;

        if (!empty($validated['subscription_id'])) {
            $subscription = Subscription::findOrFail($validated['subscription_id']);
            $userId = $subscription->user_id;
            $subPath = ltrim($validated['path'] ?? '', '/');
            $fullPath = "/var/www/vhosts/{$subscription->username}" . ($subPath ? "/{$subPath}" : '');
        } elseif (!empty($validated['path'])) {
            $fullPath = $validated['path'];
        }

        if (!is_dir($fullPath)) {
            @mkdir($fullPath, 0755, true);
        }

        $sftpUser = SftpUser::create([
            'subscription_id' => $validated['subscription_id'] ?? null,
            'user_id' => $userId,
            'username' => $validated['username'],
            'password' => !empty($validated['password']) ? Hash::make($validated['password']) : null,
            'auth_type' => $validated['auth_type'],
            'public_key' => $validated['public_key'] ?? null,
            'path' => $fullPath,
            'shell' => $validated['shell'],
            'permissions' => $validated['permissions'],
            'status' => 'active',
        ]);

        return redirect()->route('admin.files.sftp')
            ->with('success', "SFTP User '{$sftpUser->username}' provisioned successfully.");
    }

    /**
     * Toggle SFTP user status between active and suspended.
     */
    public function toggleStatus(SftpUser $sftpUser): RedirectResponse
    {
        $this->authorize('create', User::class);

        $newStatus = $sftpUser->status === 'active' ? 'suspended' : 'active';
        $sftpUser->update(['status' => $newStatus]);

        return redirect()->back()
            ->with('success', "SFTP User '{$sftpUser->username}' status changed to {$newStatus}.");
    }

    /**
     * Change password for an SFTP user.
     */
    public function changePassword(Request $request, SftpUser $sftpUser): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'password' => 'required|string|min:8',
        ]);

        $sftpUser->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->back()
            ->with('success', "Password updated for SFTP user '{$sftpUser->username}'.");
    }

    /**
     * Update SSH Public Key for an SFTP user.
     */
    public function updateKey(Request $request, SftpUser $sftpUser): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'public_key' => 'required|string',
        ]);

        $sftpUser->update([
            'public_key' => $validated['public_key'],
            'auth_type' => $sftpUser->password ? 'both' : 'key',
        ]);

        return redirect()->back()
            ->with('success', "SSH Public Key registered for '{$sftpUser->username}'.");
    }

    /**
     * Delete an SFTP user.
     */
    public function destroy(SftpUser $sftpUser): RedirectResponse
    {
        $this->authorize('create', User::class);

        $username = $sftpUser->username;
        $sftpUser->delete();

        return redirect()->route('admin.files.sftp')
            ->with('success', "SFTP User '{$username}' deleted successfully.");
    }
}
