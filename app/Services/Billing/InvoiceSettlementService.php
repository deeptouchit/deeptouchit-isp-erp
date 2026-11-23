<?php

namespace App\Services\Billing;

use App\Models\FinancialLedgerEntry;
use App\Models\PaymentTransaction;
use App\Models\SaasInvoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class InvoiceSettlementService
{
    public function settle(SaasInvoice $invoice, ?int $userId = null): SaasInvoice
    {
        return DB::transaction(function () use ($invoice, $userId): SaasInvoice {
            $invoice = SaasInvoice::query()->lockForUpdate()->findOrFail($invoice->id);
            if ($invoice->status === 'paid') {
                return $invoice;
            }
            if ($invoice->status === 'cancelled') {
                throw new RuntimeException('Cancelled invoices cannot be settled.');
            }

            $reference = 'MANUAL-'.$invoice->id.'-'.Str::upper(Str::random(12));
            $transaction = PaymentTransaction::create([
                'tenant_id' => $invoice->tenant_id,
                'saas_invoice_id' => $invoice->id,
                'payment_method' => 'manual',
                'gateway' => 'owner_manual',
                'transaction_reference' => $reference,
                'amount' => $invoice->amount,
                'currency' => config('app.currency', 'BDT'),
                'status' => 'successful',
                'gateway_response' => ['source' => 'owner_manual_settlement'],
                'initiated_at' => now(),
                'completed_at' => now(),
            ]);

            FinancialLedgerEntry::create([
                'tenant_id' => $invoice->tenant_id,
                'saas_invoice_id' => $invoice->id,
                'payment_transaction_id' => $transaction->id,
                'entry_type' => 'invoice_settlement',
                'amount' => $invoice->amount,
                'currency' => config('app.currency', 'BDT'),
                'reference' => $reference,
                'metadata' => ['source' => 'owner_manual_settlement'],
                'created_by' => $userId,
            ]);

            $invoice->update([
                'status' => 'paid',
                'paid_at' => now(),
                'paid_amount' => $invoice->amount,
                'due_amount' => 0,
                'trx_id' => $reference,
                'payment_method' => 'manual',
            ]);

            return $invoice->fresh();
        });
    }
}
