<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity & Security Audit Trail - {{ $tenant->company_name ?? $tenant->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 15mm;
        }
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #1e293b;
            background: #fff;
        }
    </style>
</head>
<body class="p-4 sm:p-8 max-w-4xl mx-auto">

    {{-- Print Controls Header --}}
    <div class="no-print mb-6 p-4 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between shadow-xs">
        <div class="flex items-center gap-2 text-slate-700 font-semibold text-xs">
            <i class="fas fa-shield-halved text-cyan-600 text-sm"></i>
            <span>System Activity &amp; Security Audit Trail Official Statement (A4)</span>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-4 py-1.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-print"></i>
                <span>Print Statement</span>
            </button>
            <button onclick="window.close()" class="px-3 py-1.5 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg text-xs font-semibold transition cursor-pointer">
                <span>Close</span>
            </button>
        </div>
    </div>

    {{-- Official Header & Letterhead --}}
    <div class="border-b-2 border-slate-900 pb-4 mb-5 flex items-start justify-between">
        <div class="flex items-center gap-3">
            @if($tenant->logo)
                <img src="{{ asset('storage/' . $tenant->logo) }}" alt="Logo" class="h-12 w-auto object-contain">
            @else
                <div class="w-12 h-12 rounded-lg bg-slate-900 text-white font-bold flex items-center justify-center text-lg">
                    {{ substr($tenant->company_name ?? $tenant->name, 0, 2) }}
                </div>
            @endif
            <div>
                <h1 class="text-lg font-bold text-slate-900 tracking-tight uppercase">{{ $tenant->company_name ?? $tenant->name }}</h1>
                <p class="text-[11px] text-slate-600">{{ $tenant->address ?? 'Corporate Internet Service Provider Operations' }}</p>
                <p class="text-[10px] text-slate-500 font-mono">Hotline: {{ $tenant->support_phone ?? $tenant->phone ?? 'N/A' }} | Email: {{ $tenant->email ?? 'noc@isp.net' }}</p>
            </div>
        </div>
        <div class="text-right">
            <span class="inline-block px-2.5 py-1 bg-cyan-100 text-cyan-800 font-bold font-mono text-[10px] rounded uppercase tracking-wider mb-1">
                SECURITY &amp; AUDIT TRAIL
            </span>
            <p class="text-[11px] font-bold text-slate-800">TELEMETRY REGISTRY</p>
            <p class="text-[10px] text-slate-500 font-mono">Generated: {{ $stats['printed_at'] }}</p>
        </div>
    </div>

    {{-- KPI Summary Box --}}
    <div class="grid grid-cols-3 gap-3 mb-5">
        <div class="p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-center">
            <span class="text-[9.5px] uppercase font-bold text-slate-500 block">Total Audit Events</span>
            <span class="text-sm font-bold font-mono text-cyan-700">{{ number_format($stats['total_events']) }}</span>
        </div>
        <div class="p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-center">
            <span class="text-[9.5px] uppercase font-bold text-slate-500 block">Today's Logs</span>
            <span class="text-sm font-bold font-mono text-purple-700">{{ number_format($stats['today_events']) }}</span>
        </div>
        <div class="p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-center">
            <span class="text-[9.5px] uppercase font-bold text-slate-500 block">Security &amp; Auth Logs</span>
            <span class="text-sm font-bold font-mono text-emerald-700">{{ number_format($stats['security_events']) }}</span>
        </div>
    </div>

    {{-- Audit Log Table --}}
    <div class="space-y-4 mb-6">
        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider border-b border-slate-200 pb-1.5 flex items-center justify-between">
            <span>1. Activity &amp; System Event Stream</span>
            <span class="text-[10px] text-slate-500 font-normal">Records Listed: {{ count($logs) }}</span>
        </h3>

        <table class="w-full text-left text-xs border-collapse border border-slate-300">
            <thead>
                <tr class="bg-slate-100 text-slate-800 font-bold">
                    <th class="border border-slate-300 px-2 py-1.5 w-8 text-center">#</th>
                    <th class="border border-slate-300 px-2.5 py-1.5 w-36">Timestamp</th>
                    <th class="border border-slate-300 px-2.5 py-1.5 w-28">Actor</th>
                    <th class="border border-slate-300 px-2.5 py-1.5 w-32">Event Type</th>
                    <th class="border border-slate-300 px-2.5 py-1.5">Activity Description</th>
                    <th class="border border-slate-300 px-2.5 py-1.5 w-24 font-mono">IP Address</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $index => $l)
                    <tr class="{{ $index % 2 === 1 ? 'bg-slate-50/70' : 'bg-white' }}">
                        <td class="border border-slate-300 px-2 py-1 text-center font-mono text-slate-500 text-[10px]">
                            {{ $index + 1 }}
                        </td>
                        <td class="border border-slate-300 px-2.5 py-1 font-mono text-[10.5px] text-slate-700">
                            {{ $l->formatted_created_at }}
                        </td>
                        <td class="border border-slate-300 px-2.5 py-1 font-semibold text-slate-800 text-[11px]">
                            {{ $l->actor_name ?? 'System' }}
                            <span class="block text-[9px] text-slate-400 font-normal">({{ $l->actor_type ?? 'system' }})</span>
                        </td>
                        <td class="border border-slate-300 px-2.5 py-1 font-mono text-[10px] text-indigo-800">
                            {{ $l->event_type }}
                        </td>
                        <td class="border border-slate-300 px-2.5 py-1 text-slate-700 text-[10.5px] leading-tight">
                            {{ $l->description }}
                        </td>
                        <td class="border border-slate-300 px-2.5 py-1 font-mono text-slate-600 text-[10px]">
                            {{ $l->ip_address ?? '127.0.0.1' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="border border-slate-300 px-4 py-4 text-center text-slate-400">
                            No activity audit logs found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- System Certification & Compliance Footer --}}
    <div class="mt-8 pt-4 border-t border-slate-300 grid grid-cols-2 gap-4 text-xs">
        <div>
            <p class="font-bold text-slate-800 mb-1">System Security &amp; Compliance Statement</p>
            <p class="text-[10px] text-slate-500 leading-relaxed">
                This document is an authentic automated audit log statement produced by the SomitySoft ISP Management Platform. All log entries are cryptographically stamped with immutable system timestamps, client IP addresses, and user-agent identifiers.
            </p>
        </div>
        <div class="text-right flex flex-col justify-end">
            <div class="w-48 border-b border-slate-800 ml-auto mb-1"></div>
            <p class="font-bold text-slate-800 text-[11px]">Authorized System Auditor / NOC Lead</p>
            <p class="text-[9.5px] text-slate-400">Security Verified on {{ date('d-M-Y') }}</p>
        </div>
    </div>

</body>
</html>
