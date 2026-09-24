@extends('tenant.layouts.app')

@section('title', 'Due Customers - ' . ($tenant->company_name ?? 'ISP Management'))

@push('styles')
    {{-- Page Specific Styles --}}
@endpush

@section('content')
<div class="space-y-3" x-data="dueCustomersManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">
    
    {{-- 1. TOP HEADER BAR (AGENTS.md Rule 2.A: Icon + Title + Action buttons ONLY) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 bg-white px-4 py-2.5 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <a href="{{ route('tenant.dashboard') }}" class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center text-xs transition shadow-2xs">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 border border-rose-100 flex items-center justify-center text-sm shadow-2xs flex-shrink-0">
                <i class="fas fa-file-invoice-dollar"></i>
            </div>
            <h1 class="text-sm font-bold text-slate-800 tracking-tight">{{ __('Area Due Customers') }}</h1>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('tenant.dashboard') }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition inline-flex items-center gap-1.5 shadow-2xs">
                <i class="fas fa-gauge-high text-xs text-slate-500"></i>
                <span>{{ __('Collector Hub') }}</span>
            </a>
        </div>
    </div>

    {{-- 2. KPI SUMMARY STRIP (Strictly 6 Cards - AGENTS.md Rule 2.B) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Due Customers') }}</span>
                <span class="text-[13px] font-bold font-mono text-rose-600 leading-tight block truncate">{{ number_format($stats['total_due_customers']) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-rose-200 bg-rose-50 text-rose-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-users-slash"></i>
            </div>
        </div>

        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Total Due') }}</span>
                <span class="text-[13px] font-bold font-mono text-rose-700 leading-tight block truncate">@currency($stats['total_due_amount'])</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-rose-200 bg-rose-50 text-rose-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-hand-holding-dollar"></i>
            </div>
        </div>

        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Today Collected') }}</span>
                <span class="text-[13px] font-bold font-mono text-emerald-600 leading-tight block truncate">@currency($stats['today_collected'])</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-money-bill-wave"></i>
            </div>
        </div>

        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Cash in Hand') }}</span>
                <span class="text-[13px] font-bold font-mono text-amber-700 leading-tight block truncate">@currency($stats['cash_in_hand'])</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-amber-200 bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-wallet"></i>
            </div>
        </div>

        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Expired Lines') }}</span>
                <span class="text-[13px] font-bold font-mono text-slate-700 leading-tight block truncate">{{ number_format($stats['expired_customers']) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-slate-200 bg-slate-50 text-slate-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-clock"></i>
            </div>
        </div>

        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Active Lines') }}</span>
                <span class="text-[13px] font-bold font-mono text-emerald-700 leading-tight block truncate">{{ number_format($stats['active_subscribers']) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-signal"></i>
            </div>
        </div>
    </div>

    {{-- 3. SEARCH & MULTI-FILTER TOOLBAR (AGENTS.md Rule 2.C) --}}
    <form method="GET" action="{{ route('tenant.collector.due-customers') }}" class="bg-white p-2.5 rounded-xl border border-slate-200 shadow-xs flex flex-wrap items-center justify-between gap-2">
        <div class="flex items-center gap-2 flex-1 min-w-[240px]">
            <div class="relative flex-1">
                <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" name="search" value="{{ $search }}" placeholder="{{ __('Search Username, Phone, Name...') }}" class="w-full pl-8 pr-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-cyan-500 shadow-2xs">
            </div>
            <select name="per_page" onchange="this.form.submit()" class="bg-slate-50 border border-slate-200 rounded-lg text-xs px-2 py-1.5 text-slate-700 focus:bg-white focus:border-cyan-500">
                <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10</option>
                <option value="20" {{ $perPage == 20 ? 'selected' : '' }}>20</option>
                <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
            </select>
        </div>

        {{-- Filter & Reset Sequence (Strict AGENTS.md Rule 2.C) --}}
        <div class="flex items-center gap-1.5">
            <button type="submit" class="bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-1.5 px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                <i class="fas fa-filter text-[10px]"></i>
                <span>{{ __('Filter') }}</span>
            </button>
            <a href="{{ route('tenant.collector.due-customers') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-1.5 px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer">
                <i class="fas fa-rotate-left text-[10px]"></i>
                <span>{{ __('Reset') }}</span>
            </a>
        </div>
    </form>

    {{-- 4. MASTER COMPACT TABLE (AGENTS.md Rule 2.D: Max 5-7 Minimal Columns) --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-10 text-center">#</th>
                        <th>{{ __('Username') }}</th>
                        <th>{{ __('Phone Number') }}</th>
                        <th>{{ __('Packages & Rates') }}</th>
                        <th class="text-right">{{ __('Monthly Fee') }}</th>
                        <th class="text-right">{{ __('Due Amount') }}</th>
                        <th class="w-20 text-center no-sort">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $index => $customer)
                        <tr>
                            <td class="text-center font-mono text-slate-500">{{ $customers->firstItem() + $index }}</td>
                            <td class="font-semibold text-slate-800">{{ $customer->username ?? $customer->name }}</td>
                            <td class="font-mono text-slate-600">{{ $customer->phone ?? '—' }}</td>
                            <td>{{ $customer->package_display_name }}</td>
                            <td class="text-right font-mono font-bold text-slate-800">
                                @currency($customer->monthly_bill ?? ($customer->package->price ?? 0))
                            </td>
                            <td class="text-right font-mono font-bold text-rose-600">
                                @currency($customer->due_amount ?? 0)
                            </td>
                            <td class="text-center">
                                <button type="button" 
                                        @click="openCollectModal({
                                            id: {{ $customer->id }},
                                            username: '{{ addslashes($customer->username ?? $customer->name) }}',
                                            due_amount: '{{ (float)($customer->due_amount ?? 0) }}'
                                        })" 
                                        class="px-2 py-1 bg-cyan-600 hover:bg-cyan-700 text-white rounded text-[11px] font-semibold transition cursor-pointer inline-flex items-center gap-1 shadow-2xs">
                                    <i class="fas fa-money-bill-wave text-[10px]"></i>
                                    <span>{{ __('Collect') }}</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-6 text-slate-400">
                                <i class="fas fa-check-circle text-2xl mb-1.5 block text-emerald-400"></i>
                                <span>{{ __('No due subscribers found in your assigned area.') }}</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($customers->hasPages())
            <div class="p-3 border-t border-slate-200 bg-slate-50/50">
                {{ $customers->links() }}
            </div>
        @endif
    </div>

    {{-- 5. COLLECT BILL SOFT NATURAL MODAL (AGENTS.md Rule 3) --}}
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
                        <h3 class="text-xs font-semibold text-slate-800" x-text="'Collect Bill: ' + selectedCustomer?.username"></h3>
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
function dueCustomersManager() {
    return {
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
