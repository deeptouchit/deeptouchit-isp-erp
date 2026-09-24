@extends('tenant.layouts.app')

@section('title', 'SaaS Payment History - ' . ($tenant->company_name ?? $tenant->name ?? 'ISP Portal'))

{{-- 1. Page Specific Stylesheets --}}
@push('styles')
<style>
    .metric-card-hover:hover {
        transform: translateY(-2px);
    }
</style>
@endpush

{{-- 2. Main Workspace Body --}}
@section('content')
<div class="space-y-4">
    
    <!-- 1. Top Header Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-sm shadow-xs flex-shrink-0">
                <i class="fas fa-receipt"></i>
            </div>
            <div>
                <h2 class="text-sm font-bold text-slate-900 tracking-tight">SaaS Settlement &amp; Payment History</h2>
                <p class="text-[11px] text-slate-400 font-normal">Audit trail of all online and offline subscription payments and transaction references</p>
            </div>
        </div>

        <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap flex-shrink-0">
            <a href="{{ route('tenant.billing.invoices') }}" 
               class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition inline-flex items-center gap-1.5 shadow-2xs">
                <i class="fas fa-file-invoice text-blue-600 text-[11px]"></i>
                <span>SaaS Invoices</span>
            </a>
            <a href="{{ route('tenant.billing.dashboard') }}" 
               class="px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-chart-pie text-[11px]"></i>
                <span>Billing Overview</span>
            </a>
        </div>
    </div>

    <!-- 2. Ultra-Compact KPI Summary Strip (6 Metric Cards) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2.5">
        
        <!-- Metric 1: Total Payments -->
        <div class="px-2.5 py-2 rounded-xl bg-white border border-slate-200 shadow-xs metric-card-hover transition flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-bold text-slate-500 uppercase tracking-wider block truncate">Total Payments</span>
                <span class="text-[13px] font-bold text-slate-900 tracking-tight font-mono leading-tight block truncate">{{ $totalTransactionsCount }}</span>
            </div>
            <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs border border-emerald-100 flex-shrink-0">
                <i class="fas fa-receipt"></i>
            </div>
        </div>

        <!-- Metric 2: Total Settled -->
        <div class="px-2.5 py-2 rounded-xl bg-white border border-slate-200 shadow-xs metric-card-hover transition flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-bold text-emerald-700 uppercase tracking-wider block truncate">Total Settled</span>
                <span class="text-[13px] font-bold text-emerald-600 tracking-tight font-mono leading-tight block truncate">@currency($totalPaidAmount)</span>
            </div>
            <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs border border-emerald-100 flex-shrink-0">
                <i class="fas fa-check-circle"></i>
            </div>
        </div>

        <!-- Metric 3: bKash Gateway -->
        <div class="px-2.5 py-2 rounded-xl bg-white border border-slate-200 shadow-xs metric-card-hover transition flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-bold text-pink-700 uppercase tracking-wider block truncate">bKash Pay</span>
                <span class="text-[13px] font-bold text-pink-600 tracking-tight font-mono leading-tight block truncate">{{ $bkashCount }} Trx</span>
            </div>
            <div class="w-7 h-7 rounded-lg bg-pink-50 text-pink-600 flex items-center justify-center text-xs border border-pink-100 flex-shrink-0">
                <i class="fas fa-mobile-alt"></i>
            </div>
        </div>

        <!-- Metric 4: Nagad Gateway -->
        <div class="px-2.5 py-2 rounded-xl bg-white border border-slate-200 shadow-xs metric-card-hover transition flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-bold text-orange-700 uppercase tracking-wider block truncate">Nagad Pay</span>
                <span class="text-[13px] font-bold text-orange-600 tracking-tight font-mono leading-tight block truncate">{{ $nagadCount }} Trx</span>
            </div>
            <div class="w-7 h-7 rounded-lg bg-orange-50 text-orange-600 flex items-center justify-center text-xs border border-orange-100 flex-shrink-0">
                <i class="fas fa-wallet"></i>
            </div>
        </div>

        <!-- Metric 5: Online Total -->
        <div class="px-2.5 py-2 rounded-xl bg-white border border-slate-200 shadow-xs metric-card-hover transition flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-bold text-blue-700 uppercase tracking-wider block truncate">Online Gateway</span>
                <span class="text-[13px] font-bold text-blue-600 tracking-tight font-mono leading-tight block truncate">{{ $onlineCount }} Trx</span>
            </div>
            <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-xs border border-blue-100 flex-shrink-0">
                <i class="fas fa-globe"></i>
            </div>
        </div>

        <!-- Metric 6: Manual Deposit -->
        <div class="px-2.5 py-2 rounded-xl bg-white border border-slate-200 shadow-xs metric-card-hover transition flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-bold text-slate-500 uppercase tracking-wider block truncate">Manual Settlements</span>
                <span class="text-[13px] font-bold text-slate-800 tracking-tight font-mono leading-tight block truncate">{{ $manualCount }} Trx</span>
            </div>
            <div class="w-7 h-7 rounded-lg bg-slate-50 text-slate-600 flex items-center justify-center text-xs border border-slate-200 flex-shrink-0">
                <i class="fas fa-money-bill-wave"></i>
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
                       placeholder="Search by transaction reference, gateway, or invoice #..." 
                       class="w-full pl-8 pr-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg text-slate-800 placeholder-slate-400 focus:bg-white focus:border-cyan-500 focus:outline-none">
            </div>

            <!-- Gateway Filter -->
            <div class="w-full sm:w-48">
                <select name="gateway" 
                        onchange="this.form.submit()" 
                        class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 text-slate-700 font-medium focus:bg-white focus:border-cyan-500 focus:outline-none">
                    <option value="">All Gateways</option>
                    <option value="bkash" {{ $gateway === 'bkash' ? 'selected' : '' }}>bKash Checkout</option>
                    <option value="nagad" {{ $gateway === 'nagad' ? 'selected' : '' }}>Nagad Payment</option>
                    <option value="sslcommerz" {{ $gateway === 'sslcommerz' ? 'selected' : '' }}>SSLCommerz</option>
                    <option value="manual" {{ $gateway === 'manual' ? 'selected' : '' }}>Manual Deposit</option>
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
                <a href="{{ route('tenant.billing.payments') }}" 
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
                <span>SaaS Settlement &amp; Transactions Audit Log</span>
            </span>
            <span class="text-[11px] text-slate-500 font-mono font-normal">
                Total {{ $payments->total() }} Transactions
            </span>
        </div>

        <div class="overflow-x-auto">
            <table id="payments-table" class="w-full text-left whitespace-nowrap border-collapse border border-slate-200">
                <thead class="bg-slate-100 text-slate-700 uppercase text-[11px] font-normal tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="px-2.5 py-2 text-center w-10 border border-slate-200 font-normal text-[11px]">#</th>
                        <th class="px-2.5 py-2 text-left border border-slate-200 font-normal text-[11px]">Date &amp; Time</th>
                        <th class="px-2.5 py-2 text-left border border-slate-200 font-normal text-[11px]">Transaction ID</th>
                        <th class="px-2.5 py-2 text-left border border-slate-200 font-normal text-[11px]">Gateway</th>
                        <th class="px-2.5 py-2 text-left border border-slate-200 font-normal text-[11px]">Invoice #</th>
                        <th class="px-2.5 py-2 text-right border border-slate-200 font-normal text-[11px]">Amount</th>
                        <th class="px-2.5 py-2 text-center border border-slate-200 font-normal text-[11px]">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-[11px] font-normal text-slate-700">
                    @forelse($payments as $index => $pay)
                        <tr class="hover:bg-slate-50/80 transition whitespace-nowrap">
                            
                            <!-- 1. Index -->
                            <td class="px-2.5 py-1.5 text-center text-slate-400 font-mono text-[11px] border border-slate-200 font-normal">
                                {{ $payments->firstItem() + $index }}
                            </td>

                            <!-- 2. Date & Time -->
                            <td class="px-2.5 py-1.5 border border-slate-200 font-normal font-mono text-slate-600">
                                {{ $pay->created_at ? $pay->created_at->format('d M Y - h:i A') : '--' }}
                            </td>

                            <!-- 3. Transaction ID -->
                            <td class="px-2.5 py-1.5 border border-slate-200 font-normal font-mono text-slate-900 font-semibold">
                                {{ $pay->transaction_id ?: $pay->gateway_transaction_id ?: ('TRX-' . $pay->id) }}
                            </td>

                            <!-- 4. Gateway -->
                            <td class="px-2.5 py-1.5 border border-slate-200 font-normal uppercase text-slate-800">
                                @if(in_array(strtolower($pay->payment_method), ['bkash']))
                                    <span class="inline-flex items-center gap-1 text-pink-700 font-semibold text-[10.5px]">
                                        <span class="w-1.5 h-1.5 rounded-full bg-pink-500"></span>
                                        <span>bKash</span>
                                    </span>
                                @elseif(in_array(strtolower($pay->payment_method), ['nagad']))
                                    <span class="inline-flex items-center gap-1 text-orange-700 font-semibold text-[10.5px]">
                                        <span class="w-1.5 h-1.5 rounded-full bg-orange-500"></span>
                                        <span>Nagad</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-slate-700 font-medium text-[10.5px]">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                        <span>{{ $pay->payment_method }}</span>
                                    </span>
                                @endif
                            </td>

                            <!-- 5. Invoice # -->
                            <td class="px-2.5 py-1.5 border border-slate-200 font-normal font-mono">
                                @if($pay->invoice)
                                    <a href="{{ route('tenant.billing.invoice.show', $pay->invoice) }}" class="text-blue-600 hover:text-blue-800 hover:underline">
                                        {{ $pay->invoice->invoice_no }}
                                    </a>
                                @else
                                    <span class="text-slate-400">Direct Recharge</span>
                                @endif
                            </td>

                            <!-- 6. Amount -->
                            <td class="px-2.5 py-1.5 border border-slate-200 font-normal font-mono text-emerald-700 text-right font-medium">
                                @currency($pay->amount)
                            </td>

                            <!-- 7. Status -->
                            <td class="px-2.5 py-1.5 text-center border border-slate-200 font-normal">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-normal bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    <span>Success</span>
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center border border-slate-200 font-normal">
                                <div class="max-w-md mx-auto space-y-2">
                                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 mx-auto flex items-center justify-center text-base border border-emerald-100">
                                        <i class="fas fa-receipt"></i>
                                    </div>
                                    <h3 class="text-xs font-normal text-slate-800">No Payment Records Found</h3>
                                    <p class="text-[11px] text-slate-400 font-normal">No subscription settlement transactions match the filter criteria.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($payments->hasPages())
            <div class="p-3 border-t border-slate-200 bg-slate-50">
                {{ $payments->links() }}
            </div>
        @endif
    </div>

</div>
@endsection

{{-- 3. Page Specific Scripts --}}
@push('scripts')
<script>
$(document).ready(function() {
    if ($('#payments-table').length && !$('#payments-table tbody td[colspan]').length) {
        $('#payments-table').DataTable({
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
