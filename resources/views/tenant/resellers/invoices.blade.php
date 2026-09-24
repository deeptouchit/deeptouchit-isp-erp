@extends('tenant.layouts.app')

@section('title', 'Subscription Invoices & Receipts - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
<style>
    @media print {
        body * {
            visibility: hidden;
        }
        #printableReceipt, #printableReceipt * {
            visibility: visible;
        }
        #printableReceipt {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            background: white !important;
            padding: 20px !important;
        }
    }
</style>
@endpush

@section('content')
<div class="space-y-3" x-data="resellerInvoicesManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">
    
    <!-- Floating Global Toast Notification -->
    <div x-show="toast.show" 
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         class="fixed top-5 right-5 z-50 flex items-center gap-2.5 px-4 py-2.5 rounded-xl shadow-xl border text-xs font-semibold"
         :class="{
             'bg-slate-900 text-white border-slate-700': toast.type === 'info',
             'bg-emerald-600 text-white border-emerald-500': toast.type === 'success',
             'bg-rose-600 text-white border-rose-500': toast.type === 'error'
         }"
         style="display: none;">
        <i class="fas text-xs" :class="{
            'fa-circle-info': toast.type === 'info',
            'fa-circle-check': toast.type === 'success',
            'fa-circle-exclamation': toast.type === 'error'
        }"></i>
        <span x-text="toast.message"></span>
    </div>

    <!-- 1. Top Header Bar (Strict Rules: Icon + Title + Actions ONLY - No Subtitle) -->
    <div class="flex items-center justify-between bg-white px-3.5 py-2.5 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-100 flex items-center justify-center text-sm shadow-xs flex-shrink-0">
                <i class="fas fa-file-invoice-dollar"></i>
            </div>
            <h1 class="text-xs font-bold text-slate-800 tracking-tight">Subscription Invoices &amp; Receipts</h1>
        </div>

        <div class="flex items-center gap-2">
            <!-- Link to Panel Licenses -->
            <a href="{{ route('tenant.resellers.subscriptions') }}" 
               class="px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold text-xs shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-key text-slate-400 text-xs"></i>
                <span>Panel Licenses</span>
            </a>

            <!-- Generate Monthly Bills Button -->
            <button type="button" 
                    @click="openBulkBillModal()" 
                    class="px-3.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-wand-magic-sparkles text-[10px]"></i>
                <span>Generate Invoices</span>
            </button>
        </div>
    </div>

    <!-- 2. KPI Summary Strip (Strictly 6 Cards) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        <!-- Card 1: Total Invoiced -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Total Invoiced</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-slate-900">@currency($totalInvoiced)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-slate-100 text-slate-600 border-slate-200 flex items-center justify-center">
                <i class="fas fa-calculator"></i>
            </div>
        </div>

        <!-- Card 2: Total Collected -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-emerald-600">Total Collected</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-emerald-700">@currency($totalPaid)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-emerald-50 text-emerald-600 border-emerald-100 flex items-center justify-center">
                <i class="fas fa-circle-check"></i>
            </div>
        </div>

        <!-- Card 3: Outstanding Due -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-rose-600">Outstanding Due</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-rose-700">@currency($totalDue)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-rose-50 text-rose-600 border-rose-100 flex items-center justify-center">
                <i class="fas fa-clock"></i>
            </div>
        </div>

        <!-- Card 4: Paid Count -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-emerald-600">Paid Invoices</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-emerald-700">{{ number_format($paidCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-emerald-50 text-emerald-600 border-emerald-100 flex items-center justify-center">
                <i class="fas fa-file-circle-check"></i>
            </div>
        </div>

        <!-- Card 5: Unpaid / Due Count -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-amber-600">Unpaid / Due</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-amber-700">{{ number_format($dueCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-amber-50 text-amber-600 border-amber-100 flex items-center justify-center">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
        </div>

        <!-- Card 6: This Month Billed -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-purple-600">This Month Billed</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-purple-700">@currency($thisMonthBilled)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-purple-50 text-purple-600 border-purple-100 flex items-center justify-center">
                <i class="fas fa-calendar-check"></i>
            </div>
        </div>
    </div>

    <!-- 3. Search & Filter Bar -->
    <div class="p-3 bg-white rounded-xl border border-slate-200 shadow-xs">
        <form action="{{ route('tenant.resellers.invoices') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-7 gap-2">
            
            <!-- Search Input -->
            <div class="lg:col-span-2">
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-2.5 pointer-events-none text-slate-400 text-xs">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" 
                           name="search" 
                           value="{{ request('search') }}" 
                           placeholder="Search Invoice No, Reseller, Notes..." 
                           class="w-full pl-8 pr-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-emerald-500 outline-hidden transition font-normal" />
                </div>
            </div>

            <!-- Reseller Filter Dropdown -->
            <div>
                <select name="reseller_id" 
                        class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:bg-white focus:border-emerald-500 outline-hidden transition font-normal">
                    <option value="">All Resellers</option>
                    @foreach($allResellers as $r)
                        <option value="{{ $r->id }}" {{ request('reseller_id') == $r->id ? 'selected' : '' }}>
                            {{ $r->name }} ({{ $r->code }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Payment Status Filter -->
            <div>
                <select name="payment_status" 
                        class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:bg-white focus:border-emerald-500 outline-hidden transition font-normal">
                    <option value="">All Statuses</option>
                    <option value="PAID" {{ request('payment_status') === 'PAID' ? 'selected' : '' }}>Paid</option>
                    <option value="UNPAID" {{ request('payment_status') === 'UNPAID' ? 'selected' : '' }}>Unpaid / Due</option>
                    <option value="PARTIAL" {{ request('payment_status') === 'PARTIAL' ? 'selected' : '' }}>Partial Paid</option>
                    <option value="CANCELLED" {{ request('payment_status') === 'CANCELLED' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>

            <!-- Billing Month Filter -->
            <div>
                <input type="month" 
                       name="billing_month" 
                       value="{{ request('billing_month') }}" 
                       class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:bg-white focus:border-emerald-500 outline-hidden transition font-normal font-mono" />
            </div>

            <!-- Per Page Selector -->
            <div>
                <select name="per_page" 
                        class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:bg-white focus:border-emerald-500 outline-hidden transition font-normal">
                    <option value="10" {{ request('per_page') == 10 ? 'selected' : '' }}>10 Per Page</option>
                    <option value="20" {{ request('per_page', 20) == 20 ? 'selected' : '' }}>20 Per Page</option>
                    <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50 Per Page</option>
                    <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100 Per Page</option>
                </select>
            </div>

            <!-- Action Buttons (Strict Universal Standard) -->
            <div class="flex items-center gap-1.5 flex-shrink-0">
                <button type="submit" 
                        class="flex-1 sm:flex-initial bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-1.5 px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer" 
                        title="Apply Filters">
                    <i class="fas fa-filter text-[10px]"></i>
                    <span>Filter</span>
                </button>
                <a href="{{ route('tenant.resellers.invoices') }}" 
                   class="flex-1 sm:flex-initial bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-1.5 px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer" 
                   title="Reset Filters">
                    <i class="fas fa-rotate-left text-[10px]"></i>
                    <span>Reset</span>
                </a>
            </div>
        </form>
    </div>

    <!-- 4. Master Compact Table (Pure CSS System Compliant) -->
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-xs">
        <div class="px-3.5 py-2 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
            <span class="font-normal text-slate-700 text-xs flex items-center gap-1.5">
                <i class="fas fa-file-invoice text-slate-400 text-xs"></i>
                <span>Subscription Invoices</span>
            </span>
            <span class="text-[11px] text-slate-500 font-mono font-normal">
                Total {{ $invoices->total() }} Invoices
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-10">#</th>
                        <th>Status</th>
                        <th>Invoice No</th>
                        <th>Code</th>
                        <th>Reseller Name</th>
                        <th class="text-center">Billing Month</th>
                        <th>Invoice Type</th>
                        <th class="text-right">Total Amount</th>
                        <th class="text-right">Paid Amount</th>
                        <th class="text-right">Due Amount</th>
                        <th class="text-center">Payment Method</th>
                        <th class="w-10 text-center no-sort">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-slate-700">
                    @forelse($invoices as $index => $inv)
                        @php
                            $statusBadge = $inv->status_badge;
                            $invPayload = [
                                'id' => $inv->id,
                                'invoice_no' => $inv->invoice_no,
                                'reseller_id' => $inv->reseller_id,
                                'reseller_name' => $inv->reseller?->name ?? 'N/A',
                                'reseller_code' => $inv->reseller?->code ?? 'N/A',
                                'reseller_mobile' => $inv->reseller?->mobile ?? '',
                                'reseller_address' => $inv->reseller?->address ?? '',
                                'billing_month' => $inv->billing_month ? \Carbon\Carbon::parse($inv->billing_month)->format('F Y') : '',
                                'period_start' => $inv->period_start ? substr((string)$inv->period_start, 0, 10) : '',
                                'period_end' => $inv->period_end ? substr((string)$inv->period_end, 0, 10) : '',
                                'type_label' => $inv->type_label,
                                'amount' => (float)$inv->amount,
                                'paid_amount' => (float)$inv->paid_amount,
                                'due_amount' => (float)$inv->due_amount,
                                'payment_status' => $inv->payment_status,
                                'payment_method' => $inv->payment_method ?? 'Unpaid',
                                'paid_at' => $inv->paid_at ? \Carbon\Carbon::parse($inv->paid_at)->format('d M, Y h:i A') : null,
                                'notes' => $inv->notes,
                                'wallet_balance' => (float)($inv->reseller?->wallet_balance ?? 0),
                            ];
                        @endphp
                        <tr>
                            <!-- 1. Index -->
                            <td class="font-mono text-slate-400">
                                {{ $invoices->firstItem() + $index }}
                            </td>

                            <!-- 2. Status -->
                            <td>
                                @if($inv->payment_status === 'PAID')
                                    <span class="inline-flex items-center justify-center gap-1 px-2 py-0.5 rounded-full text-[10px] bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        <span>Paid</span>
                                    </span>
                                @elseif($inv->payment_status === 'PARTIAL')
                                    <span class="inline-flex items-center justify-center gap-1 px-2 py-0.5 rounded-full text-[10px] bg-amber-50 text-amber-700 border border-amber-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        <span>Partial</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center justify-center gap-1 px-2 py-0.5 rounded-full text-[10px] bg-rose-50 text-rose-700 border border-rose-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        <span>Unpaid</span>
                                    </span>
                                @endif
                            </td>

                            <!-- 3. Invoice No -->
                            <td class="font-mono text-cyan-800 font-semibold">
                                <button type="button" 
                                        @click="openReceiptModal({{ Js::from($invPayload) }})" 
                                        class="hover:underline cursor-pointer">
                                    {{ $inv->invoice_no }}
                                </button>
                            </td>

                            <!-- 4. Reseller Code -->
                            <td class="font-mono text-purple-700 font-semibold">
                                {{ $inv->reseller?->code ?? '--' }}
                            </td>

                            <!-- 5. Reseller Name -->
                            <td class="text-slate-800 font-medium">
                                {{ $inv->reseller?->name ?? 'N/A' }}
                            </td>

                            <!-- 6. Billing Month -->
                            <td class="text-center font-mono text-[11px] text-slate-700">
                                {{ $inv->billing_month ? \Carbon\Carbon::parse($inv->billing_month)->format('M Y') : '--' }}
                            </td>

                            <!-- 7. Invoice Type -->
                            <td class="text-slate-600 text-[11px]">
                                {{ $inv->type_label }}
                            </td>

                            <!-- 8. Total Amount -->
                            <td class="text-right font-mono font-bold text-slate-900">
                                @currency($inv->amount)
                            </td>

                            <!-- 9. Paid Amount -->
                            <td class="text-right font-mono font-semibold text-emerald-700">
                                @currency($inv->paid_amount)
                            </td>

                            <!-- 10. Due Amount -->
                            <td class="text-right font-mono font-semibold text-rose-600">
                                @currency($inv->due_amount)
                            </td>

                            <!-- 11. Payment Method -->
                            <td class="text-center font-mono text-[10px]">
                                @if($inv->payment_method)
                                    <span class="inline-flex px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 border border-slate-200">
                                        {{ str_replace('_', ' ', $inv->payment_method) }}
                                    </span>
                                @else
                                    <span class="text-slate-400">--</span>
                                @endif
                            </td>

                            <!-- 12. Actions (3-Dot Action Button) -->
                            <td class="w-10 text-center">
                                <button type="button" 
                                        @click.stop="toggleMenu({{ Js::from($invPayload) }}, $event)" 
                                        class="inv-action-btn w-5 h-5 mx-auto rounded hover:bg-slate-200 text-slate-500 hover:text-emerald-700 transition cursor-pointer flex items-center justify-center text-[10px]"
                                        title="Actions">
                                    <i class="fas fa-ellipsis-v text-[10px] pointer-events-none"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="p-8 text-center">
                                <div class="max-w-md mx-auto space-y-2">
                                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 mx-auto flex items-center justify-center text-base border border-emerald-100">
                                        <i class="fas fa-file-invoice"></i>
                                    </div>
                                    <h3 class="text-xs font-normal text-slate-800">No Invoices Found</h3>
                                    <p class="text-[11px] text-slate-400 font-normal">Generate monthly bills or record partner renewals to create invoices.</p>
                                    <div class="pt-1 flex items-center justify-center gap-2">
                                        <button type="button" 
                                                @click="openBulkBillModal()" 
                                                class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs px-3.5 py-1.5 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                                            <i class="fas fa-wand-magic-sparkles text-[10px]"></i>
                                            <span>Generate Monthly Invoices</span>
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Bar -->
        @if($invoices->hasPages())
            <div class="px-3.5 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                <span class="text-[11px] text-slate-500 font-normal">
                    Showing {{ $invoices->firstItem() }} to {{ $invoices->lastItem() }} of {{ $invoices->total() }} invoices
                </span>
                <div class="scale-90 origin-right">
                    {{ $invoices->links() }}
                </div>
            </div>
        @endif
    </div>

    <!-- 5. Global Floating 3-Dot Action Menu -->
    <div x-show="activeMenu !== null" 
         @click.away="activeMenu = null"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="fixed z-50 w-52 bg-white rounded-xl shadow-2xl border border-slate-200 py-1.5 text-xs divide-y divide-slate-100"
         :style="`top: ${menuPos.top}; bottom: ${menuPos.bottom}; right: ${menuPos.right}; left: ${menuPos.left};`"
         style="display: none;">

        <!-- Group 1: View & Collect -->
        <div class="py-1">
            <button type="button" 
                    @click="openReceiptModal(activeMenu); activeMenu = null;" 
                    class="w-full text-left px-3.5 py-1.5 hover:bg-slate-50 text-slate-700 hover:text-emerald-600 flex items-center gap-2 transition cursor-pointer">
                <i class="fas fa-receipt w-3.5 text-slate-400"></i>
                <span>View Receipt / Invoice</span>
            </button>
            <button type="button" 
                    x-show="activeMenu?.payment_status !== 'PAID'"
                    @click="openPaymentModal(activeMenu); activeMenu = null;" 
                    class="w-full text-left px-3.5 py-1.5 hover:bg-slate-50 text-emerald-700 font-semibold flex items-center gap-2 transition cursor-pointer">
                <i class="fas fa-hand-holding-dollar w-3.5 text-emerald-500"></i>
                <span>Collect Payment</span>
            </button>
        </div>

        <!-- Group 2: Management -->
        <div class="py-1">
            <button type="button" 
                    @click="deleteInvoice(activeMenu.id, activeMenu.invoice_no); activeMenu = null;" 
                    class="w-full text-left px-3.5 py-1.5 hover:bg-rose-50 text-rose-600 flex items-center gap-2 transition cursor-pointer">
                <i class="fas fa-trash-can w-3.5 text-rose-400"></i>
                <span>Delete Invoice</span>
            </button>
        </div>
    </div>

    <!-- 6. MODAL 1: View / Print Invoice Receipt -->
    <div x-show="modals.receipt" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50"
         style="display: none;">
        
        <div @click.away="modals.receipt = false" 
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="transform opacity-0 scale-95"
             x-transition:enter-end="transform opacity-100 scale-100"
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-xl overflow-hidden">
            
            <!-- Soft Natural Header -->
            <div class="px-4 py-3 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800" x-text="receiptItem?.invoice_no"></h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">Subscription Invoice &amp; Payment Receipt</p>
                    </div>
                </div>
                <button type="button" @click="modals.receipt = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center text-xs cursor-pointer">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>

            <!-- Receipt Body (Printable) -->
            <div id="printableReceipt" class="p-5 space-y-4 text-xs">
                <!-- Header Info -->
                <div class="flex items-start justify-between border-b border-slate-200 pb-3">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">{{ $tenant->company_name ?? $tenant->name }}</h2>
                        <p class="text-[11px] text-slate-500">{{ $tenant->address ?? 'ISP Billing Center' }}</p>
                        <p class="text-[11px] text-slate-500">Phone: {{ $tenant->phone ?? 'N/A' }}</p>
                    </div>
                    <div class="text-right">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase"
                              :class="receiptItem?.payment_status === 'PAID' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200'"
                              x-text="receiptItem?.payment_status"></span>
                        <p class="text-xs font-mono font-bold text-slate-800 mt-1" x-text="receiptItem?.invoice_no"></p>
                    </div>
                </div>

                <!-- Reseller & Billing Period Meta -->
                <div class="grid grid-cols-2 gap-3 p-3 bg-slate-50 rounded-lg border border-slate-200/80">
                    <div>
                        <span class="text-[10px] text-slate-400 uppercase tracking-wider block">Billed To (Reseller)</span>
                        <p class="font-bold text-slate-900 text-xs" x-text="receiptItem?.reseller_name"></p>
                        <p class="text-[11px] text-slate-600 font-mono" x-text="'Code: ' + receiptItem?.reseller_code"></p>
                        <p class="text-[11px] text-slate-600" x-text="receiptItem?.reseller_mobile"></p>
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] text-slate-400 uppercase tracking-wider block">Billing Period</span>
                        <p class="font-bold text-slate-900 text-xs" x-text="receiptItem?.billing_month"></p>
                        <p class="text-[11px] text-slate-600" x-text="receiptItem?.period_start + ' ~ ' + receiptItem?.period_end"></p>
                        <p class="text-[11px] text-slate-500" x-text="receiptItem?.paid_at ? 'Paid on: ' + receiptItem?.paid_at : 'Status: Unpaid'"></p>
                    </div>
                </div>

                <!-- Line Items Table -->
                <div class="border border-slate-200 rounded-lg overflow-hidden">
                    <table class="w-full text-xs">
                        <thead class="bg-slate-100 border-b border-slate-200 text-slate-700">
                            <tr>
                                <th class="p-2 text-left font-medium">Description</th>
                                <th class="p-2 text-right font-medium">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <tr>
                                <td class="p-2 text-slate-800">
                                    <span class="font-semibold block" x-text="receiptItem?.type_label"></span>
                                    <span class="text-[11px] text-slate-500 font-normal" x-text="receiptItem?.notes"></span>
                                </td>
                                <td class="p-2 text-right font-mono font-bold text-slate-900" x-text="'৳' + Number(receiptItem?.amount || 0).toFixed(2)"></td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-slate-50 border-t border-slate-200 text-xs">
                            <tr>
                                <td class="p-2 font-semibold text-slate-700 text-right">Total Invoiced:</td>
                                <td class="p-2 text-right font-mono font-bold text-slate-900" x-text="'৳' + Number(receiptItem?.amount || 0).toFixed(2)"></td>
                            </tr>
                            <tr>
                                <td class="p-2 font-semibold text-emerald-700 text-right">Paid Amount:</td>
                                <td class="p-2 text-right font-mono font-bold text-emerald-700" x-text="'৳' + Number(receiptItem?.paid_amount || 0).toFixed(2)"></td>
                            </tr>
                            <tr>
                                <td class="p-2 font-semibold text-rose-700 text-right">Due Balance:</td>
                                <td class="p-2 text-right font-mono font-bold text-rose-700" x-text="'৳' + Number(receiptItem?.due_amount || 0).toFixed(2)"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                <button type="button" 
                        @click="modals.receipt = false" 
                        class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                    Close
                </button>
                <div class="flex items-center gap-2">
                    <button type="button" 
                            x-show="receiptItem?.payment_status !== 'PAID'"
                            @click="modals.receipt = false; openPaymentModal(receiptItem);" 
                            class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs px-3.5 py-1.5 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                        <i class="fas fa-hand-holding-dollar text-[10px]"></i>
                        <span>Collect Payment</span>
                    </button>
                    <button type="button" 
                            @click="window.print()" 
                            class="bg-slate-800 hover:bg-slate-900 text-white font-medium text-xs px-3.5 py-1.5 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                        <i class="fas fa-print text-[10px]"></i>
                        <span>Print Receipt</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- 7. MODAL 2: Collect Payment / Settle Invoice -->
    <div x-show="modals.payment" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50"
         style="display: none;">
        
        <div @click.away="modals.payment = false" 
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-md overflow-hidden">
            
            <!-- Soft Natural Header -->
            <div class="px-4 py-3 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-hand-holding-dollar"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Collect Invoice Payment</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal" x-text="paymentItem?.invoice_no"></p>
                    </div>
                </div>
                <button type="button" @click="modals.payment = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center text-xs cursor-pointer">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>

            <!-- Modal Form Body -->
            <div class="p-4 space-y-3.5 text-xs">
                <!-- Info Summary -->
                <div class="p-3 bg-emerald-50/60 rounded-lg border border-emerald-100 grid grid-cols-2 gap-2 text-xs">
                    <div>
                        <span class="text-[10.5px] text-slate-500 block">Reseller:</span>
                        <span class="font-semibold text-slate-800" x-text="paymentItem?.reseller_name"></span>
                    </div>
                    <div class="text-right">
                        <span class="text-[10.5px] text-slate-500 block">Due Amount:</span>
                        <span class="font-bold text-rose-600 font-mono" x-text="'৳' + Number(paymentItem?.due_amount || 0).toFixed(2)"></span>
                    </div>
                </div>

                <!-- Payment Amount -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                        Collected Amount (৳) <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" 
                           step="0.01" 
                           x-model="paymentForm.amount" 
                           class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono font-bold text-slate-800 focus:bg-white focus:border-emerald-500 outline-hidden transition" />
                </div>

                <!-- Payment Method -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                        Payment Method <span class="text-rose-500">*</span>
                    </label>
                    <select x-model="paymentForm.payment_method" 
                            class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-emerald-500 outline-hidden transition font-normal">
                        <option value="CASH">Cash Collection</option>
                        <option value="WALLET_DEDUCT">Deduct from Reseller Wallet</option>
                        <option value="BANK">Bank Transfer / Deposit</option>
                        <option value="BKASH">bKash</option>
                        <option value="NAGAD">Nagad</option>
                    </select>
                </div>

                <!-- Remarks -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">Notes / Trx ID</label>
                    <input type="text" 
                           x-model="paymentForm.notes" 
                           placeholder="e.g. Received via bKash TrxID #827182" 
                           class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-emerald-500 outline-hidden transition font-normal" />
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                <button type="button" 
                        @click="modals.payment = false" 
                        class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                    Cancel
                </button>
                <button type="button" 
                        @click="submitPayment()" 
                        :disabled="saving"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50">
                    <i class="fas fa-check text-[10px]"></i>
                    <span x-text="saving ? 'Recording...' : 'Confirm Payment'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- 8. MODAL 3: Bulk Generate Monthly Invoices -->
    <div x-show="modals.bulkBill" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50"
         style="display: none;">
        
        <div @click.away="modals.bulkBill = false" 
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-md overflow-hidden">
            
            <!-- Soft Natural Header -->
            <div class="px-4 py-3 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-wand-magic-sparkles"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Generate Monthly Invoices</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">Auto-create invoices for all active reseller licenses</p>
                    </div>
                </div>
                <button type="button" @click="modals.bulkBill = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center text-xs cursor-pointer">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>

            <!-- Modal Form Body -->
            <div class="p-4 space-y-3.5 text-xs">
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                        Billing Month <span class="text-rose-500">*</span>
                    </label>
                    <input type="month" 
                           x-model="bulkForm.billing_month" 
                           class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:bg-white focus:border-emerald-500 outline-hidden transition" />
                </div>

                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 space-y-2">
                    <label class="flex items-start gap-2 cursor-pointer">
                        <input type="checkbox" 
                               x-model="bulkForm.auto_deduct_wallet" 
                               class="mt-0.5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" />
                        <div>
                            <span class="font-semibold text-slate-800 block text-xs">Auto-deduct from Reseller Wallet</span>
                            <span class="text-[10.5px] text-slate-500">If checked and the reseller has sufficient balance, invoice will be marked as PAID immediately.</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                <button type="button" 
                        @click="modals.bulkBill = false" 
                        class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                    Cancel
                </button>
                <button type="button" 
                        @click="submitBulkBill()" 
                        :disabled="saving"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50">
                    <i class="fas fa-wand-magic-sparkles text-[10px]"></i>
                    <span x-text="saving ? 'Generating...' : 'Generate Invoices'"></span>
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    function resellerInvoicesManager() {
        return {
            activeMenu: null,
            menuPos: { top: '0px', bottom: 'auto', right: '10px', left: 'auto' },
            saving: false,
            toast: { show: false, message: '', type: 'success' },
            modals: {
                receipt: false,
                payment: false,
                bulkBill: false
            },
            receiptItem: null,
            paymentItem: null,
            paymentForm: {
                invoice_id: null,
                amount: 0,
                payment_method: 'CASH',
                notes: ''
            },
            bulkForm: {
                billing_month: new Date().toISOString().slice(0, 7),
                auto_deduct_wallet: true
            },

            showToast(message, type = 'success') {
                this.toast.message = message;
                this.toast.type = type;
                this.toast.show = true;
                setTimeout(() => { this.toast.show = false; }, 3500);
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

            openReceiptModal(item) {
                this.receiptItem = item;
                this.modals.receipt = true;
            },

            openPaymentModal(item) {
                this.paymentItem = item;
                this.paymentForm = {
                    invoice_id: item.id,
                    amount: item.due_amount,
                    payment_method: 'CASH',
                    notes: ''
                };
                this.modals.payment = true;
            },

            async submitPayment() {
                if (!this.paymentForm.amount || this.paymentForm.amount <= 0) {
                    this.showToast('Please enter a valid payment amount.', 'error');
                    return;
                }
                this.saving = true;
                try {
                    const res = await fetch(`/admin/resellers/invoices/${this.paymentForm.invoice_id}/pay`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(this.paymentForm)
                    });
                    const data = await res.json();
                    if (res.ok && data.success) {
                        this.showToast(data.message || 'Payment recorded successfully!', 'success');
                        this.modals.payment = false;
                        setTimeout(() => window.location.reload(), 600);
                    } else {
                        this.showToast(data.message || 'Payment collection failed.', 'error');
                    }
                } catch (e) {
                    this.showToast('Error processing payment', 'error');
                } finally {
                    this.saving = false;
                }
            },

            openBulkBillModal() {
                this.bulkForm = {
                    billing_month: new Date().toISOString().slice(0, 7),
                    auto_deduct_wallet: true
                };
                this.modals.bulkBill = true;
            },

            async submitBulkBill() {
                this.saving = true;
                try {
                    const res = await fetch("{{ route('tenant.resellers.subscriptions.generate-bills') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(this.bulkForm)
                    });
                    const data = await res.json();
                    if (res.ok && data.success) {
                        this.showToast(data.message || 'Invoices generated successfully!', 'success');
                        this.modals.bulkBill = false;
                        setTimeout(() => window.location.reload(), 600);
                    } else {
                        this.showToast(data.message || 'Failed to generate invoices.', 'error');
                    }
                } catch (e) {
                    this.showToast('Error generating bulk invoices', 'error');
                } finally {
                    this.saving = false;
                }
            },

            deleteInvoice(id, invoiceNo) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Delete Invoice?',
                        html: `<div class="text-xs text-slate-600 mt-1">Are you sure you want to delete Invoice <strong class="text-slate-900 font-semibold">${invoiceNo}</strong>?<br><span class="text-[11px] text-rose-500 mt-1 block">This financial record will be permanently deleted.</span></div>`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#e11d48',
                        cancelButtonColor: '#64748b',
                        confirmButtonText: '<i class="far fa-trash-alt mr-1"></i> Yes, Delete',
                        cancelButtonText: 'Cancel',
                        reverseButtons: true,
                        customClass: {
                            popup: 'rounded-2xl shadow-xl border border-slate-200 p-5',
                            confirmButton: 'px-4 py-2 rounded-xl text-xs font-semibold shadow-xs',
                            cancelButton: 'px-4 py-2 rounded-xl text-xs font-semibold'
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            this.executeDelete(id);
                        }
                    });
                } else if (confirm(`Are you sure you want to delete Invoice '${invoiceNo}'?`)) {
                    this.executeDelete(id);
                }
            },

            async executeDelete(id) {
                try {
                    const res = await fetch(`/admin/resellers/invoices/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    });
                    const data = await res.json();
                    if (res.ok && data.success) {
                        this.showToast(data.message || 'Invoice deleted successfully.', 'success');
                        setTimeout(() => window.location.reload(), 500);
                    } else {
                        this.showToast(data.message || 'Delete failed.', 'error');
                    }
                } catch (e) {
                    this.showToast('Error deleting invoice', 'error');
                }
            }
        };
    }
</script>
@endpush
