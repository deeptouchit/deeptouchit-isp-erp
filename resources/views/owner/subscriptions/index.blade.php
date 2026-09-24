@extends('owner.layouts.app')

@section('page-title', 'Tenant Subscriptions Lifecycle')

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


    <!-- Top Header Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs">
        <div>
            <div class="flex items-center gap-2">
                <h2 class="text-sm font-bold text-slate-900 tracking-tight">ISP Tenant Subscriptions</h2>
                <span class="px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 border border-blue-200 text-[10px] font-bold font-mono">
                    {{ $totalSubscriptions }} Subscriptions
                </span>
            </div>
            <p class="text-[11px] text-slate-500 mt-0.5">Manage SaaS cloud licenses, billing periods, scheduled auto-renewals, and grace period states</p>
        </div>

        <div class="flex items-center gap-2">
            <form action="{{ route('owner.billing.run-engine') }}" method="POST" onsubmit="return confirm('Run automated billing engine now?');">
                @csrf
                <button type="submit" class="px-3 py-1.5 rounded-lg bg-purple-50 hover:bg-purple-600 hover:text-white text-purple-700 border border-purple-200 font-bold text-xs shadow-2xs transition flex items-center gap-1.5">
                    <i class="fas fa-bolt text-[11px]"></i>
                    <span>Run Billing Engine</span>
                </button>
            </form>
        </div>
    </div>

    <!-- 5 Subscription State Metrics Strip -->
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
        <div class="p-3 bg-white rounded-xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold text-emerald-600 uppercase block">Active</span>
                <span class="text-base font-extrabold text-slate-900 font-mono">{{ $activeCount }}</span>
            </div>
            <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs">
                <i class="fas fa-check"></i>
            </div>
        </div>
        <div class="p-3 bg-white rounded-xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold text-blue-600 uppercase block">Trial</span>
                <span class="text-base font-extrabold text-slate-900 font-mono">{{ $trialCount }}</span>
            </div>
            <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-xs">
                <i class="fas fa-flask"></i>
            </div>
        </div>
        <div class="p-3 bg-white rounded-xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold text-amber-600 uppercase block">Grace Period</span>
                <span class="text-base font-extrabold text-amber-700 font-mono">{{ $gracePeriodCount }}</span>
            </div>
            <div class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center text-xs">
                <i class="fas fa-clock"></i>
            </div>
        </div>
        <div class="p-3 bg-white rounded-xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold text-rose-600 uppercase block">Suspended</span>
                <span class="text-base font-extrabold text-rose-700 font-mono">{{ $suspendedCount }}</span>
            </div>
            <div class="w-7 h-7 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center text-xs">
                <i class="fas fa-lock"></i>
            </div>
        </div>
        <div class="p-3 bg-white rounded-xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold text-purple-600 uppercase block">Auto Renew</span>
                <span class="text-base font-extrabold text-purple-700 font-mono">{{ $autoRenewCount }}</span>
            </div>
            <div class="w-7 h-7 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center text-xs">
                <i class="fas fa-sync-alt"></i>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="p-3.5 rounded-xl bg-white border border-slate-200 shadow-xs">
        <form action="{{ route('owner.subscriptions.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-2.5">
            <div>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search ISP Tenant..." class="w-full px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs focus:ring-1 focus:ring-blue-500">
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
            <div>
                <select name="plan_id" class="w-full px-2.5 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs focus:ring-1 focus:ring-blue-500">
                    <option value="">All Plans</option>
                    @foreach($plans as $p)
                        <option value="{{ $p->id }}" {{ request('plan_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <select name="billing_cycle" class="w-full px-2.5 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs focus:ring-1 focus:ring-blue-500">
                    <option value="">All Cycles</option>
                    <option value="monthly" {{ request('billing_cycle') === 'monthly' ? 'selected' : '' }}>Monthly</option>
                    <option value="yearly" {{ request('billing_cycle') === 'yearly' ? 'selected' : '' }}>Yearly</option>
                </select>
            </div>
            <div class="flex items-center gap-1.5">
                <button type="submit" class="flex-1 py-1.5 px-3 rounded-lg bg-slate-800 hover:bg-slate-900 text-white font-semibold text-xs transition flex items-center justify-center gap-1.5 shadow-xs">
                    <i class="fas fa-filter text-[10px]"></i>
                    <span>Filter</span>
                </button>
                <a href="{{ route('owner.subscriptions.index') }}" title="Reset" class="py-1.5 px-2.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold flex items-center justify-center">
                    <i class="fas fa-rotate-left"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Subscriptions Table -->
    <div class="rounded-xl bg-white border border-slate-200 overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-700 whitespace-nowrap border-collapse">
                <thead class="bg-slate-50 text-slate-600 uppercase tracking-wider text-[10px] border-b border-slate-200 font-bold text-center">
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
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse($subscriptions as $sub)
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
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-slate-400">No tenant subscriptions found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3 border-t border-slate-200 bg-slate-50 flex items-center justify-between text-xs text-slate-600">
            <div>Showing {{ $subscriptions->firstItem() ?? 0 }} to {{ $subscriptions->lastItem() ?? 0 }} of {{ $subscriptions->total() }}</div>
            <div>{{ $subscriptions->links() }}</div>
        </div>
    </div>

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
