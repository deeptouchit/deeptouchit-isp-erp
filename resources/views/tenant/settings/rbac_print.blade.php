<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RBAC & Permissions Audit Statement - {{ $tenant->company_name ?? $tenant->name }}</title>
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
            <span>Role-Based Access Control (RBAC) Official Audit Statement (A4)</span>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-4 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-print"></i>
                <span>Print Document</span>
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
            <span class="inline-block px-2.5 py-1 bg-purple-100 text-purple-800 font-bold font-mono text-[10px] rounded uppercase tracking-wider mb-1">
                SECURITY COMPLIANCE AUDIT
            </span>
            <p class="text-[11px] font-bold text-slate-800">ROLE &amp; PERMISSION MATRIX</p>
            <p class="text-[10px] text-slate-500 font-mono">Date: {{ date('d-M-Y h:i A') }}</p>
        </div>
    </div>

    {{-- KPI Summary Box --}}
    <div class="grid grid-cols-4 gap-2 mb-5">
        <div class="p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-center">
            <span class="text-[9.5px] uppercase font-bold text-slate-500 block">Total Defined Roles</span>
            <span class="text-sm font-bold font-mono text-purple-700">{{ $stats['total_roles'] }}</span>
        </div>
        <div class="p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-center">
            <span class="text-[9.5px] uppercase font-bold text-slate-500 block">System Built-in</span>
            <span class="text-sm font-bold font-mono text-indigo-700">{{ $stats['system_roles'] }}</span>
        </div>
        <div class="p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-center">
            <span class="text-[9.5px] uppercase font-bold text-slate-500 block">Custom Roles</span>
            <span class="text-sm font-bold font-mono text-cyan-700">{{ $stats['custom_roles'] }}</span>
        </div>
        <div class="p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-center">
            <span class="text-[9.5px] uppercase font-bold text-slate-500 block">Assigned Staff</span>
            <span class="text-sm font-bold font-mono text-emerald-700">{{ $stats['assigned_staff'] }}</span>
        </div>
    </div>

    {{-- Roles & Matrix Breakdown --}}
    <div class="space-y-4 mb-6">
        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider border-b border-slate-200 pb-1.5 flex items-center justify-between">
            <span>1. Roles &amp; Capability Allocation Summary</span>
            <span class="text-[10px] text-slate-500 font-normal">Total Defined Access Profiles: {{ count($roles) }}</span>
        </h3>

        <table class="w-full text-left text-xs border-collapse border border-slate-300">
            <thead>
                <tr class="bg-slate-100 text-slate-800 font-bold">
                    <th class="border border-slate-300 px-2 py-1.5 w-8 text-center">#</th>
                    <th class="border border-slate-300 px-2.5 py-1.5">Role Title</th>
                    <th class="border border-slate-300 px-2.5 py-1.5 font-mono">System Key</th>
                    <th class="border border-slate-300 px-2.5 py-1.5 text-center">Type</th>
                    <th class="border border-slate-300 px-2.5 py-1.5 text-center">Capabilities</th>
                    <th class="border border-slate-300 px-2.5 py-1.5 text-center">Staff Count</th>
                    <th class="border border-slate-300 px-2.5 py-1.5 text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($roles as $idx => $r)
                    <tr class="{{ $idx % 2 === 1 ? 'bg-slate-50/70' : '' }}">
                        <td class="border border-slate-300 px-2 py-1 text-center font-mono text-[11px]">{{ $idx + 1 }}</td>
                        <td class="border border-slate-300 px-2.5 py-1 font-semibold text-slate-800">
                            {{ $r->display_name }}
                        </td>
                        <td class="border border-slate-300 px-2.5 py-1 font-mono text-slate-600 text-[11px]">
                            {{ $r->name }}
                        </td>
                        <td class="border border-slate-300 px-2.5 py-1 text-center text-[10.5px]">
                            {{ $r->is_system ? 'System Preset' : 'Custom' }}
                        </td>
                        <td class="border border-slate-300 px-2.5 py-1 text-center font-mono font-bold text-emerald-700 text-[11px]">
                            {{ count($r->permissions ?? []) }} Perms
                        </td>
                        <td class="border border-slate-300 px-2.5 py-1 text-center font-mono text-[11px]">
                            {{ $r->staff_count }}
                        </td>
                        <td class="border border-slate-300 px-2.5 py-1 text-center text-[10.5px] font-bold {{ $r->status === 'active' ? 'text-emerald-700' : 'text-rose-600' }}">
                            {{ strtoupper($r->status) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Granular Matrix Details --}}
    <div class="space-y-3 mb-8">
        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider border-b border-slate-200 pb-1.5">
            2. Granular Permission Catalog &amp; Category Coverage
        </h3>

        <div class="grid grid-cols-2 gap-3 text-xs">
            @foreach($permissionCatalog as $groupTitle => $group)
                <div class="border border-slate-200 rounded p-2.5 bg-slate-50/50">
                    <h4 class="font-bold text-slate-800 text-[11px] mb-1 flex items-center gap-1.5">
                        <i class="fas {{ $group['icon'] }} text-purple-600 text-[10px]"></i>
                        <span>{{ $groupTitle }}</span>
                    </h4>
                    <ul class="space-y-0.5 text-[10px] text-slate-600">
                        @foreach($group['permissions'] as $pkey => $plabel)
                            <li class="flex items-center gap-1 truncate">
                                <span class="text-emerald-600 font-bold">•</span>
                                <span class="font-mono text-slate-500">[{{ $pkey }}]</span>
                                <span class="truncate">{{ $plabel }}</span>
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
            <p class="text-[10px] font-bold text-slate-800 uppercase">IT Security Officer</p>
            <p class="text-[9px] text-slate-500">Access Control &amp; 2FA</p>
        </div>
        <div>
            <div class="h-10 border-b border-slate-400 mb-1"></div>
            <p class="text-[10px] font-bold text-slate-800 uppercase">HR &amp; Administration</p>
            <p class="text-[9px] text-slate-500">Personnel Authorization</p>
        </div>
        <div>
            <div class="h-10 border-b border-slate-400 mb-1"></div>
            <p class="text-[10px] font-bold text-slate-800 uppercase">Internal Audit</p>
            <p class="text-[9px] text-slate-500">Compliance &amp; Policy</p>
        </div>
        <div>
            <div class="h-10 border-b border-slate-400 mb-1"></div>
            <p class="text-[10px] font-bold text-slate-800 uppercase">Managing Director</p>
            <p class="text-[9px] text-slate-500">Executive Approval</p>
        </div>
    </div>

    {{-- Document Footer --}}
    <div class="mt-6 pt-3 border-t border-slate-200 text-center text-[9px] text-slate-400 flex items-center justify-between">
        <span>Confidential - For Internal ISP Compliance &amp; Governance Use Only</span>
        <span class="font-mono">Document ID: RBAC-SEC-{{ date('Ymd') }}-{{ $tenant->id }}</span>
    </div>

</body>
</html>
