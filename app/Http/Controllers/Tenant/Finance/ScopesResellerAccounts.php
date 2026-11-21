<?php

namespace App\Http\Controllers\Tenant\Finance;

use App\Models\Tenant;
use App\Models\TenantCustomerPayment;
use App\Models\TenantExpenseCategory;
use App\Models\TenantExpenseTransaction;
use App\Models\TenantResellerInvoice;
use App\Models\TenantUpstreamPayment;
use Illuminate\Database\Eloquent\Builder;

trait ScopesResellerAccounts
{
    private function resellerAccountPortal(): bool
    {
        return request()->routeIs('reseller.finance.expenses*', 'reseller.finance.ledger*');
    }

    private function accountReseller(Tenant $tenant): int
    {
        $user = request()->user();
        abort_unless($user?->reseller && (int) $user->tenant_id === (int) $tenant->id
            && (int) $user->reseller->tenant_id === (int) $tenant->id, 403);

        return (int) $user->reseller_id;
    }

    private function accountQuery(string $model, int $tenantId): Builder
    {
        $query = $model::where('tenant_id', $tenantId);
        $isVoucherSource = in_array($model, [TenantExpenseCategory::class, TenantExpenseTransaction::class], true);

        if (!$this->resellerAccountPortal()) {
            return $isVoucherSource ? $query->whereNull('reseller_id') : $query;
        }

        $resellerId = (int) request()->user()->reseller_id;

        return match ($model) {
            TenantExpenseCategory::class, TenantExpenseTransaction::class, TenantResellerInvoice::class =>
                $query->where('reseller_id', $resellerId),
            TenantCustomerPayment::class => $query
                ->whereIn('status', ['paid', 'completed', 'approved', 'active'])
                ->where(fn ($payment) => $payment->where('reseller_id', $resellerId)->orWhereNull('reseller_id'))
                ->whereHas('customer', fn ($customer) => $customer
                    ->where('tenant_id', $tenantId)->where('reseller_id', $resellerId)),
            TenantUpstreamPayment::class => $query->whereRaw('1 = 0'),
        };
    }
}
