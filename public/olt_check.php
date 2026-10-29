<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

header('Content-Type: text/plain');

$olt = App\Models\TenantOlt::find(4);
$ip = $olt->ip_address;
$port = (int)$olt->web_port;

$fp = @fsockopen($ip, $port, $errno, $errstr, 5);
if ($fp) {
    $req = "GET / HTTP/1.1\r\nHost: {$ip}:{$port}\r\nUser-Agent: Mozilla/5.0\r\nConnection: close\r\n\r\n";
    fwrite($fp, $req);
    $res = '';
    while (!feof($fp)) $res .= fgets($fp, 1024);
    fclose($fp);
    echo $res;
}
