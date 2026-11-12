<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Disaster Recovery & Backup Audit Statement') }} - {{ $tenant->company_name ?? $tenant->name }}</title>
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
            <span>{{ __('System Database Backup Report (A4)') }}</span>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-4 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-print"></i>
                <span>{{ __('Print') }}</span>
            </button>
            <button onclick="window.close()" class="px-3 py-1.5 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg text-xs font-semibold transition cursor-pointer">
                <span>{{ __('Close') }}</span>
            </button>
        </div>
    </div>

    {{-- Official Header & Letterhead --}}
    <div class="border-b-2 border-slate-900 pb-4 mb-5 flex items-start justify-between">
        <div class="flex items-center gap-3">
            @if($tenant->logo)
                <img src="{{ asset('storage/' . $tenant->logo) }}" alt="{{ __('Logo') }}" class="h-12 w-auto object-contain">
            @else
                <div class="w-12 h-12 rounded-lg bg-slate-900 text-white font-bold flex items-center justify-center text-lg">
                    {{ substr($tenant->company_name ?? $tenant->name, 0, 2) }}
                </div>
            @endif
            <div>
                <h1 class="text-lg font-bold text-slate-900 tracking-tight uppercase">{{ $tenant->company_name ?? $tenant->name }}</h1>
                <p class="text-[11px] text-slate-600">{{ $tenant->address ?? __('Internet Service Provider Operations') }}</p>
                <p class="text-[10px] text-slate-500 font-mono">{{ __('Hotline:') }} {{ $tenant->support_phone ?? $tenant->phone ?? __('N/A') }} | {{ __('Email:') }} {{ $tenant->email ?? 'noc@isp.net' }}</p>
            </div>
        </div>
        <div class="text-right">
            <span class="inline-block px-2.5 py-1 bg-purple-100 text-purple-800 font-bold font-mono text-[10px] rounded uppercase tracking-wider mb-1">
                {{ __('BACKUP REPORT') }}
            </span>
            <p class="text-[11px] font-bold text-slate-800">{{ __('DATABASE BACKUP LIST') }}</p>
            <p class="text-[10px] text-slate-500 font-mono">{{ __('Date:') }} {{ now()->translatedFormat('d-M-Y h:i A') }}</p>
        </div>
    </div>

    {{-- KPI Summary Box --}}
    <div class="grid grid-cols-4 gap-2 mb-5">
        <div class="p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-center">
            <span class="text-[9.5px] uppercase font-bold text-slate-500 block">{{ __('Total Backups') }}</span>
            <span class="text-sm font-bold font-mono text-purple-700">{{ $stats['total_snapshots'] }}</span>
        </div>
        <div class="p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-center">
            <span class="text-[9.5px] uppercase font-bold text-slate-500 block">{{ __('Total Size') }}</span>
            <span class="text-sm font-bold font-mono text-indigo-700">{{ $stats['storage_consumed'] }}</span>
        </div>
        <div class="p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-center">
            <span class="text-[9.5px] uppercase font-bold text-slate-500 block">{{ __('Verified Backups') }}</span>
            <span class="text-sm font-bold font-mono text-emerald-700">{{ $stats['verified_count'] }}</span>
        </div>
        <div class="p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-center">
            <span class="text-[9.5px] uppercase font-bold text-slate-500 block">{{ __('Last Backup') }}</span>
            <span class="text-[11px] font-bold font-mono text-cyan-700">{{ $stats['last_backup'] }}</span>
        </div>
    </div>

    {{-- Snapshots Table --}}
    <div class="space-y-4 mb-6">
        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider border-b border-slate-200 pb-1.5 flex items-center justify-between">
            <span>{{ __('Database Backup Files') }}</span>
            <span class="text-[10px] text-slate-500 font-normal">{{ __('Total Files: :count', ['count' => count($backups)]) }}</span>
        </h3>

        <table class="w-full text-left text-xs border-collapse border border-slate-300">
            <thead>
                <tr class="bg-slate-100 text-slate-800 font-bold">
                    <th class="border border-slate-300 px-2 py-1.5 w-8 text-center">#</th>
                    <th class="border border-slate-300 px-2.5 py-1.5">{{ __('Backup Filename') }}</th>
                    <th class="border border-slate-300 px-2.5 py-1.5">{{ __('Type') }}</th>
                    <th class="border border-slate-300 px-2.5 py-1.5 text-center">{{ __('Source') }}</th>
                    <th class="border border-slate-300 px-2.5 py-1.5 text-center font-mono">{{ __('Size') }}</th>
                    <th class="border border-slate-300 px-2.5 py-1.5 text-center font-mono">{{ __('Tables / Rows') }}</th>
                    <th class="border border-slate-300 px-2.5 py-1.5 text-center">{{ __('Status') }}</th>
                    <th class="border border-slate-300 px-2.5 py-1.5 font-mono">{{ __('Date') }}</th>
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
                            {{ __($b->type_badge['label']) }}
                        </td>
                        <td class="border border-slate-300 px-2.5 py-1 text-center text-[10.5px]">
                            {{ __($b->trigger_badge['label']) }}
                        </td>
                        <td class="border border-slate-300 px-2.5 py-1 text-center font-mono font-bold text-slate-800 text-[11px]">
                            {{ $b->formatted_size }}
                        </td>
                        <td class="border border-slate-300 px-2.5 py-1 text-center font-mono text-slate-600 text-[10.5px]">
                            {{ __(':tablesT · :recordsR', ['tables' => $b->tables_count, 'records' => number_format($b->records_count)]) }}
                        </td>
                        <td class="border border-slate-300 px-2.5 py-1 text-center text-[10.5px] font-bold text-emerald-700">
                            {{ __($b->status_badge['label']) }}
                        </td>
                        <td class="border border-slate-300 px-2.5 py-1 font-mono text-slate-500 text-[10px]">
                            {{ $b->created_at ? $b->created_at->translatedFormat('d-M-Y h:i A') : __('N/A') }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Disaster Recovery Standards --}}
    <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg text-xs space-y-2 mb-8">
        <h4 class="font-bold text-slate-800 text-[11px] uppercase tracking-wider">
            {{ __('2. Disaster Recovery Policy & RPO/RTO Objectives') }}
        </h4>
        <div class="grid grid-cols-3 gap-3 text-[10.5px] text-slate-600">
            <div>
                <span class="font-bold text-slate-800 block">{{ __('RPO (Recovery Point Objective):') }}</span>
                <span>{{ __('24 Hours (Nightly automated full database snapshots at 03:00 AM)') }}</span>
            </div>
            <div>
                <span class="font-bold text-slate-800 block">{{ __('RTO (Recovery Time Objective):') }}</span>
                <span>{{ __('Under 15 Minutes (1-Click sandbox automated schema restore)') }}</span>
            </div>
            <div>
                <span class="font-bold text-slate-800 block">{{ __('Encryption & Storage:') }}</span>
                <span>{{ __('AES-256 GZIP compressed with MD5 checksum verification') }}</span>
            </div>
        </div>
    </div>

    {{-- Official 4-Tier Signature Verification Grid --}}
    <div class="pt-8 border-t border-slate-300 grid grid-cols-4 gap-4 text-center">
        <div>
            <div class="h-10 border-b border-slate-400 mb-1"></div>
            <p class="text-[10px] font-bold text-slate-800 uppercase">{{ __('System Administrator') }}</p>
            <p class="text-[9px] text-slate-500">{{ __('Database & Storage') }}</p>
        </div>
        <div>
            <div class="h-10 border-b border-slate-400 mb-1"></div>
            <p class="text-[10px] font-bold text-slate-800 uppercase">{{ __('NOC Operations Lead') }}</p>
            <p class="text-[9px] text-slate-500">{{ __('Infrastructure Verification') }}</p>
        </div>
        <div>
            <div class="h-10 border-b border-slate-400 mb-1"></div>
            <p class="text-[10px] font-bold text-slate-800 uppercase">{{ __('Internal Audit') }}</p>
            <p class="text-[9px] text-slate-500">{{ __('Compliance & Policy') }}</p>
        </div>
        <div>
            <div class="h-10 border-b border-slate-400 mb-1"></div>
            <p class="text-[10px] font-bold text-slate-800 uppercase">{{ __('Chief Technology Officer') }}</p>
            <p class="text-[9px] text-slate-500">{{ __('Executive Approval') }}</p>
        </div>
    </div>

    {{-- Document Footer --}}
    <div class="mt-6 pt-3 border-t border-slate-200 text-center text-[9px] text-slate-400 flex items-center justify-between">
        <span>{{ __('Confidential - For Internal ISP Compliance & Governance Use Only') }}</span>
        <span class="font-mono">{{ __('Document ID:') }} BKP-DR-{{ date('Ymd') }}-{{ $tenant->id }}</span>
    </div>

</body>
</html>
