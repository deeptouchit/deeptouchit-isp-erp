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

// 1. Fetch main.html to see frame/menu structure
$mainHtml = $oltService->fetchVsolHtmlPage($ip, $port, $username, $password, 'main.html');
echo "MAIN HTML LENGTH: " . strlen($mainHtml ?? '') . "\n";
if ($mainHtml) {
    preg_match_all('/(?:src|href)=["\']([^"\']+\.html)["\']/i', $mainHtml, $matches);
    echo "FRAMES / LINKS IN MAIN.HTML:\n";
    print_r(array_unique($matches[1] ?? []));
}

// 2. Fetch menu.html / nav.html / left.html
$menuPages = ['menu.html', 'left.html', 'nav.html', 'navigation.html', 'tree.html', 'header.html'];
foreach ($menuPages as $mp) {
    $mHtml = $oltService->fetchVsolHtmlPage($ip, $port, $username, $password, $mp);
    if ($mHtml) {
        echo "FOUND MENU PAGE {$mp}! All links inside:\n";
        preg_match_all('/(?:href|src|openPage|goPage)=["\']([^"\']+\.html)["\']/i', $mHtml, $links);
        print_r(array_unique($links[1] ?? []));
    }
}
