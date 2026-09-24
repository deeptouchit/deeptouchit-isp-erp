@extends('tenant.layouts.app')

@section('title', 'Database Backup & Maintenance - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
    {{-- Page-specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="backupManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">
    
    {{-- 1. TOP HEADER BAR (AGENTS.md Rule 2.A) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 border border-purple-100 flex items-center justify-center text-sm shadow-2xs flex-shrink-0">
                <i class="fas fa-database"></i>
            </div>
            <h1 class="text-sm font-bold text-slate-800 tracking-tight">Database Backup &amp; Maintenance</h1>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('tenant.settings.backup.print') }}" target="_blank" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-print text-slate-500 text-xs"></i>
                <span>Print List</span>
            </a>
            <a href="{{ route('tenant.settings.backup.export') }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-file-csv text-slate-500 text-xs"></i>
                <span>Export CSV</span>
            </a>
            <button type="button" @click="openSnapshotModal()" class="px-3.5 py-1.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-plus text-xs"></i>
                <span>Create Backup</span>
            </button>
        </div>
    </div>

    {{-- 2. KPI SUMMARY STRIP (Strictly 6 Cards - AGENTS.md Rule 2.B) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        {{-- Card 1: Total Backups --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Total Backups</span>
                <span class="text-[13px] font-bold font-mono text-indigo-700 leading-tight block">
                    {{ $stats['total_snapshots'] }} Files
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-indigo-200 bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-box-archive"></i>
            </div>
        </div>

        {{-- Card 2: Backup Size --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Backup Size</span>
                <span class="text-[13px] font-bold font-mono text-purple-700 leading-tight block">
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
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Last Backup</span>
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
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Status</span>
                <span class="text-[13px] font-bold font-mono text-emerald-700 leading-tight block">
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
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Storage</span>
                <span class="text-[13px] font-bold font-mono text-blue-700 leading-tight block truncate">
                    {{ $stats['cloud_sync'] }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-blue-200 bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-server"></i>
            </div>
        </div>

        {{-- Card 6: Total Tables --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Tables</span>
                <span class="text-[13px] font-bold font-mono text-amber-600 leading-tight block truncate">
                    {{ $stats['tables_count'] }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-amber-200 bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-table-list"></i>
            </div>
        </div>
    </div>

    {{-- 2.1 MAINTENANCE & UTILITIES ACTION BAR --}}
    <div class="bg-white p-2.5 rounded-xl border border-slate-200 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-2">
        <div class="flex items-center gap-2">
            <div class="w-6 h-6 rounded-md bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center text-xs flex-shrink-0">
                <i class="fas fa-wrench"></i>
            </div>
            <h4 class="text-xs font-bold text-slate-800">System Maintenance</h4>
        </div>
        <div class="flex flex-wrap items-center gap-1.5">
            <button type="button" @click="runMaintenanceTool('optimize_db', 'Optimize Database', 'Clean and optimize database tables for faster speed?')" class="px-2.5 py-1.5 bg-slate-50 hover:bg-slate-100 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200 transition flex items-center gap-1 cursor-pointer">
                <i class="fas fa-bolt text-amber-500 text-[10px]"></i>
                <span>Optimize Database</span>
            </button>
            <button type="button" @click="runMaintenanceTool('clear_cache', 'Clear System Cache', 'Clear all temporary system and view cache?')" class="px-2.5 py-1.5 bg-slate-50 hover:bg-slate-100 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200 transition flex items-center gap-1 cursor-pointer">
                <i class="fas fa-broom text-blue-500 text-[10px]"></i>
                <span>Clear Cache</span>
            </button>
            <button type="button" @click="runMaintenanceTool('cleanup_logs', 'Clean Old Logs', 'Delete activity logs older than 90 days to free space?')" class="px-2.5 py-1.5 bg-slate-50 hover:bg-slate-100 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200 transition flex items-center gap-1 cursor-pointer">
                <i class="fas fa-trash-can text-rose-500 text-[10px]"></i>
                <span>Clean Old Logs</span>
            </button>
            <button type="button" @click="runMaintenanceTool('sync_cloud', 'Check Storage', 'Check available backup storage space on server?')" class="px-2.5 py-1.5 bg-slate-50 hover:bg-slate-100 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200 transition flex items-center gap-1 cursor-pointer">
                <i class="fas fa-hard-drive text-purple-500 text-[10px]"></i>
                <span>Check Storage</span>
            </button>
        </div>
    </div>

    {{-- 3. SEARCH & MULTI-FILTER TOOLBAR (AGENTS.md Rule 2.C) --}}
    <div class="bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
        <form action="{{ route('tenant.settings.backup') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-6 gap-2.5 items-center">
            {{-- Search Box --}}
            <div class="relative md:col-span-2">
                <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" name="search" value="{{ $search }}" placeholder="Search filename or notes..." class="w-full pl-8 pr-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-cyan-500 focus:outline-none">
            </div>

            {{-- Type Filter --}}
            <div>
                <select name="type" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none">
                    <option value="all" {{ $typeFilter === 'all' || !$typeFilter ? 'selected' : '' }}>All Types</option>
                    <option value="full_database" {{ $typeFilter === 'full_database' ? 'selected' : '' }}>Full Backup</option>
                    <option value="subscribers_only" {{ $typeFilter === 'subscribers_only' ? 'selected' : '' }}>Customers &amp; PPPoE</option>
                    <option value="mikrotik_rsc" {{ $typeFilter === 'mikrotik_rsc' ? 'selected' : '' }}>Router Config</option>
                    <option value="billing_ledger" {{ $typeFilter === 'billing_ledger' ? 'selected' : '' }}>Billing &amp; Invoices</option>
                    <option value="system_config" {{ $typeFilter === 'system_config' ? 'selected' : '' }}>System Settings</option>
                </select>
            </div>

            {{-- Trigger Filter --}}
            <div>
                <select name="trigger" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none">
                    <option value="all" {{ $triggerFilter === 'all' || !$triggerFilter ? 'selected' : '' }}>All Sources</option>
                    <option value="manual_admin" {{ $triggerFilter === 'manual_admin' ? 'selected' : '' }}>Manual Backup</option>
                    <option value="scheduled_cron" {{ $triggerFilter === 'scheduled_cron' ? 'selected' : '' }}>Auto Scheduled</option>
                </select>
            </div>

            {{-- Pagination per_page --}}
            <div>
                <select name="per_page" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none">
                    <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10 Per Page</option>
                    <option value="20" {{ $perPage == 20 ? 'selected' : '' }}>20 Per Page</option>
                    <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50 Per Page</option>
                    <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100 Per Page</option>
                </select>
            </div>

            {{-- Strict Filter & Reset Action Buttons (AGENTS.md Rule 2.C) --}}
            <div class="flex items-center gap-1.5">
                <button type="submit" class="flex-1 bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-1.5 px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                    <i class="fas fa-filter text-[10px]"></i>
                    <span>Filter</span>
                </button>
                <a href="{{ route('tenant.settings.backup') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-1.5 px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer" title="Reset All Filters">
                    <i class="fas fa-rotate-left text-[10px]"></i>
                    <span>Reset</span>
                </a>
            </div>
        </form>
    </div>

    {{-- 4. MASTER COMPACT TABLE (<table class="saas-table"> - AGENTS.md Rule 2.D) --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-10 text-center">#</th>
                        <th>Backup File</th>
                        <th>Type</th>
                        <th class="font-mono">Size</th>
                        <th class="text-center">Status</th>
                        <th class="w-12 text-center no-sort">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($backups as $index => $b)
                        @php
                            $typeBadge = $b->type_badge;
                            $statusBadge = $b->status_badge;
                        @endphp
                        <tr>
                            {{-- Index --}}
                            <td class="text-center font-mono text-slate-500 text-xs">
                                {{ $backups->firstItem() + $index }}
                            </td>

                            {{-- Backup File --}}
                            <td class="font-semibold text-slate-800">
                                <div class="flex items-center gap-2">
                                    <span class="w-5 h-5 rounded-md flex items-center justify-center text-[10px] border {{ $typeBadge['class'] }}">
                                        <i class="fas {{ $typeBadge['icon'] }}"></i>
                                    </span>
                                    <span class="font-mono text-xs cursor-pointer hover:text-cyan-700 hover:underline" @click="viewSnapshotDetails({{ json_encode($b) }})">
                                        {{ $b->filename }}
                                    </span>
                                </div>
                            </td>

                            {{-- Backup Type --}}
                            <td>
                                <span class="px-2 py-0.5 rounded text-[10.5px] font-semibold border inline-flex items-center gap-1 {{ $typeBadge['class'] }}">
                                    <i class="fas {{ $typeBadge['icon'] }} text-[9px]"></i>
                                    <span>{{ $typeBadge['label'] }}</span>
                                </span>
                            </td>

                            {{-- File Size --}}
                            <td class="font-mono text-slate-800 text-xs font-bold">
                                {{ $b->formatted_size }}
                            </td>

                            {{-- Status --}}
                            <td class="text-center">
                                <span class="px-2 py-0.5 rounded text-[10.5px] font-semibold border inline-flex items-center gap-1 {{ $statusBadge['class'] }}">
                                    <i class="fas {{ $statusBadge['icon'] }} text-[9px]"></i>
                                    <span>{{ $statusBadge['label'] }}</span>
                                </span>
                            </td>

                            {{-- Action 3-Dot Menu (AGENTS.md Rule 2.E) --}}
                            <td class="text-center">
                                <button type="button" 
                                        @click="toggleMenu({{ json_encode($b) }}, $event)"
                                        class="w-7 h-7 rounded-lg hover:bg-slate-100 text-slate-500 hover:text-slate-800 transition flex items-center justify-center mx-auto cursor-pointer">
                                    <i class="fas fa-ellipsis-v text-[10px]"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-8 text-slate-500 text-xs">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <i class="fas fa-database text-2xl text-slate-300"></i>
                                    <span>No backup files found.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Laravel Native Compact Pagination --}}
        @if($backups->hasPages())
            <div class="px-4 py-2.5 border-t border-slate-100 bg-slate-50/50 flex items-center justify-between">
                <span class="text-xs text-slate-500 font-medium">
                    Showing {{ $backups->firstItem() }} to {{ $backups->lastItem() }} of {{ $backups->total() }} backups
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
         :style="`position: fixed; top: ${menuPos.top}; bottom: ${menuPos.bottom}; right: ${menuPos.right}; z-index: 50;`"
         class="w-48 bg-white rounded-xl border border-slate-200 shadow-xl py-1 text-xs divide-y divide-slate-100 animate-in fade-in zoom-in-95 duration-100">
        
        {{-- Group 1: Diagnostics & Download --}}
        <div class="py-1">
            <button type="button" @click="viewSnapshotDetails(activeMenu); activeMenu = null" class="w-full text-left px-3 py-1.5 hover:bg-slate-50 text-slate-700 flex items-center gap-2 transition cursor-pointer">
                <i class="fas fa-circle-info text-slate-400 w-3.5"></i>
                <span>View Details</span>
            </button>
            <button type="button" @click="verifyChecksum(activeMenu); activeMenu = null" class="w-full text-left px-3 py-1.5 hover:bg-slate-50 text-slate-700 flex items-center gap-2 transition cursor-pointer">
                <i class="fas fa-shield-check text-emerald-600 w-3.5"></i>
                <span>Check File Health</span>
            </button>
            <a :href="`/admin/settings/backup/${activeMenu ? activeMenu.id : ''}/download`" @click="activeMenu = null" class="w-full text-left px-3 py-1.5 hover:bg-slate-50 text-slate-700 flex items-center gap-2 transition cursor-pointer">
                <i class="fas fa-download text-blue-600 w-3.5"></i>
                <span>Download Backup</span>
            </a>
        </div>

        {{-- Group 2: Restore & Delete --}}
        <div class="py-1">
            <button type="button" @click="restoreSnapshotAction(activeMenu); activeMenu = null" class="w-full text-left px-3 py-1.5 hover:bg-amber-50 text-amber-700 flex items-center gap-2 transition cursor-pointer">
                <i class="fas fa-rotate-left text-amber-600 w-3.5"></i>
                <span>Restore Backup</span>
            </button>
            <button type="button" @click="deleteSnapshotAction(activeMenu); activeMenu = null" class="w-full text-left px-3 py-1.5 hover:bg-rose-50 text-rose-600 flex items-center gap-2 transition cursor-pointer">
                <i class="fas fa-trash-can text-rose-500 w-3.5"></i>
                <span>Delete Backup</span>
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
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-plus"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Create New Backup</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">Save a secure backup copy of your database</p>
                    </div>
                </div>
                <button type="button" @click="showSnapshotModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            {{-- Modal Body --}}
            <form id="createSnapshotForm" action="{{ route('tenant.settings.backup.snapshot') }}" method="POST" class="p-5 space-y-4 text-xs">
                @csrf

                <div>
                    <label class="block text-[10.5px] font-bold uppercase text-slate-500 mb-1">Backup Type <span class="text-rose-500">*</span></label>
                    <select name="backup_type" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-2 focus:bg-white focus:border-cyan-500 focus:outline-none">
                        <option value="full_database">1. Full Backup (All Database &amp; Settings) - Recommended</option>
                        <option value="subscribers_only">2. Customers &amp; PPPoE Accounts</option>
                        <option value="mikrotik_rsc">3. Router Configuration Scripts</option>
                        <option value="billing_ledger">4. Billing &amp; Payment Invoices</option>
                        <option value="system_config">5. System Settings &amp; Permissions</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[10.5px] font-bold uppercase text-slate-500 mb-1">Notes / Description (Optional)</label>
                    <input type="text" name="notes" placeholder="e.g. Before monthly update, Routine backup" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none">
                </div>

                <div class="p-3 bg-cyan-50/50 rounded-lg border border-cyan-100 flex items-start gap-2 text-cyan-900 text-[11px]">
                    <i class="fas fa-shield-check text-cyan-600 mt-0.5"></i>
                    <span>Backup files are compressed and securely saved in server storage.</span>
                </div>
            </form>

            {{-- Modal Footer --}}
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between flex-shrink-0">
                <button type="button" @click="showSnapshotModal = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                    Cancel
                </button>
                <button type="submit" form="createSnapshotForm" class="bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                    <i class="fas fa-check text-xs"></i>
                    <span>Create Backup Now</span>
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
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-circle-info"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Backup Details</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">Information about this backup file</p>
                    </div>
                </div>
                <button type="button" @click="showDetailsModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            {{-- Modal Body --}}
            <div class="p-5 space-y-3 text-xs" x-show="selectedSnapshot">
                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] uppercase font-bold text-slate-500">Filename</span>
                        <span class="font-mono text-xs text-slate-800 font-bold truncate max-w-[260px]" x-text="selectedSnapshot?.filename"></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] uppercase font-bold text-slate-500">File Size</span>
                        <span class="font-mono text-xs text-slate-800 font-bold" x-text="selectedSnapshot?.formatted_size"></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] uppercase font-bold text-slate-500">Total Tables</span>
                        <span class="font-mono text-xs text-indigo-700 font-bold" x-text="`${selectedSnapshot?.tables_count} Tables`"></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] uppercase font-bold text-slate-500">Total Records</span>
                        <span class="font-mono text-xs text-emerald-700 font-bold" x-text="`${selectedSnapshot?.records_count} Rows`"></span>
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] uppercase font-bold text-slate-500 mb-1">File Checksum (MD5)</label>
                    <div class="p-2.5 bg-slate-900 text-cyan-300 font-mono text-[11px] rounded-lg border border-slate-800 flex items-center justify-between">
                        <span class="truncate" x-text="selectedSnapshot?.checksum_md5 || 'N/A'"></span>
                        <button type="button" @click="copyChecksum(selectedSnapshot?.checksum_md5)" class="text-slate-400 hover:text-cyan-300 transition ml-2 cursor-pointer" title="Copy Checksum">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] uppercase font-bold text-slate-500 mb-1">Notes / Summary</label>
                    <p class="text-slate-600 text-[11.5px] p-2.5 bg-slate-50 rounded-lg border border-slate-200/80 leading-relaxed" x-text="selectedSnapshot?.log_summary || 'Backup created successfully.'"></p>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between flex-shrink-0">
                <a :href="`/admin/settings/backup/${selectedSnapshot ? selectedSnapshot.id : ''}/download`" class="bg-cyan-600 hover:bg-cyan-700 text-white text-xs font-semibold px-3.5 py-1.5 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                    <i class="fas fa-download text-xs"></i>
                    <span>Download</span>
                </a>
                <button type="button" @click="showDetailsModal = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-4 py-1.5 rounded-lg transition cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function backupManager() {
    return {
        activeMenu: null,
        menuPos: { top: '0px', bottom: 'auto', right: '0px' },

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
                title: 'Checking File...',
                text: `Checking file health for '${snapshot.filename}'...`,
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
                        title: 'File Health OK',
                        html: `<div class="text-left text-xs space-y-1.5">
                                <p><strong>Status:</strong> Healthy &amp; Intact</p>
                                <p><strong>Tables:</strong> ${data.tables} Tables</p>
                                <p><strong>Records:</strong> ${data.records} Rows</p>
                                <p><strong>Checksum:</strong> <code class="bg-slate-100 p-1 rounded font-mono">${data.checksum}</code></p>
                               </div>`,
                        confirmButtonColor: '#0891b2'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Check Failed',
                        text: data.message || 'Backup file check failed.',
                        confirmButtonColor: '#0891b2'
                    });
                }
            } catch (err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Unable to check backup file.',
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
                    title: 'Checksum copied to clipboard!',
                    showConfirmButton: false,
                    timer: 2000
                });
            });
        },

        restoreSnapshotAction(snapshot) {
            Swal.fire({
                title: 'Restore Database?',
                html: `Are you sure you want to test and restore from backup <strong>'${snapshot.filename}'</strong>?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f59e0b',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, Restore'
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
                title: 'Delete Backup?',
                text: `Are you sure you want to delete '${snapshot.filename}'? This cannot be undone.`,
                icon: 'error',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, Delete'
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

        runMaintenanceTool(action, title, message) {
            Swal.fire({
                title: title,
                text: message,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0891b2',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, Run Now'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Running Task...',
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
                                title: 'Completed',
                                text: data.message,
                                confirmButtonColor: '#0891b2'
                            });
                        }
                    } catch (e) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to complete task.',
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

