@extends('tenant.layouts.app')

@section('title', 'Admin Dashboard - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
    <style>
        .stat-card-gradient {
            background: linear-gradient(135deg, rgba(255,255,255,0.95) 0%, rgba(248,250,252,0.95) 100%);
        }
    </style>
@endpush

@section('content')
<div class="space-y-4" x-data="{ 
    activeTab: 'overview',
    refreshing: false
}">

    {{-- 1. Top Welcome & Action Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white px-4 py-3 rounded-2xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-cyan-600 to-blue-600 text-white flex items-center justify-center text-lg shadow-sm flex-shrink-0">
                <i class="fas fa-chart-line"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-sm font-bold text-slate-800 tracking-tight">{{ $tenant->company_name ?? $tenant->name ?? 'ISP Management Portal' }}</h1>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <i class="fas fa-circle text-[6px] mr-1 text-emerald-500 animate-pulse"></i>{{ __('Live System') }}
                    </span>
                </div>
                <p class="text-[11px] text-slate-500 mt-0.5">
                    {{ __('Welcome back') }}, <span class="font-semibold text-slate-700">{{ $user->name ?? 'Administrator' }}</span>! {{ __('Here is your ISP network & billing summary.') }}
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
            @if(Route::has('tenant.customers.create'))
            <a href="{{ route('tenant.customers.create') }}" class="px-3 py-1.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-xl text-xs font-semibold shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-user-plus text-[11px]"></i>
                <span>{{ __('New Customer') }}</span>
            </a>
            @endif

            @if(Route::has('tenant.billing.payments.create'))
            <a href="{{ route('tenant.billing.payments.create') }}" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-money-bill-wave text-[11px]"></i>
                <span>{{ __('Collect Bill') }}</span>
            </a>
            @endif

            @if(Route::has('tenant.mikrotik.index'))
            <a href="{{ route('tenant.mikrotik.index') }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold border border-slate-200 transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-server text-indigo-600 text-[11px]"></i>
                <span>{{ __('MikroTik Routers') }}</span>
            </a>
            @endif
        </div>
    </div>

    {{-- 2. KPI Summary Strip (6 Metric Cards) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2.5">
        {{-- Total Customers --}}
        <div class="stat-card-gradient p-3 rounded-2xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[10px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Active Clients') }}</span>
                <div class="flex items-baseline gap-1 mt-0.5">
                    <span class="text-base font-extrabold font-mono text-cyan-600 leading-tight">{{ number_format($activeCustomers) }}</span>
                    <span class="text-[10px] text-slate-400 font-mono">/ {{ number_format($totalCustomers) }}</span>
                </div>
            </div>
            <div class="w-8 h-8 rounded-xl text-xs border border-cyan-200 bg-cyan-50 text-cyan-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-users"></i>
            </div>
        </div>

        {{-- Today Collection --}}
        <div class="stat-card-gradient p-3 rounded-2xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[10px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Today Collected') }}</span>
                <span class="text-base font-extrabold font-mono text-emerald-600 leading-tight block truncate mt-0.5">৳{{ number_format($todayCollection) }}</span>
            </div>
            <div class="w-8 h-8 rounded-xl text-xs border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-hand-holding-dollar"></i>
            </div>
        </div>

        {{-- Monthly Revenue --}}
        <div class="stat-card-gradient p-3 rounded-2xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[10px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Monthly Billing') }}</span>
                <span class="text-base font-extrabold font-mono text-blue-600 leading-tight block truncate mt-0.5">৳{{ number_format($monthlyRevenue) }}</span>
            </div>
            <div class="w-8 h-8 rounded-xl text-xs border border-blue-200 bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-coins"></i>
            </div>
        </div>

        {{-- Outstanding Due --}}
        <div class="stat-card-gradient p-3 rounded-2xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[10px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Total Due') }}</span>
                <span class="text-base font-extrabold font-mono text-rose-600 leading-tight block truncate mt-0.5">৳{{ number_format($totalOutstandingDue) }}</span>
            </div>
            <div class="w-8 h-8 rounded-xl text-xs border border-rose-200 bg-rose-50 text-rose-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-receipt"></i>
            </div>
        </div>

        {{-- Online Routers --}}
        <div class="stat-card-gradient p-3 rounded-2xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[10px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Network Routers') }}</span>
                <div class="flex items-baseline gap-1 mt-0.5">
                    <span class="text-base font-extrabold font-mono text-indigo-600 leading-tight">{{ number_format($onlineRouters) }}</span>
                    <span class="text-[10px] text-slate-400 font-mono">/ {{ number_format($routerCount) }} Online</span>
                </div>
            </div>
            <div class="w-8 h-8 rounded-xl text-xs border border-indigo-200 bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-network-wired"></i>
            </div>
        </div>

        {{-- Support & Tickets --}}
        <div class="stat-card-gradient p-3 rounded-2xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[10px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Open Tickets') }}</span>
                <span class="text-base font-extrabold font-mono text-amber-600 leading-tight block truncate mt-0.5">{{ number_format($openTicketsCount) }}</span>
            </div>
            <div class="w-8 h-8 rounded-xl text-xs border border-amber-200 bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-headset"></i>
            </div>
        </div>
    </div>

    {{-- 3. Main Dashboard Grid (2 Columns) --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        
        {{-- Left 2 Columns: Customers & Payments Tables --}}
        <div class="lg:col-span-2 space-y-4">
            
            {{-- Recent Customers Table Card --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-lg bg-cyan-50 text-cyan-600 flex items-center justify-center text-xs">
                            <i class="fas fa-user-group"></i>
                        </div>
                        <h2 class="text-xs font-bold text-slate-800">{{ __('Recently Added Customers') }}</h2>
                    </div>
                    @if(Route::has('tenant.customers.index'))
                    <a href="{{ route('tenant.customers.index') }}" class="text-[11px] font-semibold text-cyan-600 hover:text-cyan-700 flex items-center gap-1">
                        <span>{{ __('View All') }}</span>
                        <i class="fas fa-arrow-right text-[9px]"></i>
                    </a>
                    @endif
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50/80 text-slate-500 text-[10px] uppercase tracking-wider border-b border-slate-100">
                                <th class="px-4 py-2 font-semibold">{{ __('Customer / Username') }}</th>
                                <th class="px-3 py-2 font-semibold">{{ __('Mobile / Zone') }}</th>
                                <th class="px-3 py-2 font-semibold">{{ __('Package') }}</th>
                                <th class="px-3 py-2 font-semibold text-right">{{ __('Monthly Bill') }}</th>
                                <th class="px-4 py-2 font-semibold text-center">{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @forelse($recentCustomers as $customer)
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="px-4 py-2.5">
                                    <div class="font-bold text-slate-800">{{ $customer->name }}</div>
                                    <div class="text-[10px] text-cyan-600 font-mono">{{ $customer->username ?? $customer->pppoe_username ?? 'N/A' }}</div>
                                </td>
                                <td class="px-3 py-2.5">
                                    <div class="text-slate-700 font-mono">{{ $customer->phone ?? '—' }}</div>
                                    <div class="text-[10px] text-slate-400">{{ $customer->zone->name ?? $customer->zone_name ?? 'Default Zone' }}</div>
                                </td>
                                <td class="px-3 py-2.5">
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                        {{ $customer->package->name ?? $customer->package_name ?? 'Standard' }}
                                    </span>
                                </td>
                                <td class="px-3 py-2.5 text-right font-mono font-bold text-slate-800">
                                    ৳{{ number_format($customer->monthly_bill ?? 0, 2) }}
                                </td>
                                <td class="px-4 py-2.5 text-center">
                                    @php
                                        $statusColor = match($customer->status ?? 'active') {
                                            'active' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                            'expired' => 'bg-rose-50 text-rose-700 border-rose-200',
                                            'suspended' => 'bg-amber-50 text-amber-700 border-amber-200',
                                            default => 'bg-slate-100 text-slate-700 border-slate-200'
                                        };
                                    @endphp
                                    <span class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider border {{ $statusColor }}">
                                        {{ $customer->status ?? 'active' }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-slate-400">
                                    <i class="fas fa-users text-2xl mb-2 text-slate-300 block"></i>
                                    <span>{{ __('No customer records found yet. Click "New Customer" to add one.') }}</span>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Recent Collections & Payments Table Card --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs">
                            <i class="fas fa-money-bill-transfer"></i>
                        </div>
                        <h2 class="text-xs font-bold text-slate-800">{{ __('Recent Billing Collections') }}</h2>
                    </div>
                    @if(Route::has('tenant.billing.payments'))
                    <a href="{{ route('tenant.billing.payments') }}" class="text-[11px] font-semibold text-emerald-600 hover:text-emerald-700 flex items-center gap-1">
                        <span>{{ __('View Ledger') }}</span>
                        <i class="fas fa-arrow-right text-[9px]"></i>
                    </a>
                    @endif
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50/80 text-slate-500 text-[10px] uppercase tracking-wider border-b border-slate-100">
                                <th class="px-4 py-2 font-semibold">{{ __('Transaction ID') }}</th>
                                <th class="px-3 py-2 font-semibold">{{ __('Customer') }}</th>
                                <th class="px-3 py-2 font-semibold">{{ __('Payment Method') }}</th>
                                <th class="px-3 py-2 font-semibold text-right">{{ __('Amount') }}</th>
                                <th class="px-4 py-2 font-semibold text-right">{{ __('Time') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @forelse($recentPayments as $payment)
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="px-4 py-2.5 font-mono text-[11px] font-semibold text-slate-800">
                                    {{ $payment->trx_id ?? ('TRX-' . $payment->id) }}
                                </td>
                                <td class="px-3 py-2.5">
                                    <div class="font-semibold text-slate-800">{{ $payment->customer->name ?? 'Direct Client' }}</div>
                                    <div class="text-[10px] text-slate-400 font-mono">{{ $payment->customer->phone ?? '' }}</div>
                                </td>
                                <td class="px-3 py-2.5">
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-slate-100 text-slate-700 border border-slate-200 uppercase">
                                        {{ $payment->payment_method ?? 'Cash' }}
                                    </span>
                                </td>
                                <td class="px-3 py-2.5 text-right font-mono font-bold text-emerald-600">
                                    +৳{{ number_format($payment->amount ?? 0, 2) }}
                                </td>
                                <td class="px-4 py-2.5 text-right text-[10px] text-slate-400">
                                    {{ $payment->created_at ? $payment->created_at->diffForHumans() : '—' }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-slate-400">
                                    <i class="fas fa-receipt text-2xl mb-2 text-slate-300 block"></i>
                                    <span>{{ __('No payment transactions recorded today yet.') }}</span>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        {{-- Right 1 Column: Quick Navigation & Network Status --}}
        <div class="space-y-4">
            
            {{-- Quick Operations Shortcuts --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-4 space-y-3">
                <div class="flex items-center gap-2 border-b border-slate-100 pb-2.5">
                    <div class="w-6 h-6 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <h2 class="text-xs font-bold text-slate-800">{{ __('Quick Operations Hub') }}</h2>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    @if(Route::has('tenant.customers.index'))
                    <a href="{{ route('tenant.customers.index') }}" class="p-2.5 rounded-xl border border-slate-200 hover:border-cyan-300 hover:bg-cyan-50/50 transition group flex flex-col items-center text-center">
                        <div class="w-8 h-8 rounded-lg bg-cyan-100 text-cyan-600 group-hover:scale-110 transition flex items-center justify-center text-xs mb-1.5">
                            <i class="fas fa-users"></i>
                        </div>
                        <span class="text-[11px] font-bold text-slate-700">{{ __('Clients') }}</span>
                        <span class="text-[9px] text-slate-400">{{ __('PPPoE / Static') }}</span>
                    </a>
                    @endif

                    @if(Route::has('tenant.mikrotik.index'))
                    <a href="{{ route('tenant.mikrotik.index') }}" class="p-2.5 rounded-xl border border-slate-200 hover:border-indigo-300 hover:bg-indigo-50/50 transition group flex flex-col items-center text-center">
                        <div class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-600 group-hover:scale-110 transition flex items-center justify-center text-xs mb-1.5">
                            <i class="fas fa-server"></i>
                        </div>
                        <span class="text-[11px] font-bold text-slate-700">{{ __('Routers') }}</span>
                        <span class="text-[9px] text-slate-400">{{ __('Mikrotik API') }}</span>
                    </a>
                    @endif

                    @if(Route::has('tenant.packages.index'))
                    <a href="{{ route('tenant.packages.index') }}" class="p-2.5 rounded-xl border border-slate-200 hover:border-emerald-300 hover:bg-emerald-50/50 transition group flex flex-col items-center text-center">
                        <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-600 group-hover:scale-110 transition flex items-center justify-center text-xs mb-1.5">
                            <i class="fas fa-cubes"></i>
                        </div>
                        <span class="text-[11px] font-bold text-slate-700">{{ __('Packages') }}</span>
                        <span class="text-[9px] text-slate-400">{{ __('Bandwidth Plans') }}</span>
                    </a>
                    @endif

                    @if(Route::has('tenant.billing.invoices'))
                    <a href="{{ route('tenant.billing.invoices') }}" class="p-2.5 rounded-xl border border-slate-200 hover:border-amber-300 hover:bg-amber-50/50 transition group flex flex-col items-center text-center">
                        <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-600 group-hover:scale-110 transition flex items-center justify-center text-xs mb-1.5">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </div>
                        <span class="text-[11px] font-bold text-slate-700">{{ __('Invoices') }}</span>
                        <span class="text-[9px] text-slate-400">{{ __('Billing / Due') }}</span>
                    </a>
                    @endif

                    @if(Route::has('tenant.noc.dashboard'))
                    <a href="{{ route('tenant.noc.dashboard') }}" class="p-2.5 rounded-xl border border-slate-200 hover:border-purple-300 hover:bg-purple-50/50 transition group flex flex-col items-center text-center">
                        <div class="w-8 h-8 rounded-lg bg-purple-100 text-purple-600 group-hover:scale-110 transition flex items-center justify-center text-xs mb-1.5">
                            <i class="fas fa-screwdriver-wrench"></i>
                        </div>
                        <span class="text-[11px] font-bold text-slate-700">{{ __('NOC Hub') }}</span>
                        <span class="text-[9px] text-slate-400">{{ __('Field Jobs') }}</span>
                    </a>
                    @endif

                    @if(Route::has('tenant.reports.revenue'))
                    <a href="{{ route('tenant.reports.revenue') }}" class="p-2.5 rounded-xl border border-slate-200 hover:border-blue-300 hover:bg-blue-50/50 transition group flex flex-col items-center text-center">
                        <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-600 group-hover:scale-110 transition flex items-center justify-center text-xs mb-1.5">
                            <i class="fas fa-chart-pie"></i>
                        </div>
                        <span class="text-[11px] font-bold text-slate-700">{{ __('Reports') }}</span>
                        <span class="text-[9px] text-slate-400">{{ __('BTRC & Profit') }}</span>
                    </a>
                    @endif
                </div>
            </div>

            {{-- Network Hardware Summary Card --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-4 space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center text-xs">
                            <i class="fas fa-microchip"></i>
                        </div>
                        <h2 class="text-xs font-bold text-slate-800">{{ __('Network Infrastructure') }}</h2>
                    </div>
                    <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-slate-100 text-slate-600">
                        {{ $routerCount }} {{ __('Routers') }}
                    </span>
                </div>

                <div class="space-y-2 text-xs">
                    <div class="flex items-center justify-between p-2 rounded-xl bg-slate-50 border border-slate-100">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-server text-cyan-600"></i>
                            <span class="font-medium text-slate-700">{{ __('MikroTik Routers') }}</span>
                        </div>
                        <span class="font-bold font-mono text-slate-800">{{ $routerCount }}</span>
                    </div>

                    <div class="flex items-center justify-between p-2 rounded-xl bg-slate-50 border border-slate-100">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-network-wired text-indigo-600"></i>
                            <span class="font-medium text-slate-700">{{ __('OLT Units') }}</span>
                        </div>
                        <span class="font-bold font-mono text-slate-800">{{ $oltCount }}</span>
                    </div>

                    <div class="flex items-center justify-between p-2 rounded-xl bg-slate-50 border border-slate-100">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-box text-emerald-600"></i>
                            <span class="font-medium text-slate-700">{{ __('Provisioned ONUs') }}</span>
                        </div>
                        <span class="font-bold font-mono text-slate-800">{{ $onuCount }}</span>
                    </div>

                    <div class="flex items-center justify-between p-2 rounded-xl bg-slate-50 border border-slate-100">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-ticket text-amber-600"></i>
                            <span class="font-medium text-slate-700">{{ __('Pending Tickets') }}</span>
                        </div>
                        <span class="font-bold font-mono text-amber-600">{{ $openTicketsCount }}</span>
                    </div>
                </div>
            </div>

            {{-- Subscription Validity Widget --}}
            @if(!is_null($daysRemaining))
            <div class="p-3.5 rounded-2xl bg-gradient-to-br from-slate-900 to-indigo-950 text-white shadow-sm space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-medium uppercase tracking-wider text-slate-300">{{ __('SaaS Subscription') }}</span>
                    <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-indigo-500/30 text-indigo-200 border border-indigo-400/30">
                        {{ $tenant->plan->name ?? 'Pro Plan' }}
                    </span>
                </div>
                <div class="flex items-baseline justify-between">
                    <div class="text-sm font-extrabold text-white">
                        {{ $daysRemaining > 0 ? $daysRemaining . ' Days Left' : 'Expired' }}
                    </div>
                    <span class="text-[10px] text-slate-300">
                        {{ $tenant->subscription_expires_at ? \Carbon\Carbon::parse($tenant->subscription_expires_at)->format('d M, Y') : '' }}
                    </span>
                </div>
            </div>
            @endif

        </div>

    </div>

</div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            // Dashboard scripts & reactive hooks
        });
    </script>
@endpush
