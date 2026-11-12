<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('RBAC & Permissions Audit Statement') }} - {{ $tenant->company_name ?? $tenant->name }}</title>
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
            <i class="fas fa-shield-halved text-purple-600 text-sm"></i>
            <span>{{ __('Role-Based Access Control (RBAC) Official Audit Statement (A4)') }}</span>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-4 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-print"></i>
                <span>{{ __('Print Document') }}</span>
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
                <p class="text-[11px] text-slate-600">{{ $tenant->address ?? __('Corporate Internet Service Provider Operations') }}</p>
                <p class="text-[10px] text-slate-500 font-mono">{{ __('Hotline:') }} {{ $tenant->support_phone ?? $tenant->phone ?? __('N/A') }} | {{ __('Email:') }} {{ $tenant->email ?? 'noc@isp.net' }}</p>
            </div>
        </div>
        <div class="text-right">
            <span class="inline-block px-2.5 py-1 bg-purple-100 text-purple-800 font-bold font-mono text-[10px] rounded uppercase tracking-wider mb-1">
                {{ __('SECURITY COMPLIANCE AUDIT') }}
            </span>
            <p class="text-[11px] font-bold text-slate-800">{{ __('ROLE & PERMISSION MATRIX') }}</p>
            <p class="text-[10px] text-slate-500 font-mono">{{ __('Date:') }} {{ now()->translatedFormat('d-M-Y h:i A') }}</p>
        </div>
    </div>

    {{-- KPI Summary Box --}}
    <div class="grid grid-cols-4 gap-2 mb-5">
        <div class="p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-center">
            <span class="text-[9.5px] uppercase font-bold text-slate-500 block">{{ __('Total Defined Roles') }}</span>
            <span class="text-sm font-bold font-mono text-purple-700">{{ $stats['total_roles'] }}</span>
        </div>
        <div class="p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-center">
            <span class="text-[9.5px] uppercase font-bold text-slate-500 block">{{ __('System Built-in') }}</span>
            <span class="text-sm font-bold font-mono text-indigo-700">{{ $stats['system_roles'] }}</span>
        </div>
        <div class="p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-center">
            <span class="text-[9.5px] uppercase font-bold text-slate-500 block">{{ __('Custom Roles') }}</span>
            <span class="text-sm font-bold font-mono text-cyan-700">{{ $stats['custom_roles'] }}</span>
        </div>
        <div class="p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-center">
            <span class="text-[9.5px] uppercase font-bold text-slate-500 block">{{ __('Assigned Staff') }}</span>
            <span class="text-sm font-bold font-mono text-emerald-700">{{ $stats['assigned_staff'] }}</span>
        </div>
    </div>

    {{-- Roles & Matrix Breakdown --}}
    <div class="space-y-4 mb-6">
        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider border-b border-slate-200 pb-1.5 flex items-center justify-between">
            <span>{{ __('1. Roles & Capability Allocation Summary') }}</span>
            <span class="text-[10px] text-slate-500 font-normal">{{ __('Total Defined Access Profiles: :count', ['count' => count($roles)]) }}</span>
        </h3>

        <table class="w-full text-left text-xs border-collapse border border-slate-300">
            <thead>
                <tr class="bg-slate-100 text-slate-800 font-bold">
                    <th class="border border-slate-300 px-2 py-1.5 w-8 text-center">#</th>
                    <th class="border border-slate-300 px-2.5 py-1.5">{{ __('Role Title') }}</th>
                    <th class="border border-slate-300 px-2.5 py-1.5 font-mono">{{ __('System Key') }}</th>
                    <th class="border border-slate-300 px-2.5 py-1.5 text-center">{{ __('Type') }}</th>
                    <th class="border border-slate-300 px-2.5 py-1.5 text-center">{{ __('Capabilities') }}</th>
                    <th class="border border-slate-300 px-2.5 py-1.5 text-center">{{ __('Staff Count') }}</th>
                    <th class="border border-slate-300 px-2.5 py-1.5 text-center">{{ __('Status') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($roles as $idx => $r)
                    <tr class="{{ $idx % 2 === 1 ? 'bg-slate-50/70' : '' }}">
                        <td class="border border-slate-300 px-2 py-1 text-center font-mono text-[11px]">{{ $idx + 1 }}</td>
                        <td class="border border-slate-300 px-2.5 py-1 font-semibold text-slate-800">
                            {{ __($r->badge['label']) }}
                        </td>
                        <td class="border border-slate-300 px-2.5 py-1 font-mono text-slate-600 text-[11px]">
                            {{ $r->name }}
                        </td>
                        <td class="border border-slate-300 px-2.5 py-1 text-center text-[10.5px]">
                            {{ $r->is_system ? __('System Preset') : __('Custom') }}
                        </td>
                        <td class="border border-slate-300 px-2.5 py-1 text-center font-mono font-bold text-emerald-700 text-[11px]">
                            {{ __(':count Perms', ['count' => count($r->permissions ?? [])]) }}
                        </td>
                        <td class="border border-slate-300 px-2.5 py-1 text-center font-mono text-[11px]">
                            {{ $r->staff_count }}
                        </td>
                        <td class="border border-slate-300 px-2.5 py-1 text-center text-[10.5px] font-bold {{ $r->status === 'active' ? 'text-emerald-700' : 'text-rose-600' }}">
                            {{ $r->status === 'active' ? __('ACTIVE') : ($r->status === 'disabled' ? __('DISABLED') : strtoupper($r->status)) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Granular Matrix Details --}}
    <div class="space-y-3 mb-8">
        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider border-b border-slate-200 pb-1.5">
            {{ __('2. Granular Permission Catalog & Category Coverage') }}
        </h3>

        <div class="grid grid-cols-2 gap-3 text-xs">
            @foreach($permissionCatalog as $groupTitle => $group)
                <div class="border border-slate-200 rounded p-2.5 bg-slate-50/50">
                    <h4 class="font-bold text-slate-800 text-[11px] mb-1 flex items-center gap-1.5">
                        <i class="fas {{ $group['icon'] }} text-purple-600 text-[10px]"></i>
                        <span>{{ __($groupTitle) }}</span>
                    </h4>
                    <ul class="space-y-0.5 text-[10px] text-slate-600">
                        @foreach($group['permissions'] as $pkey => $plabel)
                            <li class="flex items-center gap-1 truncate">
                                <span class="text-emerald-600 font-bold">•</span>
                                <span class="font-mono text-slate-500">[{{ $pkey }}]</span>
                                <span class="truncate">{{ __($plabel) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Official 4-Tier Signature Verification Grid --}}
    <div class="pt-8 border-t border-slate-300 grid grid-cols-4 gap-4 text-center">
        <div>
            <div class="h-10 border-b border-slate-400 mb-1"></div>
            <p class="text-[10px] font-bold text-slate-800 uppercase">{{ __('IT Security Officer') }}</p>
            <p class="text-[9px] text-slate-500">{{ __('Access Control & 2FA') }}</p>
        </div>
        <div>
            <div class="h-10 border-b border-slate-400 mb-1"></div>
            <p class="text-[10px] font-bold text-slate-800 uppercase">{{ __('HR & Administration') }}</p>
            <p class="text-[9px] text-slate-500">{{ __('Personnel Authorization') }}</p>
        </div>
        <div>
            <div class="h-10 border-b border-slate-400 mb-1"></div>
            <p class="text-[10px] font-bold text-slate-800 uppercase">{{ __('Internal Audit') }}</p>
            <p class="text-[9px] text-slate-500">{{ __('Compliance & Policy') }}</p>
        </div>
        <div>
            <div class="h-10 border-b border-slate-400 mb-1"></div>
            <p class="text-[10px] font-bold text-slate-800 uppercase">{{ __('Managing Director') }}</p>
            <p class="text-[9px] text-slate-500">{{ __('Executive Approval') }}</p>
        </div>
    </div>

    {{-- Document Footer --}}
    <div class="mt-6 pt-3 border-t border-slate-200 text-center text-[9px] text-slate-400 flex items-center justify-between">
        <span>{{ __('Confidential - For Internal ISP Compliance & Governance Use Only') }}</span>
        <span class="font-mono">{{ __('Document ID:') }} RBAC-SEC-{{ date('Ymd') }}-{{ $tenant->id }}</span>
    </div>

</body>
</html>
