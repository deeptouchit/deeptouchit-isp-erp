@extends('owner.layouts.app')

@section('page-title', 'Revenue & Income Report')

@section('content')
<div class="space-y-3 max-w-7xl mx-auto">
    
    <!-- Top Compact Header Strip -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 bg-white px-3.5 py-2.5 rounded-lg border border-slate-200/90 shadow-2xs">
        <div class="flex items-center gap-2.5">
            <div class="w-7 h-7 rounded-md bg-emerald-600 text-white flex items-center justify-center font-bold text-xs shadow-2xs flex-shrink-0">
                <i class="fas fa-chart-line"></i>
            </div>
            <div>
                <h2 class="text-xs font-bold text-slate-900 tracking-tight leading-none">Platform Revenue & Income Statement</h2>
                <p class="text-[10px] text-slate-500 mt-0.5">SaaS subscription income, gateway settlements, and financial ledger</p>
            </div>
        </div>

        <div class="flex items-center gap-1.5">
            <a href="{{ route('owner.reports.revenue.export', request()->query()) }}" 
               class="px-2.5 py-1 rounded bg-slate-50 hover:bg-slate-100 text-slate-700 text-[10.5px] font-semibold border border-slate-200 transition flex items-center gap-1 shadow-2xs">
                <i class="fas fa-file-csv text-emerald-600 text-[10px]"></i>
                <span>Export CSV</span>
            </a>
            <button type="button" onclick="window.print()" 
                    class="px-2.5 py-1 rounded bg-slate-50 hover:bg-slate-100 text-slate-700 text-[10.5px] font-semibold border border-slate-200 transition flex items-center gap-1 shadow-2xs">
                <i class="fas fa-print text-slate-500 text-[10px]"></i>
                <span>Print Statement</span>
            </button>
        </div>
    </div>

    <!-- 4 High-Density KPI Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2">
        
        <!-- Period Collected Income -->
        <div class="p-2.5 rounded-lg bg-white border border-slate-200/90 shadow-2xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9.5px] font-bold text-slate-500 uppercase tracking-wider block">Period Income</span>
                <h3 class="text-sm font-bold text-emerald-600 font-mono tracking-tight mt-0.5">@currency($totalCollectedIncome)</h3>
                <span class="text-[9.5px] text-slate-400 block truncate">{{ $totalInvoicesCount }} Invoices Processed</span>
            </div>
            <div class="w-7 h-7 rounded-md bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs border border-emerald-100 flex-shrink-0">
                <i class="fas fa-coins text-[11px]"></i>
            </div>
        </div>

        <!-- Estimated MRR -->
        <div class="p-2.5 rounded-lg bg-white border border-slate-200/90 shadow-2xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9.5px] font-bold text-slate-500 uppercase tracking-wider block">Current MRR</span>
                <h3 class="text-sm font-bold text-slate-900 font-mono tracking-tight mt-0.5">@currency($currentMrr)</h3>
                <span class="text-[9.5px] text-blue-600 font-medium block truncate">Monthly Subscription Run</span>
            </div>
            <div class="w-7 h-7 rounded-md bg-blue-50 text-blue-600 flex items-center justify-center text-xs border border-blue-100 flex-shrink-0">
                <i class="fas fa-arrow-trend-up text-[11px]"></i>
            </div>
        </div>

        <!-- Lifetime Revenue -->
        <div class="p-2.5 rounded-lg bg-white border border-slate-200/90 shadow-2xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9.5px] font-bold text-slate-500 uppercase tracking-wider block">Lifetime Collected</span>
                <h3 class="text-sm font-bold text-slate-900 font-mono tracking-tight mt-0.5">@currency($lifetimeIncome)</h3>
                <span class="text-[9.5px] text-slate-500 font-medium block truncate">Gross Platform Income</span>
            </div>
            <div class="w-7 h-7 rounded-md bg-purple-50 text-purple-600 flex items-center justify-center text-xs border border-purple-100 flex-shrink-0">
                <i class="fas fa-vault text-[11px]"></i>
            </div>
        </div>

        <!-- Outstanding Receivables -->
        <div class="p-2.5 rounded-lg bg-white border border-slate-200/90 shadow-2xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9.5px] font-bold text-slate-500 uppercase tracking-wider block">Outstanding Due</span>
                <h3 class="text-sm font-bold text-rose-600 font-mono tracking-tight mt-0.5">@currency($totalOutstandingDue)</h3>
                <span class="text-[9.5px] text-slate-400 block truncate">Unpaid & Overdue</span>
            </div>
            <div class="w-7 h-7 rounded-md bg-rose-50 text-rose-600 flex items-center justify-center text-xs border border-rose-100 flex-shrink-0">
                <i class="fas fa-clock text-[11px]"></i>
            </div>
        </div>

    </div>

    <!-- Filter Control Bar -->
    <div class="bg-white rounded-lg border border-slate-200/90 p-3 shadow-2xs">
        <form method="GET" action="{{ route('owner.reports.revenue') }}" class="space-y-2.5">
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2 items-end">
                
                <!-- Date Preset -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-600 uppercase tracking-wider mb-1">Time Period</label>
                    <select name="date_preset" class="w-full text-[11px] rounded border border-slate-200 bg-white py-1 px-2 text-slate-800 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                        <option value="today" {{ $preset === 'today' ? 'selected' : '' }}>Today</option>
                        <option value="yesterday" {{ $preset === 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                        <option value="this_week" {{ $preset === 'this_week' ? 'selected' : '' }}>This Week</option>
                        <option value="this_month" {{ $preset === 'this_month' ? 'selected' : '' }}>This Month</option>
                        <option value="last_month" {{ $preset === 'last_month' ? 'selected' : '' }}>Last Month</option>
                        <option value="this_year" {{ $preset === 'this_year' ? 'selected' : '' }}>This Year</option>
                        <option value="all_time" {{ $preset === 'all_time' ? 'selected' : '' }}>All Time</option>
                    </select>
                </div>

                <!-- Tenant Filter -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-600 uppercase tracking-wider mb-1">ISP Tenant</label>
                    <select name="tenant_id" class="w-full text-[11px] rounded border border-slate-200 bg-white py-1 px-2 text-slate-800 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                        <option value="">All Tenants</option>
                        @foreach($tenantsList as $t)
                            <option value="{{ $t->id }}" {{ $tenantId == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Payment Method -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-600 uppercase tracking-wider mb-1">Payment Channel</label>
                    <select name="payment_method" class="w-full text-[11px] rounded border border-slate-200 bg-white py-1 px-2 text-slate-800 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                        <option value="">All Methods</option>
                        <option value="bkash" {{ $method === 'bkash' ? 'selected' : '' }}>bKash Online</option>
                        <option value="nagad" {{ $method === 'nagad' ? 'selected' : '' }}>Nagad Online</option>
                        <option value="rocket" {{ $method === 'rocket' ? 'selected' : '' }}>Rocket Online</option>
                        <option value="bank" {{ $method === 'bank' ? 'selected' : '' }}>Bank Transfer</option>
                        <option value="cash" {{ $method === 'cash' ? 'selected' : '' }}>Cash / Manual</option>
                    </select>
                </div>

                <!-- Invoice Status -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-600 uppercase tracking-wider mb-1">Payment Status</label>
                    <select name="status" class="w-full text-[11px] rounded border border-slate-200 bg-white py-1 px-2 text-slate-800 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                        <option value="paid" {{ $status === 'paid' ? 'selected' : '' }}>Paid Only</option>
                        <option value="unpaid" {{ $status === 'unpaid' ? 'selected' : '' }}>Unpaid / Due</option>
                        <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All Statuses</option>
                    </select>
                </div>

                <!-- Start Date -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-600 uppercase tracking-wider mb-1">From Date</label>
                    <input type="date" name="start_date" value="{{ request('start_date', $from ? $from->format('Y-m-d') : '') }}" 
                           class="w-full text-[11px] rounded border border-slate-200 bg-white py-1 px-2 text-slate-800 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                </div>

                <!-- End Date & Action Buttons -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-600 uppercase tracking-wider mb-1">To Date</label>
                    <div class="flex items-center gap-1.5">
                        <input type="date" name="end_date" value="{{ request('end_date', $to ? $to->format('Y-m-d') : '') }}" 
                               class="w-full text-[11px] rounded border border-slate-200 bg-white py-1 px-2 text-slate-800 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                        <button type="submit" class="px-2.5 py-1 rounded bg-blue-600 hover:bg-blue-700 text-white font-bold text-[11px] transition shadow-2xs flex-shrink-0">
                            <i class="fas fa-filter text-[10px]"></i>
                        </button>
                    </div>
                </div>

            </div>
        </form>
    </div>

    <!-- Analytics Chart & Channel Breakdown (2-cols) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-2.5 items-stretch">
        
        <!-- Revenue Trend Chart (8 cols) -->
        <div class="lg:col-span-8 bg-white rounded-lg border border-slate-200/90 p-3 shadow-2xs flex flex-col justify-between">
            <div class="flex items-center justify-between border-b border-slate-100 pb-1.5 mb-2">
                <h3 class="font-bold text-slate-900 text-xs tracking-tight">Income Collection Trend</h3>
                <span class="text-[10px] text-slate-400 font-mono">Real-time Income Graph</span>
            </div>

            <div class="h-40 w-full relative">
                <canvas id="incomeTrendChart"></canvas>
            </div>
        </div>

        <!-- Breakdown by Payment Channels & Top Tenants (4 cols) -->
        <div class="lg:col-span-4 bg-white rounded-lg border border-slate-200/90 p-3 shadow-2xs flex flex-col justify-between space-y-2">
            <div class="border-b border-slate-100 pb-1.5">
                <h3 class="font-bold text-slate-900 text-xs tracking-tight">Payment Gateways Share</h3>
            </div>

            <div class="space-y-1.5">
                @forelse ($methodBreakdown as $mb)
                    @php
                        $percentage = $totalCollectedIncome > 0 ? round(($mb->total / $totalCollectedIncome) * 100) : 0;
                    @endphp
                    <div class="p-1.5 px-2 rounded-md bg-slate-50 border border-slate-200/80 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-credit-card text-emerald-600 text-[10px]"></i>
                            <span class="font-semibold text-slate-800 text-[11px] uppercase">{{ $mb->payment_method ?: 'Manual' }}</span>
                        </div>
                        <div class="text-right font-mono">
                            <span class="font-bold text-slate-900 text-[11px]">@currency($mb->total)</span>
                            <span class="text-[9.5px] text-slate-400 block leading-none">({{ $percentage }}%)</span>
                        </div>
                    </div>
                @empty
                    <div class="py-3 text-center text-slate-400 text-xs">
                        No payment channel data for selected period.
                    </div>
                @endforelse
            </div>

            <div class="pt-1.5 border-t border-slate-100 flex items-center justify-between text-[10px]">
                <span class="text-slate-500">Active Subscriptions</span>
                <a href="{{ route('owner.subscriptions.index') }}" class="font-semibold text-blue-600 hover:underline">
                    View Subscriptions &rarr;
                </a>
            </div>
        </div>

    </div>

    <!-- Income Statement & Ledger Table -->
    <div class="bg-white rounded-lg border border-slate-200/90 overflow-hidden shadow-2xs">
        <div class="px-3.5 py-2 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
            <div>
                <h3 class="font-bold text-slate-900 text-xs tracking-tight">Income Statement & Payment Ledger</h3>
                <p class="text-[10px] text-slate-500">Detailed list of billing transactions, invoices, and collected revenue</p>
            </div>
            <span class="text-[10px] font-semibold text-slate-500 font-mono">
                Showing {{ $invoices->firstItem() ?? 0 }} - {{ $invoices->lastItem() ?? 0 }} of {{ $invoices->total() }} Records
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider text-[9.5px] border-b border-slate-100">
                    <tr>
                        <th class="px-3.5 py-2">Invoice #</th>
                        <th class="px-3 py-2">Date & Time</th>
                        <th class="px-3 py-2">ISP Tenant</th>
                        <th class="px-3 py-2">SaaS Plan</th>
                        <th class="px-3 py-2">Method / Trx</th>
                        <th class="px-3 py-2 text-right">Gross Amount</th>
                        <th class="px-3 py-2 text-right">Discount</th>
                        <th class="px-3 py-2 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($invoices as $row)
                        <tr class="hover:bg-slate-50/70 transition font-medium">
                            <td class="px-3.5 py-2 font-mono font-bold text-slate-800 text-[11px]">
                                {{ $row->invoice_no ?? '#INV-' . $row->id }}
                            </td>
                            <td class="px-3 py-2 text-slate-500 text-[10.5px] whitespace-nowrap">
                                {{ $row->created_at->format('d M, Y h:i A') }}
                            </td>
                            <td class="px-3 py-2">
                                <span class="font-bold text-slate-900 text-xs block truncate max-w-[150px]">
                                    {{ $row->tenant->name ?? 'N/A' }}
                                </span>
                                <span class="text-[9.5px] text-slate-400 font-mono block">{{ $row->tenant->domain ?? 'Standard' }}</span>
                            </td>
                            <td class="px-3 py-2">
                                <span class="px-1.5 py-0.2 rounded bg-slate-100 text-slate-700 text-[9.5px] font-semibold border border-slate-200">
                                    {{ $row->plan->name ?? 'Standard' }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-slate-700 leading-tight">
                                <span class="block font-semibold text-[10.5px] uppercase">{{ $row->payment_method ?: 'Manual' }}</span>
                                <span class="text-[9px] text-slate-400 font-mono">{{ $row->trx_id ?: 'N/A' }}</span>
                            </td>
                            <td class="px-3 py-2 text-right font-mono font-bold text-slate-900 text-xs whitespace-nowrap">
                                @currency($row->amount)
                            </td>
                            <td class="px-3 py-2 text-right font-mono text-slate-400 text-xs whitespace-nowrap">
                                @if($row->discount > 0)
                                    <span class="text-rose-500">-@currency($row->discount)</span>
                                @else
                                    <span>৳0.00</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-center">
                                @if ($row->status === 'paid')
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.2 rounded-full text-[9px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <i class="fas fa-check text-[7.5px]"></i>
                                        Paid
                                    </span>
                                @elseif ($row->status === 'overdue' || ($row->due_date && $row->due_date->isPast()))
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.2 rounded-full text-[9px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                        <i class="fas fa-exclamation text-[7.5px]"></i>
                                        Overdue
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.2 rounded-full text-[9px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                        <i class="fas fa-clock text-[7.5px]"></i>
                                        Unpaid
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-6 text-center text-slate-400 text-xs">
                                No revenue or invoice records found for the selected filter criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($invoices->hasPages())
            <div class="px-3 py-2 border-t border-slate-100 bg-slate-50/50">
                {{ $invoices->links() }}
            </div>
        @endif
    </div>

</div>

<!-- Chart.js Script for Revenue & Growth Visuals -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('incomeTrendChart').getContext('2d');
        
        const labels = @json($chartLabels);
        const data = @json($chartData);

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Income (BDT)',
                        data: data,
                        backgroundColor: '#10b981',
                        borderRadius: 3,
                        barPercentage: 0.55
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleFont: { size: 10, weight: 'bold' },
                        bodyFont: { size: 10 },
                        padding: 6,
                        cornerRadius: 4,
                        callbacks: {
                            label: function(context) {
                                return 'Income: ৳' + context.raw.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 9 }, color: '#64748b' }
                    },
                    y: {
                        grid: { color: '#f1f5f9' },
                        ticks: {
                            font: { size: 9 },
                            color: '#64748b',
                            callback: function(value) {
                                return '৳' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    });
</script>
@endsection
