@extends('owner.layouts.app')

@section('page-title', 'Invoice #' . $invoice->invoice_no)

@section('content')
@php
    $isPaid = $invoice->status === 'paid';
    $isOverdue = !$isPaid && $invoice->due_date && \Carbon\Carbon::parse($invoice->due_date)->isPast();
    $tenant = $invoice->tenant;
    $admin = $tenant ? ($tenant->users->where('role', 'isp_admin')->first() ?? $tenant->users->first()) : null;
    $plan = $invoice->plan ?? ($tenant->plan ?? null);
    $subtotal = (float)($invoice->subtotal ?: $invoice->amount);
    $discount = (float)($invoice->discount ?: 0);
    $tax = (float)($invoice->tax ?: 0);
    $amount = (float)$invoice->amount;
    $paidAmount = $isPaid ? $amount : (float)($invoice->paid_amount ?: 0);
    $balanceDue = $isPaid ? 0 : (float)($invoice->due_amount ?: ($amount - $paidAmount));
@endphp

<div class="max-w-5xl mx-auto space-y-3.5" x-data="{
    editModalOpen: false,
    copiedInvoice: false,
    editInvoice: {
        id: {{ $invoice->id }},
        invoice_no: '{{ addslashes($invoice->invoice_no) }}',
        amount: {{ (float)$invoice->amount }},
        status: '{{ $invoice->status }}',
        due_date: '{{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->toDateString() : '' }}',
        paid_at: '{{ $invoice->paid_at ? \Carbon\Carbon::parse($invoice->paid_at)->toDateString() : '' }}',
        payment_method: '{{ addslashes($invoice->payment_method ?? '') }}',
        trx_id: '{{ addslashes($invoice->trx_id ?? '') }}'
    },
    copyText(text) {
        navigator.clipboard.writeText(text).then(() => {
            this.copiedInvoice = true;
            setTimeout(() => this.copiedInvoice = false, 2000);
        });
    }
}">


    <!-- Toolbar -->
    <div class="bg-white px-4 py-2.5 rounded-xl border border-slate-200 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        
        <!-- Left: Back & Invoice Identifier -->
        <div class="flex items-center gap-2.5">
            <a href="{{ route('owner.billing.index') }}" 
               class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 transition flex items-center justify-center text-xs"
               title="Back to All Invoices">
                <i class="fas fa-arrow-left"></i>
            </a>

            <div class="flex items-center gap-2 flex-wrap">
                <span class="text-xs text-slate-400 font-medium">Invoice:</span>
                <span class="text-xs font-bold text-slate-900 font-mono">{{ $invoice->invoice_no }}</span>
                
                <button type="button" 
                        @click="copyText('{{ $invoice->invoice_no }}')"
                        class="p-1 rounded text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition text-xs"
                        title="Copy Invoice ID">
                    <i class="far fa-copy" x-show="!copiedInvoice"></i>
                    <i class="fas fa-check text-emerald-600 text-[11px]" x-show="copiedInvoice" x-cloak></i>
                </button>

                @if($isPaid)
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10.5px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        Paid
                    </span>
                @elseif($isOverdue)
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10.5px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                        Overdue
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10.5px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                        Unpaid
                    </span>
                @endif
            </div>
        </div>

        <!-- Right: Actions -->
        <div class="flex items-center gap-1.5 flex-wrap">
            <a href="{{ route('owner.invoices.print', $invoice) }}" 
               target="_blank"
               class="px-2.5 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs transition inline-flex items-center gap-1.5 shadow-2xs">
                <i class="fas fa-print text-[11px]"></i>
                <span>Print A4</span>
            </a>

            @if($tenant && ($tenant->email || ($admin && $admin->email)))
                @php
                    $targetEmailTop = $tenant->email ?: ($admin->email ?? null);
                    $billingFromTop = $globalSettings['billing_email'] ?? $globalSettings['company_email'] ?? 'billing@somitysoft.com';
                @endphp
                <form action="{{ route('owner.invoices.send-email', $invoice) }}" method="POST" class="inline" onsubmit="event.preventDefault(); confirmSendInvoiceEmail(this, '{{ $invoice->invoice_no }}', '{{ $billingFromTop }}', '{{ $targetEmailTop }}');">
                    @csrf
                    <button type="submit" 
                            class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 hover:bg-blue-50 hover:border-blue-200 text-slate-700 hover:text-blue-700 font-semibold text-xs transition inline-flex items-center gap-1.5 shadow-2xs cursor-pointer"
                            title="Dispatch official invoice email from {{ $billingFromTop }}">
                        <i class="fas fa-paper-plane text-blue-600 text-[10px]"></i>
                        <span>Email Invoice</span>
                    </button>
                </form>
            @endif

            <form action="{{ route('owner.billing.toggle-status', $invoice) }}" method="POST" class="inline">
                @csrf
                <button type="submit" 
                        class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold text-xs transition inline-flex items-center gap-1.5 shadow-2xs">
                    <i class="fas {{ $isPaid ? 'fa-rotate-left text-amber-500' : 'fa-check text-emerald-500' }} text-[11px]"></i>
                    <span>{{ $isPaid ? 'Mark Unpaid' : 'Mark Paid' }}</span>
                </button>
            </form>

            <button type="button" 
                    @click="editModalOpen = true"
                    class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold text-xs transition inline-flex items-center gap-1.5 shadow-2xs">
                <i class="fas fa-pen text-slate-400 text-[10px]"></i>
                <span>Edit</span>
            </button>

            <button type="button" 
                    @click="confirmDeleteInvoice({{ $invoice->id }}, '{{ addslashes($invoice->invoice_no) }}')"
                    class="p-1.5 rounded-lg bg-white hover:bg-rose-50 border border-slate-200 hover:border-rose-200 text-slate-400 hover:text-rose-600 transition shadow-2xs"
                    title="Delete Invoice">
                <i class="fas fa-trash-alt text-xs"></i>
            </button>

            <form id="delete-invoice-form-{{ $invoice->id }}" 
                  action="{{ route('owner.billing.destroy', $invoice) }}" 
                  method="POST" 
                  class="hidden">
                @csrf
                @method('DELETE')
            </form>
        </div>

    </div>

    <!-- Main Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-3.5 items-start">
        
        <!-- Left: Invoice Document (8 cols) -->
        <div class="lg:col-span-8 bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
            
            <div class="p-4 sm:p-6 space-y-4">
                
                <!-- Document Header -->
                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 border-b border-slate-100 pb-3.5">
                    
                    <!-- Company Info -->
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            @if(!empty($globalSettings['app_logo']))
                                <img src="{{ $globalSettings['app_logo'] }}" alt="{{ $globalSettings['app_name'] ?? 'Logo' }}" class="h-7 w-7 object-contain rounded">
                            @endif
                            @if(!empty($globalSettings['app_name']))
                                <span class="text-xs font-bold text-slate-900 tracking-tight">
                                    {{ $globalSettings['app_name'] }}
                                </span>
                            @endif
                        </div>

                        <div class="text-[11px] text-slate-500 leading-tight pt-0.5">
                            @if(!empty($globalSettings['company_address']))
                                <div>{{ $globalSettings['company_address'] }}</div>
                            @endif
                            @if(!empty($globalSettings['billing_email']) || !empty($globalSettings['company_email']))
                                <div>Email: {{ $globalSettings['billing_email'] ?? $globalSettings['company_email'] }}</div>
                            @endif
                            @if(!empty($globalSettings['support_phone']))
                                <div>Phone: {{ $globalSettings['support_phone'] }}</div>
                            @endif
                        </div>
                    </div>

                    <!-- Invoice Header Meta -->
                    <div class="sm:text-right space-y-1">
                        <div class="flex items-center sm:justify-end gap-1.5">
                            <span class="text-xs font-extrabold uppercase tracking-wide text-slate-800">INVOICE</span>
                            @if($isPaid)
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase bg-emerald-100 text-emerald-800">Paid</span>
                            @elseif($isOverdue)
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase bg-rose-100 text-rose-800">Overdue</span>
                            @else
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase bg-amber-100 text-amber-800">Due</span>
                            @endif
                        </div>
                        <div class="font-mono text-[11px] font-bold text-slate-600">#{{ $invoice->invoice_no }}</div>
                        <div class="text-[11px] text-slate-500 space-y-0.5 pt-0.5">
                            <div>Issued: <span class="font-mono text-slate-700 font-medium">{{ $invoice->created_at ? $invoice->created_at->format('d M, Y') : '—' }}</span></div>
                            <div>Due: <span class="font-mono {{ $isOverdue ? 'text-rose-600 font-bold' : 'text-slate-700 font-medium' }}">{{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('d M, Y') : '—' }}</span></div>
                        </div>
                    </div>

                </div>

                <!-- Parties & Payment Details -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 bg-slate-50/75 p-3 rounded-lg border border-slate-100 text-xs">
                    
                    <!-- Billed To -->
                    <div class="space-y-1">
                        <div class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Billed To</div>
                        @if($tenant)
                            <div class="font-bold text-slate-900 text-xs flex items-center gap-1">
                                <span>{{ $tenant->company_name ?: $tenant->name }}</span>
                                <a href="{{ route('owner.tenants.show', $tenant) }}" class="text-blue-600 hover:text-blue-800 text-[10px]" title="View Client Details">
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                            </div>

                            <div class="text-[11px] text-slate-500 leading-tight pt-0.5">
                                @if(!empty($tenant->email))
                                    <div>{{ $tenant->email }}</div>
                                @elseif($admin && !empty($admin->email))
                                    <div>{{ $admin->email }}</div>
                                @endif
                                @if(!empty($tenant->phone))
                                    <div>{{ $tenant->phone }}</div>
                                @endif
                                @if(!empty($tenant->address))
                                    <div>{{ $tenant->address }}</div>
                                @endif
                            </div>
                        @else
                            <div class="text-[11px] text-slate-400 italic">Client record not available</div>
                        @endif
                    </div>

                    <!-- Payment Information -->
                    <div class="space-y-1 sm:border-l sm:border-slate-200/80 sm:pl-3">
                        <div class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Payment Details</div>
                        <div class="text-[11px] space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500">Method:</span>
                                <span class="font-medium text-slate-800 uppercase">{{ $invoice->payment_method ?: '—' }}</span>
                            </div>
                            @if(!empty($invoice->trx_id))
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-500">Trx ID:</span>
                                    <span class="font-mono text-slate-800 font-semibold">{{ $invoice->trx_id }}</span>
                                </div>
                            @endif
                            @if($plan)
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-500">Plan:</span>
                                    <span class="font-semibold text-slate-800">{{ $plan->name }}</span>
                                </div>
                            @endif
                            @if($invoice->paid_at)
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-500">Paid Date:</span>
                                    <span class="font-mono text-slate-700">{{ \Carbon\Carbon::parse($invoice->paid_at)->format('d M, Y (h:i A)') }}</span>
                                </div>
                            @endif
                        </div>
                    </div>

                </div>

                <!-- Line Items Table -->
                <div class="overflow-hidden rounded-lg border border-slate-200">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead class="bg-slate-50/90 text-slate-600 uppercase text-[10px] font-bold tracking-wider border-b border-slate-200">
                            <tr>
                                <th class="py-2.5 px-3 w-8 text-center text-slate-400">#</th>
                                <th class="py-2.5 px-3 text-left">Description</th>
                                <th class="py-2.5 px-3 text-center whitespace-nowrap w-16">Qty</th>
                                <th class="py-2.5 px-3 text-right whitespace-nowrap">Unit Price</th>
                                <th class="py-2.5 px-3 text-right whitespace-nowrap">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @if($invoice->items && $invoice->items->count() > 0)
                                @foreach($invoice->items as $idx => $item)
                                    <tr class="hover:bg-slate-50/50 transition">
                                        <td class="py-2.5 px-3 text-center text-slate-400 font-mono align-top">{{ $idx + 1 }}</td>
                                        <td class="py-2.5 px-3 text-left align-top">
                                            <div class="">{{ $item->description }}</div>
                                        </td>
                                        <td class="py-2.5 px-3 text-center text-slate-600 font-medium whitespace-nowrap align-top">
                                            {{ $item->quantity }}
                                        </td>
                                        <td class="py-2.5 px-3 text-right font-mono text-slate-700 whitespace-nowrap align-top">
                                            @currency($item->unit_price)
                                        </td>
                                        <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-900 whitespace-nowrap align-top">
                                            @currency($item->total_price)
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr class="hover:bg-slate-50/50 transition">
                                    <td class="py-2.5 px-3 text-center text-slate-400 font-mono align-top">1</td>
                                    <td class="py-2.5 px-3 text-left align-top">
                                        <div class="font-semibold text-slate-900 text-xs">
                                            {{ $invoice->notes ?: ($plan ? ($plan->name . ' Subscription') : 'Subscription') }}
                                        </div>
                                    </td>
                                    <td class="py-2.5 px-3 text-center text-slate-600 font-medium whitespace-nowrap align-top">
                                        1
                                    </td>
                                    <td class="py-2.5 px-3 text-right font-mono text-slate-700 whitespace-nowrap align-top">
                                        @currency($invoice->amount)
                                    </td>
                                    <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-900 whitespace-nowrap align-top">
                                        @currency($invoice->amount)
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                <!-- Ledger & Notes -->
                <div class="grid grid-cols-1 sm:grid-cols-12 gap-4 pt-1">
                    
                    <!-- Left: Amount in Words & Notes -->
                    <div class="sm:col-span-7 space-y-2">
                        <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-100 text-xs space-y-0.5">
                            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider block">In Words:</span>
                            <span class="font-medium text-slate-700 italic text-[11px] block">{{ $amountInWords }}</span>
                        </div>

                        @if(!empty($invoice->notes))
                            <div class="text-[11px] text-slate-500 pt-1">
                                <span class="font-semibold text-slate-700">Note:</span> {{ $invoice->notes }}
                            </div>
                        @endif
                    </div>

                    <!-- Right: Summary Totals -->
                    <div class="sm:col-span-5 space-y-1.5 text-xs">
                        <div class="flex items-center justify-between text-slate-500">
                            <span>Subtotal:</span>
                            <span class="font-mono font-medium text-slate-800 whitespace-nowrap">@currency($subtotal)</span>
                        </div>

                        @if($discount > 0)
                            <div class="flex items-center justify-between text-emerald-600">
                                <span>Discount:</span>
                                <span class="font-mono font-medium whitespace-nowrap">- @currency($discount)</span>
                            </div>
                        @endif

                        @if($tax > 0)
                            <div class="flex items-center justify-between text-slate-500">
                                <span>Tax / VAT:</span>
                                <span class="font-mono text-slate-600 whitespace-nowrap">@currency($tax)</span>
                            </div>
                        @endif

                        <div class="flex items-center justify-between text-slate-900 font-bold border-t border-slate-200 pt-1.5 pb-0.5 text-xs">
                            <span>Total Amount:</span>
                            <span class="font-mono text-blue-700 text-sm font-bold whitespace-nowrap">@currency($amount)</span>
                        </div>

                        <div class="flex items-center justify-between text-slate-600 text-[11px]">
                            <span>Paid Amount:</span>
                            <span class="font-mono font-medium {{ $isPaid ? 'text-emerald-700' : 'text-slate-700' }} whitespace-nowrap">
                                @currency($paidAmount)
                            </span>
                        </div>

                        <div class="flex items-center justify-between border-t border-dashed border-slate-200 pt-1 font-semibold text-xs {{ $balanceDue > 0 ? 'text-rose-600' : 'text-slate-500' }}">
                            <span>Balance Due:</span>
                            <span class="font-mono font-bold whitespace-nowrap">@currency($balanceDue)</span>
                        </div>
                    </div>

                </div>

            </div>

        </div>

        <!-- Right: Actions & Client Sidebar (4 cols) -->
        <div class="lg:col-span-4 space-y-3">
            
            <!-- Quick Settlement (If Unpaid) -->
            @if(!$isPaid)
                <div class="bg-white rounded-xl border border-amber-200 shadow-2xs overflow-hidden">
                    <div class="px-3.5 py-2 bg-amber-50/80 border-b border-amber-200/60 flex items-center justify-between">
                        <span class="text-xs font-bold text-amber-900 flex items-center gap-1.5">
                            <i class="fas fa-hand-holding-usd text-amber-600"></i>
                            <span>Record Payment</span>
                        </span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-amber-200/70 text-amber-900">
                            Due: @currency($balanceDue)
                        </span>
                    </div>

                    <form action="{{ route('owner.billing.update', $invoice) }}" method="POST" class="p-3 space-y-2.5 text-xs">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="redirect_to" value="show">
                        <input type="hidden" name="status" value="paid">
                        <input type="hidden" name="amount" value="{{ $invoice->amount }}">
                        <input type="hidden" name="due_date" value="{{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->toDateString() : '' }}">

                        <div>
                            <label class="block text-[10.5px] font-semibold text-slate-600 mb-0.5">Method</label>
                            <select name="payment_method" class="w-full px-2.5 py-1 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs focus:ring-1 focus:ring-blue-500">
                                <option value="bKash">bKash</option>
                                <option value="Nagad">Nagad</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Online">Online Gateway</option>
                                <option value="Cash">Cash</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[10.5px] font-semibold text-slate-600 mb-0.5">Trx ID</label>
                            <input type="text" name="trx_id" placeholder="Transaction ID" class="w-full px-2.5 py-1 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs font-mono focus:ring-1 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-[10.5px] font-semibold text-slate-600 mb-0.5">Settlement Date</label>
                            <input type="date" name="paid_at" value="{{ date('Y-m-d') }}" class="w-full px-2.5 py-1 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs focus:ring-1 focus:ring-blue-500">
                        </div>

                        <button type="submit" class="w-full py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-2xs transition flex items-center justify-center gap-1">
                            <i class="fas fa-check-circle text-[11px]"></i>
                            <span>Save Payment</span>
                        </button>
                    </form>
                </div>
            @else
                <!-- Settlement Details (If Paid) -->
                <div class="bg-white rounded-xl border border-emerald-200 shadow-2xs p-3 space-y-2">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                        <span class="text-xs font-bold text-emerald-800 flex items-center gap-1.5">
                            <i class="fas fa-check-circle text-emerald-600"></i>
                            <span>Payment Completed</span>
                        </span>
                        <span class="font-mono font-bold text-emerald-700 text-xs">@currency($invoice->amount)</span>
                    </div>

                    <div class="text-[11px] space-y-1 text-slate-600">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400">Method:</span>
                            <span class="font-semibold text-slate-800 uppercase">{{ $invoice->payment_method ?: '—' }}</span>
                        </div>
                        @if($invoice->trx_id)
                            <div class="flex items-center justify-between">
                                <span class="text-slate-400">Trx ID:</span>
                                <span class="font-mono text-slate-800 font-semibold">{{ $invoice->trx_id }}</span>
                            </div>
                        @endif
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400">Paid At:</span>
                            <span class="font-mono text-slate-700">{{ $invoice->paid_at ? \Carbon\Carbon::parse($invoice->paid_at)->format('d M, Y h:i A') : '—' }}</span>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Client Snapshot -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-2xs p-3 space-y-2">
                <div class="flex items-center justify-between border-b border-slate-100 pb-1.5">
                    <span class="text-[10.5px] font-bold text-slate-500 uppercase tracking-wider">Client</span>
                    @if($tenant)
                        <a href="{{ route('owner.tenants.show', $tenant) }}" class="text-[10.5px] text-blue-600 hover:underline font-semibold">View &rarr;</a>
                    @endif
                </div>

                @if($tenant)
                    <div class="text-xs space-y-1.5">
                        <div>
                            <div class="font-bold text-slate-900 text-xs">{{ $tenant->name }}</div>
                            @if(!empty($tenant->domain))
                                <a href="https://{{ $tenant->domain }}" target="_blank" class="text-[10.5px] text-blue-600 hover:underline font-mono">
                                    {{ $tenant->domain }}
                                </a>
                            @endif
                        </div>

                        <div class="grid grid-cols-2 gap-1.5 pt-0.5">
                            @if($tenant->phone)
                                <a href="tel:{{ $tenant->phone }}" class="py-1 px-2 rounded bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-700 text-center flex items-center justify-center gap-1 text-[10.5px] font-medium transition">
                                    <i class="fas fa-phone text-emerald-600 text-[9px]"></i>
                                    <span>Call</span>
                                </a>
                            @endif
                            @if($tenant->email || ($admin && $admin->email))
                                @php
                                    $targetEmailCard = $tenant->email ?: ($admin->email ?? null);
                                    $billingFromCard = $globalSettings['billing_email'] ?? $globalSettings['company_email'] ?? 'billing@somitysoft.com';
                                @endphp
                                <form action="{{ route('owner.invoices.send-email', $invoice) }}" method="POST" class="inline" onsubmit="event.preventDefault(); confirmSendInvoiceEmail(this, '{{ $invoice->invoice_no }}', '{{ $billingFromCard }}', '{{ $targetEmailCard }}');">
                                    @csrf
                                    <button type="submit" class="w-full py-1 px-2 rounded bg-blue-50 hover:bg-blue-100 border border-blue-200 text-blue-700 text-center flex items-center justify-center gap-1 text-[10.5px] font-medium transition cursor-pointer" title="Send official billing email to {{ $targetEmailCard }} from {{ $billingFromCard }}">
                                        <i class="fas fa-paper-plane text-blue-600 text-[9px]"></i>
                                        <span>Send Invoice Email</span>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="text-[11px] text-slate-400 italic">No client record found</div>
                @endif
            </div>

        </div>

    </div>

    <!-- Edit Invoice Modal -->
    <div x-show="editModalOpen" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs"
         x-cloak>
        <div @click.outside="editModalOpen = false" 
             class="bg-white rounded-xl shadow-xl border border-slate-200 w-full max-w-md overflow-hidden">
            
            <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/80 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-slate-900">Edit Invoice Record</span>
                    <span class="text-[10px] text-slate-400 font-mono">#{{ $invoice->invoice_no }}</span>
                </div>
                <button type="button" @click="editModalOpen = false" class="text-slate-400 hover:text-slate-600">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <form action="{{ route('owner.billing.update', $invoice) }}" method="POST" class="p-4 space-y-3 text-xs">
                @csrf
                @method('PUT')
                <input type="hidden" name="redirect_to" value="show">

                <div>
                    <label class="block font-semibold text-slate-700 text-[11px] mb-1">Invoice Amount (৳)</label>
                    <input type="number" 
                           step="0.01" 
                           name="amount" 
                           x-model="editInvoice.amount" 
                           required 
                           class="w-full px-2.5 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs font-mono font-bold focus:ring-1 focus:ring-blue-500 focus:outline-none">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 text-[11px] mb-1">Payment Status</label>
                    <select name="status" 
                            x-model="editInvoice.status" 
                            class="w-full px-2.5 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none">
                        <option value="unpaid">Unpaid / Due</option>
                        <option value="paid">Paid</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <label class="block font-semibold text-slate-700 text-[11px] mb-1">Payment Method</label>
                        <select name="payment_method" 
                                x-model="editInvoice.payment_method" 
                                class="w-full px-2.5 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none">
                            <option value="Online">Online</option>
                            <option value="bKash">bKash</option>
                            <option value="Nagad">Nagad</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="Cash">Cash</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 text-[11px] mb-1">Trx ID</label>
                        <input type="text" 
                               name="trx_id" 
                               x-model="editInvoice.trx_id" 
                               placeholder="TRX-12345" 
                               class="w-full px-2.5 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs font-mono focus:ring-1 focus:ring-blue-500 focus:outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <label class="block font-semibold text-slate-700 text-[11px] mb-1">Due Date</label>
                        <input type="date" 
                               name="due_date" 
                               x-model="editInvoice.due_date" 
                               class="w-full px-2.5 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 text-[11px] mb-1">Paid Date</label>
                        <input type="date" 
                               name="paid_at" 
                               x-model="editInvoice.paid_at" 
                               class="w-full px-2.5 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" 
                            @click="editModalOpen = false" 
                            class="px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 font-semibold text-xs transition">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-3.5 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs transition flex items-center gap-1 shadow-2xs">
                        <i class="fas fa-save text-[11px]"></i>
                        <span>Save Changes</span>
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
                    <div class="p-2 rounded-lg bg-rose-50 border border-rose-200 text-rose-800 text-[11px]">
                        <i class="fas fa-exclamation-triangle mr-1 text-rose-600"></i>
                        This will remove the transaction record from metrics.
                    </div>
                </div>
            `,
            icon: 'warning',
            iconColor: '#e11d48',
            showCancelButton: true,
            confirmButtonColor: '#e11d48',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, Delete Invoice',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            customClass: {
                popup: 'rounded-xl shadow-xl border border-slate-200',
                title: 'text-xs font-bold text-slate-900',
                confirmButton: 'rounded-lg text-xs font-bold px-3 py-1.5 shadow-2xs',
                cancelButton: 'rounded-lg text-xs font-semibold px-3 py-1.5 bg-slate-100 text-slate-700 hover:bg-slate-200 border-0'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('delete-invoice-form-' + invoiceId).submit();
            }
        });
    }

    function confirmSendInvoiceEmail(formElement, invoiceNo, fromEmail, toEmail) {
        Swal.fire({
            title: 'Send Official Invoice Email?',
            html: `
                <div class="text-left text-xs space-y-2.5 mt-2">
                    <p class="text-slate-600 leading-relaxed">
                        Are you sure you want to dispatch official invoice <strong class="text-slate-900 font-mono">#${invoiceNo}</strong> directly to this client?
                    </p>
                    <div class="bg-slate-50 border border-slate-200 rounded-lg p-2.5 space-y-1.5 text-[11px]">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500 font-medium">From (Billing Email):</span>
                            <span class="font-mono text-blue-600 font-semibold">${fromEmail}</span>
                        </div>
                        <div class="flex items-center justify-between border-t border-slate-200/60 pt-1.5">
                            <span class="text-slate-500 font-medium">To (Client Email):</span>
                            <span class="font-mono text-slate-800 font-semibold">${toEmail}</span>
                        </div>
                    </div>
                </div>
            `,
            icon: 'question',
            iconColor: '#2563eb',
            showCancelButton: true,
            confirmButtonColor: '#2563eb',
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="fas fa-paper-plane mr-1.5 text-xs"></i> Yes, Send Email',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            customClass: {
                popup: 'rounded-xl shadow-xl border border-slate-200',
                title: 'text-sm font-bold text-slate-900',
                confirmButton: 'rounded-lg text-xs font-bold px-3.5 py-2 shadow-2xs bg-blue-600 hover:bg-blue-700 text-white',
                cancelButton: 'rounded-lg text-xs font-semibold px-3.5 py-2 bg-slate-100 text-slate-700 hover:bg-slate-200 border-0'
            },
            showLoaderOnConfirm: true,
            preConfirm: () => {
                formElement.submit();
            }
        });
    }
</script>
@endpush
