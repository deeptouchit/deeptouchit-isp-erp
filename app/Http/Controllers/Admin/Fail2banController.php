<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Security\Fail2banService;
use App\Services\Security\IpAllowlistService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class Fail2banController extends Controller
{
    protected Fail2banService $fail2banService;

    public function __construct(Fail2banService $fail2banService)
    {
        $this->fail2banService = $fail2banService;
    }

    /**
     * Display Fail2ban Intrusion Prevention System console.
     */
    public function index(Request $request): Response
    {
        $overview = $this->fail2banService->getOverview();

        $search = $request->query('search', '');
        $bannedIps = $overview['banned_ips'];

        if ($search && trim($search)) {
            $s = strtolower(trim($search));
            $bannedIps = array_values(array_filter($bannedIps, function ($item) use ($s) {
                return str_contains(strtolower($item['ip']), $s) || str_contains(strtolower($item['jail']), $s);
            }));
        }

        return Inertia::render('Admin/Security/Fail2ban', [
            'overview' => $overview,
            'jails' => $overview['jails'],
            'bannedIps' => $bannedIps,
            'stats' => $overview['stats'],
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Ban IP manually in Fail2ban jail.
     */
    public function ban(Request $request)
    {
        $validated = $request->validate([
            'jail' => 'required|string|max:100',
            'ip' => 'required|string|max:45',
        ]);

        $res = $this->fail2banService->banIp($validated['jail'], $validated['ip'], auth()->id());

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Unban IP from Fail2ban jail.
     */
    public function unban(Request $request)
    {
        $validated = $request->validate([
            'jail' => 'required|string|max:100',
            'ip' => 'required|string|max:45',
        ]);

        $res = $this->fail2banService->unbanIp($validated['jail'], $validated['ip'], auth()->id());

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Flush all bans in jail or all jails.
     */
    public function unbanAll(Request $request)
    {
        $jail = $request->input('jail');

        $res = $this->fail2banService->unbanAll($jail, auth()->id());

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Restart Fail2ban daemon.
     */
    public function restart()
    {
        $res = $this->fail2banService->restartDaemon(auth()->id());

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Elevate temporary Fail2ban ban into permanent kernel blocklist.
     */
    public function elevate(Request $request)
    {
        $validated = $request->validate([
            'jail' => 'required|string|max:100',
            'ip' => 'required|string|max:45',
        ]);

        $res = $this->fail2banService->elevateToPermanentBlock($validated['jail'], $validated['ip'], auth()->id());

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Whitelist an IP directly from Fail2ban.
     */
    public function whitelist(Request $request)
    {
        $validated = $request->validate([
            'jail' => 'required|string|max:100',
            'ip' => 'required|string|max:45',
        ]);

        $this->fail2banService->unbanIp($validated['jail'], $validated['ip'], auth()->id());

        app(IpAllowlistService::class)->allowIp([
            'ip_address' => $validated['ip'],
            'label' => "Whitelisted from Fail2ban {$validated['jail']}",
            'scope' => 'global',
            'duration' => 'permanent',
        ], auth()->id());

        return redirect()->back()->with('success', "IP `{$validated['ip']}` unbanned and added to trusted allowlist.");
    }
}
