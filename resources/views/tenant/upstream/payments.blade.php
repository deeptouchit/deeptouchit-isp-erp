@extends('tenant.layouts.app')

@section('title', 'Carrier Payment Vouchers - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
    {{-- Page specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="upstreamPaymentManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">

    <!-- 1. Top Header Bar (Strictly Icon + Title + Action Button ONLY - AGENTS.md Rule 2.A) -->
    <div class="flex items-center justify-between bg-white px-3.5 py-2.5 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-sm shadow-2xs flex-shrink-0">
                <i class="fas fa-money-bill-transfer"></i>
            </div>
            <h1 class="text-xs font-bold text-slate-800 tracking-tight">Carrier Payment Vouchers</h1>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" 
                    @click="openPaymentModal()"
                    class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-plus text-xs"></i>
                <span>Disburse Payment</span>
            </button>
        </div>
    </div>

    <!-- 2. KPI Summary Strip (Strictly 6 Cards - AGENTS.md Rule 2.B & Rule 6) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        <!-- Card 1: Total Disbursed -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Total Disbursed</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-emerald-700 truncate">@currency($totalDisbursed)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-emerald-50 text-emerald-600 border-emerald-100 flex items-center justify-center">
                <i class="fas fa-money-bill-wave"></i>
            </div>
        </div>

        <!-- Card 2: Total Vouchers Count -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Total Vouchers</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-slate-900 truncate">{{ number_format($totalVouchersCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-slate-50 text-slate-600 border-slate-200 flex items-center justify-center">
                <i class="fas fa-receipt"></i>
            </div>
        </div>

        <!-- Card 3: Bank Transfer Total -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Bank Transfer</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-blue-700 truncate">@currency($bankTransferTotal)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-blue-50 text-blue-600 border-blue-100 flex items-center justify-center">
                <i class="fas fa-building-columns"></i>
            </div>
        </div>

        <!-- Card 4: Cheque / RTGS Total -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Cheque / RTGS</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-indigo-700 truncate">@currency($chequeRtgsTotal)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-indigo-50 text-indigo-600 border-indigo-100 flex items-center justify-center">
                <i class="fas fa-money-check"></i>
            </div>
        </div>

        <!-- Card 5: Cash Settlements -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Cash Disbursed</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-amber-700 truncate">@currency($cashTotal)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-amber-50 text-amber-600 border-amber-100 flex items-center justify-center">
                <i class="fas fa-coins"></i>
            </div>
        </div>

        <!-- Card 6: This Month Disbursed -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">This Month</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-cyan-800 truncate">@currency($thisMonthTotal)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-cyan-50 text-cyan-600 border-cyan-100 flex items-center justify-center">
                <i class="fas fa-calendar-check"></i>
            </div>
        </div>
    </div>

    <!-- 3. Search & Multi-Filter Toolbar (Strictly Rule 2.C - Filter then Reset Sequence) -->
    <div class="bg-white p-2.5 rounded-xl border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('tenant.upstream.payments') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-12 gap-2 items-center">
            
            <!-- Search Input -->
            <div class="relative md:col-span-4">
                <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" 
                       name="search" 
                       value="{{ request('search') }}" 
                       placeholder="Search Voucher No, Carrier, Bank, Cheque..." 
                       class="w-full pl-8 pr-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-cyan-500 focus:outline-none transition">
            </div>

            <!-- Provider Filter -->
            <div class="md:col-span-3">
                <select name="provider_id" class="w-full px-2.5 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                    <option value="">All Carriers</option>
                    @foreach($providers as $p)
                    <option value="{{ $p->id }}" {{ request('provider_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Payment Method Filter -->
            <div class="md:col-span-2">
                <select name="payment_method" class="w-full px-2.5 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                    <option value="">All Methods</option>
                    <option value="BANK_TRANSFER" {{ request('payment_method') === 'BANK_TRANSFER' ? 'selected' : '' }}>Bank Transfer</option>
                    <option value="CHEQUE" {{ request('payment_method') === 'CHEQUE' ? 'selected' : '' }}>Cheque</option>
                    <option value="RTGS" {{ request('payment_method') === 'RTGS' ? 'selected' : '' }}>RTGS</option>
                    <option value="CASH" {{ request('payment_method') === 'CASH' ? 'selected' : '' }}>Cash</option>
                </select>
            </div>

            <!-- Per Page Selector -->
            <div class="md:col-span-1">
                <select name="per_page" class="w-full px-2 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                    <option value="10" {{ request('per_page') == '10' ? 'selected' : '' }}>10</option>
                    <option value="20" {{ request('per_page', '20') == '20' ? 'selected' : '' }}>20</option>
                    <option value="50" {{ request('per_page') == '50' ? 'selected' : '' }}>50</option>
                    <option value="100" {{ request('per_page') == '100' ? 'selected' : '' }}>100</option>
                </select>
            </div>

            <!-- Filter & Reset Button Sequence (Strictly Filter Cyan first, Reset Slate second, Always visible) -->
            <div class="md:col-span-2 flex items-center justify-end gap-1.5">
                <button type="submit" 
                        class="w-full bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-1.5 px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                    <i class="fas fa-filter text-[10px]"></i>
                    <span>Filter</span>
                </button>

                <a href="{{ route('tenant.upstream.payments') }}" 
                   class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-1.5 px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer"
                   title="Reset All Filters">
                    <i class="fas fa-rotate-left text-[10px]"></i>
                    <span>Reset</span>
                </a>
            </div>
        </form>
    </div>

    <!-- 4. Master Table (Strictly .saas-table pure CSS system - Max 7 Core Columns - AGENTS.md Rule 2.D) -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-10 text-center">#</th>
                        <th>Voucher No</th>
                        <th>Carrier Provider</th>
                        <th>Payment Method</th>
                        <th class="text-right">Paid Amount</th>
                        <th>Payment Date</th>
                        <th class="w-12 text-center no-sort">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $index => $payment)
                    <tr>
                        <td class="text-center font-mono text-slate-500">
                            {{ $payments->firstItem() + $index }}
                        </td>

                        <!-- Voucher No (Single Data) -->
                        <td class="font-mono font-bold text-cyan-700">
                            {{ $payment->voucher_no }}
                        </td>

                        <!-- Carrier Provider (Single Data) -->
                        <td class="font-semibold text-slate-800">
                            {{ $payment->provider->name ?? '-' }}
                        </td>

                        <!-- Payment Method (Single Data) -->
                        <td class="text-slate-700 font-medium">
                            {{ str_replace('_', ' ', $payment->payment_method) }}
                        </td>

                        <!-- Paid Amount (Single Data) -->
                        <td class="text-right font-mono font-bold text-emerald-700">
                            @currency($payment->amount)
                        </td>

                        <!-- Payment Date (Single Data) -->
                        <td class="text-slate-600 font-medium">
                            {{ $payment->paid_at ? $payment->paid_at->format('d M, Y') : '-' }}
                        </td>

                        <!-- Action (3-Dot Floating Trigger) -->
                        <td class="text-center">
                            <button type="button" 
                                    @click="toggleMenu({{ json_encode([
                                        'id' => $payment->id,
                                        'voucher_no' => $payment->voucher_no,
                                        'provider_id' => $payment->provider_id,
                                        'provider_name' => $payment->provider->name ?? '-',
                                        'amount' => $payment->amount,
                                        'payment_method' => $payment->payment_method,
                                        'bank_name' => $payment->bank_name,
                                        'cheque_no' => $payment->cheque_no,
                                        'transaction_ref' => $payment->transaction_ref,
                                        'paid_at' => $payment->paid_at ? $payment->paid_at->format('Y-m-d H:i') : '',
                                    ]) }}, $event)"
                                    class="w-7 h-7 rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-600 flex items-center justify-center transition cursor-pointer">
                                <i class="fas fa-ellipsis-v text-[10px]"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-8 text-slate-500">
                            <div class="flex flex-col items-center justify-center gap-1.5">
                                <i class="fas fa-money-bill-transfer text-2xl text-slate-300"></i>
                                <span class="text-xs font-semibold text-slate-600">No payment vouchers found</span>
                                <span class="text-[11px] text-slate-400">Click '+ Disburse Payment' above to record bank settlements.</span>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Native Laravel Pagination -->
        @if($payments->hasPages())
        <div class="px-3 py-2 border-t border-slate-200 bg-slate-50/50 flex items-center justify-between">
            <div class="text-[11px] text-slate-500">
                Showing <span class="font-semibold text-slate-700">{{ $payments->firstItem() }}</span> to <span class="font-semibold text-slate-700">{{ $payments->lastItem() }}</span> of <span class="font-semibold text-slate-700">{{ $payments->total() }}</span> vouchers
            </div>
            <div>
                {{ $payments->links() }}
            </div>
        </div>
        @endif
    </div>

    <!-- 5. Global Floating 3-Dot Action Menu (AGENTS.md Rule 2.E) -->
    <div x-show="activeMenu" 
         x-cloak 
         @click.away="activeMenu = null" 
         class="fixed z-50 bg-white rounded-xl border border-slate-200 shadow-xl py-1 w-48 text-xs font-medium text-slate-700 space-y-0.5"
         :style="{ top: menuPos.top, bottom: menuPos.bottom, right: menuPos.right, left: menuPos.left }">
        
        <a :href="`/admin/upstream/payments/${activeMenu?.id}/voucher`" 
           target="_blank"
           class="w-full text-left px-3 py-1.5 hover:bg-slate-50 flex items-center gap-2 text-slate-700 transition cursor-pointer">
            <i class="fas fa-print w-4 text-cyan-600 text-[11px]"></i>
            <span>Print Voucher</span>
        </a>

        <div class="border-t border-slate-100 my-0.5"></div>

        <button type="button" 
                @click="deletePayment(activeMenu)" 
                class="w-full text-left px-3 py-1.5 hover:bg-rose-50 flex items-center gap-2 text-rose-600 transition cursor-pointer">
            <i class="fas fa-trash-can w-4 text-rose-400 text-[11px]"></i>
            <span>Delete Voucher</span>
        </button>
    </div>

    <!-- Modal 1: Disburse Payment Modal (Rule 3) -->
    <div x-show="showPaymentModal" 
         x-cloak 
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50">
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden animate-in fade-in zoom-in duration-150"
             @click.away="showPaymentModal = false">
            
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-money-bill-transfer"></i>
                    </div>
                    <div>
                        <h2 class="text-xs font-semibold text-slate-800">Disburse Payment to Carrier</h2>
                        <p class="text-[10.5px] text-slate-500 font-normal">Record bank settlement and generate printable voucher.</p>
                    </div>
                </div>
                <button type="button" @click="showPaymentModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-xmark text-xs"></i>
                </button>
            </div>

            <form action="{{ route('tenant.upstream.payment.store') }}" method="POST" class="p-4 space-y-3">
                @csrf
                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">Carrier Provider <span class="text-rose-500">*</span></label>
                    <select name="provider_id" x-model="paymentData.provider_id" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                        <option value="">Select Carrier</option>
                        @foreach($providers as $p)
                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">Paid Amount ({{ $currencySymbol }}) <span class="text-rose-500">*</span></label>
                        <input type="number" step="0.01" min="1" name="amount" x-model.number="paymentData.amount" required placeholder="0.00" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 font-mono font-bold text-emerald-700">
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">Payment Method <span class="text-rose-500">*</span></label>
                        <select name="payment_method" x-model="paymentData.payment_method" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                            <option value="BANK_TRANSFER">Bank Transfer / BEFTN</option>
                            <option value="CHEQUE">Bank Cheque</option>
                            <option value="RTGS">RTGS Instant</option>
                            <option value="CASH">Cash</option>
                            <option value="OTHER">Other</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">Bank Name / Branch</label>
                        <input type="text" name="bank_name" x-model="paymentData.bank_name" placeholder="e.g. Standard Chartered" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5">
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">Cheque / Trx Reference</label>
                        <input type="text" name="transaction_ref" x-model="paymentData.transaction_ref" placeholder="Ref No / Cheque No" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 font-mono">
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">Payment Date <span class="text-rose-500">*</span></label>
                    <input type="datetime-local" name="paid_at" x-model="paymentData.paid_at" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5">
                </div>

                <div class="pt-2 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="showPaymentModal = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition cursor-pointer">
                        Disburse &amp; Generate Voucher
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    function upstreamPaymentManager() {
        return {
            activeMenu: null,
            menuPos: { top: '0px', bottom: 'auto', right: '0px', left: 'auto' },
            showPaymentModal: false,

            paymentData: {
                provider_id: '',
                amount: 0,
                payment_method: 'BANK_TRANSFER',
                bank_name: '',
                transaction_ref: '',
                paid_at: '{{ date('Y-m-d\TH:i') }}'
            },

            toggleMenu(item, event) {
                if (this.activeMenu?.id === item.id) {
                    this.activeMenu = null;
                    return;
                }
                this.activeMenu = item;
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

            openPaymentModal() {
                this.activeMenu = null;
                this.paymentData = {
                    provider_id: '',
                    amount: 0,
                    payment_method: 'BANK_TRANSFER',
                    bank_name: '',
                    transaction_ref: '',
                    paid_at: '{{ date('Y-m-d\TH:i') }}'
                };
                this.showPaymentModal = true;
            },

            async deletePayment(item) {
                this.activeMenu = null;
                const result = await Swal.fire({
                    title: 'Delete Payment Voucher?',
                    text: `Are you sure you want to delete voucher '${item.voucher_no}'? Linked invoice balance will be readjusted.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e11d48',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Yes, Delete Voucher',
                    cancelButtonText: 'Cancel'
                });

                if (result.isConfirmed) {
                    try {
                        const response = await fetch(`/admin/upstream/payments/${item.id}`, {
                            method: 'DELETE',
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
                                title: 'Deleted',
                                text: data.message,
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                        }
                    } catch (err) {
                        Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to delete payment voucher.' });
                    }
                }
            }
        };
    }

    // Trigger SweetAlert2 for server-side flash sessions
    document.addEventListener('DOMContentLoaded', function() {
        @if(session('success'))
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: "{{ session('success') }}",
                timer: 2000,
                showConfirmButton: false
            });
        @endif

        @if(session('error'))
            Swal.fire({
                icon: 'error',
                title: 'Action Failed',
                text: "{{ session('error') }}"
            });
        @endif
    });
</script>
@endpush
