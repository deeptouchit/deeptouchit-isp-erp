@extends('tenant.layouts.app')

@section('title', 'Reseller Recharge History - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
    {{-- Page specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="resellerRechargeManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">

    <!-- 1. Top Header Bar (Strictly Icon + Title + Action Button ONLY) -->
    <div class="flex items-center justify-between bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-3">
            <div class="w-7 h-7 rounded-lg bg-purple-50 text-purple-600 border border-purple-100 flex items-center justify-center text-xs flex-shrink-0">
                <i class="fas fa-clock-rotate-left"></i>
            </div>
            <h1 class="text-sm font-bold text-slate-900 tracking-tight">Reseller Recharge History</h1>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" 
                    @click="openNewRechargeModal()"
                    class="px-3 py-1.5 rounded-lg bg-purple-600 hover:bg-purple-700 text-white font-semibold text-xs shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-circle-dollar-to-slot text-xs"></i>
                <span>Submit Recharge Entry</span>
            </button>
        </div>
    </div>

    <!-- 2. KPI Summary Strip (Strictly 6 Cards) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        <!-- Card 1: Total Recharged -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Total Recharged</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-slate-900">@currency($totalRechargedEver)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-purple-50 text-purple-600 border-purple-100 flex items-center justify-center">
                <i class="fas fa-wallet"></i>
            </div>
        </div>

        <!-- Card 2: Recharged This Month -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-purple-600">This Month</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-purple-700">@currency($totalRechargedThisMonth)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-purple-50 text-purple-600 border-purple-100 flex items-center justify-center">
                <i class="fas fa-calendar-check"></i>
            </div>
        </div>

        <!-- Card 3: Today's Recharge -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-emerald-600">Today</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-emerald-700">@currency($totalRechargedToday)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-emerald-50 text-emerald-600 border-emerald-100 flex items-center justify-center">
                <i class="fas fa-circle-dollar-to-slot"></i>
            </div>
        </div>

        <!-- Card 4: Pending Approvals -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-amber-600">Pending Approval</span>
                <span class="text-[13px] font-bold font-mono leading-tight block {{ $pendingCount > 0 ? 'text-amber-600' : 'text-slate-700' }}">
                    {{ number_format($pendingCount) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-amber-50 text-amber-600 border-amber-100 flex items-center justify-center">
                <i class="fas fa-clock"></i>
            </div>
        </div>

        <!-- Card 5: Online Gateway -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-pink-600">Online PGW</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-pink-700">{{ number_format($onlinePgwCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-pink-50 text-pink-600 border-pink-100 flex items-center justify-center">
                <i class="fas fa-mobile-screen"></i>
            </div>
        </div>

        <!-- Card 6: Bank & Cash -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-blue-600">Bank / Cash</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-blue-700">{{ number_format($bankAndCashCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-blue-50 text-blue-600 border-blue-100 flex items-center justify-center">
                <i class="fas fa-building-columns"></i>
            </div>
        </div>
    </div>

    <!-- 3. Search & Multi-Filter Toolbar -->
    <div class="bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('tenant.resellers.recharge') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-7 gap-2">
            
            <!-- Search Input -->
            <div class="lg:col-span-2 relative">
                <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" 
                       name="search" 
                       value="{{ request('search') }}" 
                       placeholder="Search Recharge #, TrxID, Reseller..." 
                       class="w-full pl-8 pr-3 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50 focus:bg-white focus:border-purple-500 transition">
            </div>

            <!-- Reseller Filter -->
            <div>
                <select name="reseller_id" 
                        onchange="this.form.submit()" 
                        class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50 focus:bg-white focus:border-purple-500">
                    <option value="">All Resellers</option>
                    @foreach($allResellers as $res)
                        <option value="{{ $res->id }}" {{ (string)request('reseller_id') === (string)$res->id ? 'selected' : '' }}>
                            {{ $res->name }} ({{ $res->code }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Status Filter -->
            <div>
                <select name="status" 
                        onchange="this.form.submit()" 
                        class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50 focus:bg-white focus:border-purple-500">
                    <option value="">All Statuses</option>
                    <option value="APPROVED" {{ request('status') === 'APPROVED' ? 'selected' : '' }}>Approved</option>
                    <option value="PENDING" {{ request('status') === 'PENDING' ? 'selected' : '' }}>Pending Approval</option>
                    <option value="REJECTED" {{ request('status') === 'REJECTED' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>

            <!-- Payment Method Filter -->
            <div>
                <select name="payment_method" 
                        onchange="this.form.submit()" 
                        class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50 focus:bg-white focus:border-purple-500">
                    <option value="">All Methods</option>
                    <option value="BKASH" {{ request('payment_method') === 'BKASH' ? 'selected' : '' }}>bKash</option>
                    <option value="NAGAD" {{ request('payment_method') === 'NAGAD' ? 'selected' : '' }}>Nagad</option>
                    <option value="ROCKET" {{ request('payment_method') === 'ROCKET' ? 'selected' : '' }}>Rocket</option>
                    <option value="BANK_TRANSFER" {{ request('payment_method') === 'BANK_TRANSFER' ? 'selected' : '' }}>Bank Transfer</option>
                    <option value="CASH" {{ request('payment_method') === 'CASH' ? 'selected' : '' }}>Cash</option>
                    <option value="ONLINE_GATEWAY" {{ request('payment_method') === 'ONLINE_GATEWAY' ? 'selected' : '' }}>Online Gateway</option>
                </select>
            </div>

            <!-- Per Page Selector -->
            <div>
                <select name="per_page" 
                        onchange="this.form.submit()" 
                        class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50 focus:bg-white focus:border-purple-500">
                    @foreach([10, 20, 50, 100] as $size)
                        <option value="{{ $size }}" {{ (int)request('per_page', $perPage) === $size ? 'selected' : '' }}>
                            {{ $size }} per page
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Action Buttons (Strict Universal Standard) -->
            <div class="flex items-center gap-1.5 flex-shrink-0">
                <button type="submit" 
                        class="flex-1 sm:flex-initial bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-1.5 px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer" 
                        title="Apply Filters">
                    <i class="fas fa-filter text-[10px]"></i>
                    <span>Filter</span>
                </button>
                <a href="{{ route('tenant.resellers.recharge') }}" 
                   class="flex-1 sm:flex-initial bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-1.5 px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer" 
                   title="Reset Filters">
                    <i class="fas fa-rotate-left text-[10px]"></i>
                    <span>Reset</span>
                </a>
            </div>
        </form>
    </div>

    <!-- 4. Master Table (<table class="saas-table">) -->
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-10 text-center">#</th>
                        <th>Recharge #</th>
                        <th>Date &amp; Time</th>
                        <th>Reseller Partner</th>
                        <th class="text-right">Amount</th>
                        <th class="text-right">Bonus</th>
                        <th class="text-right">Total Credited</th>
                        <th class="text-center">Method</th>
                        <th class="text-center">Status</th>
                        <th class="w-10 text-center no-sort">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recharges as $index => $r)
                        <tr>
                            <!-- 1. Index -->
                            <td class="text-center font-mono text-slate-400">
                                {{ $recharges->firstItem() + $index }}
                            </td>

                            <!-- 2. Recharge # -->
                            <td class="font-mono text-purple-700 font-semibold">
                                <button type="button" 
                                        @click="viewReceipt({{ $r->id }})" 
                                        class="hover:underline cursor-pointer">
                                    {{ $r->recharge_no }}
                                </button>
                            </td>

                            <!-- 3. Date & Time -->
                            <td class="font-mono text-slate-600">
                                {{ $r->created_at->format('d M Y, h:i A') }}
                            </td>

                            <!-- 4. Reseller Partner -->
                            <td class="font-medium text-slate-900">
                                {{ $r->reseller?->name ?? 'N/A' }}
                            </td>

                            <!-- 5. Amount -->
                            <td class="text-right font-mono font-medium text-slate-800">
                                @currency($r->amount)
                            </td>

                            <!-- 6. Bonus -->
                            <td class="text-right font-mono text-emerald-600">
                                @if($r->bonus_amount > 0)+@currency($r->bonus_amount)@else--@endif
                            </td>

                            <!-- 7. Total Credited -->
                            <td class="text-right font-mono font-bold text-purple-700">
                                @currency($r->total_credited)
                            </td>

                            <!-- 8. Method -->
                            <td class="text-center">
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                    {{ $r->payment_method_badge['label'] ?? $r->payment_method }}
                                </span>
                            </td>

                            <!-- 9. Status -->
                            <td class="text-center">
                                @if($r->status === 'APPROVED')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <i class="fas fa-check-circle text-[8px]"></i>
                                        <span>Approved</span>
                                    </span>
                                @elseif($r->status === 'PENDING')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                        <i class="fas fa-clock text-[8px]"></i>
                                        <span>Pending</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                        <i class="fas fa-times-circle text-[8px]"></i>
                                        <span>Rejected</span>
                                    </span>
                                @endif
                            </td>

                            <!-- 11. Action (3-Dot) -->
                            <td class="text-center">
                                <button type="button" 
                                        @click.stop="toggleMenu({{ Js::from($r) }}, $event)" 
                                        class="w-6 h-6 rounded hover:bg-slate-100 text-slate-500 hover:text-purple-600 transition cursor-pointer inline-flex items-center justify-center text-xs"
                                        title="Actions">
                                    <i class="fas fa-ellipsis-v text-[10px]"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-8 text-slate-400">
                                <div class="max-w-md mx-auto space-y-2">
                                    <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 mx-auto flex items-center justify-center text-base border border-purple-100">
                                        <i class="fas fa-receipt"></i>
                                    </div>
                                    <h3 class="text-xs font-semibold text-slate-800">No Recharge Records Found</h3>
                                    <p class="text-[11px] text-slate-500">Submit a manual recharge entry or await online gateway payments from resellers.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($recharges->hasPages())
            <div class="px-3.5 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between text-xs text-slate-600">
                <div>
                    Showing {{ $recharges->firstItem() }} to {{ $recharges->lastItem() }} of {{ $recharges->total() }} entries
                </div>
                <div>
                    {{ $recharges->links() }}
                </div>
            </div>
        @endif
    </div>

    <!-- 5. Floating Action Dropdown Menu -->
    <div x-show="activeMenu !== null" 
         @click.away="activeMenu = null"
         :style="menuPos"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="fixed z-50 w-52 bg-white rounded-xl shadow-xl border border-slate-200 py-1.5 text-xs text-slate-700 divide-y divide-slate-100"
         style="display: none;">
        
        <div class="py-0.5">
            <button type="button" 
                    @click="const r = activeMenu; activeMenu = null; viewReceipt(r.id)" 
                    class="w-full px-3 py-1.5 text-left hover:bg-slate-50 flex items-center gap-2 text-slate-700 transition">
                <i class="fas fa-file-invoice text-purple-600 w-3.5 text-[11px]"></i>
                <span>View Recharge Voucher</span>
            </button>
            <a :href="`/admin/resellers/${activeMenu?.reseller_id}`" 
               class="w-full px-3 py-1.5 text-left hover:bg-slate-50 flex items-center gap-2 text-slate-700 transition">
                <i class="fas fa-user-gear text-slate-500 w-3.5 text-[11px]"></i>
                <span>Reseller Profile</span>
            </a>
        </div>

        <template x-if="activeMenu?.status === 'PENDING'">
            <div class="py-0.5">
                <button type="button" 
                        @click="const r = activeMenu; activeMenu = null; approveRecharge(r.id)" 
                        class="w-full px-3 py-1.5 text-left hover:bg-emerald-50 text-emerald-700 flex items-center gap-2 transition font-medium">
                    <i class="fas fa-check-circle text-emerald-600 w-3.5 text-[11px]"></i>
                    <span>Approve Recharge</span>
                </button>
                <button type="button" 
                        @click="const r = activeMenu; activeMenu = null; openRejectModal(r)" 
                        class="w-full px-3 py-1.5 text-left hover:bg-rose-50 text-rose-700 flex items-center gap-2 transition font-medium">
                    <i class="fas fa-times-circle text-rose-600 w-3.5 text-[11px]"></i>
                    <span>Reject Request</span>
                </button>
            </div>
        </template>
    </div>

    <!-- 6. Modals -->
    <!-- Modal 1: Submit Recharge Entry Modal -->
    <div x-show="showCreateModal" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50"
         style="display: none;">
        
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-xl overflow-hidden"
             @click.away="showCreateModal = false">
            
            <!-- Soft Natural Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-purple-50 text-purple-600 border border-purple-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-circle-dollar-to-slot"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Submit Reseller Recharge Entry</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">Record direct cash, bank deposit or gateway top-up</p>
                    </div>
                </div>
                <button type="button" @click="showCreateModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <!-- Form -->
            <form @submit.prevent="submitRecharge()" class="p-4 space-y-3 text-xs">
                <!-- Reseller Picker -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                        Select Reseller Partner <span class="text-rose-500">*</span>
                    </label>
                    <select x-model="form.reseller_id" 
                            required 
                            class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-purple-500">
                        <option value="">-- Choose Reseller --</option>
                        @foreach($allResellers as $r)
                            <option value="{{ $r->id }}">{{ $r->name }} ({{ $r->code }}) — Balance: ৳{{ number_format($r->wallet_balance, 2) }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Amount & Bonus -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            Recharge Amount (৳) <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" 
                               step="0.01" 
                               min="1" 
                               x-model="form.amount" 
                               required 
                               placeholder="e.g. 10000" 
                               class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono px-3 py-1.5 focus:bg-white focus:border-purple-500">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            Bonus Amount (৳)
                        </label>
                        <input type="number" 
                               step="0.01" 
                               min="0" 
                               x-model="form.bonus_amount" 
                               placeholder="0.00" 
                               class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono px-3 py-1.5 focus:bg-white focus:border-purple-500">
                    </div>
                </div>

                <!-- Payment Method & Status -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            Payment Method <span class="text-rose-500">*</span>
                        </label>
                        <select x-model="form.payment_method" 
                                required 
                                class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-purple-500">
                            <option value="CASH">Cash Deposit</option>
                            <option value="BANK_TRANSFER">Bank Transfer / EFT</option>
                            <option value="BKASH">bKash Merchant / Personal</option>
                            <option value="NAGAD">Nagad Direct</option>
                            <option value="ROCKET">Rocket Direct</option>
                            <option value="CHEQUE">Cheque / Demand Draft</option>
                            <option value="ONLINE_GATEWAY">Online Gateway</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            Initial Status <span class="text-rose-500">*</span>
                        </label>
                        <select x-model="form.status" 
                                required 
                                class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-purple-500">
                            <option value="APPROVED">Direct Approved (Credit Wallet Now)</option>
                            <option value="PENDING">Pending Approval (Review Later)</option>
                        </select>
                    </div>
                </div>

                <!-- Gateway Trx ID & Bank Details -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            Gateway Trx ID / Deposit Slip
                        </label>
                        <input type="text" 
                               x-model="form.gateway_trx_id" 
                               placeholder="e.g. 9J87K654" 
                               class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono px-3 py-1.5 focus:bg-white focus:border-purple-500">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            Deposit Date
                        </label>
                        <input type="date" 
                               x-model="form.deposit_date" 
                               class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono px-3 py-1.5 focus:bg-white focus:border-purple-500">
                    </div>
                </div>

                <!-- Remarks / Notes -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                        Operational Notes
                    </label>
                    <input type="text" 
                           x-model="form.notes" 
                           placeholder="Optional notes or remarks..." 
                           class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-purple-500">
                </div>

                <!-- Summary Preview -->
                <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200 text-[11px] flex items-center justify-between">
                    <span class="text-slate-600 font-medium">Total Credited to Wallet:</span>
                    <span class="font-mono font-bold text-purple-700 text-xs" x-text="calculateTotalCredited()"></span>
                </div>

                <!-- Footer Actions -->
                <div class="pt-2 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="showCreateModal = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition">
                        Cancel
                    </button>
                    <button type="submit" 
                            :disabled="submitting"
                            class="bg-purple-600 hover:bg-purple-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition inline-flex items-center gap-1.5 disabled:opacity-50">
                        <i class="fas fa-check text-xs" :class="{ 'fa-spin fa-spinner': submitting }"></i>
                        <span x-text="submitting ? 'Saving...' : 'Save Recharge'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 2: Printable Receipt / Voucher Modal -->
    <div x-show="receiptModal.open" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50"
         style="display: none;">
        
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-md overflow-hidden"
             @click.away="receiptModal.open = false">
            
            <!-- Soft Natural Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-purple-50 text-purple-600 border border-purple-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Recharge Receipt Voucher</h3>
                        <p class="text-[10.5px] text-slate-500 font-mono" x-text="receiptModal.data?.recharge_no"></p>
                    </div>
                </div>
                <button type="button" @click="receiptModal.open = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <!-- Printable Voucher Body -->
            <div id="printableReceipt" class="p-4 space-y-3.5 text-xs">
                <!-- Amount Highlight Card -->
                <div class="p-3.5 rounded-xl border border-purple-200 bg-purple-50/60 text-center space-y-1">
                    <span class="text-[10px] uppercase font-semibold block tracking-wider text-purple-700">Total Credited Amount</span>
                    <span class="text-xl font-bold font-mono block leading-tight text-purple-900" x-text="'৳' + parseFloat(receiptModal.data?.total_credited || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })"></span>
                </div>

                <!-- Details Table -->
                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200/80 space-y-2 text-[11px]">
                    <div class="flex items-center justify-between py-1 border-b border-slate-200">
                        <span class="text-slate-500">Reseller Partner:</span>
                        <span class="font-semibold text-slate-800" x-text="receiptModal.data?.reseller_name"></span>
                    </div>
                    <div class="flex items-center justify-between py-1 border-b border-slate-200">
                        <span class="text-slate-500">Reseller Code:</span>
                        <span class="font-mono text-slate-800" x-text="receiptModal.data?.reseller_code"></span>
                    </div>
                    <div class="flex items-center justify-between py-1 border-b border-slate-200">
                        <span class="text-slate-500">Base Amount:</span>
                        <span class="font-mono text-slate-800" x-text="'৳' + parseFloat(receiptModal.data?.amount || 0).toFixed(2)"></span>
                    </div>
                    <div class="flex items-center justify-between py-1 border-b border-slate-200" x-show="receiptModal.data?.bonus_amount > 0">
                        <span class="text-slate-500">Bonus Added:</span>
                        <span class="font-mono text-emerald-600" x-text="'+৳' + parseFloat(receiptModal.data?.bonus_amount || 0).toFixed(2)"></span>
                    </div>
                    <div class="flex items-center justify-between py-1 border-b border-slate-200">
                        <span class="text-slate-500">Payment Method:</span>
                        <span class="font-medium text-slate-800" x-text="receiptModal.data?.payment_method"></span>
                    </div>
                    <div class="flex items-center justify-between py-1 border-b border-slate-200" x-show="receiptModal.data?.gateway_trx_id">
                        <span class="text-slate-500">Gateway Trx ID:</span>
                        <span class="font-mono text-slate-800" x-text="receiptModal.data?.gateway_trx_id"></span>
                    </div>
                    <div class="flex items-center justify-between py-1 border-b border-slate-200">
                        <span class="text-slate-500">Status:</span>
                        <span class="font-semibold" :class="receiptModal.data?.status === 'APPROVED' ? 'text-emerald-700' : (receiptModal.data?.status === 'PENDING' ? 'text-amber-700' : 'text-rose-700')" x-text="receiptModal.data?.status"></span>
                    </div>
                    <div class="flex items-center justify-between py-1">
                        <span class="text-slate-500">Date:</span>
                        <span class="font-mono text-slate-700" x-text="receiptModal.data?.date"></span>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                <button type="button" @click="receiptModal.open = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition">
                    Close
                </button>
                <button type="button" @click="printReceipt()" class="bg-purple-600 hover:bg-purple-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition inline-flex items-center gap-1.5">
                    <i class="fas fa-print text-xs"></i>
                    <span>Print Receipt</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Modal 3: Reject Reason Modal -->
    <div x-show="rejectModal.open" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50"
         style="display: none;">
        
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-md overflow-hidden"
             @click.away="rejectModal.open = false">
            
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-rose-50 text-rose-600 border border-rose-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Reject Recharge Request</h3>
                        <p class="text-[10.5px] text-slate-500 font-mono" x-text="rejectModal.data?.recharge_no"></p>
                    </div>
                </div>
                <button type="button" @click="rejectModal.open = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <form @submit.prevent="confirmReject()" class="p-4 space-y-3 text-xs">
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                        Reason for Rejection <span class="text-rose-500">*</span>
                    </label>
                    <textarea x-model="rejectModal.reason" 
                              required 
                              rows="3" 
                              placeholder="e.g. Invalid transaction ID or payment not received in bank account..." 
                              class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-rose-500 resize-none"></textarea>
                </div>

                <div class="pt-2 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="rejectModal.open = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition">
                        Cancel
                    </button>
                    <button type="submit" 
                            :disabled="submitting"
                            class="bg-rose-600 hover:bg-rose-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition inline-flex items-center gap-1.5 disabled:opacity-50">
                        <i class="fas fa-ban text-xs" :class="{ 'fa-spin fa-spinner': submitting }"></i>
                        <span x-text="submitting ? 'Rejecting...' : 'Confirm Rejection'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function resellerRechargeManager() {
    return {
        activeMenu: null,
        menuPos: { top: '0px', right: '10px', left: 'auto', bottom: 'auto' },
        submitting: false,

        showCreateModal: false,
        receiptModal: {
            open: false,
            data: null
        },
        rejectModal: {
            open: false,
            data: null,
            reason: ''
        },

        form: {
            reseller_id: '{{ $allResellers->first()?->id ?? "" }}',
            amount: '',
            bonus_amount: 0,
            payment_method: 'CASH',
            status: 'APPROVED',
            gateway_trx_id: '',
            deposit_date: new Date().toISOString().split('T')[0],
            notes: ''
        },

        toggleMenu(item, event) {
            if (this.activeMenu?.id === item.id) {
                this.activeMenu = null;
                return;
            }
            this.activeMenu = item;
            const rect = event.currentTarget.getBoundingClientRect();
            const dropdownHeight = 160;
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

        openNewRechargeModal() {
            this.form.amount = '';
            this.form.bonus_amount = 0;
            this.form.gateway_trx_id = '';
            this.form.notes = '';
            this.showCreateModal = true;
        },

        calculateTotalCredited() {
            const a = parseFloat(this.form.amount || 0);
            const b = parseFloat(this.form.bonus_amount || 0);
            return '৳' + (a + b).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        async submitRecharge() {
            if (!this.form.reseller_id || !this.form.amount) return;
            this.submitting = true;
            try {
                const response = await fetch('{{ route('tenant.resellers.recharge.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.form)
                });
                const data = await response.json();
                if (data.success) {
                    this.showCreateModal = false;
                    Swal.fire({
                        icon: 'success',
                        title: 'Recharge Saved',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Failed to record recharge.'
                    });
                }
            } catch (err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Network error or server unreachable.'
                });
            } finally {
                this.submitting = false;
            }
        },

        async viewReceipt(id) {
            try {
                const res = await fetch(`/admin/resellers/recharge/${id}/receipt`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    this.receiptModal.data = data.receipt;
                    this.receiptModal.open = true;
                }
            } catch (err) {
                console.error(err);
            }
        },

        async approveRecharge(id) {
            const result = await Swal.fire({
                title: 'Approve Recharge?',
                text: 'This will instantly credit the reseller wallet balance.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, Approve'
            });

            if (result.isConfirmed) {
                try {
                    const response = await fetch(`/admin/resellers/recharge/${id}/approve`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    });
                    const data = await response.json();
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Approved',
                            text: data.message,
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Failed',
                            text: data.message || 'Failed to approve.'
                        });
                    }
                } catch (err) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Network error.'
                    });
                }
            }
        },

        openRejectModal(item) {
            this.rejectModal.data = item;
            this.rejectModal.reason = '';
            this.rejectModal.open = true;
        },

        async confirmReject() {
            if (!this.rejectModal.reason) return;
            this.submitting = true;
            try {
                const response = await fetch(`/admin/resellers/recharge/${this.rejectModal.data.id}/reject`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ reason: this.rejectModal.reason })
                });
                const data = await response.json();
                if (data.success) {
                    this.rejectModal.open = false;
                    Swal.fire({
                        icon: 'success',
                        title: 'Rejected',
                        text: data.message,
                        timer: 1800,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Failed',
                        text: data.message || 'Failed to reject.'
                    });
                }
            } catch (err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Network error.'
                });
            } finally {
                this.submitting = false;
            }
        },

        printReceipt() {
            const printContent = document.getElementById('printableReceipt').innerHTML;
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                <head>
                    <title>Recharge Receipt - ${this.receiptModal.data?.recharge_no || ''}</title>
                    <style>
                        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; padding: 24px; color: #1e293b; }
                        .text-center { text-align: center; }
                        .font-bold { font-weight: bold; }
                        .font-mono { font-family: monospace; }
                        .border { border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; }
                        .flex { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px dashed #e2e8f0; }
                    </style>
                </head>
                <body>
                    <h2 class="text-center" style="margin-bottom: 4px;">{{ $tenant->company_name ?? $tenant->name }}</h2>
                    <p class="text-center" style="font-size: 12px; color: #64748b; margin-top: 0;">Official Reseller Recharge Voucher</p>
                    <div style="max-width: 400px; margin: 20px auto;">
                        ${printContent}
                    </div>
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.focus();
            setTimeout(() => {
                printWindow.print();
                printWindow.close();
            }, 300);
        }
    }
}
</script>
@endpush
