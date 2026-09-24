@extends('owner.layouts.app')

@section('page-title', 'Payment Gateway Transactions')

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

    <!-- Top Header Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs">
        <div>
            <div class="flex items-center gap-2">
                <h2 class="text-sm font-bold text-slate-900 tracking-tight">Gateway Transactions & Handshakes</h2>
                <span class="px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 border border-blue-200 text-[10px] font-bold font-mono">
                    {{ $totalTransactions }} Handshakes
                </span>
            </div>
            <p class="text-[11px] text-slate-500 mt-0.5">Real-time technical transaction ledger for bKash Tokenized, Nagad Direct, SSLCommerz, and Bank Transfers</p>
        </div>
    </div>

    <!-- 4 Key Transaction Metrics Strip -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="p-3.5 bg-white rounded-xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5">
                <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider block">Successful Handshakes</span>
                <span class="text-base font-extrabold text-emerald-700 tracking-tight font-mono">{{ $successfulCount }}</span>
                <span class="text-[10px] text-slate-400 block">@currency($successfulVolume) Volume</span>
            </div>
            <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs">
                <i class="fas fa-check-double"></i>
            </div>
        </div>

        <div class="p-3.5 bg-white rounded-xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5">
                <span class="text-[10px] font-bold text-amber-600 uppercase tracking-wider block">Pending Sessions</span>
                <span class="text-base font-extrabold text-amber-700 tracking-tight font-mono">{{ $pendingCount }}</span>
                <span class="text-[10px] text-slate-400 block">Awaiting Verification</span>
            </div>
            <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center text-xs">
                <i class="fas fa-spinner"></i>
            </div>
        </div>

        <div class="p-3.5 bg-white rounded-xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5">
                <span class="text-[10px] font-bold text-rose-600 uppercase tracking-wider block">Failed / Cancelled</span>
                <span class="text-base font-extrabold text-rose-700 tracking-tight font-mono">{{ $failedCount }}</span>
                <span class="text-[10px] text-slate-400 block">Aborted Handshakes</span>
            </div>
            <div class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center text-xs">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
        </div>

        <div class="p-3.5 bg-white rounded-xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5">
                <span class="text-[10px] font-bold text-blue-600 uppercase tracking-wider block">Total Attempts</span>
                <span class="text-base font-extrabold text-slate-900 tracking-tight font-mono">{{ $totalTransactions }}</span>
                <span class="text-[10px] text-slate-400 block">Gateway Calls</span>
            </div>
            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-xs">
                <i class="fas fa-network-wired"></i>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="p-3.5 rounded-xl bg-white border border-slate-200 shadow-xs">
        <form action="{{ route('owner.transactions.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-2.5">
            <div class="lg:col-span-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Transaction Reference, Gateway, Tenant..." class="w-full px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs focus:ring-1 focus:ring-blue-500">
            </div>
            <div>
                <select name="gateway" class="w-full px-2.5 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs focus:ring-1 focus:ring-blue-500">
                    <option value="">All Gateways</option>
                    <option value="bkash" {{ request('gateway') === 'bkash' ? 'selected' : '' }}>bKash Tokenized</option>
                    <option value="nagad" {{ request('gateway') === 'nagad' ? 'selected' : '' }}>Nagad Direct</option>
                    <option value="sslcommerz" {{ request('gateway') === 'sslcommerz' ? 'selected' : '' }}>SSLCommerz</option>
                    <option value="bank" {{ request('gateway') === 'bank' ? 'selected' : '' }}>Bank Wire</option>
                </select>
            </div>
            <div>
                <select name="status" class="w-full px-2.5 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs focus:ring-1 focus:ring-blue-500">
                    <option value="">All Statuses</option>
                    <option value="successful" {{ request('status') === 'successful' ? 'selected' : '' }}>Successful</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                </select>
            </div>
            <div class="flex items-center gap-1.5">
                <button type="submit" class="flex-1 py-1.5 px-3 rounded-lg bg-slate-800 hover:bg-slate-900 text-white font-semibold text-xs transition flex items-center justify-center gap-1.5 shadow-xs">
                    <i class="fas fa-filter text-[10px]"></i>
                    <span>Filter</span>
                </button>
                <a href="{{ route('owner.transactions.index') }}" title="Reset" class="py-1.5 px-2.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold flex items-center justify-center">
                    <i class="fas fa-rotate-left"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Transactions Table -->
    <div class="rounded-xl bg-white border border-slate-200 overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-700 whitespace-nowrap border-collapse">
                <thead class="bg-slate-50 text-slate-600 uppercase tracking-wider text-[10px] border-b border-slate-200 font-bold text-center">
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
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse($transactions as $trx)
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
                                        @click="viewPayload('{{ $trx->transaction_reference }}', {{ json_encode($trx->gateway_response ?? ['status' => $trx->status, 'ref' => $trx->transaction_reference]) }})"
                                        class="px-2 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 font-mono text-[10px] border border-slate-200 transition">
                                    <i class="fas fa-code mr-1"></i> JSON
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-slate-400">No payment transaction handshakes found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3 border-t border-slate-200 bg-slate-50 flex items-center justify-between text-xs text-slate-600">
            <div>Showing {{ $transactions->firstItem() ?? 0 }} to {{ $transactions->lastItem() ?? 0 }} of {{ $transactions->total() }} Records</div>
            <div>{{ $transactions->links() }}</div>
        </div>
    </div>

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
