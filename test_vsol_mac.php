<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$oltService = app(\App\Services\Network\OltApiService::class);
$vsol = \App\Models\TenantOlt::find(1);

$ip = $vsol->ip_address;
$port = (int)$vsol->web_port;
$username = $vsol->web_username;
$password = $vsol->decrypted_web_password;

echo "Querying VSOL OLT {$ip}:{$port}...\n";

$pages = [
    'macaddress.html',
    'maclist.html',
    'mactable.html',
    'mac_table.html',
    'mac_list.html',
    'fdb.html',
    'fdblist.html',
    'onumactable.html',
    'onumac.html',
    'onu_mac.html',
    'onumaclist.html',
];

foreach ($pages as $p) {
    $res = $oltService->fetchVsolHtmlPage($ip, $port, $username, $password, $p);
    if ($res) {
        echo "PAGE SUCCESS: {$p} (Length: " . strlen($res) . ")\n";
        // Check if target mac is in page
        if (str_contains(strtolower($res), 'a8:31:62') || str_contains(strtolower($res), 'a83162') || str_contains(strtolower($res), 'a8-31-62')) {
            echo "  ==> TARGET MAC A8:31:62:02:15:77 FOUND IN {$p}!\n";
        }
        // Print preview of first 500 chars
        echo substr(strip_tags($res), 0, 300) . "\n-------------------------\n";
    } else {
        echo "PAGE FAILED/404: {$p}\n";
    }
}

// Let's also check optical power page in VSOL!
// The user noted: Receive Power: 0.02 mW (-16.52 dBm)
echo "\nChecking onustatusinfo.html for port 1...\n";
$onuHtml = $oltService->fetchVsolHtmlPage($ip, $port, $username, $password, 'onustatusinfo.html', ['select' => '1']);
if ($onuHtml) {
    echo "onustatusinfo.html select=1:\n";
    echo substr(strip_tags($onuHtml), 0, 600) . "\n";
}

// Also check optical power page
$powerPages = [
    'onuopticalinfo.html',
    'opticalinfo.html',
    'onu_optical.html',
    'ponoptical.html',
    'ponstatusinfo.html'
];
foreach ($powerPages as $p) {
    $res = $oltService->fetchVsolHtmlPage($ip, $port, $username, $password, $p, ['select' => '1']);
    if ($res) {
        echo "POWER PAGE FOUND: {$p}!\n";
        echo substr(strip_tags($res), 0, 400) . "\n";
    }
}
