<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Package Tariff &amp; Rates - {{ $reseller->name ?? 'Partner' }}</title>
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
            <span class="font-bold text-slate-800 text-sm">Package Tariff &amp; Margin Rates</span>
            <span class="text-xs text-slate-500 font-mono">Commission Rate: {{ $commissionRate }}%</span>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-3.5 py-1.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-print"></i>
                <span>Print Tariff Sheet</span>
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
                <p class="text-[11px] text-slate-500">Internet Packages &amp; Margin Schedule • {{ $tenant->company_name ?? $tenant->name ?? 'ISP Management Network' }}</p>
                <div class="mt-1 text-[10.5px] text-slate-600 font-mono">
                    <div>Partner Code: {{ $reseller->code ?? 'RES-001' }} • Contact: {{ $reseller->mobile ?? 'N/A' }}</div>
                    <div>Commission Rate: <strong class="text-cyan-800">{{ $commissionRate }}% Margin</strong></div>
                </div>
            </div>
            <div class="text-right">
                <div class="inline-block px-2.5 py-1 rounded-lg bg-cyan-50 text-cyan-800 font-mono font-bold text-xs border border-cyan-200">
                    PLANS: {{ count($packages) }} Assigned
                </div>
                <div class="text-[10px] text-slate-400 font-mono mt-1">Effective: {{ now()->format('F d, Y') }}</div>
            </div>
        </div>

        <!-- 2. Tariff Table -->
        <div class="space-y-2">
            <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Approved Retail &amp; Wholesale Price Matrix</h2>

            <table class="w-full border-collapse text-[10.5px]">
                <thead>
                    <tr class="bg-slate-100 border-b border-slate-200 text-slate-700">
                        <th class="py-1.5 px-2 text-left font-semibold w-8">#</th>
                        <th class="py-1.5 px-2 text-left font-semibold">Package</th>
                        <th class="py-1.5 px-2 text-center font-semibold w-24">Bandwidth</th>
                        <th class="py-1.5 px-2 text-center font-semibold w-16">Facebook</th>
                        <th class="py-1.5 px-2 text-center font-semibold w-16">YouTube</th>
                        <th class="py-1.5 px-2 text-center font-semibold w-16">BDIX</th>
                        <th class="py-1.5 px-2 text-right font-semibold w-24">Retail Price</th>
                        <th class="py-1.5 px-2 text-right font-semibold w-24">Wholesale Cost</th>
                        <th class="py-1.5 px-2 text-right font-semibold w-24">Profit ({{ $commissionRate }}%)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($packages as $index => $pkg)
                        @php
                            $retail = (float) $pkg->price;
                            $margin = ($retail * $commissionRate) / 100;
                            $cost = max(0, $retail - $margin);
                        @endphp
                        <tr>
                            <td class="py-2 px-2 font-mono text-slate-500">{{ $index + 1 }}</td>
                            <td class="py-2 px-2 font-mono font-bold text-cyan-800">{{ $pkg->mikrotik_profile ?? $pkg->name }}</td>
                            <td class="py-2 px-2 text-center font-mono font-bold text-slate-700">{{ $pkg->download_speed ? $pkg->download_speed . 'M' : '—' }} ↓ / {{ $pkg->upload_speed ? $pkg->upload_speed . 'M' : '—' }} ↑</td>
                            <td class="py-2 px-2 text-center font-mono font-bold text-indigo-700">{{ $pkg->facebook_speed ? $pkg->facebook_speed . 'M' : '—' }}</td>
                            <td class="py-2 px-2 text-center font-mono font-bold text-rose-700">{{ $pkg->youtube_speed ? $pkg->youtube_speed . 'M' : '—' }}</td>
                            <td class="py-2 px-2 text-center font-mono font-bold text-purple-700">{{ $pkg->bdix_speed ? $pkg->bdix_speed . 'M' : '—' }}</td>
                            <td class="py-2 px-2 text-right font-mono font-bold text-slate-900">@currency($retail)</td>
                            <td class="py-2 px-2 text-right font-mono font-semibold text-slate-600">@currency($cost)</td>
                            <td class="py-2 px-2 text-right font-mono font-bold text-emerald-700">+@currency($margin)</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-slate-400">No packages assigned.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- 3. Policy Terms -->
        <div class="p-3 rounded-lg bg-slate-50 border border-slate-200 text-xs space-y-1">
            <span class="font-bold text-slate-700 block">Terms &amp; SLA Conditions:</span>
            <ul class="list-disc list-inside text-[11px] text-slate-600 space-y-0.5">
                <li>All packages are synchronous full-duplex with unmetered peering to local BDIX, CDN &amp; Youtube cache nodes.</li>
                <li>Commission profit is automatically credited upon active customer renewal in the billing system.</li>
                <li>Sub-ISP partners must adhere to the minimum retail pricing policy set by host ISP.</li>
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
                    Host ISP NOC &amp; Commercial Seal
                </div>
            </div>
        </div>

    </div>

</body>
</html>
