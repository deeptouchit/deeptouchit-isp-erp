@extends('tenant.layouts.app')

@section('title', 'Staff Targets & Commission - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
    {{-- Page-specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="staffCommissionManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">

    {{-- 1. Top Header Bar (Strictly Icon + Title + Count Badge + Action Buttons ONLY - AGENTS.md Rule 2.A) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 bg-white px-3.5 py-2.5 rounded-xl border border-slate-200/90 shadow-2xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center text-sm shadow-2xs flex-shrink-0">
                <i class="fas fa-bullseye"></i>
            </div>
            <div class="flex items-center gap-2">
                <h1 class="text-xs sm:text-sm font-bold text-slate-900 tracking-tight">Staff Targets &amp; Commission</h1>
                <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                    {{ $periodLabel }}
                </span>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('tenant.staff.index') }}" 
               class="h-[34px] py-0 px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-users text-slate-500 text-xs"></i>
                <span>Staff Directory</span>
            </a>
            <a href="{{ route('tenant.finance.collector-collections', ['month' => $selectedMonth]) }}" 
               class="h-[34px] py-0 px-3.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-chart-line text-xs"></i>
                <span>Collection Report</span>
            </a>
        </div>
    </div>

    {{-- 2. KPI Summary Strip (Strictly 6 Cards - AGENTS.md Rule 2.B) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        {{-- Card 1: Active Staff --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Matched Staff</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-slate-900 truncate">{{ number_format($totalStaffCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-indigo-50 text-indigo-600 border-indigo-100 flex items-center justify-center">
                <i class="fas fa-users"></i>
            </div>
        </div>

        {{-- Card 2: Monthly Target --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-blue-700">Assigned Target</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-blue-700 truncate">@currency($totalTargetAmount)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-blue-50 text-blue-600 border-blue-100 flex items-center justify-center">
                <i class="fas fa-bullseye"></i>
            </div>
        </div>

        {{-- Card 3: Collected Amount --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-emerald-700">Total Collected</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-emerald-700 truncate">@currency($totalCollectedAmount)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-emerald-50 text-emerald-600 border-emerald-100 flex items-center justify-center">
                <i class="fas fa-sack-dollar"></i>
            </div>
        </div>

        {{-- Card 4: Achievement % --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-cyan-700">Achievement Rate</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-cyan-800 truncate">{{ $avgAchievementRate }}%</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-cyan-50 text-cyan-600 border-cyan-100 flex items-center justify-center">
                <i class="fas fa-gauge-high"></i>
            </div>
        </div>

        {{-- Card 5: Est. Commission --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-purple-700">Est. Commission</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-purple-700 truncate">@currency($totalCommissionAmount)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-purple-50 text-purple-600 border-purple-100 flex items-center justify-center">
                <i class="fas fa-award"></i>
            </div>
        </div>

        {{-- Card 6: Total Receipts --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-amber-700">Total Receipts</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-amber-700 truncate">{{ number_format($totalReceiptsCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-amber-50 text-amber-600 border-amber-100 flex items-center justify-center">
                <i class="fas fa-receipt"></i>
            </div>
        </div>
    </div>

    {{-- 3. Search & Multi-Filter Toolbar (Customer-Standard 2-Tier Filter System - AGENTS.md Rule 2.C) --}}
    <div class="bg-white p-2.5 rounded-xl border border-slate-200/90 shadow-2xs space-y-2.5"
         x-data="{ showMoreFilters: {{ $isMoreOpen ? 'true' : 'false' }}, searchQuery: '{{ addslashes($search ?? '') }}' }">
        <form method="GET" action="{{ route('tenant.staff.commissions') }}" class="space-y-2.5">
            {{-- Tier 1: Primary Filter Row --}}
            <div class="flex flex-col md:flex-row items-stretch md:items-center gap-2">
                {{-- Intelligent Live Search Box --}}
                <div class="relative flex-1 min-w-[200px]">
                    <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-xs transition-colors"
                       :class="searchQuery ? 'text-cyan-600 font-bold' : 'text-slate-400'"></i>
                    <input type="text" 
                           name="search" 
                           x-model="searchQuery"
                           placeholder="Search Staff Name, Staff ID, Mobile, Designation..." 
                           class="w-full h-[34px] pl-8 pr-7 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    <button type="button" 
                            x-show="searchQuery && searchQuery.length > 0" 
                            @click="searchQuery = ''; $nextTick(() => $el.closest('form').submit())"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs cursor-pointer">
                        <i class="fas fa-times-circle"></i>
                    </button>
                </div>

                {{-- Month Picker --}}
                <div class="w-full md:w-40 flex-shrink-0">
                    <input type="month" 
                           name="month" 
                           value="{{ $selectedMonth }}" 
                           onchange="this.form.submit()"
                           class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono focus:bg-white focus:border-cyan-500 focus:outline-hidden cursor-pointer">
                </div>

                {{-- Scope Filter --}}
                <div class="w-full md:w-40 flex-shrink-0">
                    <select name="scope" 
                            onchange="this.form.submit()" 
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 cursor-pointer">
                        <option value="">All Scopes</option>
                        <option value="isp" {{ $scopeFilter === 'isp' ? 'selected' : '' }}>ISP Core HQ</option>
                        <option value="reseller" {{ $scopeFilter === 'reseller' ? 'selected' : '' }}>Sub-ISP Franchise</option>
                    </select>
                </div>

                {{-- Pagination Selector (per_page) --}}
                <div class="w-full md:w-28 flex-shrink-0">
                    <select name="per_page" 
                            onchange="this.form.submit()" 
                            class="w-full h-[34px] px-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 cursor-pointer">
                        @foreach([15, 20, 30, 50, 100] as $size)
                            <option value="{{ $size }}" {{ (int)$perPage === $size ? 'selected' : '' }}>
                                {{ $size }} / page
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- More Filters Accordion Toggle Button --}}
                <button type="button" 
                        @click="showMoreFilters = !showMoreFilters"
                        class="h-[34px] py-0 px-2.5 rounded-lg border text-xs font-medium transition flex items-center justify-center gap-1.5 cursor-pointer flex-shrink-0"
                        :class="showMoreFilters ? 'bg-cyan-50 border-cyan-200 text-cyan-700' : 'bg-slate-50 hover:bg-slate-100 border-slate-200 text-slate-700'">
                    <i class="fas fa-sliders text-[10px]"></i>
                    <span>More Filters</span>
                    @if($activeMoreCount > 0)
                        <span class="w-4 h-4 rounded-full bg-cyan-600 text-white text-[9px] font-bold flex items-center justify-center">
                            {{ $activeMoreCount }}
                        </span>
                    @endif
                    <i class="fas text-[9px] transition-transform duration-200" 
                       :class="showMoreFilters ? 'fa-chevron-up text-cyan-600' : 'fa-chevron-down text-slate-400'"></i>
                </button>

                {{-- Strict Filter & Reset Buttons Sequence --}}
                <div class="flex items-center gap-1.5 flex-shrink-0 md:ml-auto">
                    <button type="submit" 
                            class="h-[34px] py-0 bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer" 
                            title="Apply Filters">
                        <i class="fas fa-filter text-[10px]"></i>
                        <span>Filter</span>
                    </button>
                    <a href="{{ route('tenant.staff.commissions') }}" 
                       class="h-[34px] py-0 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer" 
                       title="Reset Filters">
                        <i class="fas fa-rotate-left text-[10px]"></i>
                        <span>Reset</span>
                    </a>
                </div>
            </div>

            {{-- Tier 2: Secondary Filters Drawer --}}
            <div x-show="showMoreFilters" 
                 x-cloak 
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="pt-2 border-t border-slate-100 flex flex-wrap items-center gap-2">
                
                {{-- Reseller Partner Filter --}}
                <div class="w-full sm:w-52">
                    <select name="reseller_id" 
                            onchange="this.form.submit()" 
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 cursor-pointer">
                        <option value="">All Sub-ISP Partners</option>
                        @foreach($allResellers as $res)
                            <option value="{{ $res->id }}" {{ (string)$selectedResellerId === (string)$res->id ? 'selected' : '' }}>
                                {{ $res->name }} ({{ $res->code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Role Filter --}}
                <div class="w-full sm:w-44">
                    <select name="role" 
                            onchange="this.form.submit()" 
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 cursor-pointer">
                        <option value="">All Roles</option>
                        <option value="isp_collector" {{ $roleFilter === 'isp_collector' ? 'selected' : '' }}>Bill Collector</option>
                        <option value="reseller_collector" {{ $roleFilter === 'reseller_collector' ? 'selected' : '' }}>Reseller Collector</option>
                        <option value="isp_technician" {{ $roleFilter === 'isp_technician' ? 'selected' : '' }}>Field Technician</option>
                        <option value="isp_manager" {{ $roleFilter === 'isp_manager' ? 'selected' : '' }}>Operations Manager</option>
                    </select>
                </div>

                {{-- Commission Rate % Input --}}
                <div class="w-full sm:w-44 flex items-center gap-1.5">
                    <span class="text-[11px] font-semibold text-slate-600 whitespace-nowrap">Comm. Rate (%):</span>
                    <input type="number" 
                           name="rate" 
                           step="0.1" 
                           min="0" 
                           max="50" 
                           value="{{ $commissionRate }}" 
                           class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:bg-white focus:border-cyan-500">
                </div>
            </div>
        </form>
    </div>

    {{-- 4. Master Compact Table (<table class="saas-table"> - AGENTS.md Rule 2.D) --}}
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-10 text-center font-semibold no-sort">#</th>
                        <th class="text-center font-semibold">Staff ID</th>
                        <th class="text-center font-semibold">Staff Name</th>
                        <th class="text-center font-semibold">Role</th>
                        <th class="text-center font-semibold">Assigned Target</th>
                        <th class="text-center font-semibold">Collected</th>
                        <th class="text-center font-semibold">Achievement</th>
                        <th class="text-center font-semibold">Commission ({{ $commissionRate }}%)</th>
                        <th class="w-12 text-center font-semibold no-sort">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $index => $emp)
                        @php
                            $staffCode = $emp->staff_id ?: ('STF-' . str_pad($emp->id, 4, '0', STR_PAD_LEFT));
                            $roleBadge = $emp->role_badge;
                            $rowPayload = [
                                'id' => $emp->id,
                                'staff_id' => $staffCode,
                                'name' => $emp->name,
                                'role' => $roleBadge['label'],
                                'scope' => $emp->reseller?->name ?: 'ISP Core HQ',
                                'assigned_customers' => $emp->assigned_customers_count,
                                'target_amount' => (float) $emp->target_amount,
                                'collected_amount' => (float) $emp->collected_amount,
                                'receipts_count' => $emp->receipts_count,
                                'achievement_pct' => $emp->achievement_pct,
                                'commission_rate' => $commissionRate,
                                'earned_commission' => (float) $emp->earned_commission,
                            ];
                        @endphp
                        <tr>
                            {{-- 1. Index --}}
                            <td class="text-center font-mono text-slate-400 whitespace-nowrap">
                                {{ $employees->firstItem() + $index }}
                            </td>

                            {{-- 2. Staff ID --}}
                            <td class="text-center font-mono text-slate-700 font-semibold whitespace-nowrap">
                                {{ $staffCode }}
                            </td>

                            {{-- 3. Staff Name --}}
                            <td class="text-left font-medium text-slate-900 whitespace-nowrap">
                                {{ $emp->name }}
                            </td>

                            {{-- 4. Role --}}
                            <td class="text-center whitespace-nowrap">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] border {{ $roleBadge['class'] }}">
                                    <i class="fas {{ $roleBadge['icon'] }} text-[9px]"></i>
                                    <span>{{ $roleBadge['label'] }}</span>
                                </span>
                            </td>

                            {{-- 5. Assigned Target --}}
                            <td class="text-right font-mono font-medium text-slate-700 whitespace-nowrap">
                                @currency($emp->target_amount)
                            </td>

                            {{-- 6. Collected Amount --}}
                            <td class="text-right font-mono font-bold text-emerald-700 whitespace-nowrap">
                                @currency($emp->collected_amount)
                            </td>

                            {{-- 7. Achievement % --}}
                            <td class="text-center font-mono font-semibold whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded text-[10px] border {{ $emp->achievement_pct >= 80 ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : ($emp->achievement_pct >= 40 ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-slate-100 text-slate-600 border-slate-200') }}">
                                    {{ $emp->achievement_pct }}%
                                </span>
                            </td>

                            {{-- 8. Earned Commission --}}
                            <td class="text-right font-mono font-bold text-purple-700 whitespace-nowrap">
                                @currency($emp->earned_commission)
                            </td>

                            {{-- 9. Action (3-Dot Menu) --}}
                            <td class="text-center whitespace-nowrap">
                                <button type="button" 
                                        @click.stop="toggleMenu({{ Js::from($rowPayload) }}, $event)" 
                                        class="w-6 h-6 rounded hover:bg-slate-100 text-slate-500 hover:text-indigo-600 transition cursor-pointer inline-flex items-center justify-center text-xs">
                                    <i class="fas fa-ellipsis-v text-[10px]"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-10 text-slate-400">
                                <div class="max-w-xs mx-auto space-y-2.5">
                                    <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 mx-auto flex items-center justify-center text-base border border-indigo-100 shadow-2xs">
                                        <i class="fas fa-bullseye"></i>
                                    </div>
                                    <h3 class="text-xs font-semibold text-slate-800">No Staff Commission Records Found</h3>
                                    <p class="text-[11px] text-slate-500">No staff targets or collection logs match the selected filters.</p>
                                    <div class="pt-1">
                                        <a href="{{ route('tenant.staff.commissions') }}" 
                                           class="h-[34px] py-0 px-3.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold shadow-xs transition inline-flex items-center gap-1.5">
                                            <i class="fas fa-rotate-left text-[10px]"></i>
                                            <span>Reset Filters</span>
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($employees->hasPages())
            <div class="px-3.5 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between text-xs text-slate-600">
                <div>
                    Showing {{ $employees->firstItem() }} to {{ $employees->lastItem() }} of {{ $employees->total() }} entries
                </div>
                <div>
                    {{ $employees->links() }}
                </div>
            </div>
        @endif
    </div>

    {{-- 5. Floating Action Dropdown Menu (AGENTS.md Rule 2.E) --}}
    <div x-show="activeMenu !== null" 
         @click.away="activeMenu = null"
         :style="menuPos"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         class="fixed z-50 w-52 bg-white rounded-xl shadow-xl border border-slate-200 py-1 text-xs text-slate-700 divide-y divide-slate-100"
         style="display: none;">
        
        <div class="py-0.5">
            <button type="button" 
                    @click="const row = activeMenu; activeMenu = null; viewSlip(row)" 
                    class="w-full px-3 py-1.5 text-left hover:bg-slate-50 flex items-center gap-2 text-slate-700 transition cursor-pointer">
                <i class="fas fa-file-invoice-dollar text-indigo-600 w-3.5 text-[11px]"></i>
                <span>View Commission Breakdown</span>
            </button>
            <a :href="`/admin/staff/${activeMenu?.id}`" 
               class="w-full px-3 py-1.5 text-left hover:bg-slate-50 flex items-center gap-2 text-slate-700 transition cursor-pointer">
                <i class="fas fa-id-card text-slate-500 w-3.5 text-[11px]"></i>
                <span>View Staff Profile</span>
            </a>
            <a :href="`/admin/finance/collector-collections?collector_id=${activeMenu?.id}&month={{ $selectedMonth }}`" 
               class="w-full px-3 py-1.5 text-left hover:bg-slate-50 flex items-center gap-2 text-slate-700 transition cursor-pointer">
                <i class="fas fa-list-check text-emerald-600 w-3.5 text-[11px]"></i>
                <span>Itemized Collection Logs</span>
            </a>
        </div>
    </div>

    {{-- 6. Production-Grade Natural Modal: Commission Breakdown Slip (AGENTS.md Rule 3) --}}
    <div x-show="slipModal.open" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50"
         style="display: none;">
        
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-md overflow-hidden"
             @click.away="slipModal.open = false">
            
            {{-- Soft Natural Header --}}
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-award"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Commission Payout Statement</h3>
                        <p class="text-[10.5px] text-slate-500 font-mono" x-text="(slipModal.data?.staff_id || '') + ' • {{ $periodLabel }}'"></p>
                    </div>
                </div>
                <button type="button" @click="slipModal.open = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <div id="printableCommissionSlip" class="p-4 space-y-3.5 text-xs">
                <div class="p-3.5 rounded-xl border border-purple-200 bg-purple-50/60 text-center space-y-1">
                    <span class="text-[10px] uppercase font-semibold block tracking-wider text-purple-700">Earned Commission (@{{ $commissionRate }}%)</span>
                    <span class="text-xl font-bold font-mono block leading-tight text-purple-900" x-text="formatCurrency(slipModal.data?.earned_commission)"></span>
                </div>

                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200/80 space-y-2 text-[11px]">
                    <div class="flex items-center justify-between py-1 border-b border-slate-200">
                        <span class="text-slate-500">Staff Name:</span>
                        <span class="font-semibold text-slate-800" x-text="slipModal.data?.name"></span>
                    </div>
                    <div class="flex items-center justify-between py-1 border-b border-slate-200">
                        <span class="text-slate-500">Designation / Role:</span>
                        <span class="font-medium text-slate-800" x-text="slipModal.data?.role"></span>
                    </div>
                    <div class="flex items-center justify-between py-1 border-b border-slate-200">
                        <span class="text-slate-500">Assigned Customers:</span>
                        <span class="font-mono font-bold text-slate-800" x-text="slipModal.data?.assigned_customers + ' Subscribers'"></span>
                    </div>
                    <div class="flex items-center justify-between py-1 border-b border-slate-200">
                        <span class="text-slate-500">Monthly Target:</span>
                        <span class="font-mono font-medium text-slate-800" x-text="formatCurrency(slipModal.data?.target_amount)"></span>
                    </div>
                    <div class="flex items-center justify-between py-1 border-b border-slate-200">
                        <span class="text-slate-500">Total Collected:</span>
                        <span class="font-mono font-bold text-emerald-700" x-text="formatCurrency(slipModal.data?.collected_amount)"></span>
                    </div>
                    <div class="flex items-center justify-between py-1 border-b border-slate-200">
                        <span class="text-slate-500">Receipts Issued:</span>
                        <span class="font-mono font-semibold text-slate-800" x-text="slipModal.data?.receipts_count + ' Receipts'"></span>
                    </div>
                    <div class="flex items-center justify-between py-1">
                        <span class="text-slate-500">Target Achievement:</span>
                        <span class="font-mono font-bold text-cyan-800" x-text="slipModal.data?.achievement_pct + '%'"></span>
                    </div>
                </div>
            </div>

            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                <button type="button" @click="slipModal.open = false" class="h-[34px] py-0 px-3.5 border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs rounded-lg transition cursor-pointer">
                    Close
                </button>
                <button type="button" @click="printSlip()" class="h-[34px] py-0 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-xs rounded-lg shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                    <i class="fas fa-print text-xs"></i>
                    <span>Print Statement</span>
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function staffCommissionManager() {
    return {
        activeMenu: null,
        menuPos: { top: '0px', right: '10px', left: 'auto', bottom: 'auto' },
        slipModal: {
            open: false,
            data: null
        },

        toggleMenu(item, event) {
            if (this.activeMenu?.id === item.id) {
                this.activeMenu = null;
                return;
            }
            this.activeMenu = item;
            const rect = event.currentTarget.getBoundingClientRect();
            const dropdownHeight = 140;
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

        viewSlip(row) {
            this.slipModal.data = row;
            this.slipModal.open = true;
        },

        formatCurrency(val) {
            const sym = '{{ $currencySymbol ?? "৳" }}';
            const n = Number(val || 0);
            return sym + ' ' + n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        printSlip() {
            const printContent = document.getElementById('printableCommissionSlip').innerHTML;
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                <head>
                    <title>Commission Statement - ${this.slipModal.data?.name || ''}</title>
                    <style>
                        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; padding: 24px; color: #1e293b; }
                        .text-center { text-align: center; }
                        .font-bold { font-weight: bold; }
                        .font-mono { font-family: monospace; }
                        .flex { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px dashed #e2e8f0; }
                    </style>
                </head>
                <body>
                    <h2 class="text-center" style="margin-bottom: 4px;">{{ $tenant->company_name ?? $tenant->name }}</h2>
                    <p class="text-center" style="font-size: 12px; color: #64748b; margin-top: 0;">Staff Commission Statement ({{ $periodLabel }})</p>
                    <div style="max-width: 420px; margin: 20px auto;">
                        ${printContent}
                    </div>
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.focus();
            setTimeout(() => {
                printWindow.print();
                printWindow.close();
            }, 300);
        }
    };
}
</script>
@endpush
