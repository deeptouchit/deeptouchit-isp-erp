@extends('owner.layouts.app')

@section('page-title', 'Payment Gateway Transactions')

@push('styles')
<style>.owner-finance-table.saas-table td { white-space: nowrap; } .owner-finance-table.saas-table td > div { display: inline-flex; vertical-align: middle; } .owner-finance-table.saas-table td > div + div { margin-left: .35rem; } .owner-finance-table.saas-table th:nth-child(3), .owner-finance-table.saas-table td:nth-child(3) { display: none; } .owner-finance-filter select, .owner-finance-filter input, .owner-finance-filter button { height: 34px; } .owner-finance-filter > div:first-child { flex: 1 1 220px; min-width: 200px; } .owner-finance-filter > div:not(:first-child) { flex: 0 0 auto; } .owner-finance-filter select { width: auto !important; min-width: 130px; }</style>
@endpush

@section('content')
<div class="space-y-4" x-data="{
    payloadModalOpen: false,
    selectedPayload: '',
    selectedRef: '',

    viewPayload(ref, payload) {
        this.selectedRef = ref;
        this.selectedPayload = typeof payload === 'object' ? JSON.stringify(payload, null, 2) : payload;
        this.payloadModalOpen = true;
    }
}">

    <!-- Global KPI Cards -->
    <div class="owner-kpi-grid">
        <x-owner.kpi-card title="Successful Handshakes" :value="$successfulCount" icon="fa-check-double" theme="emerald" :meta="currency($successfulVolume) . ' Volume'" />
        <x-owner.kpi-card title="Pending Sessions" :value="$pendingCount" icon="fa-spinner" theme="amber" meta="Awaiting Verification" />
        <x-owner.kpi-card title="Failed / Cancelled" :value="$failedCount" icon="fa-triangle-exclamation" theme="rose" meta="Aborted Handshakes" />
        <x-owner.kpi-card title="Total Attempts" :value="$totalTransactions" icon="fa-network-wired" theme="blue" meta="Gateway Calls" />
    </div>

    <!-- Filters -->
    <x-owner.filter-toolbar action="{{ route('owner.transactions.index') }}" reset-url="{{ route('owner.transactions.index') }}" form-id="owner-transactions-filter" :show-more-open="request()->filled('gateway') || request()->filled('status')" :active-more-count="request()->filled('gateway') || request()->filled('status') ? 2 : 0" :search-value="request('search', '')">
        <x-slot:primary>
            <div class="relative flex-1 min-w-[200px]">
                <div class="relative"><i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-[11px]" :class="searchQuery ? 'text-cyan-600 font-bold' : 'text-slate-400'"></i><input type="text" name="search" x-model="searchQuery" value="{{ request('search') }}" placeholder="Transaction Reference, Gateway, Tenant..." class="w-full h-[34px] pl-8 pr-7 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs focus:bg-white focus:ring-1 focus:ring-cyan-500 focus:border-cyan-500"><button type="button" x-show="searchQuery" x-cloak @click="searchQuery = ''; $nextTick(() => $el.closest('form').submit())" class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-rose-600"><i class="fas fa-times text-[10px]"></i></button></div>
            </div>
        </x-slot:primary>
        <x-slot:secondary>
                <select name="gateway" class="w-full px-2.5 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs focus:ring-1 focus:ring-blue-500">
                    <option value="">All Gateways</option>
                    <option value="bkash" {{ request('gateway') === 'bkash' ? 'selected' : '' }}>bKash Tokenized</option>
                    <option value="nagad" {{ request('gateway') === 'nagad' ? 'selected' : '' }}>Nagad Direct</option>
                    <option value="sslcommerz" {{ request('gateway') === 'sslcommerz' ? 'selected' : '' }}>SSLCommerz</option>
                    <option value="bank" {{ request('gateway') === 'bank' ? 'selected' : '' }}>Bank Wire</option>
                </select>
                <select name="status" class="w-full px-2.5 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs focus:ring-1 focus:ring-blue-500">
                    <option value="">All Statuses</option>
                    <option value="successful" {{ request('status') === 'successful' ? 'selected' : '' }}>Successful</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                </select>
        </x-slot:secondary>
    </x-owner.filter-toolbar>

    <!-- Transactions Table -->
    <x-owner.table-shell
        :has-rows="$transactions->isNotEmpty()"
        empty-title="No payment transactions found"
        empty-message="Payment transaction handshakes will appear here."
        empty-icon="fa-right-left"
    >
        <x-slot:head>
                    <tr>
                        <th class="px-3 py-2.5 border-r border-slate-200">Date & Time</th>
                        <th class="px-3.5 py-2.5 border-r border-slate-200">ISP Tenant</th>
                        <th class="px-3.5 py-2.5 border-r border-slate-200">Invoice #</th>
                        <th class="px-3.5 py-2.5 border-r border-slate-200">Gateway</th>
                        <th class="px-3.5 py-2.5 border-r border-slate-200">Transaction Reference</th>
                        <th class="px-3.5 py-2.5 border-r border-slate-200">Amount</th>
                        <th class="px-3.5 py-2.5 border-r border-slate-200">Status</th>
                        <th class="px-3.5 py-2.5 text-center">Payload</th>
                    </tr>
        </x-slot:head>
        <x-slot:body>
                    @foreach($transactions as $trx)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-3.5 py-2.5 border-r border-slate-200 font-mono text-[10.5px] text-slate-500 text-center">
                                {{ $trx->created_at ? $trx->created_at->format('d M, Y (h:i A)') : '—' }}
                            </td>
                            <td class="px-3.5 py-2.5 border-r border-slate-200 font-bold text-slate-900">
                                @if($trx->tenant)
                                    <a href="{{ route('owner.tenants.show', $trx->tenant) }}" class="hover:text-blue-600 transition">
                                        {{ $trx->tenant->name }}
                                    </a>
                                @else
                                    <span class="text-slate-400 italic">Deleted Tenant</span>
                                @endif
                            </td>
                            <td class="px-3.5 py-2.5 border-r border-slate-200 font-mono text-center text-blue-700 font-bold">
                                {{ $trx->invoice->invoice_no ?? ('#' . $trx->saas_invoice_id) }}
                            </td>
                            <td class="px-3.5 py-2.5 border-r border-slate-200 text-center uppercase font-bold text-slate-700">
                                {{ $trx->gateway ?: 'Online' }}
                            </td>
                            <td class="px-3.5 py-2.5 border-r border-slate-200 font-mono text-[11px] text-slate-700 text-center font-semibold">
                                {{ $trx->transaction_reference }}
                            </td>
                            <td class="px-3.5 py-2.5 border-r border-slate-200 font-mono font-extrabold text-emerald-700 text-right">
                                @currency($trx->amount)
                            </td>
                            <td class="px-3.5 py-2.5 border-r border-slate-200 text-center">
                                @if($trx->status === 'successful')
                                    <span class="px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 font-bold text-[9.5px] border border-emerald-200">SUCCESSFUL</span>
                                @elseif($trx->status === 'pending')
                                    <span class="px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-700 font-bold text-[9.5px] border border-amber-200">PENDING</span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full bg-rose-50 text-rose-700 font-bold text-[9.5px] border border-rose-200">{{ strtoupper($trx->status) }}</span>
                                @endif
                            </td>
                            <td class="px-3.5 py-2.5 text-center">
                                <button type="button" 
                                        @click="viewPayload(@js($trx->transaction_reference), @js($trx->gateway_response ?? ['status' => $trx->status, 'ref' => $trx->transaction_reference]))"
                                        class="px-2 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 font-mono text-[10px] border border-slate-200 transition">
                                    <i class="fas fa-code mr-1"></i> JSON
                                </button>
                            </td>
                        </tr>
                    @endforeach

        </x-slot:body>
        <x-slot:pagination>
            <div>Showing {{ $transactions->firstItem() ?? 0 }} to {{ $transactions->lastItem() ?? 0 }} of {{ $transactions->total() }} Records</div>
            <div>{{ $transactions->links() }}</div>
        </x-slot:pagination>
    </x-owner.table-shell>

    <!-- Payload JSON Modal -->
    <div x-show="payloadModalOpen" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4"
         x-cloak>
        
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-lg w-full overflow-hidden text-xs"
             @click.outside="payloadModalOpen = false">
            
            <div class="p-3.5 bg-slate-800 text-white flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i class="fas fa-code text-blue-400"></i>
                    <span class="font-bold text-xs" x-text="'Gateway Handshake: ' + selectedRef"></span>
                </div>
                <button type="button" @click="payloadModalOpen = false" class="p-1 text-slate-400 hover:text-white">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="p-4 bg-slate-900 text-emerald-400 font-mono text-[11px] overflow-x-auto max-h-96">
                <pre x-text="selectedPayload"></pre>
            </div>

            <div class="p-3 bg-slate-50 border-t border-slate-200 flex justify-end">
                <button type="button" @click="payloadModalOpen = false" class="px-3.5 py-1.5 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold text-xs transition">
                    Close
                </button>
            </div>
        </div>
    </div>

</div>
@endsection
