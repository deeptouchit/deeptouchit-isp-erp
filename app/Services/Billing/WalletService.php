<?php

namespace App\Services\Billing;

use App\Models\SaasInvoice;
use App\Models\Tenant;
use App\Models\TenantWallet;
use App\Models\TenantWalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

class WalletService
{
    public function __construct(
        protected InvoiceService $invoiceService
    ) {}

    /**
     * Get or create a wallet for a tenant with pessimistic locking.
     */
    public function getOrCreateWallet(Tenant $tenant, bool $lock = false): TenantWallet
    {
        $wallet = TenantWallet::firstOrCreate(
            ['tenant_id' => $tenant->id],
            ['balance' => 0.00, 'currency' => 'BDT', 'is_active' => true]
        );

        if ($lock) {
            return TenantWallet::where('id', $wallet->id)->lockForUpdate()->first();
        }

        return $wallet;
    }

    /**
     * Atomically credit funds into tenant wallet with pessimistic locking.
     */
    public function credit(
        Tenant $tenant,
        float $amount,
        string $source = 'manual_topup',
        ?string $reference = null,
        ?string $description = null,
        array $metadata = []
    ): TenantWalletTransaction {
        if ($amount <= 0) {
            throw new InvalidArgumentException("Credit amount must be greater than zero.");
        }

        return DB::transaction(function () use ($tenant, $amount, $source, $reference, $description, $metadata) {
            $wallet = $this->getOrCreateWallet($tenant, lock: true);

            $balanceBefore = (float)$wallet->balance;
            $balanceAfter = $balanceBefore + $amount;

            $wallet->balance = $balanceAfter;
            $wallet->save();

            $transaction = TenantWalletTransaction::create([
                'wallet_id' => $wallet->id,
                'tenant_id' => $tenant->id,
                'transaction_type' => 'credit',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'source' => $source,
                'reference_id' => $reference,
                'description' => $description ?: "Wallet top-up of ৳" . number_format($amount, 2),
                'metadata' => $metadata,
            ]);

            Log::info("Tenant Wallet Credited: Tenant #{$tenant->id}, Amount: ৳{$amount}, Balance After: ৳{$balanceAfter}");

            return $transaction;
        });
    }

    /**
     * Atomically debit funds from tenant wallet with pessimistic locking and balance check.
     */
    public function debit(
        Tenant $tenant,
        float $amount,
        string $reason = 'subscription_renewal',
        ?string $reference = null,
        ?string $description = null,
        bool $allowNegative = false,
        array $metadata = []
    ): TenantWalletTransaction {
        if ($amount <= 0) {
            throw new InvalidArgumentException("Debit amount must be greater than zero.");
        }

        return DB::transaction(function () use ($tenant, $amount, $reason, $reference, $description, $allowNegative, $metadata) {
            $wallet = $this->getOrCreateWallet($tenant, lock: true);

            $balanceBefore = (float)$wallet->balance;

            if (!$allowNegative && $balanceBefore < $amount) {
                throw new RuntimeException("Insufficient wallet balance. Available: ৳" . number_format($balanceBefore, 2) . ", Required: ৳" . number_format($amount, 2));
            }

            $balanceAfter = $balanceBefore - $amount;

            $wallet->balance = $balanceAfter;
            $wallet->save();

            $transaction = TenantWalletTransaction::create([
                'wallet_id' => $wallet->id,
                'tenant_id' => $tenant->id,
                'transaction_type' => 'debit',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'source' => $reason,
                'reference_id' => $reference,
                'description' => $description ?: "Wallet debit for {$reason} (৳" . number_format($amount, 2) . ")",
                'metadata' => $metadata,
            ]);

            Log::info("Tenant Wallet Debited: Tenant #{$tenant->id}, Amount: ৳{$amount}, Balance After: ৳{$balanceAfter}");

            return $transaction;
        });
    }

    /**
     * Atomically settle an invoice using wallet balance with lockForUpdate().
     */
    public function settleInvoiceWithWallet(Tenant $tenant, SaasInvoice $invoice): array
    {
        return DB::transaction(function () use ($tenant, $invoice) {
            // Lock invoice and wallet simultaneously
            $lockedInvoice = SaasInvoice::where('id', $invoice->id)->lockForUpdate()->first();
            $wallet = $this->getOrCreateWallet($tenant, lock: true);

            if (!$lockedInvoice) {
                return ['success' => false, 'message' => 'Invoice not found'];
            }

            $dueAmount = (float)$lockedInvoice->calculated_due;
            if ($dueAmount <= 0) {
                return ['success' => true, 'message' => 'Invoice is already settled', 'paid_amount' => 0];
            }

            $availableBalance = (float)$wallet->balance;
            if ($availableBalance <= 0) {
                return ['success' => false, 'message' => 'Wallet balance is ৳0.00. Please top up your wallet.'];
            }

            // Determine payable amount
            $payableAmount = min($dueAmount, $availableBalance);

            // 1. Debit wallet
            $walletTx = $this->debit(
                tenant: $tenant,
                amount: $payableAmount,
                reason: 'invoice_settlement',
                reference: $lockedInvoice->invoice_number,
                description: "Paid invoice #{$lockedInvoice->invoice_number} via prepaid wallet balance"
            );

            // 2. Record payment on invoice
            $paymentResult = $this->invoiceService->recordPayment(
                invoice: $lockedInvoice,
                amount: $payableAmount,
                paymentMethod: 'wallet',
                transactionId: 'WAL-' . strtoupper(substr(md5((string)$walletTx->id . microtime()), 0, 10)),
                notes: 'Settled via Tenant Prepaid Wallet'
            );

            return [
                'success' => true,
                'message' => 'Successfully deducted ৳' . number_format($payableAmount, 2) . ' from wallet balance.',
                'paid_amount' => $payableAmount,
                'remaining_due' => (float)$lockedInvoice->fresh()->calculated_due,
                'wallet_balance' => (float)$wallet->fresh()->balance,
            ];
        });
    }
}
