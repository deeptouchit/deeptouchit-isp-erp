@extends('tenant.layouts.app')

@section('title', 'Monthly Analytics & Recharge - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
<style>
    .progress-bar-glow {
        box-shadow: 0 0 8px rgba(8, 145, 178, 0.2);
    }
</style>
@endpush

@section('content')
<div class="space-y-3" x-data="monthlyAnalyticsManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">

    <!-- 1. Top Header Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white px-4 py-2.5 rounded-xl border border-slate-200 shadow-2xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-sm shadow-2xs flex-shrink-0">
                <i class="fas fa-chart-column"></i>
            </div>
            <div>
                <h1 class="text-sm font-bold text-slate-800 leading-none">Monthly Analytics &amp; Recharge Growth</h1>
            </div>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <!-- Export CSV -->
            <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" 
               class="px-3 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-semibold text-xs border border-emerald-200 transition inline-flex items-center gap-1.5 shadow-2xs cursor-pointer"
               title="Export Monthly Report as CSV">
                <i class="fas fa-file-excel text-[11px]"></i>
                <span>Export CSV</span>
            </a>

            <!-- Bulk Payments Page Link -->
            <a href="{{ route('tenant.customers.bulk-payments') }}" 
               class="px-3 py-1.5 rounded-lg bg-purple-50 hover:bg-purple-100 text-purple-700 font-semibold text-xs border border-purple-200 transition inline-flex items-center gap-1.5 shadow-2xs cursor-pointer">
                <i class="fas fa-layer-group text-[10px]"></i>
                <span>Bulk Payments</span>
            </a>

            <!-- Customer Master -->
            <a href="{{ route('tenant.customers.index') }}" 
               class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs border border-slate-200/80 transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-users text-[10px]"></i>
                <span>All Customers</span>
            </a>
        </div>
    </div>

    <!-- 2. KPI Summary Strip (Strictly 6 Cards) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        <!-- Card 1: Total Active Base -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-2xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-slate-500 uppercase tracking-wider block truncate">Active Base</span>
                <span class="text-[13px] font-bold text-slate-800 font-mono leading-tight block">{{ number_format($kpis['total_subscribers']) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-cyan-50 text-cyan-600 flex items-center justify-center text-[10px] border border-cyan-100 flex-shrink-0">
                <i class="fas fa-users"></i>
            </div>
        </div>

        <!-- Card 2: Year Added -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-2xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-purple-700 uppercase tracking-wider block truncate">{{ $selectedYear }} Added</span>
                <span class="text-[13px] font-bold text-purple-700 font-mono leading-tight block">+{{ number_format($kpis['year_new']) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-purple-50 text-purple-600 flex items-center justify-center text-[10px] border border-purple-100 flex-shrink-0">
                <i class="fas fa-user-plus"></i>
            </div>
        </div>

        <!-- Card 3: This Month New -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-2xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-indigo-700 uppercase tracking-wider block truncate">This Month New</span>
                <span class="text-[13px] font-bold text-indigo-700 font-mono leading-tight block">+{{ number_format($kpis['current_month_new']) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-indigo-50 text-indigo-600 flex items-center justify-center text-[10px] border border-indigo-100 flex-shrink-0">
                <i class="fas fa-user-check"></i>
            </div>
        </div>

        <!-- Card 4: This Month Recharged -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-2xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-emerald-700 uppercase tracking-wider block truncate">This Mo. Paid</span>
                <span class="text-[13px] font-bold text-emerald-600 font-mono leading-tight block">{{ number_format($kpis['current_month_recharged']) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-emerald-50 text-emerald-600 flex items-center justify-center text-[10px] border border-emerald-100 flex-shrink-0">
                <i class="fas fa-circle-check"></i>
            </div>
        </div>

        <!-- Card 5: This Month Unpaid -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-2xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-rose-700 uppercase tracking-wider block truncate">This Mo. Unpaid</span>
                <span class="text-[13px] font-bold text-rose-600 font-mono leading-tight block">{{ number_format($kpis['current_month_non_recharged']) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-rose-50 text-rose-600 flex items-center justify-center text-[10px] border border-rose-100 flex-shrink-0">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
        </div>

        <!-- Card 6: This Month Collection -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-2xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-amber-700 uppercase tracking-wider block truncate">This Mo. Collect</span>
                <span class="text-[13px] font-bold text-amber-700 font-mono leading-tight block">@currency($kpis['current_month_collection'])</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-amber-50 text-amber-600 flex items-center justify-center text-[10px] border border-amber-100 flex-shrink-0">
                <i class="fas fa-file-invoice-dollar"></i>
            </div>
        </div>
    </div>

    <!-- 3. Search & Multi-Filter Toolbar (Strict AGENTS.md Rule 2.C) -->
    <div class="bg-white p-2.5 rounded-xl border border-slate-200/90 shadow-2xs">
        <form method="GET" action="{{ route('tenant.customers.monthly-analytics') }}" class="flex flex-col md:flex-row items-stretch md:items-center gap-2">
            
            <!-- Year Selector -->
            <div class="w-full md:w-36 flex-shrink-0">
                <select name="year" 
                        onchange="this.form.submit()"
                        class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-semibold text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    @foreach($years as $yr)
                        <option value="{{ $yr }}" {{ (int)$selectedYear === (int)$yr ? 'selected' : '' }}>
                            Year: {{ $yr }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Scope Selector -->
            <div class="w-full md:w-44 flex-shrink-0">
                <select name="scope" 
                        onchange="this.form.submit()"
                        class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition text-slate-700 font-medium">
                    <option value="all" {{ $scope === 'all' ? 'selected' : '' }}>All Scopes (Direct + Sub-ISP)</option>
                    <option value="isp" {{ $scope === 'isp' ? 'selected' : '' }}>ISP Direct Retail Only</option>
                    <option value="reseller" {{ $scope === 'reseller' ? 'selected' : '' }}>Sub-ISP Franchise Only</option>
                </select>
            </div>

            <!-- Reseller / Partner Filter -->
            <div class="w-full md:w-56 flex-shrink-0">
                <select name="reseller_id" 
                        onchange="this.form.submit()"
                        class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition text-slate-700">
                    <option value="">All Reseller Partners</option>
                    @foreach($resellers as $res)
                        <option value="{{ $res->id }}" {{ (string)$resellerId === (string)$res->id ? 'selected' : '' }}>
                            {{ $res->name }} ({{ $res->code ?: 'RES-' . $res->id }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Coverage Zone Filter -->
            <div class="w-full md:w-44 flex-shrink-0">
                <select name="zone" 
                        onchange="this.form.submit()"
                        class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition text-slate-700">
                    <option value="">All Zones / Areas</option>
                    @foreach($zones as $z)
                        <option value="{{ $z }}" {{ $zone === $z ? 'selected' : '' }}>{{ $z }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Filter & Reset Buttons (Strict AGENTS.md sequence: [Filter] then [Reset]) -->
            <div class="flex items-center gap-1.5 flex-shrink-0 md:ml-auto">
                <button type="submit" 
                        class="h-[34px] bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-0 px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer" 
                        title="Apply Filters">
                    <i class="fas fa-filter text-[10px]"></i>
                    <span>Filter</span>
                </button>
                <a href="{{ route('tenant.customers.monthly-analytics') }}" 
                   class="h-[34px] bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-0 px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer" 
                   title="Reset Filters">
                    <i class="fas fa-rotate-left text-[10px]"></i>
                    <span>Reset</span>
                </a>
            </div>
        </form>
    </div>

    <!-- 4. Master Table (<table class="saas-table"> Pure CSS System) -->
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-2xs">
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-36">Month &amp; Year</th>
                        <th class="text-center">New Added</th>
                        <th class="text-center">Active Base</th>
                        <th class="text-center">Recharged (Paid)</th>
                        <th class="text-center">Non-Recharged (Unpaid)</th>
                        <th class="text-right">Total Collection</th>
                        <th class="text-center">Recharge Rate</th>
                        <th class="w-20 text-center no-sort">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $r)
                        @php
                            $rate = $r['recharge_rate'];
                            $rateBadgeClass = $rate >= 85 ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : ($rate >= 60 ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-rose-50 text-rose-700 border-rose-200');
                        @endphp
                        <tr>
                            <!-- 1. Month Label -->
                            <td class="font-bold text-slate-800 flex items-center gap-1.5">
                                <i class="fas fa-calendar-alt text-cyan-600 text-[11px]"></i>
                                <span>{{ $r['month_label'] }}</span>
                                @if($r['month_num'] === (int)date('n') && (int)$selectedYear === (int)date('Y'))
                                    <span class="px-1.5 py-0.2 rounded-full text-[9px] bg-cyan-100 text-cyan-800 font-bold">Current</span>
                                @endif
                            </td>

                            <!-- 2. New Customers Added -->
                            <td class="text-center">
                                @if($r['new_customers'] > 0)
                                    <a href="{{ route('tenant.customers.index', ['created_month' => $r['month_key'], 'scope' => $scope, 'reseller_id' => $resellerId, 'zone' => $zone]) }}" 
                                       target="_blank"
                                       class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-purple-50 hover:bg-purple-100 text-purple-700 border border-purple-200 font-mono font-bold text-xs transition cursor-pointer"
                                       title="View {{ $r['new_customers'] }} new customers onboarded in {{ $r['month_label'] }}">
                                        <i class="fas fa-user-plus text-[9px]"></i>
                                        <span>+{{ $r['new_customers'] }}</span>
                                    </a>
                                @else
                                    <span class="font-mono text-slate-400 text-xs">0</span>
                                @endif
                            </td>

                            <!-- 3. Active Base -->
                            <td class="text-center font-mono text-slate-700 font-semibold">
                                {{ number_format($r['base_customers']) }}
                            </td>

                            <!-- 4. Recharged (Paid) -->
                            <td class="text-center">
                                @if($r['recharged_customers'] > 0)
                                    <a href="{{ route('tenant.customers.index', ['recharged_month' => $r['month_key'], 'scope' => $scope, 'reseller_id' => $resellerId, 'zone' => $zone]) }}" 
                                       target="_blank"
                                       class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 font-mono font-bold text-xs transition cursor-pointer"
                                       title="View {{ $r['recharged_customers'] }} customers who recharged in {{ $r['month_label'] }}">
                                        <i class="fas fa-check-circle text-[9px]"></i>
                                        <span>{{ number_format($r['recharged_customers']) }}</span>
                                    </a>
                                @else
                                    <span class="font-mono text-slate-400 text-xs">0</span>
                                @endif
                            </td>

                            <!-- 5. Non-Recharged (Unpaid / Due) -->
                            <td class="text-center">
                                @if($r['non_recharged_customers'] > 0)
                                    <a href="{{ route('tenant.customers.index', ['unpaid_month' => $r['month_key'], 'scope' => $scope, 'reseller_id' => $resellerId, 'zone' => $zone]) }}" 
                                       target="_blank"
                                       class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-mono font-bold text-xs transition cursor-pointer"
                                       title="View {{ $r['non_recharged_customers'] }} customers who did not recharge in {{ $r['month_label'] }}">
                                        <i class="fas fa-triangle-exclamation text-[9px]"></i>
                                        <span>{{ number_format($r['non_recharged_customers']) }}</span>
                                    </a>
                                @else
                                    <span class="font-mono text-slate-400 text-xs">0</span>
                                @endif
                            </td>

                            <!-- 6. Total Collection -->
                            <td class="text-right font-mono font-bold text-slate-800">
                                @currency($r['collection'])
                            </td>

                            <!-- 7. Recharge Rate -->
                            <td class="text-center">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md border text-[11px] font-mono font-bold {{ $rateBadgeClass }}">
                                    <span>{{ $rate }}%</span>
                                </span>
                            </td>

                            <!-- 8. Action: View Details Modal -->
                            <td class="text-center">
                                <button type="button" 
                                        @click="openDetailsModal(@js($r))"
                                        class="px-2 py-1 rounded-md bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-medium transition cursor-pointer inline-flex items-center gap-1"
                                        title="View Detailed Summary for {{ $r['month_label'] }}">
                                    <i class="fas fa-eye text-[10px] text-cyan-600"></i>
                                    <span>View</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-8 text-slate-400 text-xs italic">
                                <i class="fas fa-chart-line text-2xl text-slate-300 mb-2 block"></i>
                                No analytics records available for Year {{ $selectedYear }}.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- 5. Production-Grade Natural Modal: Month Breakdown Drilldown -->
    <div x-show="modalOpen" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs">
        
        <div @click.away="modalOpen = false" 
             x-show="modalOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden space-y-0">
            
            <!-- Modal Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                    <div>
                        <h2 class="text-xs font-semibold text-slate-800" x-text="activeMonth?.month_full + ' Performance Summary'"></h2>
                        <span class="text-[10.5px] text-slate-500 font-normal">Monthly subscriber retention and collection telemetry</span>
                    </div>
                </div>
                <button type="button" 
                        @click="modalOpen = false" 
                        class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-4 space-y-3 text-xs" x-show="activeMonth">
                <!-- 2x2 Stat Cards -->
                <div class="grid grid-cols-2 gap-2">
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] text-slate-500 font-medium block">Total Active Base</span>
                        <span class="text-sm font-bold font-mono text-slate-800" x-text="activeMonth?.base_customers"></span>
                    </div>
                    <div class="p-2.5 bg-purple-50/60 rounded-lg border border-purple-200/80">
                        <span class="text-[10px] text-purple-700 font-medium block">New Onboarded</span>
                        <span class="text-sm font-bold font-mono text-purple-800" x-text="'+' + activeMonth?.new_customers"></span>
                    </div>
                    <div class="p-2.5 bg-emerald-50/60 rounded-lg border border-emerald-200/80">
                        <span class="text-[10px] text-emerald-700 font-medium block">Recharged (Paid)</span>
                        <span class="text-sm font-bold font-mono text-emerald-800" x-text="activeMonth?.recharged_customers"></span>
                    </div>
                    <div class="p-2.5 bg-rose-50/60 rounded-lg border border-rose-200/80">
                        <span class="text-[10px] text-rose-700 font-medium block">Non-Recharged (Unpaid)</span>
                        <span class="text-sm font-bold font-mono text-rose-800" x-text="activeMonth?.non_recharged_customers"></span>
                    </div>
                </div>

                <!-- Financial Metric -->
                <div class="p-3 bg-cyan-50/50 rounded-lg border border-cyan-200 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] text-cyan-800 font-medium block">Total Collection</span>
                        <span class="text-base font-bold font-mono text-cyan-900">
                            {{ $currencySymbol }} <span x-text="Number(activeMonth?.collection || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span>
                        </span>
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] text-cyan-800 font-medium block">Recharge Success Rate</span>
                        <span class="text-sm font-bold font-mono text-cyan-700" x-text="activeMonth?.recharge_rate + '%'"></span>
                    </div>
                </div>

                <!-- Fast Filter Links -->
                <div class="pt-2 border-t border-slate-100 flex flex-wrap gap-2">
                    <a :href="'{{ route('tenant.customers.index') }}?created_month=' + activeMonth?.month_key" 
                       target="_blank"
                       class="flex-1 py-1.5 px-2.5 rounded-lg bg-purple-50 hover:bg-purple-100 text-purple-700 font-semibold text-center border border-purple-200 transition cursor-pointer">
                        <i class="fas fa-users mr-1"></i> View New Added
                    </a>
                    <a :href="'{{ route('tenant.customers.index') }}?recharged_month=' + activeMonth?.month_key" 
                       target="_blank"
                       class="flex-1 py-1.5 px-2.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-semibold text-center border border-emerald-200 transition cursor-pointer">
                        <i class="fas fa-circle-check mr-1"></i> View Recharged
                    </a>
                    <a :href="'{{ route('tenant.customers.index') }}?unpaid_month=' + activeMonth?.month_key" 
                       target="_blank"
                       class="flex-1 py-1.5 px-2.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 font-semibold text-center border border-rose-200 transition cursor-pointer">
                        <i class="fas fa-circle-xmark mr-1"></i> View Unpaid
                    </a>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end">
                <button type="button" 
                        @click="modalOpen = false" 
                        class="px-3.5 py-1.5 rounded-lg border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs transition cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function monthlyAnalyticsManager() {
    return {
        modalOpen: false,
        activeMonth: null,

        openDetailsModal(row) {
            this.activeMonth = row;
            this.modalOpen = true;
        }
    };
}
</script>
@endpush

