<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

header('Content-Type: text/plain');

$payments = \App\Models\TenantCustomerPayment::where('payment_method', 'cash')->get();
echo "=== CASH PAYMENTS SUMMARY ===\n";
echo "Total Cash Payments Count: " . $payments->count() . "\n";
echo "Total Cash Amount: " . $payments->sum('amount') . "\n\n";

echo "=== BREAKDOWN BY COLLECTOR (collected_by) ===\n";
foreach ($payments->groupBy('collected_by') as $uid => $items) {
    $u = \App\Models\User::find($uid);
    $name = $u ? "{$u->name} (ID: {$u->id}, Role: {$u->role})" : "Unassigned / NULL (ID: 0)";
    echo "Collected by: {$name}\n";
    echo "  - Total Amount: " . $items->sum('amount') . " BDT\n";
    echo "  - Total Receipts: " . $items->count() . "\n";
    echo "  - Unhanded (handover_id is null): " . $items->whereNull('handover_id')->sum('amount') . " BDT (" . $items->whereNull('handover_id')->count() . " receipts)\n\n";
}

echo "=== LATEST 5 CASH PAYMENTS ===\n";
$latest = \App\Models\TenantCustomerPayment::where('payment_method', 'cash')->latest('id')->take(5)->get();
foreach ($latest as $p) {
    $collector = \App\Models\User::find($p->collected_by);
    $customer = $p->customer;
    echo "ID: {$p->id} | Invoice: {$p->invoice_no} | Amount: {$p->amount} | Customer: " . ($customer?->name ?? 'N/A') . " ({$customer?->customer_id}) | Collected By: " . ($collector?->name ?? 'None') . " (ID: {$p->collected_by}) | Date: {$p->paid_at}\n";
}
