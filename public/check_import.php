<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::first();
Illuminate\Support\Facades\Auth::login($user);

$ctrl = new App\Http\Controllers\Tenant\Reseller\ResellerDirectoryController();
$uploaded = new Illuminate\Http\UploadedFile(
    __DIR__ . '/resellers_import.csv',
    'resellers_import.csv',
    'text/csv',
    null,
    true
);

$request = Illuminate\Http\Request::create('/admin/resellers/import', 'POST', [
    'duplicate_action' => 'update'
], [], ['file' => $uploaded]);

try {
    $response = $ctrl->importProcess($request);
    echo "SUCCESS: " . $response->getContent() . PHP_EOL;
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL . $e->getTraceAsString() . PHP_EOL;
}

echo "Total Resellers: " . App\Models\TenantReseller::count() . PHP_EOL;
foreach (App\Models\TenantReseller::all() as $r) {
    echo "#{$r->id} | {$r->name} | {$r->contact_person} | {$r->billing_type} | {$r->wallet_balance} | {$r->commission_rate}%\n";
}
