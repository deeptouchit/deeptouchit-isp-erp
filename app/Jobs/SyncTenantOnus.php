<?php

namespace App\Jobs;

use App\Models\TenantOlt;
use App\Models\TenantOnu;
use App\Services\Network\OltApiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncTenantOnus implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public int $tries = 1;

    public function __construct(public int $tenantId, public string $operationId, public bool $linkOnly = false)
    {
        $this->onConnection('network')->onQueue('network');
    }

    public function handle(OltApiService $api): void
    {
        if (Cache::get('onu-operation-pending:'.$this->tenantId) !== $this->operationId) {
            $this->failed(new \RuntimeException('This queued operation has expired.'));
            return;
        }
        $lock = Cache::lock('onu-operation:'.$this->tenantId, 660);
        if (! $lock->get()) {
            $this->failed(new \RuntimeException('Another operation is running.'));

            return;
        }
        try {
            $this->status(['state' => 'running', 'success' => true]);
            $failed = 0;
            $failedDevices = [];
            if (! $this->linkOnly) {
                foreach (TenantOlt::where('tenant_id', $this->tenantId)->get() as $olt) {
                    try {
                        $result = $api->syncOlt($olt);
                        if (empty($result['success'])) {
                            $failed++;
                            $failedDevices[] = [
                                'id' => $olt->id,
                                'name' => $olt->name,
                                'message' => $result['message'] ?? __('Synchronization failed.'),
                            ];
                        }
                    } catch (Throwable $e) {
                        $failed++;
                        $failedDevices[] = [
                            'id' => $olt->id,
                            'name' => $olt->name,
                            'message' => __('Unexpected synchronization error.'),
                        ];
                        Log::warning('OLT sync failed', [
                            'olt_id' => $olt->id,
                            'exception' => get_class($e),
                        ]);
                    }
                }
            }
            $exit = Artisan::call('isp:auto-link-onus', ['--tenant' => $this->tenantId]);
            $query = TenantOnu::where('tenant_id', $this->tenantId);
            $this->status([
                'state' => $failed || $exit ? 'partial' : 'completed',
                'success' => ! $failed && ! $exit,
                'message' => $failed || $exit
                    ? __('Some devices could not be synchronized.')
                    : __('ONU operation completed.'),
                'failed_devices' => $failedDevices,
                'total_onus' => (clone $query)->count(),
                'online_onus' => (clone $query)->where('status', 'online')->count(),
                'linked_onus' => (clone $query)->whereNotNull('customer_id')->count(),
            ]);
        } finally {
            $lock->release();
            $this->clearPending();
        }
    }

    public function failed(?Throwable $exception): void
    {
        $this->status(['state' => 'failed', 'success' => false, 'message' => __('ONU operation failed. Please try again.')]);
        $this->clearPending();
    }

    private function clearPending(): void
    {
        $key = 'onu-operation-pending:'.$this->tenantId;
        if (Cache::get($key) === $this->operationId) {
            Cache::forget($key);
        }
    }

    private function status(array $status): void
    {
        Cache::put('onu-operation-status:'.$this->tenantId.':'.$this->operationId, $status, now()->addHour());
    }
}
