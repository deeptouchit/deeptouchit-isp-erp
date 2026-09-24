<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\FtpAccount;
use App\Models\Subscription;
use App\Services\FTPManager;
use App\Support\ServerHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class FTPController extends Controller
{
    /**
     * Display listing of client FTP accounts with quota tracking and connection telemetry.
     */
    public function index(): Response
    {
        $userId = auth()->id();
        $user = auth()->user();

        // 1. Fetch active subscriptions with plans and existing FTP accounts
        if ($user && $user->role === 'admin') {
            $subscriptions = Subscription::where('status', 'active')
                ->with(['plan', 'ftpAccounts', 'websites'])
                ->get();
            $totalUsed = FtpAccount::count();
            $ftpAccountsQuery = FtpAccount::with('subscription.plan');
        } else {
            $subscriptions = Subscription::where('user_id', $userId)
                ->where('status', 'active')
                ->with(['plan', 'ftpAccounts', 'websites'])
                ->get();
            $totalUsed = FtpAccount::whereHas('subscription', fn($q) => $q->where('user_id', $userId))->count();
            $ftpAccountsQuery = FtpAccount::whereHas('subscription', fn($q) => $q->where('user_id', $userId))->with('subscription.plan');
        }

        // 2. Strict FTP Account Quota Calculation
        $totalAllowed = $subscriptions->sum(fn($sub) => (int) ($sub->plan->max_ftp_accounts ?? 1));
        $canAddMore = $subscriptions->contains(function ($sub) {
            $allowed = (int) ($sub->plan->max_ftp_accounts ?? 1);
            return $sub->ftpAccounts->count() < $allowed;
        });

        $quota = [
            'total_allowed' => $totalAllowed,
            'total_used' => $totalUsed,
            'remaining' => max(0, $totalAllowed - $totalUsed),
            'can_add' => $canAddMore,
            'usage_percentage' => $totalAllowed > 0 ? min(100, round(($totalUsed / $totalAllowed) * 100)) : 100,
        ];

        // 3. Fetch all client FTP accounts
        $ftpAccounts = $ftpAccountsQuery
            ->latest()
            ->get()
            ->map(function ($account) {
                // Strip system vhost path for human-friendly display
                $vhostPrefix = "/var/www/vhosts/" . ($account->subscription?->username ?? '');
                $displayPath = str_replace($vhostPrefix, '', $account->path);
                if (empty($displayPath)) {
                    $displayPath = '/ (Root)';
                }

                return [
                    'id' => $account->id,
                    'username' => $account->username,
                    'full_path' => $account->path,
                    'display_path' => $displayPath,
                    'permissions' => $account->permissions ?? 'readwrite',
                    'status' => $account->status ?? 'active',
                    'created_at' => $account->created_at?->format('M d, Y'),
                    'subscription' => [
                        'id' => $account->subscription?->id,
                        'domain' => $account->subscription?->domain,
                        'plan_name' => $account->subscription?->plan?->name ?? 'Hosting Plan',
                    ],
                ];
            });

        // 4. Server connection reference
        $serverInfo = [
            'public_ip' => ServerHelper::getPublicIp(),
            'ftp_host' => ServerHelper::getPublicIp(),
            'primary_domain' => $subscriptions->first()?->domain ?? 'deeptouchit.com',
            'ftp_port' => 21,
            'passive_ports' => '30000 - 31000',
            'protocols' => 'FTP / FTPS (Explicit TLS/SSL)',
        ];

        return Inertia::render('Client/FTP/Index', [
            'ftpAccounts' => $ftpAccounts,
            'subscriptions' => $subscriptions->map(fn($s) => [
                'id' => $s->id,
                'domain' => $s->domain,
                'username' => $s->username,
                'plan_name' => $s->plan?->name,
                'max_ftp_accounts' => $s->plan?->max_ftp_accounts ?? 1,
                'used_ftp_accounts' => $s->ftpAccounts->count(),
                'can_add' => $s->ftpAccounts->count() < ($s->plan?->max_ftp_accounts ?? 1),
                'websites' => $s->websites->pluck('domain'),
            ]),
            'quota' => $quota,
            'serverInfo' => $serverInfo,
        ]);
    }

    /**
     * Provision a new FTP account locked strictly to user's vhost directory.
     */
    public function store(Request $request, FTPManager $ftpManager): RedirectResponse
    {
        $validated = $request->validate([
            'subscription_id' => 'required|exists:subscriptions,id',
            'username' => 'required|string|min:3|max:32|alpha_dash|unique:ftp_accounts,username',
            'password' => 'required|string|min:8',
            'path' => 'nullable|string|max:255',
            'permissions' => 'nullable|string|in:readwrite,readonly',
        ], [
            'username.alpha_dash' => 'Username may only contain letters, numbers, dashes and underscores (no spaces or @).',
            'password.min' => 'Password must be at least 8 characters long.',
            'username.unique' => 'An FTP account with this username already exists.',
        ]);

        $user = auth()->user();
        $subscription = Subscription::with(['plan', 'ftpAccounts'])->findOrFail($validated['subscription_id']);
        if ($user->role !== 'admin' && $subscription->user_id !== $user->id) {
            abort(403, 'Unauthorized access to subscription.');
        }

        // 1. STRICT FTP ACCOUNT QUOTA CHECK
        $maxAllowed = (int) ($subscription->plan->max_ftp_accounts ?? 1);
        $currentCount = $subscription->ftpAccounts()->count();

        if ($currentCount >= $maxAllowed) {
            return back()->withErrors([
                'username' => "Package FTP Quota Reached: Your hosting plan ({$subscription->plan->name}) permits a maximum of {$maxAllowed} FTP account(s). You have already created {$currentCount}/{$maxAllowed}. Please upgrade your hosting package to add more FTP users."
            ]);
        }

        // 2. Construct secure locked directory path
        $rawPath = trim($validated['path'] ?? 'public_html');
        $cleanPath = ltrim(str_replace(['../', '..\\'], '', $rawPath), '/');

        if (empty($cleanPath) || $cleanPath === '/' || $cleanPath === 'root') {
            $fullPath = "/var/www/vhosts/{$subscription->username}";
        } elseif ($cleanPath === 'public_html') {
            $fullPath = $subscription->document_root ?: "/var/www/vhosts/{$subscription->username}/{$subscription->domain}/public_html";
        } elseif (str_starts_with($cleanPath, $subscription->domain)) {
            $fullPath = "/var/www/vhosts/{$subscription->username}/{$cleanPath}";
        } elseif (str_starts_with($cleanPath, 'public_html/')) {
            $fullPath = "/var/www/vhosts/{$subscription->username}/{$subscription->domain}/{$cleanPath}";
        } else {
            $fullPath = "/var/www/vhosts/{$subscription->username}/{$cleanPath}";
        }

        if (!is_dir($fullPath)) {
            @mkdir($fullPath, 0775, true);
            @chown($fullPath, 'www-data');
        }

        // 3. Provision daemon virtual user
        $cleanUsername = strtolower(trim($validated['username']));
        
        try {
            $ftpManager->createFtpUser($cleanUsername, $validated['password'], $fullPath, $validated['permissions'] ?? 'readwrite');
        } catch (\Throwable $e) {
            return back()->withErrors([
                'username' => 'Failed to configure FTP user on system: ' . $e->getMessage()
            ]);
        }

        // 4. Record FTP account
        FtpAccount::create([
            'subscription_id' => $subscription->id,
            'username' => $cleanUsername,
            'password' => Hash::make($validated['password']),
            'path' => $fullPath,
            'permissions' => $validated['permissions'] ?? 'readwrite',
            'status' => 'active',
        ]);

        return redirect()->route('ftp-accounts.index')->with('success', "FTP user '{$cleanUsername}' created successfully.");
    }

    /**
     * Change FTP user password.
     */
    public function changePassword(Request $request, FtpAccount $ftpAccount, FTPManager $ftpManager): RedirectResponse
    {
        $this->authorizeAccess($ftpAccount);

        $validated = $request->validate([
            'password' => 'required|string|min:8',
        ]);

        $ftpManager->changePassword($ftpAccount->username, $validated['password']);

        $ftpAccount->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', "Password for FTP user '{$ftpAccount->username}' updated successfully.");
    }

    /**
     * Toggle FTP account between Active and Suspended.
     */
    public function toggleStatus(FtpAccount $ftpAccount): RedirectResponse
    {
        $this->authorizeAccess($ftpAccount);

        $newStatus = $ftpAccount->status === 'active' ? 'suspended' : 'active';
        $ftpAccount->update(['status' => $newStatus]);

        return back()->with('success', "FTP account '{$ftpAccount->username}' is now {$newStatus}.");
    }

    /**
     * Generate and download a FileZilla XML Configuration file for 1-click import.
     */
    public function filezillaXml(FtpAccount $ftpAccount): HttpResponse
    {
        $this->authorizeAccess($ftpAccount);

        $serverIp = ServerHelper::getPublicIp();
        $domain = $ftpAccount->subscription?->domain ?? $serverIp;
        $name = "DeepTouch Host - {$ftpAccount->username}";

        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<FileZilla3 version="3.0" platform="all">
    <Servers>
        <Server>
            <Host>{$serverIp}</Host>
            <Port>21</Port>
            <Protocol>0</Protocol>
            <Type>0</Type>
            <User>{$ftpAccount->username}</User>
            <Logontype>1</Logontype>
            <PasvMode>MODE_DEFAULT</PasvMode>
            <EncodingType>Auto</EncodingType>
            <Name>{$name}</Name>
            <Comments>Exported from DeepTouch Cloud Client Portal for {$domain}</Comments>
        </Server>
    </Servers>
</FileZilla3>
XML;

        return response($xml, 200, [
            'Content-Type' => 'application/xml',
            'Content-Disposition' => "attachment; filename=\"filezilla_{$ftpAccount->username}.xml\"",
        ]);
    }

    /**
     * Delete an FTP account.
     */
    public function destroy(FtpAccount $ftpAccount, FTPManager $ftpManager): RedirectResponse
    {
        $this->authorizeAccess($ftpAccount);

        $username = $ftpAccount->username;
        $ftpManager->deleteFtpUser($username);
        $ftpAccount->delete();

        return redirect()->route('ftp-accounts.index')->with('success', "FTP account '{$username}' deleted successfully.");
    }

    private function authorizeAccess(FtpAccount $ftpAccount): void
    {
        if (auth()->user()->role !== 'admin' && $ftpAccount->subscription->user_id !== auth()->id()) {
            abort(403);
        }
    }
}
