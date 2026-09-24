<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff &amp; Collectors Roster - {{ $reseller->name ?? 'Partner' }}</title>
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
            <span class="font-bold text-slate-800 text-sm">Staff Directory Roster</span>
            <span class="text-xs text-slate-500 font-mono">Generated: {{ now()->format('d M Y, h:i A') }}</span>
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

    <!-- Printable Paper Sheet -->
    <div class="max-w-4xl mx-auto bg-white border border-slate-200 rounded-xl p-6 print:border-none print:p-0 print:shadow-none space-y-5">
        
        <!-- 1. Header -->
        <div class="flex items-start justify-between border-b border-slate-200 pb-4">
            <div>
                <h1 class="text-lg font-bold text-slate-900 tracking-tight">{{ $reseller->name ?? 'Sub-ISP Partner' }}</h1>
                <p class="text-[11px] text-slate-500">Authorized Partner • {{ $tenant->company_name ?? 'ISP Management Network' }}</p>
                <div class="mt-1 text-[10.5px] text-slate-600 font-mono">
                    <div>Contact: {{ $reseller->mobile ?? 'N/A' }} • Email: {{ $reseller->email ?? 'N/A' }}</div>
                    <div>Address: {{ $reseller->address ?? 'Territory Zone' }}</div>
                </div>
            </div>
            <div class="text-right">
                <div class="inline-block px-2.5 py-1 rounded-lg bg-cyan-50 text-cyan-800 font-mono font-bold text-xs border border-cyan-200">
                    PARTNER ID: {{ $reseller->code ?? 'P-1001' }}
                </div>
                <div class="text-[11px] text-slate-500 font-mono mt-1.5">Total Staff: {{ count($staff) }} Active Members</div>
                <div class="text-[10px] text-slate-400 font-mono mt-0.5">Date: {{ now()->format('F d, Y') }}</div>
            </div>
        </div>

        <!-- 2. Staff Roster Table -->
        <div class="space-y-2">
            <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Employee &amp; Collector Directory</h2>

            <table class="w-full border-collapse text-[10.5px]">
                <thead>
                    <tr class="bg-slate-100 border-b border-slate-200 text-slate-700">
                        <th class="py-1.5 px-2 text-left font-semibold w-8">#</th>
                        <th class="py-1.5 px-2 text-left font-semibold w-24">Staff ID</th>
                        <th class="py-1.5 px-2 text-left font-semibold">Employee Name</th>
                        <th class="py-1.5 px-2 text-left font-semibold">Role / Designation</th>
                        <th class="py-1.5 px-2 text-left font-semibold font-mono">Contact Phone</th>
                        <th class="py-1.5 px-2 text-left font-semibold">Email</th>
                        <th class="py-1.5 px-2 text-right font-semibold">Salary</th>
                        <th class="py-1.5 px-2 text-center font-semibold w-16">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($staff as $index => $s)
                        <tr>
                            <td class="py-1.5 px-2 font-mono text-slate-500">{{ $index + 1 }}</td>
                            <td class="py-1.5 px-2 font-mono font-bold text-cyan-800">{{ $s->staff_id ?: ('STF-' . $s->id) }}</td>
                            <td class="py-1.5 px-2 font-semibold text-slate-800">{{ $s->name }}</td>
                            <td class="py-1.5 px-2 text-slate-700">{{ $s->designation ?: ucwords(str_replace(['reseller_', '_'], ['', ' '], $s->role)) }}</td>
                            <td class="py-1.5 px-2 font-mono text-slate-700">{{ $s->phone ?: ($s->mobile ?: 'N/A') }}</td>
                            <td class="py-1.5 px-2 text-slate-600 font-mono">{{ $s->email }}</td>
                            <td class="py-1.5 px-2 text-right font-mono font-semibold text-slate-800">
                                @currency($s->salary_amount ?? 0)
                            </td>
                            <td class="py-1.5 px-2 text-center">
                                @if($s->status === 'active')
                                    <span class="px-1.5 py-0.2 rounded text-[9px] font-bold font-mono bg-emerald-50 text-emerald-700 border border-emerald-200">ACTIVE</span>
                                @else
                                    <span class="px-1.5 py-0.2 rounded text-[9px] font-bold font-mono bg-slate-100 text-slate-600 border border-slate-200">INACTIVE</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-slate-400">No staff members found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- 3. Signatures -->
        <div class="pt-12 grid grid-cols-2 gap-8 text-[11px] border-t border-slate-200 mt-6">
            <div class="text-left">
                <div class="w-40 border-b border-slate-300 pb-1 mb-1"></div>
                <div class="font-bold text-slate-800">{{ $reseller->name ?? 'Partner Representative' }}</div>
                <div class="text-slate-500 text-[10px]">Managing Partner / Reseller Admin</div>
            </div>
            <div class="text-right">
                <div class="w-40 border-b border-slate-300 pb-1 mb-1 ml-auto"></div>
                <div class="font-bold text-slate-800">Human Resources &amp; Operations</div>
                <div class="text-slate-500 text-[10px]">{{ $tenant->company_name ?? 'Host ISP Organization' }}</div>
            </div>
        </div>

    </div>

</body>
</html>
