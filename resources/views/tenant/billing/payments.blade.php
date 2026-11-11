@extends('tenant.layouts.app')

@section('title', __('Payment History & Settlements') . ' - ' . ($tenant->company_name ?? $tenant->name ?? 'ISP Portal'))

@push('styles')
    {{-- Page-specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="paymentsManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">

    {{-- 1. TOP HEADER BAR (Icon + Title + Action Buttons ONLY - Zero Subtitle) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-sm shadow-2xs flex-shrink-0">
                <i class="fas fa-receipt"></i>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <h1 class="text-sm font-bold text-slate-800 tracking-tight">{{ __('Payment History & Settlements') }}</h1>
                <span class="px-2 py-0.5 rounded-md text-[10px] font-mono font-medium bg-slate-100 text-slate-600 border border-slate-200">
                    {{ __('Total') }} {{ number_format($totalTransactionsCount) }}
                </span>
            </div>
        </div>
        <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap flex-shrink-0">
            <a href="{{ route('tenant.billing.invoices') }}"
               class="h-[34px] px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-file-invoice text-xs text-blue-600"></i>
                <span>{{ __('Invoices') }}</span>
            </a>

            <a href="{{ route('tenant.billing.dashboard') }}"
               class="h-[34px] px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-chart-pie text-xs text-slate-500"></i>
                <span>{{ __('Plan Overview') }}</span>
            </a>
        </div>
    </div>

    {{-- 2. KPI SUMMARY STRIP (Strictly 6 Cards) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        {{-- Card 1: Total Payments --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Total Payments') }}</span>
                <span class="text-[13px] font-bold font-mono text-slate-800 leading-tight block truncate">
                    {{ number_format($totalTransactionsCount) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-receipt"></i>
            </div>
        </div>

        {{-- Card 2: Total Settled --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-emerald-700 block truncate">{{ __('Total Settled') }}</span>
                <span class="text-[13px] font-bold font-mono text-emerald-600 leading-tight block truncate">
                    @currency($totalPaidAmount)
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-circle-check"></i>
            </div>
        </div>

        {{-- Card 3: bKash Pay --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-pink-700 block truncate">{{ __('bKash') }}</span>
                <span class="text-[13px] font-bold font-mono text-pink-600 leading-tight block truncate">
                    {{ number_format($bkashCount) }} {{ __('Trx') }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-pink-200 bg-pink-50 text-pink-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-mobile-screen"></i>
            </div>
        </div>

        {{-- Card 4: Nagad Pay --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-orange-700 block truncate">{{ __('Nagad') }}</span>
                <span class="text-[13px] font-bold font-mono text-orange-600 leading-tight block truncate">
                    {{ number_format($nagadCount) }} {{ __('Trx') }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-orange-200 bg-orange-50 text-orange-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-wallet"></i>
            </div>
        </div>

        {{-- Card 5: Online Gateway --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-blue-700 block truncate">{{ __('Online Gateway') }}</span>
                <span class="text-[13px] font-bold font-mono text-blue-600 leading-tight block truncate">
                    {{ number_format($onlineCount) }} {{ __('Trx') }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-blue-200 bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-globe"></i>
            </div>
        </div>

        {{-- Card 6: Manual / Bank --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Manual / Bank') }}</span>
                <span class="text-[13px] font-bold font-mono text-slate-800 leading-tight block truncate">
                    {{ number_format($manualCount) }} {{ __('Trx') }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-slate-200 bg-slate-50 text-slate-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-money-bill-wave"></i>
            </div>
        </div>
    </div>

    {{-- 3. SEARCH & MULTI-FILTER TOOLBAR (Customer-Standard 2-Tier Filter System) --}}
    @php
        $isMoreOpen = (!empty($dateFrom) || !empty($dateTo) || !empty($status));
        $activeMoreCount = (!empty($dateFrom) ? 1 : 0) + (!empty($dateTo) ? 1 : 0) + (!empty($status) ? 1 : 0);
    @endphp
    <div class="bg-white p-2.5 rounded-xl border border-slate-200/90 shadow-2xs space-y-2.5"
         x-data="{ showMoreFilters: {{ $isMoreOpen ? 'true' : 'false' }}, searchQuery: '{{ addslashes($search ?? '') }}' }">
        <form action="{{ route('tenant.billing.payments') }}" method="GET" class="space-y-2.5">

            {{-- 1st Tier: Primary Single Row --}}
            <div class="flex flex-col md:flex-row items-stretch md:items-center gap-2">
                {{-- Intelligent Live Search Box with 1-Click Clear --}}
                <div class="relative flex-1 min-w-[200px]">
                    <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-xs transition-colors"
                       :class="searchQuery ? 'text-cyan-600 font-bold' : 'text-slate-400'"></i>
                    <input type="text"
                           name="search"
                           x-model="searchQuery"
                           value="{{ $search }}"
                           placeholder="{{ __('Search by transaction reference, gateway, or invoice #...') }}"
                           class="h-[34px] w-full pl-8 pr-7 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    <button type="button"
                            x-show="searchQuery && searchQuery.length > 0"
                            x-cloak
                            @click="searchQuery = ''; $nextTick(() => $el.closest('form').submit())"
                            title="{{ __('Clear Search') }}"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-0.5 rounded cursor-pointer">
                        <i class="fas fa-xmark text-xs"></i>
                    </button>
                </div>

                {{-- Primary Filter: Gateway --}}
                <select name="gateway" class="h-[34px] w-full md:w-44 bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 focus:bg-white focus:border-cyan-500 focus:outline-hidden font-medium cursor-pointer transition">
                    <option value="">{{ __('All Gateways') }}</option>
                    <option value="bkash" {{ $gateway === 'bkash' ? 'selected' : '' }}>{{ __('bKash Checkout') }}</option>
                    <option value="nagad" {{ $gateway === 'nagad' ? 'selected' : '' }}>{{ __('Nagad Payment') }}</option>
                    <option value="sslcommerz" {{ $gateway === 'sslcommerz' ? 'selected' : '' }}>SSLCommerz</option>
                    <option value="aamarpay" {{ $gateway === 'aamarpay' ? 'selected' : '' }}>Aamarpay</option>
                    <option value="manual" {{ $gateway === 'manual' ? 'selected' : '' }}>{{ __('Manual Deposit') }}</option>
                </select>

                {{-- Pagination Selector (per_page) --}}
                <select name="per_page" class="h-[34px] w-full md:w-28 flex-shrink-0 bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 focus:bg-white focus:border-cyan-500 focus:outline-hidden font-mono cursor-pointer transition">
                    <option value="15" {{ ($perPage ?? 15) == 15 ? 'selected' : '' }}>15 / {{ __('page') }}</option>
                    <option value="20" {{ ($perPage ?? 15) == 20 ? 'selected' : '' }}>20 / {{ __('page') }}</option>
                    <option value="50" {{ ($perPage ?? 15) == 50 ? 'selected' : '' }}>50 / {{ __('page') }}</option>
                    <option value="100" {{ ($perPage ?? 15) == 100 ? 'selected' : '' }}>100 / {{ __('page') }}</option>
                </select>

                {{-- More Filters Toggle Accordion Button --}}
                <button type="button"
                        @click="showMoreFilters = !showMoreFilters"
                        title="{{ __('Toggle Advanced Filters') }}"
                        class="h-[34px] px-2.5 py-0 bg-slate-50 hover:bg-slate-100 text-slate-700 font-medium text-xs rounded-lg border border-slate-200 transition flex items-center justify-center gap-1.5 cursor-pointer flex-shrink-0"
                        :class="showMoreFilters ? 'border-cyan-400 bg-cyan-50/50 text-cyan-800' : ''">
                    <i class="fas fa-sliders text-[11px] text-slate-500"></i>
                    <span>{{ __('More Filters') }}</span>
                    @if($activeMoreCount > 0)
                        <span class="w-4 h-4 rounded-full bg-cyan-600 text-white text-[9px] font-bold flex items-center justify-center">{{ $activeMoreCount }}</span>
                    @endif
                    <i class="fas text-[10px] transition-transform duration-200" :class="showMoreFilters ? 'fa-chevron-up text-cyan-600' : 'fa-chevron-down text-slate-400'"></i>
                </button>

                {{-- Strict Filter & Reset Action Buttons Sequence --}}
                <div class="flex items-center gap-1.5 flex-shrink-0 md:ml-auto">
                    <button type="submit"
                            title="{{ __('Apply Filters') }}"
                            class="h-[34px] bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-0 px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                        <i class="fas fa-filter text-[10px]"></i>
                        <span>{{ __('Filter') }}</span>
                    </button>
                    <a href="{{ route('tenant.billing.payments') }}"
                       class="h-[34px] bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-0 px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer"
                       title="{{ __('Reset Filters') }}">
                        <i class="fas fa-rotate-left text-[10px]"></i>
                        <span>{{ __('Reset') }}</span>
                    </a>
                </div>
            </div>

            {{-- 2nd Tier: Secondary Filters Drawer --}}
            <div x-show="showMoreFilters"
                 x-cloak
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="pt-2 border-t border-slate-100 flex flex-wrap items-center gap-2">

                {{-- Status Filter --}}
                <div class="w-full sm:w-auto">
                    <label class="block text-[9.5px] uppercase font-bold text-slate-400 mb-0.5">{{ __('Status') }}</label>
                    <select name="status" class="h-[34px] w-full sm:w-40 bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 focus:bg-white focus:border-cyan-500 focus:outline-hidden font-medium cursor-pointer transition">
                        <option value="">{{ __('All Statuses') }}</option>
                        <option value="success" {{ ($status ?? '') === 'success' ? 'selected' : '' }}>{{ __('Successful') }}</option>
                        <option value="pending" {{ ($status ?? '') === 'pending' ? 'selected' : '' }}>{{ __('Pending') }}</option>
                        <option value="failed" {{ ($status ?? '') === 'failed' ? 'selected' : '' }}>{{ __('Failed') }}</option>
                    </select>
                </div>

                {{-- Date From --}}
                <div class="w-full sm:w-auto">
                    <label class="block text-[9.5px] uppercase font-bold text-slate-400 mb-0.5">{{ __('From Date') }}</label>
                    <input type="date" name="date_from" value="{{ $dateFrom ?? '' }}" class="h-[34px] w-full sm:w-40 bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 font-mono focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                </div>

                {{-- Date To --}}
                <div class="w-full sm:w-auto">
                    <label class="block text-[9.5px] uppercase font-bold text-slate-400 mb-0.5">{{ __('To Date') }}</label>
                    <input type="date" name="date_to" value="{{ $dateTo ?? '' }}" class="h-[34px] w-full sm:w-40 bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 font-mono focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                </div>
            </div>
        </form>
    </div>

    {{-- 4. MASTER COMPACT TABLE (<table class="saas-table">) --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-10 text-center font-semibold no-sort">#</th>
                        <th class="text-center font-semibold">{{ __('Date') }}</th>
                        <th class="text-center font-semibold">{{ __('Transaction ID') }}</th>
                        <th class="text-center font-semibold">{{ __('Gateway') }}</th>
                        <th class="text-center font-semibold">{{ __('Invoice #') }}</th>
                        <th class="text-center font-semibold">{{ __('Amount') }}</th>
                        <th class="text-center font-semibold">{{ __('Status') }}</th>
                        <th class="w-16 text-center font-semibold no-sort">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $index => $pay)
                        @php
                            $trxId = $pay->transaction_id ?: $pay->gateway_transaction_id ?: ('TRX-' . $pay->id);
                        @endphp
                        <tr>
                            <td class="text-center font-mono text-slate-400 text-xs w-10">
                                {{ $payments->firstItem() + $index }}
                            </td>

                            <td class="text-center font-mono text-slate-500 text-[11px] whitespace-nowrap">
                                {{ $pay->created_at ? $pay->created_at->format('d M Y - h:i A') : '--' }}
                            </td>

                            <td class="text-left font-mono font-semibold text-slate-900 text-xs whitespace-nowrap">
                                <span class="cursor-pointer hover:text-cyan-700 hover:underline" @click="viewPaymentDetails({{ json_encode($pay) }})">
                                    {{ $trxId }}
                                </span>
                            </td>

                            <td class="text-center whitespace-nowrap">
                                @if(in_array(strtolower((string) $pay->payment_method), ['bkash']))
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold border border-pink-200 bg-pink-50 text-pink-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-pink-500"></span>
                                        <span>{{ __('bKash') }}</span>
                                    </span>
                                @elseif(in_array(strtolower((string) $pay->payment_method), ['nagad']))
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold border border-orange-200 bg-orange-50 text-orange-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-orange-500"></span>
                                        <span>{{ __('Nagad') }}</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold uppercase font-mono border border-slate-200 bg-slate-50 text-slate-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                        <span>{{ $pay->payment_method ?: __('Online') }}</span>
                                    </span>
                                @endif
                            </td>

                            <td class="text-center font-mono text-xs whitespace-nowrap">
                                @if($pay->invoice)
                                    <a href="{{ route('tenant.billing.invoice.show', $pay->invoice) }}" class="text-cyan-700 hover:text-cyan-900 font-bold hover:underline">
                                        {{ $pay->invoice->invoice_no }}
                                    </a>
                                @else
                                    <span class="text-slate-400">{{ __('Direct Settlement') }}</span>
                                @endif
                            </td>

                            <td class="text-right font-mono font-bold text-emerald-700 text-xs whitespace-nowrap">
                                @currency($pay->amount)
                            </td>

                            <td class="text-center whitespace-nowrap">
                                @if($pay->status === 'success' || $pay->status === 'completed' || !$pay->status)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold border border-emerald-200 bg-emerald-50 text-emerald-700">
                                        <i class="fas fa-check text-[8px]"></i>
                                        <span>{{ __('Success') }}</span>
                                    </span>
                                @elseif($pay->status === 'pending')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold border border-amber-200 bg-amber-50 text-amber-700">
                                        <i class="fas fa-clock text-[8px]"></i>
                                        <span>{{ __('Pending') }}</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold border border-rose-200 bg-rose-50 text-rose-700">
                                        <i class="fas fa-xmark text-[8px]"></i>
                                        <span>{{ __(ucwords(str_replace('_', ' ', $pay->status))) }}</span>
                                    </span>
                                @endif
                            </td>

                            <td class="text-center whitespace-nowrap">
                                <button type="button"
                                        @click="viewPaymentDetails({{ json_encode($pay) }})"
                                        class="h-[26px] px-2.5 rounded-md text-[10.5px] font-semibold border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-700 transition inline-flex items-center gap-1 cursor-pointer"
                                        title="{{ __('View Details') }}">
                                    <i class="fas fa-eye text-[10px] text-slate-500"></i>
                                    <span>{{ __('Details') }}</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-8 text-slate-400">
                                <div class="max-w-xs mx-auto space-y-2">
                                    <div class="w-10 h-10 rounded-full bg-emerald-50 text-emerald-600 mx-auto flex items-center justify-center text-sm border border-emerald-100">
                                        <i class="fas fa-receipt"></i>
                                    </div>
                                    <p class="text-xs font-semibold text-slate-700">{{ __('No Settlement Records Found') }}</p>
                                    <p class="text-[11px] text-slate-400 leading-tight">{{ __('No subscription payment transactions match the selected filter criteria.') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Laravel Native Compact Pagination --}}
        @if($payments->hasPages())
            <div class="px-4 py-2.5 border-t border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row items-center justify-between gap-2">
                <span class="text-xs text-slate-500 font-medium">
                    {{ __('Showing :first to :last of :total settlement transactions', ['first' => $payments->firstItem(), 'last' => $payments->lastItem(), 'total' => $payments->total()]) }}
                </span>
                <div>
                    {{ $payments->links() }}
                </div>
            </div>
        @endif
    </div>

    {{-- 5. VIEW PAYMENT RECEIPT NATURAL MODAL --}}
    <div x-show="showReceiptModal"
         x-cloak
         class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto"
         style="display: none;">
        <div @click.away="showReceiptModal = false"
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden my-6 flex flex-col">

            {{-- Modal Header --}}
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between flex-shrink-0">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">{{ __('Payment Transaction Details') }}</h3>
                </div>
                <button type="button" @click="showReceiptModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            {{-- Modal Body --}}
            <div class="p-5 space-y-3.5 text-xs" x-show="selectedPayment">
                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] uppercase font-bold text-slate-500">{{ __('Transaction ID') }}</span>
                        <span class="font-mono text-xs text-slate-900 font-bold" x-text="selectedPayment?.transaction_id || selectedPayment?.gateway_transaction_id || ('TRX-' + selectedPayment?.id)"></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] uppercase font-bold text-slate-500">{{ __('Payment Gateway') }}</span>
                        <span class="font-semibold text-slate-800 uppercase font-mono" x-text="selectedPayment?.payment_method || i18n.online"></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] uppercase font-bold text-slate-500">{{ __('Settled Amount') }}</span>
                        <span class="font-mono text-sm text-emerald-700 font-bold" x-text="currencySymbol + parseFloat(selectedPayment?.amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2})"></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] uppercase font-bold text-slate-500">{{ __('Status') }}</span>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold border border-emerald-200 bg-emerald-50 text-emerald-700">
                            <i class="fas fa-check text-[8px]"></i>
                            <span x-text="statusLabel(selectedPayment?.status)"></span>
                        </span>
                    </div>
                </div>

                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200/80 space-y-1.5 text-[11px]">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">{{ __('Associated Invoice') }}:</span>
                        <span class="font-mono font-bold text-cyan-800" x-text="selectedPayment?.invoice ? selectedPayment.invoice.invoice_no : i18n.directRecharge"></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">{{ __('Paid Timestamp') }}:</span>
                        <span class="font-mono text-slate-700" x-text="selectedPayment?.created_at || '--'"></span>
                    </div>
                    <template x-if="selectedPayment?.invoice">
                        <div class="flex items-center justify-between pt-1 border-t border-slate-200/60">
                            <span class="text-slate-500">{{ __('Invoice Plan') }}:</span>
                            <span class="font-semibold text-slate-800" x-text="selectedPayment?.invoice?.plan ? selectedPayment.invoice.plan.name : i18n.subscription"></span>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between flex-shrink-0">
                <template x-if="selectedPayment?.invoice">
                    <a :href="`/admin/billing/invoices/${selectedPayment.invoice.id}`" class="h-[34px] px-3.5 bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                        <i class="fas fa-file-invoice text-xs"></i>
                        <span>{{ __('View Invoice') }}</span>
                    </a>
                </template>
                <div :class="!selectedPayment?.invoice ? 'w-full flex justify-end' : ''">
                    <button type="button" @click="showReceiptModal = false" class="h-[34px] border border-slate-300 hover:bg-slate-100 text-slate-700 font-semibold text-xs px-4 rounded-lg transition cursor-pointer">
                        {{ __('Close') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function paymentsManager() {
    return {
        showReceiptModal: false,
        selectedPayment: null,
        currencySymbol: @js($currencySymbol ?? '৳'),
        i18n: {
            online: @js(__('Online')),
            directRecharge: @js(__('Direct Recharge')),
            subscription: @js(__('Standard Plan')),
            completed: @js(__('Completed')),
            pending: @js(__('Pending')),
            failed: @js(__('Failed')),
        },

        statusLabel(status) {
            const normalized = String(status || '').toLowerCase();
            if (!normalized || normalized === 'success' || normalized === 'completed' || normalized === 'paid') {
                return this.i18n.completed;
            }
            if (normalized === 'pending') {
                return this.i18n.pending;
            }
            if (normalized === 'failed') {
                return this.i18n.failed;
            }
            return status;
        },

        viewPaymentDetails(pay) {
            this.selectedPayment = pay;
            this.showReceiptModal = true;
        }
    };
}
</script>
@endpush
