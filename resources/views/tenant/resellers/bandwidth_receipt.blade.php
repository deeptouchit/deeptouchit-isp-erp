<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wholesale Bandwidth Money Receipt - {{ $invoice->invoice_no }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: white !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .receipt-container {
                box-shadow: none !important;
                border: none !important;
                max-width: 100% !important;
                width: 100% !important;
                padding: 10px !important;
            }
        }
        @page {
            size: auto;
            margin: 8mm;
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 antialiased min-h-screen py-6 px-4 font-sans text-xs">

    <!-- Top Floating Actions (Hidden in Print) -->
    <div class="no-print max-w-2xl mx-auto mb-4 flex items-center justify-between">
        <button onclick="window.close()" class="px-3.5 py-1.5 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs transition flex items-center gap-1.5 shadow-2xs cursor-pointer">
            <i class="fas fa-arrow-left text-[10px]"></i>
            <span>Close Window</span>
        </button>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-4 py-1.5 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs transition flex items-center gap-1.5 shadow-xs cursor-pointer">
                <i class="fas fa-print text-[11px]"></i>
                <span>Print Money Receipt</span>
            </button>
        </div>
    </div>

    <!-- Printable Receipt Container -->
    <div class="receipt-container max-w-2xl mx-auto bg-white rounded-xl border border-slate-200 shadow-lg p-6 space-y-4">
        
        <!-- Receipt Header -->
        <div class="border-b border-slate-200 pb-3 flex items-start justify-between gap-4">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-cyan-600 text-white flex items-center justify-center font-bold text-sm shadow-2xs">
                        <i class="fas fa-network-wired"></i>
                    </div>
                    <div>
                        <h1 class="text-sm font-bold text-slate-900 tracking-tight leading-none uppercase">
                            {{ $tenant->company_name ?? $tenant->name }}
                        </h1>
                        <p class="text-[10px] text-slate-500 font-medium">Wholesale Bandwidth &amp; Network Carrier</p>
                    </div>
                </div>
                <div class="text-[10px] text-slate-600 space-y-0.5 pt-1">
                    @if($tenant->address)
                        <p><i class="fas fa-map-marker-alt text-slate-400 w-3 text-center"></i> {{ $tenant->address }}</p>
                    @endif
                    @if($tenant->phone || $tenant->billing_phone)
                        <p><i class="fas fa-phone text-slate-400 w-3 text-center"></i> {{ $tenant->billing_phone ?? $tenant->phone }}</p>
                    @endif
                    @if($tenant->email)
                        <p><i class="fas fa-envelope text-slate-400 w-3 text-center"></i> {{ $tenant->email }}</p>
                    @endif
                </div>
            </div>

            <!-- Receipt Badge & Number -->
            <div class="text-right space-y-1">
                <span class="inline-block px-2.5 py-0.5 rounded bg-cyan-50 text-cyan-700 border border-cyan-200 font-bold uppercase tracking-wider text-[10px]">
                    WHOLESALE BANDWIDTH RECEIPT
                </span>
                <div class="text-right font-mono">
                    <span class="text-[10px] text-slate-400 block">Invoice / Receipt No:</span>
                    <span class="text-xs font-bold text-slate-900 block">{{ $invoice->invoice_no }}</span>
                </div>
                <div class="text-[10px] text-slate-500 font-mono">
                    Billing Month: <strong>{{ $invoice->billing_month ? $invoice->billing_month->format('F Y') : date('F Y') }}</strong>
                </div>
                <div class="text-[10px] text-slate-500 font-mono">
                    Date: {{ $invoice->paid_at ? $invoice->paid_at->format('d M Y, h:i A') : $invoice->created_at->format('d M Y, h:i A') }}
                </div>
            </div>
        </div>

        @php
            $reseller = $invoice->reseller;
            $bw = $reseller?->bandwidthAllocation;
            $details = $invoice->item_details ?? [];
        @endphp

        <!-- Partner & Routing Grid -->
        <div class="grid grid-cols-2 gap-3 p-3 bg-slate-50 rounded-lg border border-slate-200/80">
            <div class="space-y-1">
                <span class="text-[9.5px] uppercase font-bold text-slate-400 tracking-wider block">Sub-ISP / Reseller Partner</span>
                <div class="font-bold text-slate-800 text-xs">{{ $reseller?->name }}</div>
                <div class="font-mono text-[10.5px] text-slate-600">Code: <strong>{{ $reseller?->code }}</strong> @if($reseller?->prefix) &bull; Prefix: <strong>{{ $reseller?->prefix }}</strong> @endif</div>
                <div class="text-[10.5px] text-slate-600"><i class="fas fa-phone text-slate-400 text-[9px]"></i> {{ $reseller?->mobile ?: 'N/A' }}</div>
                @if($reseller?->address)
                    <div class="text-[10px] text-slate-500 truncate"><i class="fas fa-location-dot text-slate-400 text-[9px]"></i> {{ $reseller?->address }}</div>
                @endif
            </div>

            <div class="space-y-1 text-right">
                <span class="text-[9.5px] uppercase font-bold text-slate-400 tracking-wider block">Port &amp; Routing Delivery</span>
                <div class="font-bold text-cyan-800 text-xs">{{ $details['router_name'] ?? ($bw?->router?->name ?? 'Core Gateway') }}</div>
                <div class="font-mono text-[10.5px] text-slate-600">VLAN Tag: <strong>{{ $details['vlan_id'] ?? ($bw?->vlan_id ?? 'Untagged') }}</strong></div>
                <div class="font-mono text-[10.5px] text-slate-600">Total Port Capacity: <strong class="text-cyan-800">{{ $bw?->formatted_total_bandwidth ?? (($details['total_bandwidth_mbps'] ?? 0) . ' Mbps') }}</strong></div>
                <div class="text-[10px] text-slate-500">Rate Plan: <strong>{{ $details['plan_name'] ?? ($bw?->bandwidthPlan?->name ?? 'Standard Tariff') }}</strong></div>
            </div>
        </div>

        <!-- 6-Category Wholesale Traffic Breakdown Table -->
        <table class="w-full border-collapse border border-slate-200 rounded-lg overflow-hidden text-[11px]">
            <thead>
                <tr class="bg-slate-100 text-slate-700 font-bold border-b border-slate-200">
                    <th class="p-2 text-left">Traffic Stream / Allocation</th>
                    <th class="p-2 text-right">Capacity (Mbps)</th>
                    <th class="p-2 text-right">Tariff Rate ({{ $currencySymbol ?? '৳' }}/M)</th>
                    <th class="p-2 text-right">Line Total</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $streams = [
                        'global' => ['label' => 'Global Internet (Raw CIR)', 'data' => $details['global'] ?? null],
                        'cdn' => ['label' => 'CDN Cache Stream', 'data' => $details['cdn'] ?? null],
                        'bdix' => ['label' => 'BDIX National Peering', 'data' => $details['bdix'] ?? null],
                        'ggc' => ['label' => 'Google Global Cache (GGC)', 'data' => $details['ggc'] ?? null],
                        'fna' => ['label' => 'Facebook Node Appliance (FNA)', 'data' => $details['fna'] ?? null],
                        'other' => ['label' => 'Other Upstream Cache', 'data' => $details['other'] ?? null],
                    ];
                @endphp

                @foreach($streams as $key => $st)
                    @php
                        $mbps = (float)($st['data']['mbps'] ?? 0);
                        $rate = (float)($st['data']['rate'] ?? 0);
                        $amt = (float)($st['data']['amount'] ?? ($mbps * $rate));
                    @endphp
                    @if($mbps > 0 || $key === 'global')
                        <tr class="border-b border-slate-100">
                            <td class="p-2 font-medium text-slate-800">
                                {{ $st['label'] }}
                            </td>
                            <td class="p-2 text-right font-mono text-slate-700">
                                {{ number_format($mbps, 0) }} Mbps
                            </td>
                            <td class="p-2 text-right font-mono text-slate-700">
                                {{ $currencySymbol ?? '৳' }} {{ number_format($rate, 2) }}
                            </td>
                            <td class="p-2 text-right font-mono font-bold text-slate-800">
                                @currency($amt)
                            </td>
                        </tr>
                    @endif
                @endforeach

                <!-- Subtotal Row -->
                <tr class="border-t border-slate-200 bg-slate-50/50">
                    <td colspan="3" class="p-2 text-right font-medium text-slate-700">Wholesale Subtotal:</td>
                    <td class="p-2 text-right font-mono font-bold text-slate-900">
                        @currency($invoice->subtotal ?: $invoice->amount)
                    </td>
                </tr>

                @if((float)$invoice->discount > 0)
                    <tr class="border-b border-slate-100 bg-emerald-50/40">
                        <td colspan="3" class="p-2 text-right font-medium text-emerald-800">Carrier Discount / Rebate:</td>
                        <td class="p-2 text-right font-mono font-bold text-emerald-700">-@currency($invoice->discount)</td>
                    </tr>
                @endif

                <!-- Total Amount Paid Row -->
                <tr class="bg-slate-50 font-bold text-xs border-t-2 border-slate-300">
                    <td colspan="3" class="p-2 text-right uppercase tracking-wider text-slate-800">Total Net Amount Paid:</td>
                    <td class="p-2 text-right font-mono font-bold text-emerald-700 text-sm">
                        @currency($invoice->paid_amount)
                    </td>
                </tr>

                @if((float)$invoice->due_amount > 0)
                    <tr class="bg-rose-50/50 text-xs border-t border-rose-200">
                        <td colspan="3" class="p-2 text-right font-bold uppercase tracking-wider text-rose-800">Remaining Balance Due:</td>
                        <td class="p-2 text-right font-mono font-bold text-rose-700 text-xs">
                            @currency($invoice->due_amount)
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>

        <!-- Payment Metadata & Status Summary -->
        <div class="grid grid-cols-2 gap-3 p-2.5 bg-slate-50 rounded-lg border border-slate-200 text-[10.5px]">
            <div class="space-y-1">
                <div>Payment Method: <strong>{{ $invoice->payment_method ?: 'Cash / Bank' }}</strong></div>
                <div>Payment Status: 
                    @if($invoice->payment_status === 'PAID')
                        <span class="inline-flex items-center gap-1 font-bold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200">
                            <i class="fas fa-check-circle text-[9px]"></i> PAID
                        </span>
                    @elseif($invoice->payment_status === 'PARTIAL')
                        <span class="inline-flex items-center gap-1 font-bold text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200">
                            <i class="fas fa-circle-half-stroke text-[9px]"></i> PARTIAL
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 font-bold text-rose-700 bg-rose-50 px-1.5 py-0.5 rounded border border-rose-200">
                            <i class="fas fa-clock text-[9px]"></i> UNPAID
                        </span>
                    @endif
                </div>
                @if($invoice->notes)
                    <div class="text-slate-500 italic">Notes: {{ $invoice->notes }}</div>
                @endif
            </div>
            <div class="text-right space-y-1">
                <div>Processed By: <strong>{{ $invoice->creator?->name ?: 'Accounts Department' }}</strong></div>
                <div class="text-slate-500 italic">Thank you for partnering with {{ $tenant->company_name ?? $tenant->name }}.</div>
            </div>
        </div>

        <!-- Authorization Signatures -->
        <div class="pt-10 grid grid-cols-2 gap-12 text-center text-[10.5px] text-slate-600">
            <div>
                <div class="border-t border-slate-300 pt-1.5 font-medium">Sub-ISP / Reseller Signature</div>
            </div>
            <div>
                <div class="border-t border-slate-300 pt-1.5 font-semibold text-slate-800">Authorized ISP Accounts &amp; NOC Seal</div>
            </div>
        </div>

    </div>

</body>
</html>
