<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

header('Content-Type: text/plain');

$olt = App\Models\TenantOlt::find(4);
$api = new App\Services\Network\OltApiService();

$ip = $olt->ip_address;
$port = (int)$olt->web_port;
$username = $olt->web_username;
$password = $olt->decrypted_web_password;

$sid = base64_encode("{$username}&{$password}");
$loginRes = $api->sendUniMarsRequest($ip, $port, "set=login&user={$sid}");
echo "--- LOGIN RESPONSE ---\n" . $loginRes . "\n\n";

$sysinfo = $api->sendUniMarsRequest($ip, $port, "get=sysinfo2&sysunit=1");
echo "--- SYSINFO RESPONSE ---\n" . $sysinfo . "\n\n";

// Let's test different commands for ONU list on HA7302V / CoreLink / UniMars:
$cmds = [
    "get=onualllist&sysUnit=1",
    "get=onulist&sysUnit=1",
    "get=onustatus&sysUnit=1",
    "get=onu_list&sysUnit=1",
    "get=all_onu&sysUnit=1",
    "get=onuinfo&sysUnit=1",
    "get=onualllist&sysunit=1",
    "get=onulist&pon=1",
    "get=onulist&pon=2",
    "get=onu_table",
];

foreach ($cmds as $cmd) {
    $res = $api->sendUniMarsRequest($ip, $port, $cmd);
    echo "=== CMD: {$cmd} ===\n" . substr($res, 0, 500) . "\n\n";
}
