<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\OwnerSmsGateway;
use App\Models\Setting;
use App\Models\SmsLog;
use App\Services\Sms\SmsTrackerService;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class OwnerSmsGatewayController extends Controller
{
    protected SmsTrackerService $smsTracker;

    public function __construct(SmsTrackerService $smsTracker)
    {
        $this->smsTracker = $smsTracker;
    }

    public function index(Request $request)
    {
        $settings = Setting::getGroup('sms');

        $gateways = OwnerSmsGateway::query()->latest('is_active')->latest()->get();
        $activeGateway = $gateways->firstWhere('is_active', true);
        $gatewaySmsCounts = SmsLog::query()
            ->selectRaw('gateway_name, COUNT(*) as total')
            ->groupBy('gateway_name')
            ->pluck('total', 'gateway_name');
        $normalizedSmsCounts = $gatewaySmsCounts->mapWithKeys(
            fn ($count, $name) => [strtolower(preg_replace('/[^a-z0-9]+/i', '', (string) $name)) => (int) $count]
        );
        $totalGatewayBalance = (float) $gateways->sum(fn (OwnerSmsGateway $gateway) => (float) ($gateway->last_balance ?? 0));

        foreach ($gateways as $gateway) {
            $gatewayNames = [
                $gateway->name,
                $gateway->provider,
                strtoupper(str_replace('_', ' ', $gateway->provider)),
            ];
            $gateway->sms_count = collect($gatewayNames)
                ->map(fn ($name) => strtolower(preg_replace('/[^a-z0-9]+/i', '', (string) $name)))
                ->map(fn ($name) => $normalizedSmsCounts[$name] ?? 0)
                ->max();
        }

        foreach (config('secure_settings.keys', []) as $secureKey) {
            if (array_key_exists($secureKey, $settings) && $settings[$secureKey] !== '') {
                $settings[$secureKey] = '••••••••••••';
            }
        }

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

        // Do not call an external provider during a normal page render.
        // Balance is fetched only through the protected, throttled action.
        $balanceInfo = [
            'success' => false,
            'formatted' => 'Use Check Balance to fetch live balance',
            'balance' => null,
        ];

        return view('owner.sms-gateways.index', compact(
            'gateways',
            'activeGateway',
            'totalGatewayBalance',
            'settings',
            'realSmsLogs',
            'totalSmsCount',
            'totalSmsCost',
            'deliveredSmsCount',
            'failedSmsCount',
            'balanceInfo'
        ));
    }

    public function storeGateway(Request $request)
    {
        $data = $this->validateGateway($request);
        $gateway = OwnerSmsGateway::create($data);

        if ($request->boolean('is_active')) {
            $this->activateGateway($gateway);
        }

        return back()->with('success', "SMS gateway '{$gateway->name}' was added successfully.");
    }

    public function updateGateway(Request $request, OwnerSmsGateway $gateway)
    {
        $data = $this->validateGateway($request, $gateway);
        $gateway->update($data);

        if ($request->boolean('is_active')) {
            $this->activateGateway($gateway->fresh());
        } elseif ($gateway->is_active && ! $request->boolean('keep_active')) {
            $gateway->update(['is_active' => false]);
        }

        return back()->with('success', "SMS gateway '{$gateway->name}' was updated successfully.");
    }

    public function activateGateway(OwnerSmsGateway $gateway)
    {
        DB::transaction(function () use ($gateway) {
            OwnerSmsGateway::where('id', '!=', $gateway->id)->update(['is_active' => false]);
            $gateway->update(['is_active' => true, 'last_test_error' => null]);
        });

        return request()->expectsJson()
            ? response()->json(['success' => true, 'message' => "{$gateway->name} is now active."])
            : back()->with('success', "SMS gateway '{$gateway->name}' is now active.");
    }

    public function deactivateGateway(OwnerSmsGateway $gateway)
    {
        if ($gateway->is_active) {
            $gateway->update(['is_active' => false]);
        }

        return back()->with('success', "SMS gateway '{$gateway->name}' was deactivated.");
    }

    public function destroyGateway(OwnerSmsGateway $gateway)
    {
        $wasActive = $gateway->is_active;
        $gateway->delete();

        if ($wasActive) {
            OwnerSmsGateway::latest()->first()?->update(['is_active' => true]);
        }

        return back()->with('success', 'SMS gateway removed successfully.');
    }

    private function validateGateway(Request $request, ?OwnerSmsGateway $gateway = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'provider' => ['required', Rule::in(SmsService::supportedProviders())],
            'sender_id' => ['nullable', 'string', 'max:100'],
            'custom_url' => ['nullable', 'string', 'max:1000', function (string $attribute, mixed $value, \Closure $fail): void {
                $candidate = preg_replace('/\{(?:number|phone|message|msg|text|api_key|token|sender_id|senderid)\}/', 'placeholder', (string) $value);
                if (! filter_var($candidate, FILTER_VALIDATE_URL) || ! preg_match('/^https?:\/\//i', $candidate)) {
                    $fail('The custom URL must be a valid HTTP or HTTPS URL.');
                }
            }],
            'sms_api_url' => ['nullable', 'url:http,https', 'max:1000'],
            'balance_api_url' => ['nullable', 'url:http,https', 'max:1000'],
            'api_key' => ['nullable', 'string', 'max:500'],
            'api_secret' => ['nullable', 'string', 'max:500'],
            'client_id' => ['nullable', 'string', 'max:255'],
            'rate_per_part' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'is_active' => ['nullable', 'boolean'],
            'keep_active' => ['nullable', 'boolean'],
        ]);

        $credentials = $gateway?->credentials ?? [];
        foreach (['api_key', 'api_secret', 'client_id'] as $key) {
            if (filled($data[$key] ?? null)) {
                $credentials[$key] = $data[$key];
            }
        }

        return [
            'name' => $data['name'],
            'provider' => $data['provider'],
            'sender_id' => $data['sender_id'] ?? null,
            'custom_url' => $data['custom_url'] ?? null,
            'sms_api_url' => $data['sms_api_url'] ?? ($gateway?->sms_api_url),
            'balance_api_url' => $data['balance_api_url'] ?? ($gateway?->balance_api_url),
            'credentials' => $credentials,
            'rate_per_part' => $data['rate_per_part'] ?? ($gateway?->rate_per_part ?? 0),
            'is_active' => false,
        ];
    }

    public function checkBalance(?Request $request = null)
    {
        $request = $request ?? request();
        $provider = $request->input('provider');
        if ($provider !== null && ! in_array($provider, SmsService::supportedProviders(), true)) {
            abort(422, 'Unsupported SMS provider.');
        }
        $balanceInfo = SmsService::getBalance($provider);

        $gateway = OwnerSmsGateway::where('is_active', true)->first();
        if ($gateway && ($provider === null || $gateway->provider === $provider)) {
            $balanceValue = $balanceInfo['balance'] ?? null;
            if ($balanceInfo['success'] && is_numeric(str_replace(',', '', (string) $balanceValue))) {
                $gateway->update([
                    'last_balance' => (float) str_replace(',', '', (string) $balanceValue),
                    'last_balance_checked_at' => now(),
                    'last_test_error' => null,
                ]);
            } elseif (! $balanceInfo['success']) {
                $gateway->update(['last_test_error' => $balanceInfo['error'] ?? $balanceInfo['formatted'] ?? 'Balance check failed']);
            }
        }

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
        $inputs = $request->validate([
            'sms_provider' => ['nullable', Rule::in(SmsService::supportedProviders())],
            'sms_sender_id' => ['nullable', 'string', 'max:20'],
            'sms_api_key' => ['nullable', 'string', 'max:255'],
            'sms_api_secret' => ['nullable', 'string', 'max:255'],
            'sms_client_id' => ['nullable', 'string', 'max:255'],
            'sms_custom_url' => ['nullable', 'url:http,https', 'max:1000'],
            'sms_rate_per_part' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'sms_template_invoice' => ['nullable', 'string', 'max:1000'],
            'sms_template_payment' => ['nullable', 'string', 'max:1000'],
            'sms_template_otp' => ['nullable', 'string', 'max:1000'],
        ]);

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
            if (Setting::isSecureKey($key) && ($value === '' || $value === '••••••••••••')) {
                unset($inputs[$key]);
                continue;
            }
            Setting::set($key, (string)$value, 'sms', null);
        }

        return back()->with('success', 'SMS gateway configuration & event triggers saved successfully.');
    }

    public function test(Request $request)
    {
        $request->validate([
            'test_phone' => ['required', 'regex:/^(?:\+?880|0)1[3-9]\d{8}(?:\s*,\s*(?:\+?880|0)1[3-9]\d{8})*$/'],
            'test_message' => ['required', 'string', 'max:500'],
            'test_provider' => ['nullable', Rule::in(SmsService::supportedProviders())],
        ]);

        $settings = Setting::getGroup('sms');
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
