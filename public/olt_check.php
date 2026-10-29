<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

header('Content-Type: text/plain');

$olt = App\Models\TenantOlt::find(4);
$ip = $olt->ip_address;
$port = (int)$olt->web_port;
$username = $olt->web_username;
$password = $olt->decrypted_web_password;

function rawHttp($ip, $port, $body, $cookie = '') {
    $fp = @fsockopen($ip, $port, $errno, $errstr, 5);
    if (!$fp) return "FAILED TO CONNECT: $errstr ($errno)";

    $len = strlen($body);
    $req = "POST /sw.cgi HTTP/1.1\r\n" .
           "Host: {$ip}:{$port}\r\n" .
           "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\n" .
           "Accept: */*\r\n" .
           "Content-Type: uni_mars_ap\r\n" .
           "Referer: http://{$ip}:{$port}/m/onu_all_onu.htm\r\n";
    if ($cookie) {
        $req .= "Cookie: {$cookie}\r\n";
    }
    $req .= "Content-Length: {$len}\r\n" .
            "Connection: close\r\n\r\n" .
            $body;

    fwrite($fp, $req);
    $res = '';
    while (!feof($fp)) {
        $res .= fgets($fp, 1024);
    }
    fclose($fp);
    return $res;
}

$sid = base64_encode("{$username}&{$password}");
$res1 = rawHttp($ip, $port, "set=login&user={$sid}");
echo "=== LOGIN RAW RESPONSE ===\n" . $res1 . "\n\n";

// Check if any cookie in headers
preg_match_all('/Set-Cookie:\s*([^;\r\n]+)/i', $res1, $cMatches);
$cookie = implode('; ', $cMatches[1] ?? []);
echo "EXTRACTED COOKIE: [{$cookie}]\n\n";

// What if we send login and query in the same persistent connection, or send user credentials with query?
// Or does UniMars / HA7302V accept credentials in the request or requires a specific session/token?
// Let's test with cookie:
$res2 = rawHttp($ip, $port, "get=onualllist&sysUnit=1", $cookie);
echo "=== GET ONU WITH COOKIE ===\n" . $res2 . "\n\n";

// What if we test GET /index.htm or GET /login.htm to see what web server it is?
function rawHttpGet($ip, $port, $path) {
    $fp = @fsockopen($ip, $port, $errno, $errstr, 5);
    if (!$fp) return "FAILED";
    $req = "GET {$path} HTTP/1.1\r\nHost: {$ip}:{$port}\r\nUser-Agent: Mozilla/5.0\r\nConnection: close\r\n\r\n";
    fwrite($fp, $req);
    $res = '';
    while (!feof($fp)) $res .= fgets($fp, 1024);
    fclose($fp);
    return substr($res, 0, 500);
}

echo "=== GET / ===\n" . rawHttpGet($ip, $port, '/') . "\n\n";
echo "=== GET /index.html ===\n" . rawHttpGet($ip, $port, '/index.html') . "\n\n";
echo "=== GET /login.html ===\n" . rawHttpGet($ip, $port, '/login.html') . "\n\n";
