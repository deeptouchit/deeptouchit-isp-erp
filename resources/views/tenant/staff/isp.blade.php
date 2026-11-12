@extends('tenant.layouts.app')

@section('title', __('ISP Operations Staff') . ' - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
    {{-- Page specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="ispStaffManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">

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

    <!-- 1. Top Header Bar (Strictly Icon + Title + Count Badge + Action Button ONLY - AGENTS.md Rule 2.A) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 bg-white px-3.5 py-2.5 rounded-xl border border-slate-200/90 shadow-2xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 border border-purple-100 flex items-center justify-center text-sm shadow-2xs flex-shrink-0">
                <i class="fas fa-building-user"></i>
            </div>
            <div class="flex items-center gap-2">
                <h1 class="text-xs sm:text-sm font-bold text-slate-900 tracking-tight">{{ __('ISP Operations Staff') }}</h1>
                <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-purple-50 text-purple-700 border border-purple-200">
                    {{ __(':count HQ Staff', ['count' => number_format($employees->total())]) }}
                </span>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <!-- Onboard ISP Staff Button -->
            <button type="button" 
                    @click="openAddModal()"
                    class="h-[34px] py-0 px-3.5 rounded-lg bg-purple-600 hover:bg-purple-700 text-white font-semibold text-xs shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-user-plus text-[11px]"></i>
                <span>{{ __('+ Onboard ISP Staff') }}</span>
            </button>
        </div>
    </div>

    <!-- 2. KPI Summary Strip (Strictly 6 Cards) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        <!-- Metric 1: Total ISP Staff -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-purple-700 uppercase tracking-wider block truncate">{{ __('ISP Core HQ') }}</span>
                <span class="text-[13px] font-bold text-purple-600 font-mono leading-tight block">{{ number_format($totalIspStaff) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-purple-50 text-purple-600 flex items-center justify-center text-[10px] border border-purple-100 flex-shrink-0">
                <i class="fas fa-building"></i>
            </div>
        </div>

        <!-- Metric 2: Management Team -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-blue-700 uppercase tracking-wider block truncate">{{ __('Managers') }}</span>
                <span class="text-[13px] font-bold text-blue-600 font-mono leading-tight block">{{ number_format($managersCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-blue-50 text-blue-600 flex items-center justify-center text-[10px] border border-blue-100 flex-shrink-0">
                <i class="fas fa-user-tie"></i>
            </div>
        </div>

        <!-- Metric 3: NOC Engineers -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-indigo-700 uppercase tracking-wider block truncate">{{ __('NOC Engineers') }}</span>
                <span class="text-[13px] font-bold text-indigo-600 font-mono leading-tight block">{{ number_format($nocCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-indigo-50 text-indigo-600 flex items-center justify-center text-[10px] border border-indigo-100 flex-shrink-0">
                <i class="fas fa-tower-broadcast"></i>
            </div>
        </div>

        <!-- Metric 4: Field Technicians -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-emerald-700 uppercase tracking-wider block truncate">{{ __('Field Techs') }}</span>
                <span class="text-[13px] font-bold text-emerald-600 font-mono leading-tight block">{{ number_format($fieldTechCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-emerald-50 text-emerald-600 flex items-center justify-center text-[10px] border border-emerald-100 flex-shrink-0">
                <i class="fas fa-screwdriver-wrench"></i>
            </div>
        </div>

        <!-- Metric 5: Bill Collectors -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-amber-700 uppercase tracking-wider block truncate">{{ __('Bill Collectors') }}</span>
                <span class="text-[13px] font-bold text-amber-600 font-mono leading-tight block">{{ number_format($collectorsCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-amber-50 text-amber-600 flex items-center justify-center text-[10px] border border-amber-100 flex-shrink-0">
                <i class="fas fa-money-bill-wave"></i>
            </div>
        </div>

        <!-- Metric 6: Active Staff Status -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-teal-700 uppercase tracking-wider block truncate">{{ __('Active on Duty') }}</span>
                <span class="text-[13px] font-bold text-teal-600 font-mono leading-tight block">{{ number_format($activeStaffCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-teal-50 text-teal-600 flex items-center justify-center text-[10px] border border-teal-100 flex-shrink-0">
                <i class="fas fa-circle-check"></i>
            </div>
        </div>
    </div>

    <!-- 3. Search & Multi-Filter Toolbar (Customer-Standard 2-Tier Filter System - AGENTS.md Rule 2.C) -->
    <div class="bg-white p-2.5 rounded-xl border border-slate-200/90 shadow-2xs space-y-2.5"
         x-data="{ showMoreFilters: {{ $isMoreOpen ? 'true' : 'false' }}, searchQuery: '{{ addslashes(request('search', '')) }}' }">
        <form method="GET" action="{{ route('tenant.staff.isp') }}" class="space-y-2.5">
            <!-- Tier 1: Primary Filter Bar -->
            <div class="flex flex-col md:flex-row items-stretch md:items-center gap-2">
                <!-- Live Search Input -->
                <div class="relative flex-1 min-w-[200px]">
                    <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-xs transition-colors pointer-events-none"
                       :class="searchQuery ? 'text-cyan-600 font-bold' : 'text-slate-400'"></i>
                    <input type="text" 
                           name="search" 
                           x-model="searchQuery"
                           placeholder="{{ __('Search Name, Email, Mobile, Staff ID...') }}" 
                           class="w-full h-[34px] pl-8 pr-7 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition" />
                    <button type="button" 
                            x-show="searchQuery && searchQuery.length > 0" 
                            x-cloak
                            @click="searchQuery = ''; $nextTick(() => $el.closest('form').submit())"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-rose-500 text-xs cursor-pointer">
                        <i class="fas fa-times-circle"></i>
                    </button>
                </div>

                <!-- Department Filter -->
                <div class="w-full md:w-44 flex-shrink-0">
                    <select name="department" 
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition cursor-pointer">
                        <option value="">{{ __('All Departments') }}</option>
                        <option value="management" {{ request('department') === 'management' ? 'selected' : '' }}>{{ __('Operations & Admin') }}</option>
                        <option value="noc" {{ request('department') === 'noc' ? 'selected' : '' }}>{{ __('NOC / Server Eng') }}</option>
                        <option value="field" {{ request('department') === 'field' ? 'selected' : '' }}>{{ __('Field & Fiber Tech') }}</option>
                        <option value="billing" {{ request('department') === 'billing' ? 'selected' : '' }}>{{ __('Retail Billing Dept') }}</option>
                    </select>
                </div>

                <!-- Role Filter -->
                <div class="w-full md:w-40 flex-shrink-0">
                    <select name="role" 
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition cursor-pointer">
                        <option value="">{{ __('All Roles') }}</option>
                        @if(isset($availableRoles) && $availableRoles->count() > 0)
                            @foreach($availableRoles as $r)
                                <option value="{{ $r->name }}" {{ request('role') === $r->name ? 'selected' : '' }}>{{ __($r->display_name) }}</option>
                            @endforeach
                        @else
                            <option value="isp_admin" {{ request('role') === 'isp_admin' ? 'selected' : '' }}>{{ __('ISP Super Admin') }}</option>
                            <option value="isp_manager" {{ request('role') === 'isp_manager' ? 'selected' : '' }}>{{ __('ISP Manager') }}</option>
                            <option value="isp_technician" {{ request('role') === 'isp_technician' ? 'selected' : '' }}>{{ __('NOC / Field Tech') }}</option>
                            <option value="isp_collector" {{ request('role') === 'isp_collector' ? 'selected' : '' }}>{{ __('Bill Collector') }}</option>
                        @endif
                    </select>
                </div>

                <!-- Per Page Select -->
                <div class="w-full md:w-28 flex-shrink-0">
                    <select name="per_page" 
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition cursor-pointer">
                        @foreach([15, 20, 30, 50, 100] as $size)
                            <option value="{{ $size }}" {{ (int)$perPage === $size ? 'selected' : '' }}>{{ __(':size / page', ['size' => $size]) }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- More Filters Accordion Toggle Button -->
                <button type="button" 
                        @click="showMoreFilters = !showMoreFilters"
                        class="h-[34px] py-0 px-2.5 rounded-lg border text-xs font-medium transition flex items-center justify-center gap-1.5 cursor-pointer flex-shrink-0"
                        :class="showMoreFilters ? 'bg-cyan-50 border-cyan-200 text-cyan-700' : 'bg-slate-50 hover:bg-slate-100 border-slate-200 text-slate-700'">
                    <i class="fas fa-sliders text-[10px]"></i>
                    <span>{{ __('More Filters') }}</span>
                    @if($activeMoreCount > 0)
                        <span class="w-4 h-4 rounded-full bg-cyan-600 text-white text-[9px] font-bold flex items-center justify-center">
                            {{ $activeMoreCount }}
                        </span>
                    @endif
                    <i class="fas text-[9px] transition-transform duration-200" 
                       :class="showMoreFilters ? 'fa-chevron-up text-cyan-600' : 'fa-chevron-down text-slate-400'"></i>
                </button>

                <!-- Action Buttons (Strict Universal Standard) -->
                <div class="flex items-center gap-1.5 flex-shrink-0 md:ml-auto">
                    <button type="submit" 
                            class="h-[34px] py-0 bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer" 
                            title="{{ __('Apply Filters') }}">
                        <i class="fas fa-filter text-[10px]"></i>
                        <span>{{ __('Filter') }}</span>
                    </button>
                    <a href="{{ route('tenant.staff.isp') }}" 
                       class="h-[34px] py-0 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer" 
                       title="{{ __('Reset Filters') }}">
                        <i class="fas fa-rotate-left text-[10px]"></i>
                        <span>{{ __('Reset') }}</span>
                    </a>
                </div>
            </div>

            <!-- Tier 2: Secondary Filters Drawer -->
            <div x-show="showMoreFilters" 
                 x-cloak 
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="pt-2 border-t border-slate-100 flex flex-wrap items-center gap-2">
                <!-- Shift Filter -->
                <div class="w-full sm:w-44">
                    <select name="shift" 
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition cursor-pointer">
                        <option value="">{{ __('All Shifts') }}</option>
                        <option value="Morning Shift (09:00 - 18:00)" {{ request('shift') === 'Morning Shift (09:00 - 18:00)' ? 'selected' : '' }}>{{ __('Morning Shift') }}</option>
                        <option value="Evening Shift (14:00 - 22:00)" {{ request('shift') === 'Evening Shift (14:00 - 22:00)' ? 'selected' : '' }}>{{ __('Evening Shift') }}</option>
                        <option value="Night NOC Shift (22:00 - 08:00)" {{ request('shift') === 'Night NOC Shift (22:00 - 08:00)' ? 'selected' : '' }}>{{ __('Night NOC Shift') }}</option>
                        <option value="Full Day Field Duty" {{ request('shift') === 'Full Day Field Duty' ? 'selected' : '' }}>{{ __('Full Day Duty') }}</option>
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="w-full sm:w-40">
                    <select name="status" 
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition cursor-pointer">
                        <option value="">{{ __('All Status') }}</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>{{ __('Suspended') }}</option>
                    </select>
                </div>
            </div>
        </form>
    </div>

    <!-- 4. Master Table (<table class="saas-table">) -->
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-12 text-center font-semibold">#</th>
                        <th class="text-center font-semibold">{{ __('Staff ID') }}</th>
                        <th class="text-center font-semibold">{{ __('Name') }}</th>
                        <th class="text-center font-semibold">{{ __('Role') }}</th>
                        <th class="text-center font-semibold">{{ __('Department') }}</th>
                        <th class="text-center font-semibold">{{ __('Mobile') }}</th>
                        <th class="text-center font-semibold">{{ __('Shift') }}</th>
                        <th class="text-center font-semibold">{{ __('Status') }}</th>
                        <th class="w-14 text-center font-semibold no-sort">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $index => $emp)
                        @php
                            $roleBadge = $emp->role_badge;
                            $statusBadge = $emp->status_badge;
                            $staffCode = $emp->staff_id ?: ('STF-' . str_pad($emp->id, 4, '0', STR_PAD_LEFT));
                        @endphp
                        <tr>
                            <!-- Row Index -->
                            <td class="text-center font-mono text-slate-400 whitespace-nowrap">
                                {{ $employees->firstItem() + $index }}
                            </td>

                            <!-- Staff ID (Single-data) -->
                            <td class="text-center font-mono text-slate-600 font-medium whitespace-nowrap">
                                {{ $staffCode }}
                            </td>

                            <!-- Name (Single-data) -->
                            <td class="text-left font-semibold text-slate-800 whitespace-nowrap">
                                {{ $emp->name }}
                            </td>

                            <!-- Role (Single-data) -->
                            <td class="text-center whitespace-nowrap">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] border {{ $roleBadge['class'] }}">
                                    <i class="fas {{ $roleBadge['icon'] }} text-[9px]"></i>
                                    <span>{{ __($roleBadge['label']) }}</span>
                                </span>
                            </td>

                            <!-- Department (Single-data) -->
                            <td class="text-left text-slate-700 whitespace-nowrap">
                                {{ $emp->designation ?: __('ISP Operations') }}
                            </td>

                            <!-- Mobile (Single-data) -->
                            <td class="text-center font-mono text-slate-700 whitespace-nowrap">
                                {{ $emp->mobile ?: ($emp->phone ?: '--') }}
                            </td>

                            <!-- Shift -->
                            <td class="text-center text-slate-600 text-[10.5px] whitespace-nowrap">
                                {{ $emp->shift ? __($emp->shift) : __('Morning Shift') }}
                            </td>

                            <!-- Status (Single-data) -->
                            <td class="text-center whitespace-nowrap">
                                <button type="button" 
                                        @click="toggleStatus({{ $emp->id }})"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] border transition cursor-pointer {{ $statusBadge['class'] }} hover:opacity-80"
                                        title="{{ __('Click to toggle status') }}">
                                    <i class="fas {{ $statusBadge['icon'] }} text-[9px]"></i>
                                    <span>{{ __($statusBadge['label']) }}</span>
                                </button>
                            </td>

                            <!-- Floating Action Menu Button -->
                            <td class="text-center whitespace-nowrap">
                                <button type="button" 
                                        @click="toggleMenu({{ Js::from($emp) }}, $event)" 
                                        class="w-7 h-7 rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-600 flex items-center justify-center transition cursor-pointer mx-auto">
                                    <i class="fas fa-ellipsis-v text-[10px]"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-10 text-slate-400">
                                <div class="max-w-xs mx-auto space-y-2.5">
                                    <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 mx-auto flex items-center justify-center text-base border border-purple-100 shadow-2xs">
                                        <i class="fas fa-building-user"></i>
                                    </div>
                                    <h3 class="text-xs font-semibold text-slate-800">{{ __('No ISP Operations Staff Found') }}</h3>
                                    <p class="text-[11px] text-slate-500">{{ __('No HQ operations personnel matched your filter criteria.') }}</p>
                                    <div class="pt-1">
                                        <button type="button" @click="openAddModal()" class="h-[34px] py-0 px-3.5 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-xs font-semibold shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                                            <i class="fas fa-user-plus text-[10px]"></i>
                                            <span>{{ __('+ Onboard ISP Staff') }}</span>
                                        </button>
                                    </div>
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
                    class="w-full px-3 py-1.5 hover:bg-purple-50 hover:text-purple-700 flex items-center gap-2 transition text-left cursor-pointer">
                <i class="fas fa-id-card text-purple-500 w-4 text-center text-[11px]"></i>
                <span>{{ __('View Full Profile') }}</span>
            </button>
        </div>

        <!-- Group 2: Edit & Security -->
        <div class="py-1">
            <button type="button" 
                    @click="const emp = activeMenu; activeMenu = null; openEditModal(emp)" 
                    class="w-full px-3 py-1.5 hover:bg-purple-50 hover:text-purple-700 flex items-center gap-2 transition text-left cursor-pointer">
                <i class="fas fa-edit text-purple-500 w-4 text-center text-[11px]"></i>
                <span>{{ __('Edit Employee Details') }}</span>
            </button>

            <button type="button" 
                    @click="const emp = activeMenu; activeMenu = null; openPasswordModal(emp.id, emp.name)" 
                    class="w-full px-3 py-1.5 hover:bg-amber-50 hover:text-amber-700 flex items-center gap-2 transition text-left cursor-pointer">
                <i class="fas fa-key text-amber-500 w-4 text-center text-[11px]"></i>
                <span>{{ __('Reset Login Password') }}</span>
            </button>
        </div>

        <!-- Group 3: Status Toggle -->
        <div class="py-1">
            <button type="button" 
                    @click="const emp = activeMenu; activeMenu = null; toggleStatus(emp.id)" 
                    class="w-full px-3 py-1.5 hover:bg-slate-50 text-slate-700 flex items-center gap-2 transition text-left cursor-pointer">
                <i class="fas w-4 text-center text-[11px]" :class="activeMenu?.status === 'active' ? 'fa-user-lock text-rose-500' : 'fa-user-check text-emerald-500'"></i>
                <span x-text="activeMenu?.status === 'active' ? i18n.suspendEmployee : i18n.activateEmployee"></span>
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
                    <div class="w-7 h-7 rounded-lg bg-purple-50 text-purple-600 border border-purple-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">{{ __('Onboard ISP Core Staff') }}</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">{{ __('Add HQ manager, NOC engineer, field tech or collector') }}</p>
                    </div>
                </div>
                <button type="button" @click="showAddModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <!-- Modal Form Body -->
            <form @submit.prevent="submitAddEmployee()">
                <div class="p-4 space-y-3.5 text-xs max-h-[75vh] overflow-y-auto">
                    
                    <!-- Role Selector -->
                    <div>
                        <label class="block text-[10.5px] font-bold text-slate-700 uppercase tracking-wider mb-1">{{ __('ISP Operational Role') }} <span class="text-rose-500">*</span></label>
                        <select x-model="addForm.role" 
                                required
                                class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-purple-500 focus:outline-hidden transition">
                            @if(isset($availableRoles) && $availableRoles->count() > 0)
                                @foreach($availableRoles as $r)
                                    <option value="{{ $r->name }}">{{ __($r->display_name) }} ({{ $r->is_system ? __('System') : __('Custom') }})</option>
                                @endforeach
                            @else
                                <option value="isp_manager">{{ __('ISP Manager (Operations & Team Lead)') }}</option>
                                <option value="isp_technician">{{ __('ISP Technician / NOC Field Engineer') }}</option>
                                <option value="isp_collector">{{ __('ISP Bill Collector (Retail Billing)') }}</option>
                                <option value="isp_admin">{{ __('ISP Administrator') }}</option>
                            @endif
                        </select>
                    </div>

                    <!-- Name & Email Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-[10.5px] font-bold text-slate-700 uppercase tracking-wider mb-1">{{ __('Full Name') }} <span class="text-rose-500">*</span></label>
                            <input type="text" 
                                   x-model="addForm.name" 
                                   required 
                                   placeholder="{{ __('e.g. Md. Hasan Ali') }}" 
                                   class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-900 focus:bg-white focus:border-purple-500 focus:outline-hidden transition" />
                        </div>

                        <div>
                            <label class="block text-[10.5px] font-bold text-slate-700 uppercase tracking-wider mb-1">{{ __('Email Address') }} <span class="text-rose-500">*</span></label>
                            <input type="email" 
                                   x-model="addForm.email" 
                                   required 
                                   placeholder="hasan@somitysoft.com" 
                                   class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-900 focus:bg-white focus:border-purple-500 focus:outline-hidden transition" />
                        </div>
                    </div>

                    <!-- Mobile & Designation Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-[10.5px] font-bold text-slate-700 uppercase tracking-wider mb-1">{{ __('Mobile Number') }}</label>
                            <input type="text" 
                                   x-model="addForm.mobile" 
                                   placeholder="{{ __('017XX-XXXXXX') }}" 
                                   class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-900 focus:bg-white focus:border-purple-500 focus:outline-hidden transition" />
                        </div>

                        <div>
                            <label class="block text-[10.5px] font-bold text-slate-700 uppercase tracking-wider mb-1">{{ __('Designation') }}</label>
                            <input type="text" 
                                   x-model="addForm.designation" 
                                   placeholder="{{ __('e.g. Senior NOC Engineer') }}" 
                                   class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-900 focus:bg-white focus:border-purple-500 focus:outline-hidden transition" />
                        </div>
                    </div>

                    <!-- Shift & Password Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-[10.5px] font-bold text-slate-700 uppercase tracking-wider mb-1">{{ __('Duty Shift') }}</label>
                            <select x-model="addForm.shift" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-purple-500 focus:outline-hidden transition">
                                <option value="Morning Shift (09:00 - 18:00)">{{ __('Morning Shift (09:00 - 18:00)') }}</option>
                                <option value="Evening Shift (14:00 - 22:00)">{{ __('Evening Shift (14:00 - 22:00)') }}</option>
                                <option value="Night NOC Shift (22:00 - 08:00)">{{ __('Night NOC Shift (22:00 - 08:00)') }}</option>
                                <option value="Full Day Field Duty">{{ __('Full Day Field Duty') }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[10.5px] font-bold text-slate-700 uppercase tracking-wider mb-1">{{ __('Login Password') }} <span class="text-rose-500">*</span></label>
                            <input type="password" 
                                   x-model="addForm.password" 
                                   required 
                                   minlength="6"
                                   placeholder="{{ __('Min 6 characters') }}" 
                                   class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-900 focus:bg-white focus:border-purple-500 focus:outline-hidden transition" />
                        </div>
                    </div>

                    <!-- Address -->
                    <div>
                        <label class="block text-[10.5px] font-bold text-slate-700 uppercase tracking-wider mb-1">{{ __('Permanent / Present Address') }}</label>
                        <input type="text" 
                               x-model="addForm.address" 
                               placeholder="{{ __('e.g. Sector 7, Uttara, Dhaka') }}" 
                               class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-purple-500 focus:outline-hidden transition" />
                    </div>

                </div>

                <!-- Modal Footer -->
                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" 
                            @click="showAddModal = false" 
                            class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit" 
                            :disabled="isSubmitting"
                            class="bg-purple-600 hover:bg-purple-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50">
                        <i class="fas fa-spinner fa-spin" x-show="isSubmitting" style="display: none;"></i>
                        <span>{{ __('Onboard Staff') }}</span>
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
                        <h3 class="text-xs font-semibold text-slate-800">{{ __('Edit Staff Profile') }}</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">{{ __('Update contact details, role permissions & assignment') }}</p>
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
                        <label class="block text-[10.5px] font-bold text-slate-700 uppercase tracking-wider mb-1">{{ __('System Role') }} <span class="text-rose-500">*</span></label>
                        <select x-model="editForm.role" 
                                required
                                class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-purple-500 focus:outline-hidden transition">
                            @if(isset($availableRoles) && $availableRoles->count() > 0)
                                @foreach($availableRoles as $r)
                                    <option value="{{ $r->name }}">{{ __($r->display_name) }} ({{ $r->is_system ? __('System') : __('Custom') }})</option>
                                @endforeach
                            @else
                                <option value="isp_manager">{{ __('ISP Manager (Operations & Team Lead)') }}</option>
                                <option value="isp_technician">{{ __('ISP NOC / Field Tech') }}</option>
                                <option value="isp_collector">{{ __('ISP Bill Collector') }}</option>
                                <option value="isp_admin">{{ __('ISP Administrator') }}</option>
                            @endif
                        </select>
                    </div>

                    <!-- Name & Email Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-[10.5px] font-bold text-slate-700 uppercase tracking-wider mb-1">{{ __('Full Name') }} <span class="text-rose-500">*</span></label>
                            <input type="text" 
                                   x-model="editForm.name" 
                                   required 
                                   class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-900 focus:bg-white focus:border-purple-500 focus:outline-hidden transition" />
                        </div>

                        <div>
                            <label class="block text-[10.5px] font-bold text-slate-700 uppercase tracking-wider mb-1">{{ __('Email Address') }} <span class="text-rose-500">*</span></label>
                            <input type="email" 
                                   x-model="editForm.email" 
                                   required 
                                   class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-900 focus:bg-white focus:border-purple-500 focus:outline-hidden transition" />
                        </div>
                    </div>

                    <!-- Mobile & Designation Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-[10.5px] font-bold text-slate-700 uppercase tracking-wider mb-1">{{ __('Mobile Number') }}</label>
                            <input type="text" 
                                   x-model="editForm.mobile" 
                                   class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-900 focus:bg-white focus:border-purple-500 focus:outline-hidden transition" />
                        </div>

                        <div>
                            <label class="block text-[10.5px] font-bold text-slate-700 uppercase tracking-wider mb-1">{{ __('Designation') }}</label>
                            <input type="text" 
                                   x-model="editForm.designation" 
                                   class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-900 focus:bg-white focus:border-purple-500 focus:outline-hidden transition" />
                        </div>
                    </div>

                    <!-- Shift & Password Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-[10.5px] font-bold text-slate-700 uppercase tracking-wider mb-1">{{ __('Duty Shift') }}</label>
                            <select x-model="editForm.shift" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-purple-500 focus:outline-hidden transition">
                                <option value="Morning Shift (09:00 - 18:00)">{{ __('Morning Shift (09:00 - 18:00)') }}</option>
                                <option value="Evening Shift (14:00 - 22:00)">{{ __('Evening Shift (14:00 - 22:00)') }}</option>
                                <option value="Night NOC Shift (22:00 - 08:00)">{{ __('Night NOC Shift (22:00 - 08:00)') }}</option>
                                <option value="Full Day Field Duty">{{ __('Full Day Field Duty') }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[10.5px] font-bold text-slate-700 uppercase tracking-wider mb-1">{{ __('New Password (Optional)') }}</label>
                            <input type="password" 
                                   x-model="editForm.password" 
                                   placeholder="{{ __('Leave blank to keep current') }}" 
                                   class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-900 focus:bg-white focus:border-purple-500 focus:outline-hidden transition" />
                        </div>
                    </div>

                    <!-- Address -->
                    <div>
                        <label class="block text-[10.5px] font-bold text-slate-700 uppercase tracking-wider mb-1">{{ __('Address') }}</label>
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
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit" 
                            :disabled="isSubmitting"
                            class="bg-purple-600 hover:bg-purple-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50">
                        <i class="fas fa-spinner fa-spin" x-show="isSubmitting" style="display: none;"></i>
                        <span>{{ __('Update Staff') }}</span>
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
                    <div class="w-7 h-7 rounded-lg bg-purple-50 text-purple-600 border border-purple-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-id-card"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">{{ __('Staff Full Profile') }}</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">{{ __('Detailed operational info & department assignment') }}</p>
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
                        <div class="w-10 h-10 rounded-full bg-purple-100 text-purple-700 flex items-center justify-center text-sm font-bold border border-purple-200 shadow-2xs">
                            <span x-text="viewData?.name ? viewData.name.substr(0,1).toUpperCase() : 'E'"></span>
                        </div>
                        <div>
                            <h4 class="font-bold text-slate-900 text-xs" x-text="viewData?.name"></h4>
                            <div class="text-[10.5px] text-purple-700 font-medium" x-text="viewData?.designation || i18n.ispStaffMember"></div>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold border"
                              :class="viewData?.status === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
                              x-text="viewData?.status === 'active' ? i18n.active : i18n.suspended">
                        </span>
                        <div class="text-[10px] text-slate-400 font-mono mt-0.5" x-text="viewData?.staff_id"></div>
                    </div>
                </div>

                <!-- 3-Column Mini Metrics Cards -->
                <div class="grid grid-cols-3 gap-2">
                    <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[9.5px] text-slate-500 uppercase block font-medium">{{ __('Affiliation') }}</span>
                        <span class="font-bold text-purple-700 text-[11px] mt-0.5 block truncate">{{ __('ISP Headquarters') }}</span>
                    </div>
                    <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[9.5px] text-slate-500 uppercase block font-medium">{{ __('Role Badge') }}</span>
                        <span class="font-bold text-slate-800 text-[11px] mt-0.5 block truncate" x-text="translateLabel(viewData?.role_badge?.label || viewData?.role)"></span>
                    </div>
                    <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[9.5px] text-slate-500 uppercase block font-medium">{{ __('Duty Shift') }}</span>
                        <span class="font-bold text-slate-800 text-[11px] mt-0.5 block truncate" x-text="translateLabel(viewData?.shift || i18n.morningShift)"></span>
                    </div>
                </div>

                <!-- Complete Details Table -->
                <div class="border border-slate-200 rounded-lg overflow-hidden">
                    <table class="saas-table">
                        <tbody>
                            <tr>
                                <td class="text-slate-500 w-32">{{ __('Staff ID') }}</td>
                                <td class="font-mono font-bold text-purple-700" x-text="viewData?.staff_id"></td>
                            </tr>
                            <tr>
                                <td class="text-slate-500">{{ __('Email Address') }}</td>
                                <td class="font-medium text-slate-900" x-text="viewData?.email"></td>
                            </tr>
                            <tr>
                                <td class="text-slate-500">{{ __('Mobile Phone') }}</td>
                                <td class="font-mono font-medium text-slate-900" x-text="viewData?.mobile || viewData?.phone || i18n.na"></td>
                            </tr>
                            <tr>
                                <td class="text-slate-500">{{ __('System Role') }}</td>
                                <td>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] border font-medium" 
                                          :class="viewData?.role_badge?.class" 
                                          x-text="translateLabel(viewData?.role_badge?.label || viewData?.role)"></span>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-slate-500">{{ __('Work Shift') }}</td>
                                <td class="text-slate-700" x-text="translateLabel(viewData?.shift || i18n.morningShiftHours)"></td>
                            </tr>
                            <tr>
                                <td class="text-slate-500">{{ __('Address') }}</td>
                                <td class="text-slate-700" x-text="viewData?.address || i18n.na"></td>
                            </tr>
                            <tr>
                                <td class="text-slate-500">{{ __('Onboarded At') }}</td>
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
                    <span>{{ __('Edit Profile') }}</span>
                </button>
                <button type="button" @click="showViewModal = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                    {{ __('Close') }}
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
                        <h3 class="text-xs font-semibold text-slate-800">{{ __('Reset Password') }}</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">{{ __('Set a new login password for employee') }}</p>
                    </div>
                </div>
                <button type="button" @click="showPasswordModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <form @submit.prevent="submitResetPassword()">
                <div class="p-4 space-y-3 text-xs">
                    <div>
                        <span class="text-[10.5px] font-bold text-slate-500 uppercase tracking-wider block mb-1">{{ __('Target Account') }}</span>
                        <div class="p-2 rounded-lg bg-slate-50 border border-slate-200 font-bold text-slate-900" x-text="passwordTargetName"></div>
                    </div>

                    <div>
                        <label class="block text-[10.5px] font-bold text-slate-700 uppercase tracking-wider mb-1">{{ __('New Password') }} <span class="text-rose-500">*</span></label>
                        <input type="password" 
                               x-model="newPassword" 
                               required 
                               minlength="6"
                               placeholder="{{ __('Minimum 6 characters') }}" 
                               class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-900 focus:bg-white focus:border-amber-500 focus:outline-hidden transition" />
                    </div>
                </div>

                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="showPasswordModal = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit" 
                            :disabled="isSubmitting || newPassword.length < 6"
                            class="bg-amber-600 hover:bg-amber-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50">
                        <i class="fas fa-spinner fa-spin" x-show="isSubmitting" style="display: none;"></i>
                        <span>{{ __('Save New Password') }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function ispStaffManager() {
    return {
        i18n: {
            suspendEmployee: @json(__('Suspend Employee')),
            activateEmployee: @json(__('Activate Employee')),
            ispStaffMember: @json(__('ISP Staff Member')),
            active: @json(__('Active')),
            suspended: @json(__('Suspended')),
            morningShift: @json(__('Morning Shift')),
            morningShiftHours: @json(__('Morning Shift (09:00 - 18:00)')),
            na: @json(__('N/A')),
            validationError: @json(__('Validation error occurred.')),
            networkCreateError: @json(__('Network error while creating staff.')),
            updateFailed: @json(__('Update failed.')),
            networkUpdateError: @json(__('Network error while updating staff.')),
            loadDetailsError: @json(__('Failed to load staff details.')),
            toggleStatusFailed: @json(__('Toggle status failed.')),
            networkToggleError: @json(__('Network error toggling status.')),
            passwordResetFailed: @json(__('Password reset failed.')),
            networkPasswordError: @json(__('Network error resetting password.')),
            labels: {
                'ISP Super Admin': @json(__('ISP Super Admin')),
                'ISP Manager': @json(__('ISP Manager')),
                'NOC / Field Tech': @json(__('NOC / Field Tech')),
                'Bill Collector': @json(__('Bill Collector')),
                'Sub-ISP Admin': @json(__('Sub-ISP Admin')),
                'Sub-ISP Manager': @json(__('Sub-ISP Manager')),
                'Sub-ISP Tech': @json(__('Sub-ISP Tech')),
                'Sub-ISP Collector': @json(__('Sub-ISP Collector')),
                'Staff Member': @json(__('Staff Member')),
                'Morning Shift (09:00 - 18:00)': @json(__('Morning Shift (09:00 - 18:00)')),
                'Evening Shift (14:00 - 22:00)': @json(__('Evening Shift (14:00 - 22:00)')),
                'Night NOC Shift (22:00 - 08:00)': @json(__('Night NOC Shift (22:00 - 08:00)')),
                'Full Day Field Duty': @json(__('Full Day Field Duty')),
                'Morning Shift': @json(__('Morning Shift')),
            }
        },
        translateLabel(val) {
            if (!val) return '';
            return this.i18n.labels[val] || val;
        },

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
                    this.showToast(data.message || this.i18n.validationError, 'error');
                }
            } catch (err) {
                this.showToast(this.i18n.networkCreateError, 'error');
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
                    this.showToast(data.message || this.i18n.updateFailed, 'error');
                }
            } catch (err) {
                this.showToast(this.i18n.networkUpdateError, 'error');
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
                this.showToast(this.i18n.loadDetailsError, 'error');
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
                    this.showToast(data.message || this.i18n.toggleStatusFailed, 'error');
                }
            } catch (err) {
                this.showToast(this.i18n.networkToggleError, 'error');
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
                    this.showToast(data.message || this.i18n.passwordResetFailed, 'error');
                }
            } catch (err) {
                this.showToast(this.i18n.networkPasswordError, 'error');
            } finally {
                this.isSubmitting = false;
            }
        }
    };
}
</script>
@endpush
