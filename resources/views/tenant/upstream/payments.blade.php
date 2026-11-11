@extends('tenant.layouts.app')

@section('title', __('Carrier Payment Vouchers') . ' - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
    {{-- Page specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="upstreamPaymentManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">

    <!-- 1. Top Header Bar (Icon + Title + Action Buttons ONLY) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-white px-3.5 py-2.5 rounded-xl border border-slate-200/90 shadow-2xs">
        <div class="flex items-center gap-2.5">
            <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-xs shadow-2xs flex-shrink-0">
                <i class="fas fa-money-bill-transfer"></i>
            </div>
            <div class="flex items-center gap-2">
                <h1 class="text-xs sm:text-sm font-bold text-slate-800 tracking-tight">{{ __('Carrier Payment Vouchers') }}</h1>
                <span class="text-[10px] font-bold text-slate-500 bg-slate-100 border border-slate-200/80 px-2 py-0.5 rounded-full font-mono">{{ number_format($payments->total()) }}</span>
            </div>
        </div>

        <div class="flex items-center gap-1.5 flex-wrap">
            <button type="button"
                    @click="openPaymentModal()"
                    class="h-[34px] px-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-plus text-xs"></i>
                <span>{{ __('Disburse Payment') }}</span>
            </button>

            <a href="{{ route('tenant.upstream.invoices') }}"
               class="h-[34px] px-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200/80 rounded-lg text-xs font-medium transition flex items-center gap-1 cursor-pointer"
               title="{{ __('Carrier Bills') }}">
                <i class="fas fa-file-invoice-dollar text-[11px] text-slate-500"></i>
                <span>{{ __('Carrier Bills') }}</span>
            </a>

            <a href="{{ route('tenant.upstream.index') }}"
               class="h-[34px] px-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200/80 rounded-lg text-xs font-medium transition flex items-center gap-1 cursor-pointer"
               title="{{ __('Carriers & Links') }}">
                <i class="fas fa-tower-broadcast text-[11px] text-slate-500"></i>
                <span>{{ __('Carriers & Links') }}</span>
            </a>
        </div>
    </div>

    <!-- 2. KPI Summary Strip (Strictly 6 Cards) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        <!-- Card 1: Total Disbursed -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">{{ __('Total Disbursed') }}</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-emerald-700 truncate">@currency($totalDisbursed)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-emerald-50 text-emerald-600 border-emerald-100 flex items-center justify-center">
                <i class="fas fa-money-bill-wave"></i>
            </div>
        </div>

        <!-- Card 2: Total Vouchers Count -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">{{ __('Total Vouchers') }}</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-slate-900 truncate">{{ number_format($totalVouchersCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-slate-50 text-slate-600 border-slate-200 flex items-center justify-center">
                <i class="fas fa-receipt"></i>
            </div>
        </div>

        <!-- Card 3: Bank Transfer Total -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">{{ __('Bank Transfer') }}</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-blue-700 truncate">@currency($bankTransferTotal)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-blue-50 text-blue-600 border-blue-100 flex items-center justify-center">
                <i class="fas fa-building-columns"></i>
            </div>
        </div>

        <!-- Card 4: Cheque / RTGS Total -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">{{ __('Cheque / RTGS') }}</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-indigo-700 truncate">@currency($chequeRtgsTotal)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-indigo-50 text-indigo-600 border-indigo-100 flex items-center justify-center">
                <i class="fas fa-money-check"></i>
            </div>
        </div>

        <!-- Card 5: Cash Settlements -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">{{ __('Cash Disbursed') }}</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-amber-700 truncate">@currency($cashTotal)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-amber-50 text-amber-600 border-amber-100 flex items-center justify-center">
                <i class="fas fa-coins"></i>
            </div>
        </div>

        <!-- Card 6: This Month Disbursed -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">{{ __('This Month') }}</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-cyan-800 truncate">@currency($thisMonthTotal)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-cyan-50 text-cyan-600 border-cyan-100 flex items-center justify-center">
                <i class="fas fa-calendar-check"></i>
            </div>
        </div>
    </div>

    <!-- 3. Search & Multi-Filter Toolbar (Customer-Standard 2-Tier Filter System) -->
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
                           placeholder="{{ __('Search Voucher No, Carrier, Bank, Cheque...') }}"
                           class="w-full h-[34px] pl-8 pr-7 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    <button type="button"
                            x-show="searchQuery && searchQuery.length > 0"
                            x-cloak
                            @click="searchQuery = ''; $nextTick(() => $el.closest('form').submit())"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 cursor-pointer"
                            title="{{ __('Clear Search') }}">
                        <i class="fas fa-xmark text-xs"></i>
                    </button>
                </div>

                <!-- Carrier Provider Primary Filter -->
                <div class="w-full md:w-52 flex-shrink-0">
                    <select name="provider_id"
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="">{{ __('All Carrier Providers') }}</option>
                        @foreach($providers as $p)
                        <option value="{{ $p->id }}" {{ request('provider_id') == $p->id ? 'selected' : '' }}>
                            {{ $p->name }} ({{ __($p->carrier_type ?: 'Upstream Carrier') }})
                        </option>
                        @endforeach
                    </select>
                </div>

                <!-- Payment Method Primary Filter -->
                <div class="w-full md:w-44 flex-shrink-0">
                    <select name="payment_method"
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="">{{ __('All Methods') }}</option>
                        <option value="BANK_TRANSFER" {{ request('payment_method') === 'BANK_TRANSFER' ? 'selected' : '' }}>{{ __('Bank Transfer / BEFTN') }}</option>
                        <option value="CHEQUE" {{ request('payment_method') === 'CHEQUE' ? 'selected' : '' }}>{{ __('Bank Cheque') }}</option>
                        <option value="RTGS" {{ request('payment_method') === 'RTGS' ? 'selected' : '' }}>{{ __('RTGS Instant') }}</option>
                        <option value="CASH" {{ request('payment_method') === 'CASH' ? 'selected' : '' }}>{{ __('Cash') }}</option>
                        <option value="OTHER" {{ request('payment_method') === 'OTHER' ? 'selected' : '' }}>{{ __('Other Method') }}</option>
                    </select>
                </div>

                <!-- Pagination Selector (per_page) -->
                <div class="w-full md:w-28 flex-shrink-0">
                    <select name="per_page"
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="15" {{ request('per_page') == '15' ? 'selected' : '' }}>15 / {{ __('page') }}</option>
                        <option value="20" {{ request('per_page', '20') == '20' ? 'selected' : '' }}>20 / {{ __('page') }}</option>
                        <option value="30" {{ request('per_page') == '30' ? 'selected' : '' }}>30 / {{ __('page') }}</option>
                        <option value="50" {{ request('per_page') == '50' ? 'selected' : '' }}>50 / {{ __('page') }}</option>
                        <option value="100" {{ request('per_page') == '100' ? 'selected' : '' }}>100 / {{ __('page') }}</option>
                        <option value="250" {{ request('per_page') == '250' ? 'selected' : '' }}>250 / {{ __('page') }}</option>
                    </select>
                </div>

                <!-- More Filters Toggle Button -->
                <button type="button"
                        @click="showMoreFilters = !showMoreFilters"
                        title="{{ __('Toggle Advanced Filters') }}"
                        class="h-[34px] py-0 px-2.5 rounded-lg border text-xs font-medium flex items-center justify-center gap-1.5 transition flex-shrink-0 cursor-pointer"
                        :class="showMoreFilters || {{ $activeMoreCount }} > 0 ? 'bg-cyan-50 border-cyan-200 text-cyan-700' : 'bg-slate-50 hover:bg-slate-100 border-slate-200 text-slate-600'">
                    <i class="fas fa-sliders text-[11px]"></i>
                    <span>{{ __('More Filters') }}</span>
                    @if($activeMoreCount > 0)
                    <span class="w-4 h-4 rounded-full bg-cyan-600 text-white text-[9px] font-bold flex items-center justify-center">
                        {{ $activeMoreCount }}
                    </span>
                    @endif
                    <i class="fas text-[9px] transition-transform duration-150"
                       :class="showMoreFilters ? 'fa-chevron-up text-cyan-600' : 'fa-chevron-down text-slate-400'"></i>
                </button>

                <!-- Filter & Reset Button Sequence -->
                <div class="flex items-center gap-1.5 flex-shrink-0 md:ml-auto">
                    <button type="submit"
                            title="{{ __('Apply Filters') }}"
                            class="h-[34px] bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-0 px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                        <i class="fas fa-filter text-[10px]"></i>
                        <span>{{ __('Filter') }}</span>
                    </button>

                    <a href="{{ route('tenant.upstream.payments') }}"
                       class="h-[34px] bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-0 px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer"
                       title="{{ __('Reset Filters') }}">
                        <i class="fas fa-rotate-left text-[10px]"></i>
                        <span>{{ __('Reset') }}</span>
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
                    <label class="block text-[9px] font-bold uppercase tracking-wider text-slate-400 mb-0.5">{{ __('From Date') }}</label>
                    <input type="date"
                           name="start_date"
                           value="{{ request('start_date') }}"
                           class="w-full h-[34px] px-2.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                </div>

                <!-- End Date -->
                <div class="w-full sm:w-40 flex-shrink-0">
                    <label class="block text-[9px] font-bold uppercase tracking-wider text-slate-400 mb-0.5">{{ __('To Date') }}</label>
                    <input type="date"
                           name="end_date"
                           value="{{ request('end_date') }}"
                           class="w-full h-[34px] px-2.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                </div>
            </div>
        </form>
    </div>

    <!-- 4. Master Table (Strictly .saas-table pure CSS system) -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-10 text-center font-semibold no-sort">#</th>
                        <th class="text-center font-semibold">{{ __('Voucher No') }}</th>
                        <th class="text-center font-semibold">{{ __('Carrier Provider') }}</th>
                        <th class="text-center font-semibold">{{ __('Linked Bill') }}</th>
                        <th class="text-center font-semibold">{{ __('Payment Method') }}</th>
                        <th class="text-center font-semibold">{{ __('Bank / Trx Ref') }}</th>
                        <th class="text-center font-semibold">{{ __('Paid Amount') }}</th>
                        <th class="text-center font-semibold">{{ __('Paid Date') }}</th>
                        <th class="w-12 text-center font-semibold no-sort">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $index => $payment)
                    <tr>
                        <td class="text-center font-mono text-slate-500">
                            {{ $payments->firstItem() + $index }}
                        </td>

                        <td class="text-center font-mono font-bold">
                            <button type="button"
                                    @click="viewPaymentDetails({{ json_encode([
                                        'id' => $payment->id,
                                        'voucher_no' => $payment->voucher_no,
                                        'provider_id' => $payment->provider_id,
                                        'provider_name' => $payment->provider->name ?? '-',
                                        'carrier_type' => $payment->provider->carrier_type ?? 'Upstream Carrier',
                                        'invoice_no' => $payment->invoice->invoice_no ?? '-',
                                        'amount' => $payment->amount,
                                        'payment_method' => $payment->payment_method,
                                        'bank_name' => $payment->bank_name,
                                        'cheque_no' => $payment->cheque_no,
                                        'transaction_ref' => $payment->transaction_ref,
                                        'paid_at' => $payment->paid_at ? $payment->paid_at->format('d M, Y h:i A') : '-',
                                        'creator_name' => $payment->creator->name ?? __('System Admin'),
                                        'notes' => $payment->notes,
                                    ]) }})"
                                    class="text-cyan-700 hover:text-cyan-900 hover:underline cursor-pointer">
                                {{ $payment->voucher_no }}
                            </button>
                        </td>

                        <td class="text-left font-semibold text-slate-800">
                            {{ $payment->provider->name ?? '-' }}
                        </td>

                        <td class="text-center font-mono text-slate-700">
                            {{ $payment->invoice->invoice_no ?? '-' }}
                        </td>

                        <td class="text-center">
                            @php
                                $badge = $payment->method_badge;
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold border {{ $badge['class'] ?? 'bg-slate-100 text-slate-700 border-slate-200' }}">
                                {{ __($badge['label'] ?? str_replace('_', ' ', $payment->payment_method)) }}
                            </span>
                        </td>

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

                        <td class="text-right font-mono font-bold text-emerald-700">
                            @currency($payment->amount)
                        </td>

                        <td class="text-center font-mono text-slate-600">
                            {{ $payment->paid_at ? $payment->paid_at->format('d M, Y') : '-' }}
                        </td>

                        <td class="text-center">
                            <button type="button"
                                    @click="toggleMenu({{ json_encode([
                                        'id' => $payment->id,
                                        'voucher_no' => $payment->voucher_no,
                                        'provider_id' => $payment->provider_id,
                                        'provider_name' => $payment->provider->name ?? '-',
                                        'carrier_type' => $payment->provider->carrier_type ?? 'Upstream Carrier',
                                        'invoice_no' => $payment->invoice->invoice_no ?? '-',
                                        'amount' => $payment->amount,
                                        'payment_method' => $payment->payment_method,
                                        'bank_name' => $payment->bank_name,
                                        'cheque_no' => $payment->cheque_no,
                                        'transaction_ref' => $payment->transaction_ref,
                                        'paid_at' => $payment->paid_at ? $payment->paid_at->format('d M, Y h:i A') : '-',
                                        'creator_name' => $payment->creator->name ?? __('System Admin'),
                                        'notes' => $payment->notes,
                                    ]) }}, $event)"
                                    title="{{ __('Actions') }}"
                                    class="w-7 h-7 rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-600 flex items-center justify-center mx-auto transition cursor-pointer">
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
                                <span class="text-xs font-semibold text-slate-700">{{ __('No Payment Vouchers Found') }}</span>
                                <p class="text-[11px] text-slate-400 max-w-sm whitespace-normal">{{ __('No carrier payments match your search filters or no bank disbursements have been recorded yet.') }}</p>
                                <button type="button"
                                        @click="openPaymentModal()"
                                        class="mt-2 h-[34px] px-3.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                                    <i class="fas fa-plus text-xs"></i>
                                    <span>{{ __('Disburse Payment') }}</span>
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
                {{ __('Showing :first to :last of :total vouchers', ['first' => $payments->firstItem(), 'last' => $payments->lastItem(), 'total' => $payments->total()]) }}
            </div>
            <div>
                {{ $payments->links() }}
            </div>
        </div>
        @endif
    </div>

    <!-- 5. Global Floating 3-Dot Action Menu -->
    <div x-show="activeMenu"
         x-cloak
         @click.away="activeMenu = null"
         class="fixed z-50 bg-white rounded-xl border border-slate-200 shadow-xl py-1 w-52 text-xs font-medium text-slate-700 space-y-0.5"
         :style="{ top: menuPos.top, bottom: menuPos.bottom, right: menuPos.right, left: menuPos.left }">

        <button type="button"
                @click="viewPaymentDetails(activeMenu)"
                class="w-full text-left px-3 py-1.5 hover:bg-slate-50 flex items-center gap-2 text-slate-700 transition cursor-pointer">
            <i class="fas fa-circle-info w-4 text-cyan-600 text-[11px]"></i>
            <span>{{ __('View Voucher Details') }}</span>
        </button>

        <a :href="`/admin/upstream/payments/${activeMenu?.id}/voucher`"
           target="_blank"
           class="w-full text-left px-3 py-1.5 hover:bg-slate-50 flex items-center gap-2 text-slate-700 transition cursor-pointer">
            <i class="fas fa-print w-4 text-indigo-500 text-[11px]"></i>
            <span>{{ __('Print Payment Voucher') }}</span>
        </a>

        <div class="border-t border-slate-100 my-0.5"></div>

        <button type="button"
                @click="deletePayment(activeMenu)"
                class="w-full text-left px-3 py-1.5 hover:bg-rose-50 flex items-center gap-2 text-rose-600 transition cursor-pointer">
            <i class="fas fa-trash-can w-4 text-rose-400 text-[11px]"></i>
            <span>{{ __('Delete Voucher') }}</span>
        </button>
    </div>

    <!-- Modal 1: Disburse Payment Modal -->
    <div x-show="showPaymentModal"
         x-cloak
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50">
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden"
             @click.away="showPaymentModal = false">

            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-money-bill-transfer"></i>
                    </div>
                    <div>
                        <h2 class="text-xs font-semibold text-slate-800">{{ __('Disburse Payment to Carrier') }}</h2>
                        <p class="text-[10.5px] text-slate-500 font-normal">{{ __('Record bank settlement and generate printable voucher.') }}</p>
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
                        {{ __('Carrier Provider') }} <span class="text-rose-500">*</span>
                    </label>
                    <select name="provider_id"
                            x-model="paymentData.provider_id"
                            required
                            class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="">{{ __('Select Carrier') }}</option>
                        @foreach($providers as $p)
                        <option value="{{ $p->id }}">{{ $p->name }} ({{ __($p->carrier_type ?: 'Upstream Carrier') }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            {{ __('Paid Amount') }} ({{ $currencySymbol }}) <span class="text-rose-500">*</span>
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
                            {{ __('Payment Method') }} <span class="text-rose-500">*</span>
                        </label>
                        <select name="payment_method"
                                x-model="paymentData.payment_method"
                                required
                                class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                            <option value="BANK_TRANSFER">{{ __('Bank Transfer / BEFTN') }}</option>
                            <option value="CHEQUE">{{ __('Bank Cheque') }}</option>
                            <option value="RTGS">{{ __('RTGS Instant') }}</option>
                            <option value="CASH">{{ __('Cash') }}</option>
                            <option value="OTHER">{{ __('Other Method') }}</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">{{ __('Bank Name / Branch') }}</label>
                        <input type="text"
                               name="bank_name"
                               x-model="paymentData.bank_name"
                               placeholder="e.g. City Bank - Gulshan"
                               class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">{{ __('Cheque / Trx Reference') }}</label>
                        <input type="text"
                               name="transaction_ref"
                               x-model="paymentData.transaction_ref"
                               placeholder="{{ __('Ref No / Cheque No') }}"
                               class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">{{ __('Payment Date') }} <span class="text-rose-500">*</span></label>
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
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit"
                            class="h-[34px] px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs rounded-lg shadow-xs transition cursor-pointer">
                        {{ __('Disburse & Generate Voucher') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 2: View Payment Voucher Details Modal -->
    <div x-show="showDetailsModal"
         x-cloak
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50">
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden"
             @click.away="showDetailsModal = false">

            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                    <div>
                        <h2 class="text-xs font-semibold text-slate-800" x-text="`${i18n.paymentVoucher}: ${detailsData?.voucher_no || ''}`"></h2>
                        <p class="text-[10.5px] text-slate-500 font-normal" x-text="`${i18n.carrier}: ${detailsData?.provider_name || '-'} (${carrierTypeLabel(detailsData?.carrier_type)})`"></p>
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
                        <span class="text-[10px] uppercase font-bold text-emerald-600 block">{{ __('Total Disbursed') }}</span>
                        <span class="text-[11px] text-emerald-800">{{ __('Bank / Cash Settlement confirmed') }}</span>
                    </div>
                    <span class="text-xl font-mono font-bold text-emerald-700" x-text="`{{ $currencySymbol }} ${Number(detailsData?.amount || 0).toFixed(2)}`"></span>
                </div>

                <!-- Info Grid -->
                <div class="grid grid-cols-2 gap-2 text-[11.5px] p-3 bg-slate-50 rounded-lg border border-slate-200/80">
                    <div>
                        <span class="text-slate-400 block font-medium">{{ __('Payment Method') }}:</span>
                        <span class="font-semibold text-slate-800" x-text="methodLabel(detailsData?.payment_method)"></span>
                    </div>
                    <div>
                        <span class="text-slate-400 block font-medium">{{ __('Payment Date') }}:</span>
                        <span class="font-semibold text-slate-800" x-text="detailsData?.paid_at || '-'"></span>
                    </div>
                    <div class="mt-1">
                        <span class="text-slate-400 block font-medium">{{ __('Linked Bill') }}:</span>
                        <span class="font-mono font-semibold text-slate-800" x-text="(detailsData?.invoice_no && detailsData?.invoice_no !== '-') ? detailsData.invoice_no : i18n.directCarrierPayment"></span>
                    </div>
                    <div class="mt-1">
                        <span class="text-slate-400 block font-medium">{{ __('Prepared By') }}:</span>
                        <span class="font-semibold text-slate-800" x-text="detailsData?.creator_name || i18n.systemAdmin"></span>
                    </div>
                </div>

                <!-- Bank / Cheque Details -->
                <template x-if="detailsData?.bank_name || detailsData?.transaction_ref || detailsData?.cheque_no">
                    <div class="p-3 bg-slate-50 rounded-lg border border-slate-200/80 space-y-1.5 text-[11.5px]">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block">{{ __('Bank & Disbursement Method') }}</span>
                        <div class="flex items-center justify-between" x-show="detailsData?.bank_name">
                            <span class="text-slate-500">{{ __('Bank Name / Branch') }}:</span>
                            <span class="font-semibold text-slate-800" x-text="detailsData?.bank_name"></span>
                        </div>
                        <div class="flex items-center justify-between" x-show="detailsData?.cheque_no">
                            <span class="text-slate-500">{{ __('Cheque / Trx No') }}:</span>
                            <span class="font-mono font-semibold text-slate-800" x-text="detailsData?.cheque_no"></span>
                        </div>
                        <div class="flex items-center justify-between" x-show="detailsData?.transaction_ref">
                            <span class="text-slate-500">{{ __('Transaction Ref') }}:</span>
                            <span class="font-mono font-semibold text-slate-800" x-text="detailsData?.transaction_ref"></span>
                        </div>
                    </div>
                </template>

                <div class="pt-2 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button"
                            @click="showDetailsModal = false"
                            class="h-[34px] px-3.5 border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs rounded-lg transition cursor-pointer">
                        {{ __('Close') }}
                    </button>
                    <a :href="`/admin/upstream/payments/${detailsData?.id}/voucher`"
                       target="_blank"
                       class="h-[34px] px-3.5 bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                        <i class="fas fa-print text-xs"></i>
                        <span>{{ __('Print Voucher') }}</span>
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
            i18n: {
                paymentVoucher: @js(__('Payment Voucher')),
                carrier: @js(__('Carrier Provider')),
                directCarrierPayment: @js(__('Direct Carrier Payment')),
                systemAdmin: @js(__('System Admin')),
                deleteTitle: @js(__('Delete Payment Voucher?')),
                deleteConfirmPrefix: @js(__('Are you sure you want to delete voucher')),
                deleteConfirmBtn: @js(__('Yes, Delete Voucher')),
                cancelBtn: @js(__('Cancel')),
                deletedTitle: @js(__('Deleted!')),
                errorTitle: @js(__('Error')),
                deleteFailed: @js(__('Failed to delete payment voucher.')),
                methods: {
                    'BANK_TRANSFER': @js(__('Bank Transfer / BEFTN')),
                    'CHEQUE': @js(__('Bank Cheque')),
                    'RTGS': @js(__('RTGS Instant')),
                    'CASH': @js(__('Cash')),
                    'OTHER': @js(__('Other Method')),
                },
                carrierTypes: {
                    'Upstream Carrier': @js(__('Upstream Carrier')),
                    'ITC Carrier': @js(__('ITC Carrier')),
                    'NTTN Transmission': @js(__('NTTN Transmission')),
                    'BDIX Peering': @js(__('BDIX Peering')),
                    'CDN / Cache Partner': @js(__('CDN / Cache Partner')),
                },
            },

            paymentData: {
                provider_id: '',
                amount: 0,
                payment_method: 'BANK_TRANSFER',
                bank_name: '',
                transaction_ref: '',
                paid_at: '{{ date('Y-m-d\TH:i') }}'
            },

            methodLabel(m) {
                return this.i18n.methods[m] || (m ? String(m).replace(/_/g, ' ') : '-');
            },

            carrierTypeLabel(t) {
                return this.i18n.carrierTypes[t] || t || this.i18n.carrierTypes['Upstream Carrier'];
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
                    title: this.i18n.deleteTitle,
                    text: `${this.i18n.deleteConfirmPrefix} '${item.voucher_no}'?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e11d48',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: this.i18n.deleteConfirmBtn,
                    cancelButtonText: this.i18n.cancelBtn
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
                                title: this.i18n.deletedTitle,
                                text: data.message,
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({ icon: 'error', title: this.i18n.errorTitle, text: data.message });
                        }
                    } catch (err) {
                        Swal.fire({ icon: 'error', title: this.i18n.errorTitle, text: this.i18n.deleteFailed });
                    }
                }
            }
        };
    }

    document.addEventListener('DOMContentLoaded', function() {
        @if(session('success'))
            Swal.fire({
                icon: 'success',
                title: @js(__('Success')),
                text: @json(session('success')),
                timer: 2500,
                showConfirmButton: false
            });
        @endif
        @if(session('error'))
            Swal.fire({
                icon: 'error',
                title: @js(__('Error')),
                text: @json(session('error')),
                confirmButtonColor: '#e11d48'
            });
        @endif
    });
</script>
@endpush
