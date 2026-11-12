<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Bandwidth & MRTG Utilization Statement - :company', ['company' => $tenant->name]) }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { 
                background: white !important; 
                margin: 0 !important; 
                padding: 0 !important; 
                -webkit-print-color-adjust: exact !important; 
                print-color-adjust: exact !important;
            }
            .page-break { page-break-after: always; }
            @page {
                size: A4 portrait;
                margin: 10mm;
            }
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 antialiased font-sans text-xs p-4 sm:p-8">

    {{-- Top Action Toolbar (Hidden during print) --}}
    <div class="max-w-4xl mx-auto mb-4 flex items-center justify-between no-print bg-white p-3 rounded-xl border border-slate-200 shadow-sm">
        <div class="flex items-center gap-2">
            <a href="{{ route('tenant.reports.mrtg') }}" class="text-slate-600 hover:text-slate-900 text-xs font-semibold inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-300 hover:bg-slate-50 transition">
                <i class="fas fa-arrow-left text-[11px]"></i>
                <span>{{ __('Back to Dashboard') }}</span>
            </a>
            <span class="text-xs text-slate-500 font-medium">{{ __('Ready for A4 Official Document Print or PDF Export') }}</span>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" onclick="window.print()" class="bg-cyan-600 hover:bg-cyan-700 text-white text-xs font-semibold px-4 py-1.5 rounded-lg shadow-sm transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-print text-[11px]"></i>
                <span>{{ __('Print Document') }}</span>
            </button>
        </div>
    </div>

    {{-- Official Printable Document Canvas --}}
    <div class="max-w-4xl mx-auto bg-white rounded-xl border border-slate-200 shadow-lg p-8 sm:p-10 space-y-6">
        
        {{-- 1. Official Corporate Letterhead --}}
        <div class="flex items-start justify-between border-b border-slate-200 pb-5">
            <div>
                <h1 class="text-xl font-black text-cyan-900 tracking-tight">{{ $tenant->company_name ?? $tenant->name }}</h1>
                <p class="text-xs text-slate-600 mt-0.5">{{ $tenant->address ?? __('ISP Operations Center, Bangladesh') }}</p>
                <div class="flex items-center gap-4 text-[11px] text-slate-500 mt-1">
                    @if($tenant->phone) <span><i class="fas fa-phone text-slate-400 mr-1"></i>{{ $tenant->phone }}</span> @endif
                    @if($tenant->email) <span><i class="fas fa-envelope text-slate-400 mr-1"></i>{{ $tenant->email }}</span> @endif
                </div>
            </div>

            <div class="text-right">
                <span class="inline-block px-3 py-1 rounded bg-rose-50 text-rose-700 border border-rose-200 text-[11px] font-bold uppercase tracking-wider">
                    {{ __('MRTG Bandwidth Telemetry Statement') }}
                </span>
                <p class="text-[11px] font-mono text-slate-500 mt-1.5">{{ __('Target: :target', ['target' => $selectedRouter ? $selectedRouter->name : __('Consolidated Backbone')]) }}</p>
                <p class="text-[10px] text-slate-400 mt-0.5 font-mono">{{ __('Generated: :datetime', ['datetime' => \Carbon\Carbon::now()->translatedFormat('d M Y, h:i A')]) }}</p>
            </div>
        </div>

        {{-- 2. Bandwidth KPI Metric Highlights --}}
        <div class="grid grid-cols-4 gap-3 text-center">
            <div class="p-3 rounded-lg bg-slate-50 border border-slate-200">
                <span class="text-[10px] uppercase font-bold text-slate-400 block">{{ __('Peak Inflow (Download RX)') }}</span>
                <span class="text-sm font-bold font-mono text-emerald-700 mt-0.5 block">
                    {{ $peakDownloadMbps >= 1000 ? __(':value Gbps', ['value' => round($peakDownloadMbps / 1000, 2)]) : __(':value Mbps', ['value' => $peakDownloadMbps]) }}
                </span>
            </div>
            <div class="p-3 rounded-lg bg-slate-50 border border-slate-200">
                <span class="text-[10px] uppercase font-bold text-slate-400 block">{{ __('Peak Outflow (Upload TX)') }}</span>
                <span class="text-sm font-bold font-mono text-cyan-700 mt-0.5 block">
                    {{ $peakUploadMbps >= 1000 ? __(':value Gbps', ['value' => round($peakUploadMbps / 1000, 2)]) : __(':value Mbps', ['value' => $peakUploadMbps]) }}
                </span>
            </div>
            <div class="p-3 rounded-lg bg-slate-50 border border-slate-200">
                <span class="text-[10px] uppercase font-bold text-slate-400 block">{{ __('Allocated Capacity') }}</span>
                <span class="text-sm font-bold font-mono text-indigo-700 mt-0.5 block">
                    {{ $totalSubscribedBandwidthMbps >= 1000 ? __(':value Gbps', ['value' => round($totalSubscribedBandwidthMbps / 1000, 2)]) : __(':value Mbps', ['value' => $totalSubscribedBandwidthMbps]) }}
                </span>
            </div>
            <div class="p-3 rounded-lg bg-slate-50 border border-slate-200">
                <span class="text-[10px] uppercase font-bold text-slate-400 block">{{ __('Trunk Utilization') }}</span>
                <span class="text-sm font-bold font-mono text-amber-700 mt-0.5 block">{{ $upstreamUtilizationPct }}%</span>
            </div>
        </div>

        {{-- 3. Interface & Trunk Telemetry Matrix --}}
        <div class="space-y-2">
            <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5 border-b border-slate-200 pb-1">
                <i class="fas fa-network-wired text-cyan-600"></i>
                <span>{{ __('Interface & Trunk Live Bandwidth Capacity Breakdown') }}</span>
            </h2>

            <div class="border border-slate-200 rounded-lg overflow-hidden">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold text-slate-700">
                            <th class="py-2 px-2.5">{{ __('Port Name') }}</th>
                            <th class="py-2 px-2.5">{{ __('Router Node') }}</th>
                            <th class="py-2 px-2.5">{{ __('Classification') }}</th>
                            <th class="py-2 px-2.5">{{ __('Link Capacity') }}</th>
                            <th class="py-2 px-2.5 text-right font-bold text-emerald-800">{{ __('Current RX') }}</th>
                            <th class="py-2 px-2.5 text-right font-bold text-cyan-800">{{ __('Current TX') }}</th>
                            <th class="py-2 px-2.5 text-right">{{ __('Peak RX') }}</th>
                            <th class="py-2 px-2.5 text-center">{{ __('Load %') }}</th>
                            <th class="py-2 px-2.5 text-center">{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($interfacesMatrix as $iface)
                            <tr class="hover:bg-slate-50/50">
                                <td class="py-1.5 px-2.5 font-bold font-mono text-slate-800">{{ $iface['name'] }}</td>
                                <td class="py-1.5 px-2.5 text-slate-600">{{ $iface['router_name'] }}</td>
                                <td class="py-1.5 px-2.5 font-medium text-slate-700">{{ $iface['type_label'] }}</td>
                                <td class="py-1.5 px-2.5 font-mono text-slate-600">{{ $iface['capacity_label'] }}</td>
                                <td class="py-1.5 px-2.5 text-right font-mono font-bold text-emerald-800 bg-emerald-50/20">{{ __(':value Mbps', ['value' => $iface['rx_mbps']]) }}</td>
                                <td class="py-1.5 px-2.5 text-right font-mono font-bold text-cyan-800 bg-cyan-50/20">{{ __(':value Mbps', ['value' => $iface['tx_mbps']]) }}</td>
                                <td class="py-1.5 px-2.5 text-right font-mono">{{ __(':value Mbps', ['value' => $iface['peak_rx_mbps']]) }}</td>
                                <td class="py-1.5 px-2.5 text-center font-mono font-semibold">{{ $iface['load_pct'] }}%</td>
                                <td class="py-1.5 px-2.5 text-center font-mono font-bold text-emerald-700">{{ $iface['status_label'] ?? __($iface['status']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- 4. Traffic Classification Mix --}}
        <div class="space-y-2">
            <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5 border-b border-slate-200 pb-1">
                <i class="fas fa-shuffle text-purple-600"></i>
                <span>{{ __('Traffic Classification & Peer Transit Mix') }}</span>
            </h2>

            <div class="grid grid-cols-4 gap-2 text-center text-xs">
                <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200">
                    <span class="text-[10px] font-bold text-slate-600 block">{{ __('International IIG / ITC') }}</span>
                    <span class="font-mono font-bold text-slate-900 text-sm mt-0.5 block">58.5%</span>
                    <span class="text-[10px] text-slate-400">{{ __('Global Transit') }}</span>
                </div>
                <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200">
                    <span class="text-[10px] font-bold text-slate-600 block">{{ __('BDIX Domestic IX') }}</span>
                    <span class="font-mono font-bold text-purple-700 text-sm mt-0.5 block">26.5%</span>
                    <span class="text-[10px] text-slate-400">{{ __('National Peering') }}</span>
                </div>
                <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200">
                    <span class="text-[10px] font-bold text-slate-600 block">{{ __('CDN / Edge Cache') }}</span>
                    <span class="font-mono font-bold text-emerald-700 text-sm mt-0.5 block">11.5%</span>
                    <span class="text-[10px] text-slate-400">{{ __('Google / Meta') }}</span>
                </div>
                <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200">
                    <span class="text-[10px] font-bold text-slate-600 block">{{ __('Sub-ISP Trunking') }}</span>
                    <span class="font-mono font-bold text-cyan-700 text-sm mt-0.5 block">3.5%</span>
                    <span class="text-[10px] text-slate-400">{{ __('Wholesale VLANs') }}</span>
                </div>
            </div>
        </div>

        {{-- 5. NOC & Operations 4-Tier Signatures Matrix --}}
        <div class="pt-12 grid grid-cols-4 gap-6 text-center text-xs text-slate-500">
            <div>
                <div class="border-b border-slate-300 w-32 mx-auto mb-1.5"></div>
                <span class="font-medium text-slate-700 block">{{ __('NOC Engineer') }}</span>
                <span class="text-[10px] text-slate-400">{{ __('Network Telemetry') }}</span>
            </div>
            <div>
                <div class="border-b border-slate-300 w-32 mx-auto mb-1.5"></div>
                <span class="font-medium text-slate-700 block">{{ __('NOC Manager') }}</span>
                <span class="text-[10px] text-slate-400">{{ __('Operations Head') }}</span>
            </div>
            <div>
                <div class="border-b border-slate-300 w-32 mx-auto mb-1.5"></div>
                <span class="font-medium text-slate-700 block">{{ __('Internal Audit') }}</span>
                <span class="text-[10px] text-slate-400">{{ __('Compliance Officer') }}</span>
            </div>
            <div>
                <div class="border-b border-slate-300 w-32 mx-auto mb-1.5"></div>
                <span class="font-bold text-slate-800 block">{{ __('Approved By') }}</span>
                <span class="text-[10px] text-slate-500">{{ __('Chief Technology Officer') }}</span>
            </div>
        </div>

        {{-- System Footer Stamp --}}
        <div class="border-t border-slate-100 pt-3 text-[10px] text-slate-400 flex items-center justify-between">
            <span>{{ __('Official Network Telemetry Document • Confidential Internal Record') }}</span>
            <span>{{ __('Generated via :app', ['app' => config('app.name', __('ISP Management System'))]) }}</span>
        </div>

    </div>

</body>
</html>
