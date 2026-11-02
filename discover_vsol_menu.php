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

$pages = ['macinfo.html', 'mac.html', 'macList.html', 'onuopmdiag.html'];

foreach ($pages as $p) {
    echo "\n==================== FETCHING {$p} ====================\n";
    $res = $oltService->fetchVsolHtmlPage($ip, $port, $username, $password, $p);
    echo "Length: " . strlen($res ?? '') . "\n";
    if ($res) {
        // Search for target router MAC A8:31:62:02:15:77 or ONU MAC 4C:AE:1C:97:D7:10
        if (str_contains(strtolower($res), 'a8:31:62') || str_contains(strtolower($res), 'a8-31-62') || str_contains(strtolower($res), 'a83162')) {
            echo "  >>> ROUTER MAC A8:31:62:02:15:77 FOUND IN {$p}!\n";
        }
        if (str_contains(strtolower($res), '4c:ae:1c') || str_contains(strtolower($res), '4cae1c')) {
            echo "  >>> ONU MAC 4C:AE:1C:97:D7:10 FOUND IN {$p}!\n";
        }
        echo substr(strip_tags($res), 0, 1000) . "\n";
    }
}
