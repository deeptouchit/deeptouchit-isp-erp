<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailAccount;
use App\Models\EmailAutoResponder;
use App\Models\EmailDomain;
use App\Models\Subscription;
use App\Services\Email\EmailAutoResponderService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmailAutoResponderController extends Controller
{
    protected EmailAutoResponderService $autoResponderService;

    public function __construct(EmailAutoResponderService $autoResponderService)
    {
        $this->autoResponderService = $autoResponderService;
    }

    /**
     * Display all auto-responders & vacation assistants.
     */
    public function index(Request $request): Response
    {
        $domainFilter = $request->query('domain');
        $statusFilter = $request->query('status');
        $search = $request->query('search');

        $query = EmailAutoResponder::with(['emailDomain', 'emailAccount', 'subscription.user'])->latest();

        if ($domainFilter && $domainFilter !== 'all') {
            $query->whereHas('emailDomain', function ($q) use ($domainFilter) {
                $q->where('domain', $domainFilter);
            });
        }

        if ($statusFilter && $statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        if ($search && trim($search)) {
            $s = trim($search);
            $query->where(function ($q) use ($s) {
                $q->where('email', 'like', "%{$s}%")
                  ->orWhere('subject', 'like', "%{$s}%")
                  ->orWhere('from_name', 'like', "%{$s}%")
                  ->orWhereHas('subscription.user', fn($u) => $u->where('name', 'like', "%{$s}%"));
            });
        }

        $autoResponders = $query->get();

        $activeCount = 0;
        $scheduledCount = 0;

        $autoRespondersData = $autoResponders->map(function ($ar) use (&$activeCount, &$scheduledCount) {
            if ($ar->is_currently_running) {
                $activeCount++;
            }
            if ($ar->starts_at || $ar->expires_at) {
                $scheduledCount++;
            }

            return [
                'id' => $ar->id,
                'email' => $ar->email,
                'from_name' => $ar->from_name,
                'subject' => $ar->subject,
                'body' => $ar->body,
                'is_html' => $ar->is_html,
                'interval_hours' => $ar->interval_hours,
                'starts_at' => $ar->starts_at?->format('Y-m-d H:i'),
                'expires_at' => $ar->expires_at?->format('Y-m-d H:i'),
                'domain' => $ar->emailDomain?->domain,
                'email_domain_id' => $ar->email_domain_id,
                'status' => $ar->status,
                'is_currently_running' => $ar->is_currently_running,
                'subscription' => $ar->subscription,
                'created_at' => $ar->created_at?->toIso8601String(),
            ];
        });

        $stats = [
            'total_auto_responders' => $autoResponders->count(),
            'active_count' => $activeCount,
            'scheduled_count' => $scheduledCount,
        ];

        $domains = EmailDomain::where('status', 'active')->get(['id', 'domain']);
        $accounts = EmailAccount::where('status', 'active')->get(['id', 'email', 'email_domain_id']);
        $subscriptions = Subscription::with('user')->where('status', 'active')->get(['id', 'domain', 'username', 'user_id']);

        return Inertia::render('Admin/Email/AutoResponders', [
            'autoResponders' => $autoRespondersData,
            'stats' => $stats,
            'domains' => $domains,
            'accounts' => $accounts,
            'subscriptions' => $subscriptions,
            'currentDomain' => $domainFilter ?: 'all',
            'currentStatus' => $statusFilter ?: 'all',
        ]);
    }

    /**
     * Store a new auto-responder.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'email_domain_id' => 'required|exists:email_domains,id',
            'email_prefix' => 'required|string|max:64|regex:/^[a-zA-Z0-9._-]+$/',
            'from_name' => 'nullable|string|max:191',
            'subject' => 'required|string|max:191',
            'body' => 'required|string|max:10000',
            'is_html' => 'nullable|boolean',
            'interval_hours' => 'nullable|integer|min:1|max:720',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
        ]);

        $res = $this->autoResponderService->createAutoResponder($validated, auth()->id());

        if ($res['success']) {
            return redirect()->back()->with('success', $res['message']);
        }

        return redirect()->back()->withErrors(['email_prefix' => $res['error']]);
    }

    /**
     * Update auto-responder.
     */
    public function update(Request $request, EmailAutoResponder $emailAutoResponder)
    {
        $validated = $request->validate([
            'from_name' => 'nullable|string|max:191',
            'subject' => 'required|string|max:191',
            'body' => 'required|string|max:10000',
            'is_html' => 'nullable|boolean',
            'interval_hours' => 'nullable|integer|min:1|max:720',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
        ]);

        $res = $this->autoResponderService->updateAutoResponder($emailAutoResponder, $validated, auth()->id());

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Toggle status.
     */
    public function toggleStatus(EmailAutoResponder $emailAutoResponder)
    {
        $res = $this->autoResponderService->toggleStatus($emailAutoResponder, auth()->id());

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Delete auto-responder.
     */
    public function destroy(EmailAutoResponder $emailAutoResponder)
    {
        $res = $this->autoResponderService->deleteAutoResponder($emailAutoResponder, auth()->id());

        return redirect()->back()->with('success', $res['message']);
    }
}
