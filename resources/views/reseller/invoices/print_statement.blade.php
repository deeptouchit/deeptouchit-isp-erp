<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wholesale Invoices Statement - {{ $reseller->name ?? 'Partner' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @media print {
            @page {
                size: A4 portrait;
                margin: 12mm 12mm 12mm 12mm;
            }
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 text-xs antialiased font-sans p-4 sm:p-6 print:p-0 print:bg-white">

    <!-- Print Control Bar -->
    <div class="max-w-4xl mx-auto mb-4 flex items-center justify-between no-print bg-white p-3 rounded-xl border border-slate-200 shadow-sm">
        <div class="flex items-center gap-2">
            <span class="font-bold text-slate-800 text-sm">Wholesale Invoices Statement</span>
            <span class="text-xs text-slate-500 font-mono">{{ $month !== 'all' ? 'Period: ' . $month : 'All Records' }}</span>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-3.5 py-1.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-print"></i>
                <span>Print Statement</span>
            </button>
            <button onclick="window.close()" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200 transition cursor-pointer">
                Close
            </button>
        </div>
    </div>

    <!-- Printable Paper Sheet -->
    <div class="max-w-4xl mx-auto bg-white border border-slate-200 rounded-xl p-6 print:border-none print:p-0 print:shadow-none space-y-5">
        
        <!-- 1. Header -->
        <div class="flex items-start justify-between border-b border-slate-200 pb-4">
            <div>
                <h1 class="text-lg font-bold text-slate-900 tracking-tight">{{ $reseller->name ?? 'Sub-ISP Partner' }}</h1>
                <p class="text-[11px] text-slate-500">Wholesale Billing Statement • {{ $tenant->company_name ?? $tenant->name ?? 'ISP Management Network' }}</p>
                <div class="mt-1 text-[10.5px] text-slate-600 font-mono">
                    <div>Partner Code: {{ $reseller->code ?? 'RES-001' }} • Contact: {{ $reseller->mobile ?? 'N/A' }}</div>
                    <div>Address: {{ $reseller->address ?? 'N/A' }}</div>
                </div>
            </div>
            <div class="text-right">
                <div class="inline-block px-2.5 py-1 rounded-lg bg-cyan-50 text-cyan-800 font-mono font-bold text-xs border border-cyan-200">
                    TOTAL DUE: @currency($totalDue)
                </div>
                <div class="text-[11px] text-slate-500 font-mono mt-1.5">Invoices: {{ count($invoices) }} Records</div>
                <div class="text-[10px] text-slate-400 font-mono mt-0.5">Printed: {{ now()->format('F d, Y h:i A') }}</div>
            </div>
        </div>

        <!-- 2. Summary Grid -->
        <div class="grid grid-cols-3 gap-3 p-3 rounded-lg bg-slate-50 border border-slate-200 text-xs">
            <div>
                <span class="text-[10px] uppercase font-medium text-slate-500 block">Total Invoiced</span>
                <span class="font-mono font-bold text-slate-900 text-sm">@currency($totalInvoiced)</span>
            </div>
            <div>
                <span class="text-[10px] uppercase font-medium text-slate-500 block">Total Paid</span>
                <span class="font-mono font-bold text-emerald-700 text-sm">@currency($totalPaid)</span>
            </div>
            <div>
                <span class="text-[10px] uppercase font-medium text-slate-500 block">Outstanding Due</span>
                <span class="font-mono font-bold text-rose-700 text-sm">@currency($totalDue)</span>
            </div>
        </div>

        <!-- 3. Invoices Table -->
        <div class="space-y-2">
            <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Wholesale Invoice Ledger</h2>

            <table class="w-full border-collapse text-[10.5px]">
                <thead>
                    <tr class="bg-slate-100 border-b border-slate-200 text-slate-700">
                        <th class="py-1.5 px-2 text-left font-semibold w-8">#</th>
                        <th class="py-1.5 px-2 text-left font-semibold w-32">Invoice #</th>
                        <th class="py-1.5 px-2 text-center font-semibold w-24">Month</th>
                        <th class="py-1.5 px-2 text-left font-semibold">Type / Service</th>
                        <th class="py-1.5 px-2 text-right font-semibold w-24">Billed</th>
                        <th class="py-1.5 px-2 text-right font-semibold w-24">Paid</th>
                        <th class="py-1.5 px-2 text-right font-semibold w-24">Due</th>
                        <th class="py-1.5 px-2 text-center font-semibold w-20">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($invoices as $index => $inv)
                        <tr>
                            <td class="py-1.5 px-2 font-mono text-slate-500">{{ $index + 1 }}</td>
                            <td class="py-1.5 px-2 font-mono font-bold text-indigo-700">{{ $inv->invoice_no }}</td>
                            <td class="py-1.5 px-2 text-center font-mono font-semibold text-slate-700">{{ $inv->billing_month ? $inv->billing_month->format('M Y') : '—' }}</td>
                            <td class="py-1.5 px-2 font-semibold text-slate-800">{{ $inv->type_label }}</td>
                            <td class="py-1.5 px-2 text-right font-mono font-bold text-slate-900">@currency($inv->amount)</td>
                            <td class="py-1.5 px-2 text-right font-mono font-semibold text-emerald-700">@currency($inv->paid_amount)</td>
                            <td class="py-1.5 px-2 text-right font-mono font-bold {{ $inv->due_amount > 0 ? 'text-rose-700' : 'text-slate-400' }}">@currency($inv->due_amount)</td>
                            <td class="py-1.5 px-2 text-center font-bold font-mono text-[9.5px]">
                                @if($inv->payment_status === 'PAID')
                                    <span class="text-emerald-700">PAID</span>
                                @elseif($inv->payment_status === 'PARTIAL')
                                    <span class="text-amber-700">PARTIAL</span>
                                @else
                                    <span class="text-rose-700">UNPAID</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-slate-400">No wholesale invoices found.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="bg-slate-50 border-t-2 border-slate-300 font-bold">
                        <td colspan="4" class="py-2 px-2 text-right text-slate-800">Total Amounts:</td>
                        <td class="py-2 px-2 text-right font-mono text-slate-900">@currency($totalInvoiced)</td>
                        <td class="py-2 px-2 text-right font-mono text-emerald-700">@currency($totalPaid)</td>
                        <td class="py-2 px-2 text-right font-mono text-rose-700">@currency($totalDue)</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- 4. Signatures -->
        <div class="pt-8 grid grid-cols-2 gap-8 text-[11px] text-slate-600">
            <div>
                <div class="border-t border-slate-300 pt-1.5 w-48 text-center">
                    Authorized Reseller Signature
                </div>
            </div>
            <div class="text-right flex justify-end">
                <div class="border-t border-slate-300 pt-1.5 w-48 text-center">
                    Host ISP Billing &amp; Finance
                </div>
            </div>
        </div>

    </div>

</body>
</html>
