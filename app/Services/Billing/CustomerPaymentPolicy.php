<?php

namespace App\Services\Billing;

use Carbon\Carbon;

class CustomerPaymentPolicy
{
    /**
     * Calculate financial and service-validity effects without mutating persistence.
     * Discount may settle debt, but only collected cash can purchase validity.
     *
     * @return array{new_due: float, new_expiry: ?Carbon, can_reactivate: bool, validity_extended: bool}
     */
    public function calculate(
        float $oldDue,
        ?Carbon $oldExpiry,
        float $monthlyBill,
        string $mode,
        float $amountPaid,
        float $discount,
        int $months,
        bool $extendValidity,
        bool $reactivateRequested,
        ?Carbon $now = null,
    ): array {
        $now ??= Carbon::now();
        $oldDue = max(0, round($oldDue, 2));
        $amountPaid = max(0, round($amountPaid, 2));
        $discount = max(0, round($discount, 2));
        $monthlyBill = max(0, round($monthlyBill, 2));
        $months = max(1, $months);

        $newDue = (float) max(0, round($oldDue - ($amountPaid + $discount), 2));
        $newExpiry = $oldExpiry?->copy();
        $validityExtended = false;

        if ($extendValidity && $monthlyBill > 0) {
            $baseExpiry = $oldExpiry && $oldExpiry->greaterThan($now)
                ? $oldExpiry->copy()
                : $now->copy();

            if ($mode === 'due' && $amountPaid >= max($oldDue, $monthlyBill)) {
                $newExpiry = $baseExpiry->addMonth()->endOfDay();
                $validityExtended = true;
            } elseif ($mode === 'advance' && $newDue <= 0) {
                $requiredCash = $monthlyBill * $months;
                if ($amountPaid >= $requiredCash) {
                    $newExpiry = $baseExpiry->addMonths($months)->endOfDay();
                    $validityExtended = true;
                }
            } elseif ($mode === 'custom' && $newDue <= 0) {
                $cashAvailableForValidity = max(0, $amountPaid - $oldDue);
                $daysToAdd = (int) floor($cashAvailableForValidity / ($monthlyBill / 30));
                if ($daysToAdd > 0) {
                    $newExpiry = $baseExpiry->addDays($daysToAdd)->endOfDay();
                    $validityExtended = true;
                }
            }
        }

        $canReactivate = $reactivateRequested
            && $newDue <= 0
            && $newExpiry !== null
            && $newExpiry->greaterThan($now);

        return [
            'new_due' => $newDue,
            'new_expiry' => $newExpiry,
            'can_reactivate' => $canReactivate,
            'validity_extended' => $validityExtended,
        ];
    }
}
