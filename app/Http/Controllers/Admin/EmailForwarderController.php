<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailDomain;
use App\Models\EmailForwarder;
use App\Models\Subscription;
use App\Services\Email\EmailForwarderService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmailForwarderController extends Controller
{
    protected EmailForwarderService $forwarderService;

    public function __construct(EmailForwarderService $forwarderService)
    {
        $this->forwarderService = $forwarderService;
    }

    /**
     * Display all email forwarders & aliases.
     */
    public function index(Request $request): Response
    {
        $domainFilter = $request->query('domain');
        $statusFilter = $request->query('status');
        $search = $request->query('search');

        $query = EmailForwarder::with(['emailDomain', 'subscription.user'])->latest();

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
                $q->where('source', 'like', "%{$s}%")
                  ->orWhere('destination', 'like', "%{$s}%")
                  ->orWhereHas('subscription.user', fn($u) => $u->where('name', 'like', "%{$s}%"));
            });
        }

        $forwarders = $query->get();

        $multiRecipientCount = 0;
        $localCopyCount = 0;

        $forwardersData = $forwarders->map(function ($fwd) use (&$multiRecipientCount, &$localCopyCount) {
            $destList = $fwd->destinations_list;
            if (count($destList) > 1) {
                $multiRecipientCount++;
            }
            if ($fwd->keep_local_copy) {
                $localCopyCount++;
            }

            return [
                'id' => $fwd->id,
                'source' => $fwd->source,
                'destination' => $fwd->destination,
                'destinations_list' => $destList,
                'domain' => $fwd->emailDomain?->domain,
                'email_domain_id' => $fwd->email_domain_id,
                'keep_local_copy' => $fwd->keep_local_copy,
                'status' => $fwd->status,
                'subscription' => $fwd->subscription,
                'created_at' => $fwd->created_at?->toIso8601String(),
            ];
        });

        $stats = [
            'total_forwarders' => $forwarders->count(),
            'multi_recipient_count' => $multiRecipientCount,
            'local_copy_count' => $localCopyCount,
        ];

        $domains = EmailDomain::where('status', 'active')->get(['id', 'domain']);
        $subscriptions = Subscription::with('user')->where('status', 'active')->get(['id', 'domain', 'username', 'user_id']);

        return Inertia::render('Admin/Email/Forwarders', [
            'forwarders' => $forwardersData,
            'stats' => $stats,
            'domains' => $domains,
            'subscriptions' => $subscriptions,
            'currentDomain' => $domainFilter ?: 'all',
            'currentStatus' => $statusFilter ?: 'all',
        ]);
    }

    /**
     * Store a new forwarder.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'email_domain_id' => 'required|exists:email_domains,id',
            'source' => 'required|string|max:64|regex:/^[a-zA-Z0-9._-]+$/',
            'destination' => 'required|string|max:1000',
            'keep_local_copy' => 'nullable|boolean',
        ]);

        $res = $this->forwarderService->createForwarder($validated, auth()->id());

        if ($res['success']) {
            return redirect()->back()->with('success', $res['message']);
        }

        return redirect()->back()->withErrors(['source' => $res['error']]);
    }

    /**
     * Update forwarder destinations.
     */
    public function update(Request $request, EmailForwarder $emailForwarder)
    {
        $validated = $request->validate([
            'destination' => 'required|string|max:1000',
            'keep_local_copy' => 'nullable|boolean',
        ]);

        $res = $this->forwarderService->updateForwarder($emailForwarder, $validated, auth()->id());

        if ($res['success']) {
            return redirect()->back()->with('success', $res['message']);
        }

        return redirect()->back()->withErrors(['destination' => $res['error']]);
    }

    /**
     * Toggle status.
     */
    public function toggleStatus(EmailForwarder $emailForwarder)
    {
        $res = $this->forwarderService->toggleStatus($emailForwarder, auth()->id());

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Delete forwarder.
     */
    public function destroy(EmailForwarder $emailForwarder)
    {
        $res = $this->forwarderService->deleteForwarder($emailForwarder, auth()->id());

        return redirect()->back()->with('success', $res['message']);
    }
}
