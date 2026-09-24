<?php

namespace App\Services\Payment;

use App\Models\PaymentTransaction;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentWebhookService
{
    /**
     * Verify HMAC-SHA256 signature from incoming webhook request.
     */
    public function verifySignature(Request $request, string $gateway): bool
    {
        $payload = $request->getContent();
        $signature = $request->header('X-Signature') ?? $request->header('X-Webhook-Signature') ?? $request->header('Signature');
        $timestamp = (int) ($request->header('X-Timestamp') ?? $request->header('Timestamp') ?? 0);

        // In development/test mode without signature headers, allow fallback if explicitly enabled
        if (empty($signature)) {
            $allowUnsigned = Setting::get('payment_webhook_allow_unsigned_dev', '0') === '1';
            return $allowUnsigned;
        }

        // Replay Attack Protection: Reject timestamps older than 5 minutes (300 seconds)
        if ($timestamp > 0 && abs(time() - $timestamp) > 300) {
            Log::warning("Webhook Replay Attack rejected: Timestamp {$timestamp} expired.");
            return false;
        }

        $secret = Setting::get("{$gateway}_webhook_secret", config("services.{$gateway}.webhook_secret", ''));
        if (empty($secret)) {
            $secret = Setting::get("{$gateway}_app_secret", '');
        }

        if (empty($secret)) {
            Log::error("Webhook Secret not configured for gateway: {$gateway}");
            return false;
        }

        $dataToSign = $timestamp > 0 ? "{$timestamp}.{$payload}" : $payload;
        $expectedSignature = hash_hmac('sha256', $dataToSign, $secret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Process verified webhook payload with tenant isolation & row locking.
     */
    public function processWebhook(string $gateway, array $data, ?int $tenantId = null): array
    {
        $trxRef = $data['transaction_reference'] ?? $data['paymentID'] ?? $data['order_id'] ?? $data['mer_txnid'] ?? null;
        $status = strtolower($data['status'] ?? $data['transactionStatus'] ?? '');
        $gatewayTrxId = $data['trxID'] ?? $data['issuerPaymentRefNo'] ?? $data['bank_tran_id'] ?? $trxRef;
        $amount = isset($data['amount']) ? (float)$data['amount'] : null;

        if (!$trxRef) {
            return ['success' => false, 'message' => 'Missing transaction reference in payload.'];
        }

        return DB::transaction(function () use ($trxRef, $status, $gatewayTrxId, $amount, $gateway, $data, $tenantId) {
            $query = PaymentTransaction::where('transaction_reference', $trxRef)->lockForUpdate();
            if ($tenantId) {
                $query->where('tenant_id', $tenantId);
            }
            $transaction = $query->first();

            if (!$transaction) {
                Log::warning("Webhook received for unknown or unauthorized transaction reference: {$trxRef}");
                return ['success' => false, 'message' => 'Transaction not found or unauthorized.'];
            }

            // IDEMPOTENCY GUARD: If already processed, return 200 without duplicate action
            if ($transaction->status === 'successful') {
                return [
                    'success' => true,
                    'already_processed' => true,
                    'message' => 'Transaction already processed.',
                    'transaction' => $transaction,
                ];
            }

            // Amount Verification if amount is included in webhook payload
            if ($amount !== null && abs($amount - (float)$transaction->amount) > 0.01) {
                Log::error("Webhook Amount Mismatch for {$trxRef}: Expected {$transaction->amount}, Received {$amount}");
                return ['success' => false, 'message' => 'Amount mismatch.'];
            }

            if (in_array($status, ['success', 'successful', 'completed', '0000'])) {
                $transaction->update([
                    'status' => 'successful',
                    'completed_at' => now(),
                    'gateway_response' => array_merge((array)$transaction->gateway_response, $data),
                ]);

                // Settle Invoice accurately
                if ($transaction->invoice) {
                    app(\App\Services\Billing\InvoiceService::class)->recordPayment(
                        $transaction->invoice,
                        $transaction->amount,
                        ucfirst($gateway),
                        $gatewayTrxId,
                        $data
                    );
                }

                return [
                    'success' => true,
                    'message' => 'Transaction successfully processed and invoice settled.',
                    'transaction' => $transaction,
                ];
            } else {
                $transaction->update([
                    'status' => 'failed',
                    'gateway_response' => array_merge((array)$transaction->gateway_response, $data),
                ]);

                return [
                    'success' => true,
                    'message' => 'Transaction marked as failed per webhook notification.',
                    'transaction' => $transaction,
                ];
            }
        });
    }
}
