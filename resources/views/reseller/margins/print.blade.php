<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commercial Margin &amp; Profit Schedule - {{ $reseller->name ?? 'Partner' }}</title>
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
            <span class="font-bold text-slate-800 text-sm">Commercial Margin &amp; Profit Schedule</span>
            <span class="text-xs text-slate-500 font-mono">Commission: {{ $commissionRate }}%</span>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-3.5 py-1.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-print"></i>
                <span>Print Schedule</span>
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
                <p class="text-[11px] text-slate-500">Commercial Franchise Profit &amp; Margin Agreement • {{ $tenant->company_name ?? $tenant->name ?? 'ISP Management Network' }}</p>
                <div class="mt-1 text-[10.5px] text-slate-600 font-mono">
                    <div>Partner Code: {{ $reseller->code ?? 'RES-001' }} • Contact: {{ $reseller->mobile ?? 'N/A' }}</div>
                    <div>Franchise Commission Rate: <strong class="text-cyan-800">{{ $commissionRate }}% Profit Share</strong></div>
                </div>
            </div>
            <div class="text-right">
                <div class="inline-block px-2.5 py-1 rounded-lg bg-cyan-50 text-cyan-800 font-mono font-bold text-xs border border-cyan-200">
                    RATE: {{ $commissionRate }}% MARGIN
                </div>
                <div class="text-[10px] text-slate-400 font-mono mt-1">Effective: {{ now()->format('F d, Y') }}</div>
            </div>
        </div>

        <!-- 2. Margins Table -->
        <div class="space-y-2">
            <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Approved Profit Margin Schedule</h2>

            <table class="w-full border-collapse text-[10.5px]">
                <thead>
                    <tr class="bg-slate-100 border-b border-slate-200 text-slate-700">
                        <th class="py-1.5 px-2 text-left font-semibold w-8">#</th>
                        <th class="py-1.5 px-2 text-left font-semibold">Package</th>
                        <th class="py-1.5 px-2 text-right font-semibold w-24">Retail Price</th>
                        <th class="py-1.5 px-2 text-right font-semibold w-24">Wholesale Cost</th>
                        <th class="py-1.5 px-2 text-right font-semibold w-24">Margin ({{ $commissionRate }}%)</th>
                        <th class="py-1.5 px-2 text-center font-semibold w-24">Active Users</th>
                        <th class="py-1.5 px-2 text-right font-semibold w-28">Monthly Profit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($packages as $index => $pkg)
                        @php
                            $retail = (float) $pkg->price;
                            $margin = ($retail * $commissionRate) / 100;
                            $cost = max(0, $retail - $margin);
                            $activeSubs = $subscriberCounts->get($pkg->id)?->active_subscribers ?? 0;
                            $pkgProfit = $activeSubs * $margin;
                        @endphp
                        <tr>
                            <td class="py-2 px-2 font-mono text-slate-500">{{ $index + 1 }}</td>
                            <td class="py-2 px-2 font-mono font-bold text-cyan-800">{{ $pkg->mikrotik_profile ?? $pkg->name }}</td>
                            <td class="py-2 px-2 text-right font-mono font-bold text-slate-900">@currency($retail)</td>
                            <td class="py-2 px-2 text-right font-mono font-semibold text-slate-600">@currency($cost)</td>
                            <td class="py-2 px-2 text-right font-mono font-bold text-emerald-700">+@currency($margin)</td>
                            <td class="py-2 px-2 text-center font-mono font-bold {{ $activeSubs > 0 ? 'text-emerald-700' : 'text-slate-400' }}">{{ $activeSubs }}</td>
                            <td class="py-2 px-2 text-right font-mono font-bold text-emerald-800">@currency($pkgProfit)</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-slate-400">No packages found.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if(count($packages) > 0)
                    <tfoot>
                        <tr class="bg-slate-50 border-t-2 border-slate-300 font-bold">
                            <td colspan="5" class="py-2 px-2 text-right text-slate-800">Total Summary:</td>
                            <td class="py-2 px-2 text-center font-mono font-bold text-indigo-700">{{ number_format($totalSubscribers) }}</td>
                            <td class="py-2 px-2 text-right font-mono font-bold text-emerald-800">@currency($totalProfit)</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        <!-- 3. Policy Terms -->
        <div class="p-3 rounded-lg bg-slate-50 border border-slate-200 text-xs space-y-1">
            <span class="font-bold text-slate-700 block">Commercial Billing Terms:</span>
            <ul class="list-disc list-inside text-[11px] text-slate-600 space-y-0.5">
                <li>Commission profit is retained directly by the partner from collected subscriber fees.</li>
                <li>Wholesale cost is deducted from the partner wallet at the time of subscriber line activation/renewal.</li>
            </ul>
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
                    Host ISP Commercial &amp; Accounts Seal
                </div>
            </div>
        </div>

    </div>

</body>
</html>
