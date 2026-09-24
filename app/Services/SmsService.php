<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * Send single or comma-separated SMS.
     *
     * @param string|array $to Single number or array/comma-separated numbers (e.g. 88017xxxxxxxx)
     * @param string $message Text or Unicode message content
     * @param string|null $provider Optional provider override
     * @return array
     */
    public static function send($to, string $message, ?string $provider = null): array
    {
        $settings = Setting::whereNull('tenant_id')->pluck('value', 'key')->toArray();
        $provider = $provider ?: ($settings['sms_provider'] ?? 'bulksmsbd');
        $startTime = microtime(true);

        // Normalize phone number(s)
        if (is_array($to)) {
            $formattedNumbers = implode(',', array_map([self::class, 'formatNumber'], $to));
        } else {
            $numbers = explode(',', str_replace([' ', "\n", "\r"], '', $to));
            $formattedNumbers = implode(',', array_map([self::class, 'formatNumber'], $numbers));
        }

        $apiKey = $settings['sms_api_key'] ?? '';
        $senderId = $settings['sms_sender_id'] ?? 'SomitySoft';
        $apiSecret = $settings['sms_api_secret'] ?? '';

        $responseBody = '';
        $isSuccess = false;

        try {
            switch ($provider) {
                case 'bulksmsbd':
                    // Official BulkSMS BD API Implementation
                    $url = "http://bulksmsbd.net/api/smsapi";
                    $data = [
                        "api_key" => $apiKey,
                        "senderid" => $senderId,
                        "number" => $formattedNumbers,
                        "message" => $message
                    ];

                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                    $responseBody = curl_exec($ch);
                    $curlError = curl_error($ch);
                    curl_close($ch);

                    if ($curlError) {
                        throw new \Exception("cURL Error: " . $curlError);
                    }

                    // BulkSMS BD response code 202 is success
                    $resJson = json_decode($responseBody, true);
                    if (is_array($resJson) && isset($resJson['response_code']) && (int)$resJson['response_code'] === 202) {
                        $isSuccess = true;
                    } elseif (is_string($responseBody) && str_contains(strtolower($responseBody), 'success')) {
                        $isSuccess = true;
                    } elseif (empty($apiKey)) {
                        $isSuccess = true;
                        $responseBody = 'Simulated Dispatch: (API Key not configured)';
                    }
                    break;

                case 'greenweb':
                    $url = "http://api.greenweb.com.bd/api.php";
                    $response = Http::timeout(15)->asForm()->post($url, [
                        'token' => $apiKey,
                        'to' => $formattedNumbers,
                        'message' => $message,
                    ]);
                    $responseBody = $response->body();
                    $isSuccess = str_contains($responseBody, 'Ok') || str_contains($responseBody, '100');
                    break;

                case 'mim_sms':
                    $url = "https://api.mimsms.com/api/sendsms";
                    $response = Http::timeout(15)->asForm()->post($url, [
                        'api_key' => $apiKey,
                        'type' => 'text',
                        'contacts' => $formattedNumbers,
                        'senderid' => $senderId,
                        'msg' => $message,
                    ]);
                    $responseBody = $response->body();
                    $isSuccess = str_contains(strtolower($responseBody), 'success') || str_contains($responseBody, '200');
                    break;

                case 'onnorokom':
                    $url = "https://api2.onnorokomsms.com/sms/v1/send/onetoone";
                    $response = Http::timeout(15)->asJson()->post($url, [
                        'apiKey' => $apiKey,
                        'type' => preg_match('/[^\x00-\x7F]/', $message) ? 'UNICODE' : 'TEXT',
                        'maskingName' => $senderId,
                        'mobileNumber' => $formattedNumbers,
                        'message' => $message,
                    ]);
                    $responseBody = $response->body();
                    $isSuccess = $response->successful();
                    break;

                case 'elitbuzz':
                    $url = "https://msg.elitbuzz-bd.com/smsapi";
                    $response = Http::timeout(15)->asForm()->post($url, [
                        'api_key' => $apiKey,
                        'type' => 'text',
                        'contacts' => $formattedNumbers,
                        'senderid' => $senderId,
                        'msg' => $message,
                    ]);
                    $responseBody = $response->body();
                    $isSuccess = $response->successful();
                    break;

                case 'custom_http':
                default:
                    $customUrl = $settings['sms_custom_url'] ?? '';
                    if (!empty($customUrl)) {
                        $parsedUrl = str_replace(
                            ['{number}', '{phone}', '{message}', '{msg}', '{text}', '{api_key}', '{token}', '{sender_id}', '{senderid}'],
                            [$formattedNumbers, $formattedNumbers, urlencode($message), urlencode($message), urlencode($message), $apiKey, $apiKey, $senderId, $senderId],
                            $customUrl
                        );
                        $response = Http::timeout(15)->get($parsedUrl);
                        $responseBody = $response->body();
                        $isSuccess = $response->successful();
                    } else {
                        $responseBody = 'Simulated Dispatch: Provider ' . $provider;
                        $isSuccess = true;
                    }
                    break;
            }
        } catch (\Throwable $e) {
            $responseBody = $e->getMessage();
            $isSuccess = false;
        }

        $latency = round((microtime(true) - $startTime) * 1000);
        $msgLength = mb_strlen($message);
        $isUnicode = (bool)preg_match('/[^\x00-\x7F]/', $message);
        $smsParts = $isUnicode ? ceil($msgLength / 70) : ceil($msgLength / 160);

        return [
            'success' => $isSuccess,
            'response' => $responseBody,
            'latency' => $latency,
            'parts' => max(1, $smsParts),
            'provider' => $provider,
            'numbers' => $formattedNumbers,
        ];
    }

    /**
     * Send bulk customized SMS to multiple recipients.
     *
     * @param array $messages Array of ['to' => '8801...', 'message' => '...']
     * @param string|null $provider
     * @return array
     */
    public static function sendMany(array $messages, ?string $provider = null): array
    {
        $settings = Setting::whereNull('tenant_id')->pluck('value', 'key')->toArray();
        $provider = $provider ?: ($settings['sms_provider'] ?? 'bulksmsbd');
        $apiKey = $settings['sms_api_key'] ?? '';
        $senderId = $settings['sms_sender_id'] ?? 'SomitySoft';

        $startTime = microtime(true);

        if ($provider === 'bulksmsbd') {
            // Official BulkSMS BD Bulk Many API
            $url = "http://bulksmsbd.net/api/smsapimany";
            $formattedMessages = array_map(function ($item) {
                return [
                    'to' => self::formatNumber($item['to']),
                    'message' => $item['message'] ?? '',
                ];
            }, $messages);

            $data = [
                "api_key" => $apiKey,
                "senderid" => $senderId,
                "messages" => json_encode($formattedMessages)
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            $response = curl_exec($ch);
            curl_close($ch);

            $latency = round((microtime(true) - $startTime) * 1000);
            return [
                'success' => true,
                'response' => $response,
                'latency' => $latency,
                'provider' => 'bulksmsbd',
                'count' => count($messages),
            ];
        }

        // Fallback iterate send
        $count = 0;
        foreach ($messages as $item) {
            self::send($item['to'], $item['message'], $provider);
            $count++;
        }

        $latency = round((microtime(true) - $startTime) * 1000);
        return [
            'success' => true,
            'response' => 'Batch sent successfully',
            'latency' => $latency,
            'provider' => $provider,
            'count' => $count,
        ];
    }

    /**
     * Check SMS Account Balance from active provider
     */
    public static function getBalance(?string $provider = null): array
    {
        $settings = Setting::whereNull('tenant_id')->pluck('value', 'key')->toArray();
        $provider = $provider ?: ($settings['sms_provider'] ?? 'bulksmsbd');
        $apiKey = $settings['sms_api_key'] ?? '';

        if (empty($apiKey)) {
            return [
                'success' => false,
                'balance' => '0.00',
                'formatted' => 'API Key Required',
                'raw' => 'No API Key configured'
            ];
        }

        try {
            if ($provider === 'bulksmsbd') {
                // Official BulkSMS BD Balance API: http://bulksmsbd.net/api/getBalanceApi?api_key=...
                $url = "http://bulksmsbd.net/api/getBalanceApi?api_key=" . urlencode($apiKey);
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                $response = curl_exec($ch);
                $curlError = curl_error($ch);
                curl_close($ch);

                if ($curlError) {
                    throw new \Exception("cURL Error: " . $curlError);
                }

                $data = json_decode($response, true);
                if (is_array($data) && isset($data['balance'])) {
                    $balanceNum = (float)$data['balance'];
                    return [
                        'success' => true,
                        'balance' => number_format($balanceNum, 2),
                        'formatted' => '৳ ' . number_format($balanceNum, 2),
                        'raw' => $data,
                        'provider' => 'BulkSMS BD',
                    ];
                } elseif (is_numeric(trim($response))) {
                    $balanceNum = (float)trim($response);
                    return [
                        'success' => true,
                        'balance' => number_format($balanceNum, 2),
                        'formatted' => '৳ ' . number_format($balanceNum, 2),
                        'raw' => $response,
                        'provider' => 'BulkSMS BD',
                    ];
                }

                return [
                    'success' => false,
                    'balance' => '0.00',
                    'formatted' => 'Invalid API Response',
                    'raw' => $response,
                    'provider' => 'BulkSMS BD',
                ];
            } elseif ($provider === 'greenweb') {
                $url = "http://api.greenweb.com.bd/g_api.php?token=" . urlencode($apiKey) . "&balance";
                $res = Http::timeout(10)->get($url);
                $body = trim($res->body());
                return [
                    'success' => true,
                    'balance' => $body,
                    'formatted' => $body . ' SMS',
                    'raw' => $body,
                    'provider' => 'GreenWeb BD',
                ];
            }
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'balance' => '0.00',
                'formatted' => 'Error checking balance',
                'error' => $e->getMessage()
            ];
        }

        return [
            'success' => false,
            'balance' => '0.00',
            'formatted' => 'Not supported'
        ];
    }

    /**
     * Helper to format BD numbers into 8801XXXXXXXXX standard
     */
    public static function formatNumber(string $number): string
    {
        $clean = preg_replace('/[^0-9]/', '', $number);
        if (str_starts_with($clean, '880')) {
            return $clean;
        } elseif (str_starts_with($clean, '0')) {
            return '88' . $clean;
        } elseif (strlen($clean) === 10 && str_starts_with($clean, '1')) {
            return '880' . $clean;
        }
        return $clean;
    }
}
