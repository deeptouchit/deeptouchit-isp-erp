@extends('tenant.layouts.app')

@section('title', 'Carrier Bills & Invoices - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
    {{-- Page specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="upstreamInvoiceManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">

    <!-- 1. Top Header Bar (Strictly Icon + Title + Action Button ONLY - AGENTS.md Rule 2.A) -->
    <div class="flex items-center justify-between bg-white px-3.5 py-2.5 rounded-xl border border-slate-200/90 shadow-2xs">
        <div class="flex items-center gap-2.5">
            <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center text-xs shadow-2xs flex-shrink-0">
                <i class="fas fa-file-invoice-dollar"></i>
            </div>
            <div class="flex items-center gap-2">
                <h1 class="text-xs font-bold text-slate-800 tracking-tight">Carrier Bills &amp; Invoices</h1>
                <span class="text-[10px] font-bold text-slate-500 bg-slate-100 border border-slate-200/80 px-2 py-0.5 rounded-full font-mono">{{ number_format($invoices->total()) }}</span>
            </div>
        </div>

        <div class="flex items-center gap-1.5 flex-wrap">
            <button type="button" 
                    @click="openInvoiceModal()"
                    class="h-[34px] px-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-plus text-xs"></i>
                <span>Record Bill</span>
            </button>

            <button type="button" 
                    @click="openPaymentModalDirect()"
                    class="h-[34px] px-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-money-bill-transfer text-xs"></i>
                <span>Disburse Payment</span>
            </button>

            <a href="{{ route('tenant.upstream.index') }}" 
               class="h-[34px] px-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200/80 rounded-lg text-xs font-medium transition flex items-center gap-1 cursor-pointer"
               title="Carriers & Links">
                <i class="fas fa-tower-broadcast text-[11px] text-slate-500"></i>
                <span>Carriers</span>
            </a>

            <a href="{{ route('tenant.upstream.payments') }}" 
               class="h-[34px] px-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200/80 rounded-lg text-xs font-medium transition flex items-center gap-1 cursor-pointer"
               title="Payment Vouchers">
                <i class="fas fa-receipt text-[11px] text-slate-500"></i>
                <span>Vouchers</span>
            </a>
        </div>
    </div>

    <!-- 2. KPI Summary Strip (Strictly 6 Cards - AGENTS.md Rule 2.B) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        <!-- Card 1: Total Invoices -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Total Bills</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-slate-900 truncate">{{ number_format($totalInvoicesCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-slate-50 text-slate-600 border-slate-200 flex items-center justify-center">
                <i class="fas fa-folder-open"></i>
            </div>
        </div>

        <!-- Card 2: Total Billed Amount -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Total Billed</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-indigo-700 truncate">@currency($totalBilledAmount)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-indigo-50 text-indigo-600 border-indigo-100 flex items-center justify-center">
                <i class="fas fa-receipt"></i>
            </div>
        </div>

        <!-- Card 3: Total Paid Amount -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Total Settled</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-emerald-700 truncate">@currency($totalPaidAmount)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-emerald-50 text-emerald-600 border-emerald-100 flex items-center justify-center">
                <i class="fas fa-circle-check"></i>
            </div>
        </div>

        <!-- Card 4: Total Due Balance -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Total Due</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-rose-700 truncate">@currency($totalDueBalance)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-rose-50 text-rose-600 border-rose-100 flex items-center justify-center">
                <i class="fas fa-clock"></i>
            </div>
        </div>

        <!-- Card 5: Settled Bills Count -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Fully Paid</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-emerald-700 truncate">{{ number_format($paidInvoicesCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-emerald-50 text-emerald-600 border-emerald-100 flex items-center justify-center">
                <i class="fas fa-stamp"></i>
            </div>
        </div>

        <!-- Card 6: Pending / Unpaid Bills Count -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Pending / Due</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-amber-700 truncate">{{ number_format($unpaidInvoicesCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-amber-50 text-amber-600 border-amber-100 flex items-center justify-center">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
        </div>
    </div>

    <!-- 3. Search & Multi-Filter Toolbar (Customer-Standard 2-Tier Filter System - AGENTS.md Rule 2.C) -->
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
                           placeholder="Search Bill No, Carrier Provider, Notes..." 
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

                <!-- Payment Status Primary Filter -->
                <div class="w-full md:w-36 flex-shrink-0">
                    <select name="status" 
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="">All Statuses</option>
                        <option value="PAID" {{ request('status') === 'PAID' ? 'selected' : '' }}>Paid</option>
                        <option value="PARTIAL" {{ request('status') === 'PARTIAL' ? 'selected' : '' }}>Partial</option>
                        <option value="UNPAID" {{ request('status') === 'UNPAID' ? 'selected' : '' }}>Unpaid</option>
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

                    <a href="{{ route('tenant.upstream.invoices') }}" 
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
                
                <!-- Billing Month Filter -->
                <div class="w-full sm:w-44 flex-shrink-0">
                    <label class="block text-[9px] font-bold uppercase tracking-wider text-slate-400 mb-0.5">Billing Month</label>
                    <input type="month" 
                           name="billing_month" 
                           value="{{ request('billing_month') }}"
                           class="w-full h-[34px] px-2.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                </div>

                <!-- Outstanding Due Balance Filter -->
                <div class="w-full sm:w-48 flex-shrink-0">
                    <label class="block text-[9px] font-bold uppercase tracking-wider text-slate-400 mb-0.5">Balance State</label>
                    <select name="due_only" 
                            class="w-full h-[34px] px-2.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="">All Carrier Bills</option>
                        <option value="1" {{ request('due_only') == '1' ? 'selected' : '' }}>Outstanding Due Only (&gt; 0)</option>
                    </select>
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
                        <th class="text-center font-semibold">Invoice No</th>
                        <th class="text-center font-semibold">Carrier Provider</th>
                        <th class="text-center font-semibold">Billing Month</th>
                        <th class="text-center font-semibold">Due Date</th>
                        <th class="text-center font-semibold">Total Amount</th>
                        <th class="text-center font-semibold">Paid Amount</th>
                        <th class="text-center font-semibold">Due Balance</th>
                        <th class="text-center font-semibold">Status</th>
                        <th class="w-12 text-center font-semibold no-sort">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $index => $invoice)
                    <tr>
                        <!-- 1. Serial No -->
                        <td class="text-center font-mono text-slate-500">
                            {{ $invoices->firstItem() + $index }}
                        </td>

                        <!-- 2. Invoice No (Click to View) -->
                        <td class="text-center font-mono font-bold">
                            <button type="button" 
                                    @click="viewInvoiceDetails({{ json_encode([
                                        'id' => $invoice->id,
                                        'invoice_no' => $invoice->invoice_no,
                                        'provider_id' => $invoice->provider_id,
                                        'provider_name' => $invoice->provider->name ?? '-',
                                        'carrier_type' => $invoice->provider->carrier_type ?? 'IIG',
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

                        <!-- 3. Carrier Provider -->
                        <td class="text-left font-semibold text-slate-800">
                            <div class="flex items-center gap-1.5 flex-nowrap whitespace-nowrap">
                                <span>{{ $invoice->provider->name ?? '-' }}</span>
                                @if(isset($invoice->provider->carrier_type))
                                <span class="px-1.5 py-0.5 rounded text-[9.5px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                    {{ $invoice->provider->carrier_type }}
                                </span>
                                @endif
                            </div>
                        </td>

                        <!-- 4. Billing Month -->
                        <td class="text-center font-medium text-slate-700">
                            {{ $invoice->billing_month ? $invoice->billing_month->format('M Y') : '-' }}
                        </td>

                        <!-- 5. Due Date -->
                        <td class="text-center font-mono text-slate-600">
                            {{ $invoice->due_date ? $invoice->due_date->format('d M, Y') : '-' }}
                        </td>

                        <!-- 6. Total Amount -->
                        <td class="text-right font-mono font-bold text-slate-900">
                            @currency($invoice->total_amount)
                        </td>

                        <!-- 7. Paid Amount -->
                        <td class="text-right font-mono font-semibold text-emerald-700">
                            @currency($invoice->paid_amount)
                        </td>

                        <!-- 8. Due Balance -->
                        <td class="text-right font-mono font-bold {{ $invoice->due_amount > 0 ? 'text-amber-700' : 'text-emerald-700' }}">
                            @currency($invoice->due_amount)
                        </td>

                        <!-- 9. Payment Status -->
                        <td class="text-center">
                            @if($invoice->payment_status === 'PAID')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                Paid
                            </span>
                            @elseif($invoice->payment_status === 'PARTIAL')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                Partial
                            </span>
                            @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                Unpaid
                            </span>
                            @endif
                        </td>

                        <!-- 10. Action (3-Dot Floating Trigger) -->
                        <td class="text-center">
                            <button type="button" 
                                    @click="toggleMenu({{ json_encode([
                                        'id' => $invoice->id,
                                        'invoice_no' => $invoice->invoice_no,
                                        'provider_id' => $invoice->provider_id,
                                        'provider_name' => $invoice->provider->name ?? '-',
                                        'carrier_type' => $invoice->provider->carrier_type ?? 'IIG',
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
                                    class="w-7 h-7 rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-600 flex items-center justify-center transition cursor-pointer">
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
                                <span class="text-xs font-semibold text-slate-700">No Carrier Invoices Found</span>
                                <p class="text-[11px] text-slate-400 max-w-sm">No carrier invoices match your search filters or no bills have been recorded yet.</p>
                                <button type="button" 
                                        @click="openInvoiceModal()"
                                        class="mt-2 h-[34px] px-3.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                                    <i class="fas fa-plus text-xs"></i>
                                    <span>Record First Bill</span>
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
                Showing <span class="font-semibold text-slate-700">{{ $invoices->firstItem() }}</span> to <span class="font-semibold text-slate-700">{{ $invoices->lastItem() }}</span> of <span class="font-semibold text-slate-700">{{ $invoices->total() }}</span> bills
            </div>
            <div>
                {{ $invoices->links() }}
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
                @click="viewInvoiceDetails(activeMenu)" 
                class="w-full text-left px-3 py-1.5 hover:bg-slate-50 flex items-center gap-2 text-slate-700 transition cursor-pointer">
            <i class="fas fa-circle-info w-4 text-indigo-500 text-[11px]"></i>
            <span>View Bill Details</span>
        </button>

        <button type="button" 
                @click="openPaymentModal(activeMenu)" 
                class="w-full text-left px-3 py-1.5 hover:bg-slate-50 flex items-center gap-2 text-slate-700 transition cursor-pointer">
            <i class="fas fa-money-bill-transfer w-4 text-emerald-600 text-[11px]"></i>
            <span>Disburse Payment</span>
        </button>

        <div class="border-t border-slate-100 my-0.5"></div>

        <button type="button" 
                @click="deleteInvoice(activeMenu)" 
                class="w-full text-left px-3 py-1.5 hover:bg-rose-50 flex items-center gap-2 text-rose-600 transition cursor-pointer">
            <i class="fas fa-trash-can w-4 text-rose-400 text-[11px]"></i>
            <span>Delete Bill</span>
        </button>
    </div>

    <!-- Modal 1: Record Carrier Bill Modal (Rule 3) -->
    <div x-show="showInvoiceModal" 
         x-cloak 
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50">
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-xl overflow-hidden animate-in fade-in zoom-in duration-150"
             @click.away="showInvoiceModal = false">
            
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <div>
                        <h2 class="text-xs font-semibold text-slate-800">Record Upstream Carrier Bill</h2>
                        <p class="text-[10.5px] text-slate-500 font-normal">Enter carrier billed amount, transmission cost, VAT and payment terms.</p>
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
                        Upstream Carrier Provider <span class="text-rose-500">*</span>
                    </label>
                    <select name="provider_id" 
                            x-model="invoiceData.provider_id" 
                            @change="onProviderChange()" 
                            required 
                            class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="">Select Carrier</option>
                        @foreach($providers as $p)
                        <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->carrier_type }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            Invoice / Bill No <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" 
                               name="invoice_no" 
                               x-model="invoiceData.invoice_no" 
                               required 
                               placeholder="e.g. INV-2026-001" 
                               class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>

                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            Billing Month <span class="text-rose-500">*</span>
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
                            Bandwidth Cost ({{ $currencySymbol }})
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
                            Transmission / NTTN ({{ $currencySymbol }})
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
                            Govt VAT / Tax ({{ $currencySymbol }})
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
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block">Total Calculated Bill</span>
                        <span class="text-xs text-slate-500 font-normal">Sum of transit bandwidth, transmission, and VAT charges</span>
                    </div>
                    <span class="text-base font-mono font-bold text-indigo-700"
                          x-text="`{{ $currencySymbol }} ${(Number(invoiceData.bandwidth_cost || 0) + Number(invoiceData.transmission_cost || 0) + Number(invoiceData.vat_tax || 0)).toFixed(2)}`">
                    </span>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">Payment Due Date</label>
                        <input type="date" 
                               name="due_date" 
                               class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>

                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">Bill Notes / Reference</label>
                        <input type="text" 
                               name="notes" 
                               placeholder="e.g. NTTN Link fiber ID, monthly cycle" 
                               class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>
                </div>

                <div class="pt-2 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" 
                            @click="showInvoiceModal = false" 
                            class="h-[34px] px-3.5 border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs rounded-lg transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="h-[34px] px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-xs rounded-lg shadow-xs transition cursor-pointer">
                        Save Bill
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 2: View Bill Details Modal (Rule 3) -->
    <div x-show="showDetailsModal" 
         x-cloak 
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50">
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden animate-in fade-in zoom-in duration-150"
             @click.away="showDetailsModal = false">
            
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-file-lines"></i>
                    </div>
                    <div>
                        <h2 class="text-xs font-semibold text-slate-800" x-text="`Carrier Bill: ${detailsData?.invoice_no || ''}`"></h2>
                        <p class="text-[10.5px] text-slate-500 font-normal" x-text="`Carrier: ${detailsData?.provider_name || 'N/A'} (${detailsData?.carrier_type || 'IIG'})`"></p>
                    </div>
                </div>
                <button type="button" @click="showDetailsModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-xmark text-xs"></i>
                </button>
            </div>

            <div class="p-4 space-y-3.5 text-xs text-slate-700 max-h-[75vh] overflow-y-auto">
                <!-- Summary Metrics 3 Cards -->
                <div class="grid grid-cols-3 gap-2">
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200">
                        <span class="text-[9px] uppercase font-bold text-slate-400 block">Billed Total</span>
                        <span class="text-sm font-mono font-bold text-slate-900 block mt-0.5" x-text="`{{ $currencySymbol }} ${Number(detailsData?.total_amount || 0).toFixed(2)}`"></span>
                    </div>
                    <div class="p-2.5 bg-emerald-50/50 rounded-lg border border-emerald-100">
                        <span class="text-[9px] uppercase font-bold text-emerald-600 block">Settled</span>
                        <span class="text-sm font-mono font-bold text-emerald-700 block mt-0.5" x-text="`{{ $currencySymbol }} ${Number(detailsData?.paid_amount || 0).toFixed(2)}`"></span>
                    </div>
                    <div class="p-2.5 bg-rose-50/50 rounded-lg border border-rose-100">
                        <span class="text-[9px] uppercase font-bold text-rose-600 block">Due Balance</span>
                        <span class="text-sm font-mono font-bold text-rose-700 block mt-0.5" x-text="`{{ $currencySymbol }} ${Number(detailsData?.due_amount || 0).toFixed(2)}`"></span>
                    </div>
                </div>

                <!-- Cost Breakdown Grid -->
                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200/80 space-y-2">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block">Accounting Item Breakdown</span>
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-600">Bandwidth Internet Transit:</span>
                            <span class="font-mono font-semibold text-slate-800" x-text="`{{ $currencySymbol }} ${Number(detailsData?.bandwidth_cost || 0).toFixed(2)}`"></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-600">NTTN / Transmission Fiber:</span>
                            <span class="font-mono font-semibold text-slate-800" x-text="`{{ $currencySymbol }} ${Number(detailsData?.transmission_cost || 0).toFixed(2)}`"></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-600">Government VAT &amp; Taxes:</span>
                            <span class="font-mono font-semibold text-slate-800" x-text="`{{ $currencySymbol }} ${Number(detailsData?.vat_tax || 0).toFixed(2)}`"></span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2 text-[11px] p-2.5 bg-slate-50 rounded-lg border border-slate-200">
                    <div>
                        <span class="text-slate-400 block font-medium">Billing Period:</span>
                        <span class="font-semibold text-slate-800" x-text="detailsData?.billing_month || 'N/A'"></span>
                    </div>
                    <div>
                        <span class="text-slate-400 block font-medium">Due Date:</span>
                        <span class="font-semibold text-slate-800" x-text="detailsData?.due_date || 'N/A'"></span>
                    </div>
                </div>

                <template x-if="detailsData?.notes">
                    <div class="p-2.5 bg-amber-50/50 rounded-lg border border-amber-200 text-[11px] text-amber-900">
                        <span class="font-bold block mb-0.5">Notes:</span>
                        <span x-text="detailsData?.notes"></span>
                    </div>
                </template>

                <div class="pt-2 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" 
                            @click="showDetailsModal = false" 
                            class="h-[34px] px-3.5 border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs rounded-lg transition cursor-pointer">
                        Close
                    </button>
                    <button type="button" 
                            x-show="detailsData?.due_amount > 0" 
                            @click="showDetailsModal = false; openPaymentModal(detailsData)" 
                            class="h-[34px] px-3.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                        <i class="fas fa-money-bill-transfer text-xs"></i>
                        <span>Disburse Payment</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal 3: Disburse Payment Modal (Rule 3) -->
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
                <input type="hidden" name="invoice_id" x-model="paymentData.invoice_id">

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
                            <option value="CASH">Cash</option>
                            <option value="OTHER">Other</option>
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
                    title: 'Delete Carrier Bill?',
                    text: `Are you sure you want to delete invoice '${item.invoice_no}'?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e11d48',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Yes, Delete Bill',
                    cancelButtonText: 'Cancel'
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
                        Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to delete invoice.' });
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
