<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantActivityLog;
use Illuminate\Http\Request;

class OwnerActivityLogController extends Controller
{
    /**
     * Display a listing of all tenant activity and audit logs.
     */
    public function index(Request $request)
    {
        $query = TenantActivityLog::with('tenant');

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('actor_name', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhereHas('tenant', function ($t) use ($search) {
                      $t->where('name', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('event_type')) {
            $query->where('event_type', $request->event_type);
        }

        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->tenant_id);
        }

        $logs = $query->latest()->paginate(20)->withQueryString();

        $totalLogsCount = TenantActivityLog::count();
        $tenants = Tenant::orderBy('name')->get();

        return view('owner.activity_logs.index', compact(
            'logs',
            'tenants',
            'totalLogsCount'
        ));
    }
}
