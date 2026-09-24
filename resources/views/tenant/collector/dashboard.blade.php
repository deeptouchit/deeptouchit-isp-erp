@extends('tenant.layouts.app')

@section('title', 'Bill Collector Dashboard - ' . ($tenant->company_name ?? 'ISP Portal'))

@push('styles')
    {{-- Page-specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="collectorDashboardManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">
    
    {{-- 1. TOP HEADER BAR (Collector) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 bg-white px-4 py-2.5 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center text-sm shadow-2xs flex-shrink-0">
                <i class="fas fa-hand-holding-dollar"></i>
            </div>
            <h1 class="text-sm font-bold text-slate-800 tracking-tight">{{ __('Bill Collector Dashboard') }}</h1>
        </div>
        <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
            <a href="{{ route('tenant.customers.due') }}" class="px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-800 rounded-lg text-xs font-semibold border border-amber-200/80 transition inline-flex items-center gap-1.5 shadow-2xs cursor-pointer">
                <i class="fas fa-circle-exclamation text-amber-600 text-xs"></i>
                <span>{{ __('Due Customers') }}</span>
            </a>
            <a href="{{ route('tenant.customers.index') }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition inline-flex items-center gap-1.5 shadow-2xs cursor-pointer">
                <i class="fas fa-users text-cyan-600 text-xs"></i>
                <span>{{ __('All Customers') }}</span>
            </a>
            <a href="{{ route('tenant.finance.payments') }}" class="px-3.5 py-1.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-receipt text-xs"></i>
                <span>{{ __('My Collections') }}</span>
            </a>
        </div>
    </div>

    {{-- 2. KPI SUMMARY STRIP (Strictly 6 Cards - AGENTS.md Rule 2.B) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        
        {{-- Card 1: Today's Collection --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __("Today's Collection") }}</span>
                <span class="text-[13px] font-bold font-mono text-emerald-600 leading-tight block truncate">
                    @currency($stats['today_collections'])
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-calendar-day"></i>
            </div>
        </div>

        {{-- Card 2: This Month's Collection --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __("This Month Collections") }}</span>
                <span class="text-[13px] font-bold font-mono text-purple-700 leading-tight block truncate">
                    @currency($stats['this_month_collections'])
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-purple-200 bg-purple-50 text-purple-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-calendar-check"></i>
            </div>
        </div>

        {{-- Card 3: Due Customers Count --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Due Customers') }}</span>
                <span class="text-[13px] font-bold font-mono text-rose-600 leading-tight block truncate">
                    {{ number_format($stats['due_customers']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-rose-200 bg-rose-50 text-rose-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-user-clock"></i>
            </div>
        </div>

        {{-- Card 4: Total Due Amount --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Total Due Amount') }}</span>
                <span class="text-[13px] font-bold font-mono text-amber-700 leading-tight block truncate">
                    @currency($stats['total_due_amount'])
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-amber-200 bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-money-bill-wave"></i>
            </div>
        </div>

        {{-- Card 5: Today's Receipts Count --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __("Today Receipts") }}</span>
                <span class="text-[13px] font-bold font-mono text-cyan-700 leading-tight block truncate">
                    {{ number_format($stats['today_receipts_count']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-cyan-200 bg-cyan-50 text-cyan-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-receipt"></i>
            </div>
        </div>

        {{-- Card 6: Total Customers --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Total Customers') }}</span>
                <span class="text-[13px] font-bold font-mono text-slate-800 leading-tight block truncate">
                    {{ number_format($stats['total_customers']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-slate-200 bg-slate-50 text-slate-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-users"></i>
            </div>
        </div>

    </div>

    {{-- 3. COLLECTOR OVERVIEW MODULES (3 Cards) --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">
        
        <!-- Card 1: কালেকশন ও সারাংশ -->
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs space-y-3 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-chart-pie text-emerald-600 text-xs"></i>
                        <h3 class="text-xs font-bold text-slate-800">{{ __('Collection Summary') }}</h3>
                    </div>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold font-mono bg-emerald-50 text-emerald-700 border border-emerald-200">
                        {{ __('Live') }}
                    </span>
                </div>

                <div class="space-y-2 text-xs mt-3">
                    <div class="p-2.5 rounded-lg bg-emerald-50/60 border border-emerald-100 flex items-center justify-between">
                        <span class="text-slate-600 font-medium">{{ __("Today's Collection") }}</span>
                        <span class="font-bold font-mono text-emerald-800 text-sm">@currency($stats['today_collections'])</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-50">
                        <span class="text-slate-500">{{ __('This Month Collections') }}</span>
                        <span class="font-bold font-mono text-purple-700">@currency($stats['this_month_collections'])</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-50">
                        <span class="text-slate-500">{{ __("Today Receipts") }}</span>
                        <span class="font-bold font-mono text-slate-800">{{ $stats['today_receipts_count'] }}</span>
                    </div>
                    <div class="flex justify-between py-1">
                        <span class="text-slate-500">{{ __('Due Customers') }}</span>
                        <span class="font-bold font-mono text-rose-600">{{ $stats['due_customers'] }}</span>
                    </div>
                </div>
            </div>

            <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[11px]">
                <a href="{{ route('tenant.finance.payments') }}" class="text-emerald-700 hover:text-emerald-900 font-semibold inline-flex items-center gap-1 transition">
                    <span>{{ __('My Collections') }}</span>
                    <i class="fas fa-chevron-right text-[9px]"></i>
                </a>
                <span class="text-slate-400 font-mono text-[10px]">{{ __('Receipts & Payments') }}</span>
            </div>
        </div>

        <!-- Card 2: জরুরি বকেয়া গ্রাহক তালিকা -->
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs space-y-3 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-user-clock text-rose-600 text-xs"></i>
                        <h3 class="text-xs font-bold text-slate-800">{{ __('Due Customers') }} ({{ __('Immediate') }})</h3>
                    </div>
                    <a href="{{ route('tenant.customers.due') }}" class="text-[10px] font-semibold text-cyan-700 hover:underline">
                        {{ __('View All') }}
                    </a>
                </div>

                <div class="space-y-1.5 mt-2.5">
                    @forelse($topDueCustomers as $dueCust)
                        <div class="p-2 rounded-lg bg-slate-50 hover:bg-amber-50/50 border border-slate-200/70 hover:border-amber-200 transition flex items-center justify-between text-xs">
                            <div class="min-w-0 pr-2">
                                <span class="font-bold text-slate-800 block truncate leading-tight">{{ $dueCust->name }}</span>
                                <span class="text-[10px] font-mono text-slate-500 block truncate">{{ $dueCust->mobile ?? ($dueCust->phone ?? $dueCust->username) }}</span>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <span class="font-bold font-mono text-rose-600 block leading-tight">@currency($dueCust->due_amount)</span>
                                <button type="button" 
                                        @click="openCollectModal({
                                            id: {{ $dueCust->id }},
                                            username: '{{ addslashes($dueCust->username ?? $dueCust->name) }}',
                                            due_amount: '{{ (float)($dueCust->due_amount ?? 0) }}'
                                        })" 
                                        class="text-[9.5px] text-cyan-700 font-semibold hover:underline cursor-pointer inline-flex items-center gap-0.5">
                                    {{ __('Collect') }} <i class="fas fa-arrow-right text-[8px]"></i>
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="py-6 text-center text-slate-400 text-xs">
                            <i class="fas fa-check-circle text-emerald-500 text-base mb-1 block"></i>
                            <span>{{ __('No due customers found') }}</span>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[11px]">
                <a href="{{ route('tenant.customers.due') }}" class="text-rose-700 hover:text-rose-900 font-semibold inline-flex items-center gap-1 transition">
                    <span>{{ __('All Due Customers') }}</span>
                    <i class="fas fa-chevron-right text-[9px]"></i>
                </a>
                <span class="text-slate-400 font-mono text-[10px]">{{ __('Total Due') }}: @currency($stats['total_due_amount'])</span>
            </div>
        </div>

        <!-- Card 3: সাম্প্রতিক আদায় ও মানি রিসিট -->
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs space-y-3 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-receipt text-cyan-600 text-xs"></i>
                        <h3 class="text-xs font-bold text-slate-800">{{ __('Recent Collections') }}</h3>
                    </div>
                    <span class="text-[10px] font-mono text-slate-400">{{ __('Latest') }}</span>
                </div>

                <div class="space-y-1.5 mt-2.5">
                    @forelse($recentPayments as $payment)
                        <div class="p-2 rounded-lg bg-slate-50 hover:bg-cyan-50/50 border border-slate-200/70 hover:border-cyan-200 transition flex items-center justify-between text-xs">
                            <div class="min-w-0 pr-2">
                                <span class="font-bold text-slate-800 block truncate leading-tight">{{ $payment->customer->name ?? ($payment->customer->username ?? 'Customer #' . $payment->customer_id) }}</span>
                                <span class="text-[10px] font-mono text-slate-500 block truncate">{{ $payment->created_at?->format('d M, h:i A') }}</span>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <span class="font-bold font-mono text-emerald-700 block leading-tight">@currency($payment->amount)</span>
                                <a href="{{ route('tenant.customers.payments.receipt', $payment->id) }}" target="_blank" class="text-[9.5px] text-purple-700 font-semibold hover:underline">
                                    {{ __('Receipt') }} <i class="fas fa-print text-[8px]"></i>
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="py-6 text-center text-slate-400 text-xs">
                            <i class="fas fa-receipt text-slate-300 text-base mb-1 block"></i>
                            <span>{{ __('No collections yet') }}</span>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[11px]">
                <a href="{{ route('tenant.finance.payments') }}" class="text-cyan-700 hover:text-cyan-900 font-semibold inline-flex items-center gap-1 transition">
                    <span>{{ __('View All Receipts') }}</span>
                    <i class="fas fa-chevron-right text-[9px]"></i>
                </a>
                <span class="text-slate-400 text-[10px] font-mono">{{ __('Daily Records') }}</span>
            </div>
        </div>

    </div>

    {{-- 4. COLLECT BILL SOFT NATURAL MODAL (AGENTS.md Rule 3) --}}
    <div x-show="showCollectModal" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50"
         style="display: none;"
         x-cloak>
        
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-md overflow-hidden" 
             @click.outside="showCollectModal = false">
            
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800" x-text="'Collect Bill: ' + (selectedCustomer?.username || '')"></h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">{{ __('Receive payment and instantly renew line') }}</p>
                    </div>
                </div>
                <button type="button" @click="showCollectModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <form action="{{ route('tenant.collector.collect-bill') }}" method="POST" class="p-4 space-y-3">
                @csrf
                <input type="hidden" name="customer_id" :value="selectedCustomer?.id">

                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                        {{ __('Pay Amount') }} ({{ $currencySymbol ?? '৳' }}) <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" name="amount" :value="selectedCustomer?.due_amount" step="0.01" min="1" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs font-mono">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                        {{ __('Payment Method') }} <span class="text-rose-500">*</span>
                    </label>
                    <select name="payment_method" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                        <option value="cash">{{ __('Cash in Hand') }}</option>
                        <option value="bkash">bKash</option>
                        <option value="nagad">Nagad</option>
                        <option value="rocket">Rocket</option>
                        <option value="bank">Bank Transfer</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                        {{ __('Discount') }} ({{ $currencySymbol ?? '৳' }})
                    </label>
                    <input type="number" name="discount" step="0.01" min="0" placeholder="0.00" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs font-mono">
                </div>

                <div class="pt-3 border-t border-slate-200 flex items-center justify-end gap-2 -mx-4 -mb-4 p-3 bg-slate-50/80">
                    <button type="button" @click="showCollectModal = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit" class="bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition cursor-pointer">
                        {{ __('Confirm Collection') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function collectorDashboardManager() {
    return {
        activeMenu: null,
        showCollectModal: false,
        selectedCustomer: null,

        openCollectModal(customer) {
            this.selectedCustomer = customer;
            this.showCollectModal = true;
        }
    };
}
</script>
@endpush
