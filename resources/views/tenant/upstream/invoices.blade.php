@extends('tenant.layouts.app')

@section('title', 'Carrier Invoices & Bills - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
    {{-- Page specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="upstreamInvoiceManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">

    <!-- 1. Top Header Bar (Strictly Icon + Title + Action Button ONLY - AGENTS.md Rule 2.A) -->
    <div class="flex items-center justify-between bg-white px-3.5 py-2.5 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center text-sm shadow-2xs flex-shrink-0">
                <i class="fas fa-file-invoice-dollar"></i>
            </div>
            <h1 class="text-xs font-bold text-slate-800 tracking-tight">Carrier Invoices &amp; Bills</h1>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" 
                    @click="openInvoiceModal()"
                    class="px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-plus text-xs"></i>
                <span>Record Carrier Bill</span>
            </button>
        </div>
    </div>

    <!-- 2. KPI Summary Strip (Strictly 6 Cards - AGENTS.md Rule 2.B & Rule 6) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        <!-- Card 1: Total Invoices -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Total Invoices</span>
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

    <!-- 3. Search & Multi-Filter Toolbar (Strictly Rule 2.C - Filter then Reset Sequence) -->
    <div class="bg-white p-2.5 rounded-xl border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('tenant.upstream.invoices') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-12 gap-2 items-center">
            
            <!-- Search Input -->
            <div class="relative md:col-span-4">
                <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" 
                       name="search" 
                       value="{{ request('search') }}" 
                       placeholder="Search Invoice No, Carrier, Notes..." 
                       class="w-full pl-8 pr-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-cyan-500 focus:outline-none transition">
            </div>

            <!-- Provider Filter -->
            <div class="md:col-span-3">
                <select name="provider_id" class="w-full px-2.5 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                    <option value="">All Carriers</option>
                    @foreach($providers as $p)
                    <option value="{{ $p->id }}" {{ request('provider_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Payment Status Filter -->
            <div class="md:col-span-2">
                <select name="status" class="w-full px-2.5 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                    <option value="">All Statuses</option>
                    <option value="PAID" {{ request('status') === 'PAID' ? 'selected' : '' }}>Paid</option>
                    <option value="PARTIAL" {{ request('status') === 'PARTIAL' ? 'selected' : '' }}>Partial</option>
                    <option value="UNPAID" {{ request('status') === 'UNPAID' ? 'selected' : '' }}>Unpaid</option>
                </select>
            </div>

            <!-- Per Page Selector -->
            <div class="md:col-span-1">
                <select name="per_page" class="w-full px-2 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                    <option value="10" {{ request('per_page') == '10' ? 'selected' : '' }}>10</option>
                    <option value="20" {{ request('per_page', '20') == '20' ? 'selected' : '' }}>20</option>
                    <option value="50" {{ request('per_page') == '50' ? 'selected' : '' }}>50</option>
                    <option value="100" {{ request('per_page') == '100' ? 'selected' : '' }}>100</option>
                </select>
            </div>

            <!-- Filter & Reset Button Sequence (Strictly Filter Cyan first, Reset Slate second, Always visible) -->
            <div class="md:col-span-2 flex items-center justify-end gap-1.5">
                <button type="submit" 
                        class="w-full bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-1.5 px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                    <i class="fas fa-filter text-[10px]"></i>
                    <span>Filter</span>
                </button>

                <a href="{{ route('tenant.upstream.invoices') }}" 
                   class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-1.5 px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer"
                   title="Reset All Filters">
                    <i class="fas fa-rotate-left text-[10px]"></i>
                    <span>Reset</span>
                </a>
            </div>
        </form>
    </div>

    <!-- 4. Master Table (Strictly .saas-table pure CSS system - Max 7 Core Columns - AGENTS.md Rule 2.D) -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-10 text-center">#</th>
                        <th>Invoice No</th>
                        <th>Carrier Provider</th>
                        <th>Billing Month</th>
                        <th class="text-right">Total Amount</th>
                        <th class="text-right">Due Balance</th>
                        <th class="w-12 text-center no-sort">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $index => $invoice)
                    <tr>
                        <td class="text-center font-mono text-slate-500">
                            {{ $invoices->firstItem() + $index }}
                        </td>

                        <!-- Invoice No (Single Data) -->
                        <td class="font-mono font-bold text-slate-800">
                            {{ $invoice->invoice_no }}
                        </td>

                        <!-- Carrier Provider (Single Data) -->
                        <td class="font-semibold text-slate-800">
                            {{ $invoice->provider->name ?? '-' }}
                        </td>

                        <!-- Billing Month (Single Data) -->
                        <td class="font-medium text-slate-700">
                            {{ $invoice->billing_month ? $invoice->billing_month->format('F Y') : '-' }}
                        </td>

                        <!-- Total Amount (Single Data) -->
                        <td class="text-right font-mono font-bold text-slate-900">
                            @currency($invoice->total_amount)
                        </td>

                        <!-- Due Balance (Single Data) -->
                        <td class="text-right font-mono font-bold {{ $invoice->due_amount > 0 ? 'text-rose-600' : 'text-emerald-700' }}">
                            @currency($invoice->due_amount)
                        </td>

                        <!-- Action (3-Dot Floating Trigger) -->
                        <td class="text-center">
                            <button type="button" 
                                    @click="toggleMenu({{ json_encode([
                                        'id' => $invoice->id,
                                        'invoice_no' => $invoice->invoice_no,
                                        'provider_id' => $invoice->provider_id,
                                        'provider_name' => $invoice->provider->name ?? '-',
                                        'billing_month' => $invoice->billing_month ? $invoice->billing_month->format('Y-m-d') : '',
                                        'bandwidth_cost' => $invoice->bandwidth_cost,
                                        'transmission_cost' => $invoice->transmission_cost,
                                        'vat_tax' => $invoice->vat_tax,
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
                        <td colspan="7" class="text-center py-8 text-slate-500">
                            <div class="flex flex-col items-center justify-center gap-1.5">
                                <i class="fas fa-file-invoice-dollar text-2xl text-slate-300"></i>
                                <span class="text-xs font-semibold text-slate-600">No carrier invoices recorded</span>
                                <span class="text-[11px] text-slate-400">Click '+ Record Carrier Bill' above to enter monthly billing invoices.</span>
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
                Showing <span class="font-semibold text-slate-700">{{ $invoices->firstItem() }}</span> to <span class="font-semibold text-slate-700">{{ $invoices->lastItem() }}</span> of <span class="font-semibold text-slate-700">{{ $invoices->total() }}</span> invoices
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
            <span>Delete Invoice</span>
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
                        <h2 class="text-xs font-semibold text-slate-800">Record Upstream Carrier Invoice / Bill</h2>
                        <p class="text-[10.5px] text-slate-500 font-normal">Enter carrier billed amount, transmission cost, VAT and payment terms.</p>
                    </div>
                </div>
                <button type="button" @click="showInvoiceModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-xmark text-xs"></i>
                </button>
            </div>

            <form action="{{ route('tenant.upstream.invoice.store') }}" method="POST" class="p-4 space-y-3.5 max-h-[78vh] overflow-y-auto">
                @csrf
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">
                        Upstream Carrier Provider <span class="text-rose-500">*</span>
                    </label>
                    <select name="provider_id" 
                            x-model="invoiceData.provider_id" 
                            @change="onProviderChange()" 
                            required 
                            class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                        <option value="">Select Upstream Carrier</option>
                        @foreach($providers as $p)
                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            Invoice / Bill Number <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" 
                               name="invoice_no" 
                               x-model="invoiceData.invoice_no" 
                               required 
                               placeholder="e.g. SMT-INV-2026-09" 
                               class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition font-mono">
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            Billing Month <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" 
                               name="billing_month" 
                               x-model="invoiceData.billing_month" 
                               required 
                               class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition font-mono">
                    </div>
                </div>

                <!-- Bandwidth & Transmission Breakdown -->
                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200/80 space-y-2.5">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block">Upstream Cost Breakdown</span>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-2.5">
                        <div>
                            <label class="block text-[10.5px] font-medium text-slate-700 mb-1">
                                Bandwidth Cost ({{ $currencySymbol }}) <span class="text-rose-500">*</span>
                            </label>
                            <input type="number" 
                                   step="0.01" 
                                   min="0" 
                                   name="bandwidth_cost" 
                                   x-model.number="invoiceData.bandwidth_cost" 
                                   required 
                                   placeholder="0.00" 
                                   class="w-full bg-white border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 font-mono focus:border-cyan-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[10.5px] font-medium text-slate-700 mb-1">
                                NTTN / Fiber ({{ $currencySymbol }})
                            </label>
                            <input type="number" 
                                   step="0.01" 
                                   min="0" 
                                   name="transmission_cost" 
                                   x-model.number="invoiceData.transmission_cost" 
                                   placeholder="0.00" 
                                   class="w-full bg-white border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 font-mono focus:border-cyan-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[10.5px] font-medium text-slate-700 mb-1">
                                VAT / Tax ({{ $currencySymbol }})
                            </label>
                            <input type="number" 
                                   step="0.01" 
                                   min="0" 
                                   name="vat_tax" 
                                   x-model.number="invoiceData.vat_tax" 
                                   placeholder="0.00" 
                                   class="w-full bg-white border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 font-mono focus:border-cyan-500 focus:outline-none">
                        </div>
                    </div>

                    <div class="pt-2 border-t border-slate-200/80 flex items-center justify-between text-xs font-mono">
                        <span class="font-bold text-slate-600">Total Payable Amount:</span>
                        <span class="font-bold text-indigo-700 text-sm" x-text="`{{ $currencySymbol }} ${(Number(invoiceData.bandwidth_cost || 0) + Number(invoiceData.transmission_cost || 0) + Number(invoiceData.vat_tax || 0)).toFixed(2)}`"></span>
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">Notes / Remarks</label>
                    <input type="text" name="notes" placeholder="e.g. Regular monthly bill settlement" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                </div>

                <div class="pt-2 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="showInvoiceModal = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition cursor-pointer">
                        Record Carrier Bill
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
                        <p class="text-[10.5px] text-slate-500 font-normal" x-text="`Carrier: ${detailsData?.provider_name || 'N/A'}`"></p>
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

                <!-- Cost Breakdown Breakdown Grid -->
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
                        <span class="text-slate-400 block font-medium">Payment Status:</span>
                        <span class="font-bold font-mono" :class="detailsData?.due_amount > 0 ? 'text-rose-600' : 'text-emerald-700'" x-text="detailsData?.payment_status || 'UNPAID'"></span>
                    </div>
                </div>
            </div>

            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                <button type="button" @click="showDetailsModal = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                    Close
                </button>
                <button type="button" 
                        x-show="detailsData?.due_amount > 0" 
                        @click="showDetailsModal = false; openPaymentModal(detailsData)" 
                        class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition cursor-pointer flex items-center gap-1.5">
                    <i class="fas fa-money-bill-transfer text-[10px]"></i>
                    <span>Disburse Payment</span>
                </button>
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
                <input type="hidden" name="invoice_id" :value="paymentData.invoice_id">

                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">Carrier Provider <span class="text-rose-500">*</span></label>
                    <select name="provider_id" x-model="paymentData.provider_id" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                        <option value="">Select Carrier</option>
                        @foreach($providers as $p)
                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">Paid Amount ({{ $currencySymbol }}) <span class="text-rose-500">*</span></label>
                        <input type="number" step="0.01" min="1" name="amount" x-model.number="paymentData.amount" required placeholder="0.00" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 font-mono font-bold text-emerald-700">
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">Payment Method <span class="text-rose-500">*</span></label>
                        <select name="payment_method" x-model="paymentData.payment_method" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
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
                        <input type="text" name="bank_name" x-model="paymentData.bank_name" placeholder="e.g. Standard Chartered" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5">
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">Cheque / Trx Reference</label>
                        <input type="text" name="transaction_ref" x-model="paymentData.transaction_ref" placeholder="Ref No / Cheque No" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 font-mono">
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">Payment Date <span class="text-rose-500">*</span></label>
                    <input type="datetime-local" name="paid_at" x-model="paymentData.paid_at" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5">
                </div>

                <div class="pt-2 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="showPaymentModal = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition cursor-pointer">
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
                timer: 2000,
                showConfirmButton: false
            });
        @endif

        @if(session('error'))
            Swal.fire({
                icon: 'error',
                title: 'Action Failed',
                text: "{{ session('error') }}"
            });
        @endif
    });
</script>
@endpush
