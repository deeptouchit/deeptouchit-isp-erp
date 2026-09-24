<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revenue &amp; Growth Statement - {{ $tenant->name }}</title>
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
            <a href="{{ route('tenant.reports.revenue') }}" class="text-slate-600 hover:text-slate-900 text-xs font-semibold inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-300 hover:bg-slate-50 transition">
                <i class="fas fa-arrow-left text-[11px]"></i>
                <span>Back to Dashboard</span>
            </a>
            <span class="text-xs text-slate-500 font-medium">Ready for A4 Official Document Print or PDF Export</span>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" onclick="window.print()" class="bg-cyan-600 hover:bg-cyan-700 text-white text-xs font-semibold px-4 py-1.5 rounded-lg shadow-sm transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-print text-[11px]"></i>
                <span>Print Document</span>
            </button>
        </div>
    </div>

    {{-- Official Printable Document Canvas --}}
    <div class="max-w-4xl mx-auto bg-white rounded-xl border border-slate-200 shadow-lg p-8 sm:p-10 space-y-6">
        
        {{-- 1. Official Corporate Letterhead --}}
        <div class="flex items-start justify-between border-b border-slate-200 pb-5">
            <div>
                <h1 class="text-xl font-black text-cyan-900 tracking-tight">{{ $tenant->company_name ?? $tenant->name }}</h1>
                <p class="text-xs text-slate-600 mt-0.5">{{ $tenant->address ?? 'ISP Operations Center, Bangladesh' }}</p>
                <div class="flex items-center gap-4 text-[11px] text-slate-500 mt-1">
                    @if($tenant->phone) <span><i class="fas fa-phone text-slate-400 mr-1"></i>{{ $tenant->phone }}</span> @endif
                    @if($tenant->email) <span><i class="fas fa-envelope text-slate-400 mr-1"></i>{{ $tenant->email }}</span> @endif
                </div>
            </div>

            <div class="text-right">
                <span class="inline-block px-3 py-1 rounded bg-rose-50 text-rose-700 border border-rose-200 text-[11px] font-bold uppercase tracking-wider">
                    Executive Revenue Statement
                </span>
                <p class="text-[11px] font-mono text-slate-500 mt-1.5">Period: {{ $from->format('d M Y') }} - {{ $to->format('d M Y') }}</p>
                <p class="text-[10px] text-slate-400 mt-0.5 font-mono">Generated: {{ date('d M Y, h:i A') }}</p>
            </div>
        </div>

        {{-- 2. Financial KPI Metric Highlights --}}
        <div class="grid grid-cols-4 gap-3 text-center">
            <div class="p-3 rounded-lg bg-slate-50 border border-slate-200">
                <span class="text-[10px] uppercase font-bold text-slate-400 block">Total Gross Revenue</span>
                <span class="text-sm font-bold font-mono text-emerald-700 mt-0.5 block">@currency($totalRevenue)</span>
            </div>
            <div class="p-3 rounded-lg bg-slate-50 border border-slate-200">
                <span class="text-[10px] uppercase font-bold text-slate-400 block">Operating OPEX</span>
                <span class="text-sm font-bold font-mono text-rose-700 mt-0.5 block">@currency($periodExpenses)</span>
            </div>
            <div class="p-3 rounded-lg bg-slate-50 border border-slate-200">
                <span class="text-[10px] uppercase font-bold text-slate-400 block">Net Operating Profit</span>
                <span class="text-sm font-bold font-mono {{ $netProfit >= 0 ? 'text-cyan-800' : 'text-rose-700' }} mt-0.5 block">@currency($netProfit)</span>
            </div>
            <div class="p-3 rounded-lg bg-slate-50 border border-slate-200">
                <span class="text-[10px] uppercase font-bold text-slate-400 block">Revenue Sources</span>
                <span class="text-xs font-semibold text-slate-700 mt-0.5 block">Retail &amp; Wholesale</span>
            </div>
        </div>

        {{-- 3. 12-Month Continuous Historical Growth Breakdown --}}
        <div class="space-y-2">
            <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5 border-b border-slate-200 pb-1">
                <i class="fas fa-table-list text-cyan-600"></i>
                <span>12-Month Historical Growth &amp; Performance Matrix</span>
            </h2>

            <div class="border border-slate-200 rounded-lg overflow-hidden">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold text-slate-700">
                            <th class="py-2 px-2.5">Month</th>
                            <th class="py-2 px-2.5 text-right">Retail</th>
                            <th class="py-2 px-2.5 text-right">Wholesale</th>
                            <th class="py-2 px-2.5 text-right">Direct</th>
                            <th class="py-2 px-2.5 text-right font-bold text-emerald-800">Total Rev</th>
                            <th class="py-2 px-2.5 text-right text-rose-700">Expense</th>
                            <th class="py-2 px-2.5 text-right font-bold text-cyan-900">Net Profit</th>
                            <th class="py-2 px-2.5 text-center">MoM %</th>
                            <th class="py-2 px-2.5 text-center">Subs</th>
                            <th class="py-2 px-2.5 text-right">ARPU</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($monthlyMatrix as $row)
                            <tr class="hover:bg-slate-50/50">
                                <td class="py-1.5 px-2.5 font-bold font-mono text-slate-800">{{ $row['month_label'] }}</td>
                                <td class="py-1.5 px-2.5 text-right font-mono">@currency($row['retail_revenue'])</td>
                                <td class="py-1.5 px-2.5 text-right font-mono">@currency($row['wholesale_revenue'])</td>
                                <td class="py-1.5 px-2.5 text-right font-mono font-bold text-emerald-800 bg-emerald-50/30">@currency($row['total_revenue'])</td>
                                <td class="py-1.5 px-2.5 text-right font-mono font-bold text-rose-700">@currency($row['total_expenses'])</td>
                                <td class="py-1.5 px-2.5 text-right font-mono font-bold {{ $row['net_profit'] >= 0 ? 'text-cyan-900' : 'text-rose-600' }}">@currency($row['net_profit'])</td>
                                <td class="py-1.5 px-2.5 text-center font-mono font-semibold {{ $row['growth_rate_pct'] >= 0 ? 'text-emerald-700' : 'text-rose-600' }}">
                                    {{ ($row['growth_rate_pct'] >= 0 ? '+' : '') . $row['growth_rate_pct'] }}%
                                </td>
                                <td class="py-1.5 px-2.5 text-center font-mono font-medium">{{ number_format($row['active_subscribers']) }}</td>
                                <td class="py-1.5 px-2.5 text-right font-mono font-semibold">@currency($row['arpu'])</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- 4. Audit & Verification 4-Tier Signatures Matrix --}}
        <div class="pt-12 grid grid-cols-4 gap-6 text-center text-xs text-slate-500">
            <div>
                <div class="border-b border-slate-300 w-32 mx-auto mb-1.5"></div>
                <span class="font-medium text-slate-700 block">Prepared By</span>
                <span class="text-[10px] text-slate-400">Accounts Executive</span>
            </div>
            <div>
                <div class="border-b border-slate-300 w-32 mx-auto mb-1.5"></div>
                <span class="font-medium text-slate-700 block">Verified By</span>
                <span class="text-[10px] text-slate-400">Finance Manager</span>
            </div>
            <div>
                <div class="border-b border-slate-300 w-32 mx-auto mb-1.5"></div>
                <span class="font-medium text-slate-700 block">Internal Audit</span>
                <span class="text-[10px] text-slate-400">Compliance Officer</span>
            </div>
            <div>
                <div class="border-b border-slate-300 w-32 mx-auto mb-1.5"></div>
                <span class="font-bold text-slate-800 block">Approved By</span>
                <span class="text-[10px] text-slate-500">Managing Director</span>
            </div>
        </div>

        {{-- System Footer Stamp --}}
        <div class="border-t border-slate-100 pt-3 text-[10px] text-slate-400 flex items-center justify-between">
            <span>Official Billing Document &bull; Confidential Internal Record</span>
            <span>Generated via {{ config('app.name', 'ISP Management System') }}</span>
        </div>

    </div>

</body>
</html>
