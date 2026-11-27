@extends('owner.layouts.app')

@section('page-title', 'ISP Tenants Management')

@section('content')
<div class="space-y-3" x-data="tenantDirectory()"
     @scroll.window.capture="activeMenu = null" @resize.window="activeMenu = null" @keydown.escape.window="activeMenu = null">


    <!-- Top Compact Header Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs">
        <div>
            <div class="flex items-center gap-2">
                <h2 class="text-sm font-bold text-slate-900 tracking-tight">ISP Tenants Directory</h2>
                <span class="px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 border border-blue-200 text-[10px] font-bold font-mono">
                    {{ $totalTenants }} Organizations
                </span>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('owner.tenants.create') }}" class="px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs shadow-xs transition flex items-center gap-1.5">
                <i class="fas fa-plus-circle text-[11px]"></i>
                <span>Create Tenant</span>
            </a>
        </div>
    </div>

    <!-- Global KPI Cards -->
    <div class="owner-kpi-grid">
        
        <x-owner.kpi-card title="Total Tenants" :value="number_format($totalTenants)" icon="fa-building" theme="blue" />

        <x-owner.kpi-card title="Active Licenses" :value="number_format($activeTenants)" icon="fa-check-double" theme="emerald" />

        <x-owner.kpi-card title="Suspended / Expired" :value="number_format($suspendedTenants + $expiredTenants)" icon="fa-exclamation-triangle" theme="rose" :meta="$expiredTenants . ' Expired · ' . $suspendedTenants . ' Suspended'" />

        <x-owner.kpi-card title="Estimated MRR" :value="currency($estimatedMrr)" icon="fa-wallet" theme="purple" meta="Monthly Platform Run-Rate" />

        <x-owner.kpi-card title="Expiring Soon" :value="number_format($tenants->filter(fn($t) => $t->subscription_expires_at && $t->subscription_expires_at->isBetween(now(), now()->addDays(7)))->count())" icon="fa-clock" theme="amber" />
        <x-owner.kpi-card title="MRR Currency" :value="$currencyCode ?? 'BDT'" icon="fa-coins" theme="cyan" />
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-white p-2.5 rounded-xl border border-slate-200/90 shadow-2xs space-y-2.5" x-data="{ showMoreFilters: {{ request()->filled('status') || request()->filled('plan_id') ? 'true' : 'false' }}, searchQuery: @js(request('search', '')) }">
        <form action="{{ route('owner.tenants.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-2.5">
            
            <!-- Search Keyword -->
            <div class="lg:col-span-2">
                <div class="relative">
                    <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-[11px]"></i>
                    <input type="text" 
                           name="search" 
                           value="{{ request('search') }}" 
                           placeholder="Search company, domain, email, phone..." 
                           x-model="searchQuery" class="w-full h-[34px] pl-7 pr-3 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs focus:outline-none focus:ring-1 focus:ring-cyan-500 focus:border-cyan-500 transition placeholder:text-slate-400">
                </div>
            </div>

            <!-- SaaS Plan Filter -->
            <div>
                <select name="plan_id" class="w-full h-[34px] px-2.5 py-0 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs focus:outline-none focus:ring-1 focus:ring-cyan-500">
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
                <select name="status" class="w-full h-[34px] px-2.5 py-0 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs focus:outline-none focus:ring-1 focus:ring-cyan-500">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                    <option value="expiring_soon" {{ request('status') === 'expiring_soon' ? 'selected' : '' }}>Expiring Soon (<=7 Days)</option>
                    <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired</option>
                </select>
            </div>

            <!-- Filter Action Buttons -->
            <div class="flex items-center gap-1.5">
                    <button type="submit" class="flex-1 h-[34px] py-0 px-3 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs transition flex items-center justify-center gap-1.5 shadow-xs">
                    <i class="fas fa-filter text-[10px]"></i>
                    <span>Filter</span>
                </button>
                <a href="{{ route('owner.tenants.index') }}" title="Reset Filters" class="h-[34px] py-0 px-2.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition flex items-center justify-center gap-1">
                    <i class="fas fa-rotate-left text-[11px]"></i>
                    <span>Reset</span>
                </a>
            </div>

        </form>
    </div>

    <!-- Essential Clean High-Density Administrative Matrix Table -->
    <div class="rounded-xl bg-white border border-slate-200 overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-12 text-center font-semibold">#</th>
                        <th class="text-center font-semibold">Name</th>
                        <th class="text-center font-semibold">Address</th>
                        <th class="text-center font-semibold">Phone</th>
                        <th class="text-center font-semibold">Package</th>
                        <th class="text-center font-semibold">Expiry</th>
                        <th class="text-center font-semibold">Status</th>
                        <th class="no-sort text-center font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tenants as $tenant)
                        @php
                            $expiryDate = $tenant->subscription_expires_at;
                            $isActive = $tenant->status === 'active';
                            $isExpired = $expiryDate?->isPast();
                            $extendData = [
                                'name' => $tenant->name,
                                'current_expiry' => $expiryDate?->format('Y-m-d'),
                                'action_url' => route('owner.tenants.extend-subscription', $tenant),
                            ];
                        @endphp
                        <tr>
                            <td class="text-center font-mono">
                                {{ ($tenants->currentPage() - 1) * $tenants->perPage() + $loop->iteration }}
                            </td>
                            <td class="text-left">
                                <a href="{{ route('owner.tenants.show', $tenant) }}" class="font-semibold text-slate-900 hover:text-cyan-700">{{ $tenant->name }}</a>
                            </td>
                            <td class="text-left">
                                <span class="block max-w-xs truncate" title="{{ $tenant->address }}">{{ $tenant->address ?: '—' }}</span>
                            </td>
                            <td class="text-center font-mono">{{ $tenant->phone ?: '—' }}</td>
                            <td class="text-left">{{ $tenant->plan->name ?? 'P1' }}</td>
                            <td class="text-center font-mono">{{ $expiryDate?->format('d M, Y') ?? 'Lifetime' }}</td>
                            <td class="text-center">
                                @if (!$isActive)
                                    <span class="rounded-full bg-rose-50 px-2 py-0.5 font-semibold text-rose-700">Suspended</span>
                                @elseif ($isExpired)
                                    <span class="rounded-full bg-amber-50 px-2 py-0.5 font-semibold text-amber-700">Expired</span>
                                @else
                                    <span class="rounded-full bg-emerald-50 px-2 py-0.5 font-semibold text-emerald-700">Active</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <button type="button" @click="toggleMenu({{ $tenant->id }}, $event)"
                                        :aria-expanded="activeMenu === {{ $tenant->id }}"
                                        aria-controls="tenant-actions-{{ $tenant->id }}" aria-label="Actions for {{ $tenant->name }}"
                                        class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 bg-slate-50 text-slate-600 hover:bg-slate-100">
                                    <i class="fas fa-ellipsis-v text-[10px]" aria-hidden="true"></i>
                                </button>

                                <template x-teleport="body">
                                    <div id="tenant-actions-{{ $tenant->id }}" x-show="activeMenu === {{ $tenant->id }}" x-cloak
                                         :style="menuPos" @click.outside="if (activeMenu === {{ $tenant->id }} && !$event.target.closest('[aria-controls=tenant-actions-{{ $tenant->id }}]')) activeMenu = null"
                                         class="fixed z-50 w-48 rounded-lg border border-slate-200 bg-white p-1 text-xs shadow-lg">
                                        <a href="{{ route('owner.tenants.show', $tenant) }}" class="flex items-center gap-2 rounded-md px-2 py-1.5 hover:bg-slate-50">
                                            <i class="fas fa-eye fa-fw" aria-hidden="true"></i> View Details
                                        </a>
                                        <form method="POST" action="{{ route('owner.tenants.impersonate', $tenant) }}">
                                            @csrf
                                            <button type="submit" class="flex w-full items-center gap-2 rounded-md px-2 py-1.5 hover:bg-slate-50">
                                                <i class="fas fa-sign-in-alt fa-fw" aria-hidden="true"></i> Login as Administrator
                                            </button>
                                        </form>
                                        <button type="button" @click="activeMenu = null; openExtend(@js($extendData))" class="flex w-full items-center gap-2 rounded-md px-2 py-1.5 hover:bg-slate-50">
                                            <i class="fas fa-calendar-plus fa-fw" aria-hidden="true"></i> Extend Subscription
                                        </button>
                                        <a href="{{ route('owner.tenants.edit', $tenant) }}" class="flex items-center gap-2 rounded-md px-2 py-1.5 hover:bg-slate-50">
                                            <i class="fas fa-pencil-alt fa-fw" aria-hidden="true"></i> Edit Tenant
                                        </a>
                                        <form method="POST" action="{{ route('owner.tenants.toggle-status', $tenant) }}">
                                            @csrf
                                            <button type="submit" class="flex w-full items-center gap-2 rounded-md px-2 py-1.5 hover:bg-slate-50">
                                                <i class="fas fa-fw {{ $isActive ? 'fa-ban' : 'fa-check' }}" aria-hidden="true"></i>
                                                {{ $isActive ? 'Suspend Tenant' : 'Activate Tenant' }}
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('owner.tenants.destroy', $tenant) }}"
                                              @submit.prevent="activeMenu = null; confirmDeleteTenant($el, @js($tenant->name))"
                                              class="border-t border-slate-100">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-rose-700 hover:bg-rose-50">
                                                <i class="fas fa-trash-alt fa-fw" aria-hidden="true"></i> Delete Tenant
                                            </button>
                                        </form>
                                    </div>
                                </template>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-slate-400 text-xs">
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
    function tenantDirectory() {
        return {
            activeMenu: null,
            menuPos: {},
            extendModalOpen: false,
            extendTenant: null,
            extendType: '1_month',
            customDate: '',

            toggleMenu(id, event) {
                if (this.activeMenu === id) {
                    this.activeMenu = null;
                    return;
                }

                const rect = event.currentTarget.getBoundingClientRect();
                const right = Math.max(10, window.innerWidth - rect.right);
                const top = Math.round(rect.bottom) + 2;
                const opensAbove = top + 200 > window.innerHeight;

                this.menuPos = {
                    top: opensAbove ? 'auto' : `${top}px`,
                    bottom: opensAbove ? `${Math.max(10, window.innerHeight - Math.round(rect.top) + 2)}px` : 'auto',
                    right: `${right}px`,
                };
                this.activeMenu = id;
            },

            openExtend(tenant) {
                this.extendTenant = tenant;
                this.extendType = '1_month';
                this.customDate = '';
                this.extendModalOpen = true;
            },
        };
    }

    function confirmDeleteTenant(form, tenantName) {
        const name = document.createElement('span');
        name.textContent = tenantName;
        Swal.fire({
            title: 'Delete ISP Organization?',
            html: `
                <div class="text-left text-xs space-y-2 mt-2">
                    <p class="text-slate-700">Are you sure you want to completely remove <strong>"${name.innerHTML}"</strong>?</p>
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
                form.submit();
            }
        });
    }
</script>
@endpush

