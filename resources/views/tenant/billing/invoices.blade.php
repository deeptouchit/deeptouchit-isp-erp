@extends('tenant.layouts.app')

@section('title', 'Subscription Invoices - ' . ($tenant->company_name ?? $tenant->name ?? 'ISP Portal'))

@section('content')
<div class="space-y-3">
    
    <!-- 1. Top Compact Header Bar (Strictly Icon + Title + Actions ONLY - No Subtitle) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-sm shadow-xs flex-shrink-0">
                <i class="fas fa-file-invoice"></i>
            </div>
            <div>
                <h2 class="text-sm font-bold text-slate-900 tracking-tight">SaaS Subscription Invoices Registry</h2>
            </div>
        </div>

        <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap flex-shrink-0">
            @if($totalDueAmount > 0 && $latestDueInvoice)
                <a href="{{ route('tenant.billing.invoice.show', $latestDueInvoice) }}" 
                   class="px-3 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white font-semibold text-xs shadow-2xs transition inline-flex items-center gap-1.5 cursor-pointer">
                    <i class="fas fa-credit-card text-[11px]"></i>
                    <span>Pay Due (@currency($totalDueAmount))</span>
                </a>
            @endif

            <a href="{{ route('tenant.billing.dashboard') }}" 
               class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition inline-flex items-center gap-1.5 shadow-2xs">
                <i class="fas fa-arrow-left text-[11px]"></i>
                <span>Plan Overview</span>
            </a>
        </div>
    </div>

    <!-- Outstanding Due Alert Banner (if any due exists) -->
    @if($totalDueAmount > 0 && $latestDueInvoice)
        <div class="p-3.5 rounded-xl bg-amber-50/90 border border-amber-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs shadow-xs">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center text-xs flex-shrink-0">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div>
                    <span class="font-bold text-amber-900 block text-xs">Outstanding Due: @currency($totalDueAmount)</span>
                    <span class="text-[11px] text-amber-800 font-normal">
                        Invoice #{{ $latestDueInvoice->invoice_no }} • Due Date: {{ $latestDueInvoice->due_date ? \Carbon\Carbon::parse($latestDueInvoice->due_date)->format('d M Y') : 'Immediate' }}
                    </span>
                </div>
            </div>
            <div>
                <a href="{{ route('tenant.billing.invoice.show', $latestDueInvoice) }}" 
                   class="px-3.5 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white font-semibold text-xs shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                    <i class="fas fa-credit-card text-[11px]"></i>
                    <span>Pay Online (bKash / Nagad)</span>
                    <i class="fas fa-arrow-right text-[10px]"></i>
                </a>
            </div>
        </div>
    @endif

    <!-- 2. Ultra-Compact KPI Summary Strip (Strictly 6 Cards, px-2.5 py-1.5, Label text-[9px], Value text-[13px] font-mono) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        
        <!-- Metric 1: Total Invoices -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium text-slate-500 uppercase tracking-wider block truncate">Total Invoices</span>
                <span class="text-[13px] font-bold text-slate-800 tracking-tight font-mono leading-tight block">{{ number_format($totalInvoicesCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-cyan-50 text-cyan-600 flex items-center justify-center text-[10px] border border-cyan-100 flex-shrink-0">
                <i class="fas fa-file-invoice"></i>
            </div>
        </div>

        <!-- Metric 2: Settled Invoices -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium text-emerald-700 uppercase tracking-wider block truncate">Settled Invoices</span>
                <span class="text-[13px] font-bold text-emerald-600 tracking-tight font-mono leading-tight block">{{ number_format($paidInvoicesCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-emerald-50 text-emerald-600 flex items-center justify-center text-[10px] border border-emerald-100 flex-shrink-0">
                <i class="fas fa-check-circle"></i>
            </div>
        </div>

        <!-- Metric 3: Unpaid Invoices -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium text-rose-700 uppercase tracking-wider block truncate">Unpaid Invoices</span>
                <span class="text-[13px] font-bold text-rose-600 tracking-tight font-mono leading-tight block">{{ number_format($unpaidInvoicesCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-rose-50 text-rose-600 flex items-center justify-center text-[10px] border border-rose-100 flex-shrink-0">
                <i class="fas fa-clock"></i>
            </div>
        </div>

        <!-- Metric 4: Total Invoiced -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium text-slate-500 uppercase tracking-wider block truncate">Total Invoiced</span>
                <span class="text-[13px] font-bold text-slate-800 tracking-tight font-mono leading-tight block">@currency($totalInvoicedAmount)</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-purple-50 text-purple-600 flex items-center justify-center text-[10px] border border-purple-100 flex-shrink-0">
                <i class="fas fa-receipt"></i>
            </div>
        </div>

        <!-- Metric 5: Total Settled -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium text-emerald-700 uppercase tracking-wider block truncate">Total Settled</span>
                <span class="text-[13px] font-bold text-emerald-600 tracking-tight font-mono leading-tight block">@currency($totalPaidAmount)</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-emerald-50 text-emerald-600 flex items-center justify-center text-[10px] border border-emerald-100 flex-shrink-0">
                <i class="fas fa-money-bill-wave"></i>
            </div>
        </div>

        <!-- Metric 6: Outstanding Due -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium text-amber-700 uppercase tracking-wider block truncate">Outstanding Due</span>
                <span class="text-[13px] font-bold text-amber-600 tracking-tight font-mono leading-tight block">@currency($totalDueAmount)</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-amber-50 text-amber-600 flex items-center justify-center text-[10px] border border-amber-100 flex-shrink-0">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
        </div>
    </div>

    <!-- 3. Search & Filter Toolbar -->
    <div class="bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
        <form method="GET" action="{{ url()->current() }}" class="flex flex-col sm:flex-row items-center gap-2.5">
            
            <!-- Search Text -->
            <div class="relative flex-1 w-full">
                <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" 
                       name="search" 
                       value="{{ $search }}" 
                       placeholder="Search by invoice number or subscription plan..." 
                       class="w-full pl-8 pr-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg text-slate-800 placeholder-slate-400 focus:bg-white focus:border-cyan-500 focus:outline-none">
            </div>

            <!-- Status Filter -->
            <div class="w-full sm:w-44">
                <select name="status" 
                        onchange="this.form.submit()" 
                        class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 text-slate-700 font-medium focus:bg-white focus:border-cyan-500 focus:outline-none">
                    <option value="">All Statuses</option>
                    <option value="paid" {{ $status === 'paid' ? 'selected' : '' }}>🟢 Paid Invoices</option>
                    <option value="unpaid" {{ in_array($status, ['unpaid', 'pending']) ? 'selected' : '' }}>🔴 Unpaid Invoices</option>
                    <option value="partially_paid" {{ $status === 'partially_paid' ? 'selected' : '' }}>🟡 Partially Paid</option>
                </select>
            </div>

            <!-- Filter & Reset Buttons (Strict Universal Standard) -->
            <div class="flex items-center gap-1.5 w-full sm:w-auto flex-shrink-0">
                <button type="submit" 
                        class="flex-1 sm:flex-initial bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-1.5 px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer" 
                        title="Apply Filters">
                    <i class="fas fa-filter text-[10px]"></i>
                    <span>Filter</span>
                </button>
                <a href="{{ route('tenant.billing.invoices') }}" 
                   class="flex-1 sm:flex-initial bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-1.5 px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer" 
                   title="Reset Filters">
                    <i class="fas fa-rotate-left text-[10px]"></i>
                    <span>Reset</span>
                </a>
            </div>
        </form>
    </div>

    <!-- 4. Master Compact Table (Strictly UI_DESIGN.md Compliant - Single Line, NO BOLD TEXT) -->
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-xs">
        
        <div class="px-3.5 py-2 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
            <span class="font-normal text-slate-700 text-xs flex items-center gap-1.5">
                <i class="fas fa-list text-slate-400 text-xs"></i>
                <span>SaaS Subscription Invoices Registry</span>
            </span>
            <span class="text-[11px] text-slate-500 font-mono font-normal">
                Total {{ $invoices->total() }} Invoices
            </span>
        </div>

        <div class="overflow-x-auto">
            <table id="invoices-table" class="w-full text-left whitespace-nowrap border-collapse border border-slate-200">
                <thead class="bg-slate-100 text-slate-700 uppercase text-[11px] font-normal tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="px-2.5 py-2 text-center w-10 border border-slate-200 font-normal text-[11px]">#</th>
                        <th class="px-2.5 py-2 text-left border border-slate-200 font-normal text-[11px]">Invoice #</th>
                        <th class="px-2.5 py-2 text-left border border-slate-200 font-normal text-[11px]">Plan / Description</th>
                        <th class="px-2.5 py-2 text-left border border-slate-200 font-normal text-[11px]">Issue Date</th>
                        <th class="px-2.5 py-2 text-left border border-slate-200 font-normal text-[11px]">Due Date</th>
                        <th class="px-2.5 py-2 text-left border border-slate-200 font-normal text-[11px]">Total Amount</th>
                        <th class="px-2.5 py-2 text-left border border-slate-200 font-normal text-[11px]">Paid Amount</th>
                        <th class="px-2.5 py-2 text-left border border-slate-200 font-normal text-[11px]">Due</th>
                        <th class="px-2.5 py-2 text-center border border-slate-200 font-normal text-[11px]">Status</th>
                        <th class="px-2.5 py-2 text-center border border-slate-200 font-normal text-[11px] w-20">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-[11px] font-normal text-slate-700">
                    @forelse($invoices as $index => $inv)
                        @php
                            $calculatedDue = (float) $inv->amount - (float) $inv->paid_amount;
                            if ($calculatedDue < 0) $calculatedDue = 0;
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition whitespace-nowrap">
                            
                            <!-- 1. Index -->
                            <td class="px-2.5 py-1.5 text-center text-slate-400 font-mono text-[11px] border border-slate-200 font-normal">
                                {{ $invoices->firstItem() + $index }}
                            </td>

                            <!-- 2. Invoice # (Single-data) -->
                            <td class="px-2.5 py-1.5 border border-slate-200 font-normal font-mono text-cyan-800">
                                {{ $inv->invoice_no }}
                            </td>

                            <!-- 3. Plan / Description (Single-data) -->
                            <td class="px-2.5 py-1.5 border border-slate-200 font-normal text-slate-800">
                                {{ $inv->plan->name ?? 'SaaS Subscription' }}
                            </td>

                            <!-- 4. Issue Date (Single-data) -->
                            <td class="px-2.5 py-1.5 border border-slate-200 font-normal font-mono text-slate-600">
                                {{ $inv->created_at ? $inv->created_at->format('d M Y') : '--' }}
                            </td>

                            <!-- 5. Due Date (Single-data) -->
                            <td class="px-2.5 py-1.5 border border-slate-200 font-normal font-mono text-slate-600">
                                {{ $inv->due_date ? \Carbon\Carbon::parse($inv->due_date)->format('d M Y') : '--' }}
                            </td>

                            <!-- 6. Total Amount (Single-data) -->
                            <td class="px-2.5 py-1.5 border border-slate-200 font-normal font-mono text-slate-800">
                                @currency($inv->amount)
                            </td>

                            <!-- 7. Paid Amount (Single-data) -->
                            <td class="px-2.5 py-1.5 border border-slate-200 font-normal font-mono text-emerald-700">
                                @currency($inv->paid_amount)
                            </td>

                            <!-- 8. Remaining Due (Single-data) -->
                            <td class="px-2.5 py-1.5 border border-slate-200 font-normal font-mono {{ $calculatedDue > 0 ? 'text-amber-700 bg-amber-50/50 font-semibold' : 'text-slate-400' }}">
                                @currency($calculatedDue)
                            </td>

                            <!-- 9. Status (Single-data) -->
                            <td class="px-2.5 py-1.5 text-center border border-slate-200 font-normal">
                                @if($inv->status === 'paid')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-normal bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        <span>Paid</span>
                                    </span>
                                @elseif($inv->status === 'partially_paid')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-normal bg-amber-50 text-amber-700 border border-amber-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        <span>Partial</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-normal bg-rose-50 text-rose-700 border border-rose-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        <span>Unpaid</span>
                                    </span>
                                @endif
                            </td>

                            <!-- 10. Action (Single-data) -->
                            <td class="px-2.5 py-1.5 text-center border border-slate-200 font-normal">
                                <a href="{{ route('tenant.billing.invoice.show', $inv) }}" 
                                   class="px-2.5 py-1 rounded-md text-[10.5px] font-semibold {{ $calculatedDue > 0 ? 'bg-amber-600 hover:bg-amber-700 text-white shadow-2xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200' }} transition inline-flex items-center gap-1">
                                    <i class="fas {{ $calculatedDue > 0 ? 'fa-credit-card' : 'fa-eye' }} text-[9px]"></i>
                                    <span>{{ $calculatedDue > 0 ? 'Pay Online' : 'View Voucher' }}</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="p-8 text-center border border-slate-200 font-normal">
                                <div class="max-w-md mx-auto space-y-2">
                                    <div class="w-10 h-10 rounded-xl bg-cyan-50 text-cyan-600 mx-auto flex items-center justify-center text-base border border-cyan-100">
                                        <i class="fas fa-file-invoice"></i>
                                    </div>
                                    <h3 class="text-xs font-normal text-slate-800">No Invoices Found</h3>
                                    <p class="text-[11px] text-slate-400 font-normal">No subscription invoices match the filter criteria.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($invoices->hasPages())
            <div class="p-3 border-t border-slate-200 bg-slate-50">
                {{ $invoices->links() }}
            </div>
        @endif
    </div>

</div>
@push('scripts')
<script>
$(document).ready(function() {
    if ($('#invoices-table').length && !$('#invoices-table tbody td[colspan]').length) {
        $('#invoices-table').DataTable({
            paging: false,
            info: false,
            searching: false,
            ordering: true,
            order: [],
            columnDefs: [
                { orderable: false, targets: [0, -1] }
            ]
        });
    }
});
</script>
@endpush
@endsection
