<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collection Statement - {{ $reseller->name ?? 'Partner' }}</title>
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
            <span class="font-bold text-slate-800 text-sm">Collection Statement &amp; Audit</span>
            <span class="text-xs text-slate-500 font-mono">Period: {{ $periodLabel }}</span>
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
                <p class="text-[11px] text-slate-500">Retail Collection Statement • {{ $tenant->company_name ?? 'ISP Management Network' }}</p>
                <div class="mt-1 text-[10.5px] text-slate-600 font-mono">
                    <div>Partner Code: {{ $reseller->code ?? 'RES-001' }} • Contact: {{ $reseller->mobile ?? 'N/A' }}</div>
                </div>
            </div>
            <div class="text-right">
                <div class="inline-block px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 font-mono font-bold text-xs border border-emerald-200">
                    TOTAL: @currency($totalAmount)
                </div>
                <div class="text-[11px] text-slate-500 font-mono mt-1.5">Transactions: {{ count($payments) }} Slips</div>
                <div class="text-[10px] text-slate-400 font-mono mt-0.5">Printed: {{ now()->format('F d, Y h:i A') }}</div>
            </div>
        </div>

        <!-- 2. Transactions Table -->
        <div class="space-y-2">
            <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Money Receipts &amp; Collections Ledger</h2>

            <table class="w-full border-collapse text-[10.5px]">
                <thead>
                    <tr class="bg-slate-100 border-b border-slate-200 text-slate-700">
                        <th class="py-1.5 px-2 text-left font-semibold w-8">#</th>
                        <th class="py-1.5 px-2 text-left font-semibold w-28">Receipt #</th>
                        <th class="py-1.5 px-2 text-left font-semibold">Subscriber Name</th>
                        <th class="py-1.5 px-2 text-left font-semibold">Package</th>
                        <th class="py-1.5 px-2 text-left font-semibold">Collector</th>
                        <th class="py-1.5 px-2 text-center font-semibold">Method</th>
                        <th class="py-1.5 px-2 text-right font-semibold">Amount</th>
                        <th class="py-1.5 px-2 text-center font-semibold w-24">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($payments as $index => $p)
                        <tr>
                            <td class="py-1.5 px-2 font-mono text-slate-500">{{ $index + 1 }}</td>
                            <td class="py-1.5 px-2 font-mono font-bold text-cyan-800">{{ $p->invoice_no }}</td>
                            <td class="py-1.5 px-2 font-semibold text-slate-800">{{ $p->customer?->name ?? 'Direct' }}</td>
                            <td class="py-1.5 px-2 font-mono text-slate-600">{{ $p->customer?->package_display_name ?? 'N/A' }}</td>
                            <td class="py-1.5 px-2 text-slate-700">{{ $p->collector?->name ?? 'Counter' }}</td>
                            <td class="py-1.5 px-2 text-center font-mono">{{ strtoupper($p->payment_method) }}</td>
                            <td class="py-1.5 px-2 text-right font-mono font-bold text-slate-900">@currency($p->amount)</td>
                            <td class="py-1.5 px-2 text-center font-mono text-slate-600">{{ $p->paid_at ? $p->paid_at->format('Y-m-d') : $p->created_at->format('Y-m-d') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-slate-400">No collection records found for this period.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="bg-slate-50 border-t border-slate-300 font-bold text-slate-800">
                        <td colspan="6" class="py-2 px-2 text-right uppercase text-[10px]">Total Collections Received:</td>
                        <td class="py-2 px-2 text-right font-mono text-emerald-700 text-xs">@currency($totalAmount)</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- 3. Signatures -->
        <div class="pt-12 grid grid-cols-2 gap-8 text-[11px] border-t border-slate-200 mt-6">
            <div class="text-left">
                <div class="w-40 border-b border-slate-300 pb-1 mb-1"></div>
                <div class="font-bold text-slate-800">Accounts &amp; Collections Officer</div>
                <div class="text-slate-500 text-[10px]">{{ $reseller->name ?? 'Reseller Partner' }}</div>
            </div>
            <div class="text-right">
                <div class="w-40 border-b border-slate-300 pb-1 mb-1 ml-auto"></div>
                <div class="font-bold text-slate-800">Authorized Partner Auditor</div>
                <div class="text-slate-500 text-[10px]">{{ $tenant->company_name ?? 'Host ISP Organization' }}</div>
            </div>
        </div>

    </div>

</body>
</html>
