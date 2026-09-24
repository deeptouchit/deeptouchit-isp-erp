@extends('reseller.layouts.app')

@section('title', 'Customer Invoices - ' . ($tenant->company_name ?? $tenant->name ?? 'Reseller Portal'))

@push('styles')
    {{-- Page-specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="invoicePageManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">
    
    <!-- 1. Top Header Bar (AGENTS.md Rule 2.A: Icon + Title + Action buttons ONLY) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-sm shadow-2xs flex-shrink-0">
                <i class="fas fa-file-invoice-dollar"></i>
            </div>
            <h1 class="text-sm font-bold text-slate-800 tracking-tight">{{ __('Customer Invoices') }}</h1>
        </div>
        <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
            <!-- Bulk Payments Link -->
            <a href="{{ route('reseller.customers.bulk-payments') }}" class="px-3 py-1.5 bg-purple-50 hover:bg-purple-100 text-purple-700 rounded-lg text-xs font-semibold border border-purple-200 shadow-2xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-layer-group text-purple-600 text-xs"></i>
                <span>{{ __('Bulk Payments') }}</span>
            </a>

            <!-- Print Statement Matrix -->
            <a href="{{ route('reseller.customer-invoices.print-report', request()->query()) }}" target="_blank" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-print text-slate-500 text-xs"></i>
                <span>{{ __('Print Report') }}</span>
            </a>

            <!-- Export CSV -->
            <a href="{{ route('reseller.customer-invoices.export', request()->query()) }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-file-csv text-slate-500 text-xs"></i>
                <span>{{ __('Export CSV') }}</span>
            </a>
        </div>
    </div>

    <!-- 2. KPI Summary Strip (Strictly 6 Cards - AGENTS.md Rule 2.B) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        
        <!-- Card 1: Total Invoices -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Total Invoices') }}</span>
                <span class="text-[13px] font-bold font-mono text-slate-900 leading-tight block truncate">
                    {{ number_format($stats['total_invoices']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-slate-200 bg-slate-50 text-slate-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-file-lines"></i>
            </div>
        </div>

        <!-- Card 2: Total Billed -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider text-cyan-700 block truncate">{{ __('Total Billed') }}</span>
                <span class="text-[13px] font-bold font-mono text-cyan-700 leading-tight block truncate">
                    @currency($stats['total_billed'])
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-cyan-200 bg-cyan-50 text-cyan-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-money-bill-wave"></i>
            </div>
        </div>

        <!-- Card 3: Total Paid -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider text-emerald-700 block truncate">{{ __('Total Paid') }}</span>
                <span class="text-[13px] font-bold font-mono text-emerald-600 leading-tight block truncate">
                    @currency($stats['total_paid'])
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-check-double"></i>
            </div>
        </div>

        <!-- Card 4: Outstanding Due -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider text-rose-700 block truncate">{{ __('Outstanding Due') }}</span>
                <span class="text-[13px] font-bold font-mono text-rose-600 leading-tight block truncate">
                    @currency($stats['total_due'])
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-rose-200 bg-rose-50 text-rose-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-hand-holding-dollar"></i>
            </div>
        </div>

        <!-- Card 5: Paid Invoices Count -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider text-emerald-700 block truncate">{{ __('Paid Invoices') }}</span>
                <span class="text-[13px] font-bold font-mono text-emerald-700 leading-tight block truncate">
                    {{ number_format($stats['paid_invoices']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-circle-check"></i>
            </div>
        </div>

        <!-- Card 6: Unpaid Invoices Count -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider text-amber-700 block truncate">{{ __('Unpaid Invoices') }}</span>
                <span class="text-[13px] font-bold font-mono text-amber-600 leading-tight block truncate">
                    {{ number_format($stats['unpaid_invoices']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-amber-200 bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-clock"></i>
            </div>
        </div>

    </div>

    <!-- 3. Advanced Search & Multi-Filter Toolbar (AGENTS.md Rule 2.C) -->
    <div class="bg-white p-3 rounded-xl border border-slate-200 shadow-xs space-y-2">
        <form method="GET" action="{{ route('reseller.customer-invoices.index') }}" class="space-y-2">
            
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-7 gap-2 items-center">
                
                <!-- Search Box (col-span-2) -->
                <div class="lg:col-span-2 relative">
                    <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" 
                           name="search" 
                           value="{{ $search }}" 
                           placeholder="Search invoice no, customer, username..." 
                           class="w-full pl-8 pr-3 py-1.5 rounded-lg border border-slate-200 bg-slate-50 text-xs focus:bg-white focus:outline-none focus:border-cyan-500 shadow-2xs">
                </div>

                <!-- Date Range Preset Selector -->
                <div>
                    <select name="date_preset" 
                            x-model="datePreset"
                            @change="onDatePresetChange($event)"
                            class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 bg-slate-50 text-xs focus:bg-white focus:outline-none focus:border-cyan-500 shadow-2xs">
                        <option value="all">All Dates</option>
                        <option value="today">Today</option>
                        <option value="yesterday">Yesterday</option>
                        <option value="this_week">This Week</option>
                        <option value="this_month">This Month</option>
                        <option value="last_month">Last Month</option>
                        <option value="custom">Custom Date Range</option>
                    </select>
                </div>

                <!-- Status Filter -->
                <div>
                    <select name="status" onchange="this.form.submit()" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 bg-slate-50 text-xs focus:bg-white focus:outline-none focus:border-cyan-500 shadow-2xs">
                        <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All Statuses</option>
                        <option value="paid" {{ $status === 'paid' ? 'selected' : '' }}>Paid</option>
                        <option value="due" {{ in_array($status, ['due', 'unpaid']) ? 'selected' : '' }}>Due / Unpaid</option>
                        <option value="partial" {{ $status === 'partial' ? 'selected' : '' }}>Partial</option>
                        <option value="overdue" {{ $status === 'overdue' ? 'selected' : '' }}>Overdue</option>
                    </select>
                </div>

                <!-- Billing Month Filter -->
                <div>
                    <select name="month" onchange="this.form.submit()" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 bg-slate-50 text-xs focus:bg-white focus:outline-none focus:border-cyan-500 shadow-2xs">
                        <option value="all" {{ $month === 'all' ? 'selected' : '' }}>All Months</option>
                        @foreach($months as $m)
                            <option value="{{ $m }}" {{ $month === $m ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::parse($m . '-01')->format('F Y') }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Package Filter -->
                <div>
                    <select name="package_id" onchange="this.form.submit()" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 bg-slate-50 text-xs focus:bg-white focus:outline-none focus:border-cyan-500 shadow-2xs">
                        <option value="all" {{ $packageId === 'all' ? 'selected' : '' }}>All Packages</option>
                        @foreach($packages as $pkg)
                            <option value="{{ $pkg->id }}" {{ (string)$packageId === (string)$pkg->id ? 'selected' : '' }}>
                                {{ $pkg->name ?: ($pkg->mikrotik_profile ?: ($pkg->package_name ?: 'Package #' . $pkg->id)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Per Page Selector & Buttons -->
                <div class="flex items-center gap-1.5 flex-shrink-0">
                    <select name="per_page" onchange="this.form.submit()" class="w-20 px-2 py-1.5 rounded-lg border border-slate-200 bg-slate-50 text-xs focus:bg-white focus:outline-none focus:border-cyan-500 shadow-2xs">
                        <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10 / pg</option>
                        <option value="20" {{ $perPage == 20 ? 'selected' : '' }}>20 / pg</option>
                        <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50 / pg</option>
                        <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100 / pg</option>
                    </select>

                    <!-- Strict Filter & Reset Sequence (AGENTS.md Rule 2.C) -->
                    <button type="submit" class="flex-1 bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-1.5 px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer" title="Apply Filters">
                        <i class="fas fa-filter text-[10px]"></i>
                        <span>Filter</span>
                    </button>
                    <a href="{{ route('reseller.customer-invoices.index') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-1.5 px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer" title="Reset Filters">
                        <i class="fas fa-rotate-left text-[10px]"></i>
                        <span>Reset</span>
                    </a>
                </div>

            </div>

            <!-- Custom Date Range Picker (Visible when date_preset === 'custom') -->
            <div x-show="datePreset === 'custom'" 
                 x-transition 
                 class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-2 pt-2 border-t border-slate-100 items-center"
                 style="display: none;">
                <div>
                    <label class="block text-[10px] font-semibold text-slate-600 uppercase mb-0.5">From Date</label>
                    <input type="date" 
                           name="from_date" 
                           value="{{ $fromDate }}" 
                           class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 bg-slate-50 text-xs focus:bg-white focus:border-cyan-500">
                </div>
                <div>
                    <label class="block text-[10px] font-semibold text-slate-600 uppercase mb-0.5">To Date</label>
                    <input type="date" 
                           name="to_date" 
                           value="{{ $toDate }}" 
                           class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 bg-slate-50 text-xs focus:bg-white focus:border-cyan-500">
                </div>
            </div>

        </form>
    </div>

    <!-- 4. Master Compact Table (<table class="saas-table"> Pure CSS System) -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-10 text-center">#</th>
                        <th class="w-28">Invoice No</th>
                        <th>Customer Name</th>
                        <th class="w-24">Month</th>
                        <th>Package</th>
                        <th class="w-28 text-right">Bill Amount</th>
                        <th class="w-28 text-right">Due Amount</th>
                        <th class="w-20 text-center">Status</th>
                        <th class="w-12 text-center no-sort">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $index => $inv)
                        @php
                            $st = strtolower($inv->status ?? 'unpaid');
                            $pkgDisplay = $inv->package?->name ?: ($inv->package?->mikrotik_profile ?: ($inv->customer?->package_display_name ?: ($inv->package_name ?? 'Standard')));
                            $rawInvoiceData = [
                                'id' => $inv->id,
                                'invoice_no' => $inv->invoice_no,
                                'customer_id' => $inv->customer?->customer_id ?? 'N/A',
                                'customer_name' => $inv->customer?->name ?? 'N/A',
                                'customer_username' => $inv->customer?->username ?? 'N/A',
                                'customer_phone' => $inv->customer?->phone ?? 'N/A',
                                'package_name' => $pkgDisplay,
                                'billing_month' => $inv->billing_month ? \Carbon\Carbon::parse($inv->billing_month . '-01')->format('F Y') : 'N/A',
                                'amount' => (float) $inv->amount,
                                'discount' => (float) $inv->discount,
                                'vat_tax' => (float) $inv->vat_tax,
                                'total_payable' => (float) $inv->total_payable,
                                'paid_amount' => (float) $inv->paid_amount,
                                'due_amount' => (float) $inv->due_amount,
                                'status' => $inv->status,
                                'issue_date' => $inv->issue_date ? \Carbon\Carbon::parse($inv->issue_date)->format('d M Y') : 'N/A',
                                'due_date' => $inv->due_date ? \Carbon\Carbon::parse($inv->due_date)->format('d M Y') : 'N/A',
                                'paid_at' => $inv->paid_at ? \Carbon\Carbon::parse($inv->paid_at)->format('d M Y, h:i A') : 'Unpaid',
                                'payment_method' => $inv->payment_method ?? 'N/A',
                                'notes' => $inv->notes,
                            ];
                        @endphp
                        <tr>
                            <!-- 1. Index -->
                            <td class="text-center font-mono text-slate-400">
                                {{ ($invoices->currentPage() - 1) * $invoices->perPage() + $index + 1 }}
                            </td>

                            <!-- 2. Invoice No -->
                            <td class="font-mono font-bold text-cyan-800">
                                <button type="button" 
                                        @click="openDetailModal({{ Js::from($rawInvoiceData) }})" 
                                        class="hover:underline cursor-pointer">
                                    {{ $inv->invoice_no }}
                                </button>
                            </td>

                            <!-- 3. Customer Name (Single clean cell) -->
                            <td class="font-medium text-slate-900">
                                <button type="button" 
                                        @click="openDetailModal({{ Js::from($rawInvoiceData) }})" 
                                        class="hover:underline hover:text-cyan-600 cursor-pointer">
                                    {{ $inv->customer?->name ?? 'N/A' }}
                                </button>
                            </td>

                            <!-- 4. Month -->
                            <td class="font-mono text-slate-600">
                                {{ $inv->billing_month ? \Carbon\Carbon::parse($inv->billing_month . '-01')->format('M Y') : 'N/A' }}
                            </td>

                            <!-- 5. Package Profile -->
                            <td class="font-medium text-slate-800">
                                {{ $pkgDisplay }}
                            </td>

                            <!-- 6. Total Bill -->
                            <td class="text-right font-mono font-bold text-slate-900">
                                @currency($inv->total_payable)
                            </td>

                            <!-- 7. Due Amount -->
                            <td class="text-right font-mono font-bold {{ $inv->due_amount > 0 ? 'text-rose-600' : 'text-slate-400' }}">
                                @currency($inv->due_amount)
                            </td>

                            <!-- 8. Status Badge -->
                            <td class="text-center">
                                @if($st === 'paid')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-semibold border bg-emerald-50 text-emerald-700 border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Paid
                                    </span>
                                @elseif($st === 'partial' || $st === 'partially_paid')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-semibold border bg-amber-50 text-amber-700 border-amber-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        Partial
                                    </span>
                                @elseif($st === 'overdue')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-semibold border bg-red-50 text-red-700 border-red-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                        Overdue
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-semibold border bg-rose-50 text-rose-700 border-rose-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        Unpaid
                                    </span>
                                @endif
                            </td>

                            <!-- 9. Action (3-Dot Floating Trigger) -->
                            <td class="text-center">
                                <button type="button" 
                                        @click="toggleMenu({{ $inv->id }}, $event, {{ Js::from($rawInvoiceData) }})" 
                                        class="w-6 h-6 rounded-md hover:bg-slate-100 text-slate-400 hover:text-slate-700 transition cursor-pointer inline-flex items-center justify-center">
                                    <i class="fas fa-ellipsis-v text-[10px]"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-8 text-center text-slate-400 text-xs italic bg-slate-50/50">
                                <i class="fas fa-file-invoice text-2xl text-slate-300 mb-2 block"></i>
                                No customer invoice records found matching your filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Bar -->
        @if($invoices->hasPages())
            <div class="px-4 py-2 border-t border-slate-200/80 bg-slate-50/50 flex items-center justify-between">
                <span class="text-[11px] text-slate-500 font-mono">
                    Showing {{ $invoices->firstItem() }} to {{ $invoices->lastItem() }} of {{ $invoices->total() }} entries
                </span>
                <div>
                    {{ $invoices->links() }}
                </div>
            </div>
        @endif
    </div>

    <!-- 5. FLOATING 3-DOT ACTION MENU (AGENTS.md Rule 2.E) -->
    <div x-show="activeMenu !== null" 
         x-cloak 
         @click.away="activeMenu = null"
         :style="menuPos ? `position: fixed; top: ${menuPos.top}; bottom: ${menuPos.bottom}; right: ${menuPos.right}; left: ${menuPos.left};` : ''"
         class="fixed z-50 w-48 bg-white rounded-xl shadow-xl border border-slate-200/90 py-1.5 text-xs text-slate-700 animate-in fade-in duration-100 divide-y divide-slate-100"
         style="display: none;">
        
        <div class="py-1">
            <button type="button" 
                    @click="openDetailModal(activeInvoiceData); activeMenu = null" 
                    class="w-full text-left px-3 py-1.5 hover:bg-slate-50 flex items-center gap-2 text-slate-700 font-medium transition cursor-pointer">
                <i class="fas fa-eye text-cyan-600 w-4 text-center"></i>
                <span>View Details</span>
            </button>
            <a :href="`/reseller/customer-invoices/${activeMenu?.id}/print`" 
               target="_blank"
               class="w-full text-left px-3 py-1.5 hover:bg-slate-50 flex items-center gap-2 text-slate-700 font-medium transition cursor-pointer">
                <i class="fas fa-print text-slate-500 w-4 text-center"></i>
                <span>Print Invoice</span>
            </a>
        </div>

        <template x-if="activeInvoiceData?.status !== 'paid'">
            <div class="py-1">
                <button type="button" 
                        @click="openPaymentModal(activeInvoiceData); activeMenu = null" 
                        class="w-full text-left px-3 py-1.5 hover:bg-emerald-50 text-emerald-700 font-medium flex items-center gap-2 transition cursor-pointer">
                    <i class="fas fa-check-circle text-emerald-600 w-4 text-center"></i>
                    <span>Receive Payment</span>
                </button>
            </div>
        </template>

    </div>

    <!-- 6. VIEW INVOICE DETAILS MODAL (AGENTS.md Rule 3: Natural Soft Modal) -->
    <div x-show="showDetailModal" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
         style="display: none;">
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden animate-in fade-in zoom-in-95 duration-150"
             @click.away="showDetailModal = false">
            
            <!-- Modal Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800" x-text="'Invoice #' + (selectedInvoice?.invoice_no || '')"></h3>
                        <p class="text-[10.5px] text-slate-500 font-normal" x-text="selectedInvoice?.customer_name"></p>
                    </div>
                </div>
                <button type="button" @click="showDetailModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-4 space-y-3 max-h-[75vh] overflow-y-auto" x-show="selectedInvoice">
                <div class="space-y-3 text-xs">
                    
                    <!-- Top Strip -->
                    <div class="grid grid-cols-3 gap-2">
                        <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                            <span class="text-[9px] uppercase font-semibold text-slate-500 block">Status</span>
                            <span class="text-xs font-bold font-mono uppercase" 
                                  :class="selectedInvoice?.status === 'paid' ? 'text-emerald-700' : 'text-rose-700'" 
                                  x-text="selectedInvoice?.status"></span>
                        </div>
                        <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                            <span class="text-[9px] uppercase font-semibold text-slate-500 block">Total Bill</span>
                            <span class="text-xs font-bold font-mono text-slate-800" x-text="currencySymbol + ' ' + (parseFloat(selectedInvoice?.total_payable) || 0).toFixed(2)"></span>
                        </div>
                        <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                            <span class="text-[9px] uppercase font-semibold text-slate-500 block">Outstanding Due</span>
                            <span class="text-xs font-bold font-mono" 
                                  :class="(parseFloat(selectedInvoice?.due_amount) || 0) > 0 ? 'text-rose-600 font-bold' : 'text-slate-700'" 
                                  x-text="currencySymbol + ' ' + (parseFloat(selectedInvoice?.due_amount) || 0).toFixed(2)"></span>
                        </div>
                    </div>

                    <!-- Info Grid -->
                    <div class="p-3 bg-slate-50 rounded-lg border border-slate-200/80 space-y-2 text-xs">
                        <div class="flex justify-between"><span class="text-slate-500">Customer ID:</span> <span class="font-mono font-bold text-cyan-800" x-text="selectedInvoice?.customer_id"></span></div>
                        <div class="flex justify-between"><span class="text-slate-500">Subscriber Name:</span> <span class="font-semibold text-slate-900" x-text="selectedInvoice?.customer_name"></span></div>
                        <div class="flex justify-between"><span class="text-slate-500">PPPoE User:</span> <span class="font-mono text-slate-800" x-text="selectedInvoice?.customer_username"></span></div>
                        <div class="flex justify-between"><span class="text-slate-500">Contact Phone:</span> <span class="font-mono text-slate-800" x-text="selectedInvoice?.customer_phone"></span></div>
                        <div class="flex justify-between"><span class="text-slate-500">Package Profile:</span> <span class="font-semibold text-cyan-800" x-text="selectedInvoice?.package_name"></span></div>
                        <div class="flex justify-between"><span class="text-slate-500">Billing Month:</span> <span class="font-mono text-slate-700" x-text="selectedInvoice?.billing_month"></span></div>
                        <div class="flex justify-between"><span class="text-slate-500">Issue Date:</span> <span class="font-mono text-slate-700" x-text="selectedInvoice?.issue_date"></span></div>
                        <div class="flex justify-between"><span class="text-slate-500">Due Date:</span> <span class="font-mono text-slate-700" x-text="selectedInvoice?.due_date"></span></div>
                        <div class="flex justify-between"><span class="text-slate-500">Paid Date &amp; Method:</span> <span class="font-mono text-slate-700" x-text="(selectedInvoice?.paid_at || 'Unpaid') + ' (' + (selectedInvoice?.payment_method || 'N/A') + ')'"></span></div>
                    </div>

                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                <button type="button" @click="showDetailModal = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg cursor-pointer">
                    Close
                </button>
                <div class="flex items-center gap-2">
                    <a :href="`/reseller/customer-invoices/${selectedInvoice?.id}/print`" target="_blank" class="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
                        <i class="fas fa-print text-xs"></i>
                        <span>Print A4</span>
                    </a>
                    <template x-if="selectedInvoice?.status !== 'paid'">
                        <button type="button" @click="openPaymentModal(selectedInvoice); showDetailModal = false" class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs px-3.5 py-1.5 rounded-lg shadow-xs flex items-center gap-1.5 cursor-pointer">
                            <i class="fas fa-check text-[11px]"></i>
                            <span>Receive Payment</span>
                        </button>
                    </template>
                </div>
            </div>

        </div>
    </div>

    <!-- 7. RECORD PAYMENT MODAL (AGENTS.md Rule 3: Natural Soft Modal) -->
    <div x-show="showPaymentModal" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
         style="display: none;">
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-md overflow-hidden animate-in fade-in zoom-in-95 duration-150"
             @click.away="showPaymentModal = false">
            
            <form @submit.prevent="submitPayment">
                <!-- Modal Header -->
                <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-xs flex-shrink-0">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div>
                            <h3 class="text-xs font-semibold text-slate-800">Record Invoice Payment</h3>
                            <p class="text-[10.5px] text-slate-500 font-mono" x-text="'Invoice #' + paymentTarget?.invoice_no"></p>
                        </div>
                    </div>
                    <button type="button" @click="showPaymentModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-4 space-y-3">
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80 space-y-1 text-xs">
                        <div class="flex justify-between"><span class="text-slate-500">Subscriber:</span> <span class="font-bold text-slate-800" x-text="paymentTarget?.customer_name"></span></div>
                        <div class="flex justify-between"><span class="text-slate-500">Total Bill:</span> <span class="font-mono font-bold text-slate-800" x-text="currencySymbol + ' ' + (parseFloat(paymentTarget?.total_payable) || 0).toFixed(2)"></span></div>
                        <div class="flex justify-between"><span class="text-slate-500">Outstanding Due:</span> <span class="font-mono font-bold text-rose-600" x-text="currencySymbol + ' ' + (parseFloat(paymentTarget?.due_amount) || 0).toFixed(2)"></span></div>
                    </div>

                    <!-- Payment Amount -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Payment Amount ({{ $currencySymbol ?? '৳' }}) <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" 
                               step="0.01" 
                               x-model="paymentForm.paid_amount" 
                               required 
                               class="w-full px-3 py-1.5 rounded-lg border border-slate-200 bg-slate-50 text-xs font-mono font-bold text-slate-800 focus:bg-white focus:outline-none focus:border-cyan-500">
                    </div>

                    <!-- Payment Method -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Payment Method <span class="text-rose-500">*</span></label>
                        <select x-model="paymentForm.payment_method" 
                                required 
                                class="w-full px-3 py-1.5 rounded-lg border border-slate-200 bg-slate-50 text-xs font-semibold focus:bg-white focus:outline-none focus:border-cyan-500">
                            <option value="cash">💵 Cash In Hand</option>
                            <option value="bkash">📱 bKash</option>
                            <option value="nagad">📱 Nagad</option>
                            <option value="rocket">📱 Rocket</option>
                            <option value="bank_transfer">🏦 Bank Transfer</option>
                            <option value="pos">💳 Card / POS</option>
                            <option value="online">🌐 Online Gateway</option>
                        </select>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Remarks / Note</label>
                        <input type="text" 
                               x-model="paymentForm.notes" 
                               placeholder="Optional collection note..." 
                               class="w-full px-3 py-1.5 rounded-lg border border-slate-200 bg-slate-50 text-xs focus:bg-white focus:outline-none focus:border-cyan-500">
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="showPaymentModal = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" :disabled="submittingPayment" class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs px-4 py-1.5 rounded-lg shadow-xs flex items-center gap-1.5 cursor-pointer disabled:opacity-50">
                        <i class="fas fa-spinner fa-spin text-xs" x-show="submittingPayment" style="display: none;"></i>
                        <i class="fas fa-check text-xs" x-show="!submittingPayment"></i>
                        <span>Confirm Payment</span>
                    </button>
                </div>
            </form>

        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function invoicePageManager() {
    return {
        activeMenu: null,
        activeInvoiceData: null,
        menuPos: null,
        datePreset: '{{ $datePreset ?? 'all' }}',

        showDetailModal: false,
        selectedInvoice: null,

        showPaymentModal: false,
        paymentTarget: null,
        paymentForm: {
            paid_amount: 0,
            payment_method: 'cash',
            notes: ''
        },
        submittingPayment: false,
        currencySymbol: '{{ $currencySymbol ?? '৳' }}',

        onDatePresetChange(event) {
            const val = event.target.value;
            if (val !== 'custom') {
                event.target.form.submit();
            }
        },

        toggleMenu(id, event, invoiceData) {
            if (this.activeMenu?.id === id) {
                this.activeMenu = null;
                this.activeInvoiceData = null;
                return;
            }
            this.activeMenu = { id: id };
            this.activeInvoiceData = invoiceData;

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

        openDetailModal(invoiceData) {
            this.selectedInvoice = invoiceData;
            this.showDetailModal = true;
        },

        openPaymentModal(invoiceData) {
            this.paymentTarget = invoiceData;
            this.paymentForm = {
                paid_amount: parseFloat(invoiceData.due_amount) > 0 ? parseFloat(invoiceData.due_amount) : parseFloat(invoiceData.total_payable),
                payment_method: 'cash',
                notes: `Payment for Invoice #${invoiceData.invoice_no}`
            };
            this.showPaymentModal = true;
        },

        async submitPayment() {
            if (!this.paymentTarget) return;

            this.submittingPayment = true;

            try {
                const res = await fetch(`/reseller/customer-invoices/${this.paymentTarget.id}/pay`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.paymentForm)
                });

                const data = await res.json();

                if (data.success) {
                    this.showPaymentModal = false;
                    if (typeof Swal !== 'undefined') {
                        await Swal.fire({
                            icon: 'success',
                            title: 'Payment Recorded!',
                            text: data.message,
                            confirmButtonColor: '#0891b2'
                        });
                    } else {
                        alert(data.message);
                    }
                    window.location.reload();
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Failed',
                            text: data.message || 'Payment recording failed.',
                            confirmButtonColor: '#e11d48'
                        });
                    } else {
                        alert(data.message);
                    }
                }
            } catch (err) {
                console.error(err);
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An unexpected connection error occurred.',
                        confirmButtonColor: '#e11d48'
                    });
                } else {
                    alert('An error occurred.');
                }
            } finally {
                this.submittingPayment = false;
            }
        }
    };
}
</script>
@endpush
