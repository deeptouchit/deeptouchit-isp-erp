@extends('tenant.layouts.app')

@section('title', 'Tenant Overview & Plan Details - ' . ($tenant->company_name ?? $tenant->name ?? 'ISP Portal'))

{{-- 1. Page Specific Stylesheets --}}
@push('styles')
<style>
    .metric-card-hover:hover {
        transform: translateY(-2px);
    }
</style>
@endpush

{{-- 2. Main Body Content --}}
@section('content')
<div class="space-y-4">
    
    <!-- 1. Top Header & Action Bar -->
    <div class="bg-white p-3.5 sm:p-4 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            
            <!-- Left: Company Identity -->
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white flex items-center justify-center text-base font-bold shadow-xs flex-shrink-0">
                    {{ strtoupper(substr($tenant->company_name ?? $tenant->name ?? 'ISP', 0, 2)) }}
                </div>
                <div class="space-y-0.5">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h2 class="text-sm sm:text-base font-bold text-slate-900 tracking-tight">{{ $tenant->company_name ?? $tenant->name }}</h2>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $tenant->status === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $tenant->status === 'active' ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                            <span>{{ ucfirst($tenant->status ?? 'active') }}</span>
                        </span>
                        <span class="px-2 py-0.5 rounded-md text-[10px] font-mono font-medium bg-slate-100 text-slate-700 border border-slate-200">
                            Tenant #{{ $tenant->id }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Right: Action Buttons -->
            <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap flex-shrink-0">
                @if($currentInvoice)
                    <a href="{{ route('tenant.billing.invoice.show', $currentInvoice) }}" 
                       class="px-3 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white font-semibold text-xs shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                        <i class="fas fa-credit-card text-[11px]"></i>
                        <span>Pay Due (@currency($currentInvoice->due_amount ?: $currentInvoice->amount))</span>
                    </a>
                @endif

                <a href="{{ route('tenant.billing.invoices') }}" 
                   class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition inline-flex items-center gap-1.5 shadow-2xs">
                    <i class="fas fa-file-invoice text-blue-600 text-[11px]"></i>
                    <span>Invoices</span>
                </a>

                <a href="{{ route('tenant.billing.payments') }}" 
                   class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition inline-flex items-center gap-1.5 shadow-2xs">
                    <i class="fas fa-receipt text-emerald-600 text-[11px]"></i>
                    <span>Payment History</span>
                </a>
            </div>

        </div>
    </div>

    <!-- 2. Tenant Profile & Corporate Information Widgets (4 Separate Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5">
        
        <!-- Info Card 1: Domain / Host -->
        <div class="p-3 rounded-xl bg-white border border-slate-200 shadow-xs metric-card-hover transition flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-bold text-slate-500 uppercase tracking-wider block">Domain / Host</span>
                <span class="text-[12px] font-bold text-blue-700 font-mono block truncate" title="{{ $tenant->domain ?: ($tenant->slug . '.somitysoft.com') }}">
                    {{ $tenant->domain ?: ($tenant->slug . '.somitysoft.com') }}
                </span>
            </div>
            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-xs border border-blue-100 flex-shrink-0">
                <i class="fas fa-globe"></i>
            </div>
        </div>

        <!-- Info Card 2: Contact Phone -->
        <div class="p-3 rounded-xl bg-white border border-slate-200 shadow-xs metric-card-hover transition flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-bold text-slate-500 uppercase tracking-wider block">Contact Phone</span>
                <span class="text-[12px] font-bold text-slate-800 font-mono block truncate">
                    {{ $tenant->phone ?: 'Not Configured' }}
                </span>
            </div>
            <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs border border-emerald-100 flex-shrink-0">
                <i class="fas fa-phone"></i>
            </div>
        </div>

        <!-- Info Card 3: Email Address -->
        <div class="p-3 rounded-xl bg-white border border-slate-200 shadow-xs metric-card-hover transition flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-bold text-slate-500 uppercase tracking-wider block">Official Email</span>
                <span class="text-[12px] font-bold text-slate-800 font-mono block truncate" title="{{ $tenant->email }}">
                    {{ $tenant->email ?: 'Not Configured' }}
                </span>
            </div>
            <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center text-xs border border-purple-100 flex-shrink-0">
                <i class="fas fa-envelope"></i>
            </div>
        </div>

        <!-- Info Card 4: Office Location -->
        <div class="p-3 rounded-xl bg-white border border-slate-200 shadow-xs metric-card-hover transition flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-bold text-slate-500 uppercase tracking-wider block">Office Location</span>
                <span class="text-[12px] font-bold text-slate-800 block truncate" title="{{ $tenant->address }}">
                    {{ $tenant->address ?: 'Not Configured' }}
                </span>
            </div>
            <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center text-xs border border-amber-100 flex-shrink-0">
                <i class="fas fa-map-marker-alt"></i>
            </div>
        </div>

    </div>

    <!-- 3. Ultra-Compact KPI Summary Strip (6 Financial & Subscription Metrics) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2.5">
        
        <!-- Metric 1: Current Plan -->
        <div class="px-2.5 py-2 rounded-xl bg-white border border-slate-200 shadow-xs metric-card-hover transition flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-bold text-slate-500 uppercase tracking-wider block truncate">Active Plan</span>
                <span class="text-[13px] font-bold text-slate-900 tracking-tight font-mono leading-tight block truncate">{{ $plan->name ?? 'Carrier Plan' }}</span>
            </div>
            <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-xs border border-blue-100 flex-shrink-0">
                <i class="fas fa-box-open"></i>
            </div>
        </div>

        <!-- Metric 2: License Status -->
        <div class="px-2.5 py-2 rounded-xl bg-white border border-slate-200 shadow-xs metric-card-hover transition flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-bold text-slate-500 uppercase tracking-wider block truncate">License Status</span>
                <span class="text-[13px] font-bold {{ $tenant->status === 'active' ? 'text-emerald-600' : 'text-amber-600' }} tracking-tight leading-tight block">
                    {{ ucfirst($tenant->status ?? 'active') }}
                </span>
            </div>
            <div class="w-7 h-7 rounded-lg {{ $tenant->status === 'active' ? 'bg-emerald-50 text-emerald-600 border-emerald-100' : 'bg-amber-50 text-amber-600 border-amber-100' }} flex items-center justify-center text-xs border flex-shrink-0">
                <i class="fas {{ $tenant->status === 'active' ? 'fa-shield-alt' : 'fa-exclamation-triangle' }}"></i>
            </div>
        </div>

        <!-- Metric 3: Validity Period -->
        @php
            $exp = $tenant->subscription_expires_at ? \Carbon\Carbon::parse($tenant->subscription_expires_at) : null;
            $daysLeft = $exp ? ceil(now()->floatDiffInDays($exp, false)) : null;
        @endphp
        <div class="px-2.5 py-2 rounded-xl bg-white border border-slate-200 shadow-xs metric-card-hover transition flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-bold text-slate-500 uppercase tracking-wider block truncate">Plan Validity</span>
                <span class="text-[13px] font-bold text-slate-900 tracking-tight font-mono leading-tight block truncate">
                    {{ $exp ? $exp->format('d M Y') : 'Lifetime' }}
                </span>
            </div>
            <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs border border-indigo-100 flex-shrink-0">
                <i class="fas fa-calendar-check"></i>
            </div>
        </div>

        <!-- Metric 4: Outstanding Due -->
        <div class="px-2.5 py-2 rounded-xl bg-white border border-slate-200 shadow-xs metric-card-hover transition flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-bold text-amber-700 uppercase tracking-wider block truncate">Outstanding Due</span>
                <span class="text-[13px] font-bold text-amber-600 tracking-tight font-mono leading-tight block">@currency($totalOutstanding)</span>
            </div>
            <div class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center text-xs border border-amber-100 flex-shrink-0">
                <i class="fas fa-clock"></i>
            </div>
        </div>

        <!-- Metric 5: Total Settled -->
        <div class="px-2.5 py-2 rounded-xl bg-white border border-slate-200 shadow-xs metric-card-hover transition flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-bold text-emerald-700 uppercase tracking-wider block truncate">Total Settled</span>
                <span class="text-[13px] font-bold text-emerald-600 tracking-tight font-mono leading-tight block">@currency($totalPaid)</span>
            </div>
            <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs border border-emerald-100 flex-shrink-0">
                <i class="fas fa-check-circle"></i>
            </div>
        </div>

        <!-- Metric 6: Wallet Balance -->
        <div class="px-2.5 py-2 rounded-xl bg-white border border-slate-200 shadow-xs metric-card-hover transition flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-bold text-purple-700 uppercase tracking-wider block truncate">Tenant Wallet</span>
                <span class="text-[13px] font-bold text-purple-700 tracking-tight font-mono leading-tight block">@currency($tenant->wallet_balance ?? 0)</span>
            </div>
            <div class="w-7 h-7 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center text-xs border border-purple-100 flex-shrink-0">
                <i class="fas fa-wallet"></i>
            </div>
        </div>
    </div>

    <!-- 4. Current Pending Invoice Alert Banner (if any) -->
    @if($currentInvoice)
        <div class="p-3.5 rounded-xl bg-amber-50/90 border border-amber-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs shadow-xs">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center text-xs flex-shrink-0">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div>
                    <span class="font-bold text-amber-900 block text-xs">Pending Invoice #{{ $currentInvoice->invoice_no }}</span>
                    <span class="text-[11px] text-amber-800 font-normal">
                        Due Date: <span class="font-mono font-medium">{{ $currentInvoice->due_date ? \Carbon\Carbon::parse($currentInvoice->due_date)->format('d M Y') : 'Immediate' }}</span> • 
                        Amount: <span class="font-mono font-bold">৳{{ number_format($currentInvoice->due_amount ?: $currentInvoice->amount, 2) }}</span>
                    </span>
                </div>
            </div>
            <div>
                <a href="{{ route('tenant.billing.invoice.show', $currentInvoice) }}" 
                   class="px-3.5 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white font-semibold text-xs shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                    <span>Pay Online</span>
                    <i class="fas fa-arrow-right text-[10px]"></i>
                </a>
            </div>
        </div>
    @endif

    <!-- 5. ISP Tenant Resource Quotas & Live Utilization -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs space-y-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="fas fa-sliders text-blue-600 text-xs"></i>
                <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">ISP Resource Quota &amp; Live Utilization</h3>
            </div>
            <span class="text-[11px] text-slate-500 font-mono">
                Subscription Plan: <span class="font-bold text-blue-600">{{ $plan->name ?? 'Standard' }}</span>
            </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            
            <!-- Quota 1: Customers -->
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
                    <span class="font-mono font-bold text-slate-900 text-[11px]">{{ $customersCount }} / {{ $custLimit > 0 ? number_format($custLimit) : 'Unlimited' }}</span>
                </div>
                <div class="w-full bg-slate-200 rounded-full h-1.5 overflow-hidden">
                    <div class="bg-blue-600 h-1.5 rounded-full transition-all duration-300" style="width: {{ $custPct }}%"></div>
                </div>
                <div class="flex items-center justify-between text-[10px] text-slate-500">
                    <span>{{ $activeCustomersCount }} Active Customers</span>
                    <span class="font-mono font-medium">{{ $custPct }}% Used</span>
                </div>
            </div>

            <!-- Quota 2: Resellers -->
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
                    <span class="font-mono font-bold text-slate-900 text-[11px]">{{ $resellersCount }} / {{ $resLimit > 0 ? number_format($resLimit) : 'Unlimited' }}</span>
                </div>
                <div class="w-full bg-slate-200 rounded-full h-1.5 overflow-hidden">
                    <div class="bg-purple-600 h-1.5 rounded-full transition-all duration-300" style="width: {{ $resPct }}%"></div>
                </div>
                <div class="flex items-center justify-between text-[10px] text-slate-500">
                    <span>Franchise Network</span>
                    <span class="font-mono font-medium">{{ $resPct }}% Used</span>
                </div>
            </div>

            <!-- Quota 3: MikroTik Routers -->
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
                    <span class="font-mono font-bold text-slate-900 text-[11px]">{{ $mikrotikCount }} / {{ $mkLimit > 0 ? number_format($mkLimit) : 'Unlimited' }}</span>
                </div>
                <div class="w-full bg-slate-200 rounded-full h-1.5 overflow-hidden">
                    <div class="bg-indigo-600 h-1.5 rounded-full transition-all duration-300" style="width: {{ $mkPct }}%"></div>
                </div>
                <div class="flex items-center justify-between text-[10px] text-slate-500">
                    <span>Gateways &amp; BRAS</span>
                    <span class="font-mono font-medium">{{ $mkPct }}% Used</span>
                </div>
            </div>

            <!-- Quota 4: OLT Devices -->
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
                    <span class="font-mono font-bold text-slate-900 text-[11px]">{{ $oltCount }} / {{ $oltLimit > 0 ? number_format($oltLimit) : 'Unlimited' }}</span>
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

        <!-- Additional Operational Metrics (Staff, Packages, Features) -->
        <div class="pt-2 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3 text-xs">
            <div class="flex items-center gap-4 flex-wrap">
                <span class="flex items-center gap-1.5 text-slate-700 font-medium">
                    <i class="fas fa-user-tie text-blue-500 text-[11px]"></i>
                    <span>Team &amp; Staff: <strong class="font-mono text-slate-900">{{ $staffCount }}</strong></span>
                </span>
                <span class="text-slate-300">•</span>
                <span class="flex items-center gap-1.5 text-slate-700 font-medium">
                    <i class="fas fa-box text-purple-500 text-[11px]"></i>
                    <span>Internet Packages: <strong class="font-mono text-slate-900">{{ $packagesCount }}</strong></span>
                </span>
                <span class="text-slate-300">•</span>
                <span class="flex items-center gap-1.5 text-slate-700 font-medium">
                    <i class="fas fa-comment-sms text-emerald-500 text-[11px]"></i>
                    <span>Total SMS Logs: <strong class="font-mono text-slate-900">{{ $tenant->total_sms_count }}</strong></span>
                </span>
            </div>

            <!-- Feature Pills -->
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

</div>
@endsection
