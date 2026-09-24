@extends('owner.layouts.app')

@section('page-title', 'Billing & Invoices')

@section('content')
<div class="space-y-4" 
     @scroll.window="activeMenuInvoice = null" 
     @resize.window="activeMenuInvoice = null"
     x-data="{
    createModalOpen: false,
    editModalOpen: false,
    activeMenuInvoice: null,
    menuPos: { top: '0px', right: '0px' },
    editInvoice: {
        id: null,
        invoice_no: '',
        amount: 0,
        status: 'unpaid',
        due_date: '',
        paid_at: '',
        payment_method: 'Online',
        trx_id: ''
    },

    toggleMenu(invoice, event) {
        if (this.activeMenuInvoice && this.activeMenuInvoice.id === invoice.id) {
            this.activeMenuInvoice = null;
            return;
        }
        const rect = event.currentTarget.getBoundingClientRect();
        const spaceBelow = window.innerHeight - rect.bottom;
        const topPos = spaceBelow < 210 ? Math.max(10, rect.top - 185) + 'px' : (rect.bottom + 4) + 'px';
        const rightPos = Math.max(10, window.innerWidth - rect.right) + 'px';

        this.menuPos = {
            top: topPos,
            right: rightPos
        };
        this.activeMenuInvoice = invoice;
    },

    openEdit(invoice) {
        this.editInvoice = {
            id: invoice.id,
            invoice_no: invoice.invoice_no,
            amount: invoice.amount,
            status: invoice.status,
            due_date: invoice.raw_due_date || '',
            paid_at: invoice.raw_paid_at || '',
            payment_method: invoice.payment_method || 'Online',
            trx_id: invoice.trx_id || ''
        };
        this.editModalOpen = true;
    },

    copyToClipboard(text) {
        navigator.clipboard.writeText(text);
        alert('Copied: ' + text);
    }
}">


    <!-- Top Header Bar with Run Billing Engine & Generate Invoice -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs">
        <div>
            <div class="flex items-center gap-2">
                <h2 class="text-sm font-bold text-slate-900 tracking-tight">SaaS Billing & Invoices</h2>
                <span class="px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 border border-blue-200 text-[10px] font-bold font-mono">
                    {{ $totalInvoicesCount }} Invoices
                </span>
            </div>
            <p class="text-[11px] text-slate-500 mt-0.5">Manage subscription billing vouchers, overdue tracking, itemized lines, and payment settlements</p>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <!-- Run Billing Engine -->
            <form action="{{ route('owner.billing.run-engine') }}" method="POST" onsubmit="return confirm('Run automated billing engine now? This generates upcoming invoices and enforces grace periods.');">
                @csrf
                <button type="submit" class="px-3 py-1.5 rounded-lg bg-purple-50 hover:bg-purple-600 hover:text-white text-purple-700 border border-purple-200 font-bold text-xs shadow-2xs transition flex items-center gap-1.5">
                    <i class="fas fa-bolt text-[11px]"></i>
                    <span>Run Billing Engine</span>
                </button>
            </form>

            <!-- Generate Invoice -->
            <button type="button" 
                    @click="createModalOpen = true"
                    class="px-3.5 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-xs transition flex items-center gap-1.5">
                <i class="fas fa-plus-circle text-[11px]"></i>
                <span>Generate Invoice</span>
            </button>
        </div>
    </div>

    <!-- 4 Key Financial Metrics Strip -->
    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="p-3.5 rounded-xl bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5">
                <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider block">Total Realized Revenue</span>
                <span class="text-lg font-extrabold text-emerald-700 tracking-tight font-mono">@currency($totalRevenue)</span>
                <span class="text-[10px] text-slate-400 block">All-time Paid Invoices</span>
            </div>
            <div class="w-9 h-9 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm border border-emerald-100 flex-shrink-0">
                <i class="fas fa-sack-dollar"></i>
            </div>
        </div>

        <div class="p-3.5 rounded-xl bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5">
                <span class="text-[10px] font-bold text-blue-600 uppercase tracking-wider block">This Month's Realized</span>
                <span class="text-lg font-extrabold text-blue-700 tracking-tight font-mono">@currency($thisMonthRevenue)</span>
                <span class="text-[10px] text-blue-600 font-semibold block">{{ date('F Y') }} Collection</span>
            </div>
            <div class="w-9 h-9 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-sm border border-blue-100 flex-shrink-0">
                <i class="fas fa-calendar-check"></i>
            </div>
        </div>

        <div class="p-3.5 rounded-xl bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5">
                <span class="text-[10px] font-bold text-amber-600 uppercase tracking-wider block">Pending Receivables</span>
                <div class="flex items-center gap-1.5">
                    <span class="text-lg font-extrabold text-amber-700 tracking-tight font-mono">@currency($pendingRevenue)</span>
                    @if($overdueCount > 0)
                        <span class="px-1.5 py-0.2 rounded bg-rose-100 text-rose-800 text-[9.5px] font-bold">{{ $overdueCount }} Overdue</span>
                    @endif
                </div>
                <span class="text-[10px] text-slate-400 block">Uncollected Subscription Dues</span>
            </div>
            <div class="w-9 h-9 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center text-sm border border-amber-100 flex-shrink-0">
                <i class="fas fa-hourglass-half"></i>
            </div>
        </div>

        <div class="p-3.5 rounded-xl bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5">
                <span class="text-[10px] font-bold text-purple-600 uppercase tracking-wider block">Estimated Platform MRR</span>
                <span class="text-lg font-extrabold text-purple-700 tracking-tight font-mono">@currency($estimatedMrr)</span>
                <span class="text-[10px] text-slate-400 block">Active Recurring Run-Rate</span>
            </div>
            <div class="w-9 h-9 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center text-sm border border-purple-100 flex-shrink-0">
                <i class="fas fa-chart-line"></i>
            </div>
        </div>
    </div>

    <!-- Filter & Search Matrix -->
    <div class="p-3.5 rounded-xl bg-white border border-slate-200 shadow-xs">
        <form action="{{ route('owner.billing.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-2.5">
            
            <div class="lg:col-span-2">
                <div class="relative">
                    <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-[11px]"></i>
                    <input type="text" 
                           name="search" 
                           value="{{ request('search') }}" 
                           placeholder="Invoice #, Trx ID, Company, Phone..." 
                           class="w-full pl-7 pr-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition placeholder:text-slate-400">
                </div>
            </div>

            <div>
                <select name="tenant_id" class="w-full px-2.5 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition">
                    <option value="">All ISP Tenants</option>
                    @foreach($tenants as $t)
                        <option value="{{ $t->id }}" {{ request('tenant_id') == $t->id ? 'selected' : '' }}>
                            {{ $t->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <select name="status" class="w-full px-2.5 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition">
                    <option value="">All Statuses</option>
                    <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="unpaid" {{ request('status') === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                    <option value="overdue" {{ request('status') === 'overdue' ? 'selected' : '' }}>Overdue</option>
                </select>
            </div>

            <div>
                <select name="payment_method" class="w-full px-2.5 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition">
                    <option value="">All Methods</option>
                    <option value="bkash" {{ request('payment_method') === 'bkash' ? 'selected' : '' }}>bKash</option>
                    <option value="nagad" {{ request('payment_method') === 'nagad' ? 'selected' : '' }}>Nagad</option>
                    <option value="bank" {{ request('payment_method') === 'bank' ? 'selected' : '' }}>Bank Transfer</option>
                    <option value="cash" {{ request('payment_method') === 'cash' ? 'selected' : '' }}>Cash / Manual</option>
                    <option value="online" {{ request('payment_method') === 'online' ? 'selected' : '' }}>Online PGW</option>
                </select>
            </div>

            <div class="flex items-center gap-1.5">
                <button type="submit" class="flex-1 py-1.5 px-3 rounded-lg bg-slate-800 hover:bg-slate-900 text-white font-semibold text-xs transition flex items-center justify-center gap-1.5 shadow-xs">
                    <i class="fas fa-filter text-[10px]"></i>
                    <span>Filter</span>
                </button>
                <a href="{{ route('owner.billing.index') }}" title="Reset Filters" class="py-1.5 px-2.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold transition flex items-center justify-center">
                    <i class="fas fa-rotate-left text-[11px]"></i>
                </a>
            </div>

        </form>
    </div>

    <!-- Invoices Table -->
    <div class="rounded-xl bg-white border border-slate-200 overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-700 whitespace-nowrap border-collapse">
                <thead class="bg-slate-50 text-slate-600 uppercase tracking-wider text-[10px] border-b border-slate-200 font-bold select-none text-center">
                    <tr>
                        <th class="px-2.5 py-1.5 border-r border-slate-200 text-center w-10">#</th>
                        <th class="px-2.5 py-1.5 border-r border-slate-200 text-center">Invoice #</th>
                        <th class="px-2.5 py-1.5 border-r border-slate-200 text-center">ISP Organization</th>
                        <th class="px-2.5 py-1.5 border-r border-slate-200 text-center">Plan</th>
                        <th class="px-2.5 py-1.5 border-r border-slate-200 text-center">Total / Due</th>
                        <th class="px-2.5 py-1.5 border-r border-slate-200 text-center">Payment Info</th>
                        <th class="px-2.5 py-1.5 border-r border-slate-200 text-center">Date</th>
                        <th class="px-2.5 py-1.5 border-r border-slate-200 text-center">Status</th>
                        <th class="px-2.5 py-1.5 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse ($invoices as $inv)
                        @php
                            $isOverdue = $inv->status === 'unpaid' && $inv->due_date && \Carbon\Carbon::parse($inv->due_date)->isPast();
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition">
                            
                            <td class="px-2.5 py-1.5 border-r border-slate-200 text-center font-mono text-slate-400 text-[10.5px] font-semibold">
                                {{ ($invoices->currentPage() - 1) * $invoices->perPage() + $loop->iteration }}
                            </td>

                            <td class="px-2.5 py-1.5 border-r border-slate-200 font-mono text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <a href="{{ route('owner.invoices.show', $inv) }}" 
                                        title="View Complete Invoice Details"
                                        class="font-bold text-blue-700 hover:underline text-xs">
                                        {{ $inv->invoice_no }}
                                    </a>
                                    <button type="button" 
                                            @click="copyToClipboard('{{ $inv->invoice_no }}')"
                                            title="Copy Invoice #" 
                                            class="text-slate-400 hover:text-slate-700 text-[9.5px]">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </td>

                            <td class="px-2.5 py-1.5 border-r border-slate-200">
                                @if($inv->tenant)
                                    <a href="{{ route('owner.tenants.show', $inv->tenant) }}" class="font-bold text-slate-900 hover:text-blue-600 transition block text-xs">
                                        {{ $inv->tenant->name }}
                                    </a>
                                @else
                                    <span class="text-slate-400 italic text-xs">Deleted Tenant</span>
                                @endif
                            </td>

                            <td class="px-2.5 py-1.5 border-r border-slate-200 text-center">
                                <span class="px-2 py-0.5 rounded-md bg-purple-50 text-purple-700 border border-purple-200 font-bold text-[10px]">
                                    {{ $inv->plan->name ?? ($inv->tenant->plan->name ?? 'Custom') }}
                                </span>
                            </td>

                            <td class="px-2.5 py-1.5 border-r border-slate-200 text-center font-mono">
                                <div class="font-extrabold text-slate-900 text-xs">@currency($inv->amount)</div>
                                @if($inv->status !== 'paid' && ($inv->due_amount ?: $inv->amount) > 0)
                                    <div class="text-[9.5px] text-amber-700 font-bold">Due: @currency($inv->due_amount ?: $inv->amount)</div>
                                @endif
                            </td>

                            <td class="px-2.5 py-1.5 border-r border-slate-200 text-center">
                                <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 font-semibold text-[9.5px] inline-block border border-slate-200 uppercase">
                                    {{ $inv->payment_method ?: 'Online' }}
                                </span>
                            </td>

                            <td class="px-2.5 py-1.5 border-r border-slate-200 text-center font-mono text-[10.5px]">
                                @if($inv->status === 'paid' && $inv->paid_at)
                                    <span class="text-slate-700 font-medium">{{ \Carbon\Carbon::parse($inv->paid_at)->format('d M, Y') }}</span>
                                @else
                                    <span class="text-slate-700 font-medium">{{ $inv->due_date ? \Carbon\Carbon::parse($inv->due_date)->format('d M, Y') : '—' }}</span>
                                @endif
                            </td>

                            <td class="px-2.5 py-1.5 border-r border-slate-200 text-center">
                                @if($inv->status === 'paid')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9.5px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1 h-1 rounded-full bg-emerald-500"></span>
                                        Paid
                                    </span>
                                @elseif($isOverdue)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9.5px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                        <span class="w-1 h-1 rounded-full bg-rose-500"></span>
                                        Overdue
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9.5px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                        <span class="w-1 h-1 rounded-full bg-amber-500"></span>
                                        Unpaid
                                    </span>
                                @endif
                            </td>

                            <td class="px-2.5 py-1.5 text-center">
                                <button type="button" 
                                        @click.stop="toggleMenu({{ json_encode([
                                            'id' => $inv->id,
                                            'invoice_no' => $inv->invoice_no,
                                            'amount' => (float)$inv->amount,
                                            'due_amount' => (float)($inv->due_amount ?: $inv->amount),
                                            'status' => $inv->status,
                                            'payment_method' => $inv->payment_method ?? 'Online',
                                            'trx_id' => $inv->trx_id ?? '',
                                            'due_date' => $inv->due_date ? \Carbon\Carbon::parse($inv->due_date)->format('d M, Y') : 'N/A',
                                            'paid_at' => $inv->paid_at ? \Carbon\Carbon::parse($inv->paid_at)->format('d M, Y (h:i A)') : ($inv->status === 'paid' ? 'Completed' : 'Unpaid'),
                                            'created_at' => $inv->created_at ? $inv->created_at->format('d M, Y') : 'N/A',
                                            'tenant_name' => $inv->tenant->name ?? 'N/A',
                                            'tenant_company' => $inv->tenant->company_name ?? 'ISP Organization',
                                            'tenant_email' => $inv->tenant->email ?? 'N/A',
                                            'tenant_phone' => $inv->tenant->phone ?? 'N/A',
                                            'tenant_address' => $inv->tenant->address ?? 'Bangladesh',
                                            'plan_name' => $inv->plan->name ?? ($inv->tenant->plan->name ?? 'SaaS Subscription'),
                                            'raw_due_date' => $inv->due_date ? \Carbon\Carbon::parse($inv->due_date)->toDateString() : '',
                                            'raw_paid_at' => $inv->paid_at ? \Carbon\Carbon::parse($inv->paid_at)->toDateString() : '',
                                            'show_url' => route('owner.invoices.show', $inv),
                                            'print_url' => route('owner.invoices.print', $inv),
                                            'toggle_url' => route('owner.billing.toggle-status', $inv),
                                        ]) }}, $event)"
                                        title="Actions"
                                        class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 hover:text-slate-900 border border-slate-200/80 transition inline-flex items-center justify-center cursor-pointer shadow-2xs">
                                    <i class="fas fa-ellipsis-h text-xs"></i>
                                </button>

                                <!-- Hidden Delete Form -->
                                <form id="delete-invoice-form-{{ $inv->id }}" 
                                      action="{{ route('owner.billing.destroy', $inv) }}" 
                                      method="POST" 
                                      class="hidden">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-slate-400 text-xs">
                                No invoices found. Generate an invoice to get started.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Invoices Pagination -->
        <div class="px-4 py-2.5 border-t border-slate-200 bg-slate-50 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs text-slate-600">
            <div>
                Showing <span class="font-bold text-slate-800">{{ $invoices->firstItem() ?? 0 }}</span> to <span class="font-bold text-slate-800">{{ $invoices->lastItem() ?? 0 }}</span> of <span class="font-bold text-slate-800">{{ $invoices->total() }}</span> Invoices
            </div>
            <div>
                {{ $invoices->links() }}
            </div>
        </div>
    </div>

    <!-- Global Floating Actions Dropdown Menu (Free from Table Overflow Clipping) -->
    <div x-show="activeMenuInvoice !== null" 
         @click.outside="activeMenuInvoice = null"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         :style="{ top: menuPos.top, right: menuPos.right }"
         class="fixed z-50 w-44 bg-white rounded-xl shadow-2xl border border-slate-200 py-1 text-left text-xs divide-y divide-slate-100"
         x-cloak>
        
        <!-- Group 1: View & Print -->
        <div class="py-1">
            <button type="button" 
                    @click="if (activeMenuInvoice && activeMenuInvoice.show_url) { window.location.href = activeMenuInvoice.show_url; }" 
                    class="w-full px-3 py-1.5 hover:bg-slate-50 text-slate-700 font-semibold flex items-center gap-2.5 transition text-left cursor-pointer">
                <i class="fas fa-eye text-indigo-600 w-3.5 text-center text-[11px]"></i>
                <span>View Details</span>
            </button>

            <a :href="activeMenuInvoice ? activeMenuInvoice.print_url : '#'" 
               target="_blank"
               class="w-full px-3 py-1.5 hover:bg-blue-50 text-slate-700 hover:text-blue-700 font-semibold flex items-center gap-2.5 transition">
                <i class="fas fa-print text-blue-600 w-3.5 text-center text-[11px]"></i>
                <span>Print Official A4</span>
            </a>
        </div>

        <!-- Group 2: Edit & Status -->
        <div class="py-1">
            <button type="button" 
                    @click="const inv = activeMenuInvoice; activeMenuInvoice = null; openEdit(inv)" 
                    class="w-full px-3 py-1.5 hover:bg-amber-50 text-slate-700 hover:text-amber-700 font-semibold flex items-center gap-2.5 transition">
                <i class="fas fa-edit text-amber-500 w-3.5 text-center text-[11px]"></i>
                <span>Edit Invoice</span>
            </button>

            <form :action="activeMenuInvoice ? activeMenuInvoice.toggle_url : '#'" method="POST">
                @csrf
                <button type="submit" class="w-full px-3 py-1.5 hover:bg-slate-50 text-slate-700 font-semibold flex items-center gap-2.5 transition">
                    <i class="fas w-3.5 text-center text-[11px]" :class="activeMenuInvoice && activeMenuInvoice.status === 'paid' ? 'fa-rotate-left text-amber-500' : 'fa-check text-emerald-500'"></i>
                    <span x-text="activeMenuInvoice && activeMenuInvoice.status === 'paid' ? 'Mark as Unpaid' : 'Mark as Paid'"></span>
                </button>
            </form>
        </div>

        <!-- Group 3: Destructive Action -->
        <div class="py-1">
            <button type="button" 
                    @click="const inv = activeMenuInvoice; activeMenuInvoice = null; confirmDeleteInvoice(inv.id, inv.invoice_no)" 
                    class="w-full px-3 py-1.5 hover:bg-rose-50 text-rose-600 font-semibold flex items-center gap-2.5 transition">
                <i class="fas fa-trash-alt text-rose-500 w-3.5 text-center text-[11px]"></i>
                <span>Delete Invoice</span>
            </button>
        </div>
    </div>

    <!-- 1. Generate New Invoice Modal -->
    <div x-show="createModalOpen" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4"
         x-cloak>
        
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-lg w-full overflow-hidden text-xs"
             @click.outside="createModalOpen = false">
            
            <form action="{{ route('owner.billing.store') }}" method="POST">
                @csrf

                <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center font-bold text-xs shadow-xs">
                            <i class="fas fa-plus"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-900 text-xs">Generate Subscription Invoice</h3>
                            <p class="text-[11px] text-slate-500">Create an invoice for an ISP organization</p>
                        </div>
                    </div>
                    <button type="button" @click="createModalOpen = false" class="p-1 rounded-lg text-slate-400 hover:text-slate-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="p-5 space-y-3.5">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Select ISP Organization *</label>
                        <select name="tenant_id" required class="w-full px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs font-semibold focus:ring-1 focus:ring-blue-500">
                            <option value="">Choose tenant...</option>
                            @foreach($tenants as $t)
                                <option value="{{ $t->id }}">
                                    {{ $t->name }} (Plan: {{ $t->plan->name ?? 'Standard' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">SaaS Plan</label>
                            <select name="saas_plan_id" class="w-full px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs focus:ring-1 focus:ring-blue-500">
                                <option value="">Tenant's Active Plan</option>
                                @foreach($plans as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }} ({{ $currencySymbol ?? '৳' }}{{ number_format($p->monthly_price) }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Billing Amount ({{ $currencySymbol ?? '৳' }}) *</label>
                            <input type="number" step="0.01" name="amount" required placeholder="e.g. 600" class="w-full px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs font-mono font-bold focus:ring-1 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Payment Method</label>
                            <select name="payment_method" class="w-full px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs focus:ring-1 focus:ring-blue-500">
                                <option value="online">Online Gateway</option>
                                <option value="bkash">bKash</option>
                                <option value="nagad">Nagad</option>
                                <option value="bank">Bank Transfer</option>
                                <option value="cash">Cash / Manual</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Payment Due Date</label>
                            <input type="date" name="due_date" value="{{ date('Y-m-d', strtotime('+7 days')) }}" class="w-full px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs focus:ring-1 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Initial Status</label>
                            <select name="status" required class="w-full px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs font-semibold focus:ring-1 focus:ring-blue-500">
                                <option value="unpaid">Unpaid (Pending)</option>
                                <option value="paid">Paid (Settled)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Transaction ID (Trx ID)</label>
                            <input type="text" name="trx_id" placeholder="e.g. TRX99824X" class="w-full px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs font-mono focus:ring-1 focus:ring-blue-500">
                        </div>
                    </div>

                    <div class="p-2.5 rounded-lg bg-blue-50/70 border border-blue-100 flex items-center gap-2">
                        <input type="checkbox" name="auto_extend_tenant" value="1" checked id="auto_extend_check" class="rounded text-blue-600 focus:ring-blue-500">
                        <label for="auto_extend_check" class="text-slate-700 font-medium text-[11px] cursor-pointer">
                            If marked as Paid, automatically extend tenant's subscription by +1 Month
                        </label>
                    </div>

                </div>

                <div class="p-4 bg-slate-50 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="createModalOpen = false" class="px-3.5 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-600 hover:bg-slate-100 font-semibold text-xs transition">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-xs transition flex items-center gap-1.5">
                        <i class="fas fa-check"></i>
                        <span>Generate Invoice</span>
                    </button>
                </div>

            </form>
        </div>
    </div>

    <!-- 2. Edit Invoice Modal -->
    <div x-show="editModalOpen" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
         x-cloak>
        
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-lg w-full overflow-hidden text-xs"
             @click.outside="editModalOpen = false">
            
            <form :action="'/owner/billing/invoices/' + editInvoice.id" method="POST">
                @csrf
                @method('PUT')

                <div class="p-4 bg-slate-800 text-white flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-edit text-amber-400"></i>
                        <span class="font-bold text-xs">Edit Invoice — <span class="font-mono" x-text="editInvoice.invoice_no"></span></span>
                    </div>
                    <button type="button" @click="editModalOpen = false" class="p-1 text-slate-400 hover:text-white transition">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="p-5 space-y-4 text-xs">
                    
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Invoice Number</label>
                            <input type="text" :value="editInvoice.invoice_no" readonly class="w-full px-3 py-1.5 rounded-lg bg-slate-100 border border-slate-200 text-slate-600 font-mono text-xs cursor-not-allowed">
                        </div>

                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Total Amount ({{ $currencyCode ?? 'BDT' }}) *</label>
                            <input type="number" step="0.01" min="0" name="amount" x-model="editInvoice.amount" required class="w-full px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 font-mono font-bold text-xs focus:ring-1 focus:ring-blue-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Payment Status *</label>
                            <select name="status" x-model="editInvoice.status" required class="w-full px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs font-semibold focus:ring-1 focus:ring-blue-500">
                                <option value="unpaid">Unpaid / Due</option>
                                <option value="paid">Paid / Settled</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Due Date</label>
                            <input type="date" name="due_date" x-model="editInvoice.due_date" class="w-full px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs focus:ring-1 focus:ring-blue-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Payment Method</label>
                            <select name="payment_method" x-model="editInvoice.payment_method" class="w-full px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs focus:ring-1 focus:ring-blue-500">
                                <option value="Online">Online Gateway</option>
                                <option value="bKash">bKash</option>
                                <option value="Nagad">Nagad</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Cash">Cash / Hand Payment</option>
                                <option value="Manual">Manual Settlement</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Transaction ID (Trx ID)</label>
                            <input type="text" name="trx_id" x-model="editInvoice.trx_id" placeholder="e.g. TRX99824X" class="w-full px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs font-mono focus:ring-1 focus:ring-blue-500">
                        </div>
                    </div>

                    <div x-show="editInvoice.status === 'paid'">
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Paid Settlement Date</label>
                        <input type="date" name="paid_at" x-model="editInvoice.paid_at" class="w-full px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs focus:ring-1 focus:ring-blue-500">
                    </div>

                </div>

                <div class="p-4 bg-slate-50 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="editModalOpen = false" class="px-3.5 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-600 hover:bg-slate-100 font-semibold text-xs transition">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs shadow-xs transition flex items-center gap-1.5">
                        <i class="fas fa-save"></i>
                        <span>Save Invoice Updates</span>
                    </button>
                </div>

            </form>
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
    function confirmDeleteInvoice(invoiceId, invoiceNo) {
        Swal.fire({
            title: 'Delete Billing Invoice?',
            html: `
                <div class="text-left text-xs space-y-2 mt-2">
                    <p class="text-slate-700">Are you sure you want to permanently delete invoice <strong>"${invoiceNo}"</strong>?</p>
                    <div class="p-2.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-[11.5px]">
                        <i class="fas fa-exclamation-triangle mr-1 text-rose-600"></i>
                        This will remove the transaction record from accounting metrics.
                    </div>
                </div>
            `,
            icon: 'warning',
            iconColor: '#e11d48',
            showCancelButton: true,
            confirmButtonColor: '#e11d48',
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="fas fa-trash-alt mr-1"></i> Yes, Delete Invoice',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            customClass: {
                popup: 'rounded-2xl shadow-2xl border border-slate-200',
                title: 'text-sm font-bold text-slate-900',
                confirmButton: 'rounded-lg text-xs font-bold px-4 py-2 shadow-xs',
                cancelButton: 'rounded-lg text-xs font-semibold px-4 py-2 bg-slate-100 text-slate-700 hover:bg-slate-200 border-0'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('delete-invoice-form-' + invoiceId).submit();
            }
        });
    }
</script>
@endpush
