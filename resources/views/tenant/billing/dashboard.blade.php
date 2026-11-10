@extends('tenant.layouts.app')

@section('title', __('Plan Overview & Subscription') . ' - ' . ($tenant->company_name ?? $tenant->name ?? 'ISP Portal'))

@push('styles')
    {{-- Page-specific CSS --}}
@endpush

@section('content')
@php
    $exp = $tenant->subscription_expires_at ? \Carbon\Carbon::parse($tenant->subscription_expires_at) : null;
    $custLimit = (int) ($plan->customer_limit ?? 500);
    $resLimit = (int) ($plan->reseller_limit ?? 10);
    $mkLimit = (int) ($plan->mikrotik_limit ?? 2);
    $oltLimit = (int) ($plan->olt_limit ?? 1);

    $buildQuotaCard = static function (string $label, string $subLabel, string $icon, string $baseColor, int $used, int $limit): array {
        $pct = $limit > 0 ? min(100, round(($used / $limit) * 100, 1)) : 0;
        $isFull = $limit > 0 && $used >= $limit;
        $isNear = !$isFull && $limit > 0 && $pct >= 85;
        $barClass = $isFull ? 'bg-rose-600' : ($isNear ? 'bg-amber-500' : "bg-{$baseColor}-600");
        $statusClass = $isFull ? 'text-rose-600 font-bold' : ($isNear ? 'text-amber-700 font-semibold' : 'text-slate-500 font-medium');

        return [
            'label' => $label,
            'sub_label' => $subLabel,
            'icon' => $icon,
            'icon_color' => "text-{$baseColor}-600",
            'used' => $used,
            'limit' => $limit,
            'pct' => $pct,
            'is_full' => $isFull,
            'bar_class' => $barClass,
            'status_class' => $statusClass,
        ];
    };

    $quotaCards = [
        $buildQuotaCard(__('ISP Customers'), number_format($activeCustomersCount) . ' ' . __('Active'), 'fa-users', 'blue', $customersCount, $custLimit),
        $buildQuotaCard(__('Sub-ISPs / Resellers'), __('Franchise Network'), 'fa-handshake', 'purple', $resellersCount, $resLimit),
        $buildQuotaCard(__('MikroTik Routers'), __('Gateways & BRAS'), 'fa-network-wired', 'indigo', $mikrotikCount, $mkLimit),
        $buildQuotaCard(__('OLT Devices'), __('Optical Line Terminals'), 'fa-server', 'emerald', $oltCount, $oltLimit),
    ];

    $featureBadges = [
        ['enabled' => (bool) ($plan->allow_radius ?? true), 'label' => 'RADIUS'],
        ['enabled' => (bool) ($plan->allow_wireguard ?? false), 'label' => 'WireGuard'],
        ['enabled' => (bool) ($plan->allow_snmp_monitoring ?? false), 'label' => __('SNMP Monitoring')],
    ];
@endphp

<div class="space-y-3" x-data="billingDashboard()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">

    {{-- 1. Top Header Bar (Icon + Title + Action Buttons ONLY - Zero Subtitle) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs shadow-2xs flex-shrink-0">
                <i class="fas fa-file-invoice-dollar"></i>
            </div>
            <h1 class="text-xs sm:text-sm font-bold text-slate-900 tracking-tight">{{ __('Plan Overview & Subscription') }}</h1>
        </div>
        <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap flex-shrink-0">
            @if($currentInvoice)
                <a href="{{ route('tenant.billing.invoice.show', $currentInvoice) }}"
                   class="h-[34px] px-3 rounded-lg bg-amber-600 hover:bg-amber-700 text-white font-semibold text-xs shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                    <i class="fas fa-credit-card text-xs"></i>
                    <span>{{ __('Pay Due') }} (@currency($currentInvoice->due_amount ?: $currentInvoice->amount))</span>
                </a>
            @endif

            <a href="{{ route('tenant.billing.invoices') }}"
               class="h-[34px] px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-file-invoice text-cyan-600 text-xs"></i>
                <span>{{ __('Invoices') }}</span>
            </a>

            <a href="{{ route('tenant.billing.payments') }}"
               class="h-[34px] px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-receipt text-emerald-600 text-xs"></i>
                <span>{{ __('Payment History') }}</span>
            </a>

            <button type="button"
                    @click="showUpgradeModal = true"
                    class="h-[34px] px-3.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-arrow-up-right-dots text-xs"></i>
                <span>{{ __('Upgrade Plan') }}</span>
            </button>
        </div>
    </div>

    {{-- 2. KPI Summary Strip (Strictly 6 Cards) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between gap-2">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Active Plan') }}</span>
                <span class="text-[13px] font-bold font-mono text-cyan-700 leading-tight block truncate">
                    {{ $plan->name ?? __('Standard Plan') }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-cyan-200 bg-cyan-50 text-cyan-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-box-open"></i>
            </div>
        </div>

        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between gap-2">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('License Status') }}</span>
                <span class="text-[13px] font-bold font-mono {{ $tenant->status === 'active' ? 'text-emerald-600' : 'text-rose-600' }} leading-tight block truncate">
                    {{ __(ucfirst($tenant->status ?? 'active')) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border {{ $tenant->status === 'active' ? 'border-emerald-200 bg-emerald-50 text-emerald-600' : 'border-rose-200 bg-rose-50 text-rose-600' }} flex items-center justify-center flex-shrink-0">
                <i class="fas {{ $tenant->status === 'active' ? 'fa-shield-halved' : 'fa-triangle-exclamation' }}"></i>
            </div>
        </div>

        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between gap-2">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Plan Validity') }}</span>
                <span class="text-[13px] font-bold font-mono text-indigo-700 leading-tight block truncate">
                    {{ $exp ? $exp->format('d M Y') : __('Lifetime') }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-indigo-200 bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-calendar-check"></i>
            </div>
        </div>

        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between gap-2">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Outstanding Due') }}</span>
                <span class="text-[13px] font-bold font-mono {{ $totalOutstanding > 0 ? 'text-rose-600' : 'text-slate-800' }} leading-tight block truncate">
                    @currency($totalOutstanding)
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border {{ $totalOutstanding > 0 ? 'border-rose-200 bg-rose-50 text-rose-600' : 'border-slate-200 bg-slate-50 text-slate-600' }} flex items-center justify-center flex-shrink-0">
                <i class="fas fa-clock"></i>
            </div>
        </div>

        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between gap-2">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Total Settled') }}</span>
                <span class="text-[13px] font-bold font-mono text-emerald-600 leading-tight block truncate">
                    @currency($totalPaid)
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-circle-check"></i>
            </div>
        </div>

        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between gap-2">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Tenant Wallet') }}</span>
                <span class="text-[13px] font-bold font-mono text-purple-700 leading-tight block truncate">
                    @currency($tenant->wallet_balance ?? 0)
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-purple-200 bg-purple-50 text-purple-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-wallet"></i>
            </div>
        </div>
    </div>

    {{-- 2.1 Pending Invoice Alert --}}
    @if($currentInvoice)
        <div class="p-3 rounded-xl bg-amber-50/90 border border-amber-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 shadow-2xs">
            <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center text-xs flex-shrink-0 border border-amber-200">
                    <i class="fas fa-triangle-exclamation"></i>
                </div>
                <div>
                    <span class="font-bold text-amber-900 block text-xs">{{ __('Unpaid Subscription Invoice #:no', ['no' => $currentInvoice->invoice_no]) }}</span>
                    <span class="text-[11px] text-amber-800 font-normal">
                        {{ __('Due Date') }}: <span class="font-mono font-medium">{{ $currentInvoice->due_date ? \Carbon\Carbon::parse($currentInvoice->due_date)->format('d M Y') : __('Immediate') }}</span> •
                        {{ __('Amount') }}: <span class="font-mono font-bold">@currency($currentInvoice->due_amount ?: $currentInvoice->amount)</span>
                    </span>
                </div>
            </div>
            <div>
                <a href="{{ route('tenant.billing.invoice.show', $currentInvoice) }}"
                   class="h-[32px] px-3.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white font-semibold text-xs shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                    <span>{{ __('Pay Online') }}</span>
                    <i class="fas fa-arrow-right text-[10px]"></i>
                </a>
            </div>
        </div>
    @endif

    {{-- 3. ISP Resource Quota & Live Utilization --}}
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs space-y-3">
        <div class="flex items-center justify-between border-b border-slate-100 pb-2.5 gap-2 flex-wrap">
            <div class="flex items-center gap-2">
                <div class="w-6 h-6 rounded-md bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                    <i class="fas fa-sliders"></i>
                </div>
                <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wider">{{ __('ISP Resource Quota & Live Utilization') }}</h2>
            </div>
            <span class="text-[11px] text-slate-500 font-mono">
                {{ __('Subscription Plan') }}: <strong class="text-cyan-700">{{ $plan->name ?? __('Standard Plan') }}</strong>
            </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            @foreach($quotaCards as $qc)
                <div class="p-3 rounded-lg border border-slate-200 bg-slate-50/50 space-y-2">
                    <div class="flex items-center justify-between text-xs gap-2">
                        <span class="font-semibold text-slate-700 flex items-center gap-1.5 truncate">
                            <i class="fas {{ $qc['icon'] }} {{ $qc['icon_color'] }} text-[11px] flex-shrink-0"></i>
                            <span class="truncate">{{ $qc['label'] }}</span>
                        </span>
                        <span class="font-mono font-bold text-slate-900 text-[11px] flex-shrink-0">
                            {{ number_format($qc['used']) }} / {{ $qc['limit'] > 0 ? number_format($qc['limit']) : __('Unlimited') }}
                        </span>
                    </div>
                    <div class="w-full bg-slate-200 rounded-full h-1.5 overflow-hidden">
                        <div class="{{ $qc['bar_class'] }} h-1.5 rounded-full transition-all duration-300" style="width: {{ $qc['pct'] }}%"></div>
                    </div>
                    <div class="flex items-center justify-between text-[10px]">
                        <span class="text-slate-500">{{ $qc['sub_label'] }}</span>
                        <span class="font-mono {{ $qc['status_class'] }}">
                            @if($qc['is_full'])
                                {{ __('Quota Full') }} ({{ $qc['pct'] }}%)
                            @else
                                {{ $qc['pct'] }}% {{ __('Used') }}
                            @endif
                        </span>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Operational Stats & Feature Entitlements --}}
        <div class="pt-2 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3 text-xs">
            <div class="flex items-center gap-4 flex-wrap">
                <span class="flex items-center gap-1.5 text-slate-700 font-medium">
                    <i class="fas fa-user-tie text-cyan-600 text-[11px]"></i>
                    <span>{{ __('Team & Staff') }}: <strong class="font-mono text-slate-900">{{ number_format($staffCount) }}</strong></span>
                </span>
                <span class="text-slate-300">•</span>
                <span class="flex items-center gap-1.5 text-slate-700 font-medium">
                    <i class="fas fa-box text-purple-600 text-[11px]"></i>
                    <span>{{ __('Internet Packages') }}: <strong class="font-mono text-slate-900">{{ number_format($packagesCount) }}</strong></span>
                </span>
                <span class="text-slate-300">•</span>
                <span class="flex items-center gap-1.5 text-slate-700 font-medium">
                    <i class="fas fa-comment-sms text-emerald-600 text-[11px]"></i>
                    <span>{{ __('Total SMS Dispatched') }}: <strong class="font-mono text-slate-900">{{ number_format($tenant->total_sms_count ?? 0) }}</strong></span>
                </span>
            </div>

            <div class="flex items-center gap-1.5 flex-wrap">
                @foreach($featureBadges as $fb)
                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold border inline-flex items-center gap-1 {{ $fb['enabled'] ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-400 border-slate-200' }}">
                        <i class="fas {{ $fb['enabled'] ? 'fa-check' : 'fa-lock' }} text-[8px]"></i>
                        <span>{{ $fb['label'] }}</span>
                    </span>
                @endforeach
            </div>
        </div>
    </div>

    {{-- 4. Recent Invoices & Payment Transactions Tables (Strict AGENTS.md .saas-table Alignment) --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
        {{-- Table A: Recent Subscription Invoices --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden flex flex-col">
            <div class="p-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-md bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                    <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">{{ __('Recent Subscription Invoices') }}</h3>
                </div>
                <a href="{{ route('tenant.billing.invoices') }}" class="text-[11px] font-semibold text-cyan-600 hover:text-cyan-800 flex items-center gap-1">
                    <span>{{ __('View All') }}</span>
                    <i class="fas fa-arrow-right text-[9px]"></i>
                </a>
            </div>

            <div class="overflow-x-auto flex-1">
                <table class="saas-table">
                    <thead>
                        <tr>
                            <th class="w-8 text-center font-semibold no-sort">#</th>
                            <th class="text-center font-semibold">{{ __('Invoice #') }}</th>
                            <th class="text-center font-semibold">{{ __('Plan') }}</th>
                            <th class="text-center font-semibold">{{ __('Amount') }}</th>
                            <th class="text-center font-semibold">{{ __('Status') }}</th>
                            <th class="w-16 text-center font-semibold no-sort">{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentInvoices as $idx => $inv)
                            <tr>
                                <td class="text-center font-mono text-slate-400">{{ $idx + 1 }}</td>
                                <td class="text-center font-mono font-semibold text-slate-800">
                                    <a href="{{ route('tenant.billing.invoice.show', $inv) }}" class="hover:text-cyan-600 hover:underline">
                                        {{ $inv->invoice_no }}
                                    </a>
                                </td>
                                <td class="text-left text-slate-700 truncate max-w-[120px]">
                                    {{ $inv->plan->name ?? __('Standard Plan') }}
                                </td>
                                <td class="text-right font-mono font-bold text-slate-800">
                                    @currency($inv->amount)
                                </td>
                                <td class="text-center">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold border {{ $inv->status === 'paid' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-amber-200 bg-amber-50 text-amber-700' }}">
                                        {{ __(ucfirst($inv->status)) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('tenant.billing.invoice.show', $inv) }}"
                                       class="h-[26px] px-2.5 rounded-md text-[10.5px] font-semibold border transition inline-flex items-center gap-1 {{ $inv->status === 'paid' ? 'bg-slate-50 hover:bg-slate-100 text-slate-700 border-slate-200' : 'bg-amber-600 hover:bg-amber-700 text-white border-amber-600' }}">
                                        <span>{{ $inv->status === 'paid' ? __('View') : __('Pay') }}</span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-8 text-slate-400 text-xs">
                                    {{ __('No subscription invoices generated yet.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Table B: Recent Payment Transactions --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden flex flex-col">
            <div class="p-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-md bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">{{ __('Recent Settlement Transactions') }}</h3>
                </div>
                <a href="{{ route('tenant.billing.payments') }}" class="text-[11px] font-semibold text-cyan-600 hover:text-cyan-800 flex items-center gap-1">
                    <span>{{ __('View All') }}</span>
                    <i class="fas fa-arrow-right text-[9px]"></i>
                </a>
            </div>

            <div class="overflow-x-auto flex-1">
                <table class="saas-table">
                    <thead>
                        <tr>
                            <th class="w-8 text-center font-semibold no-sort">#</th>
                            <th class="text-center font-semibold">{{ __('Transaction Ref') }}</th>
                            <th class="text-center font-semibold">{{ __('Gateway') }}</th>
                            <th class="text-center font-semibold">{{ __('Amount') }}</th>
                            <th class="text-center font-semibold">{{ __('Status') }}</th>
                            <th class="text-center font-semibold">{{ __('Date') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentPayments as $idx => $pmt)
                            <tr>
                                <td class="text-center font-mono text-slate-400">{{ $idx + 1 }}</td>
                                <td class="text-center font-mono text-slate-800 truncate max-w-[120px]" title="{{ $pmt->transaction_reference }}">
                                    {{ $pmt->transaction_reference ?: '--' }}
                                </td>
                                <td class="text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold uppercase font-mono border border-slate-200 bg-slate-50 text-slate-700">
                                        {{ $pmt->payment_method ?: __('Online') }}
                                    </span>
                                </td>
                                <td class="text-right font-mono font-bold text-emerald-700">
                                    @currency($pmt->amount)
                                </td>
                                <td class="text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold border {{ $pmt->status === 'success' || $pmt->status === 'completed' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-rose-200 bg-rose-50 text-rose-700' }}">
                                        {{ __(ucfirst($pmt->status)) }}
                                    </span>
                                </td>
                                <td class="text-center font-mono text-slate-500 text-[11px]">
                                    {{ $pmt->created_at ? $pmt->created_at->format('d M Y') : '--' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-8 text-slate-400 text-xs">
                                    {{ __('No payment settlements recorded yet.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- 5. Tenant Identity Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2">
        <div class="p-2.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between gap-2">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Domain / Portal Host') }}</span>
                <span class="text-[12px] font-bold font-mono text-cyan-700 leading-tight block truncate" title="{{ $tenant->domain ?: ($tenant->slug . '.somitysoft.com') }}">
                    {{ $tenant->domain ?: ($tenant->slug . '.somitysoft.com') }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-cyan-200 bg-cyan-50 text-cyan-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-globe"></i>
            </div>
        </div>

        <div class="p-2.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between gap-2">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Contact Hotline') }}</span>
                <span class="text-[12px] font-bold font-mono text-slate-800 leading-tight block truncate">
                    {{ $tenant->phone ?: __('Not Configured') }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-phone"></i>
            </div>
        </div>

        <div class="p-2.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between gap-2">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Official Email') }}</span>
                <span class="text-[12px] font-bold font-mono text-slate-800 leading-tight block truncate" title="{{ $tenant->email }}">
                    {{ $tenant->email ?: __('Not Configured') }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-purple-200 bg-purple-50 text-purple-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-envelope"></i>
            </div>
        </div>

        <div class="p-2.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between gap-2">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Registered Office') }}</span>
                <span class="text-[12px] font-bold text-slate-800 leading-tight block truncate" title="{{ $tenant->address }}">
                    {{ $tenant->address ?: __('Not Configured') }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-amber-200 bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-map-marker-alt"></i>
            </div>
        </div>
    </div>

    {{-- 6. Upgrade Plan Natural Modal --}}
    <div x-show="showUpgradeModal"
         x-cloak
         class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto"
         style="display: none;">
        <div @click.away="showUpgradeModal = false"
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden my-6 flex flex-col">

            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between flex-shrink-0">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-arrow-up-right-dots"></i>
                    </div>
                    <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">{{ __('Upgrade Subscription or Expand Quotas') }}</h3>
                </div>
                <button type="button" @click="showUpgradeModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <div class="p-5 space-y-3.5 text-xs">
                <div class="p-3 bg-cyan-50/60 rounded-lg border border-cyan-100 text-cyan-950 space-y-1">
                    <span class="font-bold text-xs block">{{ __('Need higher subscriber capacity or additional network licenses?') }}</span>
                    <p class="text-[11px] leading-relaxed text-cyan-900">
                        {{ __('Current Plan') }}: <strong class="font-mono text-cyan-950">{{ $plan->name ?? __('Standard Plan') }}</strong>
                    </p>
                </div>

                <div class="space-y-2">
                    <label class="block text-[10.5px] font-bold uppercase text-slate-600">{{ __('Contact Support & Billing Desk') }}</label>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div class="p-2.5 rounded-lg border border-slate-200 bg-slate-50">
                            <span class="text-[10px] text-slate-500 uppercase font-bold block">{{ __('Hotline Support') }}</span>
                            <span class="font-mono font-bold text-slate-800 text-xs">{{ $supportPhone }}</span>
                        </div>
                        <div class="p-2.5 rounded-lg border border-slate-200 bg-slate-50">
                            <span class="text-[10px] text-slate-500 uppercase font-bold block">{{ __('Billing Inquiries') }}</span>
                            <span class="font-mono font-bold text-slate-800 text-xs">{{ $billingEmail }}</span>
                        </div>
                    </div>
                </div>

                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 text-slate-600 text-[11px] space-y-1">
                    <div class="flex items-center justify-between">
                        <span>{{ __('Current Customer Usage') }}:</span>
                        <span class="font-mono font-bold text-slate-800">{{ number_format($customersCount) }} / {{ $custLimit > 0 ? number_format($custLimit) : __('Unlimited') }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>{{ __('Current Active Plan Expiry') }}:</span>
                        <span class="font-mono font-bold text-slate-800">{{ $exp ? $exp->format('d M Y') : __('Lifetime') }}</span>
                    </div>
                </div>
            </div>

            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between flex-shrink-0">
                <a href="{{ route('tenant.tickets.create') }}" class="h-[34px] px-3.5 bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                    <i class="fas fa-headset text-xs"></i>
                    <span>{{ __('Open Upgrade Ticket') }}</span>
                </a>
                <button type="button" @click="showUpgradeModal = false" class="h-[34px] border border-slate-300 hover:bg-slate-100 text-slate-700 font-semibold text-xs px-4 rounded-lg transition cursor-pointer">
                    {{ __('Close') }}
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function billingDashboard() {
    return {
        activeMenu: null,
        showUpgradeModal: false
    };
}
</script>
@endpush
