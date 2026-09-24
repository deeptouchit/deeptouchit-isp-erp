<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reseller Bulk Payments Statement - {{ $reseller->name ?? 'Reseller Portal' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Baloo+Da+2:wght@400..800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body, table, button, input {
            font-family: "Baloo Da 2", sans-serif;
        }
        @media print {
            @page {
                size: A4 landscape;
                margin: 8mm;
            }
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                background-color: #ffffff !important;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 text-xs antialiased p-4 sm:p-6 print:p-0 print:bg-white">

    <!-- Print Control Bar -->
    <div class="max-w-6xl mx-auto mb-4 flex items-center justify-between no-print bg-white p-3 rounded-xl border border-slate-200 shadow-sm">
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-700 flex items-center justify-center font-bold text-sm">
                <i class="fas fa-layer-group"></i>
            </div>
            <div>
                <span class="font-bold text-slate-800 text-sm block leading-tight">Subscriber Bulk Payments &amp; Due Statement</span>
                <span class="text-[11px] text-slate-500 font-mono">{{ $reseller->name ?? 'Sub-ISP Partner' }} ({{ $reseller->code ?? 'RES' }}) • Total: {{ count($subscribers) }} Records</span>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-4 py-2 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-print"></i>
                <span>Print Document</span>
            </button>
            <button onclick="window.close()" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200 transition cursor-pointer">
                Close
            </button>
        </div>
    </div>

    <!-- Printable Sheet -->
    <div class="max-w-6xl mx-auto bg-white border border-slate-200 rounded-xl p-6 print:border-none print:p-0 print:shadow-none space-y-4">
        
        <!-- Header -->
        <div class="flex items-start justify-between border-b border-slate-200 pb-3">
            <div>
                <h1 class="text-xl font-bold text-slate-900 tracking-tight">{{ $reseller->name ?? 'Sub-ISP Partner' }}</h1>
                <p class="text-xs text-slate-500 font-medium">Customer Subscribers Bulk Bill Clearance Sheet • {{ $tenant->company_name ?? $tenant->name ?? 'ISP Management' }}</p>
                <div class="mt-1 text-[11px] text-slate-600 font-mono flex items-center gap-3">
                    <span>Partner Code: <strong>{{ $reseller->code ?? 'RES-001' }}</strong></span>
                    <span>•</span>
                    <span>Generated: {{ now()->format('d M Y, h:i A') }}</span>
                </div>
            </div>
            <div class="text-right space-y-1">
                <div class="inline-block px-3 py-1 rounded-lg bg-purple-50 text-purple-900 font-mono font-bold text-xs border border-purple-200">
                    TOTAL: {{ count($subscribers) }} SUBSCRIBERS
                </div>
                <div class="text-[11px] text-slate-600 font-mono">
                    Commission Rate: <strong>{{ (float)$commissionRate }}%</strong>
                </div>
            </div>
        </div>

        <!-- Summary KPI Cards Strip -->
        <div class="grid grid-cols-4 gap-2">
            <!-- 1. Total Subscribers -->
            <div class="p-2 rounded-lg bg-slate-50 border border-slate-200">
                <span class="text-[9px] font-semibold text-slate-500 uppercase block">Subscribers</span>
                <span class="text-sm font-bold font-mono text-slate-800">{{ number_format(count($subscribers)) }}</span>
            </div>
            <!-- 2. Gross Total Bill -->
            <div class="p-2 rounded-lg bg-amber-50 border border-amber-200">
                <span class="text-[9px] font-semibold text-amber-800 uppercase block">Gross Total Bill</span>
                <span class="text-sm font-bold font-mono text-amber-900">@currency($totalGrossBill)</span>
            </div>
            <!-- 3. Reseller Profit -->
            <div class="p-2 rounded-lg bg-indigo-50 border border-indigo-200">
                <span class="text-[9px] font-semibold text-indigo-800 uppercase block">Your Profit ({{ (float)$commissionRate }}%)</span>
                <span class="text-sm font-bold font-mono text-indigo-900">@currency($totalResellerCommission)</span>
            </div>
            <!-- 4. Admin Share Net -->
            <div class="p-2 rounded-lg bg-emerald-50 border border-emerald-200">
                <span class="text-[9px] font-semibold text-emerald-800 uppercase block">Admin Share Net</span>
                <span class="text-sm font-bold font-mono text-emerald-900">@currency($totalAdminShare)</span>
            </div>
        </div>

        <!-- Matrix Table -->
        <table class="w-full border-collapse border border-slate-200 text-left text-[10.5px]">
            <thead>
                <tr class="bg-slate-100 text-slate-700 border-b border-slate-200 font-semibold">
                    <th class="p-1.5 border-r border-slate-200 w-8 text-center">#</th>
                    <th class="p-1.5 border-r border-slate-200 w-20">Customer ID</th>
                    <th class="p-1.5 border-r border-slate-200">Subscriber Name</th>
                    <th class="p-1.5 border-r border-slate-200 w-24">PPPoE User</th>
                    <th class="p-1.5 border-r border-slate-200 w-24">Mobile</th>
                    <th class="p-1.5 border-r border-slate-200 w-24">Zone / Area</th>
                    <th class="p-1.5 border-r border-slate-200 w-24">Package</th>
                    <th class="p-1.5 border-r border-slate-200 w-16 text-center">Status</th>
                    <th class="p-1.5 border-r border-slate-200 w-20 text-center">Expiry</th>
                    <th class="p-1.5 border-r border-slate-200 w-20 text-right">Monthly ({{ $currencySymbol }})</th>
                    <th class="p-1.5 border-r border-slate-200 w-20 text-right">Due ({{ $currencySymbol }})</th>
                    <th class="p-1.5 border-r border-slate-200 w-20 text-right font-bold bg-slate-200/60">Payable ({{ $currencySymbol }})</th>
                    <th class="p-1.5 w-24 text-center">Sign / Note</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 font-mono">
                @php
                    $sumMonthly = 0;
                    $sumDue = 0;
                    $sumPayable = 0;
                @endphp
                @forelse($subscribers as $idx => $c)
                    @php
                        $monthly = (float) $c->monthly_bill;
                        $due = (float) $c->due_amount;
                        $payable = (float) ($due > 0 ? $due : ($monthly ?: 0));
                        $sumMonthly += $monthly;
                        $sumDue += $due;
                        $sumPayable += $payable;
                    @endphp
                    <tr class="hover:bg-slate-50">
                        <td class="p-1.5 border-r border-slate-200 text-center text-slate-500">{{ $idx + 1 }}</td>
                        <td class="p-1.5 border-r border-slate-200 font-bold text-cyan-800">{{ $c->customer_id }}</td>
                        <td class="p-1.5 border-r border-slate-200 font-sans font-semibold text-slate-800">{{ $c->name }}</td>
                        <td class="p-1.5 border-r border-slate-200 text-slate-700">{{ $c->username }}</td>
                        <td class="p-1.5 border-r border-slate-200 text-slate-700">{{ $c->phone ?: '--' }}</td>
                        <td class="p-1.5 border-r border-slate-200 font-sans text-slate-600">{{ $c->zone ?: '--' }}</td>
                        <td class="p-1.5 border-r border-slate-200 font-sans text-slate-700">{{ $c->package_display_name }}</td>
                        <td class="p-1.5 border-r border-slate-200 text-center">
                            <span class="inline-block px-1 py-0.2 rounded text-[9px] uppercase font-bold {{ $c->status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                {{ $c->status }}
                            </span>
                        </td>
                        <td class="p-1.5 border-r border-slate-200 text-center text-slate-600">{{ $c->expiry_date ? \Carbon\Carbon::parse($c->expiry_date)->format('d M Y') : '--' }}</td>
                        <td class="p-1.5 border-r border-slate-200 text-right text-slate-700">{{ number_format($monthly, 2) }}</td>
                        <td class="p-1.5 border-r border-slate-200 text-right font-bold {{ $due > 0 ? 'text-rose-600' : 'text-slate-400' }}">{{ number_format($due, 2) }}</td>
                        <td class="p-1.5 border-r border-slate-200 text-right font-bold text-slate-900 bg-emerald-50/40">{{ number_format($payable, 2) }}</td>
                        <td class="p-1.5 text-center text-slate-300">__________</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="13" class="p-4 text-center text-slate-400 font-sans">No subscriber records found matching criteria.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="bg-slate-100 font-bold font-mono border-t-2 border-slate-300">
                    <td colspan="9" class="p-2 text-right border-r border-slate-200 font-sans">Total Batch Sum:</td>
                    <td class="p-2 text-right border-r border-slate-200 text-slate-800">{{ number_format($sumMonthly, 2) }}</td>
                    <td class="p-2 text-right border-r border-slate-200 text-rose-600">{{ number_format($sumDue, 2) }}</td>
                    <td class="p-2 text-right border-r border-slate-200 text-emerald-900 bg-emerald-100/50">{{ number_format($sumPayable, 2) }}</td>
                    <td class="p-2"></td>
                </tr>
            </tfoot>
        </table>

        <!-- Footer Signatures -->
        <div class="grid grid-cols-2 gap-8 pt-10 border-t border-slate-200">
            <div class="text-center">
                <div class="border-t border-slate-300 w-48 mx-auto mb-1"></div>
                <p class="text-[10.5px] font-semibold text-slate-700">Prepared By</p>
                <p class="text-[9.5px] text-slate-400">{{ auth()->user()->name ?? 'Partner Staff' }} ({{ $reseller->name ?? 'Reseller' }})</p>
            </div>
            <div class="text-center">
                <div class="border-t border-slate-300 w-48 mx-auto mb-1"></div>
                <p class="text-[10.5px] font-semibold text-slate-700">Authorized Signature &amp; Seal</p>
                <p class="text-[9.5px] text-slate-400">{{ $tenant->company_name ?? 'ISP Management' }}</p>
            </div>
        </div>

    </div>

</body>
</html>
