<?php

namespace App\Services\Sms;

use App\Models\Setting;
use App\Models\SmsLog;
use App\Models\Tenant;

class SmsTrackerService
{
    /**
     * Determine whether text contains Unicode characters (e.g. Bangla / emojis).
     */
    public function isUnicode(string $text): bool
    {
        return strlen($text) !== mb_strlen($text, 'UTF-8');
    }

    /**
     * Calculate SMS parts count based on character encoding.
     */
    public function calculateParts(string $text): array
    {
        $charCount = mb_strlen($text, 'UTF-8');
        $isUnicode = $this->isUnicode($text);

        if ($isUnicode) {
            // Unicode standard: 70 chars for 1 part, 67 chars per part for multi-part
            if ($charCount <= 70) {
                $parts = 1;
            } else {
                $parts = (int) ceil($charCount / 67);
            }
        } else {
            // GSM-7 standard: 160 chars for 1 part, 153 chars per part for multi-part
            if ($charCount <= 160) {
                $parts = 1;
            } else {
                $parts = (int) ceil($charCount / 153);
            }
        }

        return [
            'char_count' => $charCount,
            'parts_count' => max(1, $parts),
            'is_unicode' => $isUnicode,
        ];
    }

    /**
     * Record SMS dispatch with precise cost tracking.
     */
    public function recordSms(
        Tenant|int|null $tenant,
        string $recipientPhone,
        string $messageBody,
        string $smsType = 'general',
        string $gatewayName = 'Greenweb',
        string $status = 'delivered',
        ?float $ratePerPart = null,
        ?string $apiResponse = null,
        ?string $errorMessage = null
    ): SmsLog {
        $tenantId = $tenant instanceof Tenant ? $tenant->id : $tenant;

        $metrics = $this->calculateParts($messageBody);
        $charCount = $metrics['char_count'];
        $partsCount = $metrics['parts_count'];

        $defaultRate = (float) Setting::get('sms_rate_per_part', 0.35); // Default ৳0.35
        $rate = $ratePerPart !== null ? $ratePerPart : $defaultRate;
        $totalCost = ($status === 'failed') ? 0.0000 : ($partsCount * $rate);

        return SmsLog::create([
            'tenant_id' => $tenantId,
            'gateway_name' => $gatewayName,
            'recipient_phone' => $recipientPhone,
            'sms_type' => $smsType,
            'message_body' => $messageBody,
            'character_count' => $charCount,
            'parts_count' => $partsCount,
            'cost_per_part' => $rate,
            'total_cost' => $totalCost,
            'status' => $status,
            'api_response' => $apiResponse,
            'error_message' => $errorMessage,
        ]);
    }
}
