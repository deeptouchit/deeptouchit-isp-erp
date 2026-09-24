@extends('owner.layouts.app')

@section('page-title', 'Tenant Wallets & Advance Ledger')

@section('content')
<div class="space-y-4" x-data="{
    walletModalOpen: false,
    selectedTenantId: '',
    selectedTenantName: '',
    selectedBalance: '',

    openWalletAdjust(tenantId, tenantName, balance) {
        this.selectedTenantId = tenantId;
        this.selectedTenantName = tenantName;
        this.selectedBalance = balance;
        this.walletModalOpen = true;
    }
}">


    <!-- Top Header Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs">
        <div>
            <div class="flex items-center gap-2">
                <h2 class="text-sm font-bold text-slate-900 tracking-tight">ISP Tenant Prepaid Wallets</h2>
                <span class="px-2 py-0.5 rounded-full bg-purple-50 text-purple-700 border border-purple-200 text-[10px] font-bold font-mono">
                    {{ $totalWalletsCount }} Wallets
                </span>
            </div>
            <p class="text-[11px] text-slate-500 mt-0.5">Manage prepaid tenant balances, advance deposit credits, manual debit charges, and ledger records</p>
        </div>
    </div>

    <!-- 3 Key Wallet Metrics Strip -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div class="p-3.5 bg-white rounded-xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5">
                <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider block">Total Held Credits</span>
                <span class="text-lg font-extrabold text-emerald-700 tracking-tight font-mono">@currency($totalBalance)</span>
                <span class="text-[10px] text-slate-400 block">All-time Advance Balances</span>
            </div>
            <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs">
                <i class="fas fa-wallet"></i>
            </div>
        </div>

        <div class="p-3.5 bg-white rounded-xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5">
                <span class="text-[10px] font-bold text-purple-600 uppercase tracking-wider block">Funded Wallets</span>
                <span class="text-lg font-extrabold text-purple-700 tracking-tight font-mono">{{ $walletsWithBalanceCount }}</span>
                <span class="text-[10px] text-slate-400 block">Tenants with Active Balance</span>
            </div>
            <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center text-xs">
                <i class="fas fa-coins"></i>
            </div>
        </div>

        <div class="p-3.5 bg-white rounded-xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5">
                <span class="text-[10px] font-bold text-blue-600 uppercase tracking-wider block">Total Tenant Wallets</span>
                <span class="text-lg font-extrabold text-slate-900 tracking-tight font-mono">{{ $totalWalletsCount }}</span>
                <span class="text-[10px] text-slate-400 block">Provisioned Accounts</span>
            </div>
            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-xs">
                <i class="fas fa-building-columns"></i>
            </div>
        </div>
    </div>

    <!-- Wallets Table -->
    <div class="rounded-xl bg-white border border-slate-200 overflow-hidden shadow-xs">
        <div class="p-3.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <h3 class="font-bold text-slate-900 text-xs">ISP Tenant Wallets Ledger</h3>
            <span class="text-[11px] text-slate-500 font-mono">{{ $wallets->total() }} Records</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-700 whitespace-nowrap border-collapse">
                <thead class="bg-slate-50 text-slate-600 uppercase tracking-wider text-[10px] border-b border-slate-200 font-bold text-center">
                    <tr>
                        <th class="px-3 py-2.5 border-r border-slate-200">ISP Organization</th>
                        <th class="px-3.5 py-2.5 border-r border-slate-200">Current Balance</th>
                        <th class="px-3.5 py-2.5 border-r border-slate-200">Currency</th>
                        <th class="px-3.5 py-2.5 border-r border-slate-200">Recent Transactions</th>
                        <th class="px-3.5 py-2.5 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse($wallets as $w)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-3.5 py-2.5 border-r border-slate-200 font-bold text-slate-900">
                                @if($w->tenant)
                                    <a href="{{ route('owner.tenants.show', $w->tenant) }}" class="text-blue-700 hover:underline block">
                                        {{ $w->tenant->name }}
                                    </a>
                                    <span class="text-[10px] text-slate-400 font-mono">{{ $w->tenant->slug }}.somitysoft.com</span>
                                @else
                                    <span class="text-slate-400 italic">Deleted Tenant</span>
                                @endif
                            </td>
                            <td class="px-3.5 py-2.5 border-r border-slate-200 font-mono font-extrabold text-emerald-700 text-center text-sm">
                                @currency($w->balance)
                            </td>
                            <td class="px-3.5 py-2.5 border-r border-slate-200 font-mono text-center uppercase font-bold text-slate-500">
                                {{ $w->currency ?? 'BDT' }}
                            </td>
                            <td class="px-3.5 py-2.5 border-r border-slate-200 text-xs">
                                <span class="text-[11px] text-slate-500 font-mono">{{ count($w->transactions) }} adjustments recorded</span>
                            </td>
                            <td class="px-3.5 py-2.5 text-center">
                                <button type="button" 
                                        @click="openWalletAdjust({{ $w->tenant_id }}, '{{ addslashes($w->tenant->name ?? 'Tenant') }}', '{{ $w->balance }}')"
                                        class="px-3 py-1.5 rounded-lg bg-purple-50 hover:bg-purple-600 hover:text-white text-purple-700 border border-purple-200 font-bold text-xs transition flex items-center gap-1 mx-auto shadow-2xs">
                                    <i class="fas fa-plus-minus text-[10px]"></i>
                                    <span>Adjust Balance</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-slate-400">No tenant wallets provisioned yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3 border-t border-slate-200 bg-slate-50 flex items-center justify-between text-xs text-slate-600">
            <div>Showing {{ $wallets->firstItem() ?? 0 }} to {{ $wallets->lastItem() ?? 0 }} of {{ $wallets->total() }} Records</div>
            <div>{{ $wallets->links() }}</div>
        </div>
    </div>

    <!-- Recent Adjustments Ledger -->
    <div class="rounded-xl bg-white border border-slate-200 overflow-hidden shadow-xs">
        <div class="p-3.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <h3 class="font-bold text-slate-900 text-xs flex items-center gap-1.5">
                <i class="fas fa-list text-slate-600"></i>
                <span>Recent Wallet Transactions Audit Trail</span>
            </h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-700 whitespace-nowrap border-collapse">
                <thead class="bg-slate-50 text-slate-600 uppercase tracking-wider text-[10px] border-b border-slate-200 font-bold text-center">
                    <tr>
                        <th class="px-3 py-2.5 border-r border-slate-200">Date</th>
                        <th class="px-3.5 py-2.5 border-r border-slate-200">ISP Tenant</th>
                        <th class="px-3.5 py-2.5 border-r border-slate-200">Type</th>
                        <th class="px-3.5 py-2.5 border-r border-slate-200">Amount</th>
                        <th class="px-3.5 py-2.5 border-r border-slate-200">Balance After</th>
                        <th class="px-3.5 py-2.5 border-r border-slate-200">Reason / Reference</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse($recentTransactions as $tx)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-3.5 py-2.5 border-r border-slate-200 font-mono text-[10.5px] text-slate-500 text-center">
                                {{ $tx->created_at ? $tx->created_at->format('d M, Y (h:i A)') : '—' }}
                            </td>
                            <td class="px-3.5 py-2.5 border-r border-slate-200 font-bold text-slate-900">
                                {{ $tx->tenant->name ?? 'N/A' }}
                            </td>
                            <td class="px-3.5 py-2.5 border-r border-slate-200 text-center">
                                @if($tx->type === 'credit')
                                    <span class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 font-bold text-[9.5px] border border-emerald-200">+ CREDIT</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full bg-rose-50 text-rose-700 font-bold text-[9.5px] border border-rose-200">- DEBIT</span>
                                @endif
                            </td>
                            <td class="px-3.5 py-2.5 border-r border-slate-200 font-mono font-bold text-right {{ $tx->type === 'credit' ? 'text-emerald-700' : 'text-rose-700' }}">
                                {{ $tx->type === 'credit' ? '+' : '-' }}@currency($tx->amount)
                            </td>
                            <td class="px-3.5 py-2.5 border-r border-slate-200 font-mono font-bold text-right text-slate-800">
                                @currency($tx->balance_after)
                            </td>
                            <td class="px-3.5 py-2.5 border-r border-slate-200 text-slate-600">
                                {{ $tx->description }} <span class="text-slate-400 font-mono text-[10px]">({{ $tx->reference }})</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-400">No recent wallet transaction records.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal for Wallet Adjustment -->
    <div x-show="walletModalOpen" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4"
         x-cloak>
        
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-md w-full overflow-hidden text-xs"
             @click.outside="walletModalOpen = false">
            
            <form :action="'{{ url('owner/wallets') }}/' + selectedTenantId + '/adjust'" method="POST">
                @csrf

                <div class="p-4 bg-purple-50/70 border-b border-purple-100 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-purple-600 text-white flex items-center justify-center font-bold text-xs shadow-xs">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-900 text-xs">Adjust Prepaid Wallet Balance</h3>
                            <p class="text-[11px] text-purple-700 font-semibold" x-text="selectedTenantName"></p>
                        </div>
                    </div>
                    <button type="button" @click="walletModalOpen = false" class="p-1 rounded-lg text-slate-400 hover:text-slate-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="p-5 space-y-3.5">
                    <div>
                        <span class="text-[10.5px] font-semibold text-slate-600 block mb-1">Current Balance:</span>
                        <div class="p-2 rounded-lg bg-slate-50 border border-slate-200 text-emerald-700 font-mono font-extrabold text-sm" x-text="'৳ ' + Number(selectedBalance || 0).toLocaleString()">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Adjustment Type *</label>
                        <select name="type" required class="w-full px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs font-semibold focus:ring-1 focus:ring-purple-500">
                            <option value="credit">Credit (+) Deposit into Wallet</option>
                            <option value="debit">Debit (-) Withdraw / Charge from Wallet</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Amount ({{ $currencySymbol ?? '৳' }}) *</label>
                        <input type="number" step="0.01" name="amount" required placeholder="e.g. 1000" class="w-full px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs font-mono font-bold focus:ring-1 focus:ring-purple-500">
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Reason / Reference Note *</label>
                        <input type="text" name="description" required placeholder="e.g. Advance cash deposit / OTC waiver" class="w-full px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs focus:ring-1 focus:ring-purple-500">
                    </div>
                </div>

                <div class="p-4 bg-slate-50 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="walletModalOpen = false" class="px-3.5 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-600 hover:bg-slate-100 font-semibold text-xs transition">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-1.5 rounded-lg bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs shadow-xs transition flex items-center gap-1.5">
                        <i class="fas fa-check"></i>
                        <span>Apply Adjustment</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
