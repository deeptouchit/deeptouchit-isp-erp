@extends('reseller.layouts.app')

@section('title', 'Wallet Recharge - ' . ($tenant->company_name ?? $tenant->name ?? 'Reseller Portal'))

@push('styles')
    {{-- Page-specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="rechargeManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">
    
    {{-- 1. TOP HEADER BAR (AGENTS.md Rule 2.A: Icon + Title + Action buttons ONLY - Strictly No Subtitles) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-sm shadow-2xs flex-shrink-0">
                <i class="fas fa-wallet"></i>
            </div>
            <h1 class="text-sm font-bold text-slate-800 tracking-tight">{{ __('Recharge Wallet') }}</h1>
        </div>
        <div class="flex items-center gap-1.5 sm:gap-2 flex-wrap sm:flex-nowrap justify-start sm:justify-end">
            <button type="button" 
                    @click="openOnlineModal()" 
                    class="px-3 py-1.5 bg-gradient-to-r from-pink-600 to-rose-600 hover:from-pink-700 hover:to-rose-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-bolt text-xs text-amber-200"></i>
                <span>{{ __('Online Recharge') }}</span>
            </button>
            <button type="button" 
                    @click="openTopupModal()" 
                    class="px-3 py-1.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-file-invoice-dollar text-xs"></i>
                <span>{{ __('Manual Deposit') }}</span>
            </button>
            <a href="{{ route('reseller.recharge.print', request()->query()) }}" target="_blank" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-print text-slate-500 text-xs"></i>
                <span>{{ __('Print Statement') }}</span>
            </a>
            <a href="{{ route('reseller.recharge.export', request()->query()) }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
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

        {{-- Card 4: Total Approved Recharges --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Total Received') }}</span>
                <span class="text-[13px] font-bold font-mono text-emerald-700 leading-tight block truncate">
                    @currency($stats['approved_amount'])
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-circle-check"></i>
            </div>
        </div>

        {{-- Card 5: Pending Approval --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-amber-600 block truncate">{{ __('Pending Approval') }}</span>
                <span class="text-[13px] font-bold font-mono leading-tight block truncate {{ ($stats['pending_amount'] ?? 0) > 0 ? 'text-amber-600 font-bold' : 'text-slate-700' }}">
                    @currency($stats['pending_amount'])
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border {{ ($stats['pending_amount'] ?? 0) > 0 ? 'border-amber-200 bg-amber-50 text-amber-600' : 'border-slate-200 bg-slate-50 text-slate-500' }} flex items-center justify-center flex-shrink-0">
                <i class="fas fa-clock"></i>
            </div>
        </div>

        {{-- Card 6: Total Top-up Requests --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Receipt Number') }}</span>
                <span class="text-[13px] font-bold font-mono text-cyan-700 leading-tight block truncate">
                    {{ number_format($stats['total_count'] ?? 0) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-cyan-200 bg-cyan-50 text-cyan-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-list-check"></i>
            </div>
        </div>

    </div>

    {{-- 3. SEARCH & MULTI-FILTER TOOLBAR (AGENTS.md Rule 2.C) --}}
    <div class="bg-white p-2.5 sm:p-3 rounded-xl border border-slate-200 shadow-xs space-y-2">
        <form method="GET" action="{{ route('reseller.recharge.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-12 gap-2 items-center">
            
            <!-- Search Box (MD: 4 Cols) -->
            <div class="relative md:col-span-4">
                <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                <input type="search" 
                       name="search" 
                       value="{{ $search }}" 
                       autocomplete="off"
                       placeholder="Search recharge #, trx ID, bank..." 
                       class="w-full pl-8 pr-3 py-1.5 rounded-lg border border-slate-200 bg-slate-50 text-xs focus:bg-white focus:outline-none focus:border-cyan-500 shadow-2xs">
            </div>

            <!-- Status Filter (MD: 2 Cols) -->
            <div class="md:col-span-2">
                <select name="status" onchange="this.form.submit()" class="w-full py-1.5 px-2 rounded-lg border border-slate-200 bg-slate-50 text-xs focus:bg-white focus:outline-none focus:border-cyan-500 shadow-2xs">
                    <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All Status</option>
                    <option value="APPROVED" {{ $status === 'APPROVED' ? 'selected' : '' }}>Approved</option>
                    <option value="PENDING" {{ $status === 'PENDING' ? 'selected' : '' }}>Pending</option>
                    <option value="REJECTED" {{ $status === 'REJECTED' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>

            <!-- Payment Method Filter (MD: 2 Cols) -->
            <div class="md:col-span-2">
                <select name="payment_method" onchange="this.form.submit()" class="w-full py-1.5 px-2 rounded-lg border border-slate-200 bg-slate-50 text-xs focus:bg-white focus:outline-none focus:border-cyan-500 shadow-2xs">
                    <option value="all" {{ $method === 'all' ? 'selected' : '' }}>All Methods</option>
                    <option value="BKASH" {{ $method === 'BKASH' ? 'selected' : '' }}>bKash</option>
                    <option value="NAGAD" {{ $method === 'NAGAD' ? 'selected' : '' }}>Nagad</option>
                    <option value="ROCKET" {{ $method === 'ROCKET' ? 'selected' : '' }}>Rocket</option>
                    <option value="BANK_TRANSFER" {{ $method === 'BANK_TRANSFER' ? 'selected' : '' }}>Bank Deposit</option>
                    <option value="CASH" {{ $method === 'CASH' ? 'selected' : '' }}>Cash</option>
                    <option value="ONLINE_GATEWAY" {{ $method === 'ONLINE_GATEWAY' ? 'selected' : '' }}>Online PGW</option>
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
                <a href="{{ route('reseller.recharge.index') }}" class="w-1/2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-1.5 px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer">
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
                        <th class="w-36 font-mono">Recharge #</th>
                        <th class="w-24 text-center">Method</th>
                        <th>Reference / Trx ID</th>
                        <th class="w-32 text-right">Amount Credited</th>
                        <th class="w-24 text-center">Status</th>
                        <th class="w-28 text-center font-mono">Date</th>
                        <th class="w-10 text-center no-sort">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recharges as $index => $recharge)
                        <tr>
                            <!-- 1. Index -->
                            <td class="text-center font-mono text-slate-500">
                                {{ ($recharges->currentPage() - 1) * $recharges->perPage() + $index + 1 }}
                            </td>

                            <!-- 2. Recharge # (Single Clean Field) -->
                            <td class="font-mono font-bold text-cyan-800">
                                <button type="button" class="hover:underline cursor-pointer font-mono" @click="fetchReceiptDetails({{ $recharge->id }})">
                                    {{ $recharge->recharge_no }}
                                </button>
                            </td>

                            <!-- 3. Method Badge -->
                            <td class="text-center">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold border {{ $recharge->payment_method_badge['class'] }}">
                                    <i class="fas {{ $recharge->payment_method_badge['icon'] }} text-[9px]"></i>
                                    <span>{{ $recharge->payment_method_badge['label'] }}</span>
                                </span>
                            </td>

                            <!-- 4. Reference / Trx ID / Bank -->
                            <td class="font-mono text-slate-700 text-xs">
                                @if($recharge->gateway_trx_id)
                                    <span>{{ $recharge->gateway_trx_id }}</span>
                                @elseif($recharge->bank_name)
                                    <span>{{ $recharge->bank_name }}</span>
                                @elseif($recharge->notes)
                                    <span class="text-slate-500 truncate max-w-xs block">{{ $recharge->notes }}</span>
                                @else
                                    <span class="text-slate-400">Direct Deposit</span>
                                @endif
                            </td>

                            <!-- 5. Amount Credited -->
                            <td class="text-right font-mono font-bold text-emerald-600">
                                @currency($recharge->total_credited > 0 ? $recharge->total_credited : $recharge->amount)
                            </td>

                            <!-- 6. Status Badge -->
                            <td class="text-center">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold border {{ $recharge->status_badge['class'] }}">
                                    <i class="fas {{ $recharge->status_badge['icon'] }} text-[8px]"></i>
                                    <span>{{ $recharge->status_badge['label'] }}</span>
                                </span>
                            </td>

                            <!-- 7. Date -->
                            <td class="text-center font-mono text-slate-600 text-xs">
                                {{ $recharge->created_at ? $recharge->created_at->format('d M Y') : '—' }}
                            </td>

                            <!-- 8. Action (3-Dot Floating Trigger) -->
                            <td class="text-center">
                                <button type="button" 
                                        @click.stop="toggleMenu({{ Js::from([
                                            'id' => $recharge->id,
                                            'no' => $recharge->recharge_no,
                                            'has_slip' => (bool)$recharge->slip_path,
                                            'slip_url' => $recharge->slip_path ? \Illuminate\Support\Facades\Storage::url($recharge->slip_path) : null,
                                            'status' => $recharge->status
                                        ]) }}, $event)" 
                                        class="w-7 h-7 rounded-lg hover:bg-slate-100 text-slate-500 hover:text-slate-800 transition flex items-center justify-center mx-auto cursor-pointer">
                                    <i class="fas fa-ellipsis-v text-[10px]"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-400 text-xs">
                                <i class="fas fa-wallet text-3xl text-slate-300 mb-2 block"></i>
                                <span>No wallet recharge records found matching your criteria.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($recharges->hasPages())
            <div class="px-4 py-2.5 bg-white border-t border-slate-200 flex items-center justify-between text-xs">
                <div>
                    Showing <span class="font-bold">{{ $recharges->firstItem() }}</span> to <span class="font-bold">{{ $recharges->lastItem() }}</span> of <span class="font-bold">{{ $recharges->total() }}</span> topup records
                </div>
                <div>
                    {{ $recharges->links() }}
                </div>
            </div>
        @endif
    </div>

    {{-- 5. FLOATING 3-DOT ACTION MENU (AGENTS.md Rule 2.E) --}}
    <div x-show="activeMenu !== null" 
         x-cloak 
         @click.outside="activeMenu = null"
         :style="menuPos"
         class="fixed z-50 w-48 bg-white rounded-xl border border-slate-200 shadow-xl py-1 text-xs text-slate-700 font-medium space-y-0.5 divide-y divide-slate-100"
         style="display: none;">
        
        <div class="py-0.5">
            <button type="button" 
                    @click="fetchReceiptDetails(activeMenuItem.id); activeMenu = null" 
                    class="w-full px-3 py-1.5 text-left hover:bg-slate-50 hover:text-cyan-700 flex items-center gap-2 cursor-pointer transition">
                <i class="fas fa-receipt text-cyan-600 w-4"></i>
                <span>View Voucher Details</span>
            </button>

            <template x-if="activeMenuItem && activeMenuItem.has_slip">
                <a :href="activeMenuItem.slip_url" 
                   target="_blank" 
                   @click="activeMenu = null"
                   class="w-full px-3 py-1.5 text-left hover:bg-slate-50 hover:text-cyan-700 flex items-center gap-2 cursor-pointer transition">
                    <i class="fas fa-file-invoice text-slate-500 w-4"></i>
                    <span>View Deposit Slip</span>
                </a>
            </template>
        </div>
    </div>

    {{-- 6. MODAL 1: TOP-UP REQUEST MODAL (AGENTS.md Rule 3: Natural Soft Modal) --}}
    <div x-show="showTopupModal" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50 overflow-y-auto"
         style="display: none;"
         x-cloak>
        <div @click.outside="showTopupModal = false" 
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden my-6">
            
            {{-- Header --}}
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Submit Wallet Recharge Request</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">Send top-up slip or transaction ID to host ISP for balance credit</p>
                    </div>
                </div>
                <button type="button" @click="showTopupModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            {{-- Form Body --}}
            <form action="{{ route('reseller.recharge.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="p-4 space-y-3">

                    <!-- Amount & Method -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Recharge Amount <span class="text-rose-500">*</span></label>
                            <div class="flex rounded-lg border border-slate-200 overflow-hidden focus-within:border-cyan-500 focus-within:ring-1 focus-within:ring-cyan-500 bg-white shadow-2xs">
                                <span class="inline-flex items-center px-2.5 bg-slate-100 text-slate-700 font-bold text-xs border-r border-slate-200 font-mono select-none">
                                    {{ trim($currencySymbol ?? '৳') }}
                                </span>
                                <input type="number" 
                                       step="0.01" 
                                       min="10" 
                                       name="amount" 
                                       x-model="form.amount" 
                                       required 
                                       placeholder="10000" 
                                       class="w-full px-2.5 py-1.5 text-xs font-mono font-bold text-slate-800 bg-white focus:outline-none">
                            </div>
                        </div>

                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Payment Method <span class="text-rose-500">*</span></label>
                            <select name="payment_method" 
                                    x-model="form.payment_method" 
                                    required 
                                    class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 bg-slate-50 text-xs font-semibold text-slate-800 focus:bg-white focus:border-cyan-500 shadow-2xs">
                                <option value="BKASH">bKash (MFS)</option>
                                <option value="NAGAD">Nagad (MFS)</option>
                                <option value="ROCKET">Rocket (MFS)</option>
                                <option value="BANK_TRANSFER">Bank Deposit / Transfer</option>
                                <option value="CASH">Cash Deposit</option>
                                <option value="ONLINE_GATEWAY">Online PGW / Card</option>
                            </select>
                        </div>
                    </div>

                    <!-- MFS Details -->
                    <template x-if="['BKASH', 'NAGAD', 'ROCKET', 'ONLINE_GATEWAY', 'CARD'].includes(form.payment_method)">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Transaction ID (TrxID) <span class="text-rose-500">*</span></label>
                            <input type="text" 
                                   name="gateway_trx_id" 
                                   placeholder="e.g. BK98X726419" 
                                   class="w-full px-3 py-1.5 rounded-lg border border-slate-200 bg-slate-50 text-xs font-mono uppercase focus:bg-white focus:border-cyan-500 shadow-2xs">
                        </div>
                    </template>

                    <!-- Bank Details -->
                    <template x-if="form.payment_method === 'BANK_TRANSFER'">
                        <div class="space-y-2 p-2.5 rounded-lg bg-blue-50/50 border border-blue-100">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-[10.5px] font-semibold text-slate-700 mb-0.5">Bank Name</label>
                                    <input type="text" name="bank_name" placeholder="e.g. Islami Bank Ltd" class="w-full px-2.5 py-1 rounded-md border border-slate-200 bg-white text-xs">
                                </div>
                                <div>
                                    <label class="block text-[10.5px] font-semibold text-slate-700 mb-0.5">Branch Name</label>
                                    <input type="text" name="bank_branch" placeholder="e.g. Principal Branch" class="w-full px-2.5 py-1 rounded-md border border-slate-200 bg-white text-xs">
                                </div>
                            </div>
                            <div>
                                <label class="block text-[10.5px] font-semibold text-slate-700 mb-0.5">Sender / Cheque Account #</label>
                                <input type="text" name="bank_account_no" placeholder="e.g. 2050123984" class="w-full px-2.5 py-1 rounded-md border border-slate-200 bg-white text-xs font-mono">
                            </div>
                        </div>
                    </template>

                    <!-- Deposit Date & Slip Upload -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Deposit Date</label>
                            <input type="date" 
                                   name="deposit_date" 
                                   value="{{ date('Y-m-d') }}" 
                                   class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 bg-slate-50 text-xs font-mono focus:bg-white focus:border-cyan-500 shadow-2xs">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Deposit Slip / Screenshot</label>
                            <input type="file" 
                                   name="slip" 
                                   accept="image/*,.pdf" 
                                   class="w-full px-2 py-1 rounded-lg border border-slate-200 bg-slate-50 text-xs file:mr-2 file:py-0.5 file:px-2 file:rounded file:border-0 file:text-[10px] file:font-semibold file:bg-slate-200 file:text-slate-700 hover:file:bg-slate-300">
                        </div>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Remarks / Note</label>
                        <textarea name="notes" 
                                  rows="2" 
                                  placeholder="Optional note regarding this top-up..." 
                                  class="w-full px-3 py-1.5 rounded-lg border border-slate-200 bg-slate-50 text-xs focus:bg-white focus:border-cyan-500 shadow-2xs"></textarea>
                    </div>

                </div>

                {{-- Footer --}}
                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" 
                            @click="showTopupModal = false" 
                            class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs px-4 py-1.5 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                        <i class="fas fa-paper-plane text-[10px]"></i>
                        <span>Submit Top-up Request</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- 7. MODAL 2: VOUCHER / RECEIPT DETAILS MODAL (AGENTS.md Rule 3: Natural Soft Modal) --}}
    <div x-show="showReceiptModal" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50 overflow-y-auto"
         style="display: none;"
         x-cloak>
        <div @click.outside="showReceiptModal = false" 
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden my-6">
            
            {{-- Header --}}
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Recharge Voucher Details</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal" x-text="receiptData?.recharge_no || 'Recharge Voucher'"></p>
                    </div>
                </div>
                <button type="button" @click="showReceiptModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            {{-- Body --}}
            <div class="p-4 space-y-3" x-show="receiptData">
                
                <!-- Amount Banner -->
                <div class="p-3 rounded-lg bg-slate-50 border border-slate-200 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-medium uppercase tracking-wider text-slate-500 block">Total Credited Amount</span>
                        <span class="text-lg font-bold font-mono text-emerald-600" x-text="'{{ $currencySymbol ?? '৳' }}' + Number(receiptData?.total_credited || 0).toLocaleString('en-US', {minimumFractionDigits: 2})"></span>
                    </div>
                    <div>
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-bold border" 
                              :class="receiptData?.status_badge?.class">
                            <i class="fas" :class="receiptData?.status_badge?.icon"></i>
                            <span x-text="receiptData?.status_badge?.label"></span>
                        </span>
                    </div>
                </div>

                <!-- 2-Column Info Grid -->
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div class="p-2.5 rounded-lg bg-slate-50/80 border border-slate-200/80">
                        <span class="text-[10px] text-slate-500 block">Recharge Number</span>
                        <span class="font-mono font-bold text-slate-800" x-text="receiptData?.recharge_no"></span>
                    </div>

                    <div class="p-2.5 rounded-lg bg-slate-50/80 border border-slate-200/80">
                        <span class="text-[10px] text-slate-500 block">Payment Method</span>
                        <span class="font-semibold text-slate-800" x-text="receiptData?.payment_method_label"></span>
                    </div>

                    <div class="p-2.5 rounded-lg bg-slate-50/80 border border-slate-200/80">
                        <span class="text-[10px] text-slate-500 block">Base Amount</span>
                        <span class="font-mono font-semibold text-slate-800" x-text="'{{ $currencySymbol ?? '৳' }}' + Number(receiptData?.amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2})"></span>
                    </div>

                    <div class="p-2.5 rounded-lg bg-slate-50/80 border border-slate-200/80">
                        <span class="text-[10px] text-slate-500 block">Bonus Amount</span>
                        <span class="font-mono font-semibold text-cyan-700" x-text="'{{ $currencySymbol ?? '৳' }}' + Number(receiptData?.bonus_amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2})"></span>
                    </div>

                    <template x-if="receiptData?.gateway_trx_id">
                        <div class="p-2.5 rounded-lg bg-slate-50/80 border border-slate-200/80 col-span-2">
                            <span class="text-[10px] text-slate-500 block">Transaction TrxID</span>
                            <span class="font-mono font-bold text-slate-800 select-all" x-text="receiptData?.gateway_trx_id"></span>
                        </div>
                    </template>

                    <template x-if="receiptData?.bank_name">
                        <div class="p-2.5 rounded-lg bg-slate-50/80 border border-slate-200/80 col-span-2">
                            <span class="text-[10px] text-slate-500 block">Bank Details</span>
                            <span class="font-semibold text-slate-800" x-text="receiptData?.bank_name + (receiptData?.bank_branch ? ' (' + receiptData?.bank_branch + ')' : '')"></span>
                            <span class="block text-[11px] font-mono text-slate-600" x-text="'Account: ' + (receiptData?.bank_account_no || 'N/A')"></span>
                        </div>
                    </template>

                    <div class="p-2.5 rounded-lg bg-slate-50/80 border border-slate-200/80">
                        <span class="text-[10px] text-slate-500 block">Deposit Date</span>
                        <span class="font-mono text-slate-800" x-text="receiptData?.deposit_date || 'N/A'"></span>
                    </div>

                    <div class="p-2.5 rounded-lg bg-slate-50/80 border border-slate-200/80">
                        <span class="text-[10px] text-slate-500 block">Requested At</span>
                        <span class="font-mono text-slate-800" x-text="receiptData?.created_at || 'N/A'"></span>
                    </div>

                    <template x-if="receiptData?.approved_by">
                        <div class="p-2.5 rounded-lg bg-emerald-50/50 border border-emerald-200/80 col-span-2">
                            <span class="text-[10px] text-emerald-700 block font-medium">Approved By</span>
                            <span class="font-semibold text-emerald-900" x-text="receiptData?.approved_by"></span>
                            <span class="text-[10.5px] font-mono text-emerald-700 block" x-text="receiptData?.approved_at"></span>
                        </div>
                    </template>

                    <template x-if="receiptData?.rejection_reason">
                        <div class="p-2.5 rounded-lg bg-rose-50/50 border border-rose-200/80 col-span-2">
                            <span class="text-[10px] text-rose-700 block font-medium">Rejection Reason</span>
                            <span class="text-xs font-semibold text-rose-900" x-text="receiptData?.rejection_reason"></span>
                        </div>
                    </template>

                    <template x-if="receiptData?.notes">
                        <div class="p-2.5 rounded-lg bg-slate-50/80 border border-slate-200/80 col-span-2">
                            <span class="text-[10px] text-slate-500 block">Remarks</span>
                            <span class="text-xs text-slate-700" x-text="receiptData?.notes"></span>
                        </div>
                    </template>
                </div>

                <!-- Slip Link if present -->
                <template x-if="receiptData?.slip_url">
                    <div class="p-2.5 rounded-lg bg-blue-50/50 border border-blue-200/80 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-paperclip text-blue-600"></i>
                            <span class="text-xs font-semibold text-blue-900">Attached Deposit Slip</span>
                        </div>
                        <a :href="receiptData?.slip_url" target="_blank" class="px-2.5 py-1 bg-cyan-600 hover:bg-cyan-700 text-white rounded text-xs font-semibold transition">
                            View Attachment
                        </a>
                    </div>
                </template>

            </div>

            {{-- Footer --}}
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end">
                <button type="button" 
                        @click="showReceiptModal = false" 
                        class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-4 py-1.5 rounded-lg transition cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>

    {{-- 8. MODAL 3: INSTANT ONLINE RECHARGE MODAL --}}
    <div x-show="showOnlineModal" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4 fixed inset-0 z-50 overflow-y-auto"
         style="display: none;"
         x-cloak>
        <div @click.outside="showOnlineModal = false" 
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-md overflow-hidden flex flex-col max-h-[92vh] my-auto">
            
            {{-- Fixed Header --}}
            <div class="bg-slate-50 border-b border-slate-200 px-4 py-2.5 flex items-center justify-between flex-shrink-0">
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-md bg-pink-50 text-pink-600 border border-pink-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <h3 class="text-xs font-bold text-slate-800">Online Wallet Recharge</h3>
                </div>
                <button type="button" @click="showOnlineModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            {{-- Scrollable Form Body --}}
            <form @submit.prevent="submitOnlineRecharge()" class="flex flex-col flex-1 overflow-hidden">
                <div class="p-3.5 space-y-3 overflow-y-auto flex-1">

                    <!-- 1. Amount & Quick Preset Buttons -->
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <label class="block text-[11px] font-bold text-slate-700">Recharge Amount <span class="text-rose-500">*</span></label>
                            <span class="text-[10px] text-slate-500 font-mono">Wallet: @currency($stats['wallet_balance'])</span>
                        </div>
                        
                        <!-- Clean Input Group -->
                        <div class="flex rounded-lg border border-slate-200 overflow-hidden focus-within:border-pink-500 focus-within:ring-1 focus-within:ring-pink-500 bg-white shadow-2xs">
                            <span class="inline-flex items-center px-2.5 bg-slate-100 text-slate-700 font-bold text-xs border-r border-slate-200 font-mono select-none">
                                {{ trim($currencySymbol ?? '৳') }}
                            </span>
                            <input type="number" 
                                   step="1" 
                                   min="10" 
                                   x-model="onlineForm.amount" 
                                   required 
                                   placeholder="1000" 
                                   class="w-full px-2.5 py-1.5 text-xs font-mono font-bold text-slate-900 bg-white focus:outline-none">
                        </div>

                        <!-- Quick Pills -->
                        <div class="flex flex-wrap items-center gap-1 pt-0.5">
                            @foreach([500, 1000, 2000, 5000, 10000] as $preset)
                                <button type="button" 
                                        @click="setPresetAmount({{ $preset }})" 
                                        class="px-2 py-0.5 rounded text-[10px] font-mono font-semibold transition cursor-pointer border"
                                        :class="onlineForm.amount == {{ $preset }} ? 'bg-pink-600 text-white border-pink-600 shadow-2xs' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'">
                                    +{{ number_format($preset) }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- 2. Payment Channel Tabs -->
                    <div class="space-y-1">
                        <label class="block text-[11px] font-bold text-slate-700">Payment Channel <span class="text-rose-500">*</span></label>
                        <div class="grid grid-cols-3 gap-1.5 p-1 bg-slate-100/80 rounded-lg border border-slate-200/80">
                            
                            <!-- Tab 1: bKash Merchant -->
                            <button type="button" 
                                    @click="selectChannel('BKASH_MERCHANT')" 
                                    class="py-1.5 px-2 rounded-md text-center transition cursor-pointer flex items-center justify-center gap-1 text-[11px] font-bold"
                                    :class="onlineForm.gateway_channel === 'BKASH_MERCHANT' ? 'bg-white text-pink-700 shadow-xs border border-pink-200' : 'text-slate-600 hover:text-slate-900'">
                                <i class="fas fa-mobile-screen text-[10px] text-pink-600"></i>
                                <span>bKash</span>
                            </button>

                            <!-- Tab 2: Bangla QR -->
                            <button type="button" 
                                    @click="selectChannel('BANGLA_QR')" 
                                    class="py-1.5 px-2 rounded-md text-center transition cursor-pointer flex items-center justify-center gap-1 text-[11px] font-bold"
                                    :class="onlineForm.gateway_channel === 'BANGLA_QR' ? 'bg-white text-teal-700 shadow-xs border border-teal-200' : 'text-slate-600 hover:text-slate-900'">
                                <i class="fas fa-qrcode text-[10px] text-teal-600"></i>
                                <span>Bangla QR</span>
                            </button>

                            <!-- Tab 3: Bank & Cards -->
                            <button type="button" 
                                    @click="selectChannel('BANK_GATEWAY')" 
                                    class="py-1.5 px-2 rounded-md text-center transition cursor-pointer flex items-center justify-center gap-1 text-[11px] font-bold"
                                    :class="onlineForm.gateway_channel === 'BANK_GATEWAY' ? 'bg-white text-blue-700 shadow-xs border border-blue-200' : 'text-slate-600 hover:text-slate-900'">
                                <i class="fas fa-building-columns text-[10px] text-blue-600"></i>
                                <span>Bank / Card</span>
                            </button>

                        </div>
                    </div>

                    <!-- 3. Channel Instructions Box -->
                    
                    <!-- Channel 1: bKash Merchant -->
                    <template x-if="onlineForm.gateway_channel === 'BKASH_MERCHANT'">
                        <div class="space-y-2.5">
                            <!-- Direct 1-Click bKash API Checkout Card -->
                            <div class="p-3 rounded-lg bg-gradient-to-r from-pink-50 via-rose-50 to-pink-50 border border-pink-200 shadow-2xs space-y-2">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                        <span class="text-[11px] font-bold text-pink-900">Direct bKash Merchant API</span>
                                    </div>
                                    <span class="text-[9.5px] px-1.5 py-0.5 rounded bg-pink-100 text-pink-700 font-mono font-bold">1-Click Auto Credit</span>
                                </div>
                                <p class="text-[10.5px] text-slate-600 leading-tight">
                                    Pay securely using official bKash Payment Gateway (PIN/OTP). Wallet balance will be credited instantly in real-time.
                                </p>
                                <button type="button" 
                                        @click="payWithBkashApi()" 
                                        :disabled="isInitiatingBkash || !onlineForm.amount || onlineForm.amount < 10" 
                                        class="w-full py-2 px-3 rounded-lg bg-gradient-to-r from-pink-600 to-rose-600 hover:from-pink-700 hover:to-rose-700 disabled:opacity-50 text-white font-bold text-xs shadow-xs transition flex items-center justify-center gap-2 cursor-pointer">
                                    <template x-if="isInitiatingBkash">
                                        <i class="fas fa-spinner fa-spin text-xs"></i>
                                    </template>
                                    <template x-if="!isInitiatingBkash">
                                        <i class="fas fa-bolt text-xs"></i>
                                    </template>
                                    <span x-text="isInitiatingBkash ? 'Connecting bKash Gateway...' : 'Pay ৳' + Number(onlineForm.amount || 0).toLocaleString() + ' with bKash Checkout'"></span>
                                </button>
                            </div>

                            <!-- Manual TrxID Alternative -->
                            <div class="p-2.5 rounded-lg bg-slate-50/90 border border-slate-200 text-xs space-y-1.5">
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] font-semibold text-slate-600 uppercase tracking-wide">Or Manual App Payment:</span>
                                    <div class="flex items-center gap-1 font-mono font-bold text-slate-800 text-[11px]">
                                        <span>{{ $bkashMerchantNumber ?? '01819-000000' }}</span>
                                        <button type="button" 
                                                @click="copyText('{{ $bkashMerchantNumber ?? '01819-000000' }}', 'Merchant Number')" 
                                                class="text-pink-600 hover:text-pink-800 cursor-pointer p-0.5" title="Copy">
                                            <i class="fas fa-copy text-[10px]"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="text-[10px] text-slate-500 leading-tight border-t border-slate-200/80 pt-1">
                                    bKash App $\rightarrow$ Make Payment $\rightarrow$ <strong>{{ $bkashMerchantNumber ?? '01819-000000' }}</strong> $\rightarrow$ Ref: <strong class="font-mono text-slate-700">{{ $reseller->code ?? 'RES-01' }}</strong>. Paste <strong>TrxID</strong> below:
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Channel 2: Bangla QR -->
                    <template x-if="onlineForm.gateway_channel === 'BANGLA_QR'">
                        <div class="p-2.5 rounded-lg bg-teal-50/50 border border-teal-100 text-xs flex items-center gap-3">
                            <div class="w-16 h-16 bg-white p-1 rounded-md border border-teal-200 flex-shrink-0 flex items-center justify-center">
                                <svg class="w-14 h-14" viewBox="0 0 100 100" fill="none">
                                    <rect width="100" height="100" fill="white" rx="4"/>
                                    <rect x="8" y="8" width="24" height="24" rx="2" fill="#0f766e"/>
                                    <rect x="12" y="12" width="16" height="16" rx="1" fill="white"/>
                                    <rect x="15" y="15" width="10" height="10" fill="#0f766e"/>
                                    <rect x="68" y="8" width="24" height="24" rx="2" fill="#0f766e"/>
                                    <rect x="72" y="12" width="16" height="16" rx="1" fill="white"/>
                                    <rect x="75" y="15" width="10" height="10" fill="#0f766e"/>
                                    <rect x="8" y="68" width="24" height="24" rx="2" fill="#0f766e"/>
                                    <rect x="12" y="72" width="16" height="16" rx="1" fill="white"/>
                                    <rect x="15" y="75" width="10" height="10" fill="#0f766e"/>
                                    <circle cx="50" cy="50" r="10" fill="#0d9488"/>
                                    <text x="50" y="53" font-size="6" font-family="sans-serif" font-weight="bold" fill="white" text-anchor="middle">বাংলা QR</text>
                                </svg>
                            </div>
                            <div class="text-[10.5px] text-slate-600 leading-tight space-y-0.5">
                                <span class="font-bold text-teal-800 block text-[11px]">Interoperable Bangla QR</span>
                                <span>Scan with <strong>bKash, Nagad, Rocket, Cellfin, CityTouch</strong> app and enter the Transaction ID below.</span>
                            </div>
                        </div>
                    </template>

                    <!-- Channel 3: Bank & Cards -->
                    <template x-if="onlineForm.gateway_channel === 'BANK_GATEWAY'">
                        <div class="p-2.5 rounded-lg bg-blue-50/50 border border-blue-100 text-xs space-y-1">
                            <div class="flex items-center justify-between text-[11px]">
                                <span class="font-bold text-slate-800">{{ $gateways['bank']->credentials['bank_name'] ?? 'The City Bank Ltd.' }}</span>
                                <span class="text-[9px] px-1.5 py-0.2 rounded bg-blue-100 text-blue-700 font-semibold">NPSB / BEFTN</span>
                            </div>
                            <div class="flex items-center justify-between text-[10.5px] text-slate-600 font-mono">
                                <span>A/C: <strong class="text-slate-800">{{ $gateways['bank']->credentials['account_number'] ?? '1102983746001' }}</strong></span>
                                <span>Routing: <strong>{{ $gateways['bank']->credentials['routing_number'] ?? '225271890' }}</strong></span>
                            </div>
                        </div>
                    </template>

                    <!-- 4. Mandatory TrxID Input -->
                    <div class="space-y-1">
                        <label class="block text-[11px] font-bold text-slate-800">
                            <span x-text="onlineForm.gateway_channel === 'BKASH_MERCHANT' ? 'Manual Transaction ID (If paid manually via bKash App)' : 'Transaction ID (TrxID) / Reference Number'"></span>
                            <span class="text-rose-500" x-show="onlineForm.gateway_channel !== 'BKASH_MERCHANT'">*</span>
                        </label>
                        <input type="text" 
                               x-model="onlineForm.trx_id" 
                               :required="onlineForm.gateway_channel !== 'BKASH_MERCHANT'" 
                               placeholder="e.g. BK98X726419 or FT2609081234" 
                               class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 bg-slate-50 text-xs font-mono font-bold uppercase text-slate-900 focus:bg-white focus:outline-none focus:border-pink-500 shadow-2xs">
                    </div>

                    <!-- Error Alert -->
                    <div x-show="onlineError" x-cloak class="p-2 rounded-lg bg-rose-50 border border-rose-200 text-rose-800 text-[11px] flex items-center gap-1.5">
                        <i class="fas fa-circle-exclamation text-rose-600 text-xs"></i>
                        <span x-text="onlineError"></span>
                    </div>

                </div>

                {{-- Fixed Footer --}}
                <div class="px-4 py-2.5 bg-slate-50 border-t border-slate-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button type="button" 
                            @click="showOnlineModal = false" 
                            class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" 
                            :disabled="isSubmittingOnline || !onlineForm.amount || !onlineForm.trx_id" 
                            class="bg-gradient-to-r from-pink-600 to-rose-600 hover:from-pink-700 hover:to-rose-700 disabled:opacity-50 text-white font-semibold text-xs px-4 py-1.5 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                        <template x-if="isSubmittingOnline">
                            <i class="fas fa-spinner fa-spin text-xs"></i>
                        </template>
                        <template x-if="!isSubmittingOnline">
                            <i class="fas fa-bolt text-xs"></i>
                        </template>
                        <span x-text="isSubmittingOnline ? 'Processing...' : 'Credit Wallet Instantly'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function rechargeManager() {
    return {
        activeMenu: null,
        activeMenuItem: null,
        menuPos: {},
        showTopupModal: false,
        showReceiptModal: false,
        showOnlineModal: false,
        receiptData: null,
        isSubmittingOnline: false,
        isInitiatingBkash: false,
        onlineError: null,
        
        form: {
            amount: '',
            payment_method: 'BKASH'
        },

        onlineForm: {
            amount: 1000,
            gateway_channel: 'BKASH_MERCHANT',
            trx_id: '',
            bank_name: '',
            bank_account_no: '',
            notes: ''
        },

        async payWithBkashApi() {
            if (!this.onlineForm.amount || this.onlineForm.amount < 10) {
                this.onlineError = "Please enter a valid recharge amount (minimum ৳10).";
                return;
            }

            this.isInitiatingBkash = true;
            this.onlineError = null;

            try {
                const response = await fetch("{{ route('reseller.recharge.bkash.initiate') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ amount: this.onlineForm.amount })
                });

                const data = await response.json();

                if (data.success && data.redirect_url) {
                    window.location.href = data.redirect_url;
                } else {
                    this.onlineError = data.message || "Failed to initiate bKash payment gateway session.";
                }
            } catch (err) {
                console.error("bKash initiation error:", err);
                this.onlineError = "Network error connecting to bKash gateway. Please check connection.";
            } finally {
                this.isInitiatingBkash = false;
            }
        },

        openTopupModal() {
            this.form = {
                amount: '',
                payment_method: 'BKASH'
            };
            this.showTopupModal = true;
        },

        openOnlineModal() {
            this.onlineForm = {
                amount: 1000,
                gateway_channel: 'BKASH_MERCHANT',
                trx_id: '',
                bank_name: '',
                bank_account_no: '',
                notes: ''
            };
            this.onlineError = null;
            this.showOnlineModal = true;
        },

        selectChannel(channel) {
            this.onlineForm.gateway_channel = channel;
        },

        setPresetAmount(amt) {
            this.onlineForm.amount = amt;
        },

        copyText(text, label) {
            navigator.clipboard.writeText(text).then(() => {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Copied!',
                        text: `${label} copied to clipboard: ${text}`,
                        timer: 1500,
                        showConfirmButton: false
                    });
                } else {
                    alert(`${label} copied: ${text}`);
                }
            }).catch(err => {
                console.error("Copy failed", err);
            });
        },

        async submitOnlineRecharge() {
            if (!this.onlineForm.amount || this.onlineForm.amount < 10) {
                this.onlineError = "Please enter a valid recharge amount (minimum ৳10).";
                return;
            }
            if (!this.onlineForm.trx_id || this.onlineForm.trx_id.trim() === '') {
                this.onlineError = "Transaction ID (TrxID) / Reference Number is required.";
                return;
            }

            this.isSubmittingOnline = true;
            this.onlineError = null;

            try {
                const response = await fetch("{{ route('reseller.recharge.online') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(this.onlineForm)
                });

                const data = await response.json();

                if (data.success) {
                    this.showOnlineModal = false;
                    if (typeof Swal !== 'undefined') {
                        await Swal.fire({
                            icon: 'success',
                            title: 'Recharge Successful!',
                            text: data.message,
                            confirmButtonColor: '#0891b2',
                        });
                        window.location.reload();
                    } else {
                        alert(data.message);
                        window.location.reload();
                    }
                } else {
                    this.onlineError = data.message || "Failed to process online recharge. Please check your inputs.";
                }
            } catch (err) {
                console.error("Online recharge error:", err);
                this.onlineError = "Network error. Please try again or contact support.";
            } finally {
                this.isSubmittingOnline = false;
            }
        },

        toggleMenu(item, event) {
            if (this.activeMenu === item.id) {
                this.activeMenu = null;
                this.activeMenuItem = null;
                return;
            }
            this.activeMenu = item.id;
            this.activeMenuItem = item;

            const rect = event.currentTarget.getBoundingClientRect();
            const dropdownHeight = 120;
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

        async fetchReceiptDetails(id) {
            try {
                const res = await fetch(`/reseller/recharge/${id}/receipt`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    this.receiptData = data.data;
                    this.showReceiptModal = true;
                }
            } catch (err) {
                console.error("Error fetching voucher:", err);
            }
        }
    };
}
</script>
@endpush
