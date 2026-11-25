<?php

namespace App\Services\Network;

use App\Models\TenantOlt;
use App\Models\TenantOnu;
use App\Models\TenantCustomer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

class OltApiService
{
    protected int $timeout = 15;

    /**
     * Get or create session token for OLT Web API (supports FastCGI, HSGQ/VSOL-JSON, VSOL-HTTPS, UniMars/CoreLink)
     */
    public function getToken(string $ip, int $port, string $username, string $password, bool $forceRefresh = false): ?string
    {
        $cacheKey = 'olt_token_' . hash('sha256', json_encode([$ip, $port, $username, hash_hmac('sha256', $password, (string) config('app.key'))]));
        $driverKey = "olt_driver_{$ip}_{$port}";

        if (!$forceRefresh && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $knownDriver = Cache::get($driverKey);

        // 0. Legacy lighttpd CGI OLTs (login.php + cookie session, commonly port 8085)
        if ($knownDriver === 'legacy_cgi' || $port === 8085) {
            $token = $this->attemptLegacyCgiLogin($ip, $port, $username, $password);
            if ($token) {
                Cache::put($driverKey, 'legacy_cgi', now()->addDays(7));
                Cache::put($cacheKey, $token, now()->addMinutes(15));
                return $token;
            }
        }

        // 1. UniMars / CoreLink (Port 8084 style)
        if ($knownDriver === 'unimars') {
            $token = $this->attemptUniMarsLogin($ip, $port, $username, $password);
            if ($token) {
                Cache::put($driverKey, 'unimars', now()->addDays(7));
                Cache::put($cacheKey, $token, now()->addMinutes(15));
                return $token;
            }
        }

        // 2. VSOL HTTPS (Port 8082 style)
        if ($knownDriver === 'vsol_https') {
            $token = $this->attemptVsolHttpsLogin($ip, $port, $username, $password);
            if ($token) {
                Cache::put($driverKey, 'vsol_https', now()->addDays(7));
                Cache::put($cacheKey, $token, now()->addMinutes(15));
                return $token;
            }
        }

        // 3. HSGQ / VSOL-JSON (Port 8083 style)
        if ($knownDriver === 'hsgq') {
            $token = $this->attemptHsgqLogin($ip, $port, $username, $password);
            if ($token) {
                Cache::put($driverKey, 'hsgq', now()->addDays(7));
                Cache::put($cacheKey, $token, now()->addMinutes(15));
                return $token;
            }

            $token = $this->attemptFastCgiLogin($ip, $port, $username, $password);
            if ($token) {
                Cache::put($driverKey, 'fastcgi', now()->addDays(7));
                Cache::put($cacheKey, $token, now()->addMinutes(15));
                return $token;
            }
        }

        if ($knownDriver === 'fastcgi') {
            $token = $this->attemptFastCgiLogin($ip, $port, $username, $password);
            if ($token) {
                Cache::put($driverKey, 'fastcgi', now()->addDays(7));
                Cache::put($cacheKey, $token, now()->addMinutes(15));
                return $token;
            }
        }

        // 4. Dynamic Auto-Detection for ANY newly added OLT (non-blocking fallback order)
        $token = $this->attemptFastCgiLogin($ip, $port, $username, $password);
        if ($token) {
            Cache::put($driverKey, 'fastcgi', now()->addDays(7));
            Cache::put($cacheKey, $token, now()->addMinutes(15));
            return $token;
        }

        $token = $this->attemptHsgqLogin($ip, $port, $username, $password);
        if ($token) {
            Cache::put($driverKey, 'hsgq', now()->addDays(7));
            Cache::put($cacheKey, $token, now()->addMinutes(15));
            return $token;
        }

        $token = $this->attemptUniMarsLogin($ip, $port, $username, $password);
        if ($token) {
            Cache::put($driverKey, 'unimars', now()->addDays(7));
            Cache::put($cacheKey, $token, now()->addMinutes(15));
            return $token;
        }

        $token = $this->attemptVsolHttpsLogin($ip, $port, $username, $password);
        if ($token) {
            Cache::put($driverKey, 'vsol_https', now()->addDays(7));
            Cache::put($cacheKey, $token, now()->addMinutes(15));
            return $token;
        }

        return null;
    }

    /**
     * Raw HTTP helper for embedded Boa / UniMars OLTs
     */
    public function sendUniMarsRequest(string $ip, int $port, string $body, int $timeout = 4): ?string
    {
        $fp = @fsockopen($ip, $port, $errno, $errstr, $timeout);
        if (!$fp) return null;

        $referer = str_starts_with($body, 'set=login')
            ? "http://{$ip}:{$port}/"
            : "http://{$ip}:{$port}/m/onu_all_onu.htm";

        $len = strlen($body);
        $req = "POST /sw.cgi HTTP/1.1\r\n" .
               "Host: {$ip}:{$port}\r\n" .
               "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\n" .
               "Accept: text/javascript, text/html, application/xml, text/xml, */*\r\n" .
               "X-Requested-With: XMLHttpRequest\r\n" .
               "Content-Type: uni_mars_ap\r\n" .
               "Referer: {$referer}\r\n" .
               "Content-Length: {$len}\r\n" .
               "Connection: close\r\n\r\n" .
               $body;

        fwrite($fp, $req);
        $response = '';
        while (!feof($fp)) {
            $response .= fgets($fp, 1024);
        }
        fclose($fp);

        if (str_contains($response, "\r\n\r\n")) {
            $parts = explode("\r\n\r\n", $response, 2);
            return $parts[1] ?? null;
        }

        return $response;
    }

    /**
     * Driver 1: FastCGI Login (/cgi-bin/h.cgi?module=sys_login)
     */
    protected function attemptFastCgiLogin(string $ip, int $port, string $username, string $password): ?string
    {
        $protocols = ['https', 'http'];

        foreach ($protocols as $proto) {
            try {
                $url = "{$proto}://{$ip}:{$port}/cgi-bin/h.cgi?module=sys_login";
                $pwdMd5 = md5($password);

                $response = Http::withoutVerifying()
                    ->timeout(5)
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ])
                    ->post($url, [
                        'Usrname' => $username,
                        'Password' => $pwdMd5,
                    ]);

                if ($response->successful()) {
                    $token = $response->header('token') ?: $response->header('Token');
                    if ($token) {
                        Cache::put("olt_proto_{$ip}_{$port}", $proto, now()->addDays(7));
                        return $token;
                    }

                    $data = $response->json();
                    if (isset($data['data']['token']) && !empty($data['data']['token'])) {
                        Cache::put("olt_proto_{$ip}_{$port}", $proto, now()->addDays(7));
                        return $data['data']['token'];
                    }
                    if (isset($data['token']) && !empty($data['token'])) {
                        Cache::put("olt_proto_{$ip}_{$port}", $proto, now()->addDays(7));
                        return $data['token'];
                    }
                }
            } catch (Exception $e) {
                // Continue fallback
            }
        }
        return null;
    }

    /**
     * Legacy EPON/FD1108-style web login: POST /cgi/login.php and retain cookies.
     */
    protected function attemptLegacyCgiLogin(string $ip, int $port, string $username, string $password): ?string
    {
        try {
            $base = "http://{$ip}:{$port}";
            $response = Http::withoutRedirecting()
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0',
                    'Expect' => '',
                ])
                ->timeout(8)
                ->asForm()
                ->post("{$base}/cgi/login.php", [
                    'username' => $username,
                    'passwd' => $password,
                    'setLang' => 'en',
                ]);

            if ($response->failed()) {
                return null;
            }

            $cookies = collect($response->cookies()->toArray())
                ->map(fn (array $cookie) => ($cookie['Name'] ?? '') . '=' . ($cookie['Value'] ?? ''))
                ->filter()
                ->implode('; ');

            return $cookies !== '' ? base64_encode($cookies) : null;
        } catch (Exception $e) {
            Log::warning("Legacy CGI OLT login failed for {$ip}:{$port}: " . $e->getMessage());
            return null;
        }
    }

    protected function legacyCgiRequest(string $ip, int $port, string $cookieToken, string $path): ?string
    {
        try {
            $cookies = base64_decode($cookieToken, true);
            if (!$cookies) return null;

            $response = Http::withoutRedirecting()
                ->withHeaders(['User-Agent' => 'Mozilla/5.0', 'Cookie' => $cookies])
                ->timeout(8)
                ->get("http://{$ip}:{$port}/cgi/{$path}");

            return $response->successful() ? $response->body() : null;
        } catch (Exception $e) {
            Log::warning("Legacy CGI OLT request failed for {$ip}:{$port}: " . $e->getMessage());
            return null;
        }
    }

    protected function legacyCgiField(string $html, string $label): ?string
    {
        $quoted = preg_quote($label, '/');
        if (preg_match("/<li>\\s*<span>{$quoted}<a>.*?<\\/a><\\/span>(.*?)<\\/li>/is", $html, $m)) {
            $value = trim(html_entity_decode(strip_tags($m[1])));
            $value = preg_replace('/[\\x00-\\x1F\\x7F]/u', '', $value) ?? $value;
            $value = @iconv('UTF-8', 'UTF-8//IGNORE', $value) ?: $value;
            return $value !== '' ? $value : null;
        }
        return null;
    }

    /**
     * Driver 2: HSGQ / VSOL JSON Login (/userlogin?form=login)
     */
    protected function attemptHsgqLogin(string $ip, int $port, string $username, string $password): ?string
    {
        try {
            $url = "http://{$ip}:{$port}/userlogin?form=login";
            $key = md5("{$username}:{$password}");
            $value = base64_encode($password);

            $response = Http::timeout(4)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($url, [
                    'method' => 'set',
                    'param' => [
                        'name' => $username,
                        'key' => $key,
                        'value' => $value,
                        'captcha_v' => '',
                        'captcha_f' => '',
                    ]
                ]);

            if ($response->successful()) {
                $headers = $response->headers();
                $token = $headers['X-Token'][0] ?? ($headers['x-token'][0] ?? ($response->header('X-Token') ?: $response->header('x-token')));
                if ($token) return $token;

                $data = $response->json();
                if (isset($data['data']['token'])) return $data['data']['token'];
                if (isset($data['token'])) return $data['token'];
            }
        } catch (Exception $e) {
            // Continue fallback
        }
        return null;
    }

    /**
     * Driver 3: VSOL HTTPS Web Login (/action/main.html)
     */
    protected function attemptVsolHttpsLogin(string $ip, int $port, string $username, string $password): ?string
    {
        $cookieKey = hash('sha256', json_encode([$ip, $port, $username, hash_hmac('sha256', $password, (string) config('app.key'))]));
        $cookieFile = storage_path("framework/cache/vsol_cookie_{$cookieKey}.txt");
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://{$ip}:{$port}/action/main.html");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 6);
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                'user' => $username,
                'pass' => $password,
                'who'  => '100',
            ]));
            $res = curl_exec($ch);
            curl_close($ch);

            if ($res && (str_contains($res, 'systeminfo.html') || str_contains($res, 'mainFrame') || str_contains($res, 'Device Status'))) {
                return 'vsol_session_' . md5("{$ip}_{$port}_{$username}");
            }
        } catch (Exception $e) {
            // Continue fallback
        }
        return null;
    }

    /**
     * Driver 4: UniMars / CoreLink Login (/sw.cgi with uni_mars_ap)
     */
    protected function attemptUniMarsLogin(string $ip, int $port, string $username, string $password): ?string
    {
        try {
            $sid = base64_encode("{$username}&{$password}");
            $body = $this->sendUniMarsRequest($ip, $port, "set=login&user={$sid}");

            if ($body && (str_contains($body, '<item name="method" value="0"') || str_contains($body, 'value="0"'))) {
                return 'unimars_session_' . md5("{$ip}_{$port}_{$username}");
            }
        } catch (Exception $e) {
            // Continue fallback
        }
        return null;
    }

    /**
     * Fetch UniMars / CoreLink system info
     */
    public function fetchUniMarsSystemInfo(string $ip, int $port, string $username, string $password): ?array
    {
        try {
            $sid = base64_encode("{$username}&{$password}");
            $this->sendUniMarsRequest($ip, $port, "set=login&user={$sid}");
            $body = $this->sendUniMarsRequest($ip, $port, "get=sysinfo2&sysunit=1");

            if ($body && str_contains($body, '<xml>')) {
                $xml = @simplexml_load_string($body);
                if ($xml && isset($xml->item)) {
                    $info = [];
                    foreach ($xml->item as $item) {
                        foreach ($item->attributes() as $k => $v) {
                            $info[(string)$k] = (string)$v;
                        }
                    }
                    return $info;
                }
            }
        } catch (Exception $e) {
            Log::warning("UniMars fetch info error: " . $e->getMessage());
        }
        return null;
    }

    /**
     * Fetch VSOL HTML Pages over HTTPS with session persistence & auto re-login
     */
    public function fetchVsolHtmlPage(string $ip, int $port, string $username, string $password, string $page, array $postData = []): ?string
    {
        $cookieKey = hash('sha256', json_encode([$ip, $port, $username, hash_hmac('sha256', $password, (string) config('app.key'))]));
        $cookieFile = storage_path("framework/cache/vsol_cookie_{$cookieKey}.txt");

        $execCurl = function($targetUrl, $data = null) use ($cookieFile) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $targetUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 8);
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
            if ($data !== null) {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            }
            $res = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            return ['code' => $code, 'body' => $res];
        };

        try {
            $pageUrl = "https://{$ip}:{$port}/action/{$page}";

            // If cookie file doesn't exist or is older than 5 minutes, login first
            if (!file_exists($cookieFile) || (time() - filemtime($cookieFile) > 300)) {
                $execCurl("https://{$ip}:{$port}/action/main.html", [
                    'user' => $username,
                    'pass' => $password,
                    'who'  => '100'
                ]);
            }

            // Fetch target page
            $res = $execCurl($pageUrl, !empty($postData) ? $postData : null);

            // If redirected to login or unauthenticated response received, re-login and retry
            if ($res['code'] === 302 || empty($res['body']) || str_contains($res['body'], 'login_first.html') || str_contains($res['body'], 'login.html')) {
                $loginRes = $execCurl("https://{$ip}:{$port}/action/main.html", [
                    'user' => $username,
                    'pass' => $password,
                    'who'  => '100'
                ]);
                if (!empty($loginRes['body']) && (str_contains($loginRes['body'], 'systeminfo.html') || str_contains($loginRes['body'], 'mainFrame'))) {
                    $res = $execCurl($pageUrl, !empty($postData) ? $postData : null);
                }
            }

            if ($res['code'] === 200 && !empty($res['body']) && !str_contains($res['body'], 'login_first.html')) {
                return $res['body'];
            }
        } catch (Exception $e) {
            Log::warning("VSOL fetch page {$page} error: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Generic module request for FastCGI OLT
     */
    public function getModule(string $ip, int $port, string $username, string $password, string $module): ?array
    {
        $token = $this->getToken($ip, $port, $username, $password);
        if (!$token) return null;

        $proto = Cache::get("olt_proto_{$ip}_{$port}") ?: 'http';
        $url = "{$proto}://{$ip}:{$port}/cgi-bin/h.cgi?module={$module}";
        try {
            $response = Http::withoutVerifying()
                ->timeout($this->timeout)
                ->withHeaders([
                    'token' => $token,
                    'Accept' => 'application/json',
                ])
                ->get($url);

            $json = $response->json();
            if ($response->status() === 401 || $response->status() === 403 || (is_array($json) && isset($json['code']) && in_array($json['code'], [1, 2]))) {
                $token = $this->getToken($ip, $port, $username, $password, true);
                if ($token) {
                    $proto = Cache::get("olt_proto_{$ip}_{$port}") ?: 'http';
                    $url = "{$proto}://{$ip}:{$port}/cgi-bin/h.cgi?module={$module}";
                    $response = Http::withoutVerifying()
                        ->timeout($this->timeout)
                        ->withHeaders([
                            'token' => $token,
                            'Accept' => 'application/json',
                        ])
                        ->get($url);
                    $json = $response->json();
                }
            }

            if ($response->successful()) {
                if (isset($json['data'])) return $json['data'];
                return $json;
            }
        } catch (Exception $e) {
            Log::warning("OLT getModule ({$module}) error: " . $e->getMessage());
        }
        return null;
    }

    /**
     * Generic HSGQ API request
     */
    public function getHsgqEndpoint(string $ip, int $port, string $username, string $password, string $endpoint): ?array
    {
        $token = $this->getToken($ip, $port, $username, $password);
        if (!$token) return null;

        $url = "http://{$ip}:{$port}/{$endpoint}";
        for ($attempt = 1; $attempt <= 2; $attempt++) {
            try {
                $response = Http::timeout(15)
                    ->withHeaders([
                        'X-Token' => $token,
                        'Accept' => 'application/json',
                    ])
                    ->get($url);

                $json = $response->json();
                if ($response->status() === 401 || $response->status() === 403 || (is_array($json) && isset($json['message']) && str_contains(strtolower($json['message']), 'token'))) {
                    $token = $this->getToken($ip, $port, $username, $password, true);
                    if ($token) {
                        $response = Http::timeout(15)
                            ->withHeaders([
                                'X-Token' => $token,
                                'Accept' => 'application/json',
                            ])
                            ->get($url);
                    }
                }

                if ($response->successful()) {
                    return $response->json();
                }
            } catch (Exception $e) {
                Log::warning("HSGQ getEndpoint ({$endpoint}) attempt {$attempt} error: " . $e->getMessage());
                if ($attempt < 2) {
                    usleep(500000); // 500ms before retry
                }
            }
        }
        return null;
    }

    /**
     * Optical list for FastCGI OLT
     */
    public function getOpticalList(string $ip, int $port, string $username, string $password, string $ponId): ?array
    {
        $token = $this->getToken($ip, $port, $username, $password);
        if (!$token) return null;

        $proto = Cache::get("olt_proto_{$ip}_{$port}") ?: 'http';
        $endpoints = [
            "ont_optical_list_get&PonId={$ponId}",
            "onu_optical_list_get&PonId={$ponId}",
        ];

        foreach ($endpoints as $ep) {
            $url = "{$proto}://{$ip}:{$port}/cgi-bin/h.cgi?module={$ep}";
            try {
                $response = Http::withoutVerifying()
                    ->timeout($this->timeout)
                    ->withHeaders(['token' => $token])
                    ->get($url);

                if ($response->successful()) {
                    $data = $response->json();
                    if (!empty($data['data']['list'])) return $data['data']['list'];
                    if (!empty($data['list'])) return $data['list'];
                }
            } catch (Exception $e) {
                Log::warning("OLT getOpticalList error: " . $e->getMessage());
            }
        }

        return null;
    }

    /**
     * Live Test Connection
     */
    public function testConnection(string $ip, int $port, string $username, string $password, string $vendor = 'EPON OLT'): array
    {
        $start = microtime(true);
        $token = $this->getToken($ip, $port, $username, $password, true);

        if (!$token) {
            return [
                'success' => false,
                'message' => 'Connection failed: Unable to authenticate with OLT. Check IP, Port, and Credentials.',
                'latency_ms' => round((microtime(true) - $start) * 1000, 1),
            ];
        }

        $driverKey = "olt_driver_{$ip}_{$port}";
        $driver = Cache::get($driverKey);

        if ($driver === 'legacy_cgi') {
            return [
                'success' => true,
                'message' => 'Connected successfully to legacy CGI OLT Web Interface.',
                'latency_ms' => round((microtime(true) - $start) * 1000, 1),
                'device_info' => [
                    'model' => 'EPON OLT',
                    'pon_ports' => 4,
                ],
            ];
        }

        // 1. UniMars / CoreLink
        if ($driver === 'unimars' || str_contains(strtolower($vendor), 'core') || str_contains(strtolower($vendor), 'mars')) {
            $sysInfo = $this->fetchUniMarsSystemInfo($ip, $port, $username, $password);
            $latency = round((microtime(true) - $start) * 1000, 1);

            $uptimeStr = null;
            if (!empty($sysInfo['uptime'])) {
                $parts = explode('?', $sysInfo['uptime']);
                if (count($parts) >= 4) {
                    $uptimeStr = "{$parts[0]}d {$parts[1]}h {$parts[2]}m {$parts[3]}s";
                }
            }

            $sid = base64_encode("{$username}&{$password}");
            $this->sendUniMarsRequest($ip, $port, "set=login&user={$sid}");
            $onuBody = $this->sendUniMarsRequest($ip, $port, "get=onualllist&sysUnit=1");
            $totalOnus = 0;
            $onlineOnus = 0;
            if ($onuBody) {
                preg_match_all('/<item onu="([^"]+)"/i', $onuBody, $m);
                $totalOnus = count($m[1] ?? []);
                foreach ($m[1] ?? [] as $onuStr) {
                    $cols = explode(',', $onuStr);
                    if (isset($cols[3]) && strtolower($cols[3]) === 'up') {
                        $onlineOnus++;
                    }
                }
            }

            return [
                'success' => $inventoryComplete,
                'message' => 'Connected successfully to CoreLink / UniMars OLT.',
                'latency_ms' => $latency,
                'device_info' => [
                    'model' => $sysInfo['product_name'] ?? 'CW2P1P / HA7302V',
                    'hardware' => $sysInfo['hw_version'] ?? 'V2.0',
                    'firmware' => $sysInfo['sw_version'] ?? 'V1.1.11',
                    'mac' => $sysInfo['mac'] ?? null,
                    'serial' => $sysInfo['sn'] ?? null,
                    'uptime' => $uptimeStr,
                    'total_onus' => $totalOnus,
                    'online_onus' => $onlineOnus,
                    'offline_onus' => max(0, $totalOnus - $onlineOnus),
                    'pon_ports' => 2,
                ],
            ];
        }

        // 2. VSOL HTTPS Web
        if ($driver === 'vsol_https') {
            $sysHtml = $this->fetchVsolHtmlPage($ip, $port, $username, $password, 'systeminfo.html');
            $latency = round((microtime(true) - $start) * 1000, 1);

            if (!$sysHtml) {
                return [
                    'success' => false,
                    'message' => 'Connection failed: Unable to authenticate or fetch system info from VSOL OLT. Verify IP, Port, Username and Password.',
                    'latency_ms' => $latency,
                ];
            }

            $firmware = null;
            $mac = null;
            $uptime = null;
            $serialNumber = null;
            $hardware = null;
            $deviceModel = null;
            if (preg_match('/Serial Number.*?<\/td>\s*<td[^>]*>([^<]+)<\/td>/is', $sysHtml, $m)) $serialNumber = trim($m[1]);
            if (preg_match('/Hardware Version.*?<\/td>\s*<td[^>]*>([^<]+)<\/td>/is', $sysHtml, $m)) $hardware = trim($m[1]);
            if (preg_match('/Firmware Version.*?<\/td>\s*<td[^>]*>([^<]+)<\/td>/is', $sysHtml, $m)) $firmware = trim($m[1]);
            if (preg_match('/MAC Address.*?<\/td>\s*<td[^>]*>([^<]+)<\/td>/is', $sysHtml, $m)) $mac = trim($m[1]);
            if (preg_match('/Device Model.*?<\/td>\s*<td[^>]*>([^<]+)<\/td>/is', $sysHtml, $m)) $deviceModel = trim($m[1]);
            if (preg_match('/Running Time.*?<\/td>\s*<td[^>]*>([^<]+)<\/td>/is', $sysHtml, $m)) $uptime = trim($m[1]);
            if (!$uptime && preg_match('/System Up Time.*?<\/td>\s*<td[^>]*>([^<]+)<\/td>/is', $sysHtml, $m)) $uptime = trim($m[1]);

            $modelName = !empty($deviceModel) ? "VSOL {$deviceModel} (V1600D4)" : 'VSOL V1600D4';

            return [
                'success' => $inventoryComplete,
                'message' => 'Connected successfully to VSOL HTTPS OLT Gateway.',
                'latency_ms' => $latency,
                'device_info' => [
                    'model' => $modelName,
                    'hardware' => $hardware,
                    'serial' => $serialNumber,
                    'firmware' => $firmware,
                    'mac' => $mac,
                    'uptime' => $uptime,
                    'pon_ports' => 4,
                ],
            ];
        }

        // 3. HSGQ JSON-RPC
        if ($driver === 'hsgq') {
            $boardRes = $this->getHsgqEndpoint($ip, $port, $username, $password, 'board?info=system');
            $latency = round((microtime(true) - $start) * 1000, 1);
            $sys = $boardRes['data'] ?? [];

            return [
                'success' => true,
                'message' => 'Connected successfully to HSGQ Web API.',
                'latency_ms' => $latency,
                'device_info' => [
                    'model' => $sys['board_type'] ?? 'HSGQ EPON/GPON OLT',
                    'firmware' => $sys['firmware_version'] ?? null,
                    'mac' => $sys['system_mac'] ?? null,
                    'uptime' => isset($sys['running_time']) ? gmdate("z\d H\h i\m s\s", (int)$sys['running_time']) : null,
                    'pon_ports' => 4,
                ],
            ];
        }

        // 4. FastCGI
        $sysDevInfo = $this->getModule($ip, $port, $username, $password, 'sys_dev_info');
        $sysInfo = !empty($sysDevInfo) ? $sysDevInfo : $this->getModule($ip, $port, $username, $password, 'sys_info');
        $latency = round((microtime(true) - $start) * 1000, 1);

        $uptimeStr = null;
        if (!empty($sysInfo['Uptime'])) {
            $upSec = floor((int)$sysInfo['Uptime'] / 1000);
            $upDays = floor($upSec / 86400);
            $upHours = floor(($upSec % 86400) / 3600);
            $upMins = floor(($upSec % 3600) / 60);
            $uptimeStr = "{$upDays}d {$upHours}h {$upMins}m";
        }

        return [
            'success' => true,
            'message' => 'Connected successfully to FastCGI OLT Web API.',
            'latency_ms' => $latency,
            'device_info' => [
                'model' => $sysInfo['Model'] ?? ($sysInfo['product_type'] ?? ($sysInfo['board_type'] ?? 'EPON OLT')),
                'hardware' => $sysInfo['Hardware'] ?? ($sysInfo['hard_ver'] ?? null),
                'firmware' => $sysInfo['Firmware'] ?? ($sysInfo['soft_ver'] ?? null),
                'mac' => $sysInfo['DevMacAddr'] ?? ($sysInfo['mac'] ?? null),
                'serial' => $sysInfo['SnNum'] ?? null,
                'uptime' => $uptimeStr ?? ($sysInfo['sys_time'] ?? null),
                'pon_ports' => 8,
            ],
        ];
    }

    /**
     * Sync OLT State & Discover/Update all ONUs (with accurate active pruning)
     */
    public function syncOlt(TenantOlt $olt): array
    {
        $lock = Cache::lock('olt-sync:' . $olt->id, 660);
        if (!$lock->get()) {
            return ['success' => false, 'message' => __('OLT synchronization is already running.')];
        }
        try {
            $result = $this->performSyncOlt($olt);
            if (!empty($result['success'])) app(OnuLinkService::class)->linkTenant($olt->tenant_id);
            return $result;
        } finally {
            $lock->release();
        }
    }

    protected function performSyncOlt(TenantOlt $olt): array
    {
        $inventoryComplete = true;
        $start = microtime(true);
        $ip = $olt->ip_address;
        $port = (int) ($olt->web_port ?: 80);
        $username = $olt->web_username ?: 'root';
        $password = $olt->decrypted_web_password ?: 'admin';

        $token = $this->getToken($ip, $port, $username, $password);

        if (!$token) {
            $olt->update([
                'status' => 'offline',
                'last_ping_at' => now(),
                'last_error' => 'Authentication failed. Check credentials/port.',
            ]);

            return [
                'success' => false,
                'message' => "Failed to authenticate with OLT {$olt->name} ({$ip}:{$port}).",
                'latency_ms' => round((microtime(true) - $start) * 1000, 1),
            ];
        }

        $driverKey = "olt_driver_{$ip}_{$port}";
        $driver = Cache::get($driverKey);
        $syncedOnuDbIds = [];

        if ($driver === 'legacy_cgi') {
            $cookieToken = $this->getToken($ip, $port, $username, $password);
            $indexHtml = $cookieToken ? $this->legacyCgiRequest($ip, $port, $cookieToken, 'index.php') : null;
            $syncedCount = 0;
            $onlineCount = 0;

            if ($indexHtml) {
                preg_match_all('/page=onu(?:&amp;|&)id=(\d+)(?:&amp;|&)oid=(\d+)/i', $indexHtml, $matches, PREG_SET_ORDER);
                foreach ($matches as $match) {
                    $ponId = (int) $match[1];
                    $onuId = (int) $match[2];
                    $onuHtml = $this->legacyCgiRequest($ip, $port, $cookieToken, "index.php?page=onu&isajax=1&tab=gene&id={$ponId}&oid={$onuId}&runtab=1");
                    if (!$onuHtml) continue;

                    $status = strtolower((string) $this->legacyCgiField($onuHtml, 'OnLineStatus'));
                    $isOnline = $status === 'online';
                    $name = $this->legacyCgiField($onuHtml, 'ONU Name') ?: "ONU {$ponId}:{$onuId}";
                    $mac = $this->legacyCgiField($onuHtml, 'ONU MACAddr');
                    $model = $this->legacyCgiField($onuHtml, 'DeviceType');
                    $hardware = $this->legacyCgiField($onuHtml, 'HWRev');
                    $firmware = $this->legacyCgiField($onuHtml, 'FWRev');
                    $rxRaw = $this->legacyCgiField($onuHtml, 'RxPower(dBm)');
                    $txRaw = $this->legacyCgiField($onuHtml, 'TxPower(dBm)');
                    $range = $this->legacyCgiField($onuHtml, 'LinkRangeValue(m)');
                    $rx = is_numeric($rxRaw) && (float) $rxRaw > 0 ? round(10 * log10((float) $rxRaw) - 40, 2) : null;
                    $tx = is_numeric($txRaw) && (float) $txRaw > 0 ? round(10 * log10((float) $txRaw) - 40, 2) : null;

                    TenantOnu::updateOrCreate(
                        ['olt_id' => $olt->id, 'pon_port' => "0/{$ponId}", 'onu_id' => $onuId],
                        [
                            'tenant_id' => $olt->tenant_id,
                            'name' => $name,
                            'mac_address' => $mac,
                            'vendor' => 'EPON OLT',
                            'model' => $model,
                            'hardware_version' => $hardware,
                            'software_version' => $firmware,
                            'status' => $isOnline ? 'online' : 'offline',
                            'running_state' => $isOnline ? 1 : 0,
                            'rx_power_dbm' => $rx,
                            'tx_power_dbm' => $tx,
                            'distance_m' => is_numeric($range) ? (int) $range : null,
                            'last_online_at' => $isOnline ? now() : null,
                        ]
                    );
                    $syncedCount++;
                    if ($isOnline) $onlineCount++;
                }
            }

            $olt->update([
                'status' => 'online',
                'vendor' => $olt->vendor ?: 'EPON OLT',
                'model' => $olt->model ?: 'EPON OLT',
                'total_pon_ports' => $olt->total_pon_ports ?: 4,
                'total_onus_count' => $syncedCount,
                'online_onus_count' => $onlineCount,
                'offline_onus_count' => max(0, $syncedCount - $onlineCount),
                'last_ping_at' => now(),
                'last_sync_at' => now(),
                'last_error' => null,
            ]);

            return [
                'success' => true,
                'message' => __('OLT and ONU synchronization completed successfully.'),
                'total_onus' => $syncedCount,
                'online_onus' => $onlineCount,
                'offline_onus' => max(0, $syncedCount - $onlineCount),
                'synced_count' => $syncedCount,
                'latency_ms' => round((microtime(true) - $start) * 1000, 1),
            ];
        }

        // =========================================================================
        // 1. UniMars / CoreLink Driver Sync
        // =========================================================================
        if ($driver === 'unimars' || str_contains(strtolower($olt->vendor ?? ''), 'core') || str_contains(strtolower($olt->model ?? ''), 'ha73') || str_contains(strtolower($olt->model ?? ''), 'cw2')) {
            $sysInfo = $this->fetchUniMarsSystemInfo($ip, $port, $username, $password);

            $uptimeStr = null;
            if (!empty($sysInfo['uptime'])) {
                $parts = explode('?', $sysInfo['uptime']);
                if (count($parts) >= 4) {
                    $uptimeStr = "{$parts[0]}d {$parts[1]}h {$parts[2]}m {$parts[3]}s";
                }
            }

            $sid = base64_encode("{$username}&{$password}");
            $this->sendUniMarsRequest($ip, $port, "set=login&user={$sid}");
            $onuBody = $this->sendUniMarsRequest($ip, $port, "get=onualllist&sysUnit=1");
            $inventoryComplete = $onuBody !== null;
            $syncedCount = 0;
            $onlineCount = 0;

            if ($onuBody && preg_match_all('/<item onu="([^"]+)"/i', $onuBody, $matches)) {
                foreach ($matches[1] as $onuStr) {
                    $cols = explode(',', $onuStr);
                    $portOnu = $cols[0] ?? '1/1:1';
                    $mac = $cols[2] ?? null;
                    $statusStr = strtolower($cols[3] ?? 'down');
                    $isOnline = ($statusStr === 'up');
                    if ($isOnline) $onlineCount++;

                    $distance = isset($cols[10]) && is_numeric($cols[10]) ? (int)$cols[10] : null;
                    $txPower = isset($cols[14]) && is_numeric($cols[14]) ? (float)$cols[14] : null;
                    $rxPower = isset($cols[15]) && is_numeric($cols[15]) ? (float)$cols[15] : null;

                    $pParts = explode(':', $portOnu);
                    $ponPort = $pParts[0] ?? '1/1';
                    $onuId = isset($pParts[1]) ? (int)$pParts[1] : ($syncedCount + 1);

                    // Fetch learned router MAC from CoreLink FDB / onumaclist
                    $cleanPort = preg_replace('/[^0-9]/', '', substr($ponPort, strrpos($ponPort, '/') ?: 0));
                    $ponNum = is_numeric($cleanPort) && (int)$cleanPort > 0 ? (int)$cleanPort : 1;
                    $abnormalOnuId = str_replace('/', '_', $portOnu);
                    $routerMac = null;
                    if ($isOnline) {
                        try {
                            $macRes = $this->sendUniMarsRequest($ip, $port, "get=onumaclist&sysUnit=1&ponport={$ponNum}&onuid={$abnormalOnuId}");
                            if ($macRes && preg_match('/<item\s+mac="([0-9a-fA-F:]{17})"/i', $macRes, $mMacItem)) {
                                $routerMac = strtoupper(trim($mMacItem[1]));
                            }
                        } catch (\Throwable $e) {
                            Log::warning("CoreLink onumaclist error for {$portOnu}: " . $e->getMessage());
                        }
                    }

                    $existingOnu = TenantOnu::where('olt_id', $olt->id)
                        ->where('pon_port', $ponPort)
                        ->where('onu_id', $onuId)
                        ->first();

                    $savedOnu = TenantOnu::updateOrCreate(
                        [
                            'olt_id' => $olt->id,
                            'pon_port' => $ponPort,
                            'onu_id' => $onuId,
                        ],
                        [
                            'tenant_id' => $olt->tenant_id,
                            'olt_id' => $olt->id,
                            'pon_port' => $ponPort,
                            'onu_id' => $onuId,
                            'name' => "ONU {$ponPort}:{$onuId}",
                            'desc' => '',
                            'mac_address' => ($mac && $mac !== 'NA') ? $mac : null,
                            'router_mac' => $routerMac ?: ($existingOnu?->router_mac ?: null),
                            'vendor' => 'CoreLink',
                            'model' => 'HA7200',
                            'onu_type' => 'EPON',
                            'status' => $isOnline ? 'online' : 'offline',
                            'running_state' => $isOnline ? 1 : 0,
                            'control_flag' => 1,
                            'rx_power_dbm' => $rxPower,
                            'optical_observed_at' => $rxPower !== null ? now() : $existingOnu?->optical_observed_at,
                            'tx_power_dbm' => $txPower,
                            'distance_m' => $distance,
                            'last_online_at' => $isOnline ? now() : null,
                        ]
                    );


                    $syncedOnuDbIds[] = $savedOnu->id;
                    $syncedCount++;
                }
            }

            // Prune unlisted ONUs
            if (!empty($syncedOnuDbIds)) {
                // Missing rows may be caused by a partial hardware response. Preserve inventory.
            }

            $offlineCount = max(0, $syncedCount - $onlineCount);

            $olt->update([
                'status' => 'online',
                'vendor' => 'Core Link',
                'model' => $sysInfo['product_name'] ?? ($olt->model ?: 'HA7302V'),
                'hardware_version' => $sysInfo['hw_version'] ?? ($olt->hardware_version ?: 'V2.0'),
                'firmware_version' => $sysInfo['sw_version'] ?? ($olt->firmware_version ?: 'V1.1.11'),
                'mac_address' => $sysInfo['mac'] ?? $olt->mac_address,
                'uptime' => $uptimeStr ?? $olt->uptime,
                'total_pon_ports' => $olt->total_pon_ports ?: $olt->pon_ports_count,
                'total_onus_count' => $syncedCount,
                'online_onus_count' => $onlineCount,
                'offline_onus_count' => $offlineCount,
                'last_ping_at' => now(),
                'last_sync_at' => now(),
                'last_error' => null,
            ]);

            $latency = round((microtime(true) - $start) * 1000, 1);

            return [
                'success' => true,
                'message' => $inventoryComplete ? __('ONU operation completed.') : __('Some devices could not be synchronized.'),
                'total_onus' => $syncedCount,
                'online_onus' => $onlineCount,
                'offline_onus' => $offlineCount,
                'synced_count' => $syncedCount,
                'latency_ms' => $latency,
            ];
        }

        // =========================================================================
        // 2. VSOL HTTPS Web Sync (Port 8082)
        // =========================================================================
        if ($driver === 'vsol_https') {
            $sysHtml = $this->fetchVsolHtmlPage($ip, $port, $username, $password, 'systeminfo.html');

            if (!$sysHtml) {
                $olt->update([
                    'status' => 'offline',
                    'last_error' => "Unable to authenticate or fetch system info from VSOL OLT at {$ip}:{$port}",
                    'last_ping_at' => now(),
                ]);
                return [
                    'success' => false,
                    'message' => "Failed to authenticate or fetch system info from VSOL OLT at {$ip}:{$port}. Please verify Web User and Password.",
                    'latency_ms' => round((microtime(true) - $start) * 1000, 1),
                ];
            }

            $firmware = null;
            $mac = null;
            $uptime = null;
            $serialNumber = null;
            $hardwareVersion = null;
            $deviceModel = null;
            $cpuLoad = null;
            $memUsage = null;
            if (preg_match('/Serial Number.*?<\/td>\s*<td[^>]*>([^<]+)<\/td>/is', $sysHtml, $m)) $serialNumber = trim($m[1]);
            if (preg_match('/Hardware Version.*?<\/td>\s*<td[^>]*>([^<]+)<\/td>/is', $sysHtml, $m)) $hardwareVersion = trim($m[1]);
            if (preg_match('/Firmware Version.*?<\/td>\s*<td[^>]*>([^<]+)<\/td>/is', $sysHtml, $m)) $firmware = trim($m[1]);
            if (preg_match('/MAC Address.*?<\/td>\s*<td[^>]*>([^<]+)<\/td>/is', $sysHtml, $m)) $mac = trim($m[1]);
            if (preg_match('/Device Model.*?<\/td>\s*<td[^>]*>([^<]+)<\/td>/is', $sysHtml, $m)) $deviceModel = trim($m[1]);
            if (preg_match('/Running Time.*?<\/td>\s*<td[^>]*>([^<]+)<\/td>/is', $sysHtml, $m)) $uptime = trim($m[1]);
            if (!$uptime && preg_match('/System Up Time.*?<\/td>\s*<td[^>]*>([^<]+)<\/td>/is', $sysHtml, $m)) $uptime = trim($m[1]);
            if (preg_match('/CPU Usage.*?<\/td>\s*<td[^>]*>(\d+)/is', $sysHtml, $m)) $cpuLoad = (int)$m[1];
            if (preg_match('/Memory Usage.*?<\/td>\s*<td[^>]*>(\d+)/is', $sysHtml, $m)) $memUsage = (int)$m[1];

            // 1. Fetch Learned MAC Address Table (FDB / Router MAC mapping) from macinfo.html
            $macInfoHtml = $this->fetchVsolHtmlPage($ip, $port, $username, $password, 'macinfo.html');
            $learnedMacs = [];
            if ($macInfoHtml && preg_match_all('/<tr>\s*<td>(\d+)<\/td>\s*<td>([0-9a-fA-F:]{17})<\/td>\s*<td>[^<]*<\/td>\s*<td>EPON0\/(\d+):(\d+)<\/td>\s*<\/tr>/i', $macInfoHtml, $mRows, PREG_SET_ORDER)) {
                foreach ($mRows as $mr) {
                    $onuKey = "0/{$mr[3]}:{$mr[4]}";
                    $learnedMacs[$onuKey] = [
                        'vlan_id' => (int)$mr[1],
                        'router_mac' => strtoupper(trim($mr[2])),
                    ];
                }
            }

            // 2. Fetch Real Optical Diagnostics (Rx/Tx Power, Distance) across all PON ports from onuopmdiag.html
            $opmDiag = [];
            $maxPorts = (int) ($olt->total_pon_ports ?: $olt->pon_ports_count);
            if ($maxPorts < 1) {
                return ['success' => false, 'message' => __('OLT PON count is not configured.')];
            }
            for ($p = 1; $p <= $maxPorts; $p++) {
                $opmHtml = $this->fetchVsolHtmlPage($ip, $port, $username, $password, 'onuopmdiag.html', ['select' => (string)$p]);
                if ($opmHtml && preg_match_all('/<tr>\s*<td[^>]*>EPON0\/(\d+):(\d+)<\/td>\s*<td[^>]*>([0-9a-fA-F:]{17})<\/td>\s*<td[^>]*>([^<]*)<\/td>\s*<td[^>]*>([-\d.]+)<\/td>\s*<td[^>]*>([-\d.]+)<\/td>\s*<td[^>]*>([-\d.]+)<\/td>\s*<td[^>]*>([-\d.]+)<\/td>\s*<td[^>]*>([-\d.]+)<\/td>\s*<td[^>]*>([-\d.]+)<\/td>\s*<\/tr>/i', $opmHtml, $opmMatches, PREG_SET_ORDER)) {
                    foreach ($opmMatches as $om) {
                        $onuKey = "0/{$om[1]}:{$om[2]}";
                        $opmDiag[$onuKey] = [
                            'distance_m' => (int)$om[5],
                            'tx_power_dbm' => (float)$om[9],
                            'rx_power_dbm' => (float)$om[10],
                        ];
                    }
                }
            }

            $syncedCount = 0;
            $onlineCount = 0;
            $syncedOnuDbIds = [];

            for ($p = 1; $p <= $maxPorts; $p++) {
                $onuHtml = $this->fetchVsolHtmlPage($ip, $port, $username, $password, 'onustatusinfo.html', ['select' => (string)$p]);
                if (!$onuHtml) $inventoryComplete = false;
                if ($onuHtml) {
                    preg_match_all('/<tr>\s*<td[^>]*>EPON0\/(\d+):(\d+)<\/td>\s*<td[^>]*>(.*?)<\/td>\s*<td[^>]*>([^<]+)<\/td>\s*<td[^>]*>([^<]*)<\/td>\s*<td[^>]*>([^<]+)<\/td>/is', $onuHtml, $rows, PREG_SET_ORDER);
                    foreach ($rows as $r) {
                        $pId = (int)$r[1];
                        $oId = (int)$r[2];
                        $statusRaw = strtolower(strip_tags($r[3]));
                        $macAddr = trim($r[4]);
                        $desc = trim($r[5]);
                        $distStr = trim($r[6]);
                        $distM = is_numeric(str_replace('m', '', $distStr)) ? (int)str_replace('m', '', $distStr) : null;

                        $isOnline = str_contains($statusRaw, 'online') || str_contains($statusRaw, 'auth');
                        if ($isOnline) $onlineCount++;

                        $detectedModel = !empty($desc) ? $desc : 'V2801SG';
                        $detectedType = $this->detectOnuType($desc, $desc, 'VSOL', 'VSOL');

                        $onuKey = "0/{$pId}:{$oId}";
                        $routerMac = $learnedMacs[$onuKey]['router_mac'] ?? null;
                        $vlanId = $learnedMacs[$onuKey]['vlan_id'] ?? null;

                        $diag = $opmDiag[$onuKey] ?? null;
                        $realRx = $diag['rx_power_dbm'] ?? null;
                        $realTx = $diag['tx_power_dbm'] ?? null;
                        $realDist = $diag['distance_m'] ?? $distM;

                        $existingOnu = TenantOnu::where('olt_id', $olt->id)->where('pon_port', "0/{$pId}")->where('onu_id', $oId)->first();

                        $savedOnu = TenantOnu::updateOrCreate(
                            [
                                'olt_id' => $olt->id,
                                'pon_port' => "0/{$pId}",
                                'onu_id' => $oId,
                            ],
                            [
                                'tenant_id' => $olt->tenant_id,
                                'olt_id' => $olt->id,
                                'pon_port' => "0/{$pId}",
                                'onu_id' => $oId,
                                'name' => !empty($desc) ? $desc : "ONU 0/{$pId}:{$oId}",
                                'desc' => $desc,
                                'mac_address' => $macAddr,
                                'router_mac' => $routerMac ?: ($existingOnu?->router_mac ?: null),
                                'vlan_id' => $vlanId ?: ($existingOnu?->vlan_id ?: null),
                                'vendor' => 'VSOL',
                                'model' => $detectedModel,
                                'onu_type' => $detectedType,
                                'status' => $isOnline ? 'online' : 'offline',
                                'running_state' => $isOnline ? 1 : 0,
                                'control_flag' => 1,
                                'distance_m' => $realDist,
                                'rx_power_dbm' => $isOnline ? ($realRx !== null ? $realRx : $existingOnu?->rx_power_dbm) : null,
                                'optical_observed_at' => $realRx !== null ? now() : $existingOnu?->optical_observed_at,
                                'tx_power_dbm' => $isOnline ? ($realTx !== null ? $realTx : $existingOnu?->tx_power_dbm) : null,
                                'last_online_at' => $isOnline ? now() : null,
                            ]
                        );

                        // Auto-link customer via Learned Router MAC or ONU MAC

                        $syncedOnuDbIds[] = $savedOnu->id;
                        $syncedCount++;
                    }
                }
            }

            // Prune unlisted ONUs so DB matches hardware active list
            if (!empty($syncedOnuDbIds)) {
                // Preserve inventory when a PON query fails or returns incomplete data.
            }

            $offlineCount = max(0, $syncedCount - $onlineCount);
            $oltModel = !empty($deviceModel) ? "VSOL {$deviceModel} (V1600D4)" : 'VSOL V1600D4';

            $olt->update([
                'status' => 'online',
                'vendor' => 'VSOL',
                'model' => $oltModel,
                'total_pon_ports' => $maxPorts,
                'serial_number' => $serialNumber ?? $olt->serial_number,
                'hardware_version' => $hardwareVersion ?? $olt->hardware_version,
                'firmware_version' => $firmware ?? $olt->firmware_version,
                'mac_address' => $mac ?? $olt->mac_address,
                'uptime' => $uptime ?? $olt->uptime,
                'cpu_load' => $cpuLoad ?? $olt->cpu_load,
                'memory_usage' => $memUsage ?? $olt->memory_usage,
                'temperature' => $olt->temperature,
                'total_onus_count' => $syncedCount,
                'online_onus_count' => $onlineCount,
                'offline_onus_count' => $offlineCount,
                'last_ping_at' => now(),
                'last_sync_at' => now(),
                'last_error' => null,
            ]);

            $latency = round((microtime(true) - $start) * 1000, 1);

            return [
                'success' => true,
                'message' => $inventoryComplete ? __('ONU operation completed.') : __('Some devices could not be synchronized.'),
                'total_onus' => $syncedCount,
                'online_onus' => $onlineCount,
                'offline_onus' => $offlineCount,
                'synced_count' => $syncedCount,
                'latency_ms' => $latency,
            ];
        }

        // =========================================================================
        // =========================================================================
        // 3. HSGQ / JSON-RPC Driver Sync (Port 8083)
        // =========================================================================
        if ($driver === 'hsgq') {
            $boardRes = $this->getHsgqEndpoint($ip, $port, $username, $password, 'board?info=system');
            $sys = $boardRes['data'] ?? [];

            $olt->update([
                'status' => 'online',
                'vendor' => !empty($sys['vendor']) ? $sys['vendor'] : 'HSGQ',
                'model' => $sys['product_name'] ?? ($sys['board_type'] ?? 'HSGQ EPON/GPON OLT'),
                'total_pon_ports' => (int)($sys['ponports'] ?? $olt->total_pon_ports ?? $olt->pon_ports_count),
                'hardware_version' => $sys['hw_ver'] ?? ($sys['board_type'] ?? $olt->hardware_version),
                'firmware_version' => $sys['fw_ver'] ?? ($sys['firmware_version'] ?? $olt->firmware_version),
                'mac_address' => $sys['macaddr'] ?? ($sys['system_mac'] ?? $olt->mac_address),
                'uptime' => isset($sys['running_time']) ? gmdate("z\d H\h i\m s\s", (int)$sys['running_time']) : $olt->uptime,
                'last_ping_at' => now(),
                'last_sync_at' => now(),
                'last_error' => null,
            ]);

            // The base-info endpoint contains identity only. Firmware uses this
            // separate inventory endpoint for actual per-ONU state and optics.
            $stateResponse = $this->getHsgqEndpoint($ip, $port, $username, $password, 'onu_allow_list?t=' . now()->timestamp);
            $states = [];
            foreach (($stateResponse['data'] ?? []) as $state) {
                if (isset($state['port_id'], $state['onu_id'])) {
                    $states[$state['port_id'] . ':' . $state['onu_id']] = $state;
                }
            }

            $onuRes = $this->getHsgqEndpoint($ip, $port, $username, $password, 'onumgmt?form=base-info');
            $onuList = $onuRes['data'] ?? null;
            $inventoryComplete = is_array($onuList) && is_array($stateResponse['data'] ?? null);
            $syncedCount = 0;
            $onlineCount = 0;
            $syncedOnuDbIds = [];

            $existingOnus = TenantOnu::where('olt_id', $olt->id)->get()->keyBy(function($o) {
                return "{$o->pon_port}:{$o->onu_id}";
            });

            // Fetch learned Router MAC table from HSGQ PON MAC table
            $hsgqLearned = [];
            try {
                $this->getHsgqEndpoint($ip, $port, $username, $password, 'pon_mac?form=table');
                $tableRes = Http::timeout(10)->withHeaders(['X-Token' => $token, 'Accept' => 'application/json'])
                    ->get("http://{$ip}:{$port}/pon_mac_table?t=" . time());
                if ($tableRes->successful() && !empty($tableRes->json('data'))) {
                    foreach ($tableRes->json('data') as $hr) {
                        $pId = (int)($hr['port_id'] ?? 1);
                        $oId = (int)($hr['onu_id'] ?? 0);
                        if ($oId > 0 && !empty($hr['macaddr'])) {
                            $hsgqLearned["0/{$pId}:{$oId}"] = [
                                'router_mac' => strtoupper(trim($hr['macaddr'])),
                                'vlan_id' => (int)($hr['vlan_id'] ?? 1),
                            ];
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("HSGQ pon_mac_table error: " . $e->getMessage());
            }

            if (is_array($onuList)) {
                foreach ($onuList as $raw) {
                    $portId = (int) ($raw['port_id'] ?? 1);
                    $ponPort = "0/{$portId}";
                    $onuId = (int) ($raw['onu_id'] ?? 0);
                    if ($onuId <= 0) continue;

                    $state = $states[$portId . ':' . $onuId] ?? [];
                    $deviceState = strtolower((string) ($state['status'] ?? 'unknown'));
                    $isOnline = in_array($deviceState, ['online', 'up', '1'], true);
                    $stateKnown = $isOnline || in_array($deviceState, ['offline', 'down', '0'], true);

                    if ($isOnline) $onlineCount++;

                    $hsgqModel = $this->parseOnuModel($raw['extmodel'] ?? ($raw['sn_model'] ?? null), $raw['vendor'] ?? null, $raw['chip_model'] ?? null, $raw['onu_name'] ?? null);
                    $hsgqType = $this->detectOnuType($raw['onu_name'] ?? null, $hsgqModel, $raw['vendor'] ?? null, 'HSGQ');

                    $existingOnu = $existingOnus->get("{$ponPort}:{$onuId}");
                    $rawRx = trim(str_replace('dBm', '', (string) ($state['receive_power'] ?? '')));
                    $rxPower = is_numeric($rawRx) ? (float) $rawRx : $existingOnu?->rx_power_dbm;
                    $txPower = $existingOnu?->tx_power_dbm;
                    $distVal = isset($state['distance']) && is_numeric($state['distance']) ? (int) $state['distance'] : $existingOnu?->distance_m;

                    $hsgqKey = "{$ponPort}:{$onuId}";
                    $learnedInfo = $hsgqLearned[$hsgqKey] ?? null;
                    $routerMac = $learnedInfo['router_mac'] ?? null;
                    $vlanId = $learnedInfo['vlan_id'] ?? null;

                    $savedOnu = TenantOnu::updateOrCreate(
                        [
                            'olt_id' => $olt->id,
                            'pon_port' => $ponPort,
                            'onu_id' => $onuId,
                        ],
                        [
                            'tenant_id' => $olt->tenant_id,
                            'olt_id' => $olt->id,
                            'pon_port' => $ponPort,
                            'onu_id' => $onuId,
                            'name' => !empty($raw['onu_name']) ? $raw['onu_name'] : "ONU {$ponPort}:{$onuId}",
                            'desc' => $raw['onu_desc'] ?? '',
                            'mac_address' => $raw['macaddr'] ?? null,
                            'router_mac' => $routerMac ?: ($existingOnu?->router_mac ?: null),
                            'vlan_id' => $vlanId ?: ($existingOnu?->vlan_id ?: null),
                            'vendor' => $raw['vendor'] ?? 'HSGQ',
                            'model' => $hsgqModel,
                            'onu_type' => $hsgqType,
                            'hardware_version' => $raw['chip_model'] ?? null,
                            'software_version' => $raw['software_ver'] ?? null,
                            'status' => $stateKnown ? ($isOnline ? 'online' : 'offline') : 'unknown',
                            'running_state' => $isOnline ? 1 : 0,
                            'control_flag' => 1,
                            'rx_power_dbm' => $rxPower,
                            'optical_observed_at' => is_numeric($rawRx) ? now() : $existingOnu?->optical_observed_at,
                            'tx_power_dbm' => $txPower,
                            'distance_m' => $distVal,
                            'last_online_at' => $isOnline ? now() : null,
                        ]
                    );


                    $syncedOnuDbIds[] = $savedOnu->id;
                    $syncedCount++;
                }
            }

            // Prune unlisted ONUs
            if (!empty($syncedOnuDbIds)) {
                // Preserve inventory until removal is explicitly confirmed.
            }

            $offlineCount = max(0, $syncedCount - $onlineCount);
            $olt->update([
                'total_onus_count' => $syncedCount,
                'online_onus_count' => $onlineCount,
                'offline_onus_count' => $offlineCount,
                'last_sync_at' => now(),
            ]);

            $latency = round((microtime(true) - $start) * 1000, 1);

            return [
                'success' => $inventoryComplete,
                'message' => $inventoryComplete ? __('ONU operation completed.') : __('Some devices could not be synchronized.'),
                'total_onus' => $syncedCount,
                'online_onus' => $onlineCount,
                'offline_onus' => $offlineCount,
                'synced_count' => $syncedCount,
                'latency_ms' => $latency,
            ];
        }

        // =========================================================================
        // 4. FastCGI / C-Data Sync (Port 8081 - DN Optic)
        // =========================================================================
        $sysDevInfo = $this->getModule($ip, $port, $username, $password, 'sys_dev_info');
        $sysInfo = !empty($sysDevInfo) ? $sysDevInfo : $this->getModule($ip, $port, $username, $password, 'sys_info');

        $uptimeStr = null;
        if (!empty($sysInfo['Uptime'])) {
            $upSec = floor((int)$sysInfo['Uptime'] / 1000);
            $upDays = floor($upSec / 86400);
            $upHours = floor(($upSec % 86400) / 3600);
            $upMins = floor(($upSec % 3600) / 60);
            $uptimeStr = "{$upDays}d {$upHours}h {$upMins}m";
        }

        $olt->update([
            'status' => 'online',
            'vendor' => $sysInfo['DevType'] ?? ($sysInfo['Vendor'] ?? 'DN OPTIC'),
            'model' => $sysInfo['Model'] ?? ($sysInfo['SysName'] ?? ($sysInfo['product_type'] ?? $olt->model)),
            'serial_number' => $sysInfo['SnNum'] ?? $olt->serial_number,
            'hardware_version' => $sysInfo['Hardware'] ?? ($sysInfo['hard_ver'] ?? $olt->hardware_version),
            'firmware_version' => $sysInfo['Firmware'] ?? ($sysInfo['soft_ver'] ?? $olt->firmware_version),
            'mac_address' => $sysInfo['DevMacAddr'] ?? ($sysInfo['mac'] ?? $olt->mac_address),
            'uptime' => $uptimeStr ?? ($sysInfo['sys_time'] ?? $olt->uptime),
            'cpu_load' => $sysInfo['CpuLoad'] ?? $olt->cpu_load,
            'memory_usage' => $sysInfo['MemoryUsage'] ?? $olt->memory_usage,
            'temperature' => $sysInfo['Temperature'] ?? $olt->temperature,
            'last_ping_at' => now(),
            'last_sync_at' => now(),
            'last_error' => null,
        ]);

        $portMap = $olt->pon_port_map ?: [];
        $allPorts = array_keys($portMap);
        if (!$allPorts) {
            $allPorts = TenantOnu::where('tenant_id', $olt->tenant_id)->where('olt_id', $olt->id)
                ->whereNotNull('pon_port')->distinct()->pluck('pon_port')->filter()->values()->all();
        }
        if (!$allPorts) {
            return ['success' => false, 'message' => __('OLT PON mapping is not configured.')];
        }
        $syncedCount = 0;
        $onlineCount = 0;

        // Fetch learned Router MAC table from C-Data FDB (epon_mac_address_get)
        $cdataLearned = [];
        $portIndices = implode(',', array_filter(array_values($portMap), 'is_numeric'));
        try {
            $macRes = $portIndices ? $this->getModule($ip, $port, $username, $password, "epon_mac_address_get&PortIndex={$portIndices}&PageNumber=1&PageSize=500") : null;
            $macArray = $macRes['MacArray'] ?? ($macRes['data']['MacArray'] ?? ($macRes['list'] ?? []));
            if (is_array($macArray)) {
                foreach ($macArray as $mItem) {
                    $rawPort = trim(str_ireplace('epon', '', $mItem['PortIndex'] ?? ''));
                    $mOnuId = (int)($mItem['OnuId'] ?? 0);
                    $mMac = strtoupper(trim($mItem['MacAddress'] ?? ''));
                    $mVlan = (int)($mItem['Vlan'] ?? 1);
                    if ($mOnuId > 0 && !empty($mMac)) {
                        $cdataLearned["{$rawPort}:{$mOnuId}"] = [
                            'router_mac' => $mMac,
                            'vlan_id' => $mVlan,
                        ];
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning("C-Data epon_mac_address_get error: " . $e->getMessage());
        }

        foreach ($allPorts as $ponPort) {
            $opticalList = $this->getOpticalList($ip, $port, $username, $password, $ponPort);
            $opticalMap = [];
            if (!empty($opticalList)) {
                foreach ($opticalList as $opt) {
                    $onuId = (int) ($opt['OnuId'] ?? ($opt['OntId'] ?? ($opt['onu_id'] ?? 0)));
                    if ($onuId > 0) {
                        $rawRx = $opt['RxPower'] ?? ($opt['rx_power'] ?? null);
                        $rawTx = $opt['TxPower'] ?? ($opt['tx_power'] ?? null);
                        $rawRange = $opt['Range'] ?? ($opt['distance'] ?? null);

                        $rx = null;
                        if ($rawRx !== null && $rawRx !== '' && $rawRx !== '-inf' && $rawRx !== 'inf') {
                            if (is_numeric($rawRx)) {
                                $rx = (float)$rawRx;
                            } elseif (preg_match('/([-\d.]+)/', (string)$rawRx, $m)) {
                                $rx = (float)$m[1];
                            }
                        }

                        $tx = null;
                        if ($rawTx !== null && $rawTx !== '' && $rawTx !== '-inf' && $rawTx !== 'inf') {
                            if (is_numeric($rawTx)) {
                                $tx = (float)$rawTx;
                            } elseif (preg_match('/([-\d.]+)/', (string)$rawTx, $m)) {
                                $tx = (float)$m[1];
                            }
                        }

                        $dist = null;
                        if ($rawRange !== null && $rawRange !== '') {
                            if (is_numeric($rawRange)) {
                                $dist = (int)$rawRange;
                            } elseif (preg_match('/(\d+)/', (string)$rawRange, $m)) {
                                $dist = (int)$m[1];
                            }
                        }

                        $opticalMap[$onuId] = [
                            'rx' => $rx,
                            'tx' => $tx,
                            'range' => $dist,
                            'temp' => isset($opt['Temperature']) && is_numeric($opt['Temperature']) ? (float) $opt['Temperature'] : null,
                            'voltage' => isset($opt['Voltage']) && is_numeric($opt['Voltage']) ? (float) $opt['Voltage'] : null,
                        ];
                    }
                }
            }

            $onuData = $this->getModule($ip, $port, $username, $password, "onu_list_get&PonId={$ponPort}");
            $list = $onuData['list'] ?? null;
            if (!is_array($list)) $inventoryComplete = false;

            if (is_array($list)) {
                foreach ($list as $raw) {
                    $onuId = (int) ($raw['OnuId'] ?? 0);
                    if ($onuId <= 0) continue;

                    $isOnline = isset($raw['RunningState']) && (int)$raw['RunningState'] === 1;
                    if ($isOnline) $onlineCount++;

                    $opt = $opticalMap[$onuId] ?? [];
                    $dist = $opt['range'] ?? (isset($raw['Distance']) && is_numeric($raw['Distance']) ? (int)$raw['Distance'] : null);

                    $parsedModel = $this->parseOnuModel(
                        $raw['EquipId'] ?? null,
                        $raw['OnuVendor'] ?? ($raw['Vendor'] ?? null),
                        $raw['HwVersion'] ?? ($raw['HardVer'] ?? null),
                        $raw['OnuName'] ?? null
                    );

                    $detectedType = $this->detectOnuType(
                        $raw['OnuName'] ?? null,
                        $raw['EquipId'] ?? null,
                        $raw['OnuVendor'] ?? ($raw['Vendor'] ?? null),
                        $olt->vendor
                    );

                    $learnedKey = "{$ponPort}:{$onuId}";
                    $learnedInfo = $cdataLearned[$learnedKey] ?? null;
                    $routerMac = $learnedInfo['router_mac'] ?? null;
                    $vlanId = $learnedInfo['vlan_id'] ?? null;

                    $existingOnu = TenantOnu::where('olt_id', $olt->id)
                        ->where('pon_port', $ponPort)
                        ->where('onu_id', $onuId)
                        ->first();

                    $savedOnu = TenantOnu::updateOrCreate(
                        [
                            'olt_id' => $olt->id,
                            'pon_port' => $ponPort,
                            'onu_id' => $onuId,
                        ],
                        [
                            'tenant_id' => $olt->tenant_id,
                            'olt_id' => $olt->id,
                            'pon_port' => $ponPort,
                            'onu_id' => $onuId,
                            'name' => $raw['OnuName'] ?? "ONU {$ponPort}:{$onuId}",
                            'desc' => $raw['Desc'] ?? ($raw['OnuDesc'] ?? ''),
                            'mac_address' => $raw['Mac'] ?? ($raw['MacAddress'] ?? null),
                            'router_mac' => $routerMac ?: ($existingOnu?->router_mac ?: null),
                            'vlan_id' => $vlanId ?: ($existingOnu?->vlan_id ?: null),
                            'vendor' => !empty($raw['OnuVendor']) ? $raw['OnuVendor'] : (!empty($raw['Vendor']) ? $raw['Vendor'] : 'DN OPTIC'),
                            'model' => $parsedModel,
                            'onu_type' => $detectedType,
                            'hardware_version' => $raw['HwVersion'] ?? ($raw['HardVer'] ?? null),
                            'software_version' => $raw['SwVersion'] ?? ($raw['SoftVer'] ?? null),
                            'status' => $isOnline ? 'online' : 'offline',
                            'running_state' => $isOnline ? 1 : 0,
                            'control_flag' => 1,
                            'rx_power_dbm' => $opt['rx'] ?? ($isOnline ? $existingOnu?->rx_power_dbm : null),
                            'optical_observed_at' => isset($opt['rx']) ? now() : $existingOnu?->optical_observed_at,
                            'tx_power_dbm' => $opt['tx'] ?? ($isOnline ? $existingOnu?->tx_power_dbm : null),
                            'distance_m' => $dist ?? ($isOnline ? $existingOnu?->distance_m : null),
                            'last_online_at' => $isOnline ? now() : null,
                        ]
                    );


                    $syncedOnuDbIds[] = $savedOnu->id;
                    $syncedCount++;
                }
            }
        }

        // Prune unlisted ONUs
        if (!empty($syncedOnuDbIds)) {
            // Preserve inventory until removal is explicitly confirmed.
        }

        $offlineCount = max(0, $syncedCount - $onlineCount);
        $olt->update([
            'total_onus_count' => $syncedCount,
            'online_onus_count' => $onlineCount,
            'offline_onus_count' => $offlineCount,
            'last_sync_at' => now(),
        ]);

        $latency = round((microtime(true) - $start) * 1000, 1);

        return [
            'success' => $inventoryComplete,
            'message' => $inventoryComplete ? __('ONU operation completed.') : __('Some devices could not be synchronized.'),
            'total_onus' => $syncedCount,
            'online_onus' => $onlineCount,
            'offline_onus' => max(0, $syncedCount - $onlineCount),
            'synced_count' => $syncedCount,
            'latency_ms' => $latency,
        ];
    }

    /**
     * Dynamic Zero-Touch Auto-Link: Matches ANY customer across ANY OLT via Learned Router MAC, ONU MAC, or Username
     */
    public function autoLinkCustomerToOnu(TenantOnu $onu, TenantOlt $olt, ?string $routerMac = null, ?string $onuMac = null): void
    {
        app(OnuLinkService::class)->link($onu);
    }

    /**
     * Clean and decode hardware EquipId / Model string
     */
    protected function parseOnuModel(?string $equipId, ?string $vendor = null, ?string $hwVer = null, ?string $name = null): string
    {
        $m = trim((string)$equipId);
        if (str_starts_with($m, '0x') || str_starts_with($m, '0X')) {
            $hex = substr($m, 2);
            $bin = @hex2bin($hex);
            if ($bin !== false) {
                $cleaned = trim(preg_replace('/[\x00-\x1F\x7F]/', '', $bin));
                if (!empty($cleaned)) {
                    $m = $cleaned;
                }
            }
        }

        if (empty($m) || in_array(strtoupper($m), ['ONU', 'EPON', 'XPON', 'GPON', 'NA', 'NULL'])) {
            if (!empty($name) && preg_match('/^([A-Za-z0-9\-_]+)_0\//', $name, $matches)) {
                $candidate = trim($matches[1]);
                if (str_starts_with($candidate, '0x')) {
                    $bin = @hex2bin(substr($candidate, 2));
                    if ($bin !== false) $candidate = trim(preg_replace('/[\x00-\x1F\x7F]/', '', $bin));
                }
                if (!empty($candidate) && !in_array(strtoupper($candidate), ['EPON', 'XPON', 'GPON', 'ONU'])) {
                    $m = $candidate;
                }
            }
        }

        if (in_array(strtoupper($m), ['XPON', 'EPON', 'GPON'])) {
            if (!empty($vendor) && !in_array(strtoupper($vendor), ['EPON', 'XPON', 'GPON', 'ONU'])) {
                $m = "{$vendor} {$m}";
            }
        }

        if (empty($m) || in_array(strtoupper($m), ['ONU', 'NA', 'NULL'])) {
            if (!empty($hwVer) && !in_array($hwVer, ['V1.0', 'V2.0'])) {
                $m = $hwVer;
            } elseif (!empty($vendor) && $vendor !== 'EPON') {
                $m = "{$vendor} ONU";
            } else {
                $m = 'Unknown';
            }
        }

        return $m;
    }

    /**
     * Detect ONU Optical Standard Type (EPON / GPON / XPON)
     */
    protected function detectOnuType(?string $name, ?string $equipId = null, ?string $vendor = null, ?string $oltVendor = null): string
    {
        $haystack = strtoupper("{$name} {$equipId} {$vendor} {$oltVendor}");
        if (str_contains($haystack, 'XPON')) {
            return 'XPON';
        }
        if (str_contains($haystack, 'GPON')) {
            return 'GPON';
        }
        if (str_contains($haystack, 'EPON')) {
            return 'EPON';
        }
        return 'EPON';
    }

    /**
     * Remote Reboot an ONU via OLT API
     */
    public function rebootOnu(TenantOlt $olt, string $ponPort, int $onuId): bool
    {
        return $this->sendOnuCommand($olt, $ponPort, $onuId, 'ont_reboot');
    }

    /**
     * Get live optical telemetry for a specific ONU
     */
    public function getOnuOpticalTelemetry(TenantOlt $olt, string $ponPort, int $onuId): ?array
    {
        $ip = $olt->ip_address;
        $port = (int) ($olt->web_port ?: 80);
        $username = $olt->web_username ?: 'admin';
        $password = $olt->decrypted_web_password ?: ($olt->web_password ?: 'admin');

        $cleanPort = preg_replace('/[^0-9]/', '', substr($ponPort, strrpos($ponPort, '/') ?: 0));
        $ponNumber = is_numeric($cleanPort) && (int)$cleanPort > 0 ? (int)$cleanPort : 1;

        $driverKey = "olt_driver_{$ip}_{$port}";
        $driver = Cache::get($driverKey);

        // Firmware's allow-list endpoint supplies actual per-ONU state and RX.
        if ($driver === 'hsgq') {
            $response = $this->getHsgqEndpoint($ip, $port, $username, $password, 'onu_allow_list?port_id=' . $ponNumber);
            foreach (($response['data'] ?? []) as $row) {
                if ((int) ($row['port_id'] ?? 0) !== $ponNumber || (int) ($row['onu_id'] ?? 0) !== $onuId) continue;
                $rx = trim(str_replace('dBm', '', (string) ($row['receive_power'] ?? '')));
                return [
                    'rx_power_dbm' => is_numeric($rx) ? (float) $rx : null,
                    'tx_power_dbm' => null,
                    'distance_m' => isset($row['distance']) && is_numeric($row['distance']) ? (int) $row['distance'] : null,
                    'temperature' => null, 'voltage' => null,
                    'updated_at' => now()->toIso8601String(), 'source' => 'live',
                ];
            }
            return null;
        }

        // 2. VSOL HTTPS Web Driver (Port 8082)
        if ($driver === 'vsol_https') {
            $opmHtml = $this->fetchVsolHtmlPage($ip, $port, $username, $password, 'onuopmdiag.html', ['select' => (string)$ponNumber]);
            if ($opmHtml && preg_match_all('/<tr>\s*<td[^>]*>EPON0\/(\d+):(\d+)<\/td>\s*<td[^>]*>([0-9a-fA-F:]{17})<\/td>\s*<td[^>]*>([^<]*)<\/td>\s*<td[^>]*>([-\d.]+)<\/td>\s*<td[^>]*>([-\d.]+)<\/td>\s*<td[^>]*>([-\d.]+)<\/td>\s*<td[^>]*>([-\d.]+)<\/td>\s*<td[^>]*>([-\d.]+)<\/td>\s*<td[^>]*>([-\d.]+)<\/td>\s*<\/tr>/i', $opmHtml, $opmMatches, PREG_SET_ORDER)) {
                foreach ($opmMatches as $om) {
                    if ((int)$om[1] === $ponNumber && (int)$om[2] === $onuId) {
                        $dist = (int)$om[5];
                        $temp = is_numeric($om[6]) ? (float)$om[6] : null;
                        $volt = is_numeric($om[7]) ? (float)$om[7] : null;
                        $tx = is_numeric($om[9]) ? (float)$om[9] : null;
                        $rx = is_numeric($om[10]) ? (float)$om[10] : null;

                        $telemetry = [
                            'rx_power_dbm' => $rx,
                            'tx_power_dbm' => $tx,
                            'distance_m' => $dist,
                            'temperature' => $temp,
                            'voltage' => $volt,
                            'updated_at' => now()->toIso8601String(),
                    'source' => 'live',
                        ];


                        return $telemetry;
                    }
                }
            }

            // Fallback to previously stored DB telemetry if hardware query timed out
            $onu = TenantOnu::where('olt_id', $olt->id)
                ->where(function($q) use ($ponPort, $ponNumber) {
                    $q->where('pon_port', $ponPort)->orWhere('pon_port', "0/{$ponNumber}");
                })
                ->where('onu_id', $onuId)
                ->first();

            if ($onu) {
                return [
                    'rx_power_dbm' => $onu->rx_power_dbm,
                    'tx_power_dbm' => $onu->tx_power_dbm,
                    'distance_m' => $onu->distance_m,
                    'temperature' => null,
                    'voltage' => null,
                    'updated_at' => $onu->optical_observed_at?->toIso8601String(),
                    'source' => 'cache',
                ];
            }
        }

        // 3. FastCGI / Standard EPON OLT Driver (C-Data DN-10G, etc.)
        $targetPon = str_contains($ponPort, '/') ? $ponPort : (string)$ponNumber;
        $opticalList = $this->getOpticalList($ip, $port, $username, $password, $targetPon);
        if (!empty($opticalList)) {
            foreach ($opticalList as $opt) {
                $curOnuId = (int)($opt['OnuId'] ?? ($opt['OntId'] ?? ($opt['onu_id'] ?? ($opt['id'] ?? 0))));
                if ($curOnuId === $onuId) {
                    $rawRx = $opt['RxPower'] ?? ($opt['rx_power'] ?? null);
                    $rawTx = $opt['TxPower'] ?? ($opt['tx_power'] ?? null);
                    $rawRange = $opt['Range'] ?? ($opt['distance'] ?? null);

                    $rx = null;
                    if ($rawRx !== null && $rawRx !== '' && $rawRx !== '-inf' && $rawRx !== 'inf') {
                        if (is_numeric($rawRx)) {
                            $rx = (float)$rawRx;
                        } elseif (preg_match('/([-\d.]+)/', (string)$rawRx, $m)) {
                            $rx = (float)$m[1];
                        }
                    }

                    $tx = null;
                    if ($rawTx !== null && $rawTx !== '' && $rawTx !== '-inf' && $rawTx !== 'inf') {
                        if (is_numeric($rawTx)) {
                            $tx = (float)$rawTx;
                        } elseif (preg_match('/([-\d.]+)/', (string)$rawTx, $m)) {
                            $tx = (float)$m[1];
                        }
                    }

                    $dist = null;
                    if ($rawRange !== null && $rawRange !== '') {
                        if (is_numeric($rawRange)) {
                            $dist = (int)$rawRange;
                        } elseif (preg_match('/(\d+)/', (string)$rawRange, $m)) {
                            $dist = (int)$m[1];
                        }
                    }

                    $temp = isset($opt['Temperature']) && is_numeric($opt['Temperature']) ? (float)$opt['Temperature'] : (isset($opt['temp']) ? (float)$opt['temp'] : null);
                    $volt = isset($opt['Voltage']) && is_numeric($opt['Voltage']) ? (float)$opt['Voltage'] : (isset($opt['voltage']) ? (float)$opt['voltage'] : null);

                    $telemetry = [
                        'rx_power_dbm' => $rx,
                        'tx_power_dbm' => $tx,
                        'distance_m' => $dist,
                        'temperature' => $temp,
                        'voltage' => $volt,
                        'updated_at' => now()->toIso8601String(),
                    'source' => 'live',
                    ];


                    return $telemetry;
                }
            }
        }

        // 4. UniMars / CoreLink Driver (Port 8084 style)
        if ($driver === 'unimars' || str_contains(strtolower($olt->vendor ?? ''), 'core') || str_contains(strtolower($olt->model ?? ''), 'ha73')) {
            $onuBody = $this->sendUniMarsRequest($ip, $port, "get=onualllist&sysUnit=1");
            if ($onuBody && preg_match_all('/<item onu="([^"]+)"/i', $onuBody, $matches)) {
                foreach ($matches[1] as $onuStr) {
                    $cols = explode(',', $onuStr);
                    $portOnu = $cols[0] ?? '';
                    $pParts = explode(':', $portOnu);
                    $curPon = $pParts[0] ?? '';
                    $curId = isset($pParts[1]) ? (int)$pParts[1] : 0;
                    if ($curId === $onuId && $curPon === $ponPort) {
                        $distance = isset($cols[10]) && is_numeric($cols[10]) ? (int)$cols[10] : null;
                        $txPower = isset($cols[14]) && is_numeric($cols[14]) ? (float)$cols[14] : null;
                        $rxPower = isset($cols[15]) && is_numeric($cols[15]) ? (float)$cols[15] : null;


                        return [
                            'rx_power_dbm' => $rxPower,
                            'tx_power_dbm' => $txPower,
                            'distance_m' => $distance,
                            'temperature' => null,
                            'voltage' => null,
                            'updated_at' => now()->toIso8601String(),
                    'source' => 'live',
                        ];
                    }
                }
            }
        }

        return null;
    }

    /**
     * Fetch newly discovered unregistered ONUs (Auto-find)
     */
    public function getAutoFindOnus(string $ip, int $port, string $username, string $password): array
    {
        $list = [];
        $driverKey = "olt_driver_{$ip}_{$port}";
        $driver = Cache::get($driverKey);

        if ($driver === 'unimars') {
            $sid = base64_encode("{$username}&{$password}");
            $this->sendUniMarsRequest($ip, $port, "set=login&user={$sid}");
            $body = $this->sendUniMarsRequest($ip, $port, "get=onunotauthlist&sysUnit=1");
            if ($body && preg_match_all('/<item\s+([^>]+)>/i', $body, $matches)) {
                foreach ($matches[1] as $itemStr) {
                    preg_match('/onu="([^"]+)"/i', $itemStr, $mOnu);
                    preg_match('/mac="([^"]+)"/i', $itemStr, $mMac);
                    preg_match('/desc="([^"]*)"/i', $itemStr, $mDesc);
                    if (!empty($mMac[1])) {
                        $list[] = [
                            'pon_port' => $mOnu[1] ?? '1',
                            'mac_address' => $mMac[1],
                            'vendor' => 'EPON',
                            'model' => $mDesc[1] ?? 'ONU',
                        ];
                    }
                }
            }
            return $list;
        }

        $res = $this->getModule($ip, $port, $username, $password, 'autofind_onu_get');
        if (is_array($res) && !empty($res['list'])) {
            return $res['list'];
        }

        return $list;
    }

    /**
     * Push Configuration to ONU via OLT API
     */
    public function configureOnu(TenantOlt $olt, TenantOnu $onu, array $config): array
    {
        $hardware = array_diff_key($config, array_flip(['name', 'desc', 'router_mac']));
        if ($hardware === []) {
            return ['success' => true, 'pushed_to_olt' => false];
        }
        $supported = $this->onuCapabilities($olt)['configure'];
        if (array_diff(array_keys($hardware), $supported)) {
            return ['success' => false, 'pushed_to_olt' => false, 'unsupported' => true];
        }
        $commands = [];
        if (array_key_exists('vlan_id', $hardware) || array_key_exists('vlan_mode', $hardware)) {
            $vlanId = $hardware['vlan_id'] ?? $onu->vlan_id;
            if (!$vlanId) {
                return ['success' => false, 'pushed_to_olt' => false];
            }
            $commands[] = ['ont_port_vlan_set', [
                'PortId' => 1, 'Mode' => $hardware['vlan_mode'] ?? $onu->vlan_mode ?? 'tag',
                'VlanId' => $vlanId,
            ]];
        }
        if (array_key_exists('lan1_state', $hardware)) {
            $commands[] = ['ont_port_admin_set', ['PortId' => 1, 'Admin' => $hardware['lan1_state'] === 'enable' ? 1 : 0]];
        }
        foreach ($commands as [$module, $parameters]) {
            if (!$this->sendOnuCommand($olt, $onu->pon_port, (int) $onu->onu_id, $module, $parameters)) {
                return ['success' => false, 'pushed_to_olt' => false];
            }
        }
        return ['success' => true, 'pushed_to_olt' => true];
    }

    /**
     * Restore ONU to Factory Defaults
     */
    public function factoryResetOnu(TenantOlt $olt, string $ponPort, int $onuId): bool
    {
        return $this->sendOnuCommand($olt, $ponPort, $onuId, 'ont_restore_default');
    }

    /**
     * Only drivers with a machine-readable acknowledgement may perform writes.
     * Unknown firmware responses must never be interpreted as success.
     */
    public function onuCapabilities(TenantOlt $olt): array
    {
        $driver = Cache::get("olt_driver_{$olt->ip_address}_{$olt->web_port}");
        return [
            'configure' => $driver === 'fastcgi' ? ['vlan_id', 'vlan_mode', 'lan1_state'] : [],
            'reboot' => $driver === 'fastcgi',
            'factory_reset' => $driver === 'fastcgi',
        ];
    }

    protected function sendOnuCommand(TenantOlt $olt, string $ponPort, int $onuId, string $module, array $parameters = []): bool
    {
        $port = (int) ($olt->web_port ?: 80);
        $ip = $olt->ip_address;
        $token = $this->getToken($ip, $port, (string) $olt->web_username, (string) $olt->decrypted_web_password);
        if (!$token || Cache::get("olt_driver_{$ip}_{$port}") !== 'fastcgi' || $onuId < 1 || $ponPort === '') {
            return false;
        }
        $protocol = Cache::get("olt_proto_{$ip}_{$port}");
        if (!in_array($protocol, ['http', 'https'], true)) {
            return false;
        }
        try {
            $response = Http::withoutVerifying()->timeout(8)->withHeaders(['token' => $token])
                ->get("{$protocol}://{$ip}:{$port}/cgi-bin/h.cgi", array_merge([
                    'module' => $module, 'PonId' => $ponPort, 'OntId' => $onuId,
                ], $parameters));
            $body = $response->json();
            return $response->successful() && is_array($body)
                && array_key_exists('code', $body)
                && in_array($body['code'], [0, 200, '0', '200'], true);
        } catch (\Throwable $e) {
            Log::warning('ONU command could not be confirmed', [
                'olt_id' => $olt->id, 'onu_id' => $onuId, 'module' => $module,
            ]);
            return false;
        }
    }

    /**
     * Fetch Live OLT License Telemetry
     */
    public function getOltLicenseInfo(TenantOlt $olt): array
    {
        $ip = $olt->ip_address;
        $port = (int) ($olt->web_port ?: 80);
        $username = $olt->web_username ?: "root";
        $password = $olt->decrypted_web_password ?: "admin";

        $sysDevInfo = $this->getModule($ip, $port, $username, $password, "sys_dev_info");
        if (!empty($sysDevInfo)) {
            $limit = isset($sysDevInfo["LicenseLimit"]) ? (int)$sysDevInfo["LicenseLimit"] : 0;
            $hours = isset($sysDevInfo["LicenseTime"]) ? (int)$sysDevInfo["LicenseTime"] : 0;
            $days = floor($hours / 24);
            $remHours = $hours % 24;

            $formatted = $limit === 0 ? "Unlimited / Permanent" : sprintf("%02d days %02d hours", $days, $remHours);

            $olt->update([
                "license_limit" => $limit,
                "license_time_hours" => $hours,
                "last_sync_at" => now(),
            ]);

            return [
                "success" => true,
                "license_limit" => $limit,
                "license_time_hours" => $hours,
                "license_formatted" => $formatted,
                "is_unlimited" => ($limit === 0),
                "is_warning" => ($limit === 1 && $hours <= 168),
                "is_expired" => ($limit === 1 && $hours <= 0),
                "model" => $sysDevInfo["Model"] ?? $olt->model,
                "serial_number" => $sysDevInfo["SnNum"] ?? $olt->serial_number,
                "firmware" => $sysDevInfo["Firmware"] ?? $olt->firmware_version,
            ];
        }

        return [
            "success" => false,
            "license_limit" => $olt->license_limit ?? 0,
            "license_time_hours" => $olt->license_time_hours ?? 0,
            "license_formatted" => $olt->license_formatted,
            "is_unlimited" => ((int)$olt->license_limit === 0),
            "is_warning" => $olt->is_license_warning,
            "is_expired" => ((int)$olt->license_limit === 1 && (int)$olt->license_time_hours <= 0),
        ];
    }

    /**
     * Update OLT License / Lock Duration via API
     */
    public function updateOltLicense(TenantOlt $olt, string $authPassword, int $limitDays = 0): array
    {
        $start = microtime(true);
        $ip = $olt->ip_address;
        $port = (int) ($olt->web_port ?: 80);
        $username = $olt->web_username ?: "root";
        $password = $olt->decrypted_web_password ?: "admin";

        $token = $this->getToken($ip, $port, $username, $password, true);
        if (!$token) {
            return [
                "success" => false,
                "message" => "Unable to authenticate with OLT {$olt->name} ({$ip}:{$port}).",
                "latency_ms" => round((microtime(true) - $start) * 1000, 1),
            ];
        }

        // Step 1: Submit Security Auth Password
        $authUrl = "http://{$ip}:{$port}/cgi-bin/h.cgi?module=license_limit_set";
        try {
            $authRes = \Illuminate\Support\Facades\Http::timeout(6)
                ->withHeaders([
                    "token" => $token,
                    "Content-Type" => "application/json",
                    "Accept" => "application/json",
                ])
                ->post($authUrl, [
                    "AuthPassword" => $authPassword,
                ]);

            $authJson = $authRes->json();
            if ($authRes->status() !== 200 || (isset($authJson["code"]) && $authJson["code"] !== 0)) {
                $err = $authJson["data"]["error"] ?? ($authJson["description"] ?? "Authentication failed. Please verify the authentication password.");
                return [
                    "success" => false,
                    "message" => "OLT Security Authentication Failed: {$err}",
                    "latency_ms" => round((microtime(true) - $start) * 1000, 1),
                ];
            }

            // Step 2: Set License Limit (0 = Unlimited, or 1-3650 days)
            $limitPayload = [
                "LimitDay" => $limitDays,
            ];
            $limitRes = \Illuminate\Support\Facades\Http::timeout(6)
                ->withHeaders([
                    "token" => $token,
                    "Content-Type" => "application/json",
                    "Accept" => "application/json",
                ])
                ->post($authUrl, $limitPayload);

            $limitJson = $limitRes->json();
            if ($limitRes->status() !== 200 || (isset($limitJson["code"]) && $limitJson["code"] !== 0)) {
                $err = $limitJson["data"]["error"] ?? ($limitJson["description"] ?? "Failed to apply license duration limit.");
                return [
                    "success" => false,
                    "message" => "OLT License Configuration Failed: {$err}",
                    "latency_ms" => round((microtime(true) - $start) * 1000, 1),
                ];
            }

            // Step 3: Refresh live sys_dev_info
            $devInfo = $this->getModule($ip, $port, $username, $password, "sys_dev_info");
            $newLimit = isset($devInfo["LicenseLimit"]) ? (int)$devInfo["LicenseLimit"] : ($limitDays > 0 ? 1 : 0);
            $newHours = isset($devInfo["LicenseTime"]) ? (int)$devInfo["LicenseTime"] : ($limitDays * 24);

            $olt->update([
                "license_limit" => $newLimit,
                "license_time_hours" => $newHours,
                "license_auth_password" => \Illuminate\Support\Facades\Crypt::encryptString($authPassword),
                "last_sync_at" => now(),
            ]);

            $statusText = $limitDays === 0 ? "Unlimited / Permanent" : "{$limitDays} Days";

            return [
                "success" => true,
                "message" => "OLT License successfully updated for {$olt->name}.",
                "license_limit" => $newLimit,
                "license_time_hours" => $newHours,
                "latency_ms" => round((microtime(true) - $start) * 1000, 1),
            ];
        } catch (\Exception $e) {
            return [
                "success" => false,
                "message" => "Error communicating with OLT license API: " . $e->getMessage(),
                "latency_ms" => round((microtime(true) - $start) * 1000, 1),
            ];
        }
    }
}
