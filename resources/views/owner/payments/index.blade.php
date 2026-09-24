@extends('owner.layouts.app')

@section('page-title', 'Settled Payments & Revenue Ledger')

@section('content')
<div class="space-y-4">

    <!-- Top Header Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs">
        <div>
            <div class="flex items-center gap-2">
                <h2 class="text-sm font-bold text-slate-900 tracking-tight">SaaS Payment Settlements</h2>
                <span class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold font-mono">
                    {{ $totalPaidCount }} Paid Records
                </span>
            </div>
            <p class="text-[11px] text-slate-500 mt-0.5">Audit log of all realized subscription renewals, MFS settlements, and gateway payments</p>
        </div>
    </div>

    <!-- 4 Key Payment Metrics Strip -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="p-3.5 bg-white rounded-xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5">
                <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider block">All-time Settled</span>
                <span class="text-base font-extrabold text-emerald-700 tracking-tight font-mono">@currency($totalPaidAmount)</span>
                <span class="text-[10px] text-slate-400 block">Total Realized Revenue</span>
            </div>
            <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs">
                <i class="fas fa-sack-dollar"></i>
            </div>
        </div>

        <div class="p-3.5 bg-white rounded-xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5">
                <span class="text-[10px] font-bold text-blue-600 uppercase tracking-wider block">This Month</span>
                <span class="text-base font-extrabold text-blue-700 tracking-tight font-mono">@currency($thisMonthPaidAmount)</span>
                <span class="text-[10px] text-slate-400 block">{{ date('F Y') }} Total</span>
            </div>
            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-xs">
                <i class="fas fa-calendar-check"></i>
            </div>
        </div>

        <div class="p-3.5 bg-white rounded-xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5">
                <span class="text-[10px] font-bold text-purple-600 uppercase tracking-wider block">Today's Collection</span>
                <span class="text-base font-extrabold text-purple-700 tracking-tight font-mono">@currency($todayPaidAmount)</span>
                <span class="text-[10px] text-slate-400 block">{{ date('d M, Y') }}</span>
            </div>
            <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center text-xs">
                <i class="fas fa-receipt"></i>
            </div>
        </div>

        <div class="p-3.5 bg-white rounded-xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5">
                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">Paid Receipts</span>
                <span class="text-base font-extrabold text-slate-900 tracking-tight font-mono">{{ $totalPaidCount }}</span>
                <span class="text-[10px] text-slate-400 block">Settled Invoices</span>
            </div>
            <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center text-xs">
                <i class="fas fa-file-invoice"></i>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="p-3.5 rounded-xl bg-white border border-slate-200 shadow-xs">
        <form action="{{ route('owner.payments.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-2.5">
            <div class="lg:col-span-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Invoice #, Trx ID, Tenant name, Phone..." class="w-full px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs focus:ring-1 focus:ring-blue-500">
            </div>
            <div>
                <select name="tenant_id" class="w-full px-2.5 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs focus:ring-1 focus:ring-blue-500">
                    <option value="">All ISP Tenants</option>
                    @foreach($tenants as $t)
                        <option value="{{ $t->id }}" {{ request('tenant_id') == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <select name="payment_method" class="w-full px-2.5 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs focus:ring-1 focus:ring-blue-500">
                    <option value="">All Methods</option>
                    <option value="bkash" {{ request('payment_method') === 'bkash' ? 'selected' : '' }}>bKash</option>
                    <option value="nagad" {{ request('payment_method') === 'nagad' ? 'selected' : '' }}>Nagad</option>
                    <option value="bank" {{ request('payment_method') === 'bank' ? 'selected' : '' }}>Bank Wire</option>
                    <option value="cash" {{ request('payment_method') === 'cash' ? 'selected' : '' }}>Cash / Manual</option>
                    <option value="online" {{ request('payment_method') === 'online' ? 'selected' : '' }}>Online PGW</option>
                </select>
            </div>
            <div class="flex items-center gap-1.5">
                <button type="submit" class="flex-1 py-1.5 px-3 rounded-lg bg-slate-800 hover:bg-slate-900 text-white font-semibold text-xs transition flex items-center justify-center gap-1.5 shadow-xs">
                    <i class="fas fa-filter text-[10px]"></i>
                    <span>Filter</span>
                </button>
                <a href="{{ route('owner.payments.index') }}" title="Reset" class="py-1.5 px-2.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold flex items-center justify-center">
                    <i class="fas fa-rotate-left"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Payments Table -->
    <div class="rounded-xl bg-white border border-slate-200 overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-700 whitespace-nowrap border-collapse">
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
