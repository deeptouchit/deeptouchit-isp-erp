<?php

namespace App\Services\Network;

use App\Models\Tenant;
use App\Models\TenantCustomer;
use App\Models\TenantOnu;
use Illuminate\Support\Facades\DB;

class OnuLinkService
{
    public function link(TenantOnu $onu): bool
    {
        return $this->linkTenant($onu->tenant_id, $onu->id) > 0;
    }

    public function linkTenant(int $tenantId, ?int $onlyOnu = null): int
    {
        return DB::transaction(function () use ($tenantId, $onlyOnu) {
            // Serialize both manual and background linking for this tenant.
            Tenant::whereKey($tenantId)->lockForUpdate()->firstOrFail();
            $onus = TenantOnu::where('tenant_id', $tenantId)->get();
            $customers = TenantCustomer::where('tenant_id', $tenantId)->get()->keyBy('id');
            $index = [];
            foreach ($customers as $customer) {
                foreach ($this->customerKeys($customer) as $key) {
                    $index[$key][$customer->id] = $customer->id;
                }
            }
            $proposals = [];
            $claims = [];
            foreach ($onus as $onu) {
                $matches = [];
                foreach ($this->onuKeys($onu) as $key) {
                    $matches += $index[$key] ?? [];
                }
                if ($onu->customer_id) {
                    $matches[$onu->customer_id] = $onu->customer_id;
                }
                foreach ($matches as $id) {
                    $claims[$id][$onu->id] = true;
                }
                if (count($matches) === 1) {
                    $proposals[$onu->id] = reset($matches);
                }
            }
            $linked = 0;
            foreach ($onus as $onu) {
                if ($onu->customer_id || ($onlyOnu && $onu->id !== $onlyOnu)) {
                    continue;
                }
                $customerId = $proposals[$onu->id] ?? null;
                if (! $customerId || count($claims[$customerId]) !== 1) {
                    continue;
                }
                $customer = $customers[$customerId];
                $route = $onu->pon_port.':'.$onu->onu_id;
                $hasExactRouterMatch = $onu->router_mac
                    && $customer->mac_address
                    && $this->normalize($onu->router_mac) === $this->normalize($customer->mac_address);

                // A unique learned router MAC is authoritative when a
                // customer was moved from another OLT. Detach the stale ONU
                // binding before assigning the customer to the new route.
                if ($hasExactRouterMatch && $customer->olt_id && ($customer->olt_id != $onu->olt_id || $customer->pon_port !== $route)) {
                    TenantOnu::where('tenant_id', $tenantId)
                        ->where('customer_id', $customer->id)
                        ->where('id', '!=', $onu->id)
                        ->update(['customer_id' => null, 'pppoe_username' => null]);
                }
                $onu->updateQuietly([
                    'customer_id' => $customer->id,
                    'pppoe_username' => $customer->username,
                    'router_mac' => $onu->router_mac ?: $customer->mac_address,
                ]);
                $customer->updateQuietly([
                    'olt_id' => $onu->olt_id, 'pon_port' => $route,
                    'onu_mac_sn' => $onu->mac_address ?: $customer->onu_mac_sn,
                ]);
                $linked++;
            }

            return $linked;
        });
    }

    private function normalize(?string $value): string
    {
        return strtolower(preg_replace('/[:.\-\s]/', '', (string) $value));
    }

    private function customerKeys(TenantCustomer $customer): array
    {
        return array_filter([
            $customer->mac_address ? 'router:'.$this->normalize($customer->mac_address) : null,
            $customer->onu_mac_sn ? 'onu:'.$this->normalize($customer->onu_mac_sn) : null,
            $customer->username ? 'user:'.strtolower(trim($customer->username)) : null,
        ]);
    }

    private function onuKeys(TenantOnu $onu): array
    {
        $keys = [];
        if ($onu->router_mac) {
            $keys[] = 'router:'.$this->normalize($onu->router_mac);
        }
        if ($onu->mac_address) {
            $keys[] = 'onu:'.$this->normalize($onu->mac_address);
        }
        foreach ([$onu->pppoe_username, $onu->name, $onu->desc] as $username) {
            if ($username) {
                $keys[] = 'user:'.strtolower(trim($username));
            }
        }

        return $keys;
    }
}
