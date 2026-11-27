@extends('owner.layouts.app')

@section('page-title', 'Payment Settlements & Revenue Ledger')

@push('styles')
<style>.owner-finance-table.saas-table td { white-space: nowrap; } .owner-finance-table.saas-table td > div { display: inline-flex; vertical-align: middle; } .owner-finance-table.saas-table td > div + div { margin-left: .35rem; } .owner-finance-table.saas-table th:nth-child(6), .owner-finance-table.saas-table td:nth-child(6) { display: none; } .owner-finance-filter select, .owner-finance-filter input, .owner-finance-filter button { height: 34px; } .owner-finance-filter > div:first-child { flex: 1 1 220px; min-width: 200px; } .owner-finance-filter > div:not(:first-child) { flex: 0 0 auto; } .owner-finance-filter select { width: auto !important; min-width: 130px; }</style>
@endpush

@section('content')
<div class="space-y-4">

    <!-- Global KPI Cards -->
    <div class="owner-kpi-grid">
        <x-owner.kpi-card title="All-time Settled" :value="currency($totalPaidAmount)" icon="fa-sack-dollar" theme="emerald" meta="Total Realized Revenue" />
        <x-owner.kpi-card title="This Month" :value="currency($thisMonthPaidAmount)" icon="fa-calendar-check" theme="blue" :meta="date('F Y') . ' Total'" />
        <x-owner.kpi-card title="Today's Collection" :value="currency($todayPaidAmount)" icon="fa-receipt" theme="purple" :meta="date('d M, Y')" />
        <x-owner.kpi-card title="Paid Receipts" :value="$totalPaidCount" icon="fa-file-invoice" theme="slate" meta="Settled Invoices" />
    </div>

    <!-- Filters -->
    <div class="bg-white p-2.5 rounded-xl border border-slate-200/90 shadow-2xs">
        <form action="{{ route('owner.payments.index') }}" method="GET" x-data="{ showMoreFilters: {{ request()->filled('tenant_id') || request()->filled('payment_method') ? 'true' : 'false' }}, searchQuery: @js(request('search', '')) }" class="owner-finance-filter flex flex-col md:flex-row items-stretch md:items-center gap-2">
            <div class="lg:col-span-2">
                <div class="relative"><i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-[11px]" :class="searchQuery ? 'text-cyan-600 font-bold' : 'text-slate-400'"></i><input type="text" name="search" x-model="searchQuery" value="{{ request('search') }}" placeholder="Invoice #, Trx ID, Tenant name, Phone..." class="w-full h-[34px] pl-8 pr-7 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs focus:bg-white focus:ring-1 focus:ring-cyan-500 focus:border-cyan-500"><button type="button" x-show="searchQuery" x-cloak @click="searchQuery = ''; $nextTick(() => $el.closest('form').submit())" class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-rose-600"><i class="fas fa-times text-[10px]"></i></button></div>
            </div>
            <div x-show="showMoreFilters" x-cloak class="pt-2 border-t border-slate-100">
                <select name="tenant_id" class="w-full px-2.5 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs focus:ring-1 focus:ring-blue-500">
                    <option value="">All ISP Tenants</option>
                    @foreach($tenants as $t)
                        <option value="{{ $t->id }}" {{ request('tenant_id') == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>
            <div x-show="showMoreFilters" x-cloak>
                <select name="payment_method" class="w-full px-2.5 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs focus:ring-1 focus:ring-blue-500">
                    <option value="">All Methods</option>
                    <option value="bkash" {{ request('payment_method') === 'bkash' ? 'selected' : '' }}>bKash</option>
                    <option value="nagad" {{ request('payment_method') === 'nagad' ? 'selected' : '' }}>Nagad</option>
                    <option value="bank" {{ request('payment_method') === 'bank' ? 'selected' : '' }}>Bank Wire</option>
                    <option value="cash" {{ request('payment_method') === 'cash' ? 'selected' : '' }}>Cash / Manual</option>
                    <option value="online" {{ request('payment_method') === 'online' ? 'selected' : '' }}>Online PGW</option>
                </select>
            </div>
            <div class="flex items-center gap-1.5 flex-shrink-0 md:ml-auto">
                <button type="button" @click="showMoreFilters = !showMoreFilters" class="h-[34px] py-0 px-2.5 rounded-lg border border-slate-200 bg-white text-slate-700 text-xs font-semibold flex items-center gap-1.5"><i class="fas" :class="showMoreFilters ? 'fa-chevron-up text-cyan-600' : 'fa-chevron-down text-slate-400'"></i><span>More Filters</span></button>
                <button type="submit" class="h-[34px] py-0 px-3 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs transition flex items-center justify-center gap-1.5 shadow-xs">
                    <i class="fas fa-filter text-[10px]"></i>
                    <span>Filter</span>
                </button>
                <a href="{{ route('owner.payments.index') }}" title="Reset" class="h-[34px] py-0 px-2.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold flex items-center justify-center gap-1 border border-slate-200/80">
                    <i class="fas fa-rotate-left text-[10px]"></i><span>Reset</span>
                </a>
            </div>
        </form>
    </div>

    <!-- Payments Table -->
    <div class="rounded-xl bg-white border border-slate-200 overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="saas-table owner-finance-table">
                <thead class="bg-slate-50 text-slate-600 uppercase tracking-wider text-[10px] border-b border-slate-200 font-bold text-center">
                    <tr>
                        <th class="px-3 py-2.5 border-r border-slate-200">Payment Date</th>
                        <th class="px-3.5 py-2.5 border-r border-slate-200">Invoice #</th>
                        <th class="px-3.5 py-2.5 border-r border-slate-200">ISP Tenant</th>
                        <th class="px-3.5 py-2.5 border-r border-slate-200">Plan</th>
                        <th class="px-3.5 py-2.5 border-r border-slate-200">Method</th>
                        <th class="px-3.5 py-2.5 border-r border-slate-200">Transaction ID</th>
                        <th class="px-3.5 py-2.5 border-r border-slate-200">Settled Amount</th>
                        <th class="px-3.5 py-2.5 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse($payments as $p)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-3.5 py-2.5 border-r border-slate-200 font-mono text-[10.5px] text-slate-600 text-center">
                                {{ $p->paid_at ? \Carbon\Carbon::parse($p->paid_at)->format('d M, Y (h:i A)') : ($p->created_at ? $p->created_at->format('d M, Y') : '—') }}
                            </td>
                            <td class="px-3.5 py-2.5 border-r border-slate-200 font-mono font-bold text-blue-700 text-center">
                                <a href="{{ route('owner.billing.index', ['search' => $p->invoice_no]) }}" class="hover:underline">
                                    {{ $p->invoice_no }}
                                </a>
                            </td>
                            <td class="px-3.5 py-2.5 border-r border-slate-200 font-bold text-slate-900">
                                @if($p->tenant)
                                    <a href="{{ route('owner.tenants.show', $p->tenant) }}" class="hover:text-blue-600 transition">
                                        {{ $p->tenant->name }}
                                    </a>
                                @else
                                    <span class="text-slate-400 italic">Deleted Tenant</span>
                                @endif
                            </td>
                            <td class="px-3.5 py-2.5 border-r border-slate-200 text-center">
                                <span class="px-2 py-0.5 rounded bg-purple-50 text-purple-700 border border-purple-200 font-bold text-[10px]">
                                    {{ $p->plan->name ?? 'Standard Plan' }}
                                </span>
                            </td>
                            <td class="px-3.5 py-2.5 border-r border-slate-200 text-center uppercase font-bold text-slate-700">
                                {{ $p->payment_method ?: 'Online' }}
                            </td>
                            <td class="px-3.5 py-2.5 border-r border-slate-200 font-mono text-[10.5px] text-slate-600 text-center">
                                {{ $p->trx_id ?: '—' }}
                            </td>
                            <td class="px-3.5 py-2.5 border-r border-slate-200 font-mono font-extrabold text-emerald-700 text-right">
                                @currency($p->amount)
                            </td>
                            <td class="px-3.5 py-2.5 text-center">
                                <span class="px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 font-bold text-[10px] border border-emerald-200">
                                    SETTLED
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-slate-400">No settled payment records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3 border-t border-slate-200 bg-slate-50 flex items-center justify-between text-xs text-slate-600">
            <div>Showing {{ $payments->firstItem() ?? 0 }} to {{ $payments->lastItem() ?? 0 }} of {{ $payments->total() }} Records</div>
            <div>{{ $payments->links() }}</div>
        </div>
    </div>

</div>
@endsection
