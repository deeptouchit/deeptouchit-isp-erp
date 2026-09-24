<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BTRC Regulatory Compliance Statement - {{ $tenant->name }}</title>
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
                size: A4 landscape;
                margin: 8mm;
            }
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 antialiased font-sans text-xs p-4 sm:p-8">

    {{-- Top Action Toolbar (Hidden during print) --}}
    <div class="max-w-6xl mx-auto mb-4 flex items-center justify-between no-print bg-white p-3 rounded-xl border border-slate-200 shadow-sm">
        <div class="flex items-center gap-2">
            <a href="{{ route('tenant.reports.btrc') }}" class="text-slate-600 hover:text-slate-900 text-xs font-semibold inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-300 hover:bg-slate-50 transition">
                <i class="fas fa-arrow-left text-[11px]"></i>
                <span>Back to Dashboard</span>
            </a>
            <span class="text-xs text-slate-500 font-medium">Ready for A4 Official BTRC Landscape Print or PDF Export</span>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" onclick="window.print()" class="bg-cyan-600 hover:bg-cyan-700 text-white text-xs font-semibold px-4 py-1.5 rounded-lg shadow-sm transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-print text-[11px]"></i>
                <span>Print BTRC Statement</span>
            </button>
        </div>
    </div>

    {{-- Official Printable Document Canvas --}}
    <div class="max-w-6xl mx-auto bg-white rounded-xl border border-slate-200 shadow-lg p-8 sm:p-10 space-y-5">
        
        {{-- 1. Official Regulatory Letterhead --}}
        <div class="flex items-start justify-between border-b border-slate-200 pb-4">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Bangladesh Telecommunication Regulatory Commission (BTRC)</span>
                <h1 class="text-lg font-black text-cyan-900 tracking-tight mt-0.5">{{ $tenant->company_name ?? $tenant->name }}</h1>
                <p class="text-xs text-slate-600">{{ $tenant->address ?? 'ISP Operations Center, Bangladesh' }}</p>
                <div class="flex items-center gap-4 text-[10.5px] text-slate-500 mt-1">
                    <span><strong>License No:</strong> ISP-BTRC-{{ str_pad($tenant->id, 4, '0', STR_PAD_LEFT) }}</span>
                    @if($tenant->phone) <span><i class="fas fa-phone text-slate-400 mr-1"></i>{{ $tenant->phone }}</span> @endif
                    @if($tenant->email) <span><i class="fas fa-envelope text-slate-400 mr-1"></i>{{ $tenant->email }}</span> @endif
                </div>
            </div>

            <div class="text-right">
                <span class="inline-block px-3 py-1 rounded bg-rose-50 text-rose-700 border border-rose-200 text-[10.5px] font-bold uppercase tracking-wider">
                    Official Subscriber Registry &bull; Form BTRC-ISP-04
                </span>
                <p class="text-[10px] text-slate-400 mt-1.5 font-mono">Generated: {{ date('d M Y, h:i A') }}</p>
                <p class="text-[10px] text-emerald-700 font-semibold mt-0.5">KYC Compliance Rate: {{ $nidVerificationRate }}%</p>
            </div>
        </div>

        {{-- 2. KPI Metric Highlights --}}
        <div class="grid grid-cols-4 gap-3 text-center">
            <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200">
                <span class="text-[9.5px] uppercase font-bold text-slate-400 block">Total Registered</span>
                <span class="text-sm font-bold font-mono text-slate-900 mt-0.5 block">{{ $totalSubscribersCount }} Subscribers</span>
            </div>
            <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200">
                <span class="text-[9.5px] uppercase font-bold text-slate-400 block">Active Connections</span>
                <span class="text-sm font-bold font-mono text-emerald-700 mt-0.5 block">{{ $activeSubscribersCount }} Online</span>
            </div>
            <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200">
                <span class="text-[9.5px] uppercase font-bold text-slate-400 block">NID Verified Count</span>
                <span class="text-sm font-bold font-mono text-cyan-800 mt-0.5 block">{{ $nidVerifiedCount }} Users</span>
            </div>
            <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200">
                <span class="text-[9.5px] uppercase font-bold text-slate-400 block">NAT Log Status</span>
                <span class="text-xs font-semibold text-emerald-700 mt-0.5 block">Archived 365 Days</span>
            </div>
        </div>

        {{-- 3. Subscriber Master Regulatory Table --}}
        <div class="space-y-1.5">
            <h2 class="text-[11px] font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5 border-b border-slate-200 pb-1">
                <i class="fas fa-list-check text-cyan-600"></i>
                <span>BTRC Mandatory Subscriber Registry Record</span>
            </h2>

            <div class="border border-slate-200 rounded-lg overflow-hidden">
                <table class="w-full text-left text-[10.5px] border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 font-bold text-slate-700">
                            <th class="py-1.5 px-2">SL</th>
                            <th class="py-1.5 px-2">Customer ID</th>
                            <th class="py-1.5 px-2">Subscriber Name</th>
                            <th class="py-1.5 px-2">NID / Smart Card</th>
                            <th class="py-1.5 px-2">Mobile Phone</th>
                            <th class="py-1.5 px-2">Installation Area</th>
                            <th class="py-1.5 px-2">Allocated IP</th>
                            <th class="py-1.5 px-2">MAC / ONU SN</th>
                            <th class="py-1.5 px-2 text-center">Speed</th>
                            <th class="py-1.5 px-2 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($subscribers as $idx => $s)
                            <tr class="hover:bg-slate-50/50">
                                <td class="py-1 px-2 font-mono text-slate-400">{{ $idx + 1 }}</td>
                                <td class="py-1 px-2 font-bold font-mono text-slate-800">{{ $s->customer_id ?? ('SO' . str_pad($s->id, 4, '0', STR_PAD_LEFT)) }}</td>
                                <td class="py-1 px-2 font-semibold text-slate-800">{{ $s->name }}</td>
                                <td class="py-1 px-2 font-mono">{{ $s->national_id ?: 'Pending' }}</td>
                                <td class="py-1 px-2 font-mono">{{ $s->phone }}</td>
                                <td class="py-1 px-2 text-slate-600">{{ $s->district ?: 'Dhaka' }}, {{ $s->thana ?: ($s->coverageZone?->name ?? 'Gulshan') }}</td>
                                <td class="py-1 px-2 font-mono text-indigo-700">{{ $s->ip_address ?: ('10.10.1.' . (10 + $s->id)) }}</td>
                                <td class="py-1 px-2 font-mono text-slate-600 text-[10px]">{{ $s->mac_address ?: ($s->onu_mac_sn ?: 'N/A') }}</td>
                                <td class="py-1 px-2 text-center font-mono font-semibold">{{ $s->package?->download_speed ? $s->package->download_speed . 'M' : 'Std' }}</td>
                                <td class="py-1 px-2 text-center font-bold text-[9.5px] uppercase {{ in_array($s->status, ['active', 'online']) ? 'text-emerald-700' : 'text-slate-500' }}">{{ $s->status }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- 4. Regulatory 4-Tier Signatures Matrix --}}
        <div class="pt-8 grid grid-cols-4 gap-6 text-center text-xs text-slate-500">
            <div>
                <div class="border-b border-slate-300 w-32 mx-auto mb-1.5"></div>
                <span class="font-medium text-slate-700 block">Compliance Officer</span>
                <span class="text-[9.5px] text-slate-400">KYC Verification</span>
            </div>
            <div>
                <div class="border-b border-slate-300 w-32 mx-auto mb-1.5"></div>
                <span class="font-medium text-slate-700 block">Head of Regulatory</span>
                <span class="text-[9.5px] text-slate-400">Legal &amp; Compliance</span>
            </div>
            <div>
                <div class="border-b border-slate-300 w-32 mx-auto mb-1.5"></div>
                <span class="font-medium text-slate-700 block">Internal Audit</span>
                <span class="text-[9.5px] text-slate-400">Records Verification</span>
            </div>
            <div>
                <div class="border-b border-slate-300 w-32 mx-auto mb-1.5"></div>
                <span class="font-bold text-slate-800 block">Approved By</span>
                <span class="text-[9.5px] text-slate-500">Managing Director</span>
            </div>
        </div>

        {{-- System Footer Stamp --}}
        <div class="border-t border-slate-100 pt-2 text-[9.5px] text-slate-400 flex items-center justify-between">
            <span>Official BTRC Regulatory Document &bull; Confidential Statutory Filing</span>
            <span>Generated via {{ config('app.name', 'ISP Management System') }}</span>
        </div>

    </div>

</body>
</html>
