@extends('tenant.layouts.app')

@section('title', 'Carrier Payment Vouchers - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
    {{-- Page specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="upstreamPaymentManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">

    <!-- 1. Top Header Bar (Strictly Icon + Title + Action Button ONLY - AGENTS.md Rule 2.A) -->
    <div class="flex items-center justify-between bg-white px-3.5 py-2.5 rounded-xl border border-slate-200/90 shadow-2xs">
        <div class="flex items-center gap-2.5">
            <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-xs shadow-2xs flex-shrink-0">
                <i class="fas fa-money-bill-transfer"></i>
            </div>
            <div class="flex items-center gap-2">
                <h1 class="text-xs font-bold text-slate-800 tracking-tight">Carrier Payment Vouchers</h1>
                <span class="text-[10px] font-bold text-slate-500 bg-slate-100 border border-slate-200/80 px-2 py-0.5 rounded-full font-mono">{{ number_format($payments->total()) }}</span>
            </div>
        </div>

        <div class="flex items-center gap-1.5 flex-wrap">
            <button type="button" 
                    @click="openPaymentModal()"
                    class="h-[34px] px-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-plus text-xs"></i>
                <span>Disburse Payment</span>
            </button>

            <a href="{{ route('tenant.upstream.invoices') }}" 
               class="h-[34px] px-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200/80 rounded-lg text-xs font-medium transition flex items-center gap-1 cursor-pointer"
               title="Carrier Bills">
                <i class="fas fa-file-invoice-dollar text-[11px] text-slate-500"></i>
                <span>Carrier Bills</span>
            </a>

            <a href="{{ route('tenant.upstream.index') }}" 
               class="h-[34px] px-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200/80 rounded-lg text-xs font-medium transition flex items-center gap-1 cursor-pointer"
               title="Carriers & Links">
                <i class="fas fa-tower-broadcast text-[11px] text-slate-500"></i>
                <span>Carriers</span>
            </a>
        </div>
    </div>

    <!-- 2. KPI Summary Strip (Strictly 6 Cards - AGENTS.md Rule 2.B) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        <!-- Card 1: Total Disbursed -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Total Disbursed</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-emerald-700 truncate">@currency($totalDisbursed)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-emerald-50 text-emerald-600 border-emerald-100 flex items-center justify-center">
                <i class="fas fa-money-bill-wave"></i>
            </div>
        </div>

        <!-- Card 2: Total Vouchers Count -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Total Vouchers</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-slate-900 truncate">{{ number_format($totalVouchersCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-slate-50 text-slate-600 border-slate-200 flex items-center justify-center">
                <i class="fas fa-receipt"></i>
            </div>
        </div>

        <!-- Card 3: Bank Transfer Total -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Bank Transfer</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-blue-700 truncate">@currency($bankTransferTotal)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-blue-50 text-blue-600 border-blue-100 flex items-center justify-center">
                <i class="fas fa-building-columns"></i>
            </div>
        </div>

        <!-- Card 4: Cheque / RTGS Total -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Cheque / RTGS</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-indigo-700 truncate">@currency($chequeRtgsTotal)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-indigo-50 text-indigo-600 border-indigo-100 flex items-center justify-center">
                <i class="fas fa-money-check"></i>
            </div>
        </div>

        <!-- Card 5: Cash Settlements -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Cash Disbursed</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-amber-700 truncate">@currency($cashTotal)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-amber-50 text-amber-600 border-amber-100 flex items-center justify-center">
                <i class="fas fa-coins"></i>
            </div>
        </div>

        <!-- Card 6: This Month Disbursed -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">This Month</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-cyan-800 truncate">@currency($thisMonthTotal)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-cyan-50 text-cyan-600 border-cyan-100 flex items-center justify-center">
                <i class="fas fa-calendar-check"></i>
            </div>
        </div>
    </div>

    <!-- 3. Search & Multi-Filter Toolbar (Customer-Standard 2-Tier Filter System - AGENTS.md Rule 2.C) -->
    @php
        $isMoreOpen = request()->filled('start_date') || request()->filled('end_date');
        $activeMoreCount = (request()->filled('start_date') ? 1 : 0) + (request()->filled('end_date') ? 1 : 0);
    @endphp
    <div class="bg-white p-2.5 rounded-xl border border-slate-200/90 shadow-2xs space-y-2.5"
         x-data="{ 
            showMoreFilters: {{ $isMoreOpen ? 'true' : 'false' }}, 
            searchQuery: '{{ addslashes(request('search', '')) }}' 
         }">
        <form method="GET" action="{{ route('tenant.upstream.payments') }}">
            <!-- Row 1: Primary Filters -->
            <div class="flex flex-col md:flex-row items-stretch md:items-center gap-2">
                <!-- Intelligent Live Search Input with 1-Click Clear -->
                <div class="relative flex-1 min-w-[200px]">
                    <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-xs transition-colors"
                       :class="searchQuery ? 'text-cyan-600 font-bold' : 'text-slate-400'"></i>
                    <input type="text" 
                           name="search" 
                           x-model="searchQuery"
                           placeholder="Search Voucher No, Carrier, Bank, Cheque..." 
                           class="w-full h-[34px] pl-8 pr-7 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    <button type="button" 
                            x-show="searchQuery && searchQuery.length > 0" 
                            @click="searchQuery = ''; $nextTick(() => $el.closest('form').submit())"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 cursor-pointer"
                            title="Clear search">
                        <i class="fas fa-xmark text-xs"></i>
                    </button>
                </div>

                <!-- Carrier Provider Primary Filter -->
                <div class="w-full md:w-52 flex-shrink-0">
                    <select name="provider_id" 
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="">All Carrier Providers</option>
                        @foreach($providers as $p)
                        <option value="{{ $p->id }}" {{ request('provider_id') == $p->id ? 'selected' : '' }}>
                            {{ $p->name }} ({{ $p->carrier_type }})
                        </option>
                        @endforeach
                    </select>
                </div>

                <!-- Payment Method Primary Filter -->
                <div class="w-full md:w-44 flex-shrink-0">
                    <select name="payment_method" 
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="">All Payment Methods</option>
                        <option value="BANK_TRANSFER" {{ request('payment_method') === 'BANK_TRANSFER' ? 'selected' : '' }}>Bank Transfer / BEFTN</option>
                        <option value="CHEQUE" {{ request('payment_method') === 'CHEQUE' ? 'selected' : '' }}>Bank Cheque</option>
                        <option value="RTGS" {{ request('payment_method') === 'RTGS' ? 'selected' : '' }}>RTGS Instant</option>
                        <option value="CASH" {{ request('payment_method') === 'CASH' ? 'selected' : '' }}>Cash Settlement</option>
                        <option value="OTHER" {{ request('payment_method') === 'OTHER' ? 'selected' : '' }}>Other Method</option>
                    </select>
                </div>

                <!-- Pagination Selector (per_page) -->
                <div class="w-full md:w-28 flex-shrink-0">
                    <select name="per_page" 
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="15" {{ request('per_page') == '15' ? 'selected' : '' }}>15 / page</option>
                        <option value="20" {{ request('per_page', '20') == '20' ? 'selected' : '' }}>20 / page</option>
                        <option value="30" {{ request('per_page') == '30' ? 'selected' : '' }}>30 / page</option>
                        <option value="50" {{ request('per_page') == '50' ? 'selected' : '' }}>50 / page</option>
                        <option value="100" {{ request('per_page') == '100' ? 'selected' : '' }}>100 / page</option>
                        <option value="250" {{ request('per_page') == '250' ? 'selected' : '' }}>250 / page</option>
                    </select>
                </div>

                <!-- More Filters Toggle Button -->
                <button type="button" 
                        @click="showMoreFilters = !showMoreFilters"
                        class="h-[34px] py-0 px-2.5 rounded-lg border text-xs font-medium flex items-center justify-center gap-1.5 transition flex-shrink-0 cursor-pointer"
                        :class="showMoreFilters || {{ $activeMoreCount }} > 0 ? 'bg-cyan-50 border-cyan-200 text-cyan-700' : 'bg-slate-50 hover:bg-slate-100 border-slate-200 text-slate-600'">
                    <i class="fas fa-sliders text-[11px]"></i>
                    <span>More</span>
                    @if($activeMoreCount > 0)
                    <span class="w-4 h-4 rounded-full bg-cyan-600 text-white text-[9px] font-bold flex items-center justify-center">
                        {{ $activeMoreCount }}
                    </span>
                    @endif
                    <i class="fas text-[9px] transition-transform duration-150"
                       :class="showMoreFilters ? 'fa-chevron-up text-cyan-600' : 'fa-chevron-down text-slate-400'"></i>
                </button>

                <!-- Filter & Reset Button Sequence (Strictly Filter Cyan first, Reset Slate second, Always visible) -->
                <div class="flex items-center gap-1.5 flex-shrink-0 md:ml-auto">
                    <button type="submit" 
                            class="h-[34px] bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-0 px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                        <i class="fas fa-filter text-[10px]"></i>
                        <span>Filter</span>
                    </button>

                    <a href="{{ route('tenant.upstream.payments') }}" 
                       class="h-[34px] bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-0 px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer"
                       title="Reset All Filters">
                        <i class="fas fa-rotate-left text-[10px]"></i>
                        <span>Reset</span>
                    </a>
                </div>
            </div>

            <!-- Row 2: Secondary Filters Drawer -->
            <div x-show="showMoreFilters" 
                 x-cloak 
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="pt-2 border-t border-slate-100 flex flex-wrap items-center gap-2">
                
                <!-- Start Date -->
                <div class="w-full sm:w-40 flex-shrink-0">
                    <label class="block text-[9px] font-bold uppercase tracking-wider text-slate-400 mb-0.5">From Date</label>
                    <input type="date" 
                           name="start_date" 
                           value="{{ request('start_date') }}"
                           class="w-full h-[34px] px-2.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                </div>

                <!-- End Date -->
                <div class="w-full sm:w-40 flex-shrink-0">
                    <label class="block text-[9px] font-bold uppercase tracking-wider text-slate-400 mb-0.5">To Date</label>
                    <input type="date" 
                           name="end_date" 
                           value="{{ request('end_date') }}"
                           class="w-full h-[34px] px-2.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                </div>
            </div>
        </form>
    </div>

    <!-- 4. Master Table (Strictly .saas-table pure CSS system - Updated Essential Columns - AGENTS.md Rule 2.D) -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-10 text-center font-semibold">#</th>
                        <th class="text-center font-semibold">Voucher No</th>
                        <th class="text-center font-semibold">Carrier Provider</th>
                        <th class="text-center font-semibold">Linked Bill / Inv</th>
                        <th class="text-center font-semibold">Payment Method</th>
                        <th class="text-center font-semibold">Bank / Trx Ref</th>
                        <th class="text-center font-semibold">Paid Amount</th>
                        <th class="text-center font-semibold">Paid Date</th>
                        <th class="w-12 text-center font-semibold no-sort">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $index => $payment)
                    <tr>
                        <!-- 1. Serial No -->
                        <td class="text-center font-mono text-slate-500">
                            {{ $payments->firstItem() + $index }}
                        </td>

                        <!-- 2. Voucher No (Click to View Details) -->
                        <td class="text-center font-mono font-bold">
                            <button type="button" 
                                    @click="viewPaymentDetails({{ json_encode([
                                        'id' => $payment->id,
                                        'voucher_no' => $payment->voucher_no,
                                        'provider_id' => $payment->provider_id,
                                        'provider_name' => $payment->provider->name ?? '-',
                                        'carrier_type' => $payment->provider->carrier_type ?? 'IIG',
                                        'invoice_no' => $payment->invoice->invoice_no ?? '-',
                                        'amount' => $payment->amount,
                                        'payment_method' => $payment->payment_method,
                                        'bank_name' => $payment->bank_name,
                                        'cheque_no' => $payment->cheque_no,
                                        'transaction_ref' => $payment->transaction_ref,
                                        'paid_at' => $payment->paid_at ? $payment->paid_at->format('d M, Y h:i A') : '-',
                                        'creator_name' => $payment->creator->name ?? 'System',
                                        'notes' => $payment->notes,
                                    ]) }})"
                                    class="text-cyan-700 hover:text-cyan-900 hover:underline cursor-pointer">
                                {{ $payment->voucher_no }}
                            </button>
                        </td>

                        <!-- 3. Carrier Provider -->
                        <td class="text-left font-semibold text-slate-800">
                            <div class="flex items-center gap-1.5 flex-nowrap whitespace-nowrap">
                                <span>{{ $payment->provider->name ?? '-' }}</span>
                                @if(isset($payment->provider->carrier_type))
                                <span class="px-1.5 py-0.5 rounded text-[9.5px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                    {{ $payment->provider->carrier_type }}
                                </span>
                                @endif
                            </div>
                        </td>

                        <!-- 4. Linked Bill / Invoice No -->
                        <td class="text-center font-mono text-slate-700">
                            {{ $payment->invoice->invoice_no ?? '-' }}
                        </td>

                        <!-- 5. Payment Method Badge -->
                        <td class="text-center">
                            @php
                                $badge = $payment->method_badge;
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold border {{ $badge['class'] ?? 'bg-slate-100 text-slate-700 border-slate-200' }}">
                                {{ $badge['label'] ?? str_replace('_', ' ', $payment->payment_method) }}
                            </span>
                        </td>

                        <!-- 6. Bank / Transaction Ref -->
                        <td class="text-left font-mono text-slate-700 text-xs">
                            <div class="truncate max-w-[150px]" title="{{ $payment->bank_name ? ($payment->bank_name . ' - ' . ($payment->transaction_ref ?? $payment->cheque_no)) : ($payment->transaction_ref ?? '-') }}">
                                @if($payment->bank_name)
                                    <span class="font-semibold text-slate-800">{{ $payment->bank_name }}</span>
                                    @if($payment->transaction_ref || $payment->cheque_no)
                                    <span class="text-slate-400">#{{ $payment->transaction_ref ?? $payment->cheque_no }}</span>
                                    @endif
                                @elseif($payment->transaction_ref)
                                    <span>{{ $payment->transaction_ref }}</span>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </div>
                        </td>

                        <!-- 7. Paid Amount -->
                        <td class="text-right font-mono font-bold text-emerald-700">
                            @currency($payment->amount)
                        </td>

                        <!-- 8. Payment Date -->
                        <td class="text-center font-mono text-slate-600">
                            {{ $payment->paid_at ? $payment->paid_at->format('d M, Y') : '-' }}
                        </td>

                        <!-- 9. Action (3-Dot Floating Trigger) -->
                        <td class="text-center">
                            <button type="button" 
                                    @click="toggleMenu({{ json_encode([
                                        'id' => $payment->id,
                                        'voucher_no' => $payment->voucher_no,
                                        'provider_id' => $payment->provider_id,
                                        'provider_name' => $payment->provider->name ?? '-',
                                        'carrier_type' => $payment->provider->carrier_type ?? 'IIG',
                                        'invoice_no' => $payment->invoice->invoice_no ?? '-',
                                        'amount' => $payment->amount,
                                        'payment_method' => $payment->payment_method,
                                        'bank_name' => $payment->bank_name,
                                        'cheque_no' => $payment->cheque_no,
                                        'transaction_ref' => $payment->transaction_ref,
                                        'paid_at' => $payment->paid_at ? $payment->paid_at->format('d M, Y h:i A') : '-',
                                        'creator_name' => $payment->creator->name ?? 'System',
                                        'notes' => $payment->notes,
                                    ]) }}, $event)"
                                    class="w-7 h-7 rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-600 flex items-center justify-center transition cursor-pointer">
                                <i class="fas fa-ellipsis-v text-[10px]"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-8 text-slate-500">
                            <div class="flex flex-col items-center justify-center gap-1.5">
                                <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-400">
                                    <i class="fas fa-money-bill-transfer text-base"></i>
                                </div>
                                <span class="text-xs font-semibold text-slate-700">No Payment Vouchers Found</span>
                                <p class="text-[11px] text-slate-400 max-w-sm">No carrier payments match your search filters or no bank disbursements have been recorded yet.</p>
                                <button type="button" 
                                        @click="openPaymentModal()"
                                        class="mt-2 h-[34px] px-3.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                                    <i class="fas fa-plus text-xs"></i>
                                    <span>Disburse First Payment</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Native Laravel Pagination -->
        @if($payments->hasPages())
        <div class="px-3 py-2 border-t border-slate-200 bg-slate-50/50 flex items-center justify-between">
            <div class="text-[11px] text-slate-500">
                Showing <span class="font-semibold text-slate-700">{{ $payments->firstItem() }}</span> to <span class="font-semibold text-slate-700">{{ $payments->lastItem() }}</span> of <span class="font-semibold text-slate-700">{{ $payments->total() }}</span> vouchers
            </div>
            <div>
                {{ $payments->links() }}
            </div>
        </div>
        @endif
    </div>

    <!-- 5. Global Floating 3-Dot Action Menu (AGENTS.md Rule 2.E) -->
    <div x-show="activeMenu" 
         x-cloak 
         @click.away="activeMenu = null" 
         class="fixed z-50 bg-white rounded-xl border border-slate-200 shadow-xl py-1 w-52 text-xs font-medium text-slate-700 space-y-0.5"
         :style="{ top: menuPos.top, bottom: menuPos.bottom, right: menuPos.right, left: menuPos.left }">
        
        <button type="button" 
                @click="viewPaymentDetails(activeMenu)" 
                class="w-full text-left px-3 py-1.5 hover:bg-slate-50 flex items-center gap-2 text-slate-700 transition cursor-pointer">
            <i class="fas fa-circle-info w-4 text-cyan-600 text-[11px]"></i>
            <span>View Voucher Details</span>
        </button>

        <a :href="`/admin/upstream/payments/${activeMenu?.id}/voucher`" 
           target="_blank"
           class="w-full text-left px-3 py-1.5 hover:bg-slate-50 flex items-center gap-2 text-slate-700 transition cursor-pointer">
            <i class="fas fa-print w-4 text-indigo-500 text-[11px]"></i>
            <span>Print / Download Voucher</span>
        </a>

        <div class="border-t border-slate-100 my-0.5"></div>

        <button type="button" 
                @click="deletePayment(activeMenu)" 
                class="w-full text-left px-3 py-1.5 hover:bg-rose-50 flex items-center gap-2 text-rose-600 transition cursor-pointer">
            <i class="fas fa-trash-can w-4 text-rose-400 text-[11px]"></i>
            <span>Delete Voucher</span>
        </button>
    </div>

    <!-- Modal 1: Disburse Payment Modal (Rule 3) -->
    <div x-show="showPaymentModal" 
         x-cloak 
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50">
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden animate-in fade-in zoom-in duration-150"
             @click.away="showPaymentModal = false">
            
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-money-bill-transfer"></i>
                    </div>
                    <div>
                        <h2 class="text-xs font-semibold text-slate-800">Disburse Payment to Carrier</h2>
                        <p class="text-[10.5px] text-slate-500 font-normal">Record bank settlement and generate printable voucher.</p>
                    </div>
                </div>
                <button type="button" @click="showPaymentModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-xmark text-xs"></i>
                </button>
            </div>

            <form action="{{ route('tenant.upstream.payment.store') }}" method="POST" class="p-4 space-y-3">
                @csrf
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">
                        Carrier Provider <span class="text-rose-500">*</span>
                    </label>
                    <select name="provider_id" 
                            x-model="paymentData.provider_id" 
                            required 
                            class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="">Select Carrier</option>
                        @foreach($providers as $p)
                        <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->carrier_type }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            Paid Amount ({{ $currencySymbol }}) <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" 
                               step="0.01" 
                               min="1" 
                               name="amount" 
                               x-model.number="paymentData.amount" 
                               required 
                               placeholder="0.00" 
                               class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono font-bold text-emerald-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            Payment Method <span class="text-rose-500">*</span>
                        </label>
                        <select name="payment_method" 
                                x-model="paymentData.payment_method" 
                                required 
                                class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                            <option value="BANK_TRANSFER">Bank Transfer / BEFTN</option>
                            <option value="CHEQUE">Bank Cheque</option>
                            <option value="RTGS">RTGS Instant</option>
                            <option value="CASH">Cash Settlement</option>
                            <option value="OTHER">Other Method</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">Bank Name / Branch</label>
                        <input type="text" 
                               name="bank_name" 
                               x-model="paymentData.bank_name" 
                               placeholder="e.g. Standard Chartered" 
                               class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">Cheque / Trx Reference</label>
                        <input type="text" 
                               name="transaction_ref" 
                               x-model="paymentData.transaction_ref" 
                               placeholder="Ref No / Cheque No" 
                               class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">Payment Date <span class="text-rose-500">*</span></label>
                    <input type="datetime-local" 
                           name="paid_at" 
                           x-model="paymentData.paid_at" 
                           required 
                           class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                </div>

                <div class="pt-2 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" 
                            @click="showPaymentModal = false" 
                            class="h-[34px] px-3.5 border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs rounded-lg transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="h-[34px] px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs rounded-lg shadow-xs transition cursor-pointer">
                        Disburse &amp; Generate Voucher
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 2: View Payment Voucher Details Modal (Rule 3) -->
    <div x-show="showDetailsModal" 
         x-cloak 
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50">
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden animate-in fade-in zoom-in duration-150"
             @click.away="showDetailsModal = false">
            
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                    <div>
                        <h2 class="text-xs font-semibold text-slate-800" x-text="`Payment Voucher: ${detailsData?.voucher_no || ''}`"></h2>
                        <p class="text-[10.5px] text-slate-500 font-normal" x-text="`Carrier: ${detailsData?.provider_name || 'N/A'} (${detailsData?.carrier_type || 'IIG'})`"></p>
                    </div>
                </div>
                <button type="button" @click="showDetailsModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-xmark text-xs"></i>
                </button>
            </div>

            <div class="p-4 space-y-3.5 text-xs text-slate-700 max-h-[75vh] overflow-y-auto">
                <!-- Amount Banner Card -->
                <div class="p-3 bg-emerald-50/60 rounded-xl border border-emerald-100 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] uppercase font-bold text-emerald-600 block">Total Disbursed Amount</span>
                        <span class="text-[11px] text-emerald-800">Bank / Cash Settlement confirmed</span>
                    </div>
                    <span class="text-xl font-mono font-bold text-emerald-700" x-text="`{{ $currencySymbol }} ${Number(detailsData?.amount || 0).toFixed(2)}`"></span>
                </div>

                <!-- Info Grid -->
                <div class="grid grid-cols-2 gap-2 text-[11.5px] p-3 bg-slate-50 rounded-lg border border-slate-200/80">
                    <div>
                        <span class="text-slate-400 block font-medium">Payment Method:</span>
                        <span class="font-semibold text-slate-800" x-text="detailsData?.payment_method?.replace(/_/g, ' ') || 'N/A'"></span>
                    </div>
                    <div>
                        <span class="text-slate-400 block font-medium">Payment Date:</span>
                        <span class="font-semibold text-slate-800" x-text="detailsData?.paid_at || 'N/A'"></span>
                    </div>
                    <div class="mt-1">
                        <span class="text-slate-400 block font-medium">Linked Bill:</span>
                        <span class="font-mono font-semibold text-slate-800" x-text="detailsData?.invoice_no || 'Direct Carrier Payment'"></span>
                    </div>
                    <div class="mt-1">
                        <span class="text-slate-400 block font-medium">Recorded By:</span>
                        <span class="font-semibold text-slate-800" x-text="detailsData?.creator_name || 'System Admin'"></span>
                    </div>
                </div>

                <!-- Bank / Cheque Details -->
                <template x-if="detailsData?.bank_name || detailsData?.transaction_ref || detailsData?.cheque_no">
                    <div class="p-3 bg-slate-50 rounded-lg border border-slate-200/80 space-y-1.5 text-[11.5px]">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block">Bank / Cheque Information</span>
                        <div class="flex items-center justify-between" x-show="detailsData?.bank_name">
                            <span class="text-slate-500">Bank / Branch:</span>
                            <span class="font-semibold text-slate-800" x-text="detailsData?.bank_name"></span>
                        </div>
                        <div class="flex items-center justify-between" x-show="detailsData?.cheque_no">
                            <span class="text-slate-500">Cheque Number:</span>
                            <span class="font-mono font-semibold text-slate-800" x-text="detailsData?.cheque_no"></span>
                        </div>
                        <div class="flex items-center justify-between" x-show="detailsData?.transaction_ref">
                            <span class="text-slate-500">Transaction Reference:</span>
                            <span class="font-mono font-semibold text-slate-800" x-text="detailsData?.transaction_ref"></span>
                        </div>
                    </div>
                </template>

                <div class="pt-2 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" 
                            @click="showDetailsModal = false" 
                            class="h-[34px] px-3.5 border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs rounded-lg transition cursor-pointer">
                        Close
                    </button>
                    <a :href="`/admin/upstream/payments/${detailsData?.id}/voucher`" 
                       target="_blank"
                       class="h-[34px] px-3.5 bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                        <i class="fas fa-print text-xs"></i>
                        <span>Print Voucher</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    function upstreamPaymentManager() {
        return {
            activeMenu: null,
            menuPos: { top: '0px', bottom: 'auto', right: '0px', left: 'auto' },
            showPaymentModal: false,
            showDetailsModal: false,
            detailsData: {},

            paymentData: {
                provider_id: '',
                amount: 0,
                payment_method: 'BANK_TRANSFER',
                bank_name: '',
                transaction_ref: '',
                paid_at: '{{ date('Y-m-d\TH:i') }}'
            },

            toggleMenu(item, event) {
                if (this.activeMenu?.id === item.id) {
                    this.activeMenu = null;
                    return;
                }
                this.activeMenu = item;
                const rect = event.currentTarget.getBoundingClientRect();
                const dropdownHeight = 150;
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

            openPaymentModal() {
                this.activeMenu = null;
                this.paymentData = {
                    provider_id: '',
                    amount: '',
                    payment_method: 'BANK_TRANSFER',
                    bank_name: '',
                    transaction_ref: '',
                    paid_at: '{{ date('Y-m-d\TH:i') }}'
                };
                this.showPaymentModal = true;
            },

            viewPaymentDetails(item) {
                this.activeMenu = null;
                this.detailsData = item;
                this.showDetailsModal = true;
            },

            async deletePayment(item) {
                this.activeMenu = null;
                const result = await Swal.fire({
                    title: 'Delete Payment Voucher?',
                    text: `Are you sure you want to delete voucher '${item.voucher_no}'? Linked carrier invoice balance will be readjusted.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e11d48',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Yes, Delete Voucher',
                    cancelButtonText: 'Cancel'
                });

                if (result.isConfirmed) {
                    try {
                        const response = await fetch(`/admin/upstream/payments/${item.id}`, {
                            method: 'DELETE',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            }
                        });
                        const data = await response.json();
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted',
                                text: data.message,
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                        }
                    } catch (err) {
                        Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to delete payment voucher.' });
                    }
                }
            }
        };
    }

    // Trigger SweetAlert2 for server-side flash sessions
    document.addEventListener('DOMContentLoaded', function() {
        @if(session('success'))
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: "{{ session('success') }}",
                timer: 2500,
                showConfirmButton: false
            });
        @endif
        @if(session('error'))
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: "{{ session('error') }}",
                confirmButtonColor: '#e11d48'
            });
        @endif
    });
</script>
@endpush
