<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

header('Content-Type: application/json');

$olt = App\Models\TenantOlt::find(4);
if (!$olt) {
    $all = App\Models\TenantOlt::all();
    echo json_encode([
        'error' => 'OLT 4 not found',
        'available_olts' => $all->map(fn($o) => [
            'id' => $o->id,
            'name' => $o->name,
            'ip' => $o->ip_address,
            'web_port' => $o->web_port,
            'brand' => $o->brand,
            'status' => $o->status,
        ])
    ], JSON_PRETTY_PRINT);
    exit;
}

$api = new App\Services\Network\OltApiService();
$syncResult = $api->syncOlt($olt);

$onusCount = App\Models\TenantOnu::where('olt_id', $olt->id)->count();

echo json_encode([
    'olt' => [
        'id' => $olt->id,
        'name' => $olt->name,
        'ip_address' => $olt->ip_address,
        'web_port' => $olt->web_port,
        'web_username' => $olt->web_username,
        'has_password' => !empty($olt->decrypted_web_password),
        'brand' => $olt->brand,
        'model' => $olt->model,
        'status' => $olt->status,
        'total_onus_in_olt_record' => $olt->total_onus,
        'actual_onus_in_db' => $onusCount,
    ],
    'sync_result' => $syncResult,
], JSON_PRETTY_PRINT);
