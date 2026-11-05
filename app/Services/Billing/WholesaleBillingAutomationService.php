<?php

namespace App\Services\Billing;

use App\Models\Tenant;
use App\Models\TenantReseller;
use App\Models\TenantResellerInvoice;
use App\Models\TenantResellerWalletTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WholesaleBillingAutomationService
{
    /**
     * Generate Monthly Wholesale Bills for a single Tenant
     */
    public function generateMonthlyBillsForTenant(
        Tenant $tenant,
        string $billingMonth,
        bool $autoDeductWallet = true,
        ?int $resellerId = null,
        ?int $userId = null
    ): array {
        $monthDate = Carbon::createFromFormat('Y-m', $billingMonth)->startOfMonth();
        $dueDate = $monthDate->copy()->addDays(10)->toDateString();

        $resellerQuery = TenantReseller::where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->with(['bandwidthAllocation']);

        if (!empty($resellerId)) {
            $resellerQuery->where('id', $resellerId);
        }

        $resellers = $resellerQuery->get();
        if ($resellers->isEmpty()) {
            return [
                'success' => false,
                'message' => 'No active resellers found for billing.',
                'generated_count' => 0,
                'total_billed' => 0.0,
                'total_paid' => 0.0,
                'skipped_count' => 0,
            ];
        }

        $generatedCount = 0;
        $skippedCount = 0;
        $totalBilled = 0.0;
        $totalPaid = 0.0;

        DB::beginTransaction();
        try {
            foreach ($resellers as $reseller) {
                // Check if wholesale bandwidth invoice already generated for this month
                $alreadyBilled = TenantResellerInvoice::where('tenant_id', $tenant->id)
                    ->where('reseller_id', $reseller->id)
                    ->where('type', 'BANDWIDTH_WHOLESALE')
                    ->whereDate('billing_month', $monthDate->toDateString())
                    ->exists();

                if ($alreadyBilled) {
                    $skippedCount++;
                    continue;
                }

                $bw = $reseller->bandwidthAllocation;
                $items = [];
                $subtotal = 0.0;

                // 1. Bandwidth line items
                if ($bw && $bw->status === 'ACTIVE') {
                    $rate = (float) ($bw->rate_per_mbps ?: 0);
                    $globalMbps = (float) ($bw->global_bandwidth_mbps ?: 0);
                    $bdixMbps = (float) ($bw->bdix_bandwidth_mbps ?: 0);
                    $ggcMbps = (float) ($bw->ggc_bandwidth_mbps ?: 0);
                    $fnaMbps = (float) ($bw->fna_bandwidth_mbps ?: 0);
                    $fixedMonthly = (float) ($bw->monthly_bill_amount ?: 0);

                    if ($fixedMonthly > 0) {
                        $items[] = [
                            'description' => "Dedicated Wholesale Bandwidth Trunk ({$bw->formatted_total_bandwidth} - {$bw->allocation_type})",
                            'qty' => 1,
                            'unit' => 'Month',
                            'unit_price' => $fixedMonthly,
                            'total' => $fixedMonthly,
                        ];
                        $subtotal += $fixedMonthly;
                    } else {
                        if ($globalMbps > 0) {
                            $lineCost = round($globalMbps * $rate, 2);
                            $items[] = [
                                'description' => "Global Internet Bandwidth (CIR Dedicated 1:1)",
                                'qty' => $globalMbps,
                                'unit' => 'Mbps',
                                'unit_price' => $rate,
                                'total' => $lineCost,
                            ];
                            $subtotal += $lineCost;
                        }
                        if ($bdixMbps > 0) {
                            $bdixRate = round($rate * 0.25, 2);
                            $lineCost = round($bdixMbps * $bdixRate, 2);
                            $items[] = [
                                'description' => "BDIX National Peering Bandwidth",
                                'qty' => $bdixMbps,
                                'unit' => 'Mbps',
                                'unit_price' => $bdixRate,
                                'total' => $lineCost,
                            ];
                            $subtotal += $lineCost;
                        }
                        if ($ggcMbps > 0) {
                            $cdnRate = round($rate * 0.15, 2);
                            $lineCost = round($ggcMbps * $cdnRate, 2);
                            $items[] = [
                                'description' => "Google GGC / YouTube Peering Cache",
                                'qty' => $ggcMbps,
                                'unit' => 'Mbps',
                                'unit_price' => $cdnRate,
                                'total' => $lineCost,
                            ];
                            $subtotal += $lineCost;
                        }
                        if ($fnaMbps > 0) {
                            $cdnRate = round($rate * 0.15, 2);
                            $lineCost = round($fnaMbps * $cdnRate, 2);
                            $items[] = [
                                'description' => "Facebook FNA Peering Cache",
                                'qty' => $fnaMbps,
                                'unit' => 'Mbps',
                                'unit_price' => $cdnRate,
                                'total' => $lineCost,
                            ];
                            $subtotal += $lineCost;
                        }
                    }
                }

                // 2. Panel Software Charge line item
                $panelCharge = (float) ($reseller->monthly_panel_charge ?: 0);
                if ($panelCharge > 0) {
                    $items[] = [
                        'description' => "Reseller SaaS Portal License & Cloud Software Maintenance",
                        'qty' => 1,
                        'unit' => 'Month',
                        'unit_price' => $panelCharge,
                        'total' => $panelCharge,
                    ];
                    $subtotal += $panelCharge;
                }

                // If no billable items and no panel fee, skip
                if (empty($items) || $subtotal <= 0) {
                    $skippedCount++;
                    continue;
                }

                $totalAmount = $subtotal;
                $invoiceNo = TenantResellerInvoice::generateInvoiceNo($tenant->id);

                // Check wallet auto debit
                $canDeduct = false;
                if ($autoDeductWallet && ((float)$reseller->wallet_balance + (float)$reseller->credit_limit) >= $totalAmount) {
                    $canDeduct = true;
                    $balBefore = (float) $reseller->wallet_balance;
                    $reseller->decrement('wallet_balance', $totalAmount);
                    $balAfter = (float) $reseller->wallet_balance;

                    TenantResellerWalletTransaction::create([
                        'tenant_id' => $tenant->id,
                        'reseller_id' => $reseller->id,
                        'trx_id' => TenantResellerWalletTransaction::generateTrxId($tenant->id),
                        'type' => 'DEBIT',
                        'amount' => $totalAmount,
                        'balance_before' => $balBefore,
                        'balance_after' => $balAfter,
                        'payment_method' => 'WALLET_DEDUCT',
                        'reference_no' => $invoiceNo,
                        'description' => "Automated monthly wholesale billing for " . $monthDate->format('F Y'),
                        'created_by' => $userId,
                    ]);
                    $totalPaid += $totalAmount;
                }

                TenantResellerInvoice::create([
                    'tenant_id' => $tenant->id,
                    'reseller_id' => $reseller->id,
                    'invoice_no' => $invoiceNo,
                    'billing_month' => $monthDate->toDateString(),
                    'type' => 'BANDWIDTH_WHOLESALE',
                    'subtotal' => $subtotal,
                    'discount' => 0.00,
                    'vat_tax' => 0.00,
                    'amount' => $totalAmount,
                    'paid_amount' => $canDeduct ? $totalAmount : 0.00,
                    'due_amount' => $canDeduct ? 0.00 : $totalAmount,
                    'payment_status' => $canDeduct ? 'PAID' : 'UNPAID',
                    'payment_method' => $canDeduct ? 'WALLET_DEDUCT' : null,
                    'paid_at' => $canDeduct ? now() : null,
                    'period_start' => $monthDate->toDateString(),
                    'period_end' => $monthDate->copy()->endOfMonth()->toDateString(),
                    'due_date' => $dueDate,
                    'notes' => "Monthly wholesale bandwidth & panel subscription for " . $monthDate->format('F Y'),
                    'item_details' => $items,
                    'created_by' => $userId,
                ]);

                $generatedCount++;
                $totalBilled += $totalAmount;
            }

            DB::commit();

            $currency = $tenant->currency_symbol ?? '৳';
            $msg = "Generated {$generatedCount} wholesale invoices for {$monthDate->format('F Y')} (Total: {$currency}" . number_format($totalBilled, 2) . ", Auto-Deducted: {$currency}" . number_format($totalPaid, 2) . ").";

            return [
                'success' => true,
                'message' => $msg,
                'generated_count' => $generatedCount,
                'total_billed' => $totalBilled,
                'total_paid' => $totalPaid,
                'skipped_count' => $skippedCount,
            ];

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("WholesaleBillingAutomationService Error [Tenant: {$tenant->id}]: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Billing generation failed: ' . $e->getMessage(),
                'generated_count' => 0,
                'total_billed' => 0.0,
                'total_paid' => 0.0,
                'skipped_count' => $skippedCount,
            ];
        }
    }

    /**
     * Process Wholesale Billing for all active Tenants (Master Cron Automation)
     */
    public function generateMonthlyBillsForAllTenants(?string $billingMonth = null, bool $autoDeductWallet = true): array
    {
        $month = $billingMonth ?: now()->format('Y-m');
        $tenants = Tenant::all();

        $totalGenerated = 0;
        $totalBilled = 0.0;
        $totalPaid = 0.0;
        $totalSkipped = 0;
        $tenantReports = [];

        foreach ($tenants as $tenant) {
            $report = $this->generateMonthlyBillsForTenant($tenant, $month, $autoDeductWallet);
            $totalGenerated += $report['generated_count'];
            $totalBilled += $report['total_billed'];
            $totalPaid += $report['total_paid'];
            $totalSkipped += $report['skipped_count'];
            $tenantReports[$tenant->id] = $report;
        }

        return [
            'month' => $month,
            'tenants_count' => $tenants->count(),
            'total_generated' => $totalGenerated,
            'total_billed' => $totalBilled,
            'total_paid' => $totalPaid,
            'total_skipped' => $totalSkipped,
            'details' => $tenantReports,
        ];
    }
}
