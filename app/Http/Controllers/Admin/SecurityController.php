<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\IpBlock;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SecurityController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Security/Index', [
            'activityLogs' => ActivityLog::with('user')->latest()->paginate(20),
            'ipBlocks' => IpBlock::with('user')->latest()->get(),
        ]);
    }

    public function blockIp(Request $request)
    {
        $validated = $request->validate([
            'ip_address' => 'required|ip',
            'reason' => 'nullable|string',
        ]);

        IpBlock::create([
            'ip_address' => $validated['ip_address'],
            'reason' => $validated['reason'] ?? 'Manual Admin Block',
            'type' => 'system',
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'IP address successfully blocked.');
    }

    public function unblockIp(IpBlock $ipBlock)
    {
        $ipBlock->delete();
        return back()->with('success', 'IP address successfully unblocked.');
    }
}
