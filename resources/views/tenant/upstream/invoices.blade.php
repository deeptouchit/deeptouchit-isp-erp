@extends('tenant.layouts.app')

@section('title', __('Carrier Bills & Invoices') . ' - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
    {{-- Page specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="upstreamInvoiceManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">

    <!-- 1. Top Header Bar (Icon + Title + Action Buttons ONLY - Zero Subtitle) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-white px-3.5 py-2.5 rounded-xl border border-slate-200/90 shadow-2xs">
        <div class="flex items-center gap-2.5">
            <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center text-xs shadow-2xs flex-shrink-0">
                <i class="fas fa-file-invoice-dollar"></i>
            </div>
            <div class="flex items-center gap-2">
                <h1 class="text-xs sm:text-sm font-bold text-slate-800 tracking-tight">{{ __('Carrier Bills & Invoices') }}</h1>
                <span class="text-[10px] font-bold text-slate-500 bg-slate-100 border border-slate-200/80 px-2 py-0.5 rounded-full font-mono">{{ number_format($invoices->total()) }}</span>
            </div>
        </div>

        <div class="flex items-center gap-1.5 flex-wrap">
            <button type="button"
                    @click="openInvoiceModal()"
                    class="h-[34px] px-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-plus text-xs"></i>
                <span>{{ __('Record Bill') }}</span>
            </button>

            <button type="button"
                    @click="openPaymentModalDirect()"
                    class="h-[34px] px-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-money-bill-transfer text-xs"></i>
                <span>{{ __('Disburse Payment') }}</span>
            </button>

            <a href="{{ route('tenant.upstream.index') }}"
               class="h-[34px] px-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200/80 rounded-lg text-xs font-medium transition flex items-center gap-1 cursor-pointer"
               title="{{ __('Carriers & Links') }}">
                <i class="fas fa-tower-broadcast text-[11px] text-slate-500"></i>
                <span>{{ __('Carriers & Links') }}</span>
            </a>

            <a href="{{ route('tenant.upstream.payments') }}"
               class="h-[34px] px-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200/80 rounded-lg text-xs font-medium transition flex items-center gap-1 cursor-pointer"
               title="{{ __('Payment Vouchers') }}">
                <i class="fas fa-receipt text-[11px] text-slate-500"></i>
                <span>{{ __('Payment Vouchers') }}</span>
            </a>
        </div>
    </div>

    <!-- 2. KPI Summary Strip (Strictly 6 Cards) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">{{ __('Total Bills') }}</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-slate-900 truncate">{{ number_format($totalInvoicesCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-slate-50 text-slate-600 border-slate-200 flex items-center justify-center">
                <i class="fas fa-folder-open"></i>
            </div>
        </div>

        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">{{ __('Total Billed') }}</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-indigo-700 truncate">@currency($totalBilledAmount)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-indigo-50 text-indigo-600 border-indigo-100 flex items-center justify-center">
                <i class="fas fa-receipt"></i>
            </div>
        </div>

        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">{{ __('Total Settled') }}</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-emerald-700 truncate">@currency($totalPaidAmount)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-emerald-50 text-emerald-600 border-emerald-100 flex items-center justify-center">
                <i class="fas fa-circle-check"></i>
            </div>
        </div>

        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">{{ __('Total Due') }}</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-rose-700 truncate">@currency($totalDueBalance)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-rose-50 text-rose-600 border-rose-100 flex items-center justify-center">
                <i class="fas fa-clock"></i>
            </div>
        </div>

        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">{{ __('Fully Paid') }}</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-emerald-700 truncate">{{ number_format($paidInvoicesCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-emerald-50 text-emerald-600 border-emerald-100 flex items-center justify-center">
                <i class="fas fa-stamp"></i>
            </div>
        </div>

        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">{{ __('Pending / Due') }}</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-amber-700 truncate">{{ number_format($unpaidInvoicesCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-amber-50 text-amber-600 border-amber-100 flex items-center justify-center">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
        </div>
    </div>

    <!-- 3. Search & Multi-Filter Toolbar (Customer-Standard 2-Tier Filter System) -->
    @php
        $isMoreOpen = request()->filled('billing_month') || request()->filled('due_only');
        $activeMoreCount = (request()->filled('billing_month') ? 1 : 0) + (request()->filled('due_only') ? 1 : 0);
    @endphp
    <div class="bg-white p-2.5 rounded-xl border border-slate-200/90 shadow-2xs space-y-2.5"
         x-data="{
            showMoreFilters: {{ $isMoreOpen ? 'true' : 'false' }},
            searchQuery: '{{ addslashes(request('search', '')) }}'
         }">
        <form method="GET" action="{{ route('tenant.upstream.invoices') }}">
            <!-- Row 1: Primary Filters -->
            <div class="flex flex-col md:flex-row items-stretch md:items-center gap-2">
                <!-- Intelligent Live Search Input with 1-Click Clear -->
                <div class="relative flex-1 min-w-[200px]">
                    <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-xs transition-colors"
                       :class="searchQuery ? 'text-cyan-600 font-bold' : 'text-slate-400'"></i>
                    <input type="text"
                           name="search"
                           x-model="searchQuery"
                           placeholder="{{ __('Search Bill No, Carrier Provider, Notes...') }}"
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

                <!-- Payment Status Primary Filter -->
                <div class="w-full md:w-36 flex-shrink-0">
                    <select name="status"
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="">{{ __('All Statuses') }}</option>
                        <option value="PAID" {{ request('status') === 'PAID' ? 'selected' : '' }}>{{ __('Paid') }}</option>
                        <option value="PARTIAL" {{ request('status') === 'PARTIAL' ? 'selected' : '' }}>{{ __('Partial') }}</option>
                        <option value="UNPAID" {{ request('status') === 'UNPAID' ? 'selected' : '' }}>{{ __('Unpaid') }}</option>
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

                    <a href="{{ route('tenant.upstream.invoices') }}"
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

                <!-- Billing Month Filter -->
                <div class="w-full sm:w-44 flex-shrink-0">
                    <label class="block text-[9px] font-bold uppercase tracking-wider text-slate-400 mb-0.5">{{ __('Billing Month') }}</label>
                    <input type="month"
                           name="billing_month"
                           value="{{ request('billing_month') }}"
                           class="w-full h-[34px] px-2.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                </div>

                <!-- Outstanding Due Balance Filter -->
                <div class="w-full sm:w-48 flex-shrink-0">
                    <label class="block text-[9px] font-bold uppercase tracking-wider text-slate-400 mb-0.5">{{ __('Balance State') }}</label>
                    <select name="due_only"
                            class="w-full h-[34px] px-2.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="">{{ __('All Carrier Bills') }}</option>
                        <option value="1" {{ request('due_only') == '1' ? 'selected' : '' }}>{{ __('Outstanding Due Only') }}</option>
                    </select>
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
                        <th class="text-center font-semibold">{{ __('Invoice #') }}</th>
                        <th class="text-center font-semibold">{{ __('Carrier Provider') }}</th>
                        <th class="text-center font-semibold">{{ __('Billing Month') }}</th>
                        <th class="text-center font-semibold">{{ __('Due Date') }}</th>
                        <th class="text-center font-semibold">{{ __('Total Amount') }}</th>
                        <th class="text-center font-semibold">{{ __('Paid Amount') }}</th>
                        <th class="text-center font-semibold">{{ __('Due Balance') }}</th>
                        <th class="text-center font-semibold">{{ __('Status') }}</th>
                        <th class="w-12 text-center font-semibold no-sort">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $index => $invoice)
                    <tr>
                        <td class="text-center font-mono text-slate-500">
                            {{ $invoices->firstItem() + $index }}
                        </td>

                        <td class="text-center font-mono font-bold">
                            <button type="button"
                                    @click="viewInvoiceDetails({{ json_encode([
                                        'id' => $invoice->id,
                                        'invoice_no' => $invoice->invoice_no,
                                        'provider_id' => $invoice->provider_id,
                                        'provider_name' => $invoice->provider->name ?? '-',
                                        'carrier_type' => $invoice->provider->carrier_type ?? 'Upstream Carrier',
                                        'billing_month' => $invoice->billing_month ? $invoice->billing_month->format('F Y') : '-',
                                        'due_date' => $invoice->due_date ? $invoice->due_date->format('d M, Y') : '-',
                                        'bandwidth_cost' => $invoice->bandwidth_cost,
                                        'transmission_cost' => $invoice->transmission_cost,
                                        'vat_tax' => $invoice->vat_tax,
                                        'other_charges' => $invoice->other_charges,
                                        'total_amount' => $invoice->total_amount,
                                        'paid_amount' => $invoice->paid_amount,
                                        'due_amount' => $invoice->due_amount,
                                        'payment_status' => $invoice->payment_status,
                                        'notes' => $invoice->notes,
                                    ]) }})"
                                    class="text-indigo-600 hover:text-indigo-800 hover:underline cursor-pointer">
                                {{ $invoice->invoice_no }}
                            </button>
                        </td>

                        <td class="text-left font-semibold text-slate-800">
                            {{ $invoice->provider->name ?? '-' }}
                        </td>

                        <td class="text-center font-medium text-slate-700">
                            {{ $invoice->billing_month ? $invoice->billing_month->format('M Y') : '-' }}
                        </td>

                        <td class="text-center font-mono text-slate-600">
                            {{ $invoice->due_date ? $invoice->due_date->format('d M, Y') : '-' }}
                        </td>

                        <td class="text-right font-mono font-bold text-slate-900">
                            @currency($invoice->total_amount)
                        </td>

                        <td class="text-right font-mono font-semibold text-emerald-700">
                            @currency($invoice->paid_amount)
                        </td>

                        <td class="text-right font-mono font-bold {{ $invoice->due_amount > 0 ? 'text-amber-700' : 'text-emerald-700' }}">
                            @currency($invoice->due_amount)
                        </td>

                        <td class="text-center">
                            @if($invoice->payment_status === 'PAID')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                {{ __('Paid') }}
                            </span>
                            @elseif($invoice->payment_status === 'PARTIAL')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                {{ __('Partial') }}
                            </span>
                            @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                {{ __('Unpaid') }}
                            </span>
                            @endif
                        </td>

                        <td class="text-center">
                            <button type="button"
                                    @click="toggleMenu({{ json_encode([
                                        'id' => $invoice->id,
                                        'invoice_no' => $invoice->invoice_no,
                                        'provider_id' => $invoice->provider_id,
                                        'provider_name' => $invoice->provider->name ?? '-',
                                        'carrier_type' => $invoice->provider->carrier_type ?? 'Upstream Carrier',
                                        'billing_month' => $invoice->billing_month ? $invoice->billing_month->format('F Y') : '-',
                                        'due_date' => $invoice->due_date ? $invoice->due_date->format('d M, Y') : '-',
                                        'bandwidth_cost' => $invoice->bandwidth_cost,
                                        'transmission_cost' => $invoice->transmission_cost,
                                        'vat_tax' => $invoice->vat_tax,
                                        'other_charges' => $invoice->other_charges,
                                        'total_amount' => $invoice->total_amount,
                                        'paid_amount' => $invoice->paid_amount,
                                        'due_amount' => $invoice->due_amount,
                                        'payment_status' => $invoice->payment_status,
                                        'notes' => $invoice->notes,
                                    ]) }}, $event)"
                                    title="{{ __('Actions') }}"
                                    class="w-7 h-7 rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-600 flex items-center justify-center mx-auto transition cursor-pointer">
                                <i class="fas fa-ellipsis-v text-[10px]"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center py-8 text-slate-500">
                            <div class="flex flex-col items-center justify-center gap-1.5">
                                <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-400">
                                    <i class="fas fa-file-invoice-dollar text-base"></i>
                                </div>
                                <span class="text-xs font-semibold text-slate-700">{{ __('No Carrier Invoices Found') }}</span>
                                <p class="text-[11px] text-slate-400 max-w-sm whitespace-normal">{{ __('No carrier invoices match your search filters or no bills have been recorded yet.') }}</p>
                                <button type="button"
                                        @click="openInvoiceModal()"
                                        class="mt-2 h-[34px] px-3.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                                    <i class="fas fa-plus text-xs"></i>
                                    <span>{{ __('Record Bill') }}</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Native Laravel Pagination -->
        @if($invoices->hasPages())
        <div class="px-3 py-2 border-t border-slate-200 bg-slate-50/50 flex items-center justify-between">
            <div class="text-[11px] text-slate-500">
                {{ __('Showing :first to :last of :total carrier bills', ['first' => $invoices->firstItem(), 'last' => $invoices->lastItem(), 'total' => $invoices->total()]) }}
            </div>
            <div>
                {{ $invoices->links() }}
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
                @click="viewInvoiceDetails(activeMenu)"
                class="w-full text-left px-3 py-1.5 hover:bg-slate-50 flex items-center gap-2 text-slate-700 transition cursor-pointer">
            <i class="fas fa-circle-info w-4 text-indigo-500 text-[11px]"></i>
            <span>{{ __('View Bill Details') }}</span>
        </button>

        <button type="button"
                @click="openPaymentModal(activeMenu)"
                class="w-full text-left px-3 py-1.5 hover:bg-slate-50 flex items-center gap-2 text-slate-700 transition cursor-pointer">
            <i class="fas fa-money-bill-transfer w-4 text-emerald-600 text-[11px]"></i>
            <span>{{ __('Disburse Payment') }}</span>
        </button>

        <div class="border-t border-slate-100 my-0.5"></div>

        <button type="button"
                @click="deleteInvoice(activeMenu)"
                class="w-full text-left px-3 py-1.5 hover:bg-rose-50 flex items-center gap-2 text-rose-600 transition cursor-pointer">
            <i class="fas fa-trash-can w-4 text-rose-400 text-[11px]"></i>
            <span>{{ __('Delete Bill') }}</span>
        </button>
    </div>

    <!-- Modal 1: Record Carrier Bill Modal -->
    <div x-show="showInvoiceModal"
         x-cloak
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50">
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-xl overflow-hidden"
             @click.away="showInvoiceModal = false">

            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <div>
                        <h2 class="text-xs font-semibold text-slate-800">{{ __('Record Upstream Carrier Bill') }}</h2>
                        <p class="text-[10.5px] text-slate-500 font-normal">{{ __('Enter carrier billed amount, transmission cost, VAT and payment terms.') }}</p>
                    </div>
                </div>
                <button type="button" @click="showInvoiceModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-xmark text-xs"></i>
                </button>
            </div>

            <form action="{{ route('tenant.upstream.invoice.store') }}" method="POST" class="p-4 space-y-3 max-h-[78vh] overflow-y-auto">
                @csrf
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">
                        {{ __('Carrier Provider') }} <span class="text-rose-500">*</span>
                    </label>
                    <select name="provider_id"
                            x-model="invoiceData.provider_id"
                            @change="onProviderChange()"
                            required
                            class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="">{{ __('Select Carrier') }}</option>
                        @foreach($providers as $p)
                        <option value="{{ $p->id }}">{{ $p->name }} ({{ __($p->carrier_type ?: 'Upstream Carrier') }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            {{ __('Invoice / Bill No') }} <span class="text-rose-500">*</span>
                        </label>
                        <input type="text"
                               name="invoice_no"
                               x-model="invoiceData.invoice_no"
                               required
                               placeholder="INV-2026-001"
                               class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>

                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            {{ __('Billing Month') }} <span class="text-rose-500">*</span>
                        </label>
                        <input type="date"
                               name="billing_month"
                               x-model="invoiceData.billing_month"
                               required
                               class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-2.5">
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            {{ __('Bandwidth Cost') }} ({{ $currencySymbol }})
                        </label>
                        <input type="number"
                               step="0.01"
                               min="0"
                               name="bandwidth_cost"
                               x-model.number="invoiceData.bandwidth_cost"
                               placeholder="0.00"
                               class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>

                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            {{ __('Transmission / NTTN') }} ({{ $currencySymbol }})
                        </label>
                        <input type="number"
                               step="0.01"
                               min="0"
                               name="transmission_cost"
                               x-model.number="invoiceData.transmission_cost"
                               placeholder="0.00"
                               class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>

                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            {{ __('Govt VAT / Tax') }} ({{ $currencySymbol }})
                        </label>
                        <input type="number"
                               step="0.01"
                               min="0"
                               name="vat_tax"
                               x-model.number="invoiceData.vat_tax"
                               placeholder="0.00"
                               class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>
                </div>

                <!-- Live Calculated Total Banner -->
                <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block">{{ __('Total Calculated Bill') }}</span>
                        <span class="text-xs text-slate-500 font-normal">{{ __('Sum of transit bandwidth, transmission, and VAT charges') }}</span>
                    </div>
                    <span class="text-base font-mono font-bold text-indigo-700"
                          x-text="`{{ $currencySymbol }} ${(Number(invoiceData.bandwidth_cost || 0) + Number(invoiceData.transmission_cost || 0) + Number(invoiceData.vat_tax || 0)).toFixed(2)}`">
                    </span>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">{{ __('Due Date') }}</label>
                        <input type="date"
                               name="due_date"
                               class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>

                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">{{ __('Bill Notes / Reference') }}</label>
                        <input type="text"
                               name="notes"
                               placeholder="{{ __('e.g. NTTN Link fiber ID, monthly cycle') }}"
                               class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>
                </div>

                <div class="pt-2 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button"
                            @click="showInvoiceModal = false"
                            class="h-[34px] px-3.5 border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs rounded-lg transition cursor-pointer">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit"
                            class="h-[34px] px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-xs rounded-lg shadow-xs transition cursor-pointer">
                        {{ __('Save Bill') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 2: View Bill Details Modal -->
    <div x-show="showDetailsModal"
         x-cloak
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50">
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden"
             @click.away="showDetailsModal = false">

            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-file-lines"></i>
                    </div>
                    <div>
                        <h2 class="text-xs font-semibold text-slate-800" x-text="`${i18n.carrierBill}: ${detailsData?.invoice_no || ''}`"></h2>
                        <p class="text-[10.5px] text-slate-500 font-normal" x-text="`${i18n.carrier}: ${detailsData?.provider_name || '-'} (${carrierTypeLabel(detailsData?.carrier_type)})`"></p>
                    </div>
                </div>
                <button type="button" @click="showDetailsModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-xmark text-xs"></i>
                </button>
            </div>

            <div class="p-4 space-y-3.5 text-xs text-slate-700 max-h-[75vh] overflow-y-auto">
                <div class="grid grid-cols-3 gap-2">
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200">
                        <span class="text-[9px] uppercase font-bold text-slate-400 block">{{ __('Total Billed') }}</span>
                        <span class="text-sm font-mono font-bold text-slate-900 block mt-0.5" x-text="`{{ $currencySymbol }} ${Number(detailsData?.total_amount || 0).toFixed(2)}`"></span>
                    </div>
                    <div class="p-2.5 bg-emerald-50/50 rounded-lg border border-emerald-100">
                        <span class="text-[9px] uppercase font-bold text-emerald-600 block">{{ __('Total Settled') }}</span>
                        <span class="text-sm font-mono font-bold text-emerald-700 block mt-0.5" x-text="`{{ $currencySymbol }} ${Number(detailsData?.paid_amount || 0).toFixed(2)}`"></span>
                    </div>
                    <div class="p-2.5 bg-rose-50/50 rounded-lg border border-rose-100">
                        <span class="text-[9px] uppercase font-bold text-rose-600 block">{{ __('Due Balance') }}</span>
                        <span class="text-sm font-mono font-bold text-rose-700 block mt-0.5" x-text="`{{ $currencySymbol }} ${Number(detailsData?.due_amount || 0).toFixed(2)}`"></span>
                    </div>
                </div>

                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200/80 space-y-2">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block">{{ __('Cost Breakdown') }}</span>
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-600">{{ __('Bandwidth Cost') }}:</span>
                            <span class="font-mono font-semibold text-slate-800" x-text="`{{ $currencySymbol }} ${Number(detailsData?.bandwidth_cost || 0).toFixed(2)}`"></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-600">{{ __('Transmission / NTTN') }}:</span>
                            <span class="font-mono font-semibold text-slate-800" x-text="`{{ $currencySymbol }} ${Number(detailsData?.transmission_cost || 0).toFixed(2)}`"></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-600">{{ __('Govt VAT / Tax') }}:</span>
                            <span class="font-mono font-semibold text-slate-800" x-text="`{{ $currencySymbol }} ${Number(detailsData?.vat_tax || 0).toFixed(2)}`"></span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2 text-[11px] p-2.5 bg-slate-50 rounded-lg border border-slate-200">
                    <div>
                        <span class="text-slate-400 block font-medium">{{ __('Billing Month') }}:</span>
                        <span class="font-semibold text-slate-800" x-text="detailsData?.billing_month || '-'"></span>
                    </div>
                    <div>
                        <span class="text-slate-400 block font-medium">{{ __('Due Date') }}:</span>
                        <span class="font-semibold text-slate-800" x-text="detailsData?.due_date || '-'"></span>
                    </div>
                </div>

                <template x-if="detailsData?.notes">
                    <div class="p-2.5 bg-amber-50/50 rounded-lg border border-amber-200 text-[11px] text-amber-900">
                        <span class="font-bold block mb-0.5">{{ __('Remarks') }}:</span>
                        <span x-text="detailsData?.notes"></span>
                    </div>
                </template>

                <div class="pt-2 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button"
                            @click="showDetailsModal = false"
                            class="h-[34px] px-3.5 border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs rounded-lg transition cursor-pointer">
                        {{ __('Close') }}
                    </button>
                    <button type="button"
                            x-show="detailsData?.due_amount > 0"
                            @click="showDetailsModal = false; openPaymentModal(detailsData)"
                            class="h-[34px] px-3.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                        <i class="fas fa-money-bill-transfer text-xs"></i>
                        <span>{{ __('Disburse Payment') }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal 3: Disburse Payment Modal -->
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
                <input type="hidden" name="invoice_id" x-model="paymentData.invoice_id">

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

</div>
@endsection

@push('scripts')
<script>
    function upstreamInvoiceManager() {
        return {
            activeMenu: null,
            menuPos: { top: '0px', bottom: 'auto', right: '0px', left: 'auto' },
            showInvoiceModal: false,
            showDetailsModal: false,
            showPaymentModal: false,
            providersList: @json($providers),
            detailsData: {},
            i18n: {
                carrierBill: @js(__('Carrier Bill')),
                carrier: @js(__('Carrier Provider')),
                deleteTitle: @js(__('Delete Carrier Bill?')),
                deleteConfirmPrefix: @js(__('Are you sure you want to delete invoice')),
                deleteConfirmBtn: @js(__('Yes, Delete Bill')),
                cancelBtn: @js(__('Cancel')),
                deletedTitle: @js(__('Deleted!')),
                errorTitle: @js(__('Error')),
                deleteFailed: @js(__('Failed to delete invoice.')),
                carrierTypes: {
                    'Upstream Carrier': @js(__('Upstream Carrier')),
                    'ITC Carrier': @js(__('ITC Carrier')),
                    'NTTN Transmission': @js(__('NTTN Transmission')),
                    'BDIX Peering': @js(__('BDIX Peering')),
                    'CDN / Cache Partner': @js(__('CDN / Cache Partner')),
                },
            },

            invoiceData: {
                provider_id: '',
                invoice_no: '',
                billing_month: '{{ date('Y-m-01') }}',
                bandwidth_cost: 0,
                transmission_cost: 0,
                vat_tax: 0
            },

            paymentData: {
                provider_id: '',
                invoice_id: '',
                amount: 0,
                payment_method: 'BANK_TRANSFER',
                bank_name: '',
                transaction_ref: '',
                paid_at: '{{ date('Y-m-d\TH:i') }}'
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

            onProviderChange() {
                const selected = this.providersList.find(p => p.id == this.invoiceData.provider_id);
                if (selected && selected.links && selected.links.length > 0) {
                    let totalBw = 0;
                    let totalTrans = 0;
                    selected.links.forEach(l => {
                        const bw = (Number(l.global_mbps || 0) * Number(l.global_rate || 0)) +
                                   (Number(l.bdix_mbps || 0) * Number(l.bdix_rate || 0)) +
                                   (Number(l.cdn_mbps || 0) * Number(l.cdn_rate || 0)) +
                                   (Number(l.ggc_mbps || 0) * Number(l.ggc_rate || 0)) +
                                   (Number(l.fna_mbps || 0) * Number(l.fna_rate || 0)) +
                                   (Number(l.other_mbps || 0) * Number(l.other_rate || 0));
                        totalBw += bw;
                        totalTrans += Number(l.monthly_transmission_cost || 0);
                    });
                    this.invoiceData.bandwidth_cost = totalBw.toFixed(2);
                    this.invoiceData.transmission_cost = totalTrans.toFixed(2);
                }
            },

            openInvoiceModal() {
                this.activeMenu = null;
                this.invoiceData = {
                    provider_id: '',
                    invoice_no: `INV-${Date.now().toString().slice(-6)}`,
                    billing_month: '{{ date('Y-m-01') }}',
                    bandwidth_cost: 0,
                    transmission_cost: 0,
                    vat_tax: 0
                };
                this.showInvoiceModal = true;
            },

            openPaymentModalDirect() {
                this.activeMenu = null;
                this.paymentData = {
                    provider_id: '',
                    invoice_id: '',
                    amount: '',
                    payment_method: 'BANK_TRANSFER',
                    bank_name: '',
                    transaction_ref: '',
                    paid_at: '{{ date('Y-m-d\TH:i') }}'
                };
                this.showPaymentModal = true;
            },

            viewInvoiceDetails(item) {
                this.activeMenu = null;
                this.detailsData = item;
                this.showDetailsModal = true;
            },

            openPaymentModal(item) {
                this.activeMenu = null;
                this.paymentData = {
                    provider_id: item.provider_id || '',
                    invoice_id: item.id || '',
                    amount: item.due_amount > 0 ? item.due_amount : item.total_amount,
                    payment_method: 'BANK_TRANSFER',
                    bank_name: '',
                    transaction_ref: '',
                    paid_at: '{{ date('Y-m-d\TH:i') }}'
                };
                this.showPaymentModal = true;
            },

            async deleteInvoice(item) {
                this.activeMenu = null;
                const result = await Swal.fire({
                    title: this.i18n.deleteTitle,
                    text: `${this.i18n.deleteConfirmPrefix} '${item.invoice_no}'?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e11d48',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: this.i18n.deleteConfirmBtn,
                    cancelButtonText: this.i18n.cancelBtn
                });

                if (result.isConfirmed) {
                    try {
                        const response = await fetch(`/admin/upstream/invoices/${item.id}`, {
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
