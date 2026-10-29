<?php
header('Content-Type: text/plain');
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$olt = App\Models\TenantOlt::find(4);
echo "DB USERNAME: [{$olt->web_username}]\n";
echo "DB PASSWORD: [{$olt->decrypted_web_password}]\n\n";

// Let's test with admin / admin606245:
$u = "admin";
$p = "admin606245";

$sid = base64_encode("{$u}&{$p}");
echo "SID: {$sid}\n";

$body = "set=login&user=" . urlencode($sid);

$fp = @fsockopen("103.59.177.137", 8084, $errno, $errstr, 5);
if ($fp) {
    $len = strlen($body);
    $req = "POST /sw.cgi HTTP/1.1\r\n" .
           "Host: 103.59.177.137:8084\r\n" .
           "User-Agent: Mozilla/5.0\r\n" .
           "Content-Type: application/x-www-form-urlencoded; charset=UTF-8\r\n" .
           "Referer: http://103.59.177.137:8084/\r\n" .
           "Content-Length: {$len}\r\n" .
           "Connection: keep-alive\r\n\r\n" .
           $body;
    fwrite($fp, $req);
    $res = '';
    while (!feof($fp)) {
        $line = fgets($fp, 1024);
        $res .= $line;
        if (trim($line) === '</xml>' || trim($line) === '</html>') break;
    }
    echo "=== LOGIN WITH admin/admin606245 ===\n" . $res . "\n\n";

    // Now send get=sysinfo or onu list on the SAME socket:
    $req2 = "POST /sw.cgi HTTP/1.1\r\n" .
            "Host: 103.59.177.137:8084\r\n" .
            "User-Agent: Mozilla/5.0\r\n" .
            "Content-Type: application/x-www-form-urlencoded; charset=UTF-8\r\n" .
            "Referer: http://103.59.177.137:8084/m/onu_all_onu.htm\r\n" .
            "Content-Length: 24\r\n" .
            "Connection: close\r\n\r\n" .
            "get=onualllist&sysUnit=1";
    fwrite($fp, $req2);
    $res2 = '';
    while (!feof($fp)) {
        $res2 .= fgets($fp, 1024);
    }
    fclose($fp);
    echo "=== GET ONU ON SAME SOCKET ===\n" . $res2 . "\n\n";
}
