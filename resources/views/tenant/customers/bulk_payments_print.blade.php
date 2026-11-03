<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Bill &amp; Due Statement - {{ $tenant->company_name ?? $tenant->name ?? 'ISP Management' }}</title>
    
    <!-- Typography: Baloo Da 2 (Project Standard Font) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Baloo+Da+2:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- FontAwesome 6 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Baloo Da 2"', 'sans-serif'],
                        bangla: ['"Baloo Da 2"', 'sans-serif'],
                    }
                }
            }
        };
    </script>

    <style>
        * {
            font-family: 'Baloo Da 2', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        .font-mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace !important;
        }

        @media print {
            @page {
                size: A4 landscape;
                margin: 6mm 8mm;
            }
            body {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                background-color: #ffffff !important;
                font-family: 'Baloo Da 2', sans-serif !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .no-print {
                display: none !important;
            }
            .print-sheet {
                box-shadow: none !important;
                border: none !important;
                max-width: 100% !important;
                width: 100% !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            table {
                page-break-inside: auto;
            }
            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
            thead {
                display: table-header-group;
            }
            tfoot {
                display: table-footer-group;
            }
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 text-xs antialiased font-sans p-4 sm:p-6 print:p-0 print:bg-white">

    <!-- Top Action Bar (Screen Only) -->
    <div class="max-w-[1100px] mx-auto mb-4 flex items-center justify-between no-print bg-white p-3 rounded-xl border border-slate-200 shadow-sm">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-cyan-50 text-cyan-700 border border-cyan-100 flex items-center justify-center font-bold text-sm">
                <i class="fas fa-print"></i>
            </div>
            <div>
                <span class="font-bold text-slate-800 text-sm block leading-tight">Bulk Bill &amp; Due Demand Sheet</span>
                <span class="text-[11px] text-slate-500 font-mono">
                    Scope: {{ $mode === 'reseller' ? ($reseller ? $reseller->name . ' (' . ($reseller->code ?: 'RES-' . $reseller->id) . ')' : 'Reseller Scope') : 'ISP Direct Retail' }} • Printing: {{ count($subscribers) }} Filtered Table Subscribers
                </span>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-4 py-2 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-print text-[11px]"></i>
                <span>Print Document</span>
            </button>
            <button onclick="window.close()" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200 transition cursor-pointer">
                Close
            </button>
        </div>
    </div>

    <!-- Printable Sheet Container -->
    <div class="print-sheet max-w-[1100px] mx-auto bg-white border border-slate-200 rounded-xl p-6 print:border-none print:p-0 print:shadow-none space-y-3.5">
        
        <!-- Header -->
        <div class="flex items-start justify-between border-b border-slate-200 pb-3">
            <div>
                <h1 class="text-xl font-bold text-slate-900 tracking-tight leading-none mb-1">{{ $tenant->company_name ?? $tenant->name ?? 'ISP Management System' }}</h1>
                <p class="text-xs text-slate-600 font-medium">Bulk Bill Collection &amp; Subscription Due Clearance Sheet</p>
                <div class="mt-1 text-[11px] text-slate-500 font-mono flex items-center gap-2.5">
                    <span>Generated: {{ now()->format('d M Y, h:i A') }}</span>
                    <span>•</span>
                    <span>Scope: <strong>{{ $mode === 'reseller' ? ($reseller ? $reseller->name . ' (' . ($reseller->code ?: 'RES-' . $reseller->id) . ')' : 'Reseller Scope') : 'ISP Direct Retail' }}</strong></span>
                </div>
            </div>
            <div class="text-right space-y-1">
                <div class="inline-block px-3 py-1 rounded-lg bg-cyan-50 text-cyan-900 font-mono font-bold text-xs border border-cyan-200">
                    TOTAL: {{ count($subscribers) }} SUBSCRIBERS
                </div>
                @if($mode === 'reseller' && $reseller)
                    <div class="text-[11px] text-slate-600 font-mono">
                        Reseller Margin: <strong>{{ (float)$commissionRate }}%</strong>
                    </div>
                @endif
            </div>
        </div>

        <!-- 4-KPI Metric Strip -->
        <div class="grid grid-cols-4 gap-2">
            <!-- 1. Total Subscribers -->
            <div class="p-2 rounded-lg bg-slate-50 border border-slate-200 text-center">
                <span class="text-[9.5px] font-semibold text-slate-500 uppercase block">Total Listed</span>
                <span class="text-sm font-bold font-mono text-slate-800">{{ number_format(count($subscribers)) }}</span>
            </div>
            <!-- 2. Gross Total Bill -->
            <div class="p-2 rounded-lg bg-amber-50 border border-amber-200 text-center">
                <span class="text-[9.5px] font-semibold text-amber-800 uppercase block">Gross Total Bill</span>
                <span class="text-sm font-bold font-mono text-amber-900">@currency($totalGrossBill)</span>
            </div>
            <!-- 3. Reseller Profit -->
            <div class="p-2 rounded-lg bg-indigo-50 border border-indigo-200 text-center">
                <span class="text-[9.5px] font-semibold text-indigo-800 uppercase block">Reseller Profit ({{ (float)$commissionRate }}%)</span>
                <span class="text-sm font-bold font-mono text-indigo-900">@currency($totalResellerCommission)</span>
            </div>
            <!-- 4. Admin Share Net -->
            <div class="p-2 rounded-lg bg-emerald-50 border border-emerald-200 text-center">
                <span class="text-[9.5px] font-semibold text-emerald-800 uppercase block">Admin Share (Net Payable)</span>
                <span class="text-sm font-bold font-mono text-emerald-900">@currency($totalAdminShare)</span>
            </div>
        </div>

        <!-- Master Matrix Table -->
        <table class="w-full border-collapse border border-slate-300 text-left text-[11px]">
            <thead>
                <tr class="bg-slate-100 text-slate-700 border-b border-slate-300 font-bold">
                    <th class="p-1.5 border border-slate-300 w-8 text-center">#</th>
                    <th class="p-1.5 border border-slate-300 w-20">Customer ID</th>
                    <th class="p-1.5 border border-slate-300">Subscriber Name</th>
                    <th class="p-1.5 border border-slate-300 w-28">Mobile</th>
                    <th class="p-1.5 border border-slate-300 w-28">Zone / Area</th>
                    <th class="p-1.5 border border-slate-300 w-28">Package</th>
                    <th class="p-1.5 border border-slate-300 w-24 text-center">Expiry</th>
                    <th class="p-1.5 border border-slate-300 w-24 text-right">Bill ({{ $currencySymbol }})</th>
                    <th class="p-1.5 border border-slate-300 w-24 text-right">Due ({{ $currencySymbol }})</th>
                    <th class="p-1.5 border border-slate-300 w-24 text-right font-bold bg-slate-200/60">Payable ({{ $currencySymbol }})</th>
                    <th class="p-1.5 border border-slate-300 w-28 text-center">Signature / Note</th>
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
                        $packageName = $c->mikrotik_profile_name ?: ($c->package ? ($c->package->mikrotik_profile ?: ($c->package->name ?: $c->package->package_name)) : ($c->package_name ?: 'default'));
                    @endphp
                    <tr class="hover:bg-slate-50">
                        <td class="p-1.5 border border-slate-300 text-center text-slate-500">{{ $idx + 1 }}</td>
                        <td class="p-1.5 border border-slate-300 font-bold text-cyan-800">{{ $c->customer_id }}</td>
                        <td class="p-1.5 border border-slate-300 font-sans font-semibold text-slate-800">{{ $c->name }}</td>
                        <td class="p-1.5 border border-slate-300 text-slate-700">{{ $c->phone ?: '--' }}</td>
                        <td class="p-1.5 border border-slate-300 font-sans text-slate-600">{{ $c->zone ?: '--' }}</td>
                        <td class="p-1.5 border border-slate-300 font-sans font-medium text-slate-700">{{ $packageName }}</td>
                        <td class="p-1.5 border border-slate-300 text-center text-slate-600">{{ $c->expiry_date ? \Carbon\Carbon::parse($c->expiry_date)->format('d M Y') : '--' }}</td>
                        <td class="p-1.5 border border-slate-300 text-right text-slate-700">{{ number_format($monthly, 2) }}</td>
                        <td class="p-1.5 border border-slate-300 text-right font-bold {{ $due > 0 ? 'text-rose-600' : 'text-slate-400' }}">{{ number_format($due, 2) }}</td>
                        <td class="p-1.5 border border-slate-300 text-right font-bold text-slate-900 bg-emerald-50/50">{{ number_format($payable, 2) }}</td>
                        <td class="p-1.5 border border-slate-300 text-center text-slate-300 font-sans">__________</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="p-6 text-center text-slate-400 font-sans text-xs italic">
                            No subscriber records found in this table batch.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="bg-slate-100 font-bold font-mono border-t-2 border-slate-400">
                    <td colspan="7" class="p-2 text-right border border-slate-300 font-sans text-xs">Total Batch Sum ({{ count($subscribers) }} Subscribers):</td>
                    <td class="p-2 text-right border border-slate-300 text-slate-800">{{ number_format($sumMonthly, 2) }}</td>
                    <td class="p-2 text-right border border-slate-300 text-rose-600">{{ number_format($sumDue, 2) }}</td>
                    <td class="p-2 text-right border border-slate-300 text-emerald-900 bg-emerald-100/60">{{ number_format($sumPayable, 2) }}</td>
                    <td class="p-2 border border-slate-300"></td>
                </tr>
            </tfoot>
        </table>

        <!-- Signatures Strip -->
        <div class="grid grid-cols-3 gap-6 pt-10 border-t border-slate-200 font-sans">
            <div class="text-center">
                <div class="border-t border-slate-300 w-44 mx-auto mb-1"></div>
                <p class="text-[11px] font-semibold text-slate-700">Billing Officer / Prepared By</p>
                <p class="text-[10px] text-slate-400">{{ auth()->user()->name ?? 'System Operator' }}</p>
            </div>
            <div class="text-center">
                <div class="border-t border-slate-300 w-44 mx-auto mb-1"></div>
                <p class="text-[11px] font-semibold text-slate-700">{{ $mode === 'reseller' ? 'Reseller / Partner Sign' : 'Accounts Verified By' }}</p>
                <p class="text-[10px] text-slate-400">{{ $reseller ? $reseller->name : 'Finance Dept' }}</p>
            </div>
            <div class="text-center">
                <div class="border-t border-slate-300 w-44 mx-auto mb-1"></div>
                <p class="text-[11px] font-semibold text-slate-700">Authorized Seal &amp; Signature</p>
                <p class="text-[10px] text-slate-400">{{ $tenant->company_name ?? $tenant->name ?? 'ISP Management' }}</p>
            </div>
        </div>

    </div>

</body>
</html>
