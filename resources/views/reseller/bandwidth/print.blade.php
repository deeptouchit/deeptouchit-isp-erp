<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bandwidth Allocation & Usage Statement - {{ $reseller->name ?? 'Partner' }}</title>
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
            <span class="font-bold text-slate-800 text-sm">Wholesale Bandwidth Report</span>
            <span class="text-xs text-slate-500 font-mono">Generated: {{ now()->format('d M Y, h:i A') }}</span>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-3.5 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-print"></i>
                <span>Print Document</span>
            </button>
            <button onclick="window.close()" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200 transition cursor-pointer">
                Close
            </button>
        </div>
    </div>

    <!-- Printable Paper Sheet -->
    <div class="max-w-4xl mx-auto bg-white border border-slate-200 rounded-xl p-6 print:border-none print:p-0 print:shadow-none space-y-5">
        
        <!-- 1. Header: Host ISP & Reseller Partner Details -->
        <div class="flex items-start justify-between border-b border-slate-200 pb-4">
            <div>
                <h1 class="text-lg font-bold text-slate-900 tracking-tight">{{ $tenant->company_name ?? ($tenant->name ?? 'ISP Management Network') }}</h1>
                <p class="text-[11px] text-slate-500">Core Network Operations Center (NOC) &amp; Wholesale Upstream</p>
                <div class="mt-1.5 text-[10.5px] text-slate-600 space-y-0.5 font-mono">
                    <div>Host Router: {{ $bandwidth?->router?->name ?? 'Core Gateway' }} ({{ $bandwidth?->router?->ip_address ?? '10.0.0.1' }})</div>
                    <div>Interface: {{ $bandwidth?->interface_name ?? 'ether1-wan' }} • VLAN ID: {{ $bandwidth?->vlan_id ?? '100' }}</div>
                </div>
            </div>
            <div class="text-right">
                <div class="inline-block px-2.5 py-1 rounded-lg bg-purple-50 text-purple-700 font-mono font-bold text-xs border border-purple-200">
                    PARTNER ID: {{ $reseller->code ?? 'P-1001' }}
                </div>
                <h2 class="text-sm font-bold text-slate-800 mt-1.5">{{ $reseller->name ?? 'Reseller Partner' }}</h2>
                <div class="text-[10.5px] text-slate-500">Mobile: {{ $reseller->mobile ?? 'N/A' }}</div>
                <div class="text-[10px] text-slate-400 font-mono mt-0.5">Date: {{ now()->format('F d, Y') }}</div>
            </div>
        </div>

        <!-- 2. Bandwidth Summary Metrics (6 Boxes) -->
        <div class="grid grid-cols-3 sm:grid-cols-6 gap-2">
            <div class="p-2 rounded-lg bg-slate-50 border border-slate-200 text-center">
                <span class="text-[9px] uppercase font-bold text-slate-500 block">Total Capacity</span>
                <span class="text-xs font-bold font-mono text-purple-700 block mt-0.5">
                    {{ $bandwidth?->formatted_total_bandwidth ?? '500 Mbps' }}
                </span>
            </div>
            <div class="p-2 rounded-lg bg-slate-50 border border-slate-200 text-center">
                <span class="text-[9px] uppercase font-bold text-slate-500 block">Global (CIR)</span>
                <span class="text-xs font-bold font-mono text-blue-600 block mt-0.5">
                    {{ number_format($bandwidth?->global_bandwidth_mbps ?? 0, 0) }} Mbps
                </span>
            </div>
            <div class="p-2 rounded-lg bg-slate-50 border border-slate-200 text-center">
                <span class="text-[9px] uppercase font-bold text-slate-500 block">BDIX Exchange</span>
                <span class="text-xs font-bold font-mono text-amber-600 block mt-0.5">
                    {{ number_format($bandwidth?->bdix_bandwidth_mbps ?? 0, 0) }} Mbps
                </span>
            </div>
            <div class="p-2 rounded-lg bg-slate-50 border border-slate-200 text-center">
                <span class="text-[9px] uppercase font-bold text-slate-500 block">CDN / Cache</span>
                <span class="text-xs font-bold font-mono text-emerald-600 block mt-0.5">
                    {{ number_format(($bandwidth?->cdn_bandwidth_mbps ?? 0) + ($bandwidth?->ggc_bandwidth_mbps ?? 0) + ($bandwidth?->fna_bandwidth_mbps ?? 0), 0) }} Mbps
                </span>
            </div>
            <div class="p-2 rounded-lg bg-slate-50 border border-slate-200 text-center">
                <span class="text-[9px] uppercase font-bold text-slate-500 block">Active Users</span>
                <span class="text-xs font-bold font-mono text-indigo-600 block mt-0.5">
                    {{ number_format($activeSubscribersCount) }}
                </span>
            </div>
            <div class="p-2 rounded-lg bg-slate-50 border border-slate-200 text-center">
                <span class="text-[9px] uppercase font-bold text-slate-500 block">Monthly Rate</span>
                <span class="text-xs font-bold font-mono text-slate-900 block mt-0.5">
                    @currency($bandwidth?->monthly_bill_amount ?? 0)
                </span>
            </div>
        </div>

        <!-- 3. Wholesale Line Profile & Specifications -->
        <div class="border border-slate-200 rounded-lg p-3 bg-slate-50/50 space-y-2">
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Wholesale Line Configuration</h3>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-[11px]">
                <div>
                    <span class="text-slate-500 block">Allocation Profile:</span>
                    <span class="font-bold text-slate-800">{{ $bandwidth?->allocation_type ? str_replace('_', ' ', $bandwidth->allocation_type) : 'DEDICATED CIR 1:1' }}</span>
                </div>
                <div>
                    <span class="text-slate-500 block">MikroTik Queue Name:</span>
                    <span class="font-mono font-bold text-slate-800">{{ $bandwidth?->mikrotik_queue_name ?? 'Default' }}</span>
                </div>
                <div>
                    <span class="text-slate-500 block">Current Ingress Usage:</span>
                    <span class="font-mono font-bold text-purple-700">{{ number_format($bandwidth?->current_usage_mbps ?? 0, 2) }} Mbps</span>
                </div>
                <div>
                    <span class="text-slate-500 block">Peak Ingress Usage:</span>
                    <span class="font-mono font-bold text-indigo-700">{{ number_format($bandwidth?->peak_usage_mbps ?? 0, 2) }} Mbps</span>
                </div>
            </div>
        </div>

        <!-- 4. Retail Subscribers Attached to this Bandwidth Pool -->
        <div class="space-y-2">
            <div class="flex items-center justify-between">
                <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Subscriber Traffic Distribution ({{ count($customers) }} Records)</h3>
                <span class="text-[10px] text-slate-500 font-mono">Live Sessions: {{ $onlineSessionsCount }} Online</span>
            </div>

            <table class="w-full border-collapse text-[10.5px]">
                <thead>
                    <tr class="bg-slate-100 border-b border-slate-200 text-slate-700">
                        <th class="py-1 px-2 text-left font-semibold w-8">#</th>
                        <th class="py-1 px-2 text-left font-semibold">Subscriber Name</th>
                        <th class="py-1 px-2 text-left font-semibold">User ID</th>
                        <th class="py-1 px-2 text-left font-semibold">Package &amp; Speed</th>
                        <th class="py-1 px-2 text-left font-semibold font-mono">IP Address</th>
                        <th class="py-1 px-2 text-center font-semibold">State</th>
                        <th class="py-1 px-2 text-right font-semibold">Monthly Fee</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($customers as $index => $c)
                        <tr>
                            <td class="py-1 px-2 font-mono text-slate-500">{{ $index + 1 }}</td>
                            <td class="py-1 px-2 font-semibold text-slate-800">{{ $c->name }}</td>
                            <td class="py-1 px-2 font-mono text-purple-700">{{ $c->customer_id }}</td>
                            <td class="py-1 px-2 text-slate-700">{{ $c->package_display_name }}</td>
                            <td class="py-1 px-2 font-mono text-slate-600">{{ $c->ip_address ?: 'Dynamic' }}</td>
                            <td class="py-1 px-2 text-center">
                                @if($c->online_status === 'online')
                                    <span class="px-1.5 py-0.2 rounded text-[9px] font-bold font-mono bg-emerald-50 text-emerald-700 border border-emerald-200">ONLINE</span>
                                @else
                                    <span class="px-1.5 py-0.2 rounded text-[9px] font-bold font-mono bg-slate-100 text-slate-600 border border-slate-200">OFFLINE</span>
                                @endif
                            </td>
                            <td class="py-1 px-2 text-right font-mono font-semibold text-slate-800">
                                @currency($c->monthly_bill)
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-slate-400">No attached subscribers found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- 5. Signatures and Verification Stamp -->
        <div class="pt-8 grid grid-cols-2 gap-8 text-[11px] border-t border-slate-200">
            <div class="text-left">
                <div class="w-40 border-b border-slate-300 pb-1 mb-1"></div>
                <div class="font-bold text-slate-800">{{ $reseller->name ?? 'Partner Representative' }}</div>
                <div class="text-slate-500 text-[10px]">Sub-ISP Reseller Partner</div>
            </div>
            <div class="text-right">
                <div class="w-40 border-b border-slate-300 pb-1 mb-1 ml-auto"></div>
                <div class="font-bold text-slate-800">Network Operations Manager</div>
                <div class="text-slate-500 text-[10px]">{{ $tenant->company_name ?? 'Host ISP Upstream' }}</div>
            </div>
        </div>

    </div>

</body>
</html>
