<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\SmsLog;
use App\Models\Tenant;
use Illuminate\Http\Request;

class OwnerSmsLogController extends Controller
{
    /**
     * Display a listing of all system & tenant SMS transmission and consumption audit logs.
     */
    public function index(Request $request)
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'sms_type' => ['nullable', 'string', 'max:80'],
            'status' => ['nullable', 'in:delivered,sent,failed'],
            'tenant_id' => ['nullable', 'integer', 'exists:tenants,id'],
            'per_page' => ['nullable', 'integer', 'in:15,20,30,50,100'],
        ]);
        $query = SmsLog::with('tenant');

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('recipient_phone', 'like', "%{$search}%")
                  ->orWhere('message_body', 'like', "%{$search}%")
                  ->orWhere('gateway_name', 'like', "%{$search}%")
                  ->orWhereHas('tenant', function ($t) use ($search) {
                      $t->where('name', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('sms_type')) {
            $query->where('sms_type', $request->sms_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->tenant_id);
        }

        $logs = $query->latest()->paginate($request->integer('per_page', 25))->withQueryString();

        $totalSmsCount = SmsLog::count();
        $totalSmsCost = SmsLog::sum('total_cost');
        $deliveredSmsCount = SmsLog::where('status', 'delivered')->count();
        $failedSmsCount = SmsLog::where('status', 'failed')->count();
        $tenants = Tenant::orderBy('name')->get();

        return view('owner.sms_logs.index', compact(
            'logs',
            'tenants',
            'totalSmsCount',
            'totalSmsCost',
            'deliveredSmsCount',
            'failedSmsCount'
        ));
    }
}
