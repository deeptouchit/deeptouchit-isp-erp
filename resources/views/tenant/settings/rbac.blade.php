@extends('tenant.layouts.app')

@section('title', 'Role & Permissions (RBAC) - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
    {{-- Page-specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="rbacManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">
    
    {{-- 1. TOP HEADER BAR (AGENTS.md Rule 2.A) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 border border-purple-100 flex items-center justify-center text-sm shadow-2xs flex-shrink-0">
                <i class="fas fa-shield-halved"></i>
            </div>
            <h1 class="text-sm font-bold text-slate-800 tracking-tight">Role-Based Access Control (RBAC) &amp; Permissions</h1>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('tenant.settings.rbac.print') }}" target="_blank" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-print text-slate-500 text-xs"></i>
                <span>Print Matrix</span>
            </a>
            <a href="{{ route('tenant.settings.rbac.export') }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-file-csv text-slate-500 text-xs"></i>
                <span>Export CSV</span>
            </a>
            <button type="button" @click="openCreateModal()" class="px-3.5 py-1.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-plus text-xs"></i>
                <span>Create Role</span>
            </button>
        </div>
    </div>

    {{-- 2. KPI SUMMARY STRIP (Strictly 6 Cards - AGENTS.md Rule 2.B) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        {{-- Card 1: Total Defined Roles --}}
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

        {{-- Card 6: Active Roles --}}
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

    {{-- 3. SEARCH & MULTI-FILTER TOOLBAR (AGENTS.md Rule 2.C) --}}
    <div class="bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
        <form action="{{ route('tenant.settings.rbac') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-2.5 items-center">
            {{-- Search Box --}}
            <div class="relative">
                <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" name="search" value="{{ $search }}" placeholder="Search role title, key or description..." class="w-full pl-8 pr-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-cyan-500 focus:outline-none">
            </div>

            {{-- Type Filter --}}
            <div>
                <select name="type" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none">
                    <option value="all" {{ $typeFilter === 'all' || !$typeFilter ? 'selected' : '' }}>All Classifications</option>
                    <option value="system" {{ $typeFilter === 'system' ? 'selected' : '' }}>System Presets (Built-in)</option>
                    <option value="custom" {{ $typeFilter === 'custom' ? 'selected' : '' }}>Custom Defined Roles</option>
                </select>
            </div>

            {{-- Status Filter --}}
            <div>
                <select name="status" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none">
                    <option value="all" {{ $statusFilter === 'all' || !$statusFilter ? 'selected' : '' }}>All Status</option>
                    <option value="active" {{ $statusFilter === 'active' ? 'selected' : '' }}>Active Roles Only</option>
                    <option value="disabled" {{ $statusFilter === 'disabled' ? 'selected' : '' }}>Disabled Roles</option>
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
                <a href="{{ route('tenant.settings.rbac') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-1.5 px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer" title="Reset All Filters">
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
                        <th>Role Name</th>
                        <th>Classification</th>
                        <th>Capabilities</th>
                        <th class="text-center">Status</th>
                        <th class="w-12 text-center no-sort">Action</th>
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
                            {{-- Index --}}
                            <td class="text-center font-mono text-slate-500 text-xs">
                                {{ $roles->firstItem() + $index }}
                            </td>

                            {{-- Role Name --}}
                            <td class="font-semibold text-slate-800">
                                <div class="flex items-center gap-2">
                                    <span class="w-5 h-5 rounded-md flex items-center justify-center text-[10px] border {{ $badge['class'] }}">
                                        <i class="fas {{ $badge['icon'] }}"></i>
                                    </span>
                                    <span class="cursor-pointer hover:text-cyan-700 hover:underline" @click="viewRoleMatrix({{ $role->id }})">
                                        {{ $role->display_name }}
                                    </span>
                                </div>
                            </td>

                            {{-- Classification --}}
                            <td>
                                @if($role->is_system)
                                    <span class="px-2 py-0.5 rounded text-[10.5px] font-semibold bg-purple-50 text-purple-700 border border-purple-200 inline-flex items-center gap-1">
                                        <i class="fas fa-lock text-[9px]"></i>
                                        <span>System Preset</span>
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10.5px] font-semibold bg-cyan-50 text-cyan-700 border border-cyan-200 inline-flex items-center gap-1">
                                        <i class="fas fa-user-gear text-[9px]"></i>
                                        <span>Custom Defined</span>
                                    </span>
                                @endif
                            </td>

                            {{-- Capabilities --}}
                            <td>
                                <span class="px-2 py-0.5 rounded-full text-[10.5px] font-bold font-mono {{ $permCount > 0 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $permCount }} Capabilities
                                </span>
                            </td>

                            {{-- Status --}}
                            <td class="text-center">
                                @if($role->status === 'active')
                                    <span class="px-2 py-0.5 rounded text-[10.5px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 inline-flex items-center gap-1">
                                        <i class="fas fa-circle-check text-[9px]"></i>
                                        <span>Active</span>
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10.5px] font-semibold bg-rose-50 text-rose-700 border border-rose-200 inline-flex items-center gap-1">
                                        <i class="fas fa-circle-xmark text-[9px]"></i>
                                        <span>Disabled</span>
                                    </span>
                                @endif
                            </td>

                            {{-- Action 3-Dot Menu (AGENTS.md Rule 2.E) --}}
                            <td class="text-center">
                                <button type="button" 
                                        @click="toggleMenu({{ json_encode(['id' => $role->id, 'name' => $role->name, 'display_name' => $role->display_name, 'is_system' => $role->is_system, 'status' => $role->status, 'staff_count' => $staffCount]) }}, $event)"
                                        class="w-7 h-7 rounded-lg hover:bg-slate-100 text-slate-500 hover:text-slate-800 transition flex items-center justify-center mx-auto cursor-pointer">
                                    <i class="fas fa-ellipsis-v text-[10px]"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-8 text-slate-500 text-xs">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <i class="fas fa-shield-halved text-2xl text-slate-300"></i>
                                    <span>No roles found matching your criteria.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Laravel Native Compact Pagination --}}
        @if($roles->hasPages())
            <div class="px-4 py-2.5 border-t border-slate-100 bg-slate-50/50 flex items-center justify-between">
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
         :style="`position: fixed; top: ${menuPos.top}; bottom: ${menuPos.bottom}; right: ${menuPos.right}; z-index: 50;`"
         class="w-52 bg-white rounded-xl border border-slate-200 shadow-xl py-1 text-xs divide-y divide-slate-100 animate-in fade-in zoom-in-95 duration-100">
        
        {{-- Group 1: Diagnostics & Matrix --}}
        <div class="py-1">
            <button type="button" @click="viewRoleMatrix(activeMenu.id); activeMenu = null" class="w-full text-left px-3 py-1.5 hover:bg-slate-50 text-slate-700 flex items-center gap-2 transition cursor-pointer">
                <i class="fas fa-key text-slate-400 w-3.5"></i>
                <span>View Permission Matrix</span>
            </button>
            <button type="button" @click="viewAssignedStaff(activeMenu.id); activeMenu = null" class="w-full text-left px-3 py-1.5 hover:bg-slate-50 text-slate-700 flex items-center gap-2 transition cursor-pointer">
                <i class="fas fa-users text-slate-400 w-3.5"></i>
                <span>View Assigned Staff</span>
            </button>
            <button type="button" @click="copyRoleKey(activeMenu.name); activeMenu = null" class="w-full text-left px-3 py-1.5 hover:bg-slate-50 text-slate-700 flex items-center gap-2 transition cursor-pointer">
                <i class="fas fa-copy text-slate-400 w-3.5"></i>
                <span>Copy Role Key</span>
            </button>
        </div>

        {{-- Group 2: Management & Configuration --}}
        <div class="py-1">
            <button type="button" @click="editRole(activeMenu.id); activeMenu = null" class="w-full text-left px-3 py-1.5 hover:bg-slate-50 text-slate-700 flex items-center gap-2 transition cursor-pointer">
                <i class="fas fa-pen-to-square text-cyan-600 w-3.5"></i>
                <span>Edit Role &amp; Permissions</span>
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

    {{-- MODAL 1: CREATE / EDIT ROLE & PERMISSION MATRIX --}}
    <div x-show="showRoleModal" 
         x-cloak 
         class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto">
        <div @click.away="showRoleModal = false" 
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-3xl overflow-hidden my-6 flex flex-col max-h-[90vh] animate-in fade-in zoom-in-95 duration-150">
            
            {{-- Modal Header --}}
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between flex-shrink-0">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas" :class="isEditing ? 'fa-pen-to-square' : 'fa-plus'"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800" x-text="isEditing ? `Edit Role: ${roleForm.display_name}` : 'Create New Custom Role'"></h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">Define role details and toggle granular functional permissions</p>
                    </div>
                </div>
                <button type="button" @click="showRoleModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            {{-- Modal Form Body (Scrollable) --}}
            <form id="roleModalForm" :action="isEditing ? `/admin/settings/rbac/${currentRoleId}` : '{{ route('tenant.settings.rbac.store') }}'" method="POST" class="flex-1 overflow-y-auto p-5 space-y-4">
                @csrf
                <template x-if="isEditing">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                {{-- Role Basic Details Grid --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                    <div class="md:col-span-2">
                        <label class="block text-[10.5px] font-bold uppercase text-slate-500 mb-1">Role Title / Display Name <span class="text-rose-500">*</span></label>
                        <input type="text" name="display_name" x-model="roleForm.display_name" required placeholder="e.g. Senior Billing Officer" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-[10.5px] font-bold uppercase text-slate-500 mb-1">Badge Color Theme</label>
                        <select name="color" x-model="roleForm.color" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none">
                            <option value="purple">Purple Theme</option>
                            <option value="blue">Blue Theme</option>
                            <option value="indigo">Indigo Theme</option>
                            <option value="emerald">Emerald Theme</option>
                            <option value="amber">Amber Theme</option>
                            <option value="cyan">Cyan Theme</option>
                            <option value="rose">Rose Theme</option>
                            <option value="sky">Sky Theme</option>
                            <option value="slate">Slate Neutral</option>
                        </select>
                    </div>

                    <div class="sm:col-span-2 md:col-span-2">
                        <label class="block text-[10.5px] font-bold uppercase text-slate-500 mb-1">Role Description &amp; Scope</label>
                        <input type="text" name="description" x-model="roleForm.description" placeholder="Brief description of responsibilities..." class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-[10.5px] font-bold uppercase text-slate-500 mb-1">Initial Status</label>
                        <select name="status" x-model="roleForm.status" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none">
                            <option value="active">Active</option>
                            <option value="disabled">Disabled</option>
                        </select>
                    </div>
                </div>

                {{-- Permission Selector Matrix Header --}}
                <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
                    <div>
                        <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Granular Capabilities &amp; Permissions</h4>
                        <span class="text-[11px] text-slate-500 font-medium">Selected: <strong class="text-cyan-700 font-mono" x-text="roleForm.permissions.length"></strong> Capabilities</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <button type="button" @click="selectAllPermissions()" class="px-2.5 py-1 text-[10.5px] font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded border border-slate-200 transition cursor-pointer">
                            Select All
                        </button>
                        <button type="button" @click="unselectAllPermissions()" class="px-2.5 py-1 text-[10.5px] font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded border border-slate-200 transition cursor-pointer">
                            Clear All
                        </button>
                    </div>
                </div>

                {{-- Grouped Permissions Checkbox Grid --}}
                <div class="space-y-3">
                    @foreach($permissionCatalog as $groupTitle => $group)
                        <div class="border border-slate-200/90 rounded-lg overflow-hidden bg-slate-50/40">
                            <div class="bg-slate-100/80 px-3 py-1.5 flex items-center justify-between border-b border-slate-200/80">
                                <div class="flex items-center gap-2">
                                    <i class="fas {{ $group['icon'] }} text-{{ $group['color'] }}-600 text-xs"></i>
                                    <span class="text-xs font-bold text-slate-800">{{ $groupTitle }}</span>
                                </div>
                                <span class="text-[10px] text-slate-500 font-mono">({{ count($group['permissions']) }} Perms)</span>
                            </div>
                            <div class="p-3 grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                                @foreach($group['permissions'] as $permKey => $permLabel)
                                    <label class="flex items-start gap-2 p-1.5 rounded hover:bg-white transition cursor-pointer">
                                        <input type="checkbox" name="permissions[]" value="{{ $permKey }}" x-model="roleForm.permissions" class="mt-0.5 rounded border-slate-300 text-cyan-600 focus:ring-cyan-500 w-3.5 h-3.5">
                                        <div class="min-w-0">
                                            <span class="font-medium text-slate-800 block text-[11px] leading-tight">{{ $permLabel }}</span>
                                            <span class="font-mono text-[9.5px] text-slate-400 block">{{ $permKey }}</span>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </form>

            {{-- Modal Footer --}}
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between flex-shrink-0">
                <button type="button" @click="showRoleModal = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                    Cancel
                </button>
                <button type="submit" form="roleModalForm" class="bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                    <i class="fas fa-save text-xs"></i>
                    <span x-text="isEditing ? 'Save Role Changes' : 'Create Role'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- MODAL 2: VIEW PERMISSION MATRIX DETAILS (Natural Modal) --}}
    <div x-show="showMatrixModal" 
         x-cloak 
         class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto">
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
                        <p class="text-[10.5px] text-slate-500 font-normal">Active access permissions granted to this role</p>
                    </div>
                </div>
                <button type="button" @click="showMatrixModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            {{-- Matrix Content (Scrollable) --}}
            <div class="flex-1 overflow-y-auto p-5 space-y-3">
                <template x-if="selectedRole">
                    <div class="space-y-3">
                        <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 flex items-center justify-between">
                            <div>
                                <span class="text-[10px] uppercase font-bold text-slate-500 block">System Key</span>
                                <span class="font-mono text-xs text-slate-800 font-semibold" x-text="selectedRole.name"></span>
                            </div>
                            <div>
                                <span class="text-[10px] uppercase font-bold text-slate-500 block">Capabilities Granted</span>
                                <span class="font-mono text-xs text-emerald-700 font-bold" x-text="`${(selectedRole.permissions || []).length} / {{ $stats['total_permissions'] }}`"></span>
                            </div>
                            <div>
                                <span class="text-[10px] uppercase font-bold text-slate-500 block">Classification</span>
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
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end flex-shrink-0">
                <button type="button" @click="showMatrixModal = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-4 py-1.5 rounded-lg transition cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>

    {{-- MODAL 3: VIEW ASSIGNED STAFF (Natural Modal) --}}
    <div x-show="showStaffModal" 
         x-cloak 
         class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto">
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
                        <p class="text-[10.5px] text-slate-500 font-normal">Active employees mapped to this access role</p>
                    </div>
                </div>
                <button type="button" @click="showStaffModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            {{-- Staff List Body --}}
            <div class="flex-1 overflow-y-auto p-4 space-y-2">
                <template x-if="assignedStaffList.length === 0">
                    <div class="text-center py-6 text-slate-400 text-xs">
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
                <button type="button" @click="showStaffModal = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-4 py-1.5 rounded-lg transition cursor-pointer">
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
        menuPos: { top: '0px', bottom: 'auto', right: '0px' },

        // Modals state
        showRoleModal: false,
        showMatrixModal: false,
        showStaffModal: false,
        isEditing: false,
        currentRoleId: null,

        // Data containers
        selectedRole: null,
        assignedStaffList: [],

        // Form bindings
        roleForm: {
            display_name: '',
            description: '',
            color: 'purple',
            status: 'active',
            permissions: []
        },

        allAvailablePermissions: [
            @foreach($permissionCatalog as $group)
                @foreach($group['permissions'] as $pKey => $pLabel)
                    '{{ $pKey }}',
                @endforeach
            @endforeach
        ],

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

        openCreateModal() {
            this.isEditing = false;
            this.currentRoleId = null;
            this.roleForm = {
                display_name: '',
                description: '',
                color: 'purple',
                status: 'active',
                permissions: [
                    'customers.view',
                    'support.tickets'
                ]
            };
            this.showRoleModal = true;
        },

        async editRole(roleId) {
            try {
                const response = await fetch(`/admin/settings/rbac/${roleId}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await response.json();
                if (data.success) {
                    this.isEditing = true;
                    this.currentRoleId = roleId;
                    this.roleForm = {
                        display_name: data.role.display_name,
                        description: data.role.description || '',
                        color: data.role.color || 'purple',
                        status: data.role.status || 'active',
                        permissions: data.role.permissions || []
                    };
                    this.showRoleModal = true;
                }
            } catch (err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to load role details for editing.',
                    confirmButtonColor: '#0891b2'
                });
            }
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

        selectAllPermissions() {
            this.roleForm.permissions = [...this.allAvailablePermissions];
        },

        unselectAllPermissions() {
            this.roleForm.permissions = [];
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
