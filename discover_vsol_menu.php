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

$main = $oltService->fetchVsolHtmlPage($ip, $port, $username, $password, 'main.html');
echo "MAIN.HTML:\n" . substr($main, 0, 1500) . "\n-------------------------\n";

$loginRes = $oltService->fetchVsolHtmlPage($ip, $port, $username, $password, 'main.html', [
    'user' => $username,
    'pass' => $password,
    'who'  => '100'
]);
echo "LOGIN RESPONSE:\n" . substr($loginRes, 0, 1500) . "\n";
