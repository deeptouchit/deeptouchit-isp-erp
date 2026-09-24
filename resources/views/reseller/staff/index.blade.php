@extends('reseller.layouts.app')

@section('title', 'Staff & Collectors - ' . ($tenant->company_name ?? $tenant->name ?? 'Reseller Portal'))

@push('styles')
    {{-- Page-specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="staffManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">
    
    {{-- 1. TOP HEADER BAR (AGENTS.md Rule 2.A: Icon + Title + Action buttons ONLY - Strictly No Subtitles) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-sm shadow-2xs flex-shrink-0">
                <i class="fas fa-users-gear"></i>
            </div>
            <h1 class="text-sm font-bold text-slate-800 tracking-tight">{{ __('Staff Directory') }}</h1>
        </div>
        <div class="flex items-center gap-1.5 sm:gap-2 flex-wrap sm:flex-nowrap justify-start sm:justify-end">
            <a href="{{ route('reseller.staff.print', request()->query()) }}" target="_blank" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-print text-slate-500 text-xs"></i>
                <span>{{ __('Print Statement') }}</span>
            </a>
            <a href="{{ route('reseller.staff.export', request()->query()) }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-file-csv text-slate-500 text-xs"></i>
                <span>{{ __('Export CSV') }}</span>
            </a>
            <button type="button" @click="openCreateModal = true" class="px-3.5 py-1.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-user-plus text-xs"></i>
                <span>{{ __('Add Staff') }}</span>
            </button>
        </div>
    </div>

    {{-- 2. KPI SUMMARY STRIP (Strictly 6 Cards - AGENTS.md Rule 2.B) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        
        {{-- Card 1: Total Staff --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Staff Directory') }}</span>
                <span class="text-[13px] font-bold font-mono text-cyan-800 leading-tight block truncate">
                    {{ number_format($stats['total_staff']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-cyan-200 bg-cyan-50 text-cyan-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-users"></i>
            </div>
        </div>

        {{-- Card 2: Active Staff --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Active') }}</span>
                <span class="text-[13px] font-bold font-mono text-emerald-600 leading-tight block truncate">
                    {{ number_format($stats['active_staff']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-user-check"></i>
            </div>
        </div>

        {{-- Card 3: Bill Collectors --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Collected By') }}</span>
                <span class="text-[13px] font-bold font-mono text-purple-700 leading-tight block truncate">
                    {{ number_format($stats['collectors_count']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-purple-200 bg-purple-50 text-purple-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-hand-holding-dollar"></i>
            </div>
        </div>

        {{-- Card 4: Field Technicians --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Support Center') }}</span>
                <span class="text-[13px] font-bold font-mono text-indigo-600 leading-tight block truncate">
                    {{ number_format($stats['technicians_count']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-indigo-200 bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-screwdriver-wrench"></i>
            </div>
        </div>

        {{-- Card 5: Managed Clients --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Total Customers') }}</span>
                <span class="text-[13px] font-bold font-mono text-slate-900 leading-tight block truncate">
                    {{ number_format($stats['total_customers']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-slate-200 bg-slate-50 text-slate-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-address-book"></i>
            </div>
        </div>

        {{-- Card 6: Total Collections Handled --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Total Received') }}</span>
                <span class="text-[13px] font-bold font-mono text-emerald-700 leading-tight block truncate">
                    @currency($stats['total_collected_amount'] ?? ($stats['monthly_collections'] ?? 0))
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-coins"></i>
            </div>
        </div>

    </div>

    {{-- 3. SEARCH & MULTI-FILTER TOOLBAR (AGENTS.md Rule 2.C) --}}
    <div class="bg-white p-2.5 sm:p-3 rounded-xl border border-slate-200 shadow-xs space-y-2">
        <form method="GET" action="{{ route('reseller.staff.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-12 gap-2 items-center">
            
            <!-- Search Box (MD: 4 Cols) -->
            <div class="relative md:col-span-4">
                <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                <input type="search" 
                       name="search" 
                       value="{{ $search }}" 
                       autocomplete="off"
                       placeholder="Search staff name, ID, phone..." 
                       class="w-full pl-8 pr-3 py-1.5 rounded-lg border border-slate-200 bg-slate-50 text-xs focus:bg-white focus:outline-none focus:border-cyan-500 shadow-2xs">
            </div>

            <!-- Role Filter (MD: 2 Cols) -->
            <div class="md:col-span-2">
                <select name="role" onchange="this.form.submit()" class="w-full py-1.5 px-2 rounded-lg border border-slate-200 bg-slate-50 text-xs focus:bg-white focus:outline-none focus:border-cyan-500 shadow-2xs">
                    <option value="all" {{ $roleFilter === 'all' ? 'selected' : '' }}>All Roles</option>
                    <option value="reseller_collector" {{ $roleFilter === 'reseller_collector' ? 'selected' : '' }}>Bill Collector</option>
                    <option value="reseller_technician" {{ $roleFilter === 'reseller_technician' ? 'selected' : '' }}>Field Technician</option>
                    <option value="reseller_operator" {{ $roleFilter === 'reseller_operator' ? 'selected' : '' }}>Desk Operator</option>
                    <option value="reseller_manager" {{ $roleFilter === 'reseller_manager' ? 'selected' : '' }}>Branch Manager</option>
                    <option value="reseller_admin" {{ $roleFilter === 'reseller_admin' ? 'selected' : '' }}>Partner Admin</option>
                </select>
            </div>

            <!-- Status Filter (MD: 2 Cols) -->
            <div class="md:col-span-2">
                <select name="status" onchange="this.form.submit()" class="w-full py-1.5 px-2 rounded-lg border border-slate-200 bg-slate-50 text-xs focus:bg-white focus:outline-none focus:border-cyan-500 shadow-2xs">
                    <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>All Status</option>
                    <option value="active" {{ $statusFilter === 'active' ? 'selected' : '' }}>Active Staff</option>
                    <option value="inactive" {{ $statusFilter === 'inactive' ? 'selected' : '' }}>Inactive Staff</option>
                </select>
            </div>

            <!-- Per Page (MD: 2 Cols) -->
            <div class="md:col-span-2">
                <select name="per_page" onchange="this.form.submit()" class="w-full py-1.5 px-2 rounded-lg border border-slate-200 bg-slate-50 text-xs focus:bg-white focus:outline-none focus:border-cyan-500 shadow-2xs">
                    <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10 Per Page</option>
                    <option value="20" {{ $perPage == 20 ? 'selected' : '' }}>20 Per Page</option>
                    <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50 Per Page</option>
                    <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100 Per Page</option>
                </select>
            </div>

            <!-- Strict Filter & Reset Sequence (AGENTS.md Rule 2.C - MD: 2 Cols) -->
            <div class="flex items-center gap-1.5 md:col-span-2">
                <button type="submit" class="w-1/2 bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-1.5 px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                    <i class="fas fa-filter text-[10px]"></i>
                    <span>Filter</span>
                </button>
                <a href="{{ route('reseller.staff.index') }}" class="w-1/2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-1.5 px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer">
                    <i class="fas fa-rotate-left text-[10px]"></i>
                    <span>Reset</span>
                </a>
            </div>

        </form>
    </div>

    {{-- 4. MASTER COMPACT TABLE (AGENTS.md Rule 2.D: Max 5-7 Minimal Columns) --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-10 text-center">#</th>
                        <th>Staff Member</th>
                        <th class="w-28 font-mono">Staff ID</th>
                        <th class="w-40">Role &amp; Designation</th>
                        <th class="w-32 font-mono">Contact Phone</th>
                        <th class="w-24 text-center font-mono">Clients</th>
                        <th class="w-20 text-center">Status</th>
                        <th class="w-10 text-center no-sort">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($staffMembers as $index => $staff)
                        <tr>
                            <!-- 1. Index -->
                            <td class="text-center font-mono text-slate-500">
                                {{ ($staffMembers->currentPage() - 1) * $staffMembers->perPage() + $index + 1 }}
                            </td>

                            <!-- 2. Staff Member (Single Clean Field) -->
                            <td class="font-semibold text-cyan-800">
                                <button type="button" class="hover:underline cursor-pointer font-semibold text-left" @click="viewDetails({{ Js::from($staff) }})">
                                    {{ $staff->name }}
                                </button>
                            </td>

                            <!-- 3. Staff ID -->
                            <td class="font-mono text-slate-700">
                                {{ $staff->staff_id ?: ('STF-' . $staff->id) }}
                            </td>

                            <!-- 4. Role & Designation -->
                            <td class="text-slate-700">
                                {{ $staff->designation ?: ucwords(str_replace(['reseller_', '_'], ['', ' '], $staff->role)) }}
                            </td>

                            <!-- 5. Contact Phone -->
                            <td class="font-mono text-slate-800">
                                {{ $staff->phone ?: ($staff->mobile ?: '—') }}
                            </td>

                            <!-- 6. Assigned Clients -->
                            <td class="text-center font-mono font-bold text-cyan-800">
                                {{ $staff->assigned_customers_count ?? 0 }}
                            </td>

                            <!-- 7. Status -->
                            <td class="text-center">
                                @if($staff->status === 'active')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold font-mono bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <i class="fas fa-circle text-[6px]"></i>
                                        <span>ACTIVE</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold font-mono bg-slate-100 text-slate-600 border border-slate-200">
                                        <i class="fas fa-pause text-[6px]"></i>
                                        <span>INACTIVE</span>
                                    </span>
                                @endif
                            </td>

                            <!-- 8. Action: Floating 3-Dot Button (AGENTS.md Rule 2.E) -->
                            <td class="text-center">
                                <button type="button" 
                                        @click.stop="toggleMenu({{ Js::from($staff) }}, $event)" 
                                        class="w-7 h-7 rounded-lg hover:bg-slate-100 text-slate-500 hover:text-slate-800 transition flex items-center justify-center mx-auto cursor-pointer">
                                    <i class="fas fa-ellipsis-v text-[10px]"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-8 text-slate-400 text-xs">
                                <i class="fas fa-users-gear text-3xl mb-2 block text-slate-300"></i>
                                <span>No staff members found matching your search.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($staffMembers->hasPages())
            <div class="px-4 py-2.5 bg-white border-t border-slate-200 flex items-center justify-between text-xs">
                <div>
                    Showing <span class="font-bold">{{ $staffMembers->firstItem() }}</span> to <span class="font-bold">{{ $staffMembers->lastItem() }}</span> of <span class="font-bold">{{ $staffMembers->total() }}</span> staff
                </div>
                <div>
                    {{ $staffMembers->links() }}
                </div>
            </div>
        @endif
    </div>

    {{-- 5. FLOATING 3-DOT ACTION MENU (AGENTS.md Rule 2.E) --}}
    <div x-show="activeMenu !== null" 
         x-cloak 
         @click.outside="activeMenu = null"
         :style="menuPos"
         class="fixed z-50 w-48 bg-white rounded-xl border border-slate-200 shadow-xl py-1 text-xs text-slate-700 font-medium space-y-0.5"
         style="display: none;">
        
        <button type="button" 
                @click="viewDetails(activeMenu); activeMenu = null" 
                class="w-full px-3 py-1.5 text-left hover:bg-slate-50 hover:text-cyan-700 flex items-center gap-2 cursor-pointer transition">
            <i class="fas fa-id-card text-cyan-600 w-4"></i>
            <span>View Profile</span>
        </button>
        <button type="button" 
                @click="openEdit(activeMenu); activeMenu = null" 
                class="w-full px-3 py-1.5 text-left hover:bg-slate-50 hover:text-blue-600 flex items-center gap-2 cursor-pointer transition">
            <i class="fas fa-user-pen text-blue-600 w-4"></i>
            <span>Edit Details</span>
        </button>
        <button type="button" 
                @click="toggleStatusAction(activeMenu?.id); activeMenu = null" 
                class="w-full px-3 py-1.5 text-left hover:bg-slate-50 hover:text-amber-600 flex items-center gap-2 cursor-pointer transition">
            <i class="fas fa-power-off text-amber-600 w-4"></i>
            <span x-text="activeMenu?.status === 'active' ? 'Disable Account' : 'Enable Account'">Toggle Status</span>
        </button>
        <button type="button" 
                @click="deleteStaffAction(activeMenu?.id, activeMenu?.name); activeMenu = null" 
                class="w-full px-3 py-1.5 text-left text-rose-600 hover:bg-rose-50 flex items-center gap-2 cursor-pointer transition border-t border-slate-100">
            <i class="fas fa-trash-alt text-rose-600 w-4"></i>
            <span>Delete Staff</span>
        </button>
    </div>

    {{-- 6. ADD STAFF MEMBER MODAL (AGENTS.md Rule 3: Natural Soft Modal) --}}
    <div x-show="openCreateModal" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50 overflow-y-auto"
         style="display: none;"
         x-cloak>
        
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden my-6" 
             @click.outside="openCreateModal = false">
            
            <!-- Soft Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Add Staff Member</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">Create staff, field technician or collector login account</p>
                    </div>
                </div>
                <button type="button" @click="openCreateModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <!-- Body Form -->
            <form action="{{ route('reseller.staff.store') }}" method="POST">
                @csrf
                <div class="p-4 space-y-3">
                    <!-- Full Name & Phone -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="create_name" class="block text-[11px] font-semibold text-slate-700 mb-1">
                                Full Name <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" 
                                   id="create_name" 
                                   name="name" 
                                   required 
                                   placeholder="e.g. Md. Shakil Ahmed" 
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                        </div>
                        <div>
                            <label for="create_phone" class="block text-[11px] font-semibold text-slate-700 mb-1">
                                Mobile / Phone <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" 
                                   id="create_phone" 
                                   name="phone" 
                                   required 
                                   placeholder="01819-XXXXXX" 
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                        </div>
                    </div>

                    <!-- Email & Password -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="create_email" class="block text-[11px] font-semibold text-slate-700 mb-1">
                                Email Address <span class="text-rose-500">*</span>
                            </label>
                            <input type="email" 
                                   id="create_email" 
                                   name="email" 
                                   required 
                                   placeholder="staff@partner.com" 
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                        </div>
                        <div>
                            <label for="create_password" class="block text-[11px] font-semibold text-slate-700 mb-1">
                                Account Password <span class="text-rose-500">*</span>
                            </label>
                            <input type="password" 
                                   id="create_password" 
                                   name="password" 
                                   required 
                                   minlength="6" 
                                   placeholder="Min 6 characters" 
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                        </div>
                    </div>

                    <!-- Role & Designation -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="create_role" class="block text-[11px] font-semibold text-slate-700 mb-1">
                                Staff Role <span class="text-rose-500">*</span>
                            </label>
                            <select id="create_role" name="role" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                                <option value="reseller_collector">Bill Collector</option>
                                <option value="reseller_technician">Field Technician / Lineman</option>
                                <option value="reseller_operator">Desk Operator / Support</option>
                                <option value="reseller_manager">Branch Manager</option>
                                <option value="reseller_admin">Partner Admin</option>
                            </select>
                        </div>
                        <div>
                            <label for="create_designation" class="block text-[11px] font-semibold text-slate-700 mb-1">
                                Designation Title
                            </label>
                            <input type="text" 
                                   id="create_designation" 
                                   name="designation" 
                                   placeholder="e.g. Senior Area Collector" 
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                        </div>
                    </div>

                    <!-- Salary & Commission Rate -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="create_salary" class="block text-[11px] font-semibold text-slate-700 mb-1">
                                Monthly Salary ({{ $currencySymbol ?? '৳' }})
                            </label>
                            <input type="number" 
                                   id="create_salary" 
                                   name="salary_amount" 
                                   step="100" 
                                   min="0" 
                                   value="0" 
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                        </div>
                        <div>
                            <label for="create_comm" class="block text-[11px] font-semibold text-slate-700 mb-1">
                                Collection Commission (%)
                            </label>
                            <input type="number" 
                                   id="create_comm" 
                                   name="collection_commission_rate" 
                                   step="0.5" 
                                   min="0" 
                                   max="100" 
                                   value="0" 
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                        </div>
                    </div>

                    <!-- Address -->
                    <div>
                        <label for="create_address" class="block text-[11px] font-semibold text-slate-700 mb-1">
                            Address &amp; Territory Assignment
                        </label>
                        <input type="text" 
                               id="create_address" 
                               name="address" 
                               placeholder="e.g. Halishahar Area, Block B" 
                               class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="openCreateModal = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" class="bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition cursor-pointer">
                        Save Staff Member
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- 7. EDIT STAFF MEMBER MODAL --}}
    <div x-show="editStaffData" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50 overflow-y-auto"
         style="display: none;"
         x-cloak>
        
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden my-6" 
             @click.outside="editStaffData = null">
            
            <!-- Soft Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-user-pen"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Edit Staff Member</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal" x-text="editStaffData?.name || 'Update details'"></p>
                    </div>
                </div>
                <button type="button" @click="editStaffData = null" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <!-- Body Form -->
            <form :action="`/reseller/staff/${editStaffData?.id}`" method="POST">
                @csrf
                @method('PUT')
                <div class="p-4 space-y-3">
                    <!-- Full Name & Phone -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Full Name <span class="text-rose-500">*</span></label>
                            <input type="text" name="name" :value="editStaffData?.name" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Mobile / Phone <span class="text-rose-500">*</span></label>
                            <input type="text" name="phone" :value="editStaffData?.phone || editStaffData?.mobile" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                        </div>
                    </div>

                    <!-- Email & Password (optional) -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Email Address <span class="text-rose-500">*</span></label>
                            <input type="email" name="email" :value="editStaffData?.email" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Password (Leave blank to keep current)</label>
                            <input type="password" name="password" minlength="6" placeholder="******" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                        </div>
                    </div>

                    <!-- Role & Designation -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Staff Role <span class="text-rose-500">*</span></label>
                            <select name="role" :value="editStaffData?.role" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                                <option value="reseller_collector">Bill Collector</option>
                                <option value="reseller_technician">Field Technician / Lineman</option>
                                <option value="reseller_operator">Desk Operator / Support</option>
                                <option value="reseller_manager">Branch Manager</option>
                                <option value="reseller_admin">Partner Admin</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Designation Title</label>
                            <input type="text" name="designation" :value="editStaffData?.designation" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                        </div>
                    </div>

                    <!-- Salary & Commission -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Monthly Salary ({{ $currencySymbol ?? '৳' }})</label>
                            <input type="number" name="salary_amount" :value="editStaffData?.salary_amount || 0" step="100" min="0" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Collection Commission (%)</label>
                            <input type="number" name="collection_commission_rate" :value="editStaffData?.collection_commission_rate || 0" step="0.5" min="0" max="100" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                        </div>
                    </div>

                    <!-- Address & Status -->
                    <div class="grid grid-cols-3 gap-3">
                        <div class="col-span-2">
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Address &amp; Territory Assignment</label>
                            <input type="text" name="address" :value="editStaffData?.address" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Account Status</label>
                            <select name="status" :value="editStaffData?.status" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="editStaffData = null" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition cursor-pointer">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- 8. STAFF PROFILE DETAILS MODAL --}}
    <div x-show="selectedStaff" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50 overflow-y-auto"
         style="display: none;"
         x-cloak>
        
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden my-6" 
             @click.outside="selectedStaff = null">
            
            <!-- Soft Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-id-card"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800" x-text="selectedStaff?.name || 'Staff Profile'"></h3>
                        <p class="text-[10.5px] text-slate-500 font-normal" x-text="'ID: ' + (selectedStaff?.staff_id || ('STF-' + selectedStaff?.id))"></p>
                    </div>
                </div>
                <button type="button" @click="selectedStaff = null" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <!-- Body Details Grid -->
            <div class="p-4 space-y-3">
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[9.5px] font-medium text-slate-500 block uppercase">Role / Position</span>
                        <span class="font-bold text-slate-800 block" x-text="selectedStaff?.designation || selectedStaff?.role"></span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[9.5px] font-medium text-slate-500 block uppercase">Phone Contact</span>
                        <span class="font-mono font-bold text-slate-800 block" x-text="selectedStaff?.phone || selectedStaff?.mobile || '—'"></span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[9.5px] font-medium text-slate-500 block uppercase">Email Address</span>
                        <span class="font-mono font-bold text-slate-800 block truncate" x-text="selectedStaff?.email || '—'"></span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[9.5px] font-medium text-slate-500 block uppercase">Assigned Clients</span>
                        <span class="font-mono font-bold text-cyan-800 block" x-text="selectedStaff?.assigned_customers_count || 0"></span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[9.5px] font-medium text-slate-500 block uppercase">Monthly Salary</span>
                        <span class="font-mono font-bold text-emerald-700 block" x-text="'{{ $currencySymbol ?? '৳' }}' + (Number(selectedStaff?.salary_amount || 0).toLocaleString())"></span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[9.5px] font-medium text-slate-500 block uppercase">Account Status</span>
                        <span class="font-mono font-bold block" :class="selectedStaff?.status === 'active' ? 'text-emerald-600' : 'text-slate-600'" x-text="(selectedStaff?.status || 'active').toUpperCase()"></span>
                    </div>
                </div>

                <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80 text-xs">
                    <span class="text-[9.5px] font-medium text-slate-500 block uppercase">Address &amp; Territory</span>
                    <span class="font-semibold text-slate-700 block mt-0.5" x-text="selectedStaff?.address || 'Standard Partner Territory'"></span>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end">
                <button type="button" @click="selectedStaff = null" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function staffManager() {
    return {
        openCreateModal: false,
        editStaffData: null,
        selectedStaff: null,
        activeMenu: null,
        menuPos: {},

        toggleMenu(item, event) {
            if (this.activeMenu?.id === item.id) {
                this.activeMenu = null;
                return;
            }
            this.activeMenu = item;
            const rect = event.currentTarget.getBoundingClientRect();
            const dropdownHeight = 160;
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

        viewDetails(staff) {
            this.selectedStaff = staff;
        },

        openEdit(staff) {
            this.editStaffData = { ...staff };
        },

        async toggleStatusAction(id) {
            if (!id) return;
            try {
                const response = await fetch(`/reseller/staff/${id}/toggle-status`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Status Updated',
                            text: data.message,
                            timer: 1200,
                            showConfirmButton: false,
                            toast: true,
                            position: 'top-end'
                        });
                    }
                    setTimeout(() => location.reload(), 600);
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                    }
                }
            } catch (e) {
                console.error('Failed to toggle status:', e);
                location.reload();
            }
        },

        async deleteStaffAction(id, name) {
            if (!id) return;

            if (typeof Swal !== 'undefined') {
                const result = await Swal.fire({
                    title: 'Delete Staff Member?',
                    text: `Are you sure you want to remove "${name}" from your staff roster?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Delete Staff',
                    cancelButtonText: 'Cancel',
                    customClass: {
                        confirmButton: 'bg-rose-600 hover:bg-rose-700 text-white font-semibold text-xs px-4 py-2 rounded-lg mr-2 cursor-pointer',
                        cancelButton: 'bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs px-4 py-2 rounded-lg border cursor-pointer'
                    },
                    buttonsStyling: false
                });
                if (!result.isConfirmed) return;
            } else {
                if (!confirm(`Are you sure you want to delete "${name}"?`)) return;
            }

            try {
                const response = await fetch(`/reseller/staff/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    if (typeof Swal !== 'undefined') {
                        await Swal.fire({
                            icon: 'success',
                            title: 'Deleted',
                            text: data.message,
                            timer: 1200,
                            showConfirmButton: false,
                            toast: true,
                            position: 'top-end'
                        });
                    }
                    location.reload();
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                    }
                }
            } catch (e) {
                console.error('Delete failed:', e);
                location.reload();
            }
        }
    };
}
</script>
@endpush
