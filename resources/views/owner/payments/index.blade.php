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
    <x-owner.filter-toolbar action="{{ route('owner.payments.index') }}" reset-url="{{ route('owner.payments.index') }}" form-id="owner-payments-filter" :show-more-open="request()->filled('tenant_id') || request()->filled('payment_method')" :active-more-count="request()->filled('tenant_id') || request()->filled('payment_method') ? 2 : 0" :search-value="request('search', '')">
        <x-slot:primary>
            <div class="relative flex-1 min-w-[200px]">
                <div class="relative"><i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-[11px]" :class="searchQuery ? 'text-cyan-600 font-bold' : 'text-slate-400'"></i><input type="text" name="search" x-model="searchQuery" value="{{ request('search') }}" placeholder="Invoice #, Trx ID, Tenant name, Phone..." class="w-full h-[34px] pl-8 pr-7 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs focus:bg-white focus:ring-1 focus:ring-cyan-500 focus:border-cyan-500"><button type="button" x-show="searchQuery" x-cloak @click="searchQuery = ''; $nextTick(() => $el.closest('form').submit())" class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-rose-600"><i class="fas fa-times text-[10px]"></i></button></div>
            </div>
        </x-slot:primary>
        <x-slot:secondary>
                <select name="tenant_id" class="w-full px-2.5 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs focus:ring-1 focus:ring-blue-500">
                    <option value="">All ISP Tenants</option>
                    @foreach($tenants as $t)
                        <option value="{{ $t->id }}" {{ request('tenant_id') == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                    @endforeach
                </select>
                <select name="payment_method" class="w-full px-2.5 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs focus:ring-1 focus:ring-blue-500">
                    <option value="">All Methods</option>
                    <option value="bkash" {{ request('payment_method') === 'bkash' ? 'selected' : '' }}>bKash</option>
                    <option value="nagad" {{ request('payment_method') === 'nagad' ? 'selected' : '' }}>Nagad</option>
                    <option value="bank" {{ request('payment_method') === 'bank' ? 'selected' : '' }}>Bank Wire</option>
                    <option value="cash" {{ request('payment_method') === 'cash' ? 'selected' : '' }}>Cash / Manual</option>
                    <option value="online" {{ request('payment_method') === 'online' ? 'selected' : '' }}>Online PGW</option>
                </select>
        </x-slot:secondary>
    </x-owner.filter-toolbar>

    <!-- Payments Table -->
    <x-owner.table-shell
        :has-rows="$payments->isNotEmpty()"
        empty-title="No settled payments found"
        empty-message="Settled payment records will appear here."
        empty-icon="fa-money-check-dollar"
    >
        <x-slot:head>
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
        </x-slot:head>
        <x-slot:body>
                    @foreach($payments as $p)
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
                    @endforeach

        </x-slot:body>
        <x-slot:pagination>
            <div>Showing {{ $payments->firstItem() ?? 0 }} to {{ $payments->lastItem() ?? 0 }} of {{ $payments->total() }} Records</div>
            <div>{{ $payments->links() }}</div>
        </x-slot:pagination>
    </x-owner.table-shell>

</div>
@endsection
