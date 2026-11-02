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

$sysHtml = $oltService->fetchVsolHtmlPage($ip, $port, $username, $password, 'systeminfo.html');
echo "SYSTEM INFO HTML LENGTH: " . strlen($sysHtml ?? '') . "\n";
if ($sysHtml) {
    preg_match_all('/[a-zA-Z0-9_-]+\.html/i', $sysHtml, $matches);
    echo "ALL HTML REFERENCES IN SYSTEMINFO.HTML:\n";
    print_r(array_unique($matches[0] ?? []));
}

// Also check index.html
$idxHtml = $oltService->fetchVsolHtmlPage($ip, $port, $username, $password, 'index.html');
if ($idxHtml) {
    preg_match_all('/[a-zA-Z0-9_-]+\.html/i', $idxHtml, $m2);
    echo "ALL HTML REFERENCES IN INDEX.HTML:\n";
    print_r(array_unique($m2[0] ?? []));
}
