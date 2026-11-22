<?php

namespace App\Http\Controllers\Reseller;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\TenantActivityLog;
use App\Models\TenantReseller;
use App\Models\TenantResellerBandwidth;
use App\Models\TicketMessage;
use App\Models\User;
use App\Notifications\TicketActivityNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use App\Services\NotificationScopeService;
use App\Services\Network\MikrotikApiService;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResellerBandwidthController extends Controller
{
    /**
     * Resolve the active authenticated Reseller and Tenant.
     */
    protected function getResellerData(): array
    {
        $user = Auth::user();
        $reseller = $user->reseller;
        abort_unless($reseller && (int) $reseller->tenant_id === (int) $user->tenant_id, 403);

        return [$user, $reseller, $reseller->tenant];
    }

    private function allocation(TenantReseller $reseller): ?TenantResellerBandwidth
    {
        return TenantResellerBandwidth::where('tenant_id', $reseller->tenant_id)
            ->where('reseller_id', $reseller->id)
            ->with(['router' => fn ($query) => $query->where('tenant_id', $reseller->tenant_id)])
            ->where(function ($query) {
                $query->whereNull('notes')->orWhere('notes', '!=', 'Baseline dedicated wholesale pipeline')
                    ->orWhere('interface_name', '!=', 'ether1-wan')->orWhere('vlan_id', '!=', '100')
                    ->orWhere('total_bandwidth_mbps', '!=', 500);
            })
            ->orderByDesc('id')
            ->first();
    }

    public function index(): View
    {
        [, $reseller, $tenant] = $this->getResellerData();
        $bandwidth = $this->allocation($reseller);
        $pipelines = $this->pipelines($bandwidth);

        return view('reseller.bandwidth.index', compact('reseller', 'tenant', 'bandwidth', 'pipelines'));
    }

    public function trafficTelemetry(MikrotikApiService $api): JsonResponse
    {
        [, $reseller] = $this->getResellerData();
        $allocation = $this->allocation($reseller);
        $state = 'unconfigured';
        $point = null;

        if ($allocation?->router?->is_active && filled($allocation->mikrotik_queue_name) && $allocation->status !== 'SUSPENDED'
            && !TenantResellerBandwidth::where('router_id', $allocation->router_id)
                ->where('mikrotik_queue_name', $allocation->mikrotik_queue_name)
                ->where('id', '!=', $allocation->id)->exists()) {
            $key = $this->telemetryKey($allocation);
            $cache = Cache::get($key, []);
            if (($cache['checked_at'] ?? 0) > microtime(true) - 5) {
                $state = $cache['state'];
                $point = $cache['point'] ?? null;
            } else {
                $sample = Cache::lock("$key:lock", 25)->get(function () use ($key, $api, $allocation) {
                    $record = Cache::get($key, []);
                    if (($record['checked_at'] ?? 0) > microtime(true) - 5) {
                        return $record;
                    }

                    try {
                        $bytes = $api->getSimpleQueueBytes($allocation->router, $allocation->mikrotik_queue_name);
                    } catch (\Throwable $exception) {
                        report($exception);
                        $bytes = null;
                    }

                    $now = microtime(true);
                    $previous = $record['counter'] ?? null;
                    $entry = ['checked_at' => $now, 'state' => $bytes === null ? 'unavailable' : 'sampling'];
                    $entry['latest'] = $record['latest'] ?? null;
                    if ($bytes !== null) {
                        $entry['counter'] = $bytes + ['time' => $now];
                        $seconds = $now - ($previous['time'] ?? 0);
                        if ($previous && $seconds >= 1 && $seconds <= 60
                            && $bytes['download'] >= $previous['download'] && $bytes['upload'] >= $previous['upload']) {
                            $entry['point'] = [
                                'download' => round(($bytes['download'] - $previous['download']) * 8 / $seconds / 1000000, 2),
                                'upload' => round(($bytes['upload'] - $previous['upload']) * 8 / $seconds / 1000000, 2),
                                'timestamp' => now()->toIso8601String(),
                            ];
                            $entry['latest'] = $entry['point'];
                            $entry['state'] = 'live';
                        }
                    }
                    Cache::put($key, $entry, 120);

                    return $entry;
                });
                $state = $sample['state'] ?? 'sampling';
                $point = $sample['point'] ?? null;
            }
        }

        $capacity = (float) ($allocation?->total_bandwidth_mbps ?? 0);

        return response()->json([
            'state' => $state,
            'current_download' => $point['download'] ?? null,
            'current_upload' => $point['upload'] ?? null,
            'utilization_percent' => $point && $capacity > 0 ? round($point['download'] / $capacity * 100, 1) : null,
            'timestamp' => $point['timestamp'] ?? null,
        ])->header('Cache-Control', 'private, no-store');
    }

    private function telemetryKey(TenantResellerBandwidth $allocation): string
    {
        return 'reseller-bandwidth:' . hash('sha256', implode(':', [
            $allocation->tenant_id, $allocation->reseller_id, $allocation->id, $allocation->router_id, $allocation->mikrotik_queue_name,
        ]));
    }

    private function pipelines(?TenantResellerBandwidth $bandwidth): array
    {
        if (!$bandwidth) {
            return [];
        }

        return [
            'Global Internet' => (float) $bandwidth->global_bandwidth_mbps,
            'BDIX' => (float) $bandwidth->bdix_bandwidth_mbps,
            'CDN' => (float) $bandwidth->cdn_bandwidth_mbps,
            'GGC' => (float) $bandwidth->ggc_bandwidth_mbps,
            'FNA' => (float) $bandwidth->fna_bandwidth_mbps,
            'Other' => (float) $bandwidth->other_bandwidth_mbps,
        ];
    }

    /**
     * Submit Bandwidth Upgrade / Add-On Request directly to Host ISP Admin.
     */
    public function requestUpgrade(Request $request): JsonResponse|RedirectResponse
    {
        [$user, $reseller, $tenant] = $this->getResellerData();

        $validated = $request->validate([
            'upgrade_type' => ['required', 'string', 'in:additional_capacity,change_cir_ratio,emergency_boost,temporary_addon'],
            'requested_mbps' => ['required', 'numeric', 'min:5', 'max:50000'],
            'target_pipeline' => ['required', 'string', 'in:global_internet,bdix_local,cdn_cache,total_pool'],
            'effective_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::beginTransaction();
        try {
            $ticketNumber = 'TIC-BW-' . strtoupper(Str::random(3)) . '-' . rand(100, 999);
            $typeName = match ($validated['upgrade_type']) {
                'additional_capacity' => 'Permanent Capacity Upgrade',
                'emergency_boost' => 'Emergency 24h Bandwidth Boost',
                'change_cir_ratio' => 'CIR 1:1 Ratio Enhancement',
                'temporary_addon' => 'Temporary Festive Add-On',
                default => 'Bandwidth Upgrade',
            };

            $subject = "Bandwidth Request: {$typeName} (+{$validated['requested_mbps']} Mbps)";
            $messageBody = "Sub-ISP Partner '{$reseller?->name}' ({$reseller?->code}) has requested a bandwidth upgrade.\n\n" .
                "• Request Type: {$typeName}\n" .
                "• Requested Addition: +{$validated['requested_mbps']} Mbps\n" .
                "• Target Pipeline: " . strtoupper(str_replace('_', ' ', $validated['target_pipeline'])) . "\n" .
                "• Preferred Date: " . (($validated['effective_date'] ?? null) ?: 'Immediate / ASAP') . "\n" .
                "• Partner Notes: " . (($validated['notes'] ?? null) ?: 'No additional comments.');

            $ticket = SupportTicket::create([
                'ticket_number' => $ticketNumber,
                'tenant_id' => $tenant->id,
                'reseller_id' => $reseller?->id,
                'user_id' => $user->id,
                'department' => 'network',
                'priority' => 'high',
                'status' => 'open',
                'subject' => $subject,
                'last_reply_by' => 'tenant',
                'last_reply_at' => now(),
            ]);

            TicketMessage::create([
                'support_ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'sender_type' => 'tenant',
                'message' => $messageBody,
                'is_internal_note' => false,
            ]);

            // Activity Log
            try {
                TenantActivityLog::create([
                    'tenant_id' => $tenant->id,
                    'actor_type' => 'reseller',
                    'actor_id' => $user->id,
                    'actor_name' => $user->name ?? ($reseller?->name ?? 'Reseller Partner'),
                    'event_type' => 'bandwidth_upgrade_request',
                    'description' => "Reseller requested {$validated['requested_mbps']} Mbps bandwidth upgrade (Ticket #{$ticketNumber})",
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            } catch (\Throwable $e) {
                // Ignore
            }

            // Notify Tenant Admins
            $tenantAdmins = User::where('tenant_id', $tenant->id)
                ->where(function ($q) {
                    $q->whereNull('reseller_id')->orWhere('reseller_id', 0);
                })
                ->get();
            $tenantAdmins = app(NotificationScopeService::class)
                ->filterRecipients($tenantAdmins, 'ticket_activity');

            if ($tenantAdmins->isNotEmpty()) {
                try {
                    Notification::send($tenantAdmins, new TicketActivityNotification(
                        $ticket,
                        "Bandwidth Request (+{$validated['requested_mbps']} Mbps)",
                        "Partner '{$reseller?->name}' requested +{$validated['requested_mbps']} Mbps bandwidth.",
                        'ticket_created',
                        $user->name ?? 'Reseller Partner'
                    ));
                } catch (\Throwable $ne) {
                    // Graceful
                }
            }

            DB::commit();

            $successMsg = "ব্যান্ডউইথ আপগ্রেড অনুরোধ (+{$validated['requested_mbps']} Mbps) সফলভাবে পাঠানো হয়েছে (টিকেট #{$ticketNumber})।";

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $successMsg,
                    'ticket_id' => $ticket->id,
                    'ticket_number' => $ticketNumber,
                ]);
            }

            return redirect()->route('reseller.bandwidth.index')->with('success', $successMsg);
        } catch (\Throwable $e) {
            DB::rollBack();
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'অনুরোধ পাঠাতে ত্রুটি হয়েছে: ' . $e->getMessage()], 500);
            }
            return back()->withInput()->with('error', 'অনুরোধ পাঠাতে ত্রুটি হয়েছে: ' . $e->getMessage());
        }
    }

    public function exportCsv(): StreamedResponse
    {
        [, $reseller] = $this->getResellerData();
        $bandwidth = $this->allocation($reseller);
        $pipelines = $this->pipelines($bandwidth);
        $sample = $bandwidth ? Cache::get($this->telemetryKey($bandwidth))['latest'] ?? null : null;

        return response()->streamDownload(function () use ($pipelines, $sample, $bandwidth) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Pipeline', 'Allocated Mbps'], escape: '');
            foreach ($pipelines as $pipeline => $capacity) {
                fputcsv($handle, [$pipeline, $capacity], escape: '');
            }
            if ($bandwidth) {
                fputcsv($handle, ['Total', $bandwidth->total_bandwidth_mbps], escape: '');
            }
            if ($sample) {
                fputcsv($handle, [], escape: '');
                fputcsv($handle, ['Measured at', 'Download Mbps', 'Upload Mbps'], escape: '');
                fputcsv($handle, [$sample['timestamp'], $sample['download'], $sample['upload']], escape: '');
            }
            fclose($handle);
        }, 'reseller_bandwidth_' . now()->format('Ymd_His') . '.csv', ['Content-Type' => 'text/csv']);
    }

    public function printReport(): View
    {
        [, $reseller, $tenant] = $this->getResellerData();
        $bandwidth = $this->allocation($reseller);
        $pipelines = $this->pipelines($bandwidth);
        $sample = $bandwidth ? Cache::get($this->telemetryKey($bandwidth))['latest'] ?? null : null;

        return view('reseller.bandwidth.print', compact('reseller', 'tenant', 'bandwidth', 'pipelines', 'sample'));
    }
}
