@extends('tenant.layouts.app')

@section('title', 'All Employees Directory - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
    {{-- Page specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="employeeManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">

    <!-- Toast Notification Banner -->
    <div x-show="toast.show" 
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         class="fixed top-4 right-4 z-50 flex items-center gap-2 px-4 py-2.5 rounded-xl shadow-lg border text-xs font-medium"
         :class="{
             'bg-emerald-50 text-emerald-800 border-emerald-200': toast.type === 'success',
             'bg-rose-50 text-rose-800 border-rose-200': toast.type === 'error',
             'bg-blue-50 text-blue-800 border-blue-200': toast.type === 'info'
         }"
         style="display: none;">
        <i class="fas text-sm" :class="{
            'fa-check-circle text-emerald-600': toast.type === 'success',
            'fa-exclamation-circle text-rose-600': toast.type === 'error',
            'fa-info-circle text-blue-600': toast.type === 'info'
        }"></i>
        <span x-text="toast.message"></span>
    </div>

    <!-- 1. Top Header Bar (Strictly Icon + Title + Action Button ONLY - No Subtitle) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-3">
            <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center text-xs flex-shrink-0">
                <i class="fas fa-user-tie"></i>
            </div>
            <h1 class="text-sm font-bold text-slate-900 tracking-tight">All Employees Directory</h1>
        </div>

        <div class="flex items-center gap-2">
            <!-- Onboard New Employee Button -->
            <button type="button" 
                    @click="openAddModal()"
                    class="px-3.5 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-user-plus text-[11px]"></i>
                <span>+ Onboard New Employee</span>
            </button>
        </div>
    </div>

    <!-- 2. KPI Summary Strip (Strictly 6 Cards) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        <!-- Metric 1: Total Workforce -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-slate-500 uppercase tracking-wider block truncate">Total Workforce</span>
                <span class="text-[13px] font-bold text-slate-900 font-mono leading-tight block">{{ number_format($totalEmployees) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-indigo-50 text-indigo-600 flex items-center justify-center text-[10px] border border-indigo-100 flex-shrink-0">
                <i class="fas fa-users"></i>
            </div>
        </div>

        <!-- Metric 2: Operations Managers -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-blue-700 uppercase tracking-wider block truncate">Managers</span>
                <span class="text-[13px] font-bold text-blue-600 font-mono leading-tight block">{{ number_format($managersCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-blue-50 text-blue-600 flex items-center justify-center text-[10px] border border-blue-100 flex-shrink-0">
                <i class="fas fa-user-tie"></i>
            </div>
        </div>

        <!-- Metric 3: Field Techs & NOC -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-emerald-700 uppercase tracking-wider block truncate">Technicians</span>
                <span class="text-[13px] font-bold text-emerald-600 font-mono leading-tight block">{{ number_format($techniciansCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-emerald-50 text-emerald-600 flex items-center justify-center text-[10px] border border-emerald-100 flex-shrink-0">
                <i class="fas fa-screwdriver-wrench"></i>
            </div>
        </div>

        <!-- Metric 4: Bill Collectors -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-amber-700 uppercase tracking-wider block truncate">Bill Collectors</span>
                <span class="text-[13px] font-bold text-amber-600 font-mono leading-tight block">{{ number_format($collectorsCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-amber-50 text-amber-600 flex items-center justify-center text-[10px] border border-amber-100 flex-shrink-0">
                <i class="fas fa-money-bill-wave"></i>
            </div>
        </div>

        <!-- Metric 5: ISP Core HQ Staff -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-purple-700 uppercase tracking-wider block truncate">ISP Core HQ</span>
                <span class="text-[13px] font-bold text-purple-600 font-mono leading-tight block">{{ number_format($ispStaffCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-purple-50 text-purple-600 flex items-center justify-center text-[10px] border border-purple-100 flex-shrink-0">
                <i class="fas fa-building"></i>
            </div>
        </div>

        <!-- Metric 6: Active Staff Status -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-teal-700 uppercase tracking-wider block truncate">Active on Duty</span>
                <span class="text-[13px] font-bold text-teal-600 font-mono leading-tight block">{{ number_format($activeStaffCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-teal-50 text-teal-600 flex items-center justify-center text-[10px] border border-teal-100 flex-shrink-0">
                <i class="fas fa-circle-check"></i>
            </div>
        </div>
    </div>

    <!-- 3. Search & Multi-Filter Toolbar -->
    <div class="bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('tenant.staff.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-7 gap-2.5 items-center">
            
            <!-- Search Input -->
            <div class="relative sm:col-span-2 lg:col-span-2">
                <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                <input type="text" 
                       name="search" 
                       value="{{ request('search') }}" 
                       placeholder="Search Name, Email, Mobile, Staff ID..." 
                       class="w-full pl-8 pr-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-cyan-500 focus:outline-none transition" />
            </div>

            <!-- Scope Filter -->
            <div>
                <select name="scope" 
                        onchange="this.form.submit()"
                        class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                    <option value="">All Scopes</option>
                    <option value="isp" {{ request('scope') === 'isp' ? 'selected' : '' }}>ISP Core HQ</option>
                    <option value="reseller" {{ request('scope') === 'reseller' ? 'selected' : '' }}>Sub-ISP Franchise</option>
                </select>
            </div>

            <!-- Reseller Filter -->
            <div>
                <select name="reseller_id" 
                        onchange="this.form.submit()"
                        class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                    <option value="">All Sub-ISPs</option>
                    @foreach($allResellers as $res)
                        <option value="{{ $res->id }}" {{ (string)request('reseller_id') === (string)$res->id ? 'selected' : '' }}>
                            {{ $res->name }} ({{ $res->code }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Role Filter -->
            <div>
                <select name="role" 
                        onchange="this.form.submit()"
                        class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                    <option value="">All Roles</option>
                    @if(isset($availableRoles) && $availableRoles->count() > 0)
                        @foreach($availableRoles as $r)
                            <option value="{{ $r->name }}" {{ request('role') === $r->name ? 'selected' : '' }}>{{ $r->display_name }}</option>
                        @endforeach
                    @else
                        <option value="isp_admin" {{ request('role') === 'isp_admin' ? 'selected' : '' }}>ISP Super Admin</option>
                        <option value="isp_manager" {{ request('role') === 'isp_manager' ? 'selected' : '' }}>ISP Manager</option>
                        <option value="isp_technician" {{ request('role') === 'isp_technician' ? 'selected' : '' }}>NOC / Field Tech</option>
                        <option value="isp_collector" {{ request('role') === 'isp_collector' ? 'selected' : '' }}>Bill Collector</option>
                    @endif
                </select>
            </div>

            <!-- Status & Per Page in 1 Grid Item on Desktop -->
            <div>
                <select name="status" 
                        onchange="this.form.submit()"
                        class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Suspended</option>
                </select>
            </div>

            <!-- Action Buttons (Strict Universal Standard) -->
            <div class="flex items-center gap-1.5 w-full sm:w-auto flex-shrink-0">
                <button type="submit" 
                        class="flex-1 sm:flex-initial bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-1.5 px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer" 
                        title="Apply Filters">
                    <i class="fas fa-filter text-[10px]"></i>
                    <span>Filter</span>
                </button>
                <a href="{{ route('tenant.staff.index') }}" 
                   class="flex-1 sm:flex-initial bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-1.5 px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer" 
                   title="Reset Filters">
                    <i class="fas fa-rotate-left text-[10px]"></i>
                    <span>Reset</span>
                </a>
            </div>
        </form>
    </div>

    <!-- 4. Master Table (<table class="saas-table">) -->
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-12 text-center">#</th>
                        <th>Staff ID</th>
                        <th>Name</th>
                        <th class="text-center">Role</th>
                        <th>Scope / Partner</th>
                        <th>Mobile</th>
                        <th class="text-center">Status</th>
                        <th class="w-14 text-center no-sort">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $index => $emp)
                        @php
                            $roleBadge = $emp->role_badge;
                            $scopeBadge = $emp->scope_badge;
                            $statusBadge = $emp->status_badge;
                            $staffCode = $emp->staff_id ?: ('STF-' . str_pad($emp->id, 4, '0', STR_PAD_LEFT));
                        @endphp
                        <tr>
                            <!-- Row Index -->
                            <td class="text-center font-mono text-slate-400">
                                {{ $employees->firstItem() + $index }}
                            </td>

                            <!-- Staff ID (Single-data) -->
                            <td class="font-mono text-slate-600 font-medium">
                                {{ $staffCode }}
                            </td>

                            <!-- Name (Single-data) -->
                            <td class="font-semibold text-slate-800">
                                {{ $emp->name }}
                            </td>

                            <!-- Role (Single-data) -->
                            <td class="text-center">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] border {{ $roleBadge['class'] }}">
                                    <i class="fas {{ $roleBadge['icon'] }} text-[9px]"></i>
                                    <span>{{ $roleBadge['label'] }}</span>
                                </span>
                            </td>

                            <!-- Scope / Partner (Single-data) -->
                            <td>
                                @if($emp->reseller)
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] bg-indigo-50 text-indigo-700 border border-indigo-200">
                                        <i class="fas fa-handshake text-[8px]"></i>
                                        <span>{{ $emp->reseller->name }}</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] bg-purple-50 text-purple-700 border border-purple-200">
                                        <i class="fas fa-building text-[8px]"></i>
                                        <span>ISP Headquarters</span>
                                    </span>
                                @endif
                            </td>

                            <!-- Mobile (Single-data) -->
                            <td class="font-mono text-slate-700">
                                {{ $emp->mobile ?: ($emp->phone ?: '--') }}
                            </td>

                            <!-- Status (Single-data) -->
                            <td class="text-center">
                                <button type="button" 
                                        @click="toggleStatus({{ $emp->id }})"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] border transition cursor-pointer {{ $statusBadge['class'] }} hover:opacity-80"
                                        title="Click to toggle status">
                                    <i class="fas {{ $statusBadge['icon'] }} text-[9px]"></i>
                                    <span>{{ $statusBadge['label'] }}</span>
                                </button>
                            </td>

                            <!-- Floating Action Menu Button -->
                            <td class="text-center">
                                <button type="button" 
                                        @click="toggleMenu({{ Js::from($emp) }}, $event)" 
                                        class="w-7 h-7 rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-600 flex items-center justify-center transition cursor-pointer mx-auto">
                                    <i class="fas fa-ellipsis-v text-[10px]"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-8 text-slate-400">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <i class="fas fa-user-tie text-2xl text-slate-300"></i>
                                    <span class="text-xs font-medium">No employees found matching your criteria.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Native Laravel Pagination -->
        @if($employees->hasPages())
            <div class="px-4 py-2.5 border-t border-slate-200 bg-slate-50/50">
                {{ $employees->links() }}
            </div>
        @endif
    </div>

    <!-- 5. Global Floating 3-Dot Action Dropdown Menu -->
    <div x-show="activeMenu !== null" 
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="fixed z-50 w-52 bg-white rounded-xl shadow-xl border border-slate-200/90 py-1 text-xs text-slate-700 divide-y divide-slate-100"
         :style="`top: ${menuPos.top}; bottom: ${menuPos.bottom}; right: ${menuPos.right}; left: ${menuPos.left};`"
         @click.outside="activeMenu = null"
         style="display: none;">
        
        <!-- Group 1: Diagnostics -->
        <div class="py-1">
            <button type="button" 
                    @click="const emp = activeMenu; activeMenu = null; viewEmployee(emp.id)" 
                    class="w-full px-3 py-1.5 hover:bg-indigo-50 hover:text-indigo-700 flex items-center gap-2 transition text-left cursor-pointer">
                <i class="fas fa-id-card text-indigo-500 w-4 text-center text-[11px]"></i>
                <span>View Full Profile</span>
            </button>
        </div>

        <!-- Group 2: Edit & Security -->
        <div class="py-1">
            <button type="button" 
                    @click="const emp = activeMenu; activeMenu = null; openEditModal(emp)" 
                    class="w-full px-3 py-1.5 hover:bg-purple-50 hover:text-purple-700 flex items-center gap-2 transition text-left cursor-pointer">
                <i class="fas fa-edit text-purple-500 w-4 text-center text-[11px]"></i>
                <span>Edit Employee Details</span>
            </button>

            <button type="button" 
                    @click="const emp = activeMenu; activeMenu = null; openPasswordModal(emp.id, emp.name)" 
                    class="w-full px-3 py-1.5 hover:bg-amber-50 hover:text-amber-700 flex items-center gap-2 transition text-left cursor-pointer">
                <i class="fas fa-key text-amber-500 w-4 text-center text-[11px]"></i>
                <span>Reset Login Password</span>
            </button>
        </div>

        <!-- Group 3: Status Toggle -->
        <div class="py-1">
            <button type="button" 
                    @click="const emp = activeMenu; activeMenu = null; toggleStatus(emp.id)" 
                    class="w-full px-3 py-1.5 hover:bg-slate-50 text-slate-700 flex items-center gap-2 transition text-left cursor-pointer">
                <i class="fas w-4 text-center text-[11px]" :class="activeMenu?.status === 'active' ? 'fa-user-lock text-rose-500' : 'fa-user-check text-emerald-500'"></i>
                <span x-text="activeMenu?.status === 'active' ? 'Suspend Employee' : 'Activate Employee'"></span>
            </button>
        </div>
    </div>

    <!-- 6. Production-Grade Natural Modal: Add New Employee (Onboard Modal) -->
    <div x-show="showAddModal" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4"
         style="display: none;">
        
        <div @click.outside="showAddModal = false" 
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-xl overflow-hidden">
            
            <!-- Soft Natural Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Onboard New Employee</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">Configure role permissions, scope binding &amp; credentials</p>
                    </div>
                </div>
                <button type="button" @click="showAddModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <!-- Modal Form Body -->
            <form @submit.prevent="submitAddEmployee()">
                <div class="p-4 space-y-3.5 text-xs max-h-[75vh] overflow-y-auto">
                    
                    <!-- Scope Binding Selector -->
                    <div>
                        <label class="block text-[10.5px] font-bold text-slate-700 uppercase tracking-wider mb-1.5">Affiliation Scope <span class="text-rose-500">*</span></label>
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" 
                                    @click="addForm.scope = 'ISP_CORE'; addForm.reseller_id = ''; onScopeChange()"
                                    class="py-2 px-3 rounded-lg border text-xs font-semibold transition flex items-center justify-center gap-2 cursor-pointer"
                                    :class="addForm.scope === 'ISP_CORE' ? 'bg-purple-50 border-purple-300 text-purple-800 ring-1 ring-purple-400 shadow-2xs' : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50'">
                                <i class="fas fa-building text-purple-600 text-xs"></i>
                                <span>ISP Headquarters</span>
                            </button>

                            <button type="button" 
                                    @click="addForm.scope = 'RESELLER'; onScopeChange()"
                                    class="py-2 px-3 rounded-lg border text-xs font-semibold transition flex items-center justify-center gap-2 cursor-pointer"
                                    :class="addForm.scope === 'RESELLER' ? 'bg-indigo-50 border-indigo-300 text-indigo-800 ring-1 ring-indigo-400 shadow-2xs' : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50'">
                                <i class="fas fa-handshake text-indigo-600 text-xs"></i>
                                <span>Sub-ISP Franchise</span>
                            </button>
                        </div>
                    </div>

                    <!-- Reseller Picker (if Sub-ISP) -->
                    <div x-show="addForm.scope === 'RESELLER'" class="p-2.5 bg-indigo-50/50 rounded-lg border border-indigo-100 space-y-1">
                        <label class="block text-[10.5px] font-bold text-indigo-900 uppercase tracking-wider">Select Sub-ISP Partner <span class="text-rose-500">*</span></label>
                        <select x-model="addForm.reseller_id" 
                                :required="addForm.scope === 'RESELLER'"
                                class="w-full px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs text-slate-800 focus:border-indigo-500 focus:outline-hidden transition">
                            <option value="">-- Choose Sub-ISP Partner --</option>
                            @foreach($allResellers as $res)
                                <option value="{{ $res->id }}">{{ $res->name }} ({{ $res->code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- System Role -->
                    <div>
                        <label class="block text-[10.5px] font-bold text-slate-700 uppercase tracking-wider mb-1">System Role &amp; Access Scope <span class="text-rose-500">*</span></label>
                        <select x-model="addForm.role" 
                                required
                                class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-indigo-500 focus:outline-hidden transition">
                            @if(isset($availableRoles) && $availableRoles->count() > 0)
                                @foreach($availableRoles as $r)
                                    <option value="{{ $r->name }}">{{ $r->display_name }} ({{ $r->is_system ? 'System' : 'Custom' }})</option>
                                @endforeach
                            @else
                                <option value="isp_manager">ISP Manager (Operations &amp; Team Lead)</option>
                                <option value="isp_technician">ISP Technician / NOC Field Engineer</option>
                                <option value="isp_collector">ISP Bill Collector (Retail Billing)</option>
                                <option value="isp_admin">ISP Administrator</option>
                            @endif
                        </select>
                    </div>

                    <!-- Name & Email Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-[10.5px] font-bold text-slate-700 uppercase tracking-wider mb-1">Full Name <span class="text-rose-500">*</span></label>
                            <input type="text" 
                                   x-model="addForm.name" 
                                   required 
                                   placeholder="e.g. Md. Hasan Ali" 
                                   class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-900 focus:bg-white focus:border-indigo-500 focus:outline-hidden transition" />
                        </div>

                        <div>
                            <label class="block text-[10.5px] font-bold text-slate-700 uppercase tracking-wider mb-1">Email Address <span class="text-rose-500">*</span></label>
                            <input type="email" 
                                   x-model="addForm.email" 
                                   required 
                                   placeholder="hasan@somitysoft.com" 
                                   class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-900 focus:bg-white focus:border-indigo-500 focus:outline-hidden transition" />
                        </div>
                    </div>

                    <!-- Mobile & Designation Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-[10.5px] font-bold text-slate-700 uppercase tracking-wider mb-1">Mobile Number</label>
                            <input type="text" 
                                   x-model="addForm.mobile" 
                                   placeholder="017XX-XXXXXX" 
                                   class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-900 focus:bg-white focus:border-indigo-500 focus:outline-hidden transition" />
                        </div>

                        <div>
                            <label class="block text-[10.5px] font-bold text-slate-700 uppercase tracking-wider mb-1">Designation</label>
                            <input type="text" 
                                   x-model="addForm.designation" 
                                   placeholder="e.g. Senior NOC Engineer" 
                                   class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-900 focus:bg-white focus:border-indigo-500 focus:outline-hidden transition" />
                        </div>
                    </div>

                    <!-- Shift & Password Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-[10.5px] font-bold text-slate-700 uppercase tracking-wider mb-1">Duty Shift</label>
                            <select x-model="addForm.shift" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-indigo-500 focus:outline-hidden transition">
                                <option value="Morning Shift (09:00 - 18:00)">Morning Shift (09:00 - 18:00)</option>
                                <option value="Evening Shift (14:00 - 22:00)">Evening Shift (14:00 - 22:00)</option>
                                <option value="Night NOC Shift (22:00 - 08:00)">Night NOC Shift (22:00 - 08:00)</option>
                                <option value="Full Day Field Duty">Full Day Field Duty</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[10.5px] font-bold text-slate-700 uppercase tracking-wider mb-1">Login Password <span class="text-rose-500">*</span></label>
                            <input type="password" 
                                   x-model="addForm.password" 
                                   required 
                                   minlength="6"
                                   placeholder="Min 6 characters" 
                                   class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-900 focus:bg-white focus:border-indigo-500 focus:outline-hidden transition" />
                        </div>
                    </div>

                    <!-- Address -->
                    <div>
                        <label class="block text-[10.5px] font-bold text-slate-700 uppercase tracking-wider mb-1">Permanent / Present Address</label>
                        <input type="text" 
                               x-model="addForm.address" 
                               placeholder="e.g. Sector 7, Uttara, Dhaka" 
                               class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-indigo-500 focus:outline-hidden transition" />
                    </div>

                </div>

                <!-- Modal Footer -->
                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" 
                            @click="showAddModal = false" 
                            class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" 
                            :disabled="isSubmitting"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50">
                        <i class="fas fa-spinner fa-spin" x-show="isSubmitting" style="display: none;"></i>
                        <span>Onboard Employee</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 7. Production-Grade Natural Modal: Edit Employee Profile -->
    <div x-show="showEditModal" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4"
         style="display: none;">
        
        <div @click.outside="showEditModal = false" 
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-xl overflow-hidden">
            
            <!-- Soft Natural Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-purple-50 text-purple-600 border border-purple-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-user-pen"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Edit Employee Profile</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">Update contact details, role permissions &amp; affiliation</p>
                    </div>
                </div>
                <button type="button" @click="showEditModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <!-- Modal Form Body -->
            <form @submit.prevent="submitEditEmployee()">
                <div class="p-4 space-y-3.5 text-xs max-h-[75vh] overflow-y-auto">
                    
                    <!-- Role Selector -->
                    <div>
                        <label class="block text-[10.5px] font-bold text-slate-700 uppercase tracking-wider mb-1">System Role <span class="text-rose-500">*</span></label>
                        <select x-model="editForm.role" 
                                required
                                class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-purple-500 focus:outline-hidden transition">
                            @if(isset($availableRoles) && $availableRoles->count() > 0)
                                @foreach($availableRoles as $r)
                                    <option value="{{ $r->name }}">{{ $r->display_name }} ({{ $r->is_system ? 'System' : 'Custom' }})</option>
                                @endforeach
                            @else
                                <option value="isp_manager">ISP Manager (Operations &amp; Team Lead)</option>
                                <option value="isp_technician">ISP NOC / Field Tech</option>
                                <option value="isp_collector">ISP Bill Collector</option>
                                <option value="isp_admin">ISP Administrator</option>
                            @endif
                        </select>
                    </div>

                    <!-- Reseller Picker (if applicable) -->
                    <div>
                        <label class="block text-[10.5px] font-bold text-slate-700 uppercase tracking-wider mb-1">Sub-ISP Partner Binding</label>
                        <select x-model="editForm.reseller_id" 
                                class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-purple-500 focus:outline-hidden transition">
                            <option value="">-- No Sub-ISP (Direct ISP HQ) --</option>
                            @foreach($allResellers as $res)
                                <option value="{{ $res->id }}">{{ $res->name }} ({{ $res->code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Name & Email Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-[10.5px] font-bold text-slate-700 uppercase tracking-wider mb-1">Full Name <span class="text-rose-500">*</span></label>
                            <input type="text" 
                                   x-model="editForm.name" 
                                   required 
                                   class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-900 focus:bg-white focus:border-purple-500 focus:outline-hidden transition" />
                        </div>

                        <div>
                            <label class="block text-[10.5px] font-bold text-slate-700 uppercase tracking-wider mb-1">Email Address <span class="text-rose-500">*</span></label>
                            <input type="email" 
                                   x-model="editForm.email" 
                                   required 
                                   class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-900 focus:bg-white focus:border-purple-500 focus:outline-hidden transition" />
                        </div>
                    </div>

                    <!-- Mobile & Designation Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-[10.5px] font-bold text-slate-700 uppercase tracking-wider mb-1">Mobile Number</label>
                            <input type="text" 
                                   x-model="editForm.mobile" 
                                   class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-900 focus:bg-white focus:border-purple-500 focus:outline-hidden transition" />
                        </div>

                        <div>
                            <label class="block text-[10.5px] font-bold text-slate-700 uppercase tracking-wider mb-1">Designation</label>
                            <input type="text" 
                                   x-model="editForm.designation" 
                                   class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-900 focus:bg-white focus:border-purple-500 focus:outline-hidden transition" />
                        </div>
                    </div>

                    <!-- Shift & Password Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-[10.5px] font-bold text-slate-700 uppercase tracking-wider mb-1">Duty Shift</label>
                            <select x-model="editForm.shift" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-purple-500 focus:outline-hidden transition">
                                <option value="Morning Shift (09:00 - 18:00)">Morning Shift (09:00 - 18:00)</option>
                                <option value="Evening Shift (14:00 - 22:00)">Evening Shift (14:00 - 22:00)</option>
                                <option value="Night NOC Shift (22:00 - 08:00)">Night NOC Shift (22:00 - 08:00)</option>
                                <option value="Full Day Field Duty">Full Day Field Duty</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[10.5px] font-bold text-slate-700 uppercase tracking-wider mb-1">New Password (Optional)</label>
                            <input type="password" 
                                   x-model="editForm.password" 
                                   placeholder="Leave blank to keep current" 
                                   class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-900 focus:bg-white focus:border-purple-500 focus:outline-hidden transition" />
                        </div>
                    </div>

                    <!-- Address -->
                    <div>
                        <label class="block text-[10.5px] font-bold text-slate-700 uppercase tracking-wider mb-1">Address</label>
                        <input type="text" 
                               x-model="editForm.address" 
                               class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-purple-500 focus:outline-hidden transition" />
                    </div>

                </div>

                <!-- Modal Footer -->
                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" 
                            @click="showEditModal = false" 
                            class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" 
                            :disabled="isSubmitting"
                            class="bg-purple-600 hover:bg-purple-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50">
                        <i class="fas fa-spinner fa-spin" x-show="isSubmitting" style="display: none;"></i>
                        <span>Update Employee</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 8. Production-Grade Natural Modal: View Employee Profile (Details Modal) -->
    <div x-show="showViewModal" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4"
         style="display: none;">
        
        <div @click.outside="showViewModal = false" 
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden">
            
            <!-- Soft Natural Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-id-card"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Employee Full Profile</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">Detailed operational info, role assignment &amp; contact details</p>
                    </div>
                </div>
                <button type="button" @click="showViewModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-4 space-y-3.5 text-xs text-slate-800 max-h-[75vh] overflow-y-auto">
                
                <!-- Avatar & Status Profile Strip -->
                <div class="flex items-center justify-between p-3 bg-slate-50 rounded-xl border border-slate-200">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-sm font-bold border border-indigo-200 shadow-2xs">
                            <span x-text="viewData?.name ? viewData.name.substr(0,1).toUpperCase() : 'E'"></span>
                        </div>
                        <div>
                            <h4 class="font-bold text-slate-900 text-xs" x-text="viewData?.name"></h4>
                            <div class="text-[10.5px] text-indigo-700 font-medium" x-text="viewData?.designation || 'Staff Member'"></div>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold border"
                              :class="viewData?.status === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
                              x-text="viewData?.status === 'active' ? 'Active' : 'Suspended'">
                        </span>
                        <div class="text-[10px] text-slate-400 font-mono mt-0.5" x-text="viewData?.staff_id"></div>
                    </div>
                </div>

                <!-- 3-Column Mini Metrics Cards -->
                <div class="grid grid-cols-3 gap-2">
                    <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[9.5px] text-slate-500 uppercase block font-medium">Affiliation</span>
                        <span class="font-bold text-slate-800 text-[11px] mt-0.5 block truncate" x-text="viewData?.reseller_name || 'ISP Headquarters'"></span>
                    </div>
                    <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[9.5px] text-slate-500 uppercase block font-medium">Role Badge</span>
                        <span class="font-bold text-indigo-700 text-[11px] mt-0.5 block truncate" x-text="viewData?.role_badge?.label || viewData?.role"></span>
                    </div>
                    <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[9.5px] text-slate-500 uppercase block font-medium">Duty Shift</span>
                        <span class="font-bold text-slate-800 text-[11px] mt-0.5 block truncate" x-text="viewData?.shift || 'Morning Shift'"></span>
                    </div>
                </div>

                <!-- Complete Details Table -->
                <div class="border border-slate-200 rounded-lg overflow-hidden">
                    <table class="saas-table">
                        <tbody>
                            <tr>
                                <td class="text-slate-500 w-32">Staff ID</td>
                                <td class="font-mono font-bold text-purple-700" x-text="viewData?.staff_id"></td>
                            </tr>
                            <tr>
                                <td class="text-slate-500">Email Address</td>
                                <td class="font-medium text-slate-900" x-text="viewData?.email"></td>
                            </tr>
                            <tr>
                                <td class="text-slate-500">Mobile Phone</td>
                                <td class="font-mono font-medium text-slate-900" x-text="viewData?.mobile || viewData?.phone || 'N/A'"></td>
                            </tr>
                            <tr>
                                <td class="text-slate-500">Organization Scope</td>
                                <td>
                                    <span class="font-semibold text-slate-800" x-text="viewData?.reseller_name ? (viewData.reseller_name + ' (' + (viewData.reseller_code || '') + ')') : 'ISP Headquarters (Core)'"></span>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-slate-500">System Role</td>
                                <td>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] border font-medium" 
                                          :class="viewData?.role_badge?.class" 
                                          x-text="viewData?.role_badge?.label || viewData?.role"></span>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-slate-500">Work Shift</td>
                                <td class="text-slate-700" x-text="viewData?.shift || 'Morning Shift (09:00 - 18:00)'"></td>
                            </tr>
                            <tr>
                                <td class="text-slate-500">Address</td>
                                <td class="text-slate-700" x-text="viewData?.address || 'N/A'"></td>
                            </tr>
                            <tr>
                                <td class="text-slate-500">Onboarded At</td>
                                <td class="font-mono text-slate-600" x-text="viewData?.created_at"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>

            <!-- Modal Footer -->
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                <button type="button" 
                        @click="showViewModal = false; openEditModal(viewData)" 
                        class="px-3 py-1.5 rounded-lg bg-purple-50 hover:bg-purple-100 text-purple-700 font-semibold text-xs border border-purple-200 transition flex items-center gap-1.5 cursor-pointer">
                    <i class="fas fa-user-pen text-[10px]"></i>
                    <span>Edit Profile</span>
                </button>
                <button type="button" @click="showViewModal = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- 9. Production-Grade Natural Modal: Reset Password -->
    <div x-show="showPasswordModal" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4"
         style="display: none;">
        
        <div @click.outside="showPasswordModal = false" 
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-sm overflow-hidden">
            
            <!-- Soft Natural Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-key"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Reset Password</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">Set a new login password for employee</p>
                    </div>
                </div>
                <button type="button" @click="showPasswordModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <form @submit.prevent="submitResetPassword()">
                <div class="p-4 space-y-3 text-xs">
                    <div>
                        <span class="text-[10.5px] font-bold text-slate-500 uppercase tracking-wider block mb-1">Target Account</span>
                        <div class="p-2 rounded-lg bg-slate-50 border border-slate-200 font-bold text-slate-900" x-text="passwordTargetName"></div>
                    </div>

                    <div>
                        <label class="block text-[10.5px] font-bold text-slate-700 uppercase tracking-wider mb-1">New Password <span class="text-rose-500">*</span></label>
                        <input type="password" 
                               x-model="newPassword" 
                               required 
                               minlength="6"
                               placeholder="Minimum 6 characters" 
                               class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-900 focus:bg-white focus:border-amber-500 focus:outline-hidden transition" />
                    </div>
                </div>

                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="showPasswordModal = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" 
                            :disabled="isSubmitting || newPassword.length < 6"
                            class="bg-amber-600 hover:bg-amber-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50">
                        <i class="fas fa-spinner fa-spin" x-show="isSubmitting" style="display: none;"></i>
                        <span>Save New Password</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function employeeManager() {
    return {
        activeMenu: null,
        menuPos: { top: '0px', bottom: 'auto', right: '0px', left: 'auto' },

        toast: { show: false, message: '', type: 'success' },
        showToast(message, type = 'success') {
            this.toast = { show: true, message, type };
            setTimeout(() => this.toast.show = false, 4000);
        },

        showAddModal: false,
        showEditModal: false,
        showViewModal: false,
        showPasswordModal: false,
        isSubmitting: false,

        addForm: {
            scope: 'ISP_CORE',
            reseller_id: '',
            role: 'isp_manager',
            name: '',
            email: '',
            mobile: '',
            designation: '',
            shift: 'Morning Shift (09:00 - 18:00)',
            password: '',
            address: ''
        },

        editForm: {
            id: null,
            role: 'isp_manager',
            reseller_id: '',
            name: '',
            email: '',
            mobile: '',
            designation: '',
            shift: 'Morning Shift (09:00 - 18:00)',
            password: '',
            address: ''
        },

        viewData: null,
        passwordTargetId: null,
        passwordTargetName: '',
        newPassword: '',

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

        onScopeChange() {
            if (this.addForm.scope === 'ISP_CORE') {
                this.addForm.role = 'isp_manager';
                this.addForm.reseller_id = '';
            } else {
                this.addForm.role = 'reseller_manager';
            }
        },

        openAddModal() {
            this.addForm = {
                scope: 'ISP_CORE',
                reseller_id: '',
                role: 'isp_manager',
                name: '',
                email: '',
                mobile: '',
                designation: '',
                shift: 'Morning Shift (09:00 - 18:00)',
                password: '',
                address: ''
            };
            this.showAddModal = true;
        },

        async submitAddEmployee() {
            this.isSubmitting = true;
            try {
                const response = await fetch("{{ route('tenant.staff.store') }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "Accept": "application/json",
                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                    },
                    body: JSON.stringify(this.addForm)
                });

                const data = await response.json();
                if (data.success) {
                    this.showToast(data.message, 'success');
                    this.showAddModal = false;
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    this.showToast(data.message || 'Validation error occurred.', 'error');
                }
            } catch (err) {
                this.showToast('Network error while creating employee.', 'error');
            } finally {
                this.isSubmitting = false;
            }
        },

        openEditModal(emp) {
            this.editForm = {
                id: emp.id,
                role: emp.role,
                reseller_id: emp.reseller_id || '',
                name: emp.name,
                email: emp.email,
                mobile: emp.mobile || emp.phone || '',
                designation: emp.designation || '',
                shift: emp.shift || 'Morning Shift (09:00 - 18:00)',
                password: '',
                address: emp.address || ''
            };
            this.showEditModal = true;
        },

        async submitEditEmployee() {
            this.isSubmitting = true;
            try {
                const response = await fetch(`/admin/staff/${this.editForm.id}`, {
                    method: "PUT",
                    headers: {
                        "Content-Type": "application/json",
                        "Accept": "application/json",
                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                    },
                    body: JSON.stringify(this.editForm)
                });

                const data = await response.json();
                if (data.success) {
                    this.showToast(data.message, 'success');
                    this.showEditModal = false;
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    this.showToast(data.message || 'Update failed.', 'error');
                }
            } catch (err) {
                this.showToast('Network error while updating employee.', 'error');
            } finally {
                this.isSubmitting = false;
            }
        },

        async viewEmployee(id) {
            try {
                const response = await fetch(`/admin/staff/${id}`, {
                    headers: { "Accept": "application/json" }
                });
                const data = await response.json();
                if (data.success) {
                    this.viewData = data.employee || data.staff;
                    this.showViewModal = true;
                }
            } catch (err) {
                this.showToast('Failed to load employee details.', 'error');
            }
        },

        async toggleStatus(id) {
            try {
                const response = await fetch(`/admin/staff/${id}/toggle-status`, {
                    method: "POST",
                    headers: {
                        "Accept": "application/json",
                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                    }
                });
                const data = await response.json();
                if (data.success) {
                    this.showToast(data.message, 'success');
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    this.showToast(data.message || 'Toggle status failed.', 'error');
                }
            } catch (err) {
                this.showToast('Network error toggling status.', 'error');
            }
        },

        openPasswordModal(id, name) {
            this.passwordTargetId = id;
            this.passwordTargetName = name;
            this.newPassword = '';
            this.showPasswordModal = true;
        },

        async submitResetPassword() {
            this.isSubmitting = true;
            try {
                const response = await fetch(`/admin/staff/${this.passwordTargetId}/reset-password`, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "Accept": "application/json",
                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                    },
                    body: JSON.stringify({ password: this.newPassword })
                });

                const data = await response.json();
                if (data.success) {
                    this.showToast(data.message, 'success');
                    this.showPasswordModal = false;
                } else {
                    this.showToast(data.message || 'Password reset failed.', 'error');
                }
            } catch (err) {
                this.showToast('Network error resetting password.', 'error');
            } finally {
                this.isSubmitting = false;
            }
        }
    };
}
</script>
@endpush
