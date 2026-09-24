@extends('reseller.layouts.app')

@section('title', 'Collections - ' . ($tenant->company_name ?? $tenant->name ?? 'Reseller Portal'))

@push('styles')
    {{-- Page-specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="collectionManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">
    
    {{-- 1. TOP HEADER BAR (AGENTS.md Rule 2.A: Icon + Title + Action buttons ONLY - Strictly No Subtitles) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-sm shadow-2xs flex-shrink-0">
                <i class="fas fa-receipt"></i>
            </div>
            <h1 class="text-sm font-bold text-slate-800 tracking-tight">{{ __('Payment Collections & Receipts') }}</h1>
        </div>
        <div class="flex items-center gap-1.5 sm:gap-2 flex-wrap sm:flex-nowrap justify-start sm:justify-end">
            @if(Route::has('reseller.bulk-payments.index'))
                <a href="{{ route('reseller.bulk-payments.index') }}" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                    <i class="fas fa-layer-group text-xs"></i>
                    <span>{{ __('Bulk Payments') }}</span>
                </a>
            @endif
            <a href="{{ route('reseller.collections.print', request()->query()) }}" target="_blank" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-print text-slate-500 text-xs"></i>
                <span>{{ __('Print Statement') }}</span>
            </a>
            <a href="{{ route('reseller.collections.export', request()->query()) }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-file-csv text-slate-500 text-xs"></i>
                <span>{{ __('Export CSV') }}</span>
            </a>
        </div>
    </div>

    {{-- 2. KPI SUMMARY STRIP (Strictly 6 Cards - AGENTS.md Rule 2.B) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        
        {{-- Card 1: Total Received --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Total Received') }}</span>
                <span class="text-[13px] font-bold font-mono text-emerald-600 leading-tight block truncate">
                    @currency($stats['total_collected'])
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-coins"></i>
            </div>
        </div>

        {{-- Card 2: Cash In Hand --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Cash In Hand') }}</span>
                <span class="text-[13px] font-bold font-mono text-slate-900 leading-tight block truncate">
                    @currency($stats['cash_collected'])
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-slate-200 bg-slate-50 text-slate-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-money-bill-wave"></i>
            </div>
        </div>

        {{-- Card 3: Digital / MFS --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Digital / PGW') }}</span>
                <span class="text-[13px] font-bold font-mono text-pink-600 leading-tight block truncate">
                    @currency($stats['digital_collected'])
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-pink-200 bg-pink-50 text-pink-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-mobile-screen"></i>
            </div>
        </div>

        {{-- Card 4: Pending Approval --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-amber-600 block truncate">{{ __('Pending Approval') }}</span>
                <span class="text-[13px] font-bold font-mono leading-tight block truncate {{ ($stats['pending_count'] ?? 0) > 0 ? 'text-amber-600 font-bold' : 'text-slate-700' }}">
                    {{ number_format($stats['pending_count'] ?? 0) }} Slips
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border {{ ($stats['pending_count'] ?? 0) > 0 ? 'border-amber-200 bg-amber-50 text-amber-600' : 'border-slate-200 bg-slate-50 text-slate-500' }} flex items-center justify-center flex-shrink-0">
                <i class="fas fa-clock"></i>
            </div>
        </div>

        {{-- Card 5: Discounts Given --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Discounts') }}</span>
                <span class="text-[13px] font-bold font-mono text-purple-700 leading-tight block truncate">
                    @currency($stats['total_discount'])
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-purple-200 bg-purple-50 text-purple-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-tags"></i>
            </div>
        </div>

        {{-- Card 6: Total Slips --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Receipt Number') }}</span>
                <span class="text-[13px] font-bold font-mono text-cyan-700 leading-tight block truncate">
                    {{ number_format($stats['transactions_count'] ?? 0) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-cyan-200 bg-cyan-50 text-cyan-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-file-invoice"></i>
            </div>
        </div>

    </div>

    {{-- 3. COLLECTOR PERFORMANCE SUMMARY MATRIX --}}
    @if(!empty($collectorMatrix) && count($collectorMatrix) > 0)
        <div class="bg-white p-2.5 rounded-xl border border-slate-200 shadow-xs space-y-2">
            <div class="flex items-center justify-between border-b border-slate-100 pb-1.5">
                <span class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                    <i class="fas fa-users text-cyan-600 text-[11px]"></i>
                    <span>Collector Performance Roster</span>
                </span>
                <span class="text-[10.5px] text-slate-500 font-mono">Total Staff: {{ count($collectorMatrix) }}</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2">
                @foreach($collectorMatrix as $cm)
                    <div class="p-2 rounded-lg bg-slate-50 border border-slate-200/80 flex items-center justify-between">
                        <div class="min-w-0 pr-2">
                            <span class="text-xs font-bold text-slate-800 block truncate">{{ $cm['collector']->name }}</span>
                            <span class="text-[10px] text-slate-500 font-mono block truncate">{{ $cm['transactions_count'] }} Slips • Comm: {{ $cm['commission_rate'] }}%</span>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <span class="text-xs font-bold font-mono text-emerald-600 block">@currency($cm['total_collected'])</span>
                            @if($cm['commission_amount'] > 0)
                                <span class="text-[9.5px] font-mono text-purple-700 block">+@currency($cm['commission_amount'])</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- 4. SEARCH & MULTI-FILTER TOOLBAR (AGENTS.md Rule 2.C) --}}
    <div class="bg-white p-2.5 sm:p-3 rounded-xl border border-slate-200 shadow-xs space-y-2.5">
        <form method="GET" action="{{ route('reseller.collections.index') }}" class="space-y-2">
            
            {{-- Row 1: Primary Search, Quick Period Preset, Date From, Date To --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-12 gap-2 items-center">
                
                <!-- Search Box (MD: 4 Cols) -->
                <div class="relative md:col-span-4">
                    <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                    <input type="search" 
                           name="search" 
                           value="{{ $search }}" 
                           autocomplete="off"
                           placeholder="Search receipt #, subscriber, phone..." 
                           class="w-full pl-8 pr-3 py-1.5 rounded-lg border border-slate-200 bg-slate-50 text-xs focus:bg-white focus:outline-none focus:border-cyan-500 shadow-2xs">
                </div>

                <!-- Quick Period Preset (MD: 2 Cols) -->
                <div class="md:col-span-2">
                    <select name="period" 
                            x-model="filterPeriod" 
                            @change="handlePeriodChange($event.target.value)" 
                            class="w-full py-1.5 px-2.5 rounded-lg border border-slate-200 bg-slate-50 text-xs font-medium text-slate-700 focus:bg-white focus:outline-none focus:border-cyan-500 shadow-2xs">
                        <option value="today">Today (আজ)</option>
                        <option value="yesterday">Yesterday (গতকাল)</option>
                        <option value="this_week">This Week (এই সপ্তাহ)</option>
                        <option value="this_month">This Month (চলতি মাস)</option>
                        <option value="last_month">Last Month (গত মাস)</option>
                        <option value="custom">Custom Range (দিন-তারিখ)</option>
                        <option value="all">All Records (সব সময়)</option>
                    </select>
                </div>

                <!-- Date From (MD: 3 Cols) -->
                <div class="md:col-span-3">
                    <div class="relative">
                        <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-[9.5px] uppercase font-bold text-slate-400 pointer-events-none">From</span>
                        <input type="date" 
                               name="date_from" 
                               x-model="filterDateFrom" 
                               @input="filterPeriod = 'custom'"
                               class="w-full pl-11 pr-2 py-1.5 rounded-lg border border-slate-200 bg-slate-50 text-xs font-mono focus:bg-white focus:outline-none focus:border-cyan-500 shadow-2xs">
                    </div>
                </div>

                <!-- Date To (MD: 3 Cols) -->
                <div class="md:col-span-3">
                    <div class="relative">
                        <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-[9.5px] uppercase font-bold text-slate-400 pointer-events-none">To</span>
                        <input type="date" 
                               name="date_to" 
                               x-model="filterDateTo" 
                               @input="filterPeriod = 'custom'"
                               class="w-full pl-8 pr-2 py-1.5 rounded-lg border border-slate-200 bg-slate-50 text-xs font-mono focus:bg-white focus:outline-none focus:border-cyan-500 shadow-2xs">
                    </div>
                </div>

            </div>

            {{-- Row 2: Secondary Dropdowns (Collector, Method, Status, Per Page) + Strict Filter & Reset Sequence --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-2 items-center pt-1 border-t border-slate-100">
                
                <!-- Collector Filter -->
                <div>
                    <select name="collector_id" onchange="this.form.submit()" class="w-full py-1.5 px-2 rounded-lg border border-slate-200 bg-slate-50 text-xs focus:bg-white focus:outline-none focus:border-cyan-500 shadow-2xs">
                        <option value="all" {{ $collectorId === 'all' ? 'selected' : '' }}>All Collectors</option>
                        @foreach($collectors as $col)
                            <option value="{{ $col->id }}" {{ $collectorId == $col->id ? 'selected' : '' }}>{{ $col->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Payment Method Filter -->
                <div>
                    <select name="method" onchange="this.form.submit()" class="w-full py-1.5 px-2 rounded-lg border border-slate-200 bg-slate-50 text-xs focus:bg-white focus:outline-none focus:border-cyan-500 shadow-2xs">
                        <option value="all" {{ $method === 'all' ? 'selected' : '' }}>All Methods</option>
                        <option value="cash" {{ $method === 'cash' ? 'selected' : '' }}>Cash in Hand</option>
                        <option value="bangla_qr" {{ $method === 'bangla_qr' ? 'selected' : '' }}>Bangla QR</option>
                        <option value="bkash" {{ $method === 'bkash' ? 'selected' : '' }}>bKash (MFS)</option>
                        <option value="nagad" {{ $method === 'nagad' ? 'selected' : '' }}>Nagad (MFS)</option>
                        <option value="rocket" {{ $method === 'rocket' ? 'selected' : '' }}>Rocket (MFS)</option>
                        <option value="bank_transfer" {{ $method === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                    </select>
                </div>

                <!-- Status Filter -->
                <div>
                    <select name="status" onchange="this.form.submit()" class="w-full py-1.5 px-2 rounded-lg border border-slate-200 bg-slate-50 text-xs focus:bg-white focus:outline-none focus:border-cyan-500 shadow-2xs">
                        <option value="all" {{ ($status ?? 'all') === 'all' ? 'selected' : '' }}>All Statuses</option>
                        <option value="paid" {{ ($status ?? '') === 'paid' ? 'selected' : '' }}>Paid / Settled</option>
                        <option value="pending" {{ ($status ?? '') === 'pending' ? 'selected' : '' }}>Pending Approval</option>
                    </select>
                </div>

                <!-- Per Page -->
                <div>
                    <select name="per_page" onchange="this.form.submit()" class="w-full py-1.5 px-2 rounded-lg border border-slate-200 bg-slate-50 text-xs focus:bg-white focus:outline-none focus:border-cyan-500 shadow-2xs">
                        <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>Show 10 Rows</option>
                        <option value="20" {{ $perPage == 20 ? 'selected' : '' }}>Show 20 Rows</option>
                        <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>Show 50 Rows</option>
                        <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>Show 100 Rows</option>
                    </select>
                </div>

                <!-- Strict Filter & Reset Sequence (AGENTS.md Rule 2.C) -->
                <div class="flex items-center gap-1.5 col-span-2 sm:col-span-1">
                    <button type="submit" class="w-1/2 bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-1.5 px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                        <i class="fas fa-filter text-[10px]"></i>
                        <span>Filter</span>
                    </button>
                    <a href="{{ route('reseller.collections.index') }}" class="w-1/2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-1.5 px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer">
                        <i class="fas fa-rotate-left text-[10px]"></i>
                        <span>Reset</span>
                    </a>
                </div>

            </div>

        </form>
    </div>

    {{-- 5. MASTER COMPACT TABLE (AGENTS.md Rule 2.D: Max 5-7 Minimal Columns) --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-10 text-center">#</th>
                        <th class="w-28 font-mono">Receipt #</th>
                        <th>Subscriber</th>
                        <th class="w-28 font-mono">Package</th>
                        <th class="w-28">Collector</th>
                        <th class="w-24 text-center">Method</th>
                        <th class="w-28 text-right">Amount</th>
                        <th class="w-20 text-center">Status</th>
                        <th class="w-10 text-center no-sort">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $index => $payment)
                        <tr>
                            <!-- 1. Index -->
                            <td class="text-center font-mono text-slate-500">
                                {{ ($payments->currentPage() - 1) * $payments->perPage() + $index + 1 }}
                            </td>

                            <!-- 2. Receipt # (Single Clean Field) -->
                            <td class="font-mono font-bold text-cyan-800">
                                <button type="button" class="hover:underline cursor-pointer font-mono" @click="fetchReceiptDetails({{ $payment->id }})">
                                    {{ $payment->invoice_no }}
                                </button>
                            </td>

                            <!-- 3. Subscriber (Single Clean Field - No Concat) -->
                            <td class="font-medium text-slate-800">
                                <button type="button" class="hover:text-cyan-700 cursor-pointer text-left font-semibold" @click="fetchReceiptDetails({{ $payment->id }})">
                                    {{ $payment->customer?->name ?? 'Direct Subscriber' }}
                                </button>
                            </td>

                            <!-- 4. Package Profile Name (AGENTS.md Rule 2.D) -->
                            <td class="font-mono text-cyan-800">
                                {{ $payment->customer?->package_display_name ?? $payment->customer?->package?->name ?? 'N/A' }}
                            </td>

                            <!-- 5. Collector -->
                            <td class="text-slate-700">
                                {{ $payment->collector?->name ?? 'Office Counter' }}
                            </td>

                            <!-- 6. Payment Method -->
                            <td class="text-center">
                                @if(in_array(strtolower($payment->payment_method), ['bangla_qr', 'banglaqr']))
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold font-mono bg-teal-50 text-teal-700 border border-teal-200">BANGLA QR</span>
                                @elseif(strtolower($payment->payment_method) === 'cash')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold font-mono bg-emerald-50 text-emerald-700 border border-emerald-200">CASH</span>
                                @elseif(in_array(strtolower($payment->payment_method), ['bkash', 'nagad', 'rocket']))
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold font-mono bg-pink-50 text-pink-700 border border-pink-200">{{ strtoupper($payment->payment_method) }}</span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold font-mono bg-blue-50 text-blue-700 border border-blue-200">BANK/POS</span>
                                @endif
                            </td>

                            <!-- 7. Amount Paid -->
                            <td class="text-right font-mono font-bold text-slate-900">
                                @currency($payment->amount)
                            </td>

                            <!-- 8. Status -->
                            <td class="text-center">
                                @if(strtolower($payment->status) === 'pending')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold font-mono bg-amber-50 text-amber-700 border border-amber-200" title="Pending Verification & Approval">
                                        <i class="fas fa-clock text-[8px]"></i>
                                        <span>Pending</span>
                                    </span>
                                @elseif(in_array(strtolower($payment->status), ['paid', 'completed', 'approved', 'active']))
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <i class="fas fa-check-circle text-[8px]"></i>
                                        <span>Paid</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                        <i class="fas fa-times-circle text-[8px]"></i>
                                        <span>Rejected</span>
                                    </span>
                                @endif
                            </td>

                            <!-- 9. Action: Floating 3-Dot Button (AGENTS.md Rule 2.E) -->
                            <td class="text-center">
                                <button type="button" 
                                        @click.stop="toggleMenu({{ Js::from($payment) }}, $event)" 
                                        class="w-7 h-7 rounded-lg hover:bg-slate-100 text-slate-500 hover:text-slate-800 transition flex items-center justify-center mx-auto cursor-pointer">
                                    <i class="fas fa-ellipsis-v text-[10px]"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-8 text-slate-400">
                                <i class="fas fa-receipt text-3xl mb-2 block text-slate-300"></i>
                                <span>No collection slips found matching your filters.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($payments->hasPages())
            <div class="px-4 py-2.5 bg-white border-t border-slate-200 flex items-center justify-between text-xs">
                <div>
                    Showing <span class="font-bold">{{ $payments->firstItem() }}</span> to <span class="font-bold">{{ $payments->lastItem() }}</span> of <span class="font-bold">{{ $payments->total() }}</span> collections
                </div>
                <div>
                    {{ $payments->links() }}
                </div>
            </div>
        @endif
    </div>

    {{-- 6. FLOATING 3-DOT ACTION MENU (AGENTS.md Rule 2.E) --}}
    <div x-show="activeMenu" 
         @click.outside="activeMenu = null"
         class="fixed z-50 bg-white rounded-xl border border-slate-200 shadow-xl py-1 w-48 text-xs space-y-0.5 divide-y divide-slate-100"
         :style="menuPos"
         style="display: none;"
         x-cloak>
        
        <template x-if="activeMenu?.status === 'pending'">
            <div class="py-0.5 bg-amber-50/60">
                <button type="button" 
                        @click="const item = activeMenu; activeMenu = null; approvePayment(item)" 
                        class="w-full flex items-center gap-2 px-3 py-1.5 text-emerald-700 hover:bg-emerald-100 transition text-left cursor-pointer font-bold">
                    <i class="fas fa-check-circle w-4 text-emerald-600"></i>
                    <span>Approve &amp; Activate</span>
                </button>
                <button type="button" 
                        @click="const item = activeMenu; activeMenu = null; rejectPayment(item)" 
                        class="w-full flex items-center gap-2 px-3 py-1.5 text-rose-700 hover:bg-rose-100 transition text-left cursor-pointer font-semibold">
                    <i class="fas fa-times-circle w-4 text-rose-600"></i>
                    <span>Reject Payment</span>
                </button>
            </div>
        </template>

        <div class="py-0.5">
            <button type="button" 
                    @click="fetchReceiptDetails(activeMenu?.id); activeMenu = null" 
                    class="w-full flex items-center gap-2 px-3 py-1.5 text-slate-700 hover:bg-slate-50 hover:text-cyan-600 transition text-left cursor-pointer">
                <i class="fas fa-receipt w-4 text-cyan-600"></i>
                <span>View Receipt</span>
            </button>
            <a :href="`/reseller/collections/${activeMenu?.id}/receipt`" 
               target="_blank"
               class="w-full flex items-center gap-2 px-3 py-1.5 text-slate-700 hover:bg-slate-50 hover:text-cyan-600 transition text-left cursor-pointer">
                <i class="fas fa-print w-4 text-slate-500"></i>
                <span>Print Receipt</span>
            </a>
        </div>
    </div>

    {{-- 7. MONEY RECEIPT MODAL (AGENTS.md Rule 3: Natural Soft Modal) --}}
    <div x-show="receiptData" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50"
         style="display: none;"
         x-cloak>
        
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-md overflow-hidden" 
             @click.outside="receiptData = null">
            
            <!-- Soft Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Money Receipt Voucher</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal" x-text="receiptData?.receipt_no"></p>
                    </div>
                </div>
                <button type="button" @click="receiptData = null" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <!-- Receipt Voucher Body -->
            <div class="p-5 space-y-4 text-xs">
                <!-- Partner Info -->
                <div class="text-center border-b border-slate-100 pb-3">
                    <span class="font-bold text-sm text-slate-900 block">{{ $reseller->name ?? 'Reseller Partner' }}</span>
                    <span class="text-[10.5px] text-slate-500 font-mono">{{ $reseller->mobile ?? '' }} • {{ $tenant->company_name ?? 'ISP' }}</span>
                </div>

                <!-- Receipt Matrix Grid -->
                <div class="grid grid-cols-2 gap-2 text-[11px]">
                    <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[9.5px] font-medium text-slate-500 block uppercase">Subscriber</span>
                        <span class="font-bold text-slate-800 block" x-text="receiptData?.customer_name"></span>
                        <span class="text-[9.5px] font-mono text-cyan-800" x-text="'ID: ' + (receiptData?.customer_id || 'N/A')"></span>
                    </div>
                    <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[9.5px] font-medium text-slate-500 block uppercase">Billing Month</span>
                        <span class="font-bold text-slate-800 block" x-text="receiptData?.billing_month"></span>
                        <span class="text-[9.5px] text-slate-500" x-text="receiptData?.package_name"></span>
                    </div>
                    <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[9.5px] font-medium text-slate-500 block uppercase">Payment Method</span>
                        <span class="font-bold text-slate-800 block" x-text="receiptData?.payment_method"></span>
                    </div>
                    <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[9.5px] font-medium text-slate-500 block uppercase">Collector Staff</span>
                        <span class="font-bold text-slate-800 block" x-text="receiptData?.collector_name"></span>
                    </div>
                </div>

                <!-- Amount Box -->
                <div class="p-3 bg-emerald-50/80 rounded-xl border border-emerald-200 text-center">
                    <span class="text-[10px] uppercase font-bold text-emerald-700 block tracking-wider">Total Received Amount</span>
                    <span class="text-lg font-bold font-mono text-emerald-900 block" x-text="'{{ $currencySymbol ?? '৳' }}' + Number(receiptData?.amount || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })"></span>
                    <span class="text-[10px] text-slate-500 font-mono mt-0.5 block" x-text="receiptData?.paid_at"></span>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                <a :href="`/reseller/collections/${receiptData?.receipt_id || activeMenu?.id}/receipt`" 
                   target="_blank" 
                   class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200 transition flex items-center gap-1.5 cursor-pointer">
                    <i class="fas fa-print"></i>
                    <span>Print Slip</span>
                </a>
                <button type="button" @click="receiptData = null" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function collectionManager() {
    return {
        receiptData: null,
        activeMenu: null,
        menuPos: {},
        filterPeriod: '{{ $period ?? 'this_month' }}',
        filterDateFrom: '{{ $dateFrom ?? '' }}',
        filterDateTo: '{{ $dateTo ?? '' }}',

        handlePeriodChange(val) {
            const now = new Date();
            const formatDate = (d) => {
                const year = d.getFullYear();
                const month = String(d.getMonth() + 1).padStart(2, '0');
                const day = String(d.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            };

            if (val === 'today') {
                const todayStr = formatDate(now);
                this.filterDateFrom = todayStr;
                this.filterDateTo = todayStr;
            } else if (val === 'yesterday') {
                const y = new Date(now);
                y.setDate(y.getDate() - 1);
                const yStr = formatDate(y);
                this.filterDateFrom = yStr;
                this.filterDateTo = yStr;
            } else if (val === 'this_week') {
                const currentDay = now.getDay();
                const diffToMonday = (currentDay === 0 ? -6 : 1) - currentDay;
                const monday = new Date(now);
                monday.setDate(now.getDate() + diffToMonday);
                const sunday = new Date(monday);
                sunday.setDate(monday.getDate() + 6);
                this.filterDateFrom = formatDate(monday);
                this.filterDateTo = formatDate(sunday);
            } else if (val === 'this_month') {
                const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
                const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
                this.filterDateFrom = formatDate(firstDay);
                this.filterDateTo = formatDate(lastDay);
            } else if (val === 'last_month') {
                const firstDay = new Date(now.getFullYear(), now.getMonth() - 1, 1);
                const lastDay = new Date(now.getFullYear(), now.getMonth(), 0);
                this.filterDateFrom = formatDate(firstDay);
                this.filterDateTo = formatDate(lastDay);
            } else if (val === 'all') {
                this.filterDateFrom = '';
                this.filterDateTo = '';
            }
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

        async fetchReceiptDetails(id) {
            if (!id) return;
            try {
                const res = await fetch(`/reseller/collections/${id}/receipt`);
                const data = await res.json();
                if (data.success) {
                    this.receiptData = { ...data, receipt_id: id };
                }
            } catch (e) {
                console.error('Failed to load receipt details:', e);
            }
        },

        async approvePayment(item) {
            if (typeof Swal !== 'undefined') {
                const result = await Swal.fire({
                    title: 'Approve Payment & Activate Line?',
                    html: `<div class="text-xs text-slate-600">Are you sure you want to approve receipt <b>${item.invoice_no}</b> ({{ $currencySymbol ?? '৳' }}${Number(item.amount).toFixed(2)})?<br><span class="text-slate-400 mt-1 block">Subscriber line will be activated immediately on MikroTik and validity extended.</span></div>`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Approve & Activate',
                    cancelButtonText: 'Cancel',
                    customClass: {
                        confirmButton: 'bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs px-4 py-2 rounded-lg cursor-pointer mr-2',
                        cancelButton: 'bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold text-xs px-4 py-2 rounded-lg cursor-pointer'
                    },
                    buttonsStyling: false
                });
                if (!result.isConfirmed) return;
            } else {
                if (!confirm(`Are you sure you want to approve payment '${item.invoice_no}' (${item.amount}) and activate subscriber line in MikroTik & FreeRADIUS?`)) {
                    return;
                }
            }

            try {
                const res = await fetch(`/reseller/collections/${item.id}/approve`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                if (data.success) {
                    if (typeof Swal !== 'undefined') {
                        await Swal.fire({
                            icon: 'success',
                            title: 'Payment Approved!',
                            text: data.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                    window.location.reload();
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Approval Failed',
                            text: data.message || 'Failed to approve payment.',
                            customClass: {
                                confirmButton: 'bg-rose-600 hover:bg-rose-700 text-white font-semibold text-xs px-4 py-2 rounded-lg cursor-pointer'
                            },
                            buttonsStyling: false
                        });
                    } else {
                        alert(data.message || 'Failed to approve payment.');
                    }
                }
            } catch (e) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Network Error',
                        text: 'Could not communicate with the server to approve payment.',
                        customClass: {
                            confirmButton: 'bg-rose-600 hover:bg-rose-700 text-white font-semibold text-xs px-4 py-2 rounded-lg cursor-pointer'
                        },
                        buttonsStyling: false
                    });
                } else {
                    alert('Network error approving payment.');
                }
            }
        },

        async rejectPayment(item) {
            let reason = 'Invalid TrxID / Payment not verified';
            if (typeof Swal !== 'undefined') {
                const { value: text, isConfirmed } = await Swal.fire({
                    title: 'Reject Pending Payment',
                    input: 'textarea',
                    inputLabel: `Rejection reason for receipt ${item.invoice_no}`,
                    inputValue: reason,
                    inputPlaceholder: 'Type rejection reason here...',
                    inputAttributes: {
                        'aria-label': 'Type rejection reason here'
                    },
                    showCancelButton: true,
                    confirmButtonText: 'Confirm Rejection',
                    cancelButtonText: 'Cancel',
                    customClass: {
                        confirmButton: 'bg-rose-600 hover:bg-rose-700 text-white font-semibold text-xs px-4 py-2 rounded-lg cursor-pointer mr-2',
                        cancelButton: 'bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold text-xs px-4 py-2 rounded-lg cursor-pointer',
                        input: 'text-xs text-slate-700'
                    },
                    buttonsStyling: false
                });
                if (!isConfirmed) return;
                reason = text || reason;
            } else {
                let prompted = prompt(`Please enter rejection reason for payment '${item.invoice_no}':`, reason);
                if (prompted === null) return;
                reason = prompted;
            }

            try {
                const res = await fetch(`/reseller/collections/${item.id}/reject`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ reason: reason })
                });
                const data = await res.json();
                if (data.success) {
                    if (typeof Swal !== 'undefined') {
                        await Swal.fire({
                            icon: 'warning',
                            title: 'Payment Rejected',
                            text: data.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                    window.location.reload();
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Rejection Failed',
                            text: data.message || 'Failed to reject payment.',
                            customClass: {
                                confirmButton: 'bg-rose-600 hover:bg-rose-700 text-white font-semibold text-xs px-4 py-2 rounded-lg cursor-pointer'
                            },
                            buttonsStyling: false
                        });
                    } else {
                        alert(data.message || 'Failed to reject payment.');
                    }
                }
            } catch (e) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Network Error',
                        text: 'Could not communicate with the server to reject payment.',
                        customClass: {
                            confirmButton: 'bg-rose-600 hover:bg-rose-700 text-white font-semibold text-xs px-4 py-2 rounded-lg cursor-pointer'
                        },
                        buttonsStyling: false
                    });
                } else {
                    alert('Network error rejecting payment.');
                }
            }
        }
    };
}
</script>
@endpush
