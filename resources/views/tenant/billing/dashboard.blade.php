@extends('tenant.layouts.app')

@section('title', 'Plan Overview & Subscription - ' . ($tenant->company_name ?? $tenant->name ?? 'ISP Portal'))

@push('styles')
    {{-- Page-specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="billingDashboard()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">
    
    {{-- 1. TOP HEADER BAR (AGENTS.md Rule 2.A: Icon + Title + Action Buttons ONLY. No subtitle/p tag) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center text-sm shadow-2xs flex-shrink-0">
                <i class="fas fa-file-invoice-dollar"></i>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <h1 class="text-sm font-bold text-slate-800 tracking-tight">Plan Overview &amp; Subscription</h1>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $tenant->status === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                    <span class="w-1.5 h-1.5 rounded-full {{ $tenant->status === 'active' ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                    <span>{{ ucfirst($tenant->status ?? 'active') }}</span>
                </span>
                <span class="px-2 py-0.5 rounded-md text-[10px] font-mono font-medium bg-slate-100 text-slate-600 border border-slate-200">
                    Tenant #{{ $tenant->id }}
                </span>
            </div>
        </div>
        <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap flex-shrink-0">
            @if($currentInvoice)
                <a href="{{ route('tenant.billing.invoice.show', $currentInvoice) }}" 
                   class="h-[34px] px-3 rounded-lg bg-amber-600 hover:bg-amber-700 text-white font-semibold text-xs shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                    <i class="fas fa-credit-card text-xs"></i>
                    <span>Pay Due (@currency($currentInvoice->due_amount ?: $currentInvoice->amount))</span>
                </a>
            @endif

            <a href="{{ route('tenant.billing.invoices') }}" 
               class="h-[34px] px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-file-invoice text-blue-600 text-xs"></i>
                <span>Invoices</span>
            </a>

            <a href="{{ route('tenant.billing.payments') }}" 
               class="h-[34px] px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-receipt text-emerald-600 text-xs"></i>
                <span>Payment History</span>
            </a>

            <button type="button" 
                    @click="showUpgradeModal = true" 
                    class="h-[34px] px-3.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-arrow-up-right-dots text-xs"></i>
                <span>Upgrade Plan</span>
            </button>
        </div>
    </div>

    {{-- 2. KPI SUMMARY STRIP (Strictly 6 Cards - AGENTS.md Rule 2.B) --}}
    @php
        $exp = $tenant->subscription_expires_at ? \Carbon\Carbon::parse($tenant->subscription_expires_at) : null;
        $daysLeft = $exp ? ceil(now()->floatDiffInDays($exp, false)) : null;
    @endphp
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        {{-- Card 1: Active Plan --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Active Plan</span>
                <span class="text-[13px] font-bold font-mono text-blue-700 leading-tight block truncate">
                    {{ $plan->name ?? 'Standard Plan' }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-blue-200 bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-box-open"></i>
            </div>
        </div>

        {{-- Card 2: License Status --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">License Status</span>
                <span class="text-[13px] font-bold font-mono {{ $tenant->status === 'active' ? 'text-emerald-600' : 'text-rose-600' }} leading-tight block truncate">
                    {{ ucfirst($tenant->status ?? 'active') }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border {{ $tenant->status === 'active' ? 'border-emerald-200 bg-emerald-50 text-emerald-600' : 'border-rose-200 bg-rose-50 text-rose-600' }} flex items-center justify-center flex-shrink-0">
                <i class="fas {{ $tenant->status === 'active' ? 'fa-shield-halved' : 'fa-triangle-exclamation' }}"></i>
            </div>
        </div>

        {{-- Card 3: Plan Validity --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Plan Validity</span>
                <span class="text-[13px] font-bold font-mono text-indigo-700 leading-tight block truncate">
                    {{ $exp ? $exp->format('d M Y') : 'Lifetime' }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-indigo-200 bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-calendar-check"></i>
            </div>
        </div>

        {{-- Card 4: Outstanding Due --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Outstanding Due</span>
                <span class="text-[13px] font-bold font-mono {{ $totalOutstanding > 0 ? 'text-rose-600' : 'text-slate-800' }} leading-tight block truncate">
                    @currency($totalOutstanding)
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border {{ $totalOutstanding > 0 ? 'border-rose-200 bg-rose-50 text-rose-600' : 'border-slate-200 bg-slate-50 text-slate-600' }} flex items-center justify-center flex-shrink-0">
                <i class="fas fa-clock"></i>
            </div>
        </div>

        {{-- Card 5: Total Settled --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Total Settled</span>
                <span class="text-[13px] font-bold font-mono text-emerald-600 leading-tight block truncate">
                    @currency($totalPaid)
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-circle-check"></i>
            </div>
        </div>

        {{-- Card 6: Tenant Wallet --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Tenant Wallet</span>
                <span class="text-[13px] font-bold font-mono text-purple-700 leading-tight block truncate">
                    @currency($tenant->wallet_balance ?? 0)
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-purple-200 bg-purple-50 text-purple-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-wallet"></i>
            </div>
        </div>
    </div>

    {{-- 2.1 PENDING INVOICE ALERT (If outstanding invoice exists) --}}
    @if($currentInvoice)
        <div class="p-3 rounded-xl bg-amber-50/90 border border-amber-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 shadow-2xs">
            <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center text-xs flex-shrink-0 border border-amber-200">
                    <i class="fas fa-triangle-exclamation"></i>
                </div>
                <div>
                    <span class="font-bold text-amber-900 block text-xs">Unpaid Subscription Invoice #{{ $currentInvoice->invoice_no }}</span>
                    <span class="text-[11px] text-amber-800 font-normal">
                        Due Date: <span class="font-mono font-medium">{{ $currentInvoice->due_date ? \Carbon\Carbon::parse($currentInvoice->due_date)->format('d M Y') : 'Immediate' }}</span> • 
                        Amount: <span class="font-mono font-bold">@currency($currentInvoice->due_amount ?: $currentInvoice->amount)</span>
                    </span>
                </div>
            </div>
            <div>
                <a href="{{ route('tenant.billing.invoice.show', $currentInvoice) }}" 
                   class="h-[32px] px-3.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white font-semibold text-xs shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                    <span>Pay Online</span>
                    <i class="fas fa-arrow-right text-[10px]"></i>
                </a>
            </div>
        </div>
    @endif

    {{-- 3. ISP RESOURCE QUOTAS & REAL-TIME CAPACITY --}}
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs space-y-3">
        <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
            <div class="flex items-center gap-2">
                <div class="w-6 h-6 rounded-md bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center text-xs flex-shrink-0">
                    <i class="fas fa-sliders"></i>
                </div>
                <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">ISP Resource Quota &amp; Live Utilization</h3>
            </div>
            <span class="text-[11px] text-slate-500 font-mono">
                Subscription Plan: <strong class="text-blue-700">{{ $plan->name ?? 'Standard' }}</strong>
            </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            {{-- Quota 1: Customers --}}
            @php
                $custLimit = (int)($plan->customer_limit ?? 500);
                $custPct = $custLimit > 0 ? min(100, round(($customersCount / $custLimit) * 100, 1)) : 0;
            @endphp
            <div class="p-3 rounded-lg border border-slate-200 bg-slate-50/50 space-y-2">
                <div class="flex items-center justify-between text-xs">
                    <span class="font-semibold text-slate-700 flex items-center gap-1.5">
                        <i class="fas fa-users text-blue-600 text-[11px]"></i>
                        <span>ISP Customers</span>
                    </span>
                    <span class="font-mono font-bold text-slate-900 text-[11px]">{{ number_format($customersCount) }} / {{ $custLimit > 0 ? number_format($custLimit) : 'Unlimited' }}</span>
                </div>
                <div class="w-full bg-slate-200 rounded-full h-1.5 overflow-hidden">
                    <div class="bg-blue-600 h-1.5 rounded-full transition-all duration-300" style="width: {{ $custPct }}%"></div>
                </div>
                <div class="flex items-center justify-between text-[10px] text-slate-500">
                    <span>{{ number_format($activeCustomersCount) }} Active Customers</span>
                    <span class="font-mono font-medium">{{ $custPct }}% Used</span>
                </div>
            </div>

            {{-- Quota 2: Resellers --}}
            @php
                $resLimit = (int)($plan->reseller_limit ?? 10);
                $resPct = $resLimit > 0 ? min(100, round(($resellersCount / $resLimit) * 100, 1)) : 0;
            @endphp
            <div class="p-3 rounded-lg border border-slate-200 bg-slate-50/50 space-y-2">
                <div class="flex items-center justify-between text-xs">
                    <span class="font-semibold text-slate-700 flex items-center gap-1.5">
                        <i class="fas fa-handshake text-purple-600 text-[11px]"></i>
                        <span>Sub-ISPs / Resellers</span>
                    </span>
                    <span class="font-mono font-bold text-slate-900 text-[11px]">{{ number_format($resellersCount) }} / {{ $resLimit > 0 ? number_format($resLimit) : 'Unlimited' }}</span>
                </div>
                <div class="w-full bg-slate-200 rounded-full h-1.5 overflow-hidden">
                    <div class="bg-purple-600 h-1.5 rounded-full transition-all duration-300" style="width: {{ $resPct }}%"></div>
                </div>
                <div class="flex items-center justify-between text-[10px] text-slate-500">
                    <span>Franchise Network</span>
                    <span class="font-mono font-medium">{{ $resPct }}% Used</span>
                </div>
            </div>

            {{-- Quota 3: MikroTik Routers --}}
            @php
                $mkLimit = (int)($plan->mikrotik_limit ?? 5);
                $mkPct = $mkLimit > 0 ? min(100, round(($mikrotikCount / $mkLimit) * 100, 1)) : 0;
            @endphp
            <div class="p-3 rounded-lg border border-slate-200 bg-slate-50/50 space-y-2">
                <div class="flex items-center justify-between text-xs">
                    <span class="font-semibold text-slate-700 flex items-center gap-1.5">
                        <i class="fas fa-network-wired text-indigo-600 text-[11px]"></i>
                        <span>MikroTik Routers</span>
                    </span>
                    <span class="font-mono font-bold text-slate-900 text-[11px]">{{ number_format($mikrotikCount) }} / {{ $mkLimit > 0 ? number_format($mkLimit) : 'Unlimited' }}</span>
                </div>
                <div class="w-full bg-slate-200 rounded-full h-1.5 overflow-hidden">
                    <div class="bg-indigo-600 h-1.5 rounded-full transition-all duration-300" style="width: {{ $mkPct }}%"></div>
                </div>
                <div class="flex items-center justify-between text-[10px] text-slate-500">
                    <span>Gateways &amp; BRAS</span>
                    <span class="font-mono font-medium">{{ $mkPct }}% Used</span>
                </div>
            </div>

            {{-- Quota 4: OLT Devices --}}
            @php
                $oltLimit = (int)($plan->olt_limit ?? 5);
                $oltPct = $oltLimit > 0 ? min(100, round(($oltCount / $oltLimit) * 100, 1)) : 0;
            @endphp
            <div class="p-3 rounded-lg border border-slate-200 bg-slate-50/50 space-y-2">
                <div class="flex items-center justify-between text-xs">
                    <span class="font-semibold text-slate-700 flex items-center gap-1.5">
                        <i class="fas fa-server text-emerald-600 text-[11px]"></i>
                        <span>OLT Devices (GPON/EPON)</span>
                    </span>
                    <span class="font-mono font-bold text-slate-900 text-[11px]">{{ number_format($oltCount) }} / {{ $oltLimit > 0 ? number_format($oltLimit) : 'Unlimited' }}</span>
                </div>
                <div class="w-full bg-slate-200 rounded-full h-1.5 overflow-hidden">
                    <div class="bg-emerald-600 h-1.5 rounded-full transition-all duration-300" style="width: {{ $oltPct }}%"></div>
                </div>
                <div class="flex items-center justify-between text-[10px] text-slate-500">
                    <span>Optical Line Terminals</span>
                    <span class="font-mono font-medium">{{ $oltPct }}% Used</span>
                </div>
            </div>
        </div>

        {{-- Operational Telemetry & Features Badge Bar --}}
        <div class="pt-2 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3 text-xs">
            <div class="flex items-center gap-4 flex-wrap">
                <span class="flex items-center gap-1.5 text-slate-700 font-medium">
                    <i class="fas fa-user-tie text-blue-500 text-[11px]"></i>
                    <span>Team &amp; Staff: <strong class="font-mono text-slate-900">{{ number_format($staffCount) }}</strong></span>
                </span>
                <span class="text-slate-300">•</span>
                <span class="flex items-center gap-1.5 text-slate-700 font-medium">
                    <i class="fas fa-box text-purple-500 text-[11px]"></i>
                    <span>Internet Packages: <strong class="font-mono text-slate-900">{{ number_format($packagesCount) }}</strong></span>
                </span>
                <span class="text-slate-300">•</span>
                <span class="flex items-center gap-1.5 text-slate-700 font-medium">
                    <i class="fas fa-comment-sms text-emerald-500 text-[11px]"></i>
                    <span>Total SMS Dispatched: <strong class="font-mono text-slate-900">{{ number_format($tenant->total_sms_count ?? 0) }}</strong></span>
                </span>
            </div>

            <div class="flex items-center gap-1.5 flex-wrap">
                @if($plan && $plan->allow_radius)
                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <i class="fas fa-check text-[8px] mr-0.5"></i> RADIUS
                    </span>
                @endif
                @if($plan && $plan->allow_wireguard)
                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                        <i class="fas fa-check text-[8px] mr-0.5"></i> WireGuard
                    </span>
                @endif
                @if($plan && $plan->allow_snmp_monitoring)
                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                        <i class="fas fa-check text-[8px] mr-0.5"></i> SNMP Monitoring
                    </span>
                @endif
                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                    <i class="fas fa-bolt text-[8px] mr-0.5 text-amber-500"></i> API Access
                </span>
            </div>
        </div>
    </div>

    {{-- 4. RECENT INVOICES & RECENT PAYMENT TRANSACTIONS TABLES (AGENTS.md Rule 2.D) --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
        
        {{-- Table A: Recent Subscription Invoices --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden flex flex-col">
            <div class="p-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-md bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                    <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Recent Subscription Invoices</h3>
                </div>
                <a href="{{ route('tenant.billing.invoices') }}" class="text-[11px] font-semibold text-cyan-600 hover:text-cyan-800 flex items-center gap-1">
                    <span>View All</span>
                    <i class="fas fa-arrow-right text-[9px]"></i>
                </a>
            </div>
            
            <div class="overflow-x-auto flex-1">
                <table class="saas-table">
                    <thead>
                        <tr>
                            <th class="w-8 text-center font-semibold">#</th>
                            <th class="text-center font-semibold">Invoice No</th>
                            <th class="text-center font-semibold">Plan</th>
                            <th class="text-center font-semibold">Amount</th>
                            <th class="text-center font-semibold">Status</th>
                            <th class="w-16 text-center font-semibold no-sort">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentInvoices as $idx => $inv)
                            <tr>
                                <td class="text-center font-mono text-slate-400 text-xs">{{ $idx + 1 }}</td>
                                <td class="text-center font-mono font-semibold text-slate-800 text-xs">
                                    <a href="{{ route('tenant.billing.invoice.show', $inv) }}" class="hover:text-cyan-600 hover:underline">
                                        {{ $inv->invoice_no }}
                                    </a>
                                </td>
                                <td class="text-left text-slate-700 text-xs truncate max-w-[120px]">
                                    {{ $inv->plan->name ?? 'Carrier Plan' }}
                                </td>
                                <td class="text-right font-mono font-bold text-slate-800 text-xs whitespace-nowrap">
                                    @currency($inv->amount)
                                </td>
                                <td class="text-center">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold border {{ $inv->status === 'paid' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-amber-200 bg-amber-50 text-amber-700' }}">
                                        {{ ucfirst($inv->status) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('tenant.billing.invoice.show', $inv) }}" 
                                       class="h-[26px] px-2.5 rounded-md text-[10.5px] font-semibold border transition inline-flex items-center gap-1 {{ $inv->status === 'paid' ? 'bg-slate-50 hover:bg-slate-100 text-slate-700 border-slate-200' : 'bg-amber-600 hover:bg-amber-700 text-white border-amber-600' }}">
                                        <span>{{ $inv->status === 'paid' ? 'View' : 'Pay' }}</span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-6 text-slate-400 text-xs">
                                    No subscription invoices generated yet.
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
                    <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Recent Settlement Transactions</h3>
                </div>
                <a href="{{ route('tenant.billing.payments') }}" class="text-[11px] font-semibold text-cyan-600 hover:text-cyan-800 flex items-center gap-1">
                    <span>View All</span>
                    <i class="fas fa-arrow-right text-[9px]"></i>
                </a>
            </div>

            <div class="overflow-x-auto flex-1">
                <table class="saas-table">
                    <thead>
                        <tr>
                            <th class="w-8 text-center font-semibold">#</th>
                            <th class="text-center font-semibold">Transaction Ref</th>
                            <th class="text-center font-semibold">Gateway</th>
                            <th class="text-center font-semibold">Amount</th>
                            <th class="text-center font-semibold">Status</th>
                            <th class="text-center font-semibold">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentPayments as $idx => $pmt)
                            <tr>
                                <td class="text-center font-mono text-slate-400 text-xs">{{ $idx + 1 }}</td>
                                <td class="text-center font-mono text-slate-800 text-xs truncate max-w-[120px]" title="{{ $pmt->transaction_reference }}">
                                    {{ $pmt->transaction_reference ?: 'N/A' }}
                                </td>
                                <td class="text-center">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold uppercase font-mono border border-slate-200 bg-slate-50 text-slate-700">
                                        {{ $pmt->payment_method ?: 'Online' }}
                                    </span>
                                </td>
                                <td class="text-right font-mono font-bold text-emerald-700 text-xs whitespace-nowrap">
                                    @currency($pmt->amount)
                                </td>
                                <td class="text-center">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold border {{ $pmt->status === 'success' || $pmt->status === 'completed' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-rose-200 bg-rose-50 text-rose-700' }}">
                                        {{ ucfirst($pmt->status) }}
                                    </span>
                                </td>
                                <td class="text-center font-mono text-slate-500 text-[11px] whitespace-nowrap">
                                    {{ $pmt->created_at ? $pmt->created_at->format('d M Y') : 'N/A' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-6 text-slate-400 text-xs">
                                    No payment settlements recorded yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    {{-- 5. TENANT CORPORATE IDENTITY & OPERATIONS PROFILE (4 Information Cards) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2">
        {{-- Info Card 1: Domain / Host --}}
        <div class="p-2.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Domain / Portal Host</span>
                <span class="text-[12px] font-bold font-mono text-blue-700 leading-tight block truncate" title="{{ $tenant->domain ?: ($tenant->slug . '.somitysoft.com') }}">
                    {{ $tenant->domain ?: ($tenant->slug . '.somitysoft.com') }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-blue-200 bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-globe"></i>
            </div>
        </div>

        {{-- Info Card 2: Contact Phone --}}
        <div class="p-2.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Contact Hotline</span>
                <span class="text-[12px] font-bold font-mono text-slate-800 leading-tight block truncate">
                    {{ $tenant->phone ?: 'Not Configured' }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-phone"></i>
            </div>
        </div>

        {{-- Info Card 3: Email Address --}}
        <div class="p-2.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Official Email</span>
                <span class="text-[12px] font-bold font-mono text-slate-800 leading-tight block truncate" title="{{ $tenant->email }}">
                    {{ $tenant->email ?: 'Not Configured' }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-purple-200 bg-purple-50 text-purple-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-envelope"></i>
            </div>
        </div>

        {{-- Info Card 4: Office Location --}}
        <div class="p-2.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Registered Office</span>
                <span class="text-[12px] font-bold text-slate-800 leading-tight block truncate" title="{{ $tenant->address }}">
                    {{ $tenant->address ?: 'Not Configured' }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-amber-200 bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-map-marker-alt"></i>
            </div>
        </div>
    </div>

    {{-- 6. UPGRADE PLAN / SUPPORT NATURAL MODAL (AGENTS.md Rule 3) --}}
    <div x-show="showUpgradeModal" 
         x-cloak 
         class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto">
        <div @click.away="showUpgradeModal = false" 
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden my-6 flex flex-col animate-in fade-in zoom-in-95 duration-150">
            
            {{-- Modal Header --}}
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between flex-shrink-0">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-arrow-up-right-dots"></i>
                    </div>
                    <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Upgrade Subscription or Expand Quotas</h3>
                </div>
                <button type="button" @click="showUpgradeModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            {{-- Modal Body --}}
            <div class="p-5 space-y-3.5 text-xs">
                <div class="p-3 bg-cyan-50/60 rounded-lg border border-cyan-100 text-cyan-950 space-y-1">
                    <span class="font-bold text-xs block">Need more Customer Slabs or Custom Dedicated Gateways?</span>
                    <p class="text-[11px] leading-relaxed text-cyan-900">
                        Your account is currently running on the <strong class="font-mono text-cyan-950">{{ $plan->name ?? 'Standard Plan' }}</strong>. You can expand your subscriber limits, additional MikroTik/OLT licenses, or dedicated SMS routes at any time.
                    </p>
                </div>

                <div class="space-y-2">
                    <label class="block text-[10.5px] font-bold uppercase text-slate-600">Contact Support &amp; Billing Desk</label>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div class="p-2.5 rounded-lg border border-slate-200 bg-slate-50">
                            <span class="text-[10px] text-slate-500 uppercase font-bold block">Hotline Support</span>
                            <span class="font-mono font-bold text-slate-800 text-xs">+880 1819-000000</span>
                        </div>
                        <div class="p-2.5 rounded-lg border border-slate-200 bg-slate-50">
                            <span class="text-[10px] text-slate-500 uppercase font-bold block">Billing Inquiries</span>
                            <span class="font-mono font-bold text-slate-800 text-xs">billing@somitysoft.com</span>
                        </div>
                    </div>
                </div>

                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 text-slate-600 text-[11px] space-y-1">
                    <div class="flex items-center justify-between">
                        <span>Current Customer Usage:</span>
                        <span class="font-mono font-bold text-slate-800">{{ number_format($customersCount) }} / {{ $custLimit > 0 ? number_format($custLimit) : 'Unlimited' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Current Active Plan Expiry:</span>
                        <span class="font-mono font-bold text-slate-800">{{ $exp ? $exp->format('d M Y') : 'Lifetime' }}</span>
                    </div>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between flex-shrink-0">
                <a href="{{ route('tenant.tickets.create') }}" class="h-[34px] px-3.5 bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                    <i class="fas fa-headset text-xs"></i>
                    <span>Open Upgrade Ticket</span>
                </a>
                <button type="button" @click="showUpgradeModal = false" class="h-[34px] border border-slate-300 hover:bg-slate-100 text-slate-700 font-semibold text-xs px-4 rounded-lg transition cursor-pointer">
                    Close
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
        showUpgradeModal: false,
    };
}
</script>
@endpush
