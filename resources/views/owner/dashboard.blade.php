@extends('owner.layouts.app')

@section('page-title', 'Platform Overview')

@section('content')
<div class="space-y-2.5 max-w-7xl mx-auto">
    
    <!-- Top Ultra-Compact Header Strip -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-white px-3 py-2 rounded-lg border border-slate-200/90 shadow-2xs">
        <div class="flex items-center gap-2">
            <div class="w-6 h-6 rounded-md bg-blue-600 text-white flex items-center justify-center font-bold text-[10px] shadow-2xs flex-shrink-0">
                <i class="fas fa-layer-group"></i>
            </div>
            <div class="flex items-center gap-2">
                <h2 class="text-xs font-bold text-slate-900 tracking-tight">Executive Dashboard</h2>
                <span class="inline-flex items-center gap-1 px-1.5 py-0.2 rounded text-[9px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    <span class="w-1 h-1 rounded-full bg-emerald-500"></span>
                    Live
                </span>
            </div>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <!-- SMS Live Balance Pill -->
            <a href="{{ route('owner.sms-gateways.index') }}" class="px-2 py-1 rounded bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-[10px] font-semibold border border-indigo-200/80 transition flex items-center gap-1.5">
                <i class="fas fa-comment-dots text-[9px] text-indigo-500"></i>
                <span>SMS Balance: <span class="font-bold font-mono">{{ $smsBalanceInfo['formatted'] ?? 'N/A' }}</span></span>
            </a>

            <form action="{{ route('owner.billing.run-engine') }}" method="POST" class="inline" onsubmit="return confirm('Execute automated billing engine now?');">
                @csrf
                <button type="submit" class="px-2 py-1 rounded bg-slate-50 hover:bg-slate-100 text-slate-700 text-[10.5px] font-semibold border border-slate-200 transition flex items-center gap-1">
                    <i class="fas fa-bolt text-amber-500 text-[9px]"></i>
                    <span>Run Billing</span>
                </button>
            </form>

            <a href="{{ route('owner.tenants.create') }}" class="px-2.5 py-1 rounded bg-blue-600 hover:bg-blue-700 text-white text-[10.5px] font-semibold shadow-2xs transition flex items-center gap-1">
                <i class="fas fa-plus text-[9px]"></i>
                <span>New Tenant</span>
            </a>
        </div>
    </div>

    <!-- 4 Ultra-Compact Metric Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2">
        
        <!-- Estimated MRR -->
        <div class="p-2.5 rounded-lg bg-white border border-slate-200/90 shadow-2xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9.5px] font-bold text-slate-500 uppercase tracking-wider block">Estimated MRR</span>
                <h3 class="text-sm font-bold text-slate-900 font-mono tracking-tight mt-0.5">@currency($mrr)</h3>
                <span class="text-[9.5px] text-emerald-600 font-medium block truncate">Total: @currency($totalRevenue)</span>
            </div>
            <div class="w-7 h-7 rounded-md bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs border border-emerald-100 flex-shrink-0">
                <i class="fas fa-coins text-[11px]"></i>
            </div>
        </div>

        <!-- ISP Tenants -->
        <div class="p-2.5 rounded-lg bg-white border border-slate-200/90 shadow-2xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9.5px] font-bold text-slate-500 uppercase tracking-wider block">ISP Tenants</span>
                <div class="flex items-baseline gap-1 mt-0.5">
                    <h3 class="text-sm font-bold text-slate-900 font-mono">{{ $totalTenants }}</h3>
                    <span class="text-[9.5px] text-slate-400">Total</span>
                </div>
                <span class="text-[9.5px] text-slate-500 font-medium block truncate">
                    <span class="text-emerald-600 font-semibold">{{ $activeTenants }} Active</span>
                    @if($suspendedTenants > 0)
                        • <span class="text-rose-600 font-semibold">{{ $suspendedTenants }} Off</span>
                    @endif
                </span>
            </div>
            <div class="w-7 h-7 rounded-md bg-blue-50 text-blue-600 flex items-center justify-center text-xs border border-blue-100 flex-shrink-0">
                <i class="fas fa-building text-[11px]"></i>
            </div>
        </div>

        <!-- Unpaid Invoices -->
        <div class="p-2.5 rounded-lg bg-white border border-slate-200/90 shadow-2xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9.5px] font-bold text-slate-500 uppercase tracking-wider block">Receivables</span>
                <h3 class="text-sm font-bold text-slate-900 font-mono tracking-tight mt-0.5">@currency($pendingInvoicesAmount)</h3>
                <span class="text-[9.5px] text-amber-600 font-medium block truncate">{{ $pendingInvoicesCount }} Pending Invoices</span>
            </div>
            <div class="w-7 h-7 rounded-md bg-amber-50 text-amber-600 flex items-center justify-center text-xs border border-amber-100 flex-shrink-0">
                <i class="fas fa-receipt text-[11px]"></i>
            </div>
        </div>

        <!-- Support Desk -->
        <div class="p-2.5 rounded-lg bg-white border border-slate-200/90 shadow-2xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9.5px] font-bold text-slate-500 uppercase tracking-wider block">Support Desk</span>
                <div class="flex items-baseline gap-1 mt-0.5">
                    <h3 class="text-sm font-bold text-slate-900 font-mono">{{ $openTicketsCount }}</h3>
                    <span class="text-[9.5px] text-slate-400">Tickets</span>
                </div>
                <span class="text-[9.5px] font-medium block truncate">
                    @if($urgentTicketsCount > 0)
                        <span class="text-rose-600 font-bold">{{ $urgentTicketsCount }} Urgent</span>
                    @else
                        <span class="text-slate-500">All caught up</span>
                    @endif
                </span>
            </div>
            <div class="w-7 h-7 rounded-md bg-purple-50 text-purple-600 flex items-center justify-center text-xs border border-purple-100 flex-shrink-0">
                <i class="fas fa-headset text-[11px]"></i>
            </div>
        </div>

    </div>

    <!-- Analytics Chart & Platform Health (Compact 2-col) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-2.5 items-stretch">
        
        <!-- Revenue Trend Chart (8 cols) -->
        <div class="lg:col-span-8 bg-white rounded-lg border border-slate-200/90 p-3 shadow-2xs flex flex-col justify-between">
            <div class="flex items-center justify-between border-b border-slate-100 pb-1.5 mb-2">
                <div>
                    <h3 class="font-bold text-slate-900 text-xs tracking-tight">Revenue & Growth Trend</h3>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1 text-[10px] text-slate-600 font-medium">
                        <span class="w-2 h-2 rounded-full bg-blue-600"></span> Revenue
                    </span>
                    <span class="inline-flex items-center gap-1 text-[10px] text-slate-600 font-medium">
                        <span class="w-2 h-2 rounded-full bg-slate-300"></span> Tenants
                    </span>
                </div>
            </div>

            <div class="h-36 w-full relative">
                <canvas id="revenueTrendChart"></canvas>
            </div>
        </div>

        <!-- Platform Gateways & Engine Status (4 cols) -->
        <div class="lg:col-span-4 bg-white rounded-lg border border-slate-200/90 p-3 shadow-2xs flex flex-col justify-between space-y-2">
            <div class="border-b border-slate-100 pb-1.5 flex items-center justify-between">
                <h3 class="font-bold text-slate-900 text-xs tracking-tight">System & Gateways</h3>
                <span class="text-[9.5px] text-slate-400 font-mono">Real-time</span>
            </div>

            <div class="space-y-1.5">
                <!-- SMS Gateway Live Balance -->
                <div class="p-1.5 px-2 rounded-md bg-indigo-50/70 border border-indigo-200/80 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-comment-dots text-indigo-600 text-[10px]"></i>
                        <div>
                            <span class="font-semibold text-slate-800 text-[11px] block leading-tight">SMS Gateway</span>
                            <span class="text-[9px] text-slate-500">{{ $smsBalanceInfo['provider'] ?? 'Provider' }} • {{ $totalSmsSent }} Sent</span>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="font-mono font-bold text-indigo-700 text-xs block leading-tight">{{ $smsBalanceInfo['formatted'] ?? '৳0.00' }}</span>
                        <a href="{{ route('owner.sms-gateways.index') }}" class="text-[9px] font-semibold text-blue-600 hover:underline">Manage</a>
                    </div>
                </div>

                <!-- Automation Engine -->
                <div class="p-1.5 px-2 rounded-md bg-slate-50 border border-slate-200/80 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-bolt text-amber-500 text-[10px]"></i>
                        <span class="font-semibold text-slate-800 text-[11px]">Billing Engine</span>
                    </div>
                    <span class="px-1.5 py-0.2 rounded text-[9px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        Operational
                    </span>
                </div>

                <!-- Payment Gateways -->
                <div class="p-1.5 px-2 rounded-md bg-slate-50 border border-slate-200/80 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-credit-card text-emerald-600 text-[10px]"></i>
                        <span class="font-semibold text-slate-800 text-[11px]">Payment Gateways</span>
                    </div>
                    <a href="{{ route('owner.payment-gateways.index') }}" class="text-[10px] font-semibold text-blue-600 hover:underline">
                        Manage
                    </a>
                </div>
            </div>

            <div class="pt-1.5 border-t border-slate-100 flex items-center justify-between text-[10px]">
                <span class="text-slate-500">Platform Settings</span>
                <a href="{{ route('owner.settings.index') }}" class="font-semibold text-blue-600 hover:text-blue-700">
                    Console Settings &rarr;
                </a>
            </div>
        </div>

    </div>

    <!-- 2-Column Compact Feeds Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-2.5 items-start">
        
        <!-- Left: Recent Tenant Subscriptions (Clean Compact List) -->
        <div class="lg:col-span-8 bg-white rounded-lg border border-slate-200/90 p-3 shadow-2xs space-y-2">
            <div class="flex items-center justify-between border-b border-slate-100 pb-1.5">
                <h3 class="font-bold text-slate-900 text-xs tracking-tight">Active Tenant Subscriptions</h3>
                <a href="{{ route('owner.tenants.index') }}" class="text-[10.5px] font-semibold text-blue-600 hover:text-blue-700 transition flex items-center gap-1">
                    <span>Manage All</span>
                    <i class="fas fa-chevron-right text-[7px]"></i>
                </a>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse ($recentTenants as $tenant)
                    <div class="py-1.5 flex items-center justify-between gap-2 hover:bg-slate-50/60 px-1 rounded transition">
                        <div class="flex items-center gap-2 min-w-0">
                            <div class="w-6 h-6 rounded bg-blue-50 text-blue-700 flex items-center justify-center font-bold text-[10px] border border-blue-100 flex-shrink-0">
                                {{ strtoupper(substr($tenant->name, 0, 1)) }}
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-1.5">
                                    <a href="{{ route('owner.tenants.show', $tenant) }}" class="font-bold text-slate-900 hover:text-blue-600 text-[11.5px] truncate">
                                        {{ $tenant->name }}
                                    </a>
                                    <span class="px-1.5 py-0.2 rounded bg-slate-100 text-slate-600 text-[9px] font-semibold border border-slate-200">
                                        {{ $tenant->plan->name ?? 'Standard' }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-1.5 text-[9.5px] text-slate-400">
                                    <span class="font-mono text-slate-500">{{ $tenant->domain ?: $tenant->slug . '.somitysoft.com' }}</span>
                                    <span>•</span>
                                    <span>Expires: {{ $tenant->subscription_expires_at ? \Carbon\Carbon::parse($tenant->subscription_expires_at)->format('d M, Y') : 'Lifetime' }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-1.5 flex-shrink-0">
                            @if ($tenant->status === 'active')
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.2 rounded-full text-[9px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <span class="w-1 h-1 rounded-full bg-emerald-500"></span>
                                    Active
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.2 rounded-full text-[9px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                    <span class="w-1 h-1 rounded-full bg-rose-500"></span>
                                    Suspended
                                </span>
                            @endif

                            <a href="{{ route('owner.tenants.impersonate', $tenant) }}" 
                               title="1-Click Login as ISP Admin" 
                               class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white transition font-semibold text-[9.5px]">
                                <i class="fas fa-sign-in-alt text-[8px]"></i>
                                <span>Admin</span>
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="py-4 text-center text-slate-400 text-xs">
                        No active tenants found.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Right: Support Desk Queue (Compact Feed) -->
        <div class="lg:col-span-4 bg-white rounded-lg border border-slate-200/90 p-3 shadow-2xs space-y-2">
            <div class="flex items-center justify-between border-b border-slate-100 pb-1.5">
                <h3 class="font-bold text-slate-900 text-xs tracking-tight">Support Desk Queue</h3>
                <a href="{{ route('owner.tickets.index') }}" class="text-[10.5px] font-semibold text-blue-600 hover:text-blue-700 transition">
                    View All
                </a>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse ($recentTickets as $ticket)
                    <div class="py-1.5 space-y-0.5 hover:bg-slate-50/60 px-1 rounded transition">
                        <div class="flex items-center justify-between gap-1.5">
                            <div class="flex items-center gap-1">
                                <span class="font-mono text-[9px] font-bold text-slate-500">#{{ $ticket->ticket_number }}</span>
                                @if($ticket->priority === 'urgent')
                                    <span class="px-1 py-0.2 rounded text-[8.5px] font-bold uppercase bg-rose-50 text-rose-700 border border-rose-200">Urgent</span>
                                @elseif($ticket->priority === 'high')
                                    <span class="px-1 py-0.2 rounded text-[8.5px] font-bold uppercase bg-amber-50 text-amber-700 border border-amber-200">High</span>
                                @endif
                            </div>
                            <span class="text-[9px] text-slate-400">{{ $ticket->created_at->diffForHumans() }}</span>
                        </div>

                        <a href="{{ route('owner.tickets.show', $ticket) }}" class="font-semibold text-slate-900 hover:text-blue-600 text-[11px] block leading-snug truncate">
                            {{ $ticket->subject }}
                        </a>

                        <div class="flex items-center justify-between text-[9.5px] text-slate-500">
                            <span class="truncate max-w-[130px] text-slate-700 font-medium">{{ $ticket->tenant->name ?? 'Tenant' }}</span>
                            <a href="{{ route('owner.tickets.show', $ticket) }}" class="text-blue-600 font-semibold hover:underline flex items-center gap-0.5">
                                <span>Reply</span>
                                <i class="fas fa-arrow-right text-[7px]"></i>
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="py-4 text-center text-slate-400 text-xs">
                        <i class="fas fa-check-circle text-emerald-500 text-base mb-1 block"></i>
                        <span>No open tickets requiring review.</span>
                    </div>
                @endforelse
            </div>
        </div>

</div>
@endsection

@push('scripts')
<!-- Chart.js Script for Revenue & Growth Visuals -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const canvas = document.getElementById('revenueTrendChart');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        
        const labels = @json($chartLabels);
        const revenueData = @json($chartRevenue);
        const tenantsData = @json($chartTenants);

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Revenue (BDT)',
                        data: revenueData,
                        borderColor: '#2563eb',
                        backgroundColor: 'rgba(37, 99, 235, 0.08)',
                        fill: true,
                        tension: 0.35,
                        borderWidth: 1.5,
                        pointRadius: 2,
                        pointBackgroundColor: '#2563eb',
                        yAxisID: 'y'
                    },
                    {
                        label: 'Tenants Count',
                        data: tenantsData,
                        borderColor: '#94a3b8',
                        borderDash: [3, 3],
                        tension: 0.35,
                        borderWidth: 1.2,
                        pointRadius: 1.5,
                        pointBackgroundColor: '#94a3b8',
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleFont: { size: 10, weight: 'bold' },
                        bodyFont: { size: 10 },
                        padding: 6,
                        cornerRadius: 4
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 9 }, color: '#64748b' }
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        grid: { color: '#f1f5f9' },
                        ticks: {
                            font: { size: 9 },
                            color: '#64748b',
                            callback: function(value) {
                                return '৳' + value.toLocaleString();
                            }
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: false,
                        position: 'right',
                        grid: { drawOnChartArea: false }
                    }
                }
            }
        });
    });
</script>
@endpush
