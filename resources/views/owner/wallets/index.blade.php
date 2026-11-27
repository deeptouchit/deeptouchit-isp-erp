@extends('owner.layouts.app')

@section('page-title', 'Tenant Prepaid Wallets & Advance Ledger')

@section('content')
<div class="space-y-3" x-data="{
    walletModalOpen: false,
    selectedTenantId: '',
    selectedTenantName: '',
    selectedBalance: '',
    showMoreFilters: {{ request()->filled('status') ? 'true' : 'false' }},

    openWalletAdjust(tenantId, tenantName, balance) {
        this.selectedTenantId = tenantId;
        this.selectedTenantName = tenantName;
        this.selectedBalance = balance;
        this.walletModalOpen = true;
    }
}">

    <!-- Global KPI Cards -->
    <div class="owner-kpi-grid">
        <x-owner.kpi-card title="Total Held Credits" :value="currency($totalBalance)" icon="fa-wallet" theme="emerald" meta="All-time Advance Balances" />
        <x-owner.kpi-card title="Funded Wallets" :value="$walletsWithBalanceCount" icon="fa-coins" theme="purple" meta="Tenants with Active Balance" />
        <x-owner.kpi-card title="Total Tenant Wallets" :value="$totalWalletsCount" icon="fa-building-columns" theme="blue" meta="Provisioned Accounts" />
        <x-owner.kpi-card title="Average Balance" :value="currency($totalWalletsCount ? $totalBalance / $totalWalletsCount : 0)" icon="fa-scale-balanced" theme="cyan" />
        <x-owner.kpi-card title="Zero Balance" :value="$totalWalletsCount - $walletsWithBalanceCount" icon="fa-circle-minus" theme="amber" />
        <x-owner.kpi-card title="Ledger Entries" :value="$ledgerEntriesCount" icon="fa-list-ol" theme="slate" />
    </div>

    <div class="bg-white p-2.5 rounded-xl border border-slate-200/90 shadow-2xs space-y-2.5">
        <form method="GET" action="{{ route('owner.wallets.index') }}" class="flex flex-col md:flex-row items-stretch md:items-center gap-2">
            <div class="relative flex-1 min-w-[200px]"><i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-[11px]"></i><input name="search" value="{{ request('search') }}" placeholder="Search tenant, email or phone..." class="w-full h-[34px] pl-8 pr-3 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden"></div>
            <select name="status" class="h-[34px] md:w-36 px-2.5 py-0 rounded-lg border border-slate-200 bg-slate-50 text-xs"><option value="">All Statuses</option><option value="active" @selected(request('status') === 'active')>Active</option><option value="suspended" @selected(request('status') === 'suspended')>Suspended</option><option value="expired" @selected(request('status') === 'expired')>Expired</option></select>
            <select name="per_page" class="h-[34px] md:w-28 px-2.5 py-0 rounded-lg border border-slate-200 bg-slate-50 text-xs"><option value="15">15 / page</option><option value="30" @selected(request('per_page') == 30)>30 / page</option><option value="50" @selected(request('per_page') == 50)>50 / page</option><option value="100" @selected(request('per_page') == 100)>100 / page</option></select>
            <button type="button" @click="showMoreFilters = !showMoreFilters" class="h-[34px] py-0 px-3 rounded-lg border border-slate-200 bg-slate-50 text-xs font-semibold"><i class="fas" :class="showMoreFilters ? 'fa-chevron-up' : 'fa-chevron-down'"></i> More Filters</button>
            <div class="flex items-center gap-1.5 md:ml-auto"><button class="h-[34px] bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-0 px-3 rounded-lg shadow-xs"><i class="fas fa-filter text-[10px]"></i> Filter</button><a href="{{ route('owner.wallets.index') }}" class="h-[34px] bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-0 px-2.5 rounded-lg border border-slate-200/80 flex items-center gap-1"><i class="fas fa-rotate-left text-[10px]"></i> Reset</a></div>
        </form>
        <div x-show="showMoreFilters" x-cloak x-transition class="pt-2 border-t border-slate-100 text-[10px] text-slate-500">Filter wallets by tenant status and balance. Use the action button to record a credit or debit.</div>
    </div>

    <!-- Wallets Table -->
    <x-owner.table-shell
        :has-rows="$wallets->isNotEmpty()"
        empty-title="No tenant wallets found"
        empty-message="No tenant wallets match the current filters."
        empty-icon="fa-wallet"
    >
        <div class="p-3.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <h3 class="font-bold text-slate-900 text-xs">ISP Tenant Wallets Ledger</h3>
            <span class="text-[11px] text-slate-500 font-mono">{{ $wallets->total() }} Records</span>
        </div>

        <x-slot:head>
                    <tr>
                        <th class="px-3 py-2.5 border-r border-slate-200">ISP Organization</th>
                        <th class="px-3.5 py-2.5 border-r border-slate-200">Current Balance</th>
                        <th class="px-3.5 py-2.5 border-r border-slate-200">Currency</th>
                        <th class="px-3.5 py-2.5 border-r border-slate-200">Recent Transactions</th>
                        <th class="px-3.5 py-2.5 text-center">Actions</th>
                    </tr>
        </x-slot:head>
        <x-slot:body>
                    @foreach($wallets as $w)
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
                                {{ $w->currency ?? $currencyCode }}
                            </td>
                            <td class="px-3.5 py-2.5 border-r border-slate-200 text-xs">
                                <span class="text-[11px] text-slate-500 font-mono">{{ $w->transactions_count }} adjustments</span>
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
                    @endforeach
        </x-slot:body>

        <x-slot:pagination>
            <div>Showing {{ $wallets->firstItem() ?? 0 }} to {{ $wallets->lastItem() ?? 0 }} of {{ $wallets->total() }} Records</div>
            <div>{{ $wallets->links() }}</div>
        </x-slot:pagination>
    </x-owner.table-shell>

    <!-- Recent Adjustments Ledger -->
    <x-owner.table-shell
        :has-rows="$recentTransactions->isNotEmpty()"
        empty-title="No wallet transactions found"
        empty-message="Recent wallet transaction records will appear here."
        empty-icon="fa-list"
    >
        <div class="p-3.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <h3 class="font-bold text-slate-900 text-xs flex items-center gap-1.5">
                <i class="fas fa-list text-slate-600"></i>
                <span>Recent Wallet Transactions Audit Trail</span>
            </h3>
        </div>

        <x-slot:head>
                    <tr>
                        <th class="px-3 py-2.5 border-r border-slate-200">Date</th>
                        <th class="px-3.5 py-2.5 border-r border-slate-200">ISP Tenant</th>
                        <th class="px-3.5 py-2.5 border-r border-slate-200">Type</th>
                        <th class="px-3.5 py-2.5 border-r border-slate-200">Amount</th>
                        <th class="px-3.5 py-2.5 border-r border-slate-200">Balance After</th>
                        <th class="px-3.5 py-2.5 border-r border-slate-200">Reason / Reference</th>
                    </tr>
        </x-slot:head>
        <x-slot:body>
                    @foreach($recentTransactions as $tx)
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
                    @endforeach
        </x-slot:body>
    </x-owner.table-shell>

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
                        <div class="p-2 rounded-lg bg-slate-50 border border-slate-200 text-emerald-700 font-mono font-extrabold text-sm" x-text="'{{ $currencySymbol }} ' + Number(selectedBalance || 0).toLocaleString()">
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
