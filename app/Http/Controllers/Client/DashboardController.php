<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Database;
use App\Models\EmailAccount;
use App\Models\FtpAccount;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\SupportTicket;
use App\Models\Website;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $userId = auth()->id();
        $user = auth()->user();

        // 1. Subscriptions / Hosting Plans
        $subscriptions = Subscription::with('plan', 'server')
            ->where('user_id', $userId)
            ->latest()
            ->get();

        $activeSubscription = $subscriptions->firstWhere('status', 'active') ?? $subscriptions->first();

        // 2. Websites
        $websites = Website::whereHas('subscription', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })->with('subscription.plan')->latest()->get();

        // 3. Databases
        $databases = Database::whereHas('subscription', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })->latest()->get();

        // 4. Email & FTP counts
        $emailAccountsCount = EmailAccount::whereHas('subscription', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })->count();

        $ftpAccountsCount = FtpAccount::whereHas('subscription', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })->count();

        // 5. Invoices & Billing
        $invoices = Invoice::where('user_id', $userId)->latest()->take(5)->get();
        $unpaidInvoicesCount = Invoice::where('user_id', $userId)->where('status', 'unpaid')->count();
        $unpaidAmount = Invoice::where('user_id', $userId)->where('status', 'unpaid')->sum('total_amount');

        // 6. Tickets
        $tickets = SupportTicket::where('user_id', $userId)->latest()->take(5)->get();
        $openTicketsCount = SupportTicket::where('user_id', $userId)->whereIn('status', ['open', 'in_progress', 'customer_reply'])->count();

        // Calculate quotas
        $diskLimit = $activeSubscription?->custom_disk_space 
            ?? $activeSubscription?->plan?->disk_space 
            ?? 5120;
        
        $diskUsed = 0;
        if ($activeSubscription && $activeSubscription->document_root && is_dir($activeSubscription->document_root)) {
            try {
                $docDir = dirname($activeSubscription->document_root);
                $targetDir = is_dir($docDir) ? $docDir : $activeSubscription->document_root;
                $output = shell_exec("du -sm " . escapeshellarg($targetDir) . " 2>/dev/null");
                if ($output && preg_match('/^(\d+)/', trim($output), $m)) {
                    $diskUsed = (int) $m[1];
                }
            } catch (\Throwable $e) {}
        }
        if ($diskUsed <= 0) {
            $diskUsed = 120; // Default minimal base usage MB
        }
        $diskUsagePercent = min(100, round(($diskUsed / max(1, $diskLimit)) * 100));

        return Inertia::render('Client/Dashboard', [
            'user' => $user,
            'activeSubscription' => $activeSubscription,
            'subscriptions' => $subscriptions,
            'websites' => $websites,
            'databases' => $databases,
            'serverInfo' => [
                'public_ip' => $activeSubscription?->server?->ip_address ?? '103.59.177.138',
                'primary_ns' => $activeSubscription?->server?->primary_ns ?? 'ns1.deeptouchit.com',
                'secondary_ns' => $activeSubscription?->server?->secondary_ns ?? 'ns2.deeptouchit.com',
                'hostname' => $activeSubscription?->server?->hostname ?? 'node1.deeptouchit.com',
            ],
            'stats' => [
                'websites_count' => $websites->count(),
                'databases_count' => $databases->count(),
                'emails_count' => $emailAccountsCount,
                'ftp_count' => $ftpAccountsCount,
                'unpaid_invoices_count' => $unpaidInvoicesCount,
                'unpaid_amount' => $unpaidAmount,
                'open_tickets_count' => $openTicketsCount,
                'disk_used_mb' => $diskUsed,
                'disk_limit_mb' => $diskLimit,
                'disk_usage_percent' => $diskUsagePercent,
            ],
            'recentInvoices' => $invoices,
            'recentTickets' => $tickets,
        ]);
    }

    /**
     * Display the Dedicated Suspended Account Page.
     */
    public function suspended(Request $request): Response
    {
        $user = auth()->user();
        $userId = $user->id;

        // If not suspended, redirect back to regular dashboard
        if ($user->status !== 'suspended' && $user->role !== 'admin') {
            return redirect()->route('client.dashboard');
        }

        // 1. Unpaid Invoices
        $unpaidInvoices = Invoice::where('user_id', $userId)
            ->where('status', 'sent')
            ->latest('due_date')
            ->get();

        $totalDue = $unpaidInvoices->sum(function ($inv) {
            return $inv->due_amount ?: $inv->total_amount;
        });

        // 2. Open Support Tickets
        $tickets = SupportTicket::where('user_id', $userId)
            ->latest('updated_at')
            ->take(5)
            ->get();

        // 3. Subscription Info
        $subscription = Subscription::with('plan')->where('user_id', $userId)->first();

        return Inertia::render('Client/Suspended', [
            'user' => [
                'id' => $user->id,
                'name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: $user->username,
                'email' => $user->email,
                'username' => $user->username,
                'phone' => $user->phone,
                'status' => $user->status,
                'created_at' => $user->created_at ? $user->created_at->format('M d, Y') : null,
            ],
            'subscription' => $subscription,
            'unpaidInvoices' => $unpaidInvoices,
            'totalDue' => $totalDue,
            'tickets' => $tickets,
            'supportInfo' => [
                'phone' => \App\Models\SystemSetting::get('general.support_phone', '+880 1977-799300'),
                'email' => \App\Models\SystemSetting::get('general.support_email', 'support@deeptouchit.com'),
                'whatsapp' => \App\Models\SystemSetting::get('general.whatsapp_number', '8801977799300'),
            ],
        ]);
    }
}
