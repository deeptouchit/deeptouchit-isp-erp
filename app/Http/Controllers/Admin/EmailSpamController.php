<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailDomain;
use App\Services\Email\EmailSpamService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmailSpamController extends Controller
{
    protected EmailSpamService $spamService;

    public function __construct(EmailSpamService $spamService)
    {
        $this->spamService = $spamService;
    }

    /**
     * Display SpamAssassin Protection & Filtering Console.
     */
    public function index(Request $request): Response
    {
        $setting = $this->spamService->getGlobalSettings();
        $daemonStatus = $this->spamService->getDaemonStatus();

        $whiteListArray = array_filter(array_map('trim', preg_split('/[\r\n,]+/', $setting->whitelist ?? '')));
        $blackListArray = array_filter(array_map('trim', preg_split('/[\r\n,]+/', $setting->blacklist ?? '')));

        $stats = [
            'required_score' => $setting->required_score,
            'is_active' => $setting->status === 'active',
            'whitelist_count' => count($whiteListArray),
            'blacklist_count' => count($blackListArray),
            'bayesian_active' => $setting->bayesian_filter_enabled,
        ];

        return Inertia::render('Admin/Email/Spam', [
            'setting' => $setting,
            'stats' => $stats,
            'daemonStatus' => $daemonStatus,
        ]);
    }

    /**
     * Update SpamAssassin protection settings.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'required_score' => 'required|numeric|min:1|max:20',
            'rewrite_subject' => 'nullable|boolean',
            'subject_tag' => 'nullable|string|max:64',
            'auto_delete_score' => 'nullable|numeric|min:5|max:50',
            'is_auto_delete_enabled' => 'nullable|boolean',
            'whitelist' => 'nullable|string|max:10000',
            'blacklist' => 'nullable|string|max:10000',
            'bayesian_filter_enabled' => 'nullable|boolean',
            'status' => 'required|in:active,disabled',
        ]);

        $setting = $this->spamService->getGlobalSettings();
        $res = $this->spamService->updateSettings($setting, $validated, auth()->id());

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Restart spam daemon.
     */
    public function restartDaemon()
    {
        $res = $this->spamService->restartDaemon(auth()->id());

        if ($res['success']) {
            return redirect()->back()->with('success', $res['message']);
        }

        return redirect()->back()->withErrors(['daemon' => $res['error']]);
    }
}
