@extends('owner.layouts.app')

@section('page-title', 'ISP Tenants Management')

@section('content')
<div class="space-y-4" x-data="{
    // Extend Validity Modal State
    extendModalOpen: false,
    extendTenant: null,
    extendType: '1_month',
    customDate: '',

    openExtend(tenant) {
        this.extendTenant = tenant;
        this.extendType = '1_month';
        this.customDate = '';
        this.extendModalOpen = true;
    }
}">


    <!-- Top Compact Header Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs">
        <div>
            <div class="flex items-center gap-2">
                <h2 class="text-sm font-bold text-slate-900 tracking-tight">ISP Tenants Directory</h2>
                <span class="px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 border border-blue-200 text-[10px] font-bold font-mono">
                    {{ $totalTenants }} Organizations
                </span>
            </div>
            <p class="text-[11px] text-slate-500 mt-0.5">Manage client ISP organizations, subscriptions, licenses, and admin access</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('owner.tenants.create') }}" class="px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs shadow-xs transition flex items-center gap-1.5">
                <i class="fas fa-plus-circle text-[11px]"></i>
                <span>Provision ISP Tenant</span>
            </a>
        </div>
    </div>

    <!-- Top Statistics Strip -->
    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        
        <!-- Metric 1: Total Tenants -->
        <div class="p-3.5 rounded-xl bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5">
                <span class="text-[10.5px] font-medium text-slate-500 uppercase tracking-wider block">Total Tenants</span>
                <span class="text-lg font-extrabold text-slate-900 tracking-tight font-mono">{{ number_format($totalTenants) }}</span>
                <span class="text-[10px] text-slate-400 block">Registered Organizations</span>
            </div>
            <div class="w-9 h-9 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-sm border border-blue-100 flex-shrink-0">
                <i class="fas fa-building"></i>
            </div>
        </div>

        <!-- Metric 2: Active Subscriptions -->
        <div class="p-3.5 rounded-xl bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5">
                <span class="text-[10.5px] font-medium text-emerald-700 uppercase tracking-wider block">Active Licenses</span>
                <span class="text-lg font-extrabold text-emerald-700 tracking-tight font-mono">{{ number_format($activeTenants) }}</span>
                <span class="text-[10px] text-emerald-600 block">
                    {{ $totalTenants > 0 ? round(($activeTenants / $totalTenants) * 100) : 0 }}% Healthy Status
                </span>
            </div>
            <div class="w-9 h-9 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm border border-emerald-100 flex-shrink-0">
                <i class="fas fa-check-double"></i>
            </div>
        </div>

        <!-- Metric 3: Expired / Suspended -->
        <div class="p-3.5 rounded-xl bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5">
                <span class="text-[10.5px] font-medium text-rose-700 uppercase tracking-wider block">Suspended / Expired</span>
                <div class="flex items-center gap-1.5">
                    <span class="text-lg font-extrabold text-rose-700 tracking-tight font-mono">{{ number_format($suspendedTenants + $expiredTenants) }}</span>
                    @if(($suspendedTenants + $expiredTenants) > 0)
                        <span class="px-1.5 py-0.2 rounded bg-rose-100 text-rose-800 text-[9.5px] font-bold">Action Needed</span>
                    @endif
                </div>
                <span class="text-[10px] text-slate-400 block">{{ $expiredTenants }} Expired • {{ $suspendedTenants }} Suspended</span>
            </div>
            <div class="w-9 h-9 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center text-sm border border-rose-100 flex-shrink-0">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
        </div>

        <!-- Metric 4: Estimated MRR -->
        <div class="p-3.5 rounded-xl bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5">
                <span class="text-[10.5px] font-medium text-purple-700 uppercase tracking-wider block">Estimated MRR</span>
                <span class="text-lg font-extrabold text-purple-700 tracking-tight font-mono">@currency($estimatedMrr)</span>
                <span class="text-[10px] text-slate-400 block">Monthly Platform Run-Rate</span>
            </div>
            <div class="w-9 h-9 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center text-sm border border-purple-100 flex-shrink-0">
                <i class="fas fa-wallet"></i>
            </div>
        </div>

    </div>

    <!-- Filter & Search Bar -->
    <div class="p-3.5 rounded-xl bg-white border border-slate-200 shadow-xs">
        <form action="{{ route('owner.tenants.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-2.5">
            
            <!-- Search Keyword -->
            <div class="lg:col-span-2">
                <div class="relative">
                    <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-[11px]"></i>
                    <input type="text" 
                           name="search" 
                           value="{{ request('search') }}" 
                           placeholder="Search company, domain, email, phone..." 
                           class="w-full pl-7 pr-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition placeholder:text-slate-400">
                </div>
            </div>

            <!-- SaaS Plan Filter -->
            <div>
                <select name="plan_id" class="w-full px-2.5 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition">
                    <option value="">All Plans</option>
                    @foreach($plans as $plan)
                        <option value="{{ $plan->id }}" {{ request('plan_id') == $plan->id ? 'selected' : '' }}>
                            {{ $plan->name }} ({{ number_format($plan->customer_limit) }} Users)
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Status Filter -->
            <div>
                <select name="status" class="w-full px-2.5 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                    <option value="expiring_soon" {{ request('status') === 'expiring_soon' ? 'selected' : '' }}>Expiring Soon (<=7 Days)</option>
                    <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired</option>
                </select>
            </div>

            <!-- Filter Action Buttons -->
            <div class="flex items-center gap-1.5">
                <button type="submit" class="flex-1 py-1.5 px-3 rounded-lg bg-slate-800 hover:bg-slate-900 text-white font-semibold text-xs transition flex items-center justify-center gap-1.5 shadow-xs">
                    <i class="fas fa-filter text-[10px]"></i>
                    <span>Filter</span>
                </button>
                <a href="{{ route('owner.tenants.index') }}" title="Reset Filters" class="py-1.5 px-2.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold transition flex items-center justify-center">
                    <i class="fas fa-rotate-left text-[11px]"></i>
                </a>
            </div>

        </form>
    </div>

    <!-- Essential Clean High-Density Administrative Matrix Table -->
    <div class="rounded-xl bg-white border border-slate-200 overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-700 whitespace-nowrap border-collapse">
                <thead class="bg-slate-50 text-slate-600 uppercase tracking-wider text-[10.5px] border-b border-slate-200 font-bold select-none text-center">
                    <tr>
                        <th class="px-3 py-2.5 border-r border-slate-200 text-center w-12">#</th>
                        <th class="px-3.5 py-2.5 border-r border-slate-200 text-center">ISP Organization</th>
                        <th class="px-3.5 py-2.5 border-r border-slate-200 text-center">Plan</th>
                        <th class="px-3.5 py-2.5 border-r border-slate-200 text-center">Contact</th>
                        <th class="px-3.5 py-2.5 border-r border-slate-200 text-center">Expiry</th>
                        <th class="px-3.5 py-2.5 border-r border-slate-200 text-center">Status</th>
                        <th class="px-3.5 py-2.5 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse ($tenants as $tenant)
                        @php
                            $expiryDate = $tenant->subscription_expires_at ? \Carbon\Carbon::parse($tenant->subscription_expires_at) : null;
                            $isExpired = $expiryDate ? $expiryDate->isPast() : false;
                            $daysLeft = $expiryDate ? ceil(now()->floatDiffInDays($expiryDate, false)) : null;
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition">
                            
                            <!-- 1. Index Column -->
                            <td class="px-3 py-2.5 border-r border-slate-200 text-center font-mono text-slate-400 text-[11px] font-semibold">
                                {{ ($tenants->currentPage() - 1) * $tenants->perPage() + $loop->iteration }}
                            </td>

                            <!-- 2. ISP Organization -->
                            <td class="px-3.5 py-2.5 border-r border-slate-200">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center font-extrabold text-xs shadow-2xs flex-shrink-0">
                                        {{ strtoupper(substr($tenant->name, 0, 1)) }}
                                    </div>
                                    <div class="leading-tight">
                                        <a href="{{ route('owner.tenants.show', $tenant) }}" class="font-bold text-slate-900 hover:text-blue-600 transition block">
                                            {{ $tenant->name }}
                                        </a>
                                        <span class="text-[10.5px] text-slate-400 font-mono block">
                                            {{ $tenant->slug }}.somitysoft.com
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <!-- 3. Plan Badge -->
                            <td class="px-3.5 py-2.5 border-r border-slate-200 text-center">
                                <span class="px-2.5 py-1 rounded-md bg-purple-50 text-purple-700 border border-purple-200 font-bold text-[11px] inline-block">
                                    {{ $tenant->plan->name ?? 'P1' }}
                                </span>
                            </td>

                            <!-- 4. Contact -->
                            <td class="px-3.5 py-2.5 border-r border-slate-200 text-center">
                                <div class="leading-tight">
                                    <span class="font-semibold text-slate-800 block text-[11px]">{{ $tenant->phone }}</span>
                                    <span class="text-[10px] text-slate-400">{{ $tenant->email }}</span>
                                </div>
                            </td>

                            <!-- 5. Expiry & Validity -->
                            <td class="px-3.5 py-2.5 border-r border-slate-200 text-center">
                                @if($expiryDate)
                                    <div class="space-y-0.5">
                                        <span class="font-semibold text-slate-800 text-[11px] block font-mono">
                                            {{ $expiryDate->format('d M, Y') }}
                                        </span>
                                        @if($isExpired)
                                            <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9.5px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                                Expired
                                            </span>
                                        @elseif($daysLeft <= 7)
                                            <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9.5px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                                {{ $daysLeft }}d left
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9.5px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                {{ $daysLeft }}d left
                                            </span>
                                        @endif
                                    </div>
                                @else
                                    <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-600 font-semibold text-[10px]">
                                        Lifetime
                                    </span>
                                @endif
                            </td>

                            <!-- 6. Status -->
                            <td class="px-3.5 py-2.5 border-r border-slate-200 text-center">
                                @if ($tenant->status === 'active' && !$isExpired)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10.5px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Active
                                    </span>
                                @elseif($tenant->status === 'active' && $isExpired)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10.5px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        Expired
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10.5px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        Suspended
                                    </span>
                                @endif
                            </td>

                            <!-- 7. Actions (Cleanly Aligned Center) -->
                            <td class="px-3.5 py-2.5 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    
                                    <!-- Details Button -->
                                    <a href="{{ route('owner.tenants.show', $tenant) }}" 
                                       title="View Full Details"
                                       class="inline-flex items-center justify-center gap-1 px-2.5 py-1.5 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-600 hover:text-white border border-blue-200 font-bold text-[11px] transition shadow-2xs">
                                        <i class="fas fa-eye text-[10px]"></i>
                                        <span>Details</span>
                                    </a>

                                    <!-- 1-Click Login Impersonate -->
                                    <a href="{{ route('owner.tenants.impersonate', $tenant) }}" 
                                       title="Login as ISP Administrator" 
                                       class="inline-flex items-center justify-center gap-1 px-2.5 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-600 hover:text-white border border-emerald-200 font-bold text-[11px] transition shadow-2xs">
                                        <i class="fas fa-sign-in-alt text-[10px]"></i>
                                        <span>Login</span>
                                    </a>

                                    <!-- Quick Extend Validity Button -->
                                    <button type="button" 
                                            @click="openExtend({{ json_encode([
                                                'id' => $tenant->id,
                                                'name' => $tenant->name,
                                                'current_expiry' => $tenant->subscription_expires_at ? \Carbon\Carbon::parse($tenant->subscription_expires_at)->format('Y-m-d') : '',
                                                'action_url' => route('owner.tenants.extend-subscription', $tenant)
                                            ]) }})"
                                            title="Extend Subscription Validity" 
                                            class="w-7 h-7 rounded-lg bg-purple-50 hover:bg-purple-600 hover:text-white text-purple-700 border border-purple-200 transition text-[11px] flex items-center justify-center shadow-2xs">
                                        <i class="fas fa-calendar-plus"></i>
                                    </button>

                                    <!-- Edit Link -->
                                    <a href="{{ route('owner.tenants.edit', $tenant) }}" 
                                       title="Edit Settings" 
                                       class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition text-[11px] flex items-center justify-center border border-slate-200 shadow-2xs">
                                        <i class="fas fa-pencil-alt"></i>
                                    </a>

                                    <!-- Toggle Status Form -->
                                    <form action="{{ route('owner.tenants.toggle-status', $tenant) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" 
                                                title="{{ $tenant->status === 'active' ? 'Suspend Account' : 'Activate Account' }}" 
                                                class="w-7 h-7 rounded-lg {{ $tenant->status === 'active' ? 'bg-amber-50 text-amber-600 hover:bg-amber-600 hover:text-white border border-amber-200' : 'bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white border border-emerald-200' }} transition text-[11px] flex items-center justify-center shadow-2xs">
                                            <i class="fas {{ $tenant->status === 'active' ? 'fa-ban' : 'fa-check' }}"></i>
                                        </button>
                                    </form>

                                    <!-- Delete Tenant Button with SweetAlert2 Confirmation -->
                                    <button type="button" 
                                            onclick="confirmDeleteTenant({{ $tenant->id }}, '{{ addslashes($tenant->name) }}')"
                                            title="Delete Tenant & Purge All Associated Data" 
                                            class="w-7 h-7 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white border border-rose-200 transition text-[11px] flex items-center justify-center shadow-2xs">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>

                                    <!-- Hidden Delete Form -->
                                    <form id="delete-tenant-form-{{ $tenant->id }}" 
                                          action="{{ route('owner.tenants.destroy', $tenant) }}" 
                                          method="POST" 
                                          class="hidden">
                                        @csrf
                                        @method('DELETE')
                                    </form>

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-slate-400 text-xs">
                                <div class="flex flex-col items-center justify-center space-y-2">
                                    <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 text-lg">
                                        <i class="fas fa-building"></i>
                                    </div>
                                    <span class="font-bold text-slate-700 text-sm">No ISP Tenants Found</span>
                                    <p class="text-[11px] text-slate-400 max-w-sm">No tenants matched your query. Clear filters or provision a new ISP organization.</p>
                                    <a href="{{ route('owner.tenants.create') }}" class="mt-2 px-3 py-1.5 rounded-lg bg-blue-600 text-white font-semibold text-xs transition">
                                        + Provision New Tenant
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Table Footer / Pagination -->
        <div class="px-4 py-3 border-t border-slate-200 bg-slate-50 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs text-slate-600">
            <div>
                Showing <span class="font-bold text-slate-800">{{ $tenants->firstItem() ?? 0 }}</span> to <span class="font-bold text-slate-800">{{ $tenants->lastItem() ?? 0 }}</span> of <span class="font-bold text-slate-800">{{ $tenants->total() }}</span> ISP Tenants
            </div>
            <div>
                {{ $tenants->links() }}
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- Quick Extend Subscription Modal            -->
    <!-- ========================================== -->
    <div x-show="extendModalOpen" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4"
         x-cloak>
        
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-md w-full overflow-hidden text-xs"
             @click.outside="extendModalOpen = false">
            
            <form :action="extendTenant?.action_url" method="POST">
                @csrf

                <!-- Header -->
                <div class="p-4 bg-purple-50/70 border-b border-purple-100 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-purple-600 text-white flex items-center justify-center font-bold text-xs shadow-xs">
                            <i class="fas fa-calendar-plus"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-900 text-xs">Extend Subscription Validity</h3>
                            <p class="text-[11px] text-purple-700 font-semibold" x-text="extendTenant?.name"></p>
                        </div>
                    </div>
                    <button type="button" @click="extendModalOpen = false" class="p-1 rounded-lg text-slate-400 hover:text-slate-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <!-- Form Body -->
                <div class="p-5 space-y-4">
                    
                    <div>
                        <span class="text-[10.5px] font-semibold text-slate-600 block mb-1">Current Expiration:</span>
                        <div class="p-2 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 font-mono font-semibold" x-text="extendTenant?.current_expiry || 'Lifetime / No Expiry'"></div>
                    </div>

                    <!-- Extension Duration Options -->
                    <div>
                        <label class="block text-[10.5px] font-bold text-slate-700 uppercase tracking-wider mb-2">Select Extension Period</label>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="p-2.5 rounded-lg border text-center cursor-pointer transition"
                                   :class="extendType === '1_month' ? 'border-purple-600 bg-purple-50/50 font-bold text-purple-700' : 'border-slate-200 hover:bg-slate-50 text-slate-700'">
                                <input type="radio" name="extension_type" value="1_month" x-model="extendType" class="hidden">
                                <span>+ 1 Month</span>
                            </label>

                            <label class="p-2.5 rounded-lg border text-center cursor-pointer transition"
                                   :class="extendType === '3_months' ? 'border-purple-600 bg-purple-50/50 font-bold text-purple-700' : 'border-slate-200 hover:bg-slate-50 text-slate-700'">
                                <input type="radio" name="extension_type" value="3_months" x-model="extendType" class="hidden">
                                <span>+ 3 Months</span>
                            </label>

                            <label class="p-2.5 rounded-lg border text-center cursor-pointer transition"
                                   :class="extendType === '6_months' ? 'border-purple-600 bg-purple-50/50 font-bold text-purple-700' : 'border-slate-200 hover:bg-slate-50 text-slate-700'">
                                <input type="radio" name="extension_type" value="6_months" x-model="extendType" class="hidden">
                                <span>+ 6 Months</span>
                            </label>

                            <label class="p-2.5 rounded-lg border text-center cursor-pointer transition"
                                   :class="extendType === '1_year' ? 'border-purple-600 bg-purple-50/50 font-bold text-purple-700' : 'border-slate-200 hover:bg-slate-50 text-slate-700'">
                                <input type="radio" name="extension_type" value="1_year" x-model="extendType" class="hidden">
                                <span>+ 1 Year</span>
                            </label>
                        </div>
                    </div>

                    <!-- Custom Date Option -->
                    <div>
                        <label class="p-2.5 rounded-lg border flex items-center gap-2 cursor-pointer transition"
                               :class="extendType === 'custom_date' ? 'border-purple-600 bg-purple-50/50 font-bold text-purple-700' : 'border-slate-200 hover:bg-slate-50 text-slate-700'">
                            <input type="radio" name="extension_type" value="custom_date" x-model="extendType" class="text-purple-600">
                            <span>Specify Exact Expiry Date</span>
                        </label>
                        <div class="mt-2" x-show="extendType === 'custom_date'" x-cloak>
                            <input type="date" name="custom_date" x-model="customDate" class="w-full px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs focus:ring-1 focus:ring-purple-500">
                        </div>
                    </div>

                    <!-- Auto Reactivate Checkbox -->
                    <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200 flex items-center gap-2">
                        <input type="checkbox" name="reactivate_if_suspended" value="1" checked id="reactivate_check" class="rounded text-purple-600 focus:ring-purple-500">
                        <label for="reactivate_check" class="text-slate-700 font-medium text-[11px] cursor-pointer">
                            Automatically set status to Active if previously expired/suspended
                        </label>
                    </div>

                </div>

                <!-- Footer -->
                <div class="p-4 bg-slate-50 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="extendModalOpen = false" class="px-3.5 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-600 hover:bg-slate-100 font-semibold text-xs transition">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-1.5 rounded-lg bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs shadow-xs transition flex items-center gap-1.5">
                        <i class="fas fa-check"></i>
                        <span>Apply Extension</span>
                    </button>
                </div>

            </form>
        </div>
    </div>

</div>

@endsection

<!-- SweetAlert2 Confirmation Script -->
@push('scripts')
<script>
    function confirmDeleteTenant(tenantId, tenantName) {
        Swal.fire({
            title: 'Delete ISP Organization?',
            html: `
                <div class="text-left text-xs space-y-2 mt-2">
                    <p class="text-slate-700">Are you sure you want to completely remove <strong>"${tenantName}"</strong>?</p>
                    <div class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-[11.5px] leading-relaxed">
                        <i class="fas fa-exclamation-triangle mr-1 text-rose-600"></i>
                        <strong>High Impact Action:</strong> This will permanently erase the ISP organization, associated admin accounts, customer databases, billing records, and all related settings.
                    </div>
                </div>
            `,
            icon: 'warning',
            iconColor: '#e11d48',
            showCancelButton: true,
            confirmButtonColor: '#e11d48',
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="fas fa-trash-alt mr-1"></i> Yes, Delete Everything',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            customClass: {
                popup: 'rounded-2xl shadow-2xl border border-slate-200',
                title: 'text-sm font-bold text-slate-900',
                confirmButton: 'rounded-lg text-xs font-bold px-4 py-2 shadow-xs',
                cancelButton: 'rounded-lg text-xs font-semibold px-4 py-2 bg-slate-100 text-slate-700 hover:bg-slate-200 border-0'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('delete-tenant-form-' + tenantId).submit();
            }
        });
    }
</script>
@endpush

