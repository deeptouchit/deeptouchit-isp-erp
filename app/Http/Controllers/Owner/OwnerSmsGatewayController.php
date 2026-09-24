<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\SmsLog;
use App\Services\Sms\SmsTrackerService;
use App\Services\SmsService;
use Illuminate\Http\Request;

class OwnerSmsGatewayController extends Controller
{
    protected SmsTrackerService $smsTracker;

    public function __construct(SmsTrackerService $smsTracker)
    {
        $this->smsTracker = $smsTracker;
    }

    public function index(Request $request)
    {
        $settings = Setting::whereNull('tenant_id')->pluck('value', 'key')->toArray();

        // Live Database SMS Logs with filtering
        $query = SmsLog::with('tenant');

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('recipient_phone', 'like', "%{$search}%")
                  ->orWhere('message_body', 'like', "%{$search}%")
                  ->orWhere('gateway_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('sms_type')) {
            $query->where('sms_type', $request->sms_type);
        }

        $realSmsLogs = $query->latest()->paginate(15)->withQueryString();

        // Compute Metrics
        $totalSmsCount = SmsLog::count();
        $totalSmsCost = (float) SmsLog::where('status', '!=', 'failed')->sum('total_cost');
        $deliveredSmsCount = SmsLog::where('status', 'delivered')->count();
        $failedSmsCount = SmsLog::where('status', 'failed')->count();

        $balanceInfo = SmsService::getBalance();

        return view('owner.sms-gateways.index', compact(
            'settings',
            'realSmsLogs',
            'totalSmsCount',
            'totalSmsCost',
            'deliveredSmsCount',
            'failedSmsCount',
            'balanceInfo'
        ));
    }

    public function checkBalance(?Request $request = null)
    {
        $request = $request ?? request();
        $provider = $request->input('provider');
        $balanceInfo = SmsService::getBalance($provider);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($balanceInfo);
        }

        if ($balanceInfo['success']) {
            return back()->with('success', "Live SMS Balance ({$balanceInfo['provider']}): {$balanceInfo['formatted']}");
        }

        return back()->with('error', "Unable to fetch live balance: " . ($balanceInfo['error'] ?? $balanceInfo['formatted']));
    }

    public function update(Request $request)
    {
        $inputs = $request->except(['_token']);

        // Checkbox event triggers handling
        $checkboxKeys = [
            'sms_trigger_new_tenant',
            'sms_trigger_invoice_generated',
            'sms_trigger_payment_received',
            'sms_trigger_expiry_alert',
            'sms_trigger_otp_2fa',
            'sms_trigger_network_alert',
        ];

        foreach ($checkboxKeys as $key) {
            $inputs[$key] = $request->has($key) ? '1' : '0';
        }

        foreach ($inputs as $key => $value) {
            Setting::set($key, (string)$value, 'sms', null);
        }

        return back()->with('success', 'SMS gateway configuration & event triggers saved successfully.');
    }

    public function test(Request $request)
    {
        $request->validate([
            'test_phone' => ['required', 'string'],
            'test_message' => ['required', 'string', 'max:500'],
        ]);

        $settings = Setting::whereNull('tenant_id')->pluck('value', 'key')->toArray();
        $provider = $request->input('test_provider', $settings['sms_provider'] ?? 'bulksmsbd');
        $phone = $request->test_phone;
        $message = $request->test_message;

        $result = SmsService::send($phone, $message, $provider);

        $providerName = strtoupper(str_replace('_', ' ', $result['provider']));
        $formattedNum = $result['numbers'];
        $latency = $result['latency'];
        $parts = $result['parts'];
        $status = $result['success'] ? 'delivered' : 'failed';

        // Record into database ledger
        $this->smsTracker->recordSms(
            null,
            $phone,
            $message,
            'test_sms',
            $providerName,
            $status,
            (float)($settings['sms_rate_per_part'] ?? 0.35),
            $result['response'],
            $result['success'] ? null : $result['response']
        );

        if ($result['success']) {
            return back()->with('success', "Live SMS test dispatched to '+{$formattedNum}' ({$parts} SMS part, {$latency}ms) via {$providerName} API. Provider Response: " . substr($result['response'], 0, 150));
        }

        return back()->with('error', "SMS Dispatch Error ({$latency}ms) via {$providerName}: " . $result['response']);
    }
}
