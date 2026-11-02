<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$cust = \App\Models\TenantCustomer::where('username', 'ashik826')->first();
echo "--- CUSTOMER ashik826 ---\n";
if ($cust) {
    echo "ID: " . $cust->id . "\n";
    echo "Name: " . $cust->name . "\n";
    echo "Username: " . $cust->username . "\n";
    echo "Customer ID: " . $cust->customer_id . "\n";
    echo "MAC: " . $cust->mac_address . "\n";
    echo "OLT ID: " . $cust->olt_id . "\n";
    echo "ONU MAC SN: " . $cust->onu_mac_sn . "\n";
    echo "Fiber Route: " . $cust->fiber_route_info . "\n";
} else {
    echo "Customer not found!\n";
}

echo "\n--- ONUs on VSOL (olt_id=1) on PON 0/1 or 1 ---\n";
$onus = \App\Models\TenantOnu::where('olt_id', 1)
    ->where(function($q) {
        $q->where('pon_port', 'like', '%1%');
    })
    ->take(10)
    ->get();

foreach ($onus as $o) {
    echo "ONU ID: {$o->id} | PORT: {$o->pon_port}:{$o->onu_id} | MAC: {$o->mac_address} | RMAC: {$o->router_mac} | RX: {$o->rx_power_dbm} | DIST: {$o->distance_m} | NAME: '{$o->name}' | DESC: '{$o->desc}'\n";
}

echo "\n--- SEARCHING FOR MAC a8:31:62:02:15:77 ANYWHERE IN DB ---\n";
$targetMac = 'a8:31:62:02:15:77';
$clean = 'a83162021577';
$allOnus = \App\Models\TenantOnu::all();
foreach ($allOnus as $o) {
    $m1 = strtolower(preg_replace('/[^a-f0-9]/', '', $o->mac_address ?? ''));
    $m2 = strtolower(preg_replace('/[^a-f0-9]/', '', $o->router_mac ?? ''));
    if ($m1 === $clean || $m2 === $clean) {
        echo "MATCH IN ONUS: ID={$o->id} OLT={$o->olt_id} PORT={$o->pon_port}:{$o->onu_id} MAC={$o->mac_address} RMAC={$o->router_mac}\n";
    }
}
