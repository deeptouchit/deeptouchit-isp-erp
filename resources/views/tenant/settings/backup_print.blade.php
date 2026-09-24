<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disaster Recovery & Backup Audit Statement - {{ $tenant->company_name ?? $tenant->name }}</title>
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
            <i class="fas fa-database text-purple-600 text-sm"></i>
            <span>System Database Backup Report (A4)</span>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-4 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-print"></i>
                <span>Print</span>
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
                <p class="text-[11px] text-slate-600">{{ $tenant->address ?? 'Internet Service Provider Operations' }}</p>
                <p class="text-[10px] text-slate-500 font-mono">Hotline: {{ $tenant->support_phone ?? $tenant->phone ?? 'N/A' }} | Email: {{ $tenant->email ?? 'noc@isp.net' }}</p>
            </div>
        </div>
        <div class="text-right">
            <span class="inline-block px-2.5 py-1 bg-purple-100 text-purple-800 font-bold font-mono text-[10px] rounded uppercase tracking-wider mb-1">
                BACKUP REPORT
            </span>
            <p class="text-[11px] font-bold text-slate-800">DATABASE BACKUP LIST</p>
            <p class="text-[10px] text-slate-500 font-mono">Date: {{ date('d-M-Y h:i A') }}</p>
        </div>
    </div>

    {{-- KPI Summary Box --}}
    <div class="grid grid-cols-4 gap-2 mb-5">
        <div class="p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-center">
            <span class="text-[9.5px] uppercase font-bold text-slate-500 block">Total Backups</span>
            <span class="text-sm font-bold font-mono text-purple-700">{{ $stats['total_snapshots'] }}</span>
        </div>
        <div class="p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-center">
            <span class="text-[9.5px] uppercase font-bold text-slate-500 block">Total Size</span>
            <span class="text-sm font-bold font-mono text-indigo-700">{{ $stats['storage_consumed'] }}</span>
        </div>
        <div class="p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-center">
            <span class="text-[9.5px] uppercase font-bold text-slate-500 block">Verified Backups</span>
            <span class="text-sm font-bold font-mono text-emerald-700">{{ $stats['verified_count'] }}</span>
        </div>
        <div class="p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-center">
            <span class="text-[9.5px] uppercase font-bold text-slate-500 block">Last Backup</span>
            <span class="text-[11px] font-bold font-mono text-cyan-700">{{ $stats['last_backup'] }}</span>
        </div>
    </div>

    {{-- Snapshots Table --}}
    <div class="space-y-4 mb-6">
        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider border-b border-slate-200 pb-1.5 flex items-center justify-between">
            <span>Database Backup Files</span>
            <span class="text-[10px] text-slate-500 font-normal">Total Files: {{ count($backups) }}</span>
        </h3>

        <table class="w-full text-left text-xs border-collapse border border-slate-300">
            <thead>
                <tr class="bg-slate-100 text-slate-800 font-bold">
                    <th class="border border-slate-300 px-2 py-1.5 w-8 text-center">#</th>
                    <th class="border border-slate-300 px-2.5 py-1.5">Backup Filename</th>
                    <th class="border border-slate-300 px-2.5 py-1.5">Type</th>
                    <th class="border border-slate-300 px-2.5 py-1.5 text-center">Source</th>
                    <th class="border border-slate-300 px-2.5 py-1.5 text-center font-mono">Size</th>
                    <th class="border border-slate-300 px-2.5 py-1.5 text-center font-mono">Tables / Rows</th>
                    <th class="border border-slate-300 px-2.5 py-1.5 text-center">Status</th>
                    <th class="border border-slate-300 px-2.5 py-1.5 font-mono">Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($backups as $idx => $b)
                    <tr class="{{ $idx % 2 === 1 ? 'bg-slate-50/70' : '' }}">
                        <td class="border border-slate-300 px-2 py-1 text-center font-mono text-[11px]">{{ $idx + 1 }}</td>
                        <td class="border border-slate-300 px-2.5 py-1 font-mono text-slate-800 text-[11px]">
                            {{ $b->filename }}
                        </td>
                        <td class="border border-slate-300 px-2.5 py-1 font-semibold text-slate-700 text-[11px]">
                            {{ $b->type_badge['label'] }}
                        </td>
                        <td class="border border-slate-300 px-2.5 py-1 text-center text-[10.5px]">
                            {{ $b->trigger_badge['label'] }}
                        </td>
                        <td class="border border-slate-300 px-2.5 py-1 text-center font-mono font-bold text-slate-800 text-[11px]">
                            {{ $b->formatted_size }}
                        </td>
                        <td class="border border-slate-300 px-2.5 py-1 text-center font-mono text-slate-600 text-[10.5px]">
                            {{ $b->tables_count }}T · {{ number_format($b->records_count) }}R
                        </td>
                        <td class="border border-slate-300 px-2.5 py-1 text-center text-[10.5px] font-bold text-emerald-700">
                            {{ strtoupper($b->status) }}
                        </td>
                        <td class="border border-slate-300 px-2.5 py-1 font-mono text-slate-500 text-[10px]">
                            {{ $b->created_at ? $b->created_at->format('d-M-Y h:i A') : 'N/A' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Disaster Recovery Standards --}}
    <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg text-xs space-y-2 mb-8">
        <h4 class="font-bold text-slate-800 text-[11px] uppercase tracking-wider">
            2. Disaster Recovery Policy &amp; RPO/RTO Objectives
        </h4>
        <div class="grid grid-cols-3 gap-3 text-[10.5px] text-slate-600">
            <div>
                <span class="font-bold text-slate-800 block">RPO (Recovery Point Objective):</span>
                <span>24 Hours (Nightly automated full database snapshots at 03:00 AM)</span>
            </div>
            <div>
                <span class="font-bold text-slate-800 block">RTO (Recovery Time Objective):</span>
                <span>Under 15 Minutes (1-Click sandbox automated schema restore)</span>
            </div>
            <div>
                <span class="font-bold text-slate-800 block">Encryption &amp; Storage:</span>
                <span>AES-256 GZIP compressed with MD5 checksum verification</span>
            </div>
        </div>
    </div>

    {{-- Official 4-Tier Signature Verification Grid --}}
    <div class="pt-8 border-t border-slate-300 grid grid-cols-4 gap-4 text-center">
        <div>
            <div class="h-10 border-b border-slate-400 mb-1"></div>
            <p class="text-[10px] font-bold text-slate-800 uppercase">System Administrator</p>
            <p class="text-[9px] text-slate-500">Database &amp; Storage</p>
        </div>
        <div>
            <div class="h-10 border-b border-slate-400 mb-1"></div>
            <p class="text-[10px] font-bold text-slate-800 uppercase">NOC Operations Lead</p>
            <p class="text-[9px] text-slate-500">Infrastructure Verification</p>
        </div>
        <div>
            <div class="h-10 border-b border-slate-400 mb-1"></div>
            <p class="text-[10px] font-bold text-slate-800 uppercase">Internal Audit</p>
            <p class="text-[9px] text-slate-500">Compliance &amp; Policy</p>
        </div>
        <div>
            <div class="h-10 border-b border-slate-400 mb-1"></div>
            <p class="text-[10px] font-bold text-slate-800 uppercase">Chief Technology Officer</p>
            <p class="text-[9px] text-slate-500">Executive Approval</p>
        </div>
    </div>

    {{-- Document Footer --}}
    <div class="mt-6 pt-3 border-t border-slate-200 text-center text-[9px] text-slate-400 flex items-center justify-between">
        <span>Confidential - For Internal ISP Compliance &amp; Governance Use Only</span>
        <span class="font-mono">Document ID: BKP-DR-{{ date('Ymd') }}-{{ $tenant->id }}</span>
    </div>

</body>
</html>
