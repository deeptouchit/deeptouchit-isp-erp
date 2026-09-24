@extends('tenant.layouts.app')

@section('title', 'Daily Cash Handover & Closing - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
<style>
    /* Specialized Thermal POS & A4 Handover Print Styles */
    @media print {
        body * {
            visibility: hidden;
        }
        #printableHandoverArea, #printableHandoverArea * {
            visibility: visible;
        }
        #printableHandoverArea {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            margin: 0;
            padding: 0;
            background: white !important;
        }
        .no-print {
            display: none !important;
        }
    }
</style>
@endpush

@section('content')
<div class="space-y-3" x-data="cashHandoverManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">

    <!-- 1. Top Header Bar (Icon + Title + Action Buttons ONLY - AGENTS.md Rule 2.A) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 bg-white p-3 sm:px-4 sm:py-3 rounded-xl border border-slate-200/90 shadow-2xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 border border-amber-200 flex items-center justify-center text-sm shadow-2xs">
                <i class="fas fa-vault"></i>
            </div>
            <h1 class="text-sm sm:text-base font-bold text-slate-800 tracking-tight">
                Daily Cash Handover &amp; Closing
            </h1>
        </div>

        <div class="flex items-center gap-2">
            <!-- Export CSV -->
            <a href="{{ route('tenant.finance.cash-handover.export', request()->query()) }}" 
               class="border border-slate-300 hover:bg-slate-50 text-slate-700 font-medium text-xs px-3 py-1.5 rounded-lg transition flex items-center gap-1.5 shadow-2xs">
                <i class="fas fa-file-csv text-emerald-600 text-xs"></i>
                <span class="hidden sm:inline">Export</span> CSV
            </a>

            <!-- New Handover Button -->
            <button type="button" 
                    @click="openCreateModal()" 
                    class="bg-amber-600 hover:bg-amber-700 text-white font-semibold text-xs px-3.5 py-1.5 rounded-lg transition flex items-center gap-1.5 shadow-xs cursor-pointer">
                <i class="fas fa-plus text-xs"></i>
                <span>New Cash Handover</span>
            </button>
        </div>
    </div>

    <!-- 2. KPI Summary Strip (Strictly 6 Cards - AGENTS.md Rule 2.B) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        <!-- Card 1: Total Approved Vault Cash -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between min-w-0">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Approved in Vault</span>
                <span class="text-[13px] font-bold font-mono text-emerald-700 leading-tight block truncate">
                    @currency($totalApprovedVault)
                </span>
            </div>
            <div class="w-6 h-6 rounded-md bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-[10px] flex-shrink-0">
                <i class="fas fa-vault"></i>
            </div>
        </div>

        <!-- Card 2: Pending Handover Amount -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between min-w-0">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Pending Approval</span>
                <span class="text-[13px] font-bold font-mono text-amber-700 leading-tight block truncate">
                    @currency($totalPendingCash)
                </span>
            </div>
            <div class="w-6 h-6 rounded-md bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center text-[10px] flex-shrink-0">
                <i class="fas fa-hourglass-half"></i>
            </div>
        </div>

        <!-- Card 3: Today's Closing Cash -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between min-w-0">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Today's Closing</span>
                <span class="text-[13px] font-bold font-mono text-cyan-700 leading-tight block truncate">
                    @currency($todayClosingCash)
                </span>
            </div>
            <div class="w-6 h-6 rounded-md bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-[10px] flex-shrink-0">
                <i class="fas fa-calendar-day"></i>
            </div>
        </div>

        <!-- Card 4: Shortage Discrepancy -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between min-w-0">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Total Shortage</span>
                <span class="text-[13px] font-bold font-mono text-rose-700 leading-tight block truncate">
                    @currency($totalShortageAmount)
                </span>
            </div>
            <div class="w-6 h-6 rounded-md bg-rose-50 text-rose-600 border border-rose-100 flex items-center justify-center text-[10px] flex-shrink-0">
                <i class="fas fa-scale-unbalanced"></i>
            </div>
        </div>

        <!-- Card 5: Total Handover Vouchers -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between min-w-0">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Total Vouchers</span>
                <span class="text-[13px] font-bold font-mono text-indigo-700 leading-tight block truncate">
                    {{ number_format($totalVouchersCount) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center text-[10px] flex-shrink-0">
                <i class="fas fa-file-invoice-dollar"></i>
            </div>
        </div>

        <!-- Card 6: Approval Rate -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between min-w-0">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Approval Rate</span>
                <span class="text-[13px] font-bold font-mono text-blue-700 leading-tight block truncate">
                    {{ $approvalRate }}%
                </span>
            </div>
            <div class="w-6 h-6 rounded-md bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center text-[10px] flex-shrink-0">
                <i class="fas fa-chart-pie"></i>
            </div>
        </div>
    </div>

    <!-- 3. Search & Multi-Filter Toolbar (AGENTS.md Rule 2.C) -->
    <div class="bg-white p-2.5 rounded-xl border border-slate-200 shadow-2xs">
        <form method="GET" action="{{ route('tenant.finance.cash-handover') }}" class="space-y-2">
            <!-- Row 1: Search & Date / Period Filters -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-12 gap-2">
                <!-- Search Box -->
                <div class="md:col-span-4 relative">
                    <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" 
                           name="search" 
                           value="{{ $search }}" 
                           placeholder="Voucher No, Collector, Remarks..." 
                           class="w-full pl-8 pr-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg text-slate-800 placeholder-slate-400 focus:bg-white focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition">
                </div>

                <!-- Quick Period Preset -->
                <div class="md:col-span-2">
                    <select name="period" 
                            id="periodSelect"
                            onchange="handlePeriodChange(this.value)"
                            class="w-full px-2.5 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg text-slate-800 focus:bg-white focus:border-cyan-500 transition font-medium">
                        <option value="all" {{ ($period ?? '') === 'all' || empty($period) ? 'selected' : '' }}>All Dates</option>
                        <option value="today" {{ ($period ?? '') === 'today' ? 'selected' : '' }}>Today</option>
                        <option value="yesterday" {{ ($period ?? '') === 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                        <option value="this_week" {{ ($period ?? '') === 'this_week' ? 'selected' : '' }}>This Week</option>
                        <option value="this_month" {{ ($period ?? '') === 'this_month' ? 'selected' : '' }}>This Month</option>
                        <option value="last_month" {{ ($period ?? '') === 'last_month' ? 'selected' : '' }}>Last Month</option>
                        <option value="custom" {{ ($period ?? '') === 'custom' ? 'selected' : '' }}>Custom Range</option>
                    </select>
                </div>

                <!-- Date From -->
                <div class="md:col-span-3">
                    <div class="relative">
                        <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none">
                            <i class="fas fa-calendar-day"></i>
                        </span>
                        <input type="date" 
                               name="date_from" 
                               id="dateFromInput"
                               value="{{ $dateFrom }}" 
                               title="From Date"
                               class="w-full pl-8 pr-2.5 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg text-slate-800 focus:bg-white focus:border-cyan-500 transition">
                    </div>
                </div>

                <!-- Date To -->
                <div class="md:col-span-3">
                    <div class="relative">
                        <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none">
                            <i class="fas fa-calendar-check"></i>
                        </span>
                        <input type="date" 
                               name="date_to" 
                               id="dateToInput"
                               value="{{ $dateTo }}" 
                               title="To Date"
                               class="w-full pl-8 pr-2.5 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg text-slate-800 focus:bg-white focus:border-cyan-500 transition">
                    </div>
                </div>
            </div>

            <!-- Row 2: Collector, Shift, Status, Per Page & Action Buttons -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-12 gap-2 pt-1 border-t border-slate-100">
                <!-- Collector Filter -->
                <div class="md:col-span-3">
                    <select name="collector_id" 
                            class="w-full px-2.5 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg text-slate-800 focus:bg-white focus:border-cyan-500 transition">
                        <option value="all" {{ $collectorFilter === 'all' ? 'selected' : '' }}>All Staff / Collectors</option>
                        @foreach($allCollectors ?? [] as $c)
                            <option value="{{ $c->id }}" {{ (string)$collectorFilter === (string)$c->id ? 'selected' : '' }}>
                                {{ $c->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Shift Filter -->
                <div class="md:col-span-3">
                    <select name="shift" 
                            class="w-full px-2.5 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg text-slate-800 focus:bg-white focus:border-cyan-500 transition">
                        <option value="all" {{ $shiftFilter === 'all' ? 'selected' : '' }}>All Shifts</option>
                        <option value="daily" {{ $shiftFilter === 'daily' ? 'selected' : '' }}>Daily Closing</option>
                        <option value="morning" {{ $shiftFilter === 'morning' ? 'selected' : '' }}>Morning Shift</option>
                        <option value="evening" {{ $shiftFilter === 'evening' ? 'selected' : '' }}>Evening Shift</option>
                        <option value="night" {{ $shiftFilter === 'night' ? 'selected' : '' }}>Night Shift</option>
                        <option value="full_day" {{ $shiftFilter === 'full_day' ? 'selected' : '' }}>Full Day Closing</option>
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="md:col-span-2">
                    <select name="status" 
                            class="w-full px-2.5 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg text-slate-800 focus:bg-white focus:border-cyan-500 transition">
                        <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>All Statuses</option>
                        <option value="pending" {{ $statusFilter === 'pending' ? 'selected' : '' }}>Pending Verification</option>
                        <option value="approved" {{ $statusFilter === 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ $statusFilter === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>

                <!-- Per Page -->
                <div class="md:col-span-1">
                    <select name="per_page" 
                            class="w-full px-2 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg text-slate-800 focus:bg-white focus:border-cyan-500 transition">
                        <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10</option>
                        <option value="20" {{ $perPage == 20 ? 'selected' : '' }}>20</option>
                        <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                        <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                    </select>
                </div>

                <!-- Strict Action Buttons: [Filter] FIRST, [Reset] SECOND (AGENTS.md Rule 2.C) -->
                <div class="md:col-span-3 flex items-center justify-end gap-1.5">
                    <button type="submit" 
                            class="flex-1 bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-1.5 px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer" 
                            title="Apply Filters">
                        <i class="fas fa-filter text-[10px]"></i>
                        <span>Filter</span>
                    </button>

                    <a href="{{ route('tenant.finance.cash-handover') }}" 
                       class="py-1.5 px-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-lg border border-slate-200/80 transition inline-flex items-center justify-center gap-1 cursor-pointer flex-shrink-0" 
                       title="Reset Filters">
                        <i class="fas fa-rotate-left text-[10px]"></i>
                        <span>Reset</span>
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- 4. Master Compact Table (.saas-table Pure CSS System - AGENTS.md Rule 2.D) -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-10 text-center">#</th>
                        <th class="w-36">Voucher No</th>
                        <th>Collector Staff</th>
                        <th class="text-right w-28">Handed Over</th>
                        <th class="w-32 text-center">Discrepancy</th>
                        <th class="text-center w-28">Status</th>
                        <th class="w-28">Date</th>
                        <th class="no-sort text-center w-10">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($handovers as $index => $h)
                        @php
                            $badge = $h->status_badge;
                            $disc = $h->discrepancy_badge;
                            $handoverJson = [
                                'id' => $h->id,
                                'handover_no' => $h->handover_no,
                                'handover_date' => $h->handover_date->format('Y-m-d'),
                                'collector_id' => $h->collector_id,
                                'collector_name' => $h->collector?->name ?? 'Staff Collector',
                                'system_collected_amount' => (float) $h->system_collected_amount,
                                'handed_over_amount' => (float) $h->handed_over_amount,
                                'shortage_amount' => (float) $h->shortage_amount,
                                'excess_amount' => (float) $h->excess_amount,
                                'status' => $h->status,
                            ];
                        @endphp
                        <tr>
                            <!-- 0. Row Index -->
                            <td class="text-center font-mono text-slate-500">{{ $handovers->firstItem() + $index }}</td>

                            <!-- 1. Voucher No (Click to open Print / Detail Modal) -->
                            <td class="font-mono text-amber-800 font-bold">
                                <button type="button" 
                                        @click="openVoucherModal({{ $h->id }})" 
                                        class="hover:underline hover:text-amber-600 cursor-pointer flex items-center gap-1"
                                        title="Click to view & print Handover Voucher">
                                    <i class="fas fa-file-invoice-dollar text-[10px] text-amber-500"></i>
                                    <span>{{ $h->handover_no }}</span>
                                </button>
                            </td>

                            <!-- 2. Collector Staff (Strict Single Data per Cell) -->
                            <td class="font-semibold text-slate-900">
                                {{ $h->collector?->name ?? 'Unknown Staff' }}
                            </td>

                            <!-- 3. Handed Over Amount -->
                            <td class="text-right font-mono font-bold text-emerald-700">
                                @currency($h->handed_over_amount)
                            </td>

                            <!-- 4. Shortage / Excess Discrepancy -->
                            <td class="text-center font-mono text-[11px]">
                                @if($h->shortage_amount > 0)
                                    <span class="inline-flex items-center gap-1 text-rose-700 font-bold bg-rose-50 border border-rose-200 px-1.5 py-0.5 rounded">
                                        <i class="fas fa-arrow-trend-down text-[9px]"></i>
                                        -@currency($h->shortage_amount)
                                    </span>
                                @elseif($h->excess_amount > 0)
                                    <span class="inline-flex items-center gap-1 text-indigo-700 font-bold bg-indigo-50 border border-indigo-200 px-1.5 py-0.5 rounded">
                                        <i class="fas fa-arrow-trend-up text-[9px]"></i>
                                        +@currency($h->excess_amount)
                                    </span>
                                @else
                                    <span class="text-emerald-700 font-medium">Balanced (0)</span>
                                @endif
                            </td>

                            <!-- 5. Status -->
                            <td class="text-center">
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold border {{ $badge['class'] }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $badge['dot'] }}"></span>
                                    <span>{{ $badge['label'] }}</span>
                                </span>
                            </td>

                            <!-- 6. Date -->
                            <td class="font-mono text-slate-700 text-xs">
                                {{ $h->handover_date->format('d M Y') }}
                            </td>

                            <!-- 7. 3-Dot Action Button -->
                            <td class="text-center">
                                <button type="button" 
                                        @click="toggleMenu(@js($handoverJson), $event)" 
                                        class="w-6 h-6 rounded-md hover:bg-slate-100 text-slate-500 hover:text-slate-800 transition flex items-center justify-center mx-auto cursor-pointer"
                                        title="Handover Actions">
                                    <i class="fas fa-ellipsis-v text-[10px]"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-10 text-slate-400">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 text-base">
                                        <i class="fas fa-vault"></i>
                                    </div>
                                    <p class="text-xs font-medium text-slate-600">No daily cash handovers found matching your criteria.</p>
                                    <button type="button" 
                                            @click="openCreateModal()" 
                                            class="text-xs text-amber-600 font-semibold hover:underline">
                                        + Submit New Cash Handover
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Footer -->
        @if($handovers->hasPages())
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200/90 flex items-center justify-between text-xs text-slate-600">
                <div>
                    Showing <span class="font-semibold">{{ $handovers->firstItem() ?? 0 }}</span> to <span class="font-semibold">{{ $handovers->lastItem() ?? 0 }}</span> of <span class="font-semibold">{{ $handovers->total() }}</span> vouchers
                </div>
                <div>
                    {{ $handovers->links() }}
                </div>
            </div>
        @endif
    </div>

    <!-- 5. Global Floating 3-Dot Action Menu (AGENTS.md Rule 2.E) -->
    <div x-show="activeMenu" 
         x-cloak 
         @click.away="activeMenu = null"
         class="fixed z-50 bg-white border border-slate-200 rounded-xl shadow-xl w-52 py-1 text-xs divide-y divide-slate-100 animate-in fade-in zoom-in-95 duration-100"
         :style="`top: ${menuPos.top}; bottom: ${menuPos.bottom}; right: ${menuPos.right}; left: ${menuPos.left};`"
         style="display: none;">
        
        <!-- Group 1: Voucher & Print -->
        <div class="py-1">
            <button type="button" 
                    @click="const item = activeMenu; activeMenu = null; openVoucherModal(item.id)" 
                    class="w-full text-left px-3 py-1.5 hover:bg-amber-50/80 hover:text-amber-800 text-slate-700 flex items-center gap-2 transition cursor-pointer">
                <i class="fas fa-print w-4 text-center text-amber-600 text-[11px]"></i>
                <span class="font-medium">View &amp; Print Voucher</span>
            </button>
        </div>

        <!-- Group 2: Verification Actions -->
        <div class="py-1">
            <template x-if="activeMenu?.status === 'pending'">
                <button type="button" 
                        @click="const item = activeMenu; activeMenu = null; openApproveModal(item)" 
                        class="w-full text-left px-3 py-1.5 hover:bg-emerald-50/80 text-emerald-700 flex items-center gap-2 transition cursor-pointer">
                    <i class="fas fa-check-circle w-4 text-center text-emerald-600 text-[11px]"></i>
                    <span class="font-semibold">Verify &amp; Approve to Vault</span>
                </button>
            </template>

            <template x-if="activeMenu?.status === 'pending'">
                <button type="button" 
                        @click="const item = activeMenu; activeMenu = null; openRejectModal(item)" 
                        class="w-full text-left px-3 py-1.5 hover:bg-rose-50/80 text-rose-600 flex items-center gap-2 transition cursor-pointer">
                    <i class="fas fa-circle-xmark w-4 text-center text-rose-500 text-[11px]"></i>
                    <span class="font-medium">Reject Handover</span>
                </button>
            </template>
        </div>
    </div>

    <!-- 6. Production-Grade Natural Modal: Submit Daily Cash Handover -->
    <div x-show="createModal.open" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;">
        
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden"
             @click.away="if (!createModal.loading) createModal.open = false"
             x-transition:enter="transition ease-out duration-200 transform"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150 transform"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">
            
            <!-- Soft Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center text-xs flex-shrink-0 shadow-2xs">
                        <i class="fas fa-hand-holding-dollar"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Submit Daily Cash Handover</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">Close shift and submit physical cash to accounts</p>
                    </div>
                </div>
                <button type="button" 
                        @click="createModal.open = false" 
                        :disabled="createModal.loading"
                        class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <!-- Form Body -->
            <form @submit.prevent="submitCreateHandover()">
                <div class="p-4 space-y-3.5 text-xs max-h-[75vh] overflow-y-auto">
                    
                    <!-- Collector & Date Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="block text-slate-700 font-semibold text-[11px]">
                                Collector Staff <span class="text-rose-500">*</span>
                            </label>
                            <select x-model="createModal.form.collector_id" 
                                    @change="fetchCollectorLiveCash()" 
                                    required 
                                    class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 text-slate-800 focus:bg-white focus:border-amber-500 transition">
                                <option value="">-- Select Collector Staff --</option>
                                @foreach($allCollectors ?? [] as $c)
                                    <option value="{{ $c->id }}">
                                        {{ $c->name }} ({{ $c->email }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="space-y-1">
                            <label class="block text-slate-700 font-semibold text-[11px]">
                                Handover Date <span class="text-rose-500">*</span>
                            </label>
                            <input type="date" 
                                   x-model="createModal.form.handover_date" 
                                   @change="fetchCollectorLiveCash()" 
                                   required 
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 text-slate-800 focus:bg-white focus:border-amber-500 transition">
                        </div>
                    </div>

                    <!-- Live System Cash Summary Card -->
                    <div class="p-3 bg-amber-50/70 border border-amber-200/90 rounded-lg space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-bold text-amber-900 flex items-center gap-1.5">
                                <i class="fas fa-calculator text-amber-600"></i>
                                <span>System Cash Collection Summary</span>
                            </span>
                            <span class="text-[10px] text-amber-700 font-mono" x-show="createModal.fetchingLive">
                                <i class="fas fa-spinner fa-spin"></i> Calculating...
                            </span>
                        </div>

                        <div class="grid grid-cols-3 gap-2 text-center">
                            <div class="bg-white p-2 rounded-md border border-amber-100 shadow-2xs">
                                <span class="text-[9px] uppercase tracking-wider text-slate-500 block">System Cash</span>
                                <span class="text-xs font-bold font-mono text-slate-900 block" x-text="createModal.liveStats.cash_total_formatted || '{{ $currencySymbol ?? '৳' }}0.00'"></span>
                            </div>
                            <div class="bg-white p-2 rounded-md border border-amber-100 shadow-2xs">
                                <span class="text-[9px] uppercase tracking-wider text-slate-500 block">Digital MFS</span>
                                <span class="text-xs font-mono text-cyan-700 block" x-text="createModal.liveStats.digital_total_formatted || '{{ $currencySymbol ?? '৳' }}0.00'"></span>
                            </div>
                            <div class="bg-white p-2 rounded-md border border-amber-100 shadow-2xs">
                                <span class="text-[9px] uppercase tracking-wider text-slate-500 block">Receipts Count</span>
                                <span class="text-xs font-mono font-bold text-slate-800 block" x-text="createModal.liveStats.receipts_count || 0"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Handed Over Amount & Shift Type -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="block text-slate-700 font-semibold text-[11px]">
                                Physical Cash Handed ({{ $currencySymbol ?? '৳' }}) <span class="text-rose-500">*</span>
                            </label>
                            <input type="number" 
                                   step="0.01" 
                                   min="0" 
                                   x-model="createModal.form.handed_over_amount" 
                                   required 
                                   placeholder="0.00" 
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 text-slate-800 font-mono font-bold focus:bg-white focus:border-amber-500 transition">
                        </div>

                        <div class="space-y-1">
                            <label class="block text-slate-700 font-semibold text-[11px]">
                                Shift Type <span class="text-rose-500">*</span>
                            </label>
                            <select x-model="createModal.form.shift_type" 
                                    required 
                                    class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 text-slate-800 focus:bg-white focus:border-amber-500 transition">
                                <option value="daily">Daily Closing (Standard)</option>
                                <option value="morning">Morning Shift</option>
                                <option value="evening">Evening Shift</option>
                                <option value="night">Night Shift</option>
                                <option value="full_day">Full Day Closing</option>
                            </select>
                        </div>
                    </div>

                    <!-- Real-Time Discrepancy Indicator -->
                    <template x-if="createModal.form.handed_over_amount !== '' && createModal.liveStats.cash_total !== undefined">
                        <div class="p-2.5 rounded-lg border text-xs flex items-center justify-between"
                             :class="calculatedDiscrepancy.class">
                            <div class="flex items-center gap-1.5">
                                <i class="fas" :class="calculatedDiscrepancy.icon"></i>
                                <span class="font-semibold" x-text="calculatedDiscrepancy.message"></span>
                            </div>
                            <span class="font-mono font-bold text-xs" x-text="calculatedDiscrepancy.amountFormatted"></span>
                        </div>
                    </template>

                    <!-- Remarks / Notes -->
                    <div class="space-y-1">
                        <label class="block text-slate-700 font-semibold text-[11px]">
                            Collector Remarks / Handover Notes
                        </label>
                        <input type="text" 
                               x-model="createModal.form.notes" 
                               placeholder="e.g. Handed over cash to main cashier, Area A collection..." 
                               class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 text-slate-800 focus:bg-white focus:border-amber-500 transition">
                    </div>

                </div>

                <!-- Footer -->
                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                    <button type="button" 
                            @click="createModal.open = false" 
                            :disabled="createModal.loading"
                            class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition shadow-2xs cursor-pointer">
                        Cancel
                    </button>

                    <button type="submit" 
                            :disabled="createModal.loading"
                            class="bg-amber-600 hover:bg-amber-700 disabled:opacity-50 text-white font-semibold text-xs px-4 py-1.5 rounded-lg transition flex items-center gap-1.5 shadow-xs cursor-pointer">
                        <i class="fas" :class="createModal.loading ? 'fa-spinner fa-spin' : 'fa-check'"></i>
                        <span x-text="createModal.loading ? 'Submitting Handover...' : 'Submit Handover Voucher'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 7. Production-Grade Natural Modal: View / Print Handover Voucher (Dual Layout) -->
    <div x-show="voucherModal.open" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;">
        
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden"
             @click.away="voucherModal.open = false"
             x-transition:enter="transition ease-out duration-200 transform"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150 transform"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">
            
            <!-- Soft Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between no-print">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center text-xs flex-shrink-0 shadow-2xs">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Cash Handover Voucher</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">Official shift closing &amp; vault transfer slip</p>
                    </div>
                </div>

                <div class="flex items-center gap-1.5">
                    <button type="button" 
                            @click="printVoucher()" 
                            class="bg-amber-600 hover:bg-amber-700 text-white font-medium text-xs px-3 py-1.5 rounded-lg transition flex items-center gap-1 shadow-xs cursor-pointer">
                        <i class="fas fa-print text-xs"></i>
                        <span>Print</span>
                    </button>

                    <button type="button" 
                            @click="voucherModal.open = false" 
                            class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>
            </div>

            <!-- Printable Voucher Area -->
            <div id="printableHandoverArea" class="p-5 text-xs text-slate-800 font-sans max-h-[75vh] overflow-y-auto">
                <template x-if="voucherModal.handover">
                    <div class="space-y-4">
                        
                        <!-- Company Header -->
                        <div class="text-center border-b border-dashed border-slate-300 pb-3">
                            <h2 class="text-base font-bold text-slate-900 tracking-tight" x-text="voucherModal.handover.company.name"></h2>
                            <p class="text-[10.5px] text-slate-500 mt-0.5" x-text="voucherModal.handover.company.address"></p>
                            <p class="text-[10.5px] text-slate-600 font-mono mt-0.5" x-text="'Helpline: ' + voucherModal.handover.company.phone"></p>
                            <div class="mt-2 inline-block px-2.5 py-0.5 bg-slate-900 text-amber-400 font-mono font-bold text-[11px] rounded tracking-wider uppercase">
                                Daily Cash Handover Voucher
                            </div>
                        </div>

                        <!-- Meta Info Grid -->
                        <div class="grid grid-cols-2 gap-2 text-[11px] font-mono border-b border-dashed border-slate-200 pb-2.5">
                            <div>
                                <span class="text-slate-400 block text-[9px] uppercase">Voucher No</span>
                                <span class="font-bold text-slate-900" x-text="voucherModal.handover.handover_no"></span>
                            </div>
                            <div class="text-right">
                                <span class="text-slate-400 block text-[9px] uppercase">Handover Date</span>
                                <span class="text-slate-800" x-text="voucherModal.handover.handover_date"></span>
                            </div>
                        </div>

                        <!-- Collector & Shift Info -->
                        <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80 space-y-1 text-[11px]">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500">Collector Staff:</span>
                                <span class="font-bold text-slate-900" x-text="voucherModal.handover.collector.name"></span>
                            </div>
                            <div class="flex items-center justify-between text-slate-500 text-[10.5px]">
                                <span>Shift Type:</span>
                                <span class="font-medium text-slate-800" x-text="voucherModal.handover.shift_label"></span>
                            </div>
                            <div class="flex items-center justify-between text-slate-500 text-[10.5px]">
                                <span>Money Receipts Covered:</span>
                                <span class="font-mono font-bold text-slate-800" x-text="voucherModal.handover.total_receipts_count + ' Receipts'"></span>
                            </div>
                        </div>

                        <!-- Financial Summary -->
                        <div class="space-y-1.5 text-xs">
                            <div class="flex justify-between py-1 border-b border-slate-100">
                                <span class="text-slate-600">System Cash Collections:</span>
                                <span class="font-mono font-bold text-slate-900" x-text="voucherModal.handover.system_collected_formatted"></span>
                            </div>
                            <div class="flex justify-between py-1.5 border-b-2 border-slate-800 font-bold">
                                <span class="text-slate-900">Physical Cash Handed Over:</span>
                                <span class="font-mono text-emerald-700 text-sm" x-text="voucherModal.handover.handed_over_formatted"></span>
                            </div>
                            
                            <!-- Shortage / Excess Details -->
                            <template x-if="voucherModal.handover.shortage_amount > 0">
                                <div class="flex justify-between py-1 text-rose-700 font-bold border-b border-rose-100 bg-rose-50/50 px-2 rounded">
                                    <span>Cash Shortage:</span>
                                    <span class="font-mono" x-text="'-' + voucherModal.handover.shortage_formatted"></span>
                                </div>
                            </template>

                            <template x-if="voucherModal.handover.excess_amount > 0">
                                <div class="flex justify-between py-1 text-indigo-700 font-bold border-b border-indigo-100 bg-indigo-50/50 px-2 rounded">
                                    <span>Cash Excess:</span>
                                    <span class="font-mono" x-text="'+' + voucherModal.handover.excess_formatted"></span>
                                </div>
                            </template>

                            <div class="flex justify-between py-1 text-slate-500 text-[11px] font-mono" x-show="voucherModal.handover.digital_collected_amount > 0">
                                <span>Digital MFS (Non-Cash):</span>
                                <span class="text-slate-700" x-text="voucherModal.handover.digital_collected_formatted"></span>
                            </div>

                            <div class="flex justify-between py-1 text-slate-500 text-[11px]">
                                <span>Approval Status:</span>
                                <span class="font-bold uppercase tracking-wider" :class="voucherModal.handover.status === 'approved' ? 'text-emerald-700' : (voucherModal.handover.status === 'rejected' ? 'text-rose-700' : 'text-amber-700')" x-text="voucherModal.handover.status"></span>
                            </div>
                        </div>

                        <!-- Signatures & Notes -->
                        <div class="pt-4 border-t border-dashed border-slate-300 space-y-4 text-center">
                            <div class="flex justify-between items-end text-[10px] text-slate-500 pt-3">
                                <div>
                                    <div class="w-24 border-b border-slate-300 mb-1"></div>
                                    <span x-text="voucherModal.handover.collector.name"></span>
                                    <span class="block text-[8.5px] text-slate-400">Collector Signature</span>
                                </div>
                                <div>
                                    <div class="w-24 border-b border-slate-300 mb-1"></div>
                                    <span x-text="voucherModal.handover.verifier?.name || 'Accounts Manager'"></span>
                                    <span class="block text-[8.5px] text-slate-400">Cashier / Vault Sign</span>
                                </div>
                            </div>

                            <p class="text-[9.5px] text-slate-400 font-mono">
                                Official shift closing record generated from SomitySoft ISP Management Platform.
                            </p>
                        </div>

                    </div>
                </template>
            </div>

            <!-- Footer -->
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end gap-2 no-print">
                <button type="button" 
                        @click="voucherModal.open = false" 
                        class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition shadow-2xs cursor-pointer">
                    Close
                </button>
                <button type="button" 
                        @click="printVoucher()" 
                        class="bg-amber-600 hover:bg-amber-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg transition flex items-center gap-1.5 shadow-xs cursor-pointer">
                    <i class="fas fa-print"></i>
                    <span>Print Voucher</span>
                </button>
            </div>
        </div>
    </div>

    <!-- 8. Production-Grade Natural Modal: Verify & Approve Handover -->
    <div x-show="approveModal.open" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
         style="display: none;">
        
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-sm overflow-hidden"
             @click.away="if (!approveModal.loading) approveModal.open = false">
            
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-xs flex-shrink-0 shadow-2xs">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Verify &amp; Approve to Vault</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">Transfer physical cash into ISP main vault</p>
                    </div>
                </div>
                <button type="button" 
                        @click="approveModal.open = false" 
                        class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <form @submit.prevent="submitApproveHandover()">
                <div class="p-4 space-y-3 text-xs">
                    <div class="p-2.5 bg-emerald-50 border border-emerald-200 rounded-lg text-emerald-900 space-y-1">
                        <div class="flex justify-between font-bold">
                            <span>Voucher No:</span>
                            <span class="font-mono" x-text="approveModal.handover?.handover_no"></span>
                        </div>
                        <div class="flex justify-between font-bold">
                            <span>Handed Over Cash:</span>
                            <span class="font-mono text-emerald-700" x-text="'{{ $currencySymbol ?? '৳' }}' + parseFloat(approveModal.handover?.handed_over_amount || 0).toFixed(2)"></span>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="block text-slate-700 font-semibold text-[11px]">Manager Verification Remarks</label>
                        <input type="text" 
                               x-model="approveModal.manager_remarks" 
                               placeholder="e.g. Physical cash counted and verified into vault..." 
                               class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs p-2 text-slate-800 focus:bg-white focus:border-emerald-500 transition">
                    </div>
                </div>

                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                    <button type="button" 
                            @click="approveModal.open = false" 
                            class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition shadow-2xs cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" 
                            :disabled="approveModal.loading" 
                            class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg transition flex items-center gap-1.5 shadow-xs cursor-pointer">
                        <i class="fas" :class="approveModal.loading ? 'fa-spinner fa-spin' : 'fa-check'"></i>
                        <span x-text="approveModal.loading ? 'Approving...' : 'Approve &amp; Receive Cash'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 9. Production-Grade Natural Modal: Reject Handover -->
    <div x-show="rejectModal.open" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
         style="display: none;">
        
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-sm overflow-hidden"
             @click.away="if (!rejectModal.loading) rejectModal.open = false">
            
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-rose-50 text-rose-600 border border-rose-100 flex items-center justify-center text-xs flex-shrink-0 shadow-2xs">
                        <i class="fas fa-circle-xmark"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Reject Cash Handover</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">Return handover to collector for correction</p>
                    </div>
                </div>
                <button type="button" 
                        @click="rejectModal.open = false" 
                        class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <form @submit.prevent="submitRejectHandover()">
                <div class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block text-slate-700 font-semibold text-[11px]">Reason for Rejection <span class="text-rose-500">*</span></label>
                        <textarea x-model="rejectModal.manager_remarks" 
                                  required 
                                  rows="2" 
                                  placeholder="e.g. Cash count mismatch, Unresolved shortage..." 
                                  class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs p-2 text-slate-800 focus:bg-white focus:border-rose-500 transition"></textarea>
                    </div>
                </div>

                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                    <button type="button" 
                            @click="rejectModal.open = false" 
                            class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition shadow-2xs cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" 
                            :disabled="rejectModal.loading" 
                            class="bg-rose-600 hover:bg-rose-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg transition flex items-center gap-1.5 shadow-xs cursor-pointer">
                        <i class="fas" :class="rejectModal.loading ? 'fa-spinner fa-spin' : 'fa-ban'"></i>
                        <span x-text="rejectModal.loading ? 'Rejecting...' : 'Confirm Rejection'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 10. Global Toast Alert -->
    <div x-show="toast.show" 
         x-cloak 
         class="fixed bottom-5 right-5 z-50 flex items-center gap-2 px-4 py-2.5 rounded-xl shadow-2xl border text-xs font-medium transition-all"
         :class="toast.type === 'success' ? 'bg-emerald-900 text-emerald-100 border-emerald-700' : 'bg-rose-900 text-rose-100 border-rose-700'"
         style="display: none;">
        <i class="fas" :class="toast.type === 'success' ? 'fa-check-circle text-emerald-400' : 'fa-exclamation-circle text-rose-400'"></i>
        <span x-text="toast.message"></span>
    </div>

</div>
@endsection

@push('scripts')
<script>
function handlePeriodChange(val) {
    const fromInput = document.getElementById('dateFromInput');
    const toInput = document.getElementById('dateToInput');
    if (!fromInput || !toInput) return;

    const today = new Date();
    const formatDate = (d) => {
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    };

    if (val === 'today') {
        fromInput.value = formatDate(today);
        toInput.value = formatDate(today);
    } else if (val === 'yesterday') {
        const y = new Date();
        y.setDate(y.getDate() - 1);
        fromInput.value = formatDate(y);
        toInput.value = formatDate(y);
    } else if (val === 'this_week') {
        const start = new Date(today);
        const day = start.getDay();
        const diff = start.getDate() - day + (day === 0 ? -6 : 1);
        start.setDate(diff);
        const end = new Date(start);
        end.setDate(start.getDate() + 6);
        fromInput.value = formatDate(start);
        toInput.value = formatDate(end);
    } else if (val === 'this_month') {
        const start = new Date(today.getFullYear(), today.getMonth(), 1);
        const end = new Date(today.getFullYear(), today.getMonth() + 1, 0);
        fromInput.value = formatDate(start);
        toInput.value = formatDate(end);
    } else if (val === 'last_month') {
        const start = new Date(today.getFullYear(), today.getMonth() - 1, 1);
        const end = new Date(today.getFullYear(), today.getMonth(), 0);
        fromInput.value = formatDate(start);
        toInput.value = formatDate(end);
    } else if (val === 'all') {
        fromInput.value = '';
        toInput.value = '';
    }
}

function cashHandoverManager() {
    return {
        activeMenu: null,
        menuPos: { top: 'auto', bottom: 'auto', right: 'auto', left: 'auto' },

        toast: {
            show: false,
            message: '',
            type: 'success',
            timeout: null
        },

        showToast(message, type = 'success') {
            const iconType = (type === 'error' || type === 'danger') ? 'error' : (type === 'warning' ? 'warning' : 'success');
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: iconType,
                    title: message,
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    customClass: {
                        popup: 'rounded-xl text-xs font-sans shadow-lg'
                    }
                });
            } else {
                this.toast.message = message;
                this.toast.type = type;
                this.toast.show = true;
                if (this.toast.timeout) clearTimeout(this.toast.timeout);
                this.toast.timeout = setTimeout(() => {
                    this.toast.show = false;
                }, 3500);
            }
        },

        createModal: {
            open: false,
            loading: false,
            fetchingLive: false,
            liveStats: {},
            form: {
                collector_id: '',
                handover_date: '{{ Carbon\Carbon::today()->toDateString() }}',
                shift_type: 'daily',
                handed_over_amount: '',
                notes: '',
            }
        },

        voucherModal: {
            open: false,
            loading: false,
            handover: null
        },

        approveModal: {
            open: false,
            loading: false,
            handover: null,
            manager_remarks: ''
        },

        rejectModal: {
            open: false,
            loading: false,
            handover: null,
            manager_remarks: ''
        },

        get calculatedDiscrepancy() {
            const system = parseFloat(this.createModal.liveStats.cash_total || 0);
            const handed = parseFloat(this.createModal.form.handed_over_amount || 0);
            const diff = handed - system;

            if (diff < -0.01) {
                return {
                    class: 'bg-rose-50 border-rose-200 text-rose-800',
                    icon: 'fa-triangle-exclamation text-rose-600',
                    message: 'Shortage Detected (Less Cash Handed Over)',
                    amountFormatted: '{{ $currencySymbol ?? '৳' }}' + Math.abs(diff).toFixed(2)
                };
            } else if (diff > 0.01) {
                return {
                    class: 'bg-indigo-50 border-indigo-200 text-indigo-800',
                    icon: 'fa-arrow-trend-up text-indigo-600',
                    message: 'Excess Cash Handed Over',
                    amountFormatted: '+{{ $currencySymbol ?? '৳' }}' + diff.toFixed(2)
                };
            }
            return {
                class: 'bg-emerald-50 border-emerald-200 text-emerald-800',
                icon: 'fa-circle-check text-emerald-600',
                message: 'Exact Cash Match (No Discrepancy)',
                amountFormatted: '{{ $currencySymbol ?? '৳' }}0.00'
            };
        },

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

        openCreateModal() {
            this.createModal.liveStats = {};
            this.createModal.form = {
                collector_id: '',
                handover_date: '{{ Carbon\Carbon::today()->toDateString() }}',
                shift_type: 'daily',
                handed_over_amount: '',
                notes: '',
            };
            this.createModal.open = true;
        },

        async fetchCollectorLiveCash() {
            if (!this.createModal.form.collector_id || !this.createModal.form.handover_date) return;
            this.createModal.fetchingLive = true;

            try {
                const res = await fetch(`{{ route('tenant.finance.cash-handover.collector-cash') }}?collector_id=${this.createModal.form.collector_id}&date=${this.createModal.form.handover_date}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.createModal.liveStats = data;
                    if (data.cash_total !== undefined && (!this.createModal.form.handed_over_amount || this.createModal.form.handed_over_amount === '0')) {
                        this.createModal.form.handed_over_amount = parseFloat(data.cash_total).toFixed(2);
                    }
                }
            } catch (err) {
                // Non-blocking
            } finally {
                this.createModal.fetchingLive = false;
            }
        },

        async submitCreateHandover() {
            this.createModal.loading = true;
            try {
                const res = await fetch(`{{ route('tenant.finance.cash-handover.store') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.createModal.form)
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.showToast(data.message, 'success');
                    this.createModal.open = false;
                    setTimeout(() => window.location.reload(), 700);
                } else {
                    this.showToast(data.message || 'Failed to submit cash handover.', 'error');
                }
            } catch (err) {
                this.showToast('Network error submitting cash handover.', 'error');
            } finally {
                this.createModal.loading = false;
            }
        },

        async openVoucherModal(id) {
            this.voucherModal.loading = true;
            this.voucherModal.handover = null;
            this.voucherModal.open = true;

            try {
                const res = await fetch(`{{ url('/admin/finance/cash-handover') }}/${id}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.voucherModal.handover = data.handover;
                } else {
                    this.showToast('Failed to load handover voucher details.', 'error');
                    this.voucherModal.open = false;
                }
            } catch (err) {
                this.showToast('Network error fetching handover voucher.', 'error');
                this.voucherModal.open = false;
            } finally {
                this.voucherModal.loading = false;
            }
        },

        printVoucher() {
            window.print();
        },

        openApproveModal(handover) {
            this.approveModal.handover = handover;
            this.approveModal.manager_remarks = 'Physical cash counted and verified into vault.';
            this.approveModal.open = true;
        },

        async submitApproveHandover() {
            if (!this.approveModal.handover?.id) return;
            this.approveModal.loading = true;
            try {
                const res = await fetch(`{{ url('/admin/finance/cash-handover') }}/${this.approveModal.handover.id}/approve`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ manager_remarks: this.approveModal.manager_remarks })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.approveModal.open = false;
                    Swal.fire({
                        icon: 'success',
                        title: 'Handover Approved',
                        text: data.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                    setTimeout(() => window.location.reload(), 1200);
                } else {
                    this.showToast(data.message || 'Failed to approve handover.', 'error');
                }
            } catch (err) {
                this.showToast('Network error approving handover.', 'error');
            } finally {
                this.approveModal.loading = false;
            }
        },

        openRejectModal(handover) {
            this.rejectModal.handover = handover;
            this.rejectModal.manager_remarks = '';
            this.rejectModal.open = true;
        },

        async submitRejectHandover() {
            if (!this.rejectModal.handover?.id) return;
            this.rejectModal.loading = true;
            try {
                const res = await fetch(`{{ url('/admin/finance/cash-handover') }}/${this.rejectModal.handover.id}/reject`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ manager_remarks: this.rejectModal.manager_remarks })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.rejectModal.open = false;
                    Swal.fire({
                        icon: 'success',
                        title: 'Handover Rejected',
                        text: data.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                    setTimeout(() => window.location.reload(), 1200);
                } else {
                    this.showToast(data.message || 'Failed to reject handover.', 'error');
                }
            } catch (err) {
                this.showToast('Network error rejecting handover.', 'error');
            } finally {
                this.rejectModal.loading = false;
            }
        }
    };
}
</script>
@endpush
