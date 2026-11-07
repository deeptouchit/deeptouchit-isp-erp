@extends('tenant.layouts.app')

@section('title', 'System Users & Login Accounts - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
    {{-- Page-specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="userManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">
    
    {{-- 1. TOP HEADER BAR (Strict Rule 2.A: Title + Icon + Action Buttons ONLY. No subtitles/p tags) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-sm shadow-2xs flex-shrink-0">
                <i class="fas fa-users-gear"></i>
            </div>
            <h1 class="text-sm font-bold text-slate-800 tracking-tight">System Users &amp; Login Accounts</h1>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('tenant.users.print') }}" target="_blank" class="h-[34px] py-0 px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-print text-slate-500 text-xs"></i>
                <span>Print Directory</span>
            </a>
            <a href="{{ route('tenant.users.export') }}" class="h-[34px] py-0 px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-file-csv text-slate-500 text-xs"></i>
                <span>Export CSV</span>
            </a>
            <button type="button" @click="openCreateModal()" class="h-[34px] py-0 px-3.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-user-plus text-xs"></i>
                <span>Create User</span>
            </button>
        </div>
    </div>

    {{-- 2. KPI SUMMARY STRIP (Strictly 6 Cards - AGENTS.md Rule 2.B) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        {{-- Card 1: Total Users --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">User Accounts</span>
                <span class="text-[13px] font-bold font-mono text-indigo-700 leading-tight block">
                    {{ $stats['total_users'] }} Users
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-indigo-200 bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-users"></i>
            </div>
        </div>

        {{-- Card 2: ISP Admins --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">ISP Admins</span>
                <span class="text-[13px] font-bold font-mono text-purple-700 leading-tight block">
                    {{ $stats['admin_users'] }} Admins
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-purple-200 bg-purple-50 text-purple-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-shield-halved"></i>
            </div>
        </div>

        {{-- Card 3: Staff Operators --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Staff Operators</span>
                <span class="text-[13px] font-bold font-mono text-blue-700 leading-tight block">
                    {{ $stats['staff_users'] }} Operators
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-blue-200 bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-user-tie"></i>
            </div>
        </div>

        {{-- Card 4: Active Logins --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Active Logins</span>
                <span class="text-[13px] font-bold font-mono text-emerald-700 leading-tight block">
                    {{ $stats['active_users'] }} Active
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-circle-check"></i>
            </div>
        </div>

        {{-- Card 5: Suspended Accounts --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Disabled Accounts</span>
                <span class="text-[13px] font-bold font-mono {{ $stats['suspended_users'] > 0 ? 'text-amber-600' : 'text-slate-700' }} leading-tight block">
                    {{ $stats['suspended_users'] }} Inactive
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border {{ $stats['suspended_users'] > 0 ? 'border-amber-200 bg-amber-50 text-amber-600' : 'border-slate-200 bg-slate-50 text-slate-600' }} flex items-center justify-center flex-shrink-0">
                <i class="fas fa-user-lock"></i>
            </div>
        </div>

        {{-- Card 6: Assigned RBAC Roles --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Assigned Roles</span>
                <span class="text-[13px] font-bold font-mono text-cyan-700 leading-tight block">
                    {{ $stats['assigned_roles'] }} Roles
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-cyan-200 bg-cyan-50 text-cyan-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-id-badge"></i>
            </div>
        </div>
    </div>

    {{-- 3. CUSTOMER-STANDARD 2-TIER FILTER SYSTEM (AGENTS.md Rule 2.C) --}}
    <div class="bg-white p-2.5 rounded-xl border border-slate-200/90 shadow-2xs space-y-2.5"
         x-data="{ 
             showMoreFilters: {{ $isMoreOpen ? 'true' : 'false' }}, 
             searchQuery: '{{ addslashes(request('search', '')) }}' 
         }">
        <form method="GET" action="{{ route('tenant.users.index') }}" class="space-y-2.5">
            {{-- 1st Tier: Primary Single Row --}}
            <div class="flex flex-col md:flex-row items-stretch md:items-center gap-2">
                {{-- Intelligent Live Search Box --}}
                <div class="relative flex-1 min-w-[200px]">
                    <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-xs transition"
                       :class="searchQuery ? 'text-cyan-600 font-bold' : 'text-slate-400'"></i>
                    <input type="text" 
                           name="search" 
                           x-model="searchQuery"
                           placeholder="Search by name, email, phone or staff ID..." 
                           class="w-full h-[34px] pl-8 pr-7 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    <button type="button" 
                            x-show="searchQuery && searchQuery.length > 0" 
                            @click="searchQuery = ''; $nextTick(() => $el.closest('form').submit())"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 cursor-pointer">
                        <i class="fas fa-times-circle text-xs"></i>
                    </button>
                </div>

                {{-- Primary Filter: Role --}}
                <select name="role" class="w-full md:w-44 h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    <option value="all">All Assigned Roles</option>
                    @foreach($availableRoles as $role)
                        <option value="{{ $role->name }}" {{ $roleFilter === $role->name ? 'selected' : '' }}>
                            {{ $role->display_name }}
                        </option>
                    @endforeach
                </select>

                {{-- Primary Filter: Status --}}
                <select name="status" class="w-full md:w-36 h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    <option value="all">All Status</option>
                    <option value="active" {{ $statusFilter === 'active' ? 'selected' : '' }}>Active Only</option>
                    <option value="suspended" {{ $statusFilter === 'suspended' ? 'selected' : '' }}>Suspended</option>
                    <option value="inactive" {{ $statusFilter === 'inactive' ? 'selected' : '' }}>Inactive</option>
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
                    <a href="{{ route('tenant.users.index') }}" 
                       class="h-[34px] py-0 px-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer" 
                       title="Reset Filters">
                        <i class="fas fa-rotate-left text-[10px]"></i>
                        <span>Reset</span>
                    </a>
                </div>
            </div>

            {{-- 2nd Tier: Secondary Filters Drawer --}}
            <div x-show="showMoreFilters" x-cloak x-transition class="pt-2 border-t border-slate-100 flex flex-wrap items-center gap-2">
                {{-- Scope Affiliation Filter --}}
                <div class="w-full sm:w-56">
                    <select name="scope" class="w-full h-[34px] bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="all" {{ $scopeFilter === 'all' || !$scopeFilter ? 'selected' : '' }}>All Affiliations</option>
                        <option value="isp" {{ $scopeFilter === 'isp' ? 'selected' : '' }}>ISP Headquarters Core</option>
                        <option value="reseller" {{ $scopeFilter === 'reseller' ? 'selected' : '' }}>Partner Sub-ISPs</option>
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
                        <th class="text-center font-semibold">User Name</th>
                        <th class="text-center font-semibold">Email / Login ID</th>
                        <th class="text-center font-semibold">Phone</th>
                        <th class="text-center font-semibold">Assigned Role</th>
                        <th class="text-center font-semibold">Status</th>
                        <th class="w-14 text-center font-semibold no-sort">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $index => $u)
                        @php
                            $roleBadge = $u->role_badge;
                            $statusBadge = $u->status_badge;
                        @endphp
                        <tr>
                            {{-- Index (Center) --}}
                            <td class="text-center font-mono text-slate-400 text-xs whitespace-nowrap">
                                {{ $users->firstItem() + $index }}
                            </td>

                            {{-- User Name (Left-aligned, Single-line) --}}
                            <td class="text-left font-semibold text-slate-800 whitespace-nowrap">
                                <a href="javascript:void(0)" @click="viewUserDetails({{ $u->id }})" class="hover:text-cyan-700 hover:underline inline-flex items-center gap-1.5">
                                    <div class="w-5 h-5 rounded-full bg-slate-100 border border-slate-200 text-slate-600 text-[10px] font-bold flex items-center justify-center flex-shrink-0">
                                        {{ strtoupper(substr($u->name, 0, 1)) }}
                                    </div>
                                    <span>{{ $u->name }}</span>
                                </a>
                            </td>

                            {{-- Email / Login ID (Left-aligned) --}}
                            <td class="text-left font-mono text-slate-600 text-xs whitespace-nowrap">
                                {{ $u->email }}
                            </td>

                            {{-- Phone (Center) --}}
                            <td class="text-center font-mono text-slate-700 text-xs whitespace-nowrap">
                                {{ $u->phone ?? $u->mobile ?? '—' }}
                            </td>

                            {{-- Assigned Role (Center) --}}
                            <td class="text-center whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold border inline-flex items-center gap-1 {{ $roleBadge['class'] ?? 'bg-slate-100 text-slate-700 border-slate-200' }}">
                                    <i class="fas {{ $roleBadge['icon'] ?? 'fa-user' }} text-[8.5px]"></i>
                                    <span>{{ $roleBadge['label'] ?? ucfirst($u->role) }}</span>
                                </span>
                            </td>

                            {{-- Status (Center) --}}
                            <td class="text-center whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold border inline-flex items-center gap-1 {{ $statusBadge['class'] ?? 'bg-slate-100 text-slate-700 border-slate-200' }}">
                                    <i class="fas {{ $statusBadge['icon'] ?? 'fa-circle-check' }} text-[8.5px]"></i>
                                    <span>{{ $statusBadge['label'] ?? ucfirst($u->status) }}</span>
                                </span>
                            </td>

                            {{-- Action 3-Dot Menu (Center) --}}
                            <td class="text-center whitespace-nowrap">
                                <button type="button" 
                                        @click="toggleMenu({{ json_encode(['id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'role' => $u->role, 'status' => $u->status, 'phone' => $u->phone, 'designation' => $u->designation]) }}, $event)"
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
                                        <i class="fas fa-users-slash"></i>
                                    </div>
                                    <span class="text-xs font-medium text-slate-600">No user login accounts found matching your criteria.</span>
                                    <a href="{{ route('tenant.users.index') }}" class="mt-1 inline-flex items-center gap-1 text-xs text-cyan-600 font-semibold hover:underline">
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
        @if($users->hasPages())
            <div class="p-2.5 border-t border-slate-200 bg-slate-50/50 flex flex-col sm:flex-row items-center justify-between gap-2">
                <span class="text-xs text-slate-500 font-medium">
                    Showing {{ $users->firstItem() }} to {{ $users->lastItem() }} of {{ $users->total() }} user accounts
                </span>
                <div>
                    {{ $users->links() }}
                </div>
            </div>
        @endif
    </div>

    {{-- 5. FLOATING 3-DOT ACTION MENU (AGENTS.md Rule 2.E) --}}
    <div x-show="activeMenu !== null" 
         x-cloak 
         @click.away="activeMenu = null"
         class="fixed z-50 bg-white rounded-xl shadow-xl border border-slate-200 py-1.5 w-48 text-xs divide-y divide-slate-100 animate-in fade-in zoom-in-95 duration-100"
         :style="{ top: menuPos.top, bottom: menuPos.bottom, right: menuPos.right, left: menuPos.left }">
        
        {{-- Group 1: Details & Edit --}}
        <div class="py-1">
            <button type="button" @click="viewUserDetails(activeMenu.id); activeMenu = null" class="w-full text-left px-3 py-1.5 hover:bg-slate-50 text-slate-700 flex items-center gap-2 transition cursor-pointer">
                <i class="fas fa-id-card text-slate-400 w-3.5"></i>
                <span>View User Details</span>
            </button>
            <button type="button" @click="openEditModal(activeMenu); activeMenu = null" class="w-full text-left px-3 py-1.5 hover:bg-slate-50 text-slate-700 flex items-center gap-2 transition cursor-pointer">
                <i class="fas fa-user-pen text-cyan-600 w-3.5"></i>
                <span>Edit User Account</span>
            </button>
            <button type="button" @click="openPasswordModal(activeMenu); activeMenu = null" class="w-full text-left px-3 py-1.5 hover:bg-slate-50 text-slate-700 flex items-center gap-2 transition cursor-pointer">
                <i class="fas fa-key text-amber-500 w-3.5"></i>
                <span>Reset Password</span>
            </button>
        </div>

        {{-- Group 2: Status Toggle & Security --}}
        <div class="py-1">
            <button type="button" @click="toggleUserStatus(activeMenu); activeMenu = null" class="w-full text-left px-3 py-1.5 hover:bg-slate-50 text-slate-700 flex items-center gap-2 transition cursor-pointer">
                <i class="fas fa-power-off text-indigo-500 w-3.5"></i>
                <span x-text="activeMenu && activeMenu.status === 'active' ? 'Suspend Account' : 'Activate Account'"></span>
            </button>
        </div>

        {{-- Group 3: Delete Action --}}
        <div class="py-1">
            <button type="button" @click="deleteUserAction(activeMenu); activeMenu = null" class="w-full text-left px-3 py-1.5 hover:bg-rose-50 text-rose-600 flex items-center gap-2 transition cursor-pointer">
                <i class="fas fa-trash-can text-rose-500 w-3.5"></i>
                <span>Delete Account</span>
            </button>
        </div>
    </div>

    {{-- 6. PRODUCTION-GRADE NATURAL MODALS (AGENTS.md Rule 3) --}}

    {{-- MODAL 1: CREATE USER ACCOUNT --}}
    <div x-show="showCreateModal" 
         x-cloak 
         class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto"
         @keydown.escape.window="showCreateModal = false">
        <div @click.away="showCreateModal = false" 
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden my-6 flex flex-col max-h-[90vh] animate-in fade-in zoom-in-95 duration-150">
            
            {{-- Modal Header --}}
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between flex-shrink-0">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Create New User Account</h3>
                        <span class="text-[10px] text-slate-500 font-normal">Add login account and assign RBAC system permissions</span>
                    </div>
                </div>
                <button type="button" @click="showCreateModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            {{-- Form Body --}}
            <form id="createUserForm" action="{{ route('tenant.users.store') }}" method="POST" class="p-4 space-y-3 flex-1 overflow-y-auto text-xs">
                @csrf

                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Full Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" required placeholder="e.g. Mahfuzur Rahman" class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Email / Login ID <span class="text-rose-500">*</span></label>
                        <input type="email" name="email" required placeholder="user@domain.com" class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition font-mono">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Phone Number</label>
                        <input type="text" name="phone" placeholder="01819-000000" class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition font-mono">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Assigned RBAC Role <span class="text-rose-500">*</span></label>
                        <select name="role" required class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                            <option value="">Select a Role...</option>
                            @foreach($availableRoles as $role)
                                <option value="{{ $role->name }}">{{ $role->display_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Initial Status</label>
                        <select name="status" class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                            <option value="active">Active (Permitted)</option>
                            <option value="suspended">Suspended</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Account Password <span class="text-rose-500">*</span></label>
                        <input type="password" name="password" required minlength="6" placeholder="Min 6 characters" class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Designation / Note</label>
                        <input type="text" name="designation" placeholder="e.g. NOC Engineer" class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>
                </div>
            </form>

            {{-- Modal Footer --}}
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between flex-shrink-0">
                <button type="button" @click="showCreateModal = false" class="h-[34px] py-0 border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 rounded-lg transition cursor-pointer">
                    Cancel
                </button>
                <button type="submit" form="createUserForm" class="h-[34px] py-0 bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs px-4 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                    <i class="fas fa-check text-xs"></i>
                    <span>Create User</span>
                </button>
            </div>
        </div>
    </div>

    {{-- MODAL 2: EDIT USER ACCOUNT --}}
    <div x-show="showEditModal" 
         x-cloak 
         class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto"
         @keydown.escape.window="showEditModal = false">
        <div @click.away="showEditModal = false" 
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden my-6 flex flex-col max-h-[90vh] animate-in fade-in zoom-in-95 duration-150">
            
            {{-- Modal Header --}}
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between flex-shrink-0">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-user-pen"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800" x-text="`Edit User: ${editUserForm.name}`"></h3>
                        <span class="text-[10px] text-slate-500 font-normal">Update account details, role and credentials</span>
                    </div>
                </div>
                <button type="button" @click="showEditModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            {{-- Form Body --}}
            <form id="editUserForm" :action="`/admin/users/${editUserForm.id}`" method="POST" class="p-4 space-y-3 flex-1 overflow-y-auto text-xs">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Full Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" x-model="editUserForm.name" required class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Email / Login ID <span class="text-rose-500">*</span></label>
                        <input type="email" name="email" x-model="editUserForm.email" required class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition font-mono">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Phone Number</label>
                        <input type="text" name="phone" x-model="editUserForm.phone" placeholder="01819-000000" class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition font-mono">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Assigned RBAC Role <span class="text-rose-500">*</span></label>
                        <select name="role" x-model="editUserForm.role" required class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                            @foreach($availableRoles as $role)
                                <option value="{{ $role->name }}">{{ $role->display_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Status</label>
                        <select name="status" x-model="editUserForm.status" class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                            <option value="active">Active (Permitted)</option>
                            <option value="suspended">Suspended</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">New Password (Optional)</label>
                        <input type="password" name="password" minlength="6" placeholder="Leave blank to keep current" class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Designation</label>
                        <input type="text" name="designation" x-model="editUserForm.designation" class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>
                </div>
            </form>

            {{-- Modal Footer --}}
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between flex-shrink-0">
                <button type="button" @click="showEditModal = false" class="h-[34px] py-0 border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 rounded-lg transition cursor-pointer">
                    Cancel
                </button>
                <button type="submit" form="editUserForm" class="h-[34px] py-0 bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs px-4 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                    <i class="fas fa-check text-xs"></i>
                    <span>Save Changes</span>
                </button>
            </div>
        </div>
    </div>

    {{-- MODAL 3: VIEW USER DETAILS --}}
    <div x-show="showDetailsModal" 
         x-cloak 
         class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto"
         @keydown.escape.window="showDetailsModal = false">
        <div @click.away="showDetailsModal = false" 
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-md overflow-hidden my-6 flex flex-col animate-in fade-in zoom-in-95 duration-150">
            
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between flex-shrink-0">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-id-card"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800" x-text="selectedUser?.name || 'User Profile'"></h3>
                        <span class="text-[10px] text-slate-500 font-normal" x-text="selectedUser?.email"></span>
                    </div>
                </div>
                <button type="button" @click="showDetailsModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <div class="p-4 space-y-3 text-xs">
                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200/80 space-y-2">
                    <div class="flex justify-between items-center">
                        <span class="text-slate-500">Staff / User ID:</span>
                        <span class="font-mono font-bold text-slate-800" x-text="selectedUser?.staff_id || ('USR-' + selectedUser?.id)"></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-500">Assigned Role:</span>
                        <span class="font-semibold text-indigo-700" x-text="selectedUser?.role_badge?.label || selectedUser?.role"></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-500">Account Status:</span>
                        <span class="font-semibold text-emerald-700" x-text="selectedUser?.status_badge?.label || selectedUser?.status"></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-500">Affiliation:</span>
                        <span class="font-medium text-slate-700" x-text="selectedUser?.scope_badge?.label || 'ISP Headquarters'"></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-500">Phone:</span>
                        <span class="font-mono text-slate-700" x-text="selectedUser?.phone || selectedUser?.mobile || 'Not provided'"></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-500">Designation:</span>
                        <span class="text-slate-700" x-text="selectedUser?.designation || 'Staff Member'"></span>
                    </div>
                </div>
            </div>

            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end flex-shrink-0">
                <button type="button" @click="showDetailsModal = false" class="h-[34px] py-0 border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-4 rounded-lg transition cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>

    {{-- MODAL 4: RESET PASSWORD --}}
    <div x-show="showPasswordModal" 
         x-cloak 
         class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto"
         @keydown.escape.window="showPasswordModal = false">
        <div @click.away="showPasswordModal = false" 
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-sm overflow-hidden my-6 flex flex-col animate-in fade-in zoom-in-95 duration-150">
            
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between flex-shrink-0">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-key"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Reset Account Password</h3>
                        <span class="text-[10px] text-slate-500 font-normal" x-text="passwordUser?.name"></span>
                    </div>
                </div>
                <button type="button" @click="showPasswordModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <form id="resetPasswordForm" :action="`/admin/users/${passwordUser?.id}/reset-password`" method="POST" class="p-4 space-y-3 text-xs">
                @csrf
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">New Password <span class="text-rose-500">*</span></label>
                    <input type="password" name="new_password" required minlength="6" placeholder="Enter new password" class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                </div>
            </form>

            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between flex-shrink-0">
                <button type="button" @click="showPasswordModal = false" class="h-[34px] py-0 border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 rounded-lg transition cursor-pointer">
                    Cancel
                </button>
                <button type="submit" form="resetPasswordForm" class="h-[34px] py-0 bg-amber-600 hover:bg-amber-700 text-white font-medium text-xs px-4 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                    <i class="fas fa-key text-xs"></i>
                    <span>Set Password</span>
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    function userManager() {
        return {
            activeMenu: null,
            menuPos: { top: 'auto', bottom: 'auto', right: 'auto', left: 'auto' },

            showCreateModal: false,
            showEditModal: false,
            showDetailsModal: false,
            showPasswordModal: false,

            selectedUser: null,
            passwordUser: null,
            editUserForm: { id: null, name: '', email: '', phone: '', role: '', status: 'active', designation: '' },

            toggleMenu(item, event) {
                if (this.activeMenu?.id === item.id) {
                    this.activeMenu = null;
                    return;
                }
                this.activeMenu = item;
                const rect = event.currentTarget.getBoundingClientRect();
                const dropdownHeight = 200;
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
                this.showCreateModal = true;
            },

            openEditModal(user) {
                this.editUserForm = {
                    id: user.id,
                    name: user.name,
                    email: user.email,
                    phone: user.phone || '',
                    role: user.role,
                    status: user.status || 'active',
                    designation: user.designation || ''
                };
                this.showEditModal = true;
            },

            openPasswordModal(user) {
                this.passwordUser = user;
                this.showPasswordModal = true;
            },

            async viewUserDetails(userId) {
                try {
                    const response = await fetch(`/admin/users/${userId}`, {
                        headers: { 'Accept': 'application/json' }
                    });
                    const data = await response.json();
                    if (data.success) {
                        this.selectedUser = data.user;
                        this.showDetailsModal = true;
                    }
                } catch (err) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to fetch user account details.',
                        confirmButtonColor: '#0891b2'
                    });
                }
            },

            toggleUserStatus(user) {
                const actionText = user.status === 'active' ? 'suspend' : 'activate';
                Swal.fire({
                    title: `${actionText.charAt(0).toUpperCase() + actionText.slice(1)} Account?`,
                    text: `Are you sure you want to ${actionText} login access for '${user.name}'?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#0891b2',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: `Yes, ${actionText}`
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = `/admin/users/${user.id}/toggle-status`;
                        form.innerHTML = `@csrf`;
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            },

            deleteUserAction(user) {
                Swal.fire({
                    title: 'Delete User Account?',
                    text: `Are you sure you want to permanently delete '${user.name}' (${user.email})?`,
                    icon: 'error',
                    showCancelButton: true,
                    confirmButtonColor: '#e11d48',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Yes, Delete Account'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = `/admin/users/${user.id}`;
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

