<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FtpAccount;
use App\Models\Subscription;
use App\Models\User;
use App\Services\FTPManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class AdminFtpController extends Controller
{
    /**
     * Display listing of all cluster & subscription FTP accounts.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $query = FtpAccount::query()
            ->with([
                'subscription' => function ($q) {
                    $q->select('id', 'user_id', 'plan_id', 'domain', 'username', 'document_root', 'status')
                        ->with([
                            'user:id,first_name,last_name,username,email,company',
                            'plan:id,name,max_ftp_accounts'
                        ]);
                }
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

        $ftpAccounts = $query->latest('created_at')->paginate(15)->withQueryString();

        // Telemetry stats
        $totalAccounts = FtpAccount::count();
        $activeAccounts = FtpAccount::where('status', 'active')->count();
        $suspendedAccounts = FtpAccount::where('status', 'suspended')->count();
        $totalSubscriptions = Subscription::where('status', 'active')->count();

        // Detect live FTP Port & Passive Port Range from vsftpd config
        $ftpPort = 21;
        $pasvPorts = '30000 - 31000';
        if (file_exists('/etc/vsftpd.conf') && is_readable('/etc/vsftpd.conf')) {
            $vsftpdConf = file_get_contents('/etc/vsftpd.conf');
            if (preg_match('/^\s*listen_port\s*=\s*(\d+)/m', $vsftpdConf, $pMatches)) {
                $ftpPort = (int) $pMatches[1];
            }
            if (preg_match('/^\s*pasv_min_port\s*=\s*(\d+)/m', $vsftpdConf, $minM) &&
                preg_match('/^\s*pasv_max_port\s*=\s*(\d+)/m', $vsftpdConf, $maxM)) {
                $pasvPorts = "{$minM[1]} - {$maxM[1]}";
            }
        }

        $stats = [
            'total_accounts' => $totalAccounts,
            'active_accounts' => $activeAccounts,
            'suspended_accounts' => $suspendedAccounts,
            'total_subscriptions' => $totalSubscriptions,
            'server_ip' => \App\Support\ServerHelper::getPublicIp(),
            'ftp_port' => $ftpPort,
            'passive_ports' => $pasvPorts,
            'daemon_status' => 'online',
        ];

        // Active subscriptions for creating new accounts
        $subscriptions = Subscription::where('status', 'active')
            ->with('user:id,first_name,last_name,username,email')
            ->select('id', 'user_id', 'domain', 'username', 'document_root')
            ->orderBy('domain')
            ->get();

        return Inertia::render('Admin/Files/Ftp/Index', [
            'ftpAccounts' => $ftpAccounts,
            'stats' => $stats,
            'subscriptions' => $subscriptions,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    /**
     * Provision a new FTP account for a subscription.
     */
    public function store(Request $request, FTPManager $ftpManager): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'subscription_id' => 'required|exists:subscriptions,id',
            'username' => 'required|string|max:32|alpha_dash|unique:ftp_accounts,username',
            'password' => 'required|string|min:8',
            'path' => 'nullable|string|max:255',
            'permissions' => 'required|string|in:readwrite,readonly',
        ]);

        $subscription = Subscription::findOrFail($validated['subscription_id']);

        $subPath = ltrim($validated['path'] ?? '', '/');
        $fullPath = "/var/www/vhosts/{$subscription->username}" . ($subPath ? "/{$subPath}" : '/public_html');

        $ftpManager->createFtpUser($validated['username'], $validated['password'], $fullPath);

        $account = FtpAccount::create([
            'subscription_id' => $subscription->id,
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
            'path' => $fullPath,
            'permissions' => $validated['permissions'],
            'status' => 'active',
        ]);

        return redirect()->route('admin.files.ftp')
            ->with('success', "FTP Account '{$account->username}' provisioned successfully.");
    }

    /**
     * Toggle FTP account status between active and suspended.
     */
    public function toggleStatus(FtpAccount $ftpAccount): RedirectResponse
    {
        $this->authorize('create', User::class);

        $newStatus = $ftpAccount->status === 'active' ? 'suspended' : 'active';
        $ftpAccount->update(['status' => $newStatus]);

        return redirect()->back()
            ->with('success', "FTP Account '{$ftpAccount->username}' status changed to {$newStatus}.");
    }

    /**
     * Change password for an FTP account.
     */
    public function changePassword(Request $request, FtpAccount $ftpAccount, FTPManager $ftpManager): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'password' => 'required|string|min:8',
        ]);

        $ftpManager->changePassword($ftpAccount->username, $validated['password']);

        $ftpAccount->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->back()
            ->with('success', "Password updated for FTP account '{$ftpAccount->username}'.");
    }

    /**
     * Delete an FTP account.
     */
    public function destroy(FtpAccount $ftpAccount, FTPManager $ftpManager): RedirectResponse
    {
        $this->authorize('create', User::class);

        $username = $ftpAccount->username;
        $ftpManager->deleteFtpUser($username);
        $ftpAccount->delete();

        return redirect()->route('admin.files.ftp')
            ->with('success', "FTP Account '{$username}' deleted successfully.");
    }

    /**
     * Update FTP daemon port and passive mode configuration from Web UI.
     */
    public function updateConfig(Request $request, FTPManager $ftpManager): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'ftp_port' => 'required|integer|min:1|max:65535',
            'pasv_min_port' => 'required|integer|min:1024|max:65535',
            'pasv_max_port' => 'required|integer|min:1024|max:65535|gte:pasv_min_port',
            'update_firewall' => 'nullable|boolean',
            'restart_service' => 'nullable|boolean',
        ]);

        $ftpManager->updateFtpConfig(
            (int) $validated['ftp_port'],
            (int) $validated['pasv_min_port'],
            (int) $validated['pasv_max_port'],
            (bool) ($validated['update_firewall'] ?? true),
            (bool) ($validated['restart_service'] ?? true)
        );

        return redirect()->route('admin.files.ftp')
            ->with('success', "FTP Server configured on Port {$validated['ftp_port']} (PASV {$validated['pasv_min_port']}-{$validated['pasv_max_port']}) and service reloaded.");
    }
}
