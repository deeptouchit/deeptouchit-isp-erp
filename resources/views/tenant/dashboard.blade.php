@extends('tenant.layouts.app')

@section('title', __('Dashboard') . ' - ' . ($tenant->company_name ?? $tenant->name ?? __('ISP Management')))

@section('content')
<div class="space-y-3" x-data="{ activeMenu: null }" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">
    {{-- Top header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 bg-white px-4 py-2.5 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center text-sm flex-shrink-0">
                <i class="fas fa-chart-line"></i>
            </div>
            <h1 class="text-sm font-bold text-slate-800 tracking-tight">{{ __('ISP Operations Manager') }}</h1>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('tenant.customers.index') }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition inline-flex items-center gap-1.5">
                <i class="fas fa-users text-blue-600 text-[10px]"></i>
                <span>{{ __('Customers') }}</span>
            </a>
            <a href="{{ route('tenant.finance.invoices') }}" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-semibold shadow-xs transition inline-flex items-center gap-1.5">
                <i class="fas fa-file-invoice text-[10px]"></i>
                <span>{{ __('Billing') }}</span>
            </a>
        </div>
    </div>

    {{-- KPI summary --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        @foreach([
            ['label' => __('Customers'), 'value' => number_format($totalCustomers), 'value_class' => 'text-blue-700', 'icon' => 'fa-users', 'icon_class' => 'border-blue-200 bg-blue-50 text-blue-600'],
            ['label' => __('Active'), 'value' => number_format($activeCustomers), 'value_class' => 'text-emerald-700', 'icon' => 'fa-circle-check', 'icon_class' => 'border-emerald-200 bg-emerald-50 text-emerald-600'],
            ['label' => __('Due Customers'), 'value' => number_format($dueCustomers), 'value_class' => 'text-amber-700', 'icon' => 'fa-clock', 'icon_class' => 'border-amber-200 bg-amber-50 text-amber-600'],
            ['label' => __('Today Collection'), 'amount' => $todayCollection, 'value_class' => 'text-cyan-700', 'icon' => 'fa-money-bill-wave', 'icon_class' => 'border-cyan-200 bg-cyan-50 text-cyan-600'],
            ['label' => __('Monthly Revenue'), 'amount' => $monthlyRevenue, 'value_class' => 'text-indigo-700', 'icon' => 'fa-chart-column', 'icon_class' => 'border-indigo-200 bg-indigo-50 text-indigo-600'],
            ['label' => __('Outstanding Due'), 'amount' => $totalOutstandingDue, 'value_class' => 'text-rose-700', 'icon' => 'fa-triangle-exclamation', 'icon_class' => 'border-rose-200 bg-rose-50 text-rose-600'],
        ] as $card)
            <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
                <div class="min-w-0 pr-1">
                    <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ $card['label'] }}</span>
                    @isset($card['amount'])
                        <span class="text-[13px] font-bold font-mono {{ $card['value_class'] }} leading-tight block truncate">@currency($card['amount'])</span>
                    @else
                        <span class="text-[13px] font-bold font-mono {{ $card['value_class'] }} leading-tight block truncate">{{ $card['value'] }}</span>
                    @endif
                </div>
                <div class="w-6 h-6 rounded-md text-[10px] border {{ $card['icon_class'] }} flex items-center justify-center flex-shrink-0">
                    <i class="fas {{ $card['icon'] }}"></i>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Operational health --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
        @foreach([
            ['label' => __('Online Routers'), 'value' => $onlineRouters . ' / ' . $routerCount, 'href' => route('tenant.network.mikrotik'), 'icon' => 'fa-router', 'link_class' => 'hover:border-cyan-300', 'icon_class' => 'text-cyan-600'],
            ['label' => __('OLT Devices'), 'value' => number_format($oltCount), 'href' => route('tenant.network.olt'), 'icon' => 'fa-tower-broadcast', 'link_class' => 'hover:border-blue-300', 'icon_class' => 'text-blue-600'],
            ['label' => __('ONU / ONT'), 'value' => number_format($onuCount), 'href' => route('tenant.network.onu'), 'icon' => 'fa-server', 'link_class' => 'hover:border-purple-300', 'icon_class' => 'text-purple-600'],
            ['label' => __('Open Tickets'), 'value' => number_format($openTicketsCount), 'href' => route('tenant.tickets.index'), 'icon' => 'fa-headset', 'link_class' => 'hover:border-amber-300', 'icon_class' => 'text-amber-600'],
        ] as $health)
            <a href="{{ $health['href'] }}" class="bg-white rounded-lg border border-slate-200 p-2.5 shadow-xs flex items-center justify-between {{ $health['link_class'] }} transition">
                <div class="min-w-0">
                    <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ $health['label'] }}</span>
                    <span class="text-sm font-bold font-mono text-slate-800 block mt-0.5">{{ $health['value'] }}</span>
                </div>
                <i class="fas {{ $health['icon'] }} {{ $health['icon_class'] }} text-sm"></i>
            </a>
        @endforeach
    </div>

    {{-- Recent activity --}}
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-3">
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="px-3.5 py-2.5 border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i class="fas fa-users text-blue-600 text-xs"></i>
                    <h2 class="text-xs font-bold text-slate-800">{{ __('Recent Customers') }}</h2>
                </div>
                <a href="{{ route('tenant.customers.index') }}" class="text-[10px] font-semibold text-blue-600 hover:underline">{{ __('View All') }}</a>
            </div>
            <div class="overflow-x-auto">
                <table class="saas-table">
                    <thead>
                        <tr>
                            <th class="text-center">{{ __('Customer') }}</th>
                            <th class="text-center">{{ __('Package') }}</th>
                            <th class="text-center">{{ __('Status') }}</th>
                            <th class="text-center">{{ __('Due') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentCustomers as $customer)
                            <tr>
                                <td class="text-left">{{ $customer->name ?? $customer->username ?? __('Unknown') }}</td>
                                <td class="text-left">{{ $customer->package_display_name ?? __('Not Set') }}</td>
                                <td class="text-center"><span class="px-1.5 py-0.5 rounded border text-[9px] font-semibold {{ $customer->status === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-600 border-slate-200' }}">{{ __($customer->status ?? 'Unknown') }}</span></td>
                                <td class="text-right font-mono">@currency($customer->due_amount ?? 0)</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center py-8 text-slate-400">{{ __('No customers found.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="px-3.5 py-2.5 border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i class="fas fa-money-bill-wave text-emerald-600 text-xs"></i>
                    <h2 class="text-xs font-bold text-slate-800">{{ __('Recent Collections') }}</h2>
                </div>
                <a href="{{ route('tenant.finance.payments') }}" class="text-[10px] font-semibold text-emerald-600 hover:underline">{{ __('View All') }}</a>
            </div>
            <div class="overflow-x-auto">
                <table class="saas-table">
                    <thead>
                        <tr>
                            <th class="text-center">{{ __('Receipt') }}</th>
                            <th class="text-center">{{ __('Customer') }}</th>
                            <th class="text-center">{{ __('Date') }}</th>
                            <th class="text-center">{{ __('Amount') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentPayments as $payment)
                            <tr>
                                <td class="text-center font-mono">{{ $payment->invoice_no ?? ('#' . $payment->id) }}</td>
                                <td class="text-left">{{ $payment->customer?->name ?? __('Unknown') }}</td>
                                <td class="text-center">{{ optional($payment->created_at)->format('d M Y') }}</td>
                                <td class="text-right font-mono font-semibold text-emerald-700">@currency($payment->amount)</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center py-8 text-slate-400">{{ __('No collections found.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Subscription and support status --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-3">
            <div class="flex items-center justify-between mb-2">
                <h2 class="text-xs font-bold text-slate-800">{{ __('Subscription') }}</h2>
                <i class="fas fa-calendar-check text-indigo-600 text-xs"></i>
            </div>
            <div class="flex items-end justify-between">
                <span class="text-xl font-bold font-mono {{ ($daysRemaining !== null && $daysRemaining < 15) ? 'text-rose-600' : 'text-slate-800' }}">{{ $daysRemaining === null ? __('Lifetime') : max(0, $daysRemaining) }}</span>
                @if($daysRemaining !== null)
                    <span class="text-[10px] text-slate-500">{{ __('days remaining') }}</span>
                @endif
            </div>
        </div>
        <a href="{{ route('tenant.tickets.index') }}" class="bg-white rounded-xl border border-slate-200 shadow-xs p-3 hover:border-amber-300 transition">
            <div class="flex items-center justify-between mb-2">
                <h2 class="text-xs font-bold text-slate-800">{{ __('Support Queue') }}</h2>
                <i class="fas fa-headset text-amber-600 text-xs"></i>
            </div>
            <span class="text-xl font-bold font-mono text-amber-700">{{ number_format($openTicketsCount) }}</span>
            <span class="text-[10px] text-slate-500 block">{{ __('open tickets') }}</span>
        </a>
        <a href="{{ route('tenant.tickets.field-jobs') }}" class="bg-white rounded-xl border border-slate-200 shadow-xs p-3 hover:border-cyan-300 transition">
            <div class="flex items-center justify-between mb-2">
                <h2 class="text-xs font-bold text-slate-800">{{ __('Field Jobs') }}</h2>
                <i class="fas fa-screwdriver-wrench text-cyan-600 text-xs"></i>
            </div>
            <span class="text-xl font-bold font-mono text-cyan-700">{{ number_format($pendingFieldJobsCount) }}</span>
            <span class="text-[10px] text-slate-500 block">{{ __('pending jobs') }}</span>
        </a>
    </div>
</div>
@endsection
