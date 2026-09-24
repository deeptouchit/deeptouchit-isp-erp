@extends('reseller.layouts.app')

@section('title', 'Transaction Ledger - ' . ($tenant->company_name ?? $tenant->name ?? 'Reseller Portal'))

@push('styles')
    {{-- Page-specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="ledgerManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">
    
    {{-- 1. TOP HEADER BAR (AGENTS.md Rule 2.A: Icon + Title + Action buttons ONLY - Strictly No Subtitles) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-sm shadow-2xs flex-shrink-0">
                <i class="fas fa-list-check"></i>
            </div>
            <h1 class="text-sm font-bold text-slate-800 tracking-tight">{{ __('Transaction Ledger') }}</h1>
        </div>
        <div class="flex items-center gap-1.5 sm:gap-2 flex-wrap sm:flex-nowrap justify-start sm:justify-end">
            <a href="{{ route('reseller.ledger.print', request()->query()) }}" target="_blank" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-print text-slate-500 text-xs"></i>
                <span>{{ __('Print Statement') }}</span>
            </a>
            <a href="{{ route('reseller.ledger.export', request()->query()) }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-file-csv text-slate-500 text-xs"></i>
                <span>{{ __('Export CSV') }}</span>
            </a>
        </div>
    </div>

    {{-- 2. KPI SUMMARY STRIP (Strictly 6 Cards - AGENTS.md Rule 2.B) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        
        {{-- Card 1: Current Wallet Balance --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Wallet Balance') }}</span>
                <span class="text-[13px] font-bold font-mono text-emerald-600 leading-tight block truncate">
                    @currency($stats['wallet_balance'])
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-wallet"></i>
            </div>
        </div>

        {{-- Card 2: Credit Limit --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Credit Limit') ?? 'Credit Limit' }}</span>
                <span class="text-[13px] font-bold font-mono text-slate-900 leading-tight block truncate">
                    @currency($stats['credit_limit'])
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-slate-200 bg-slate-50 text-slate-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-shield-halved"></i>
            </div>
        </div>

        {{-- Card 3: Total Available Balance --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Available Balance') }}</span>
                <span class="text-[13px] font-bold font-mono text-cyan-700 leading-tight block truncate">
                    @currency($stats['total_available'])
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-cyan-200 bg-cyan-50 text-cyan-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-scale-balanced"></i>
            </div>
        </div>

        {{-- Card 4: Total Credits (Inflow) --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Credit') }}</span>
                <span class="text-[13px] font-bold font-mono text-emerald-700 leading-tight block truncate">
                    +@currency($stats['total_credits'])
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-arrow-down-left"></i>
            </div>
        </div>

        {{-- Card 5: Total Debits (Outflow) --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Debit') }}</span>
                <span class="text-[13px] font-bold font-mono text-rose-600 leading-tight block truncate">
                    -@currency($stats['total_debits'])
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-rose-200 bg-rose-50 text-rose-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-arrow-up-right"></i>
            </div>
        </div>

        {{-- Card 6: Total Transactions --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Receipt Number') }}</span>
                <span class="text-[13px] font-bold font-mono text-cyan-700 leading-tight block truncate">
                    {{ number_format($stats['total_count']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-cyan-200 bg-cyan-50 text-cyan-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-receipt"></i>
            </div>
        </div>

    </div>

    {{-- 3. SEARCH & MULTI-FILTER TOOLBAR (AGENTS.md Rule 2.C) --}}
    <div class="bg-white p-2.5 sm:p-3 rounded-xl border border-slate-200 shadow-xs space-y-2">
        <form method="GET" action="{{ route('reseller.ledger.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-12 gap-2 items-center">
            
            <!-- Search Box (MD: 4 Cols) -->
            <div class="relative md:col-span-4">
                <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                <input type="search" 
                       name="search" 
                       value="{{ $search }}" 
                       autocomplete="off"
                       placeholder="Search trx ID, reference, note..." 
                       class="w-full pl-8 pr-3 py-1.5 rounded-lg border border-slate-200 bg-slate-50 text-xs focus:bg-white focus:outline-none focus:border-cyan-500 shadow-2xs">
            </div>

            <!-- Type Filter (MD: 2 Cols) -->
            <div class="md:col-span-2">
                <select name="type" onchange="this.form.submit()" class="w-full py-1.5 px-2 rounded-lg border border-slate-200 bg-slate-50 text-xs focus:bg-white focus:outline-none focus:border-cyan-500 shadow-2xs">
                    <option value="all" {{ $type === 'all' ? 'selected' : '' }}>All Types</option>
                    <option value="CREDIT" {{ $type === 'CREDIT' ? 'selected' : '' }}>Credit (+Inflow)</option>
                    <option value="DEBIT" {{ $type === 'DEBIT' ? 'selected' : '' }}>Debit (-Outflow)</option>
                </select>
            </div>

            <!-- Payment Method Filter (MD: 2 Cols) -->
            <div class="md:col-span-2">
                <select name="payment_method" onchange="this.form.submit()" class="w-full py-1.5 px-2 rounded-lg border border-slate-200 bg-slate-50 text-xs focus:bg-white focus:outline-none focus:border-cyan-500 shadow-2xs">
                    <option value="all" {{ $method === 'all' ? 'selected' : '' }}>All Methods</option>
                    <option value="PREPAID_WALLET" {{ $method === 'PREPAID_WALLET' ? 'selected' : '' }}>Prepaid Wallet</option>
                    <option value="BKASH" {{ $method === 'BKASH' ? 'selected' : '' }}>bKash</option>
                    <option value="NAGAD" {{ $method === 'NAGAD' ? 'selected' : '' }}>Nagad</option>
                    <option value="ROCKET" {{ $method === 'ROCKET' ? 'selected' : '' }}>Rocket</option>
                    <option value="BANK_TRANSFER" {{ $method === 'BANK_TRANSFER' ? 'selected' : '' }}>Bank Transfer</option>
                    <option value="CASH" {{ $method === 'CASH' ? 'selected' : '' }}>Cash</option>
                </select>
            </div>

            <!-- Month Filter (MD: 2 Cols) -->
            <div class="md:col-span-2">
                <select name="month" onchange="this.form.submit()" class="w-full py-1.5 px-2 rounded-lg border border-slate-200 bg-slate-50 text-xs focus:bg-white focus:outline-none focus:border-cyan-500 shadow-2xs">
                    <option value="all" {{ $month === 'all' ? 'selected' : '' }}>All Months</option>
                    @foreach($availableMonths as $m)
                        <option value="{{ $m }}" {{ $month === $m ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::parse($m . '-01')->format('F Y') }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Strict Filter & Reset Sequence (AGENTS.md Rule 2.C - MD: 2 Cols) -->
            <div class="flex items-center gap-1.5 md:col-span-2">
                <button type="submit" class="w-1/2 bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-1.5 px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                    <i class="fas fa-filter text-[10px]"></i>
                    <span>Filter</span>
                </button>
                <a href="{{ route('reseller.ledger.index') }}" class="w-1/2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-1.5 px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer">
                    <i class="fas fa-rotate-left text-[10px]"></i>
                    <span>Reset</span>
                </a>
            </div>

        </form>
    </div>

    {{-- 4. MASTER COMPACT TABLE (AGENTS.md Rule 2.D: Max 5-7 Minimal Columns) --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-10 text-center">#</th>
                        <th class="w-32 font-mono">Trx ID</th>
                        <th class="w-20 text-center">Type</th>
                        <th>Particulars / Reference</th>
                        <th class="w-28 text-right">Amount</th>
                        <th class="w-28 text-right">Balance</th>
                        <th class="w-32 text-center font-mono">Date &amp; Time</th>
                        <th class="w-10 text-center no-sort">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $index => $trx)
                        <tr>
                            <!-- 1. Index -->
                            <td class="text-center font-mono text-slate-500">
                                {{ ($transactions->currentPage() - 1) * $transactions->perPage() + $index + 1 }}
                            </td>

                            <!-- 2. Trx ID (Single Clean Field) -->
                            <td class="font-mono font-bold text-cyan-800">
                                <button type="button" class="hover:underline cursor-pointer font-mono" @click="fetchTrxDetails({{ $trx->id }})">
                                    {{ $trx->trx_id }}
                                </button>
                            </td>

                            <!-- 3. Type Badge -->
                            <td class="text-center">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold border {{ $trx->type_badge['class'] }}">
                                    <i class="fas {{ $trx->type_badge['icon'] }} text-[8px]"></i>
                                    <span>{{ $trx->type === 'CREDIT' ? 'Credit' : 'Debit' }}</span>
                                </span>
                            </td>

                            <!-- 4. Description / Reference -->
                            <td class="text-slate-800">
                                <button type="button" class="hover:text-cyan-700 cursor-pointer font-medium text-left truncate max-w-md block" @click="fetchTrxDetails({{ $trx->id }})">
                                    {{ $trx->description ?? ($trx->reference_no ? 'Ref: ' . $trx->reference_no : 'Wallet Adjustment') }}
                                </button>
                            </td>

                            <!-- 5. Amount (Green for Credit / Rose for Debit) -->
                            <td class="text-right font-mono font-bold {{ $trx->type === 'CREDIT' ? 'text-emerald-600' : 'text-rose-600' }}">
                                {{ $trx->type === 'CREDIT' ? '+' : '-' }}@currency($trx->amount)
                            </td>

                            <!-- 6. Balance After -->
                            <td class="text-right font-mono font-bold text-slate-800">
                                @currency($trx->balance_after)
                            </td>

                            <!-- 7. Date & Time -->
                            <td class="text-center font-mono text-slate-600 text-xs">
                                {{ $trx->created_at ? $trx->created_at->format('d M Y, h:i A') : '—' }}
                            </td>

                            <!-- 8. Action (3-Dot Floating Trigger) -->
                            <td class="text-center">
                                <button type="button" 
                                        @click.stop="toggleMenu({{ Js::from([
                                            'id' => $trx->id,
                                            'trx_id' => $trx->trx_id,
                                            'type' => $trx->type,
                                            'amount' => (float)$trx->amount
                                        ]) }}, $event)" 
                                        class="w-7 h-7 rounded-lg hover:bg-slate-100 text-slate-500 hover:text-slate-800 transition flex items-center justify-center mx-auto cursor-pointer">
                                    <i class="fas fa-ellipsis-v text-[10px]"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-400 text-xs">
                                <i class="fas fa-list-check text-3xl text-slate-300 mb-2 block"></i>
                                <span>No wallet transactions found matching your criteria.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($transactions->hasPages())
            <div class="px-4 py-2.5 bg-white border-t border-slate-200 flex items-center justify-between text-xs">
                <div>
                    Showing <span class="font-bold">{{ $transactions->firstItem() }}</span> to <span class="font-bold">{{ $transactions->lastItem() }}</span> of <span class="font-bold">{{ $transactions->total() }}</span> transactions
                </div>
                <div>
                    {{ $transactions->links() }}
                </div>
            </div>
        @endif
    </div>

    {{-- 5. FLOATING 3-DOT ACTION MENU (AGENTS.md Rule 2.E) --}}
    <div x-show="activeMenu !== null" 
         x-cloak 
         @click.outside="activeMenu = null"
         :style="menuPos"
         class="fixed z-50 w-48 bg-white rounded-xl border border-slate-200 shadow-xl py-1 text-xs text-slate-700 font-medium space-y-0.5"
         style="display: none;">
        
        <button type="button" 
                @click="fetchTrxDetails(activeMenuItem.id); activeMenu = null" 
                class="w-full px-3 py-1.5 text-left hover:bg-slate-50 hover:text-cyan-700 flex items-center gap-2 cursor-pointer transition">
            <i class="fas fa-receipt text-cyan-600 w-4"></i>
            <span>View Trx Voucher</span>
        </button>
    </div>

    {{-- 6. MODAL: NATURAL SOFT TRANSACTION VOUCHER MODAL (AGENTS.md Rule 3) --}}
    <div x-show="showTrxModal" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50 overflow-y-auto"
         style="display: none;"
         x-cloak>
        <div @click.outside="showTrxModal = false" 
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden my-6">
            
            {{-- Soft Header --}}
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Transaction Voucher</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal" x-text="trxData?.trx_id || 'Wallet Voucher'"></p>
                    </div>
                </div>
                <button type="button" @click="showTrxModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            {{-- Body --}}
            <div class="p-4 space-y-3" x-show="trxData">
                
                <!-- Amount Banner -->
                <div class="p-3 rounded-lg bg-slate-50 border border-slate-200 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-medium uppercase tracking-wider text-slate-500 block">Transaction Amount</span>
                        <span class="text-lg font-bold font-mono" 
                              :class="trxData?.type === 'CREDIT' ? 'text-emerald-600' : 'text-rose-600'" 
                              x-text="(trxData?.type === 'CREDIT' ? '+' : '-') + '{{ $currencySymbol ?? '৳' }}' + Number(trxData?.amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2})"></span>
                    </div>
                    <div>
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-bold border" 
                              :class="trxData?.type_badge?.class">
                            <i class="fas" :class="trxData?.type_badge?.icon"></i>
                            <span x-text="trxData?.type === 'CREDIT' ? 'Credit (+Inflow)' : 'Debit (-Outflow)'"></span>
                        </span>
                    </div>
                </div>

                <!-- 2-Column Info Grid -->
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div class="p-2.5 rounded-lg bg-slate-50/80 border border-slate-200/80">
                        <span class="text-[10px] text-slate-500 block">Transaction ID</span>
                        <span class="font-mono font-bold text-slate-800" x-text="trxData?.trx_id"></span>
                    </div>

                    <div class="p-2.5 rounded-lg bg-slate-50/80 border border-slate-200/80">
                        <span class="text-[10px] text-slate-500 block">Payment Method</span>
                        <span class="font-semibold text-slate-800" x-text="trxData?.payment_method || 'Prepaid Wallet'"></span>
                    </div>

                    <div class="p-2.5 rounded-lg bg-slate-50/80 border border-slate-200/80">
                        <span class="text-[10px] text-slate-500 block">Balance Before</span>
                        <span class="font-mono font-semibold text-slate-700" x-text="'{{ $currencySymbol ?? '৳' }}' + Number(trxData?.balance_before || 0).toLocaleString('en-US', {minimumFractionDigits: 2})"></span>
                    </div>

                    <div class="p-2.5 rounded-lg bg-slate-50/80 border border-slate-200/80">
                        <span class="text-[10px] text-slate-500 block">Balance After</span>
                        <span class="font-mono font-bold text-slate-900" x-text="'{{ $currencySymbol ?? '৳' }}' + Number(trxData?.balance_after || 0).toLocaleString('en-US', {minimumFractionDigits: 2})"></span>
                    </div>

                    <template x-if="trxData?.reference_no">
                        <div class="p-2.5 rounded-lg bg-slate-50/80 border border-slate-200/80 col-span-2">
                            <span class="text-[10px] text-slate-500 block">Reference Document #</span>
                            <span class="font-mono font-bold text-slate-800" x-text="trxData?.reference_no"></span>
                        </div>
                    </template>

                    <div class="p-2.5 rounded-lg bg-slate-50/80 border border-slate-200/80 col-span-2">
                        <span class="text-[10px] text-slate-500 block">Description / Particulars</span>
                        <span class="font-medium text-slate-800" x-text="trxData?.description || 'N/A'"></span>
                    </div>

                    <div class="p-2.5 rounded-lg bg-slate-50/80 border border-slate-200/80">
                        <span class="text-[10px] text-slate-500 block">Timestamp</span>
                        <span class="font-mono text-slate-800" x-text="trxData?.created_at"></span>
                    </div>

                    <div class="p-2.5 rounded-lg bg-slate-50/80 border border-slate-200/80">
                        <span class="text-[10px] text-slate-500 block">Created By</span>
                        <span class="font-semibold text-slate-800" x-text="trxData?.created_by"></span>
                    </div>
                </div>

            </div>

            {{-- Footer --}}
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end">
                <button type="button" 
                        @click="showTrxModal = false" 
                        class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-4 py-1.5 rounded-lg transition cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function ledgerManager() {
    return {
        activeMenu: null,
        activeMenuItem: null,
        menuPos: {},
        showTrxModal: false,
        trxData: null,

        toggleMenu(item, event) {
            if (this.activeMenu === item.id) {
                this.activeMenu = null;
                this.activeMenuItem = null;
                return;
            }
            this.activeMenu = item.id;
            this.activeMenuItem = item;

            const rect = event.currentTarget.getBoundingClientRect();
            const dropdownHeight = 100;
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

        async fetchTrxDetails(id) {
            try {
                const res = await fetch(`/reseller/ledger/${id}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    this.trxData = data.data;
                    this.showTrxModal = true;
                }
            } catch (err) {
                console.error("Error fetching voucher:", err);
            }
        }
    };
}
</script>
@endpush
