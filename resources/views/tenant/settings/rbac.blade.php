@extends('tenant.layouts.app')

@section('title', 'Role & Permissions (RBAC) - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
    {{-- Page-specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="rbacManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">
    
    {{-- 1. TOP HEADER BAR (Strict AGENTS.md Rule 2.A: Title + Icon + Action Buttons ONLY. No subtitles/p tags) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-sm shadow-2xs flex-shrink-0">
                <i class="fas fa-shield-halved"></i>
            </div>
            <h1 class="text-sm font-bold text-slate-800 tracking-tight">Role-Based Access Control (RBAC) &amp; Permissions</h1>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('tenant.settings.rbac.print') }}" target="_blank" class="h-[34px] py-0 px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-print text-slate-500 text-xs"></i>
                <span>Print Matrix</span>
            </a>
            <a href="{{ route('tenant.settings.rbac.export') }}" class="h-[34px] py-0 px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-file-csv text-slate-500 text-xs"></i>
                <span>Export CSV</span>
            </a>
            <a href="{{ route('tenant.settings.rbac.create') }}" class="h-[34px] py-0 px-3.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-plus text-xs"></i>
                <span>Create Role</span>
            </a>
        </div>
    </div>

    {{-- 2. KPI SUMMARY STRIP (Strictly 6 Cards - AGENTS.md Rule 2.B) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        {{-- Card 1: Defined Roles --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Defined Roles</span>
                <span class="text-[13px] font-bold font-mono text-indigo-700 leading-tight block">
                    {{ $stats['total_roles'] }} Roles
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-indigo-200 bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-user-shield"></i>
            </div>
        </div>

        {{-- Card 2: System Presets --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">System Presets</span>
                <span class="text-[13px] font-bold font-mono text-purple-700 leading-tight block">
                    {{ $stats['system_roles'] }} Built-in
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-purple-200 bg-purple-50 text-purple-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-lock"></i>
            </div>
        </div>

        {{-- Card 3: Custom Roles --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Custom Defined</span>
                <span class="text-[13px] font-bold font-mono text-cyan-700 leading-tight block">
                    {{ $stats['custom_roles'] }} Roles
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-cyan-200 bg-cyan-50 text-cyan-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-sliders"></i>
            </div>
        </div>

        {{-- Card 4: Assigned Staff --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Assigned Staff</span>
                <span class="text-[13px] font-bold font-mono text-blue-700 leading-tight block">
                    {{ $stats['assigned_staff'] }} Employees
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-blue-200 bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-users-gear"></i>
            </div>
        </div>

        {{-- Card 5: Total Permissions --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Permission Matrix</span>
                <span class="text-[13px] font-bold font-mono text-emerald-700 leading-tight block">
                    {{ $stats['total_permissions'] }} Capabilities
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-key"></i>
            </div>
        </div>

        {{-- Card 6: Active Profiles --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Active Profiles</span>
                <span class="text-[13px] font-bold font-mono text-amber-600 leading-tight block">
                    {{ $stats['active_roles'] }} Active
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-amber-200 bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-circle-check"></i>
            </div>
        </div>
    </div>

    {{-- 3. CUSTOMER-STANDARD 2-TIER FILTER SYSTEM (AGENTS.md Rule 2.C) --}}
    <div class="bg-white p-2.5 rounded-xl border border-slate-200/90 shadow-2xs space-y-2.5"
         x-data="{ 
             showMoreFilters: {{ $isMoreOpen ? 'true' : 'false' }}, 
             searchQuery: '{{ addslashes(request('search', '')) }}' 
         }">
        <form method="GET" action="{{ route('tenant.settings.rbac') }}" class="space-y-2.5">
            {{-- 1st Tier: Primary Single Row --}}
            <div class="flex flex-col md:flex-row items-stretch md:items-center gap-2">
                {{-- Intelligent Live Search Box --}}
                <div class="relative flex-1 min-w-[200px]">
                    <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-xs transition"
                       :class="searchQuery ? 'text-cyan-600 font-bold' : 'text-slate-400'"></i>
                    <input type="text" 
                           name="search" 
                           x-model="searchQuery"
                           placeholder="Search role title, key or description..." 
                           class="w-full h-[34px] pl-8 pr-7 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    <button type="button" 
                            x-show="searchQuery && searchQuery.length > 0" 
                            @click="searchQuery = ''; $nextTick(() => $el.closest('form').submit())"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 cursor-pointer">
                        <i class="fas fa-times-circle text-xs"></i>
                    </button>
                </div>

                {{-- Primary Filter: Classification --}}
                <select name="type" class="w-full md:w-44 h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    <option value="all" {{ $typeFilter === 'all' || !$typeFilter ? 'selected' : '' }}>All Classifications</option>
                    <option value="system" {{ $typeFilter === 'system' ? 'selected' : '' }}>System Presets (Built-in)</option>
                    <option value="custom" {{ $typeFilter === 'custom' ? 'selected' : '' }}>Custom Defined Roles</option>
                </select>

                {{-- Primary Filter: Status --}}
                <select name="status" class="w-full md:w-36 h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    <option value="all" {{ $statusFilter === 'all' || !$statusFilter ? 'selected' : '' }}>All Status</option>
                    <option value="active" {{ $statusFilter === 'active' ? 'selected' : '' }}>Active Only</option>
                    <option value="disabled" {{ $statusFilter === 'disabled' ? 'selected' : '' }}>Disabled Only</option>
                </select>

                {{-- Pagination Selector (per_page) --}}
                <select name="per_page" class="w-full md:w-28 flex-shrink-0 h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10 / page</option>
                    <option value="20" {{ $perPage == 20 ? 'selected' : '' }}>20 / page</option>
                    <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50 / page</option>
                    <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100 / page</option>
                </select>

                {{-- "More Filters" Accordion Toggle Button --}}
                <button type="button" 
                        @click="showMoreFilters = !showMoreFilters"
                        class="h-[34px] py-0 px-2.5 rounded-lg border text-xs font-semibold flex items-center justify-center gap-1.5 transition flex-shrink-0 cursor-pointer"
                        :class="showMoreFilters || {{ $activeMoreCount > 0 ? 'true' : 'false' }} ? 'bg-cyan-50 border-cyan-300 text-cyan-800' : 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100'">
                    <i class="fas fa-sliders text-xs"></i>
                    <span>More Filters</span>
                    @if($activeMoreCount > 0)
                        <span class="w-4 h-4 rounded-full bg-cyan-600 text-white text-[9px] font-bold flex items-center justify-center">{{ $activeMoreCount }}</span>
                    @endif
                    <i class="fas text-[10px] transition-transform" :class="showMoreFilters ? 'fa-chevron-up text-cyan-600' : 'fa-chevron-down text-slate-400'"></i>
                </button>

                {{-- Strict Filter & Reset Buttons Sequence --}}
                <div class="flex items-center gap-1.5 flex-shrink-0 md:ml-auto">
                    <button type="submit" 
                            class="h-[34px] py-0 px-3 bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer" 
                            title="Apply Filters">
                        <i class="fas fa-filter text-[10px]"></i>
                        <span>Filter</span>
                    </button>
                    <a href="{{ route('tenant.settings.rbac') }}" 
                       class="h-[34px] py-0 px-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer" 
                       title="Reset Filters">
                        <i class="fas fa-rotate-left text-[10px]"></i>
                        <span>Reset</span>
                    </a>
                </div>
            </div>

            {{-- 2nd Tier: Secondary Filters Drawer --}}
            <div x-show="showMoreFilters" x-cloak x-transition class="pt-2 border-t border-slate-100 flex flex-wrap items-center gap-2">
                {{-- Module / Scope Filter --}}
                <div class="w-full sm:w-64">
                    <select name="scope" class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="all" {{ $scopeFilter === 'all' || !$scopeFilter ? 'selected' : '' }}>All Capability Scopes</option>
                        <option value="customers" {{ $scopeFilter === 'customers' ? 'selected' : '' }}>Subscriber &amp; CRM Modules</option>
                        <option value="network" {{ $scopeFilter === 'network' ? 'selected' : '' }}>Network &amp; MikroTik Modules</option>
                        <option value="billing" {{ $scopeFilter === 'billing' ? 'selected' : '' }}>Billing &amp; Finance Modules</option>
                        <option value="support" {{ $scopeFilter === 'support' ? 'selected' : '' }}>Helpdesk &amp; Support Modules</option>
                        <option value="settings" {{ $scopeFilter === 'settings' ? 'selected' : '' }}>System Administration Modules</option>
                    </select>
                </div>
            </div>
        </form>
    </div>

    {{-- 4. MASTER COMPACT TABLE (<table class="saas-table"> Pure CSS System) --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        {{-- Anti-min-h void rule: Natural container height without forced min-h --}}
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-10 text-center font-semibold">#</th>
                        <th class="text-center font-semibold">Role Name</th>
                        <th class="text-center font-semibold">Classification</th>
                        <th class="text-center font-semibold">Assigned Staff</th>
                        <th class="text-center font-semibold">Capabilities</th>
                        <th class="text-center font-semibold">Status</th>
                        <th class="w-14 text-center font-semibold no-sort">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($roles as $index => $role)
                        @php
                            $badge = $role->badge;
                            $permCount = count($role->permissions ?? []);
                            $staffCount = $role->staff_count;
                        @endphp
                        <tr>
                            {{-- Index (Center) --}}
                            <td class="text-center font-mono text-slate-400 text-xs whitespace-nowrap">
                                {{ $roles->firstItem() + $index }}
                            </td>

                            {{-- Role Name (Left-aligned, Single-line) --}}
                            <td class="text-left font-semibold text-slate-800 whitespace-nowrap">
                                <a href="javascript:void(0)" @click="viewRoleMatrix({{ $role->id }})" class="hover:text-cyan-700 hover:underline inline-flex items-center gap-1.5">
                                    <span class="w-5 h-5 rounded-md flex items-center justify-center text-[10px] border {{ $badge['class'] }} flex-shrink-0">
                                        <i class="fas {{ $badge['icon'] }}"></i>
                                    </span>
                                    <span>{{ $role->display_name }}</span>
                                </a>
                            </td>

                            {{-- Classification (Center) --}}
                            <td class="text-center whitespace-nowrap">
                                @if($role->is_system)
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-purple-50 text-purple-700 border border-purple-200 inline-flex items-center gap-1">
                                        <i class="fas fa-lock text-[8.5px]"></i>
                                        <span>System Preset</span>
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-cyan-50 text-cyan-700 border border-cyan-200 inline-flex items-center gap-1">
                                        <i class="fas fa-user-gear text-[8.5px]"></i>
                                        <span>Custom Role</span>
                                    </span>
                                @endif
                            </td>

                            {{-- Assigned Staff (Center) --}}
                            <td class="text-center whitespace-nowrap">
                                <button type="button" @click="viewAssignedStaff({{ $role->id }})" class="inline-flex items-center gap-1 font-mono font-bold text-xs {{ $staffCount > 0 ? 'text-blue-700 hover:underline' : 'text-slate-400' }} cursor-pointer">
                                    <i class="fas fa-users text-[10px]"></i>
                                    <span>{{ $staffCount }} Staff</span>
                                </button>
                            </td>

                            {{-- Capabilities (Center) --}}
                            <td class="text-center whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold font-mono {{ $permCount > 0 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $permCount }} Capabilities
                                </span>
                            </td>

                            {{-- Status (Center) --}}
                            <td class="text-center whitespace-nowrap">
                                @if($role->status === 'active')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 inline-flex items-center gap-1">
                                        <i class="fas fa-circle-check text-[8.5px]"></i>
                                        <span>Active</span>
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-rose-50 text-rose-700 border border-rose-200 inline-flex items-center gap-1">
                                        <i class="fas fa-circle-xmark text-[8.5px]"></i>
                                        <span>Disabled</span>
                                    </span>
                                @endif
                            </td>

                            {{-- Action 3-Dot Menu (Center) --}}
                            <td class="text-center whitespace-nowrap">
                                <button type="button" 
                                        @click="toggleMenu({{ json_encode(['id' => $role->id, 'name' => $role->name, 'display_name' => $role->display_name, 'is_system' => $role->is_system, 'status' => $role->status, 'staff_count' => $staffCount]) }}, $event)"
                                        class="p-1 rounded-md text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition cursor-pointer"
                                        title="Actions">
                                    <i class="fas fa-ellipsis-v text-[10px]"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-10 text-slate-400">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 text-sm">
                                        <i class="fas fa-shield-halved"></i>
                                    </div>
                                    <span class="text-xs font-medium text-slate-600">No roles found matching your criteria.</span>
                                    <a href="{{ route('tenant.settings.rbac') }}" class="mt-1 inline-flex items-center gap-1 text-xs text-cyan-600 font-semibold hover:underline">
                                        <i class="fas fa-rotate-left text-[10px]"></i>
                                        <span>Reset All Filters</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Laravel Native Compact Pagination --}}
        @if($roles->hasPages())
            <div class="p-2.5 border-t border-slate-200 bg-slate-50/50 flex flex-col sm:flex-row items-center justify-between gap-2">
                <span class="text-xs text-slate-500 font-medium">
                    Showing {{ $roles->firstItem() }} to {{ $roles->lastItem() }} of {{ $roles->total() }} roles
                </span>
                <div>
                    {{ $roles->links() }}
                </div>
            </div>
        @endif
    </div>

    {{-- 5. FLOATING 3-DOT ACTION MENU (AGENTS.md Rule 2.E) --}}
    <div x-show="activeMenu !== null" 
         x-cloak 
         @click.away="activeMenu = null"
         class="fixed z-50 bg-white rounded-xl shadow-xl border border-slate-200 py-1.5 w-52 text-xs divide-y divide-slate-100 animate-in fade-in zoom-in-95 duration-100"
         :style="{ top: menuPos.top, bottom: menuPos.bottom, right: menuPos.right, left: menuPos.left }">
        
        {{-- Group 1: Edit & Matrix --}}
        <div class="py-1">
            <a :href="activeMenu ? `/admin/settings/rbac/${activeMenu.id}/edit` : '#'" class="w-full text-left px-3 py-1.5 hover:bg-slate-50 text-slate-700 flex items-center gap-2 transition cursor-pointer">
                <i class="fas fa-pen-to-square text-cyan-600 w-3.5"></i>
                <span>Edit Role &amp; Permissions</span>
            </a>
            <button type="button" @click="viewRoleMatrix(activeMenu.id); activeMenu = null" class="w-full text-left px-3 py-1.5 hover:bg-slate-50 text-slate-700 flex items-center gap-2 transition cursor-pointer">
                <i class="fas fa-key text-slate-400 w-3.5"></i>
                <span>View Permission Matrix</span>
            </button>
            <button type="button" @click="viewAssignedStaff(activeMenu.id); activeMenu = null" class="w-full text-left px-3 py-1.5 hover:bg-slate-50 text-slate-700 flex items-center gap-2 transition cursor-pointer">
                <i class="fas fa-users text-slate-400 w-3.5"></i>
                <span>View Assigned Staff</span>
            </button>
        </div>

        {{-- Group 2: Profile Actions --}}
        <div class="py-1">
            <button type="button" @click="copyRoleKey(activeMenu.name); activeMenu = null" class="w-full text-left px-3 py-1.5 hover:bg-slate-50 text-slate-700 flex items-center gap-2 transition cursor-pointer">
                <i class="fas fa-copy text-slate-400 w-3.5"></i>
                <span>Copy Role Key</span>
            </button>
            <button type="button" @click="cloneRoleAction(activeMenu); activeMenu = null" class="w-full text-left px-3 py-1.5 hover:bg-slate-50 text-slate-700 flex items-center gap-2 transition cursor-pointer">
                <i class="fas fa-clone text-blue-600 w-3.5"></i>
                <span>Clone Role Profile</span>
            </button>
            <template x-if="activeMenu && activeMenu.name !== 'isp_admin'">
                <button type="button" @click="toggleStatusAction(activeMenu); activeMenu = null" class="w-full text-left px-3 py-1.5 hover:bg-slate-50 text-slate-700 flex items-center gap-2 transition cursor-pointer">
                    <i class="fas fa-power-off text-amber-500 w-3.5"></i>
                    <span x-text="activeMenu.status === 'active' ? 'Disable Role' : 'Enable Role'"></span>
                </button>
            </template>
        </div>

        {{-- Group 3: Destructive Actions --}}
        <template x-if="activeMenu && !activeMenu.is_system">
            <div class="py-1">
                <button type="button" @click="deleteRoleAction(activeMenu); activeMenu = null" class="w-full text-left px-3 py-1.5 hover:bg-rose-50 text-rose-600 flex items-center gap-2 transition cursor-pointer">
                    <i class="fas fa-trash-can text-rose-500 w-3.5"></i>
                    <span>Delete Custom Role</span>
                </button>
            </div>
        </template>
    </div>

    {{-- 6. PRODUCTION-GRADE NATURAL MODALS (AGENTS.md Rule 3) --}}

    {{-- MODAL 1: VIEW PERMISSION MATRIX DETAILS (Natural Modal) --}}
    <div x-show="showMatrixModal" 
         x-cloak 
         class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto"
         @keydown.escape.window="showMatrixModal = false">
        <div @click.away="showMatrixModal = false" 
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-2xl overflow-hidden my-6 flex flex-col max-h-[85vh] animate-in fade-in zoom-in-95 duration-150">
            
            {{-- Modal Header --}}
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between flex-shrink-0">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-key"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800" x-text="selectedRole ? `Capabilities: ${selectedRole.display_name}` : 'Role Matrix'"></h3>
                        <span class="text-[10px] text-slate-500 font-normal">Active access permissions granted to this role</span>
                    </div>
                </div>
                <button type="button" @click="showMatrixModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            {{-- Matrix Content (Scrollable) --}}
            <div class="flex-1 overflow-y-auto p-4 space-y-3">
                <template x-if="selectedRole">
                    <div class="space-y-3">
                        <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 flex items-center justify-between">
                            <div>
                                <span class="text-[9px] uppercase font-bold text-slate-500 block">System Key</span>
                                <span class="font-mono text-xs text-slate-800 font-semibold" x-text="selectedRole.name"></span>
                            </div>
                            <div>
                                <span class="text-[9px] uppercase font-bold text-slate-500 block">Capabilities Granted</span>
                                <span class="font-mono text-xs text-emerald-700 font-bold" x-text="`${(selectedRole.permissions || []).length} / {{ $stats['total_permissions'] }}`"></span>
                            </div>
                            <div>
                                <span class="text-[9px] uppercase font-bold text-slate-500 block">Classification</span>
                                <span class="text-xs font-semibold" :class="selectedRole.is_system ? 'text-purple-700' : 'text-cyan-700'" x-text="selectedRole.is_system ? 'System Core Preset' : 'Custom Defined'"></span>
                            </div>
                        </div>

                        {{-- Permissions List grouped --}}
                        <div class="space-y-2">
                            @foreach($permissionCatalog as $groupTitle => $group)
                                <div class="border border-slate-200 rounded-lg overflow-hidden">
                                    <div class="bg-slate-100/80 px-3 py-1.5 flex items-center gap-2 border-b border-slate-200">
                                        <i class="fas {{ $group['icon'] }} text-slate-500 text-xs"></i>
                                        <span class="text-xs font-bold text-slate-800">{{ $groupTitle }}</span>
                                    </div>
                                    <div class="p-2.5 grid grid-cols-1 sm:grid-cols-2 gap-1.5">
                                        @foreach($group['permissions'] as $permKey => $permLabel)
                                            <div class="flex items-center gap-2 text-xs p-1 rounded" :class="(selectedRole.permissions || []).includes('{{ $permKey }}') || selectedRole.name === 'isp_admin' ? 'bg-emerald-50/60 text-emerald-900 font-medium' : 'text-slate-400 opacity-50'">
                                                <i class="fas text-[11px]" :class="(selectedRole.permissions || []).includes('{{ $permKey }}') || selectedRole.name === 'isp_admin' ? 'fa-check text-emerald-600' : 'fa-xmark text-slate-300'"></i>
                                                <span class="text-[11px] truncate">{{ $permLabel }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </template>
            </div>

            {{-- Modal Footer --}}
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between flex-shrink-0">
                <template x-if="selectedRole">
                    <a :href="`/admin/settings/rbac/${selectedRole.id}/edit`" class="h-[34px] py-0 px-3.5 bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs rounded-lg shadow-xs inline-flex items-center gap-1.5 transition">
                        <i class="fas fa-pen-to-square text-xs"></i>
                        <span>Edit This Role</span>
                    </a>
                </template>
                <button type="button" @click="showMatrixModal = false" class="h-[34px] py-0 border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-4 rounded-lg transition cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>

    {{-- MODAL 2: VIEW ASSIGNED STAFF (Natural Modal) --}}
    <div x-show="showStaffModal" 
         x-cloak 
         class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto"
         @keydown.escape.window="showStaffModal = false">
        <div @click.away="showStaffModal = false" 
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-xl overflow-hidden my-6 flex flex-col max-h-[85vh] animate-in fade-in zoom-in-95 duration-150">
            
            {{-- Modal Header --}}
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between flex-shrink-0">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-users"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800" x-text="selectedRole ? `Assigned Staff: ${selectedRole.display_name}` : 'Assigned Employees'"></h3>
                        <span class="text-[10px] text-slate-500 font-normal">Active employees mapped to this access role</span>
                    </div>
                </div>
                <button type="button" @click="showStaffModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            {{-- Staff List Body --}}
            <div class="flex-1 overflow-y-auto p-4 space-y-2">
                <template x-if="assignedStaffList.length === 0">
                    <div class="text-center py-8 text-slate-400 text-xs">
                        No employees currently assigned to this role.
                    </div>
                </template>

                <template x-for="(staff, idx) in assignedStaffList" :key="staff.id">
                    <div class="p-2.5 bg-slate-50 hover:bg-white rounded-lg border border-slate-200/80 transition flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-full bg-slate-200 text-slate-700 flex items-center justify-center font-bold text-xs" x-text="staff.name.charAt(0)"></div>
                            <div>
                                <h5 class="text-xs font-bold text-slate-800" x-text="staff.name"></h5>
                                <span class="text-[10.5px] text-slate-500 font-mono" x-text="staff.email || staff.phone || 'ID: ' + staff.id"></span>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-[10.5px] font-semibold text-slate-700 block" x-text="staff.designation || 'Staff Member'"></span>
                            <span class="px-1.5 py-0.5 rounded text-[9.5px] font-bold uppercase" :class="staff.status === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200'" x-text="staff.status"></span>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Modal Footer --}}
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between flex-shrink-0">
                <a href="{{ route('tenant.staff.index') }}" class="text-cyan-700 hover:underline text-xs font-semibold flex items-center gap-1">
                    <span>Manage All Staff</span>
                    <i class="fas fa-arrow-right text-[10px]"></i>
                </a>
                <button type="button" @click="showStaffModal = false" class="h-[34px] py-0 border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-4 rounded-lg transition cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function rbacManager() {
    return {
        activeMenu: null,
        menuPos: { top: 'auto', bottom: 'auto', right: 'auto', left: 'auto' },

        // Modals state
        showMatrixModal: false,
        showStaffModal: false,

        // Data containers
        selectedRole: null,
        assignedStaffList: [],

        toggleMenu(item, event) {
            if (this.activeMenu?.id === item.id) {
                this.activeMenu = null;
                return;
            }
            this.activeMenu = item;
            const rect = event.currentTarget.getBoundingClientRect();
            const dropdownHeight = 220;
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

        async viewRoleMatrix(roleId) {
            try {
                const response = await fetch(`/admin/settings/rbac/${roleId}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await response.json();
                if (data.success) {
                    this.selectedRole = data.role;
                    this.showMatrixModal = true;
                }
            } catch (err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to fetch capability matrix.',
                    confirmButtonColor: '#0891b2'
                });
            }
        },

        async viewAssignedStaff(roleId) {
            try {
                const response = await fetch(`/admin/settings/rbac/${roleId}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await response.json();
                if (data.success) {
                    this.selectedRole = data.role;
                    this.assignedStaffList = data.assigned_staff || [];
                    this.showStaffModal = true;
                }
            } catch (err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to fetch assigned staff roster.',
                    confirmButtonColor: '#0891b2'
                });
            }
        },

        copyRoleKey(key) {
            navigator.clipboard.writeText(key).then(() => {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: `Role key '${key}' copied to clipboard!`,
                    showConfirmButton: false,
                    timer: 2000
                });
            });
        },

        cloneRoleAction(role) {
            Swal.fire({
                title: 'Clone Role Profile?',
                text: `Create a new customizable copy of '${role.display_name}' with all its permissions?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0891b2',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, Clone Role'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = `/admin/settings/rbac/${role.id}/clone`;
                    form.innerHTML = `@csrf`;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        },

        toggleStatusAction(role) {
            const actionText = role.status === 'active' ? 'disable' : 'enable';
            Swal.fire({
                title: `${actionText.charAt(0).toUpperCase() + actionText.slice(1)} Role?`,
                text: `Are you sure you want to ${actionText} access for role '${role.display_name}'?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#0891b2',
                cancelButtonColor: '#64748b',
                confirmButtonText: `Yes, ${actionText}`
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = `/admin/settings/rbac/${role.id}/toggle-status`;
                    form.innerHTML = `@csrf`;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        },

        deleteRoleAction(role) {
            if (role.staff_count > 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Cannot Delete',
                    text: `This role has ${role.staff_count} active employees assigned. Please reassign them before deletion.`,
                    confirmButtonColor: '#0891b2'
                });
                return;
            }

            Swal.fire({
                title: 'Delete Custom Role?',
                text: `Are you sure you want to permanently delete '${role.display_name}'? This action cannot be undone.`,
                icon: 'error',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, Delete Role'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = `/admin/settings/rbac/${role.id}`;
                    form.innerHTML = `@csrf <input type="hidden" name="_method" value="DELETE">`;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
    };
}
</script>
@endpush
