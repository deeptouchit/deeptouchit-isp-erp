<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Invoices Report - {{ $reseller->name ?? 'Reseller Portal' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @media print {
            @page {
                size: A4 landscape;
                margin: 10mm;
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
    <div class="max-w-6xl mx-auto mb-4 flex items-center justify-between no-print bg-white p-3 rounded-xl border border-slate-200 shadow-sm">
        <div class="flex items-center gap-2">
            <span class="font-bold text-slate-800 text-sm">Customer Invoices Report</span>
            <span class="text-xs text-slate-500 font-mono">Total Records: {{ count($invoices) }}</span>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-3.5 py-1.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-print"></i>
                <span>Print Document</span>
            </button>
            <button onclick="window.close()" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200 transition cursor-pointer">
                Close
            </button>
        </div>
    </div>

    <!-- Printable Sheet -->
    <div class="max-w-6xl mx-auto bg-white border border-slate-200 rounded-xl p-6 print:border-none print:p-0 print:shadow-none space-y-4">
        
        <!-- Header -->
        <div class="flex items-start justify-between border-b border-slate-200 pb-3">
            <div>
                <h1 class="text-lg font-bold text-slate-900 tracking-tight">{{ $reseller->name ?? 'Sub-ISP Partner' }}</h1>
                <p class="text-[11px] text-slate-500">Customer Invoices Summary Report • {{ $tenant->company_name ?? $tenant->name ?? 'ISP Management' }}</p>
                <div class="mt-1 text-[10.5px] text-slate-600 font-mono">
                    Partner Code: {{ $reseller->code ?? 'RES-001' }} • Generated: {{ now()->format('d M Y, h:i A') }}
                </div>
            </div>
            <div class="text-right">
                <div class="inline-block px-2.5 py-1 rounded-lg bg-cyan-50 text-cyan-800 font-mono font-bold text-xs border border-cyan-200">
                    TOTAL: {{ count($invoices) }} INVOICES
                </div>
            </div>
        </div>

        <!-- Matrix Table -->
        <table class="w-full border-collapse border border-slate-200 text-left text-[11px]">
            <thead>
                <tr class="bg-slate-100 text-slate-700 border-b border-slate-200 font-semibold">
                    <th class="p-2 border-r border-slate-200 w-8 text-center">#</th>
                    <th class="p-2 border-r border-slate-200 w-28">Invoice No</th>
                    <th class="p-2 border-r border-slate-200">Customer Name</th>
                    <th class="p-2 border-r border-slate-200 w-24">Customer ID</th>
                    <th class="p-2 border-r border-slate-200 w-24">Month</th>
                    <th class="p-2 border-r border-slate-200 w-28">Package</th>
                    <th class="p-2 border-r border-slate-200 w-24 text-right">Payable ({{ $currencySymbol }})</th>
                    <th class="p-2 border-r border-slate-200 w-24 text-right">Paid ({{ $currencySymbol }})</th>
                    <th class="p-2 border-r border-slate-200 w-24 text-right">Due ({{ $currencySymbol }})</th>
                    <th class="p-2 w-20 text-center">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 font-mono">
                @php
                    $sumPayable = 0;
                    $sumPaid = 0;
                    $sumDue = 0;
                @endphp
                @forelse($invoices as $idx => $inv)
                    @php
                        $sumPayable += (float) $inv->total_payable;
                        $sumPaid += (float) $inv->paid_amount;
                        $sumDue += (float) $inv->due_amount;
                    @endphp
                    <tr class="hover:bg-slate-50">
                        <td class="p-2 border-r border-slate-200 text-center text-slate-500">{{ $idx + 1 }}</td>
                        <td class="p-2 border-r border-slate-200 font-bold text-cyan-800">{{ $inv->invoice_no }}</td>
                        <td class="p-2 border-r border-slate-200 font-sans font-semibold text-slate-800">{{ $inv->customer?->name ?? 'N/A' }}</td>
                        <td class="p-2 border-r border-slate-200 text-slate-700">{{ $inv->customer?->customer_id ?? 'N/A' }}</td>
                        <td class="p-2 border-r border-slate-200 text-slate-700">{{ $inv->billing_month ? \Carbon\Carbon::parse($inv->billing_month . '-01')->format('M Y') : 'N/A' }}</td>
                        <td class="p-2 border-r border-slate-200 font-sans text-slate-600">{{ $inv->customer?->package_display_name ?? $inv->package?->name ?? ($inv->package_name ?? 'N/A') }}</td>
                        <td class="p-2 border-r border-slate-200 text-right font-bold text-slate-900">{{ number_format($inv->total_payable, 2) }}</td>
                        <td class="p-2 border-r border-slate-200 text-right font-bold text-emerald-700">{{ number_format($inv->paid_amount, 2) }}</td>
                        <td class="p-2 border-r border-slate-200 text-right font-bold {{ $inv->due_amount > 0 ? 'text-rose-600' : 'text-slate-500' }}">{{ number_format($inv->due_amount, 2) }}</td>
                        <td class="p-2 text-center">
                            <span class="inline-block px-1.5 py-0.5 rounded text-[9.5px] uppercase font-bold {{ $inv->status === 'paid' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                {{ $inv->status }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="p-4 text-center text-slate-400 font-sans">No customer invoice records found.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="bg-slate-100 font-bold font-mono border-t border-slate-300">
                    <td colspan="6" class="p-2 text-right border-r border-slate-200 font-sans">Total Sum:</td>
                    <td class="p-2 text-right border-r border-slate-200 text-slate-900">{{ number_format($sumPayable, 2) }}</td>
                    <td class="p-2 text-right border-r border-slate-200 text-emerald-700">{{ number_format($sumPaid, 2) }}</td>
                    <td class="p-2 text-right border-r border-slate-200 text-rose-600">{{ number_format($sumDue, 2) }}</td>
                    <td class="p-2"></td>
                </tr>
            </tfoot>
        </table>

        <!-- Footer Signatures -->
        <div class="grid grid-cols-2 gap-8 pt-8 border-t border-slate-200">
            <div class="text-center">
                <div class="border-t border-slate-300 w-48 mx-auto mb-1"></div>
                <p class="text-[10.5px] font-semibold text-slate-700">Prepared By</p>
                <p class="text-[9.5px] text-slate-400">{{ $authUser->name }} (Partner Staff)</p>
            </div>
            <div class="text-center">
                <div class="border-t border-slate-300 w-48 mx-auto mb-1"></div>
                <p class="text-[10.5px] font-semibold text-slate-700">Authorized Signature</p>
                <p class="text-[9.5px] text-slate-400">{{ $tenant->company_name ?? 'ISP Management' }}</p>
            </div>
        </div>

    </div>

</body>
</html>
