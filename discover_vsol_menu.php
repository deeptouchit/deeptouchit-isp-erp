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

$html = $oltService->fetchVsolHtmlPage($ip, $port, $username, $password, 'macinfo.html');

echo "MATCHING ROWS IN macinfo.html:\n";
preg_match_all('/<tr>(.*?)<\/tr>/is', $html, $rows);
foreach ($rows[0] as $r) {
    if (str_contains(strtolower($r), 'a8:31:62') || str_contains(strtolower($r), '4c:ae:1c')) {
        echo trim(preg_replace('/\s+/', ' ', strip_tags($r))) . "\n";
        echo "RAW: " . trim($r) . "\n-------------------------\n";
    }
}
