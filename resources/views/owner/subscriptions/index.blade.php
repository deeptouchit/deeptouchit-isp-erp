@extends('owner.layouts.app')

@section('page-title', 'Tenant Subscriptions Lifecycle')

@push('styles')
<style>.owner-finance-table.saas-table td { white-space: nowrap; } .owner-finance-table.saas-table td > div { display: inline-flex; vertical-align: middle; } .owner-finance-table.saas-table td > div + div { margin-left: .35rem; } .owner-finance-table.saas-table th:nth-child(5), .owner-finance-table.saas-table td:nth-child(5) { display: none; } .owner-finance-filter select, .owner-finance-filter input, .owner-finance-filter button { height: 34px; } .owner-finance-filter > div:first-child { flex: 1 1 220px; min-width: 200px; } .owner-finance-filter > div:not(:first-child) { flex: 0 0 auto; } .owner-finance-filter select { width: auto !important; min-width: 130px; }</style>
@endpush

@section('content')
<div class="space-y-4" x-data="{
    subModalOpen: false,
    selectedSubId: '',
    selectedSubTenant: '',
    selectedSubStatus: 'active',
    selectedSubAutoRenew: true,
    selectedPeriodEnd: '',

    openSubStatus(subId, tenantName, currentStatus, autoRenew, periodEnd) {
        this.selectedSubId = subId;
        this.selectedSubTenant = tenantName;
        this.selectedSubStatus = currentStatus;
        this.selectedSubAutoRenew = !!autoRenew;
        this.selectedPeriodEnd = periodEnd || '';
        this.subModalOpen = true;
    }
}">

    <!-- Global KPI Cards -->
    <div class="owner-kpi-grid">
        <x-owner.kpi-card title="Active" :value="$activeCount" icon="fa-check" theme="emerald" />
        <x-owner.kpi-card title="Trial" :value="$trialCount" icon="fa-flask" theme="blue" />
        <x-owner.kpi-card title="Grace Period" :value="$gracePeriodCount" icon="fa-clock" theme="amber" />
        <x-owner.kpi-card title="Suspended" :value="$suspendedCount" icon="fa-lock" theme="rose" />
        <x-owner.kpi-card title="Auto Renew" :value="$autoRenewCount" icon="fa-sync-alt" theme="purple" />
    </div>

    <!-- Filters -->
    <div class="bg-white p-2.5 rounded-xl border border-slate-200/90 shadow-2xs">
        <form action="{{ route('owner.subscriptions.index') }}" method="GET" x-data="{ showMoreFilters: {{ request()->filled('plan_id') || request()->filled('billing_cycle') ? 'true' : 'false' }}, searchQuery: @js(request('search', '')) }" class="owner-finance-filter flex flex-col md:flex-row items-stretch md:items-center gap-2">
            <div>
                <div class="relative"><i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-[11px]" :class="searchQuery ? 'text-cyan-600 font-bold' : 'text-slate-400'"></i><input type="text" name="search" x-model="searchQuery" value="{{ request('search') }}" placeholder="Search ISP Tenant..." class="w-full h-[34px] pl-8 pr-7 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs focus:bg-white focus:ring-1 focus:ring-cyan-500 focus:border-cyan-500"><button type="button" x-show="searchQuery" x-cloak @click="searchQuery = ''; $nextTick(() => $el.closest('form').submit())" class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-rose-600"><i class="fas fa-times text-[10px]"></i></button></div>
            </div>
            <div>
                <select name="status" class="w-full px-2.5 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs focus:ring-1 focus:ring-blue-500">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="trial" {{ request('status') === 'trial' ? 'selected' : '' }}>Trial</option>
                    <option value="grace_period" {{ request('status') === 'grace_period' ? 'selected' : '' }}>Grace Period</option>
                    <option value="past_due" {{ request('status') === 'past_due' ? 'selected' : '' }}>Past Due</option>
                    <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                    <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired</option>
                </select>
            </div>
            <div x-show="showMoreFilters" x-cloak class="pt-2 border-t border-slate-100">
                <select name="plan_id" class="w-full px-2.5 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs focus:ring-1 focus:ring-blue-500">
                    <option value="">All Plans</option>
                    @foreach($plans as $p)
                        <option value="{{ $p->id }}" {{ request('plan_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div x-show="showMoreFilters" x-cloak>
                <select name="billing_cycle" class="w-full px-2.5 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs focus:ring-1 focus:ring-blue-500">
                    <option value="">All Cycles</option>
                    <option value="monthly" {{ request('billing_cycle') === 'monthly' ? 'selected' : '' }}>Monthly</option>
                    <option value="yearly" {{ request('billing_cycle') === 'yearly' ? 'selected' : '' }}>Yearly</option>
                </select>
            </div>
            <div class="flex items-center gap-1.5 flex-shrink-0 md:ml-auto">
                <button type="button" @click="showMoreFilters = !showMoreFilters" class="h-[34px] py-0 px-2.5 rounded-lg border border-slate-200 bg-white text-slate-700 text-xs font-semibold flex items-center gap-1.5"><i class="fas" :class="showMoreFilters ? 'fa-chevron-up text-cyan-600' : 'fa-chevron-down text-slate-400'"></i><span>More Filters</span></button>
                <button type="submit" class="h-[34px] py-0 px-3 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs transition flex items-center justify-center gap-1.5 shadow-xs">
                    <i class="fas fa-filter text-[10px]"></i>
                    <span>Filter</span>
                </button>
                <a href="{{ route('owner.subscriptions.index') }}" title="Reset" class="h-[34px] py-0 px-2.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold flex items-center justify-center gap-1 border border-slate-200/80">
                    <i class="fas fa-rotate-left text-[10px]"></i><span>Reset</span>
                </a>
            </div>
        </form>
    </div>

    <!-- Subscriptions Table -->
    <x-owner.table-shell
        :has-rows="$subscriptions->isNotEmpty()"
        empty-title="No subscriptions found"
        empty-message="No tenant subscriptions match the current filters."
        empty-icon="fa-repeat"
    >
        <x-slot:head>
                    <tr>
                        <th class="px-3 py-2.5 border-r border-slate-200">ISP Tenant</th>
                        <th class="px-3.5 py-2.5 border-r border-slate-200">Plan & Cycle</th>
                        <th class="px-3.5 py-2.5 border-r border-slate-200">Current Period</th>
                        <th class="px-3.5 py-2.5 border-r border-slate-200">Next Billing Date</th>
                        <th class="px-3.5 py-2.5 border-r border-slate-200">Grace Period Ends</th>
                        <th class="px-3.5 py-2.5 border-r border-slate-200">Auto Renew</th>
                        <th class="px-3.5 py-2.5 border-r border-slate-200">Status</th>
                        <th class="px-3.5 py-2.5 text-center">Actions</th>
                    </tr>
        </x-slot:head>
        <x-slot:body>
                    @foreach($subscriptions as $sub)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-3.5 py-2.5 border-r border-slate-200 font-bold text-slate-900">
                                <a href="{{ route('owner.tenants.show', $sub->tenant) }}" class="text-blue-700 hover:underline">
                                    {{ $sub->tenant->name ?? 'N/A' }}
                                </a>
                                <span class="text-[10px] text-slate-400 block font-mono">{{ $sub->tenant->slug }}.somitysoft.com</span>
                            </td>
                            <td class="px-3.5 py-2.5 border-r border-slate-200 text-center">
                                <span class="font-semibold text-purple-700">{{ $sub->plan->name ?? 'Standard' }}</span>
                                <span class="text-[10px] text-slate-400 block uppercase font-mono">({{ $sub->billing_cycle }})</span>
                            </td>
                            <td class="px-3.5 py-2.5 border-r border-slate-200 font-mono text-[11px] text-center">
                                {{ $sub->current_period_start ? $sub->current_period_start->format('d M, Y') : '—' }} 
                                <span class="text-slate-400">→</span> 
                                {{ $sub->current_period_end ? $sub->current_period_end->format('d M, Y') : '—' }}
                            </td>
                            <td class="px-3.5 py-2.5 border-r border-slate-200 font-mono text-center font-bold text-slate-800">
                                {{ $sub->next_billing_date ? $sub->next_billing_date->format('d M, Y') : '—' }}
                            </td>
                            <td class="px-3.5 py-2.5 border-r border-slate-200 font-mono text-center text-slate-600">
                                {{ $sub->grace_period_ends_at ? $sub->grace_period_ends_at->format('d M, Y') : '—' }}
                            </td>
                            <td class="px-3.5 py-2.5 border-r border-slate-200 text-center">
                                @if($sub->auto_renew)
                                    <span class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 font-bold text-[9.5px] border border-emerald-200">ON</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 font-bold text-[9.5px]">OFF</span>
                                @endif
                            </td>
                            <td class="px-3.5 py-2.5 border-r border-slate-200 text-center">
                                @if($sub->status === 'active')
                                    <span class="px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 font-bold text-[10px] border border-emerald-200">ACTIVE</span>
                                @elseif($sub->status === 'grace_period')
                                    <span class="px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-700 font-bold text-[10px] border border-amber-200">GRACE PERIOD</span>
                                @elseif($sub->status === 'suspended')
                                    <span class="px-2.5 py-0.5 rounded-full bg-rose-50 text-rose-700 font-bold text-[10px] border border-rose-200">SUSPENDED</span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-700 font-bold text-[10px]">{{ strtoupper($sub->status) }}</span>
                                @endif
                            </td>
                            <td class="px-3.5 py-2.5 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <!-- Manual Renew -->
                                    <form action="{{ route('owner.subscriptions.renew', $sub) }}" method="POST" onsubmit="return confirm('Renew subscription by +1 period?');">
                                        @csrf
                                        <button type="submit" title="Manual Renew (+1 Period)" class="px-2.5 py-1 rounded-lg bg-purple-50 hover:bg-purple-600 hover:text-white text-purple-700 font-bold text-[10.5px] border border-purple-200 transition flex items-center gap-1 shadow-2xs">
                                            <i class="fas fa-sync-alt text-[9px]"></i>
                                            <span>Renew</span>
                                        </button>
                                    </form>

                                    <!-- Status Modal Trigger -->
                                    <button type="button" 
                                            @click="openSubStatus({{ $sub->id }}, '{{ addslashes($sub->tenant->name) }}', '{{ $sub->status }}', {{ $sub->auto_renew ? 'true' : 'false' }}, '{{ $sub->current_period_end ? $sub->current_period_end->format('Y-m-d') : '' }}')"
                                            class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[10.5px] border border-slate-200 transition">
                                        Status
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach

        </x-slot:body>
        <x-slot:pagination>
            <div>Showing {{ $subscriptions->firstItem() ?? 0 }} to {{ $subscriptions->lastItem() ?? 0 }} of {{ $subscriptions->total() }}</div>
            <div>{{ $subscriptions->links() }}</div>
        </x-slot:pagination>
    </x-owner.table-shell>

    <!-- Status Change Modal -->
    <div x-show="subModalOpen" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4"
         x-cloak>
        
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-md w-full overflow-hidden text-xs"
             @click.outside="subModalOpen = false">
            
            <form :action="'{{ url('owner/subscriptions') }}/' + selectedSubId + '/status'" method="POST">
                @csrf

                <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center font-bold text-xs shadow-xs">
                            <i class="fas fa-sliders-h"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-900 text-xs">Update Subscription Lifecycle State</h3>
                            <p class="text-[11px] text-blue-700 font-semibold" x-text="selectedSubTenant"></p>
                        </div>
                    </div>
                    <button type="button" @click="subModalOpen = false" class="p-1 rounded-lg text-slate-400 hover:text-slate-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="p-5 space-y-3.5">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Lifecycle State *</label>
                        <select name="status" x-model="selectedSubStatus" required class="w-full px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs font-semibold focus:ring-1 focus:ring-blue-500">
                            <option value="active">Active (Standard Full Service)</option>
                            <option value="trial">Trial (Evaluation Mode)</option>
                            <option value="grace_period">Grace Period (Past Due Alert)</option>
                            <option value="past_due">Past Due (Payment Overdue)</option>
                            <option value="suspended">Suspended (Service Paused, Data 100% Safe)</option>
                            <option value="expired">Expired</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Period End Date (Expiry)</label>
                        <input type="date" name="current_period_end" x-model="selectedPeriodEnd" class="w-full px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs focus:ring-1 focus:ring-blue-500">
                    </div>

                    <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200 flex items-center gap-2">
                        <input type="checkbox" name="auto_renew" value="1" :checked="selectedSubAutoRenew" id="sub_auto_renew_check_page" class="rounded text-blue-600 focus:ring-blue-500">
                        <label for="sub_auto_renew_check_page" class="text-slate-700 font-medium text-[11px] cursor-pointer">
                            Enable Scheduled Auto Renewal Cycle
                        </label>
                    </div>
                </div>

                <div class="p-4 bg-slate-50 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="subModalOpen = false" class="px-3.5 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-600 hover:bg-slate-100 font-semibold text-xs transition">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-xs transition flex items-center gap-1.5">
                        <i class="fas fa-check"></i>
                        <span>Save Changes</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
