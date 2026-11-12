@extends('tenant.layouts.app')

@section('title', __('Database Backup & Maintenance') . ' - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
    {{-- Page-specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="backupManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">
    
    {{-- 1. TOP HEADER BAR (AGENTS.md Rule 2.A: Icon + Title + Action Buttons ONLY. No subtitle/p tag) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 border border-purple-100 flex items-center justify-center text-sm shadow-2xs flex-shrink-0">
                <i class="fas fa-database"></i>
            </div>
            <h1 class="text-sm font-bold text-slate-800 tracking-tight">{{ __('Database Backup & Maintenance') }}</h1>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('tenant.settings.backup.print') }}" target="_blank" class="h-[34px] px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-print text-slate-500 text-xs"></i>
                <span>{{ __('Print List') }}</span>
            </a>
            <a href="{{ route('tenant.settings.backup.export') }}" class="h-[34px] px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-file-csv text-emerald-600 text-xs"></i>
                <span>{{ __('Export CSV') }}</span>
            </a>
            <button type="button" @click="openSnapshotModal()" class="h-[34px] px-3.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-plus text-xs"></i>
                <span>{{ __('Create Backup') }}</span>
            </button>
        </div>
    </div>

    {{-- 2. KPI SUMMARY STRIP (Strictly 6 Cards - AGENTS.md Rule 2.B) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        {{-- Card 1: Total Backups --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Total Backups') }}</span>
                <span class="text-[13px] font-bold font-mono text-indigo-700 leading-tight block truncate">
                    {{ __(':count Files', ['count' => $stats['total_snapshots']]) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-indigo-200 bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-box-archive"></i>
            </div>
        </div>

        {{-- Card 2: Backup Size --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Backup Size') }}</span>
                <span class="text-[13px] font-bold font-mono text-purple-700 leading-tight block truncate">
                    {{ $stats['storage_consumed'] }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-purple-200 bg-purple-50 text-purple-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-hard-drive"></i>
            </div>
        </div>

        {{-- Card 3: Last Backup --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Last Backup') }}</span>
                <span class="text-[13px] font-bold font-mono text-cyan-700 leading-tight block truncate">
                    {{ $stats['last_backup'] }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-cyan-200 bg-cyan-50 text-cyan-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-clock-rotate-left"></i>
            </div>
        </div>

        {{-- Card 4: Health Status --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Health Status') }}</span>
                <span class="text-[13px] font-bold font-mono text-emerald-700 leading-tight block truncate">
                    {{ $stats['health_rate'] }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-shield-check"></i>
            </div>
        </div>

        {{-- Card 5: Storage Location --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Storage') }}</span>
                <span class="text-[13px] font-bold font-mono text-blue-700 leading-tight block truncate">
                    {{ $stats['cloud_sync'] }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-blue-200 bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-server"></i>
            </div>
        </div>

        {{-- Card 6: Protected Tables --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Protected Tables') }}</span>
                <span class="text-[13px] font-bold font-mono text-amber-600 leading-tight block truncate">
                    {{ $stats['tables_count'] }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-amber-200 bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-table-list"></i>
            </div>
        </div>
    </div>

    {{-- 2.1 MAINTENANCE & SYSTEM UTILITIES BAR --}}
    <div class="bg-white p-2.5 rounded-xl border border-slate-200 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-2">
        <div class="flex items-center gap-2">
            <div class="w-6 h-6 rounded-md bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center text-xs flex-shrink-0">
                <i class="fas fa-wrench"></i>
            </div>
            <h4 class="text-xs font-bold text-slate-800">{{ __('System Maintenance & Database Optimization') }}</h4>
        </div>
        <div class="flex flex-wrap items-center gap-1.5">
            <button type="button" @click="runMaintenanceTool('optimize_db')" class="h-[34px] px-3 bg-slate-50 hover:bg-slate-100 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-bolt text-amber-500 text-xs"></i>
                <span>{{ __('Optimize Database') }}</span>
            </button>
            <button type="button" @click="runMaintenanceTool('clear_cache')" class="h-[34px] px-3 bg-slate-50 hover:bg-slate-100 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-broom text-blue-500 text-xs"></i>
                <span>{{ __('Clear Cache') }}</span>
            </button>
            <button type="button" @click="runMaintenanceTool('cleanup_logs')" class="h-[34px] px-3 bg-slate-50 hover:bg-slate-100 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-trash-can text-rose-500 text-xs"></i>
                <span>{{ __('Clean Old Logs') }}</span>
            </button>
            <button type="button" @click="runMaintenanceTool('sync_cloud')" class="h-[34px] px-3 bg-slate-50 hover:bg-slate-100 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-hard-drive text-purple-500 text-xs"></i>
                <span>{{ __('Check Storage') }}</span>
            </button>
        </div>
    </div>

    {{-- 3. SEARCH & MULTI-FILTER TOOLBAR (Customer-Standard 2-Tier Filter System - AGENTS.md Rule 2.C) --}}
    @php
        $isMoreOpen = ($triggerFilter && $triggerFilter !== 'all');
        $activeMoreCount = ($triggerFilter && $triggerFilter !== 'all') ? 1 : 0;
    @endphp
    <div class="bg-white p-2.5 rounded-xl border border-slate-200/90 shadow-2xs space-y-2.5" 
         x-data="{ showMoreFilters: {{ $isMoreOpen ? 'true' : 'false' }}, searchQuery: '{{ addslashes($search ?? '') }}' }">
        <form action="{{ route('tenant.settings.backup') }}" method="GET" class="space-y-2.5">
            
            {{-- 1st Tier: Primary Single Row (flex items-center gap-2) --}}
            <div class="flex flex-col md:flex-row items-stretch md:items-center gap-2">
                {{-- Intelligent Live Search Box with 1-Click Clear --}}
                <div class="relative flex-1 min-w-[200px]">
                    <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-xs transition-colors"
                       :class="searchQuery ? 'text-cyan-600 font-bold' : 'text-slate-400'"></i>
                    <input type="text" 
                           name="search" 
                           x-model="searchQuery" 
                           value="{{ $search }}" 
                           placeholder="{{ __('Search filename, checksum, or notes...') }}" 
                           class="h-[34px] w-full pl-8 pr-7 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    <button type="button" 
                            x-show="searchQuery && searchQuery.length > 0" 
                            @click="searchQuery = ''; $nextTick(() => $el.closest('form').submit())" 
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-0.5 rounded cursor-pointer">
                        <i class="fas fa-xmark text-xs"></i>
                    </button>
                </div>

                {{-- Primary Filter: Type --}}
                <select name="type" class="h-[34px] w-full md:w-48 bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 focus:bg-white focus:border-cyan-500 focus:outline-hidden font-medium cursor-pointer transition">
                    <option value="all" {{ $typeFilter === 'all' || !$typeFilter ? 'selected' : '' }}>{{ __('All Backup Types') }}</option>
                    <option value="full_database" {{ $typeFilter === 'full_database' ? 'selected' : '' }}>{{ __('Full Database Backup') }}</option>
                    <option value="subscribers_only" {{ $typeFilter === 'subscribers_only' ? 'selected' : '' }}>{{ __('Customers & PPPoE') }}</option>
                    <option value="mikrotik_rsc" {{ $typeFilter === 'mikrotik_rsc' ? 'selected' : '' }}>{{ __('Router Config Scripts') }}</option>
                    <option value="billing_ledger" {{ $typeFilter === 'billing_ledger' ? 'selected' : '' }}>{{ __('Billing & Ledger') }}</option>
                    <option value="system_config" {{ $typeFilter === 'system_config' ? 'selected' : '' }}>{{ __('System Configuration') }}</option>
                </select>

                {{-- Primary Filter: Status --}}
                <select name="status" class="h-[34px] w-full md:w-36 bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 focus:bg-white focus:border-cyan-500 focus:outline-hidden font-medium cursor-pointer transition">
                    <option value="all">{{ __('All Statuses') }}</option>
                    <option value="verified" {{ $statusFilter === 'verified' ? 'selected' : '' }}>{{ __('Verified (Healthy)') }}</option>
                    <option value="completed" {{ $statusFilter === 'completed' ? 'selected' : '' }}>{{ __('Completed') }}</option>
                    <option value="failed" {{ $statusFilter === 'failed' ? 'selected' : '' }}>{{ __('Failed') }}</option>
                </select>

                {{-- Pagination Selector (per_page) --}}
                <select name="per_page" class="h-[34px] w-full md:w-28 flex-shrink-0 bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 focus:bg-white focus:border-cyan-500 focus:outline-hidden font-mono cursor-pointer transition">
                    <option value="15" {{ $perPage == 15 ? 'selected' : '' }}>{{ __(':count / page', ['count' => 15]) }}</option>
                    <option value="20" {{ $perPage == 20 ? 'selected' : '' }}>{{ __(':count / page', ['count' => 20]) }}</option>
                    <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>{{ __(':count / page', ['count' => 50]) }}</option>
                    <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>{{ __(':count / page', ['count' => 100]) }}</option>
                </select>

                {{-- More Filters Toggle Accordion Button --}}
                <button type="button" 
                        @click="showMoreFilters = !showMoreFilters" 
                        class="h-[34px] px-2.5 py-0 bg-slate-50 hover:bg-slate-100 text-slate-700 font-medium text-xs rounded-lg border border-slate-200 transition flex items-center justify-center gap-1.5 cursor-pointer flex-shrink-0"
                        :class="showMoreFilters ? 'border-cyan-400 bg-cyan-50/50 text-cyan-800' : ''">
                    <i class="fas fa-sliders text-[11px] text-slate-500"></i>
                    <span>{{ __('More') }}</span>
                    @if($activeMoreCount > 0)
                        <span class="w-4 h-4 rounded-full bg-cyan-600 text-white text-[9px] font-bold flex items-center justify-center">{{ $activeMoreCount }}</span>
                    @endif
                    <i class="fas text-[10px] transition-transform duration-200" :class="showMoreFilters ? 'fa-chevron-up text-cyan-600' : 'fa-chevron-down text-slate-400'"></i>
                </button>

                {{-- Strict Filter & Reset Action Buttons Sequence (AGENTS.md Rule 2.C) --}}
                <div class="flex items-center gap-1.5 flex-shrink-0 md:ml-auto">
                    <button type="submit" class="h-[34px] bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-0 px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                        <i class="fas fa-filter text-[10px]"></i>
                        <span>{{ __('Filter') }}</span>
                    </button>
                    <a href="{{ route('tenant.settings.backup') }}" class="h-[34px] bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-0 px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer" title="{{ __('Reset All Filters') }}">
                        <i class="fas fa-rotate-left text-[10px]"></i>
                        <span>{{ __('Reset') }}</span>
                    </a>
                </div>
            </div>

            {{-- 2nd Tier: Secondary Filters Drawer --}}
            <div x-show="showMoreFilters" 
                 x-cloak 
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="pt-2 border-t border-slate-100 flex flex-wrap items-center gap-2">
                
                {{-- Trigger Source Filter --}}
                <div class="w-full sm:w-auto">
                    <label class="block text-[9.5px] uppercase font-bold text-slate-400 mb-0.5">{{ __('Trigger Source') }}</label>
                    <select name="trigger" class="h-[34px] w-full sm:w-48 bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 focus:bg-white focus:border-cyan-500 focus:outline-hidden font-medium cursor-pointer transition">
                        <option value="all" {{ $triggerFilter === 'all' || !$triggerFilter ? 'selected' : '' }}>{{ __('All Sources') }}</option>
                        <option value="manual_admin" {{ $triggerFilter === 'manual_admin' ? 'selected' : '' }}>{{ __('Manual Admin Snapshot') }}</option>
                        <option value="scheduled_cron" {{ $triggerFilter === 'scheduled_cron' ? 'selected' : '' }}>{{ __('Automated System Cron') }}</option>
                    </select>
                </div>
            </div>
        </form>
    </div>

    {{-- 4. MASTER COMPACT TABLE (<table class="saas-table"> - AGENTS.md Rule 2.D) --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-10 text-center font-semibold">#</th>
                        <th class="text-center font-semibold">{{ __('Backup File') }}</th>
                        <th class="text-center font-semibold">{{ __('Backup Type') }}</th>
                        <th class="text-center font-semibold">{{ __('File Size') }}</th>
                        <th class="text-center font-semibold">{{ __('Records') }}</th>
                        <th class="text-center font-semibold">{{ __('Status') }}</th>
                        <th class="text-center font-semibold">{{ __('Created At') }}</th>
                        <th class="w-12 text-center font-semibold no-sort">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($backups as $index => $b)
                        @php
                            $typeBadge = $b->type_badge;
                            $statusBadge = $b->status_badge;
                        @endphp
                        <tr>
                            {{-- 1. Index --}}
                            <td class="text-center font-mono text-slate-400 text-xs w-10">
                                {{ $backups->firstItem() + $index }}
                            </td>

                            {{-- 2. Backup File (text-left) --}}
                            <td class="text-left font-semibold text-slate-800">
                                <div class="flex items-center gap-2 whitespace-nowrap">
                                    <span class="w-5 h-5 rounded-md flex items-center justify-center text-[10px] border flex-shrink-0 {{ $typeBadge['class'] }}">
                                        <i class="fas {{ $typeBadge['icon'] }}"></i>
                                    </span>
                                    <span class="font-mono text-xs cursor-pointer hover:text-cyan-700 hover:underline" @click="viewSnapshotDetails({{ json_encode($b) }})">
                                        {{ $b->filename }}
                                    </span>
                                </div>
                            </td>

                            {{-- 3. Backup Type (text-left badge) --}}
                            <td class="text-left">
                                <span class="px-2 py-0.5 rounded text-[10.5px] font-semibold border inline-flex items-center gap-1 whitespace-nowrap {{ $typeBadge['class'] }}">
                                    <i class="fas {{ $typeBadge['icon'] }} text-[9px]"></i>
                                    <span>{{ __($typeBadge['label']) }}</span>
                                </span>
                            </td>

                            {{-- 4. File Size (text-right font-mono) --}}
                            <td class="text-right font-mono text-slate-800 text-xs font-bold whitespace-nowrap">
                                {{ $b->formatted_size }}
                            </td>

                            {{-- 5. Total Records (text-right font-mono) --}}
                            <td class="text-right font-mono text-slate-700 text-xs whitespace-nowrap">
                                {{ __(':count rows', ['count' => number_format($b->records_count)]) }}
                            </td>

                            {{-- 6. Status (text-center badge) --}}
                            <td class="text-center">
                                <span class="px-2 py-0.5 rounded text-[10.5px] font-semibold border inline-flex items-center gap-1 whitespace-nowrap {{ $statusBadge['class'] }}">
                                    <i class="fas {{ $statusBadge['icon'] }} text-[9px]"></i>
                                    <span>{{ __($statusBadge['label']) }}</span>
                                </span>
                            </td>

                            {{-- 7. Created At (text-center font-mono) --}}
                            <td class="text-center font-mono text-slate-500 text-[11px] whitespace-nowrap">
                                {{ $b->created_at ? $b->created_at->translatedFormat('d-M-Y h:i A') : __('N/A') }}
                            </td>

                            {{-- 8. Action 3-Dot Menu (AGENTS.md Rule 2.E) --}}
                            <td class="text-center">
                                <button type="button" 
                                        @click="toggleMenu({{ json_encode($b) }}, $event)"
                                        class="w-7 h-7 rounded-lg hover:bg-slate-100 text-slate-500 hover:text-slate-800 transition flex items-center justify-center mx-auto cursor-pointer"
                                        title="{{ __('Actions') }}">
                                    <i class="fas fa-ellipsis-v text-[10px]"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-8 text-slate-400">
                                <div class="max-w-xs mx-auto space-y-2">
                                    <div class="w-10 h-10 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto text-sm">
                                        <i class="fas fa-database"></i>
                                    </div>
                                    <p class="text-xs font-semibold text-slate-700">{{ __('No Backup Snapshots Found') }}</p>
                                    <p class="text-[11px] text-slate-400 leading-tight">{{ __('Create your first database snapshot to ensure data protection and disaster recovery readiness.') }}</p>
                                    <button type="button" @click="openSnapshotModal()" class="mt-2 h-[30px] px-3 bg-cyan-50 hover:bg-cyan-100 text-cyan-700 rounded-lg text-xs font-semibold border border-cyan-200 transition inline-flex items-center gap-1.5 cursor-pointer">
                                        <i class="fas fa-plus text-[10px]"></i>
                                        <span>{{ __('Create Backup Now') }}</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Laravel Native Compact Pagination --}}
        @if($backups->hasPages())
            <div class="px-4 py-2.5 border-t border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row items-center justify-between gap-2">
                <span class="text-xs text-slate-500 font-medium">
                    {{ __('Showing :from to :to of :total backup snapshots', ['from' => $backups->firstItem(), 'to' => $backups->lastItem(), 'total' => $backups->total()]) }}
                </span>
                <div>
                    {{ $backups->links() }}
                </div>
            </div>
        @endif
    </div>

    {{-- 5. FLOATING 3-DOT ACTION MENU (AGENTS.md Rule 2.E) --}}
    <div x-show="activeMenu !== null" 
         x-cloak 
         @click.away="activeMenu = null"
         :style="`position: fixed; top: ${menuPos.top}; bottom: ${menuPos.bottom}; right: ${menuPos.right}; left: ${menuPos.left}; z-index: 50;`"
         class="w-48 bg-white rounded-xl border border-slate-200 shadow-xl py-1 text-xs divide-y divide-slate-100 animate-in fade-in zoom-in-95 duration-100">
        
        {{-- Group 1: Diagnostics & Download --}}
        <div class="py-1">
            <button type="button" @click="viewSnapshotDetails(activeMenu); activeMenu = null" class="w-full text-left px-3 py-1.5 hover:bg-slate-50 text-slate-700 flex items-center gap-2 transition cursor-pointer">
                <i class="fas fa-circle-info text-slate-400 w-3.5"></i>
                <span>{{ __('View Details') }}</span>
            </button>
            <button type="button" @click="verifyChecksum(activeMenu); activeMenu = null" class="w-full text-left px-3 py-1.5 hover:bg-slate-50 text-slate-700 flex items-center gap-2 transition cursor-pointer">
                <i class="fas fa-shield-check text-emerald-600 w-3.5"></i>
                <span>{{ __('Check File Health') }}</span>
            </button>
            <a :href="`/admin/settings/backup/${activeMenu ? activeMenu.id : ''}/download`" @click="activeMenu = null" class="w-full text-left px-3 py-1.5 hover:bg-slate-50 text-slate-700 flex items-center gap-2 transition cursor-pointer">
                <i class="fas fa-download text-blue-600 w-3.5"></i>
                <span>{{ __('Download Backup') }}</span>
            </a>
        </div>

        {{-- Group 2: Restore & Delete --}}
        <div class="py-1">
            <button type="button" @click="restoreSnapshotAction(activeMenu); activeMenu = null" class="w-full text-left px-3 py-1.5 hover:bg-amber-50 text-amber-700 flex items-center gap-2 transition cursor-pointer">
                <i class="fas fa-rotate-left text-amber-600 w-3.5"></i>
                <span>{{ __('Restore Backup') }}</span>
            </button>
            <button type="button" @click="deleteSnapshotAction(activeMenu); activeMenu = null" class="w-full text-left px-3 py-1.5 hover:bg-rose-50 text-rose-600 flex items-center gap-2 transition cursor-pointer">
                <i class="fas fa-trash-can text-rose-500 w-3.5"></i>
                <span>{{ __('Delete Backup') }}</span>
            </button>
        </div>
    </div>

    {{-- 6. PRODUCTION-GRADE NATURAL MODALS (AGENTS.md Rule 3) --}}

    {{-- MODAL 1: CREATE NEW BACKUP MODAL --}}
    <div x-show="showSnapshotModal" 
         x-cloak 
         class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto">
        <div @click.away="showSnapshotModal = false" 
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden my-6 flex flex-col animate-in fade-in zoom-in-95 duration-150">
            
            {{-- Modal Header --}}
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between flex-shrink-0">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-plus"></i>
                    </div>
                    <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">{{ __('Create Database Snapshot') }}</h3>
                </div>
                <button type="button" @click="showSnapshotModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            {{-- Modal Body --}}
            <form id="createSnapshotForm" action="{{ route('tenant.settings.backup.snapshot') }}" method="POST" class="p-5 space-y-3.5 text-xs">
                @csrf

                <div>
                    <label class="block text-[10.5px] font-bold uppercase text-slate-600 mb-1">{{ __('Backup Type') }} <span class="text-rose-500">*</span></label>
                    <select name="backup_type" required class="h-[34px] w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 focus:bg-white focus:border-cyan-500 focus:outline-hidden font-medium cursor-pointer transition">
                        <option value="full_database">{{ __('1. Full Backup (All Database & Settings) - Recommended') }}</option>
                        <option value="subscribers_only">{{ __('2. Customers & PPPoE Accounts Only') }}</option>
                        <option value="mikrotik_rsc">{{ __('3. Router Configuration & NAS Scripts') }}</option>
                        <option value="billing_ledger">{{ __('4. Billing Ledger, Invoices & Payments') }}</option>
                        <option value="system_config">{{ __('5. System Settings, Roles & Permissions') }}</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[10.5px] font-bold uppercase text-slate-600 mb-1">{{ __('Notes / Snapshot Label (Optional)') }}</label>
                    <input type="text" name="notes" placeholder="{{ __('e.g. Before month-end maintenance, Routine backup') }}" class="h-[34px] w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                </div>

                <div class="p-3 bg-cyan-50/60 rounded-lg border border-cyan-100 flex items-start gap-2 text-cyan-900 text-[11px] leading-relaxed">
                    <i class="fas fa-shield-check text-cyan-600 mt-0.5"></i>
                    <span>{{ __('Database snapshot will be securely dumped, compressed via gzip, MD5 checksum verified, and saved to your protected server storage.') }}</span>
                </div>
            </form>

            {{-- Modal Footer --}}
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between flex-shrink-0">
                <button type="button" @click="showSnapshotModal = false" class="h-[34px] border border-slate-300 hover:bg-slate-100 text-slate-700 font-semibold text-xs px-3.5 rounded-lg transition cursor-pointer">
                    {{ __('Cancel') }}
                </button>
                <button type="submit" form="createSnapshotForm" class="h-[34px] bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs px-4 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                    <i class="fas fa-check text-xs"></i>
                    <span>{{ __('Create Snapshot Now') }}</span>
                </button>
            </div>
        </div>
    </div>

    {{-- MODAL 2: VIEW DETAILS MODAL --}}
    <div x-show="showDetailsModal" 
         x-cloak 
         class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto">
        <div @click.away="showDetailsModal = false" 
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden my-6 flex flex-col animate-in fade-in zoom-in-95 duration-150">
            
            {{-- Modal Header --}}
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between flex-shrink-0">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-circle-info"></i>
                    </div>
                    <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">{{ __('Backup Snapshot Details') }}</h3>
                </div>
                <button type="button" @click="showDetailsModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            {{-- Modal Body --}}
            <div class="p-5 space-y-3 text-xs" x-show="selectedSnapshot">
                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] uppercase font-bold text-slate-500">{{ __('Filename') }}</span>
                        <span class="font-mono text-xs text-slate-800 font-bold truncate max-w-[260px]" x-text="selectedSnapshot?.filename"></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] uppercase font-bold text-slate-500">{{ __('File Size') }}</span>
                        <span class="font-mono text-xs text-slate-800 font-bold" x-text="selectedSnapshot?.formatted_size"></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] uppercase font-bold text-slate-500">{{ __('Total Tables') }}</span>
                        <span class="font-mono text-xs text-indigo-700 font-bold" x-text="i18n.tablesCount.replace(':count', selectedSnapshot?.tables_count ?? 0)"></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] uppercase font-bold text-slate-500">{{ __('Total Records') }}</span>
                        <span class="font-mono text-xs text-emerald-700 font-bold" x-text="i18n.rowsCount.replace(':count', selectedSnapshot?.records_count ?? 0)"></span>
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] uppercase font-bold text-slate-500 mb-1">{{ __('File Checksum (MD5)') }}</label>
                    <div class="p-2.5 bg-slate-900 text-cyan-300 font-mono text-[11px] rounded-lg border border-slate-800 flex items-center justify-between">
                        <span class="truncate" x-text="selectedSnapshot?.checksum_md5 || i18n.na"></span>
                        <button type="button" @click="copyChecksum(selectedSnapshot?.checksum_md5)" class="text-slate-400 hover:text-cyan-300 transition ml-2 cursor-pointer" title="{{ __('Copy Checksum') }}">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] uppercase font-bold text-slate-500 mb-1">{{ __('Notes / Summary') }}</label>
                    <p class="text-slate-600 text-[11.5px] p-2.5 bg-slate-50 rounded-lg border border-slate-200/80 leading-relaxed" x-text="selectedSnapshot?.log_summary || i18n.defaultSummary"></p>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between flex-shrink-0">
                <a :href="`/admin/settings/backup/${selectedSnapshot ? selectedSnapshot.id : ''}/download`" class="h-[34px] bg-cyan-600 hover:bg-cyan-700 text-white text-xs font-semibold px-3.5 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                    <i class="fas fa-download text-xs"></i>
                    <span>{{ __('Download Archive') }}</span>
                </a>
                <button type="button" @click="showDetailsModal = false" class="h-[34px] border border-slate-300 hover:bg-slate-100 text-slate-700 font-semibold text-xs px-4 rounded-lg transition cursor-pointer">
                    {{ __('Close') }}
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function backupManager() {
    const i18n = {
        tablesCount: @js(__(':count Tables')),
        rowsCount: @js(__(':count Rows')),
        na: @js(__('N/A')),
        defaultSummary: @js(__('Backup created successfully.')),
        checkingIntegrityTitle: @js(__('Checking File Integrity...')),
        checkingIntegrityText: @js(__("Checking gzip archive and MD5 checksum for ':filename'...")),
        fileHealthOk: @js(__('File Health OK')),
        statusLabel: @js(__('Status')),
        healthyIntact: @js(__('Healthy & Intact')),
        tablesLabel: @js(__('Tables')),
        recordsLabel: @js(__('Records')),
        checksumLabel: @js(__('Checksum')),
        checkFailed: @js(__('Check Failed')),
        checkFailedDefault: @js(__('Backup file check failed.')),
        errorTitle: @js(__('Error')),
        unableCheck: @js(__('Unable to check backup file.')),
        checksumCopied: @js(__('Checksum copied to clipboard!')),
        restoreTitle: @js(__('Restore Database?')),
        restoreHtml: @js(__("Are you sure you want to test and restore from backup ':filename'?")),
        yesRestore: @js(__('Yes, Restore')),
        deleteTitle: @js(__('Delete Backup?')),
        deleteText: @js(__("Are you sure you want to delete ':filename'? This cannot be undone.")),
        yesDelete: @js(__('Yes, Delete')),
        cancelButton: @js(__('Cancel')),
        yesRunNow: @js(__('Yes, Run Now')),
        runningTask: @js(__('Running Task...')),
        completedTitle: @js(__('Completed')),
        failedTask: @js(__('Failed to complete task.')),
    };

    const maintenanceConfirmations = {
        optimize_db: {
            title: @js(__('Optimize Database')),
            message: @js(__('Clean and optimize database tables for enhanced performance?')),
        },
        clear_cache: {
            title: @js(__('Clear System Cache')),
            message: @js(__('Clear all compiled application and blade template cache?')),
        },
        cleanup_logs: {
            title: @js(__('Clean Old Logs')),
            message: @js(__('Delete system activity audit logs older than 90 days to free storage?')),
        },
        sync_cloud: {
            title: @js(__('Check Storage')),
            message: @js(__('Check available backup filesystem space on the server?')),
        },
    };

    return {
        i18n,
        activeMenu: null,
        menuPos: { top: '0px', bottom: 'auto', right: '0px', left: 'auto' },

        showSnapshotModal: false,
        showDetailsModal: false,
        selectedSnapshot: null,

        toggleMenu(item, event) {
            if (this.activeMenu?.id === item.id) {
                this.activeMenu = null;
                return;
            }
            this.activeMenu = item;
            const rect = event.currentTarget.getBoundingClientRect();
            const dropdownHeight = 180;
            const right = Math.max(10, window.innerWidth - rect.right);
            let top = Math.round(rect.bottom) + 2;
            let bottom = 'auto';

            if (top + dropdownHeight > window.innerHeight) {
                top = 'auto';
                bottom = Math.max(10, window.innerHeight - Math.round(rect.top) + 2) + 'px';
            } else {
                top = `${top}px`;
            }

            this.menuPos = {
                top: top,
                bottom: bottom,
                right: `${right}px`,
                left: 'auto'
            };
        },

        openSnapshotModal() {
            this.showSnapshotModal = true;
        },

        viewSnapshotDetails(snapshot) {
            this.selectedSnapshot = snapshot;
            this.showDetailsModal = true;
        },

        async verifyChecksum(snapshot) {
            Swal.fire({
                title: i18n.checkingIntegrityTitle,
                text: i18n.checkingIntegrityText.replace(':filename', snapshot.filename),
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            try {
                const response = await fetch(`/admin/settings/backup/${snapshot.id}/verify`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: i18n.fileHealthOk,
                        html: `<div class="text-left text-xs space-y-1.5 p-2 bg-slate-50 rounded border border-slate-200">
                                <p><strong>${i18n.statusLabel}:</strong> <span class="text-emerald-700 font-bold">${i18n.healthyIntact}</span></p>
                                <p><strong>${i18n.tablesLabel}:</strong> ${i18n.tablesCount.replace(':count', data.tables)}</p>
                                <p><strong>${i18n.recordsLabel}:</strong> ${i18n.rowsCount.replace(':count', data.records)}</p>
                                <p><strong>${i18n.checksumLabel}:</strong> <code class="bg-slate-200 px-1 py-0.5 rounded font-mono text-[10.5px]">${data.checksum}</code></p>
                               </div>`,
                        confirmButtonColor: '#0891b2'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: i18n.checkFailed,
                        text: data.message || i18n.checkFailedDefault,
                        confirmButtonColor: '#0891b2'
                    });
                }
            } catch (err) {
                Swal.fire({
                    icon: 'error',
                    title: i18n.errorTitle,
                    text: i18n.unableCheck,
                    confirmButtonColor: '#0891b2'
                });
            }
        },

        copyChecksum(checksum) {
            if (!checksum) return;
            navigator.clipboard.writeText(checksum).then(() => {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: i18n.checksumCopied,
                    showConfirmButton: false,
                    timer: 2000
                });
            });
        },

        restoreSnapshotAction(snapshot) {
            Swal.fire({
                title: i18n.restoreTitle,
                html: i18n.restoreHtml.replace(':filename', `<strong>${snapshot.filename}</strong>`),
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f59e0b',
                cancelButtonColor: '#64748b',
                confirmButtonText: i18n.yesRestore,
                cancelButtonText: i18n.cancelButton
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = `/admin/settings/backup/${snapshot.id}/restore`;
                    form.innerHTML = `@csrf`;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        },

        deleteSnapshotAction(snapshot) {
            Swal.fire({
                title: i18n.deleteTitle,
                text: i18n.deleteText.replace(':filename', snapshot.filename),
                icon: 'error',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                cancelButtonColor: '#64748b',
                confirmButtonText: i18n.yesDelete,
                cancelButtonText: i18n.cancelButton
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = `/admin/settings/backup/${snapshot.id}`;
                    form.innerHTML = `@csrf <input type="hidden" name="_method" value="DELETE">`;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        },

        runMaintenanceTool(action, title = null, message = null) {
            const config = maintenanceConfirmations[action] || {};
            Swal.fire({
                title: title || config.title || '',
                text: message || config.message || '',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0891b2',
                cancelButtonColor: '#64748b',
                confirmButtonText: i18n.yesRunNow,
                cancelButtonText: i18n.cancelButton
            }).then(async (result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: i18n.runningTask,
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    try {
                        const response = await fetch(`/admin/settings/backup/maintenance/${action}`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            }
                        });
                        const data = await response.json();
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: i18n.completedTitle,
                                text: data.message,
                                confirmButtonColor: '#0891b2'
                            });
                        }
                    } catch (e) {
                        Swal.fire({
                            icon: 'error',
                            title: i18n.errorTitle,
                            text: i18n.failedTask,
                            confirmButtonColor: '#0891b2'
                        });
                    }
                }
            });
        }
    };
}
</script>
@endpush
