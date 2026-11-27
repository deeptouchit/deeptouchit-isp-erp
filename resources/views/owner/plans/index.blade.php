@extends('owner.layouts.app')

@section('page-title', 'SaaS Subscription Pricing Plans')

@section('content')
<div class="space-y-3" x-data="{ 
    searchQuery: '',
    statusFilter: 'all',
    sortColumn: 'sort_order',
    sortAsc: true,
    currencySymbol: '{{ $currencySymbol ?? ($globalSettings['currency_symbol'] ?? '৳') }}',
    currencyPosition: '{{ $currencyPosition ?? ($globalSettings['currency_position'] ?? 'left') }}',
    formatCurrency(amount) {
        let formatted = Number(amount || 0).toLocaleString();
        return this.currencyPosition === 'right' ? (formatted + ' ' + this.currencySymbol) : (this.currencySymbol + ' ' + formatted);
    },
    createModalOpen: false,
    editModalOpen: false,
    detailsModalOpen: false,
    selectedPlan: null,
    showMoreFilters: false,
    activeMenu: null,
    menuPos: {},
    
    plansList: @js($plans),

    get filteredPlans() {
        let result = this.plansList.filter(p => {
            let matchSearch = this.searchQuery === '' || 
                (p.code && p.code.toLowerCase().includes(this.searchQuery.toLowerCase())) ||
                p.name.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
                String(p.customer_limit).includes(this.searchQuery);
            
            let matchStatus = this.statusFilter === 'all' || 
                (this.statusFilter === 'active' && p.is_active) || 
                (this.statusFilter === 'inactive' && !p.is_active);

            return matchSearch && matchStatus;
        });

        result.sort((a, b) => {
            let valA = a[this.sortColumn];
            let valB = b[this.sortColumn];

            // Numerical check
            if (!isNaN(valA) && !isNaN(valB)) {
                valA = Number(valA);
                valB = Number(valB);
            } else {
                valA = String(valA || '').toLowerCase();
                valB = String(valB || '').toLowerCase();
            }

            if (valA < valB) return this.sortAsc ? -1 : 1;
            if (valA > valB) return this.sortAsc ? 1 : -1;
            return 0;
        });

        return result;
    },

    sortBy(column) {
        if (this.sortColumn === column) {
            this.sortAsc = !this.sortAsc;
        } else {
            this.sortColumn = column;
            this.sortAsc = true;
        }
    },

    toggleMenu(id, event) {
        if (this.activeMenu === id) { this.activeMenu = null; return; }
        const rect = event.currentTarget.getBoundingClientRect();
        const top = Math.round(rect.bottom) + 2;
        const above = top + 170 > window.innerHeight;
        this.menuPos = {
            top: above ? 'auto' : (top + 'px'),
            bottom: above ? (Math.max(10, window.innerHeight - Math.round(rect.top) + 2) + 'px') : 'auto',
            right: (Math.max(10, window.innerWidth - rect.right) + 'px'),
            left: 'auto'
        };
        this.activeMenu = id;
    },

    resetFilters() {
        this.searchQuery = '';
        this.statusFilter = 'all';
        this.showMoreFilters = false;
        this.sortColumn = 'sort_order';
        this.sortAsc = true;
    },

    openDetailsModal(plan) {
        this.selectedPlan = plan;
        this.detailsModalOpen = true;
    },

    editData: {
        id: null,
        code: '',
        name: '',
        customer_limit: 500,
        otc_charge: 6000,
        monthly_price: 2000,
        premium_monthly_price: 2100,
        yearly_price: 20000,
        olt_limit: 3,
        mikrotik_limit: 3,
        reseller_limit: 10,
        trial_days: 14,
        badge_text: '',
        is_popular: false,
        allow_radius: false,
        allow_wireguard: false,
        allow_snmp_monitoring: true,
        features_str: '',
        action_url: ''
    },

    openEditModal(plan) {
        this.editData.id = plan.id;
        this.editData.code = plan.code || '';
        this.editData.name = plan.name;
        this.editData.customer_limit = plan.customer_limit;
        this.editData.otc_charge = plan.otc_charge || 0;
        this.editData.monthly_price = plan.monthly_price;
        this.editData.premium_monthly_price = plan.premium_monthly_price || (plan.monthly_price * 1.2);
        this.editData.yearly_price = plan.yearly_price || (plan.monthly_price * 10);
        this.editData.olt_limit = plan.olt_limit || 1;
        this.editData.mikrotik_limit = plan.mikrotik_limit;
        this.editData.reseller_limit = plan.reseller_limit;
        this.editData.trial_days = plan.trial_days || 14;
        this.editData.badge_text = plan.badge_text || '';
        this.editData.is_popular = !!plan.is_popular;
        this.editData.allow_radius = !!plan.allow_radius;
        this.editData.allow_wireguard = !!plan.allow_wireguard;
        this.editData.allow_snmp_monitoring = plan.allow_snmp_monitoring !== false;
        this.editData.features_str = Array.isArray(plan.features) ? plan.features.join(', ') : '';
        this.editData.action_url = '/owner/plans/' + plan.id;
        this.editModalOpen = true;
    }
}" @scroll.window="activeMenu = null" @resize.window="activeMenu = null" @keydown.escape.window="activeMenu = null">

    <!-- Top Header Bar -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs p-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-sm font-bold">
                <i class="fas fa-layer-group"></i>
            </div>
            <div class="flex items-center gap-2">
                    <h2 class="text-sm font-bold text-slate-800">SaaS Subscription Pricing Plans</h2>
                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        {{ $activePlansCount }} Active
                    </span>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <form action="{{ route('owner.plans.seed-defaults') }}" method="POST" onsubmit="return confirm('Restore/Sync standard P1 to P12 ISP pricing matrix?');">
                @csrf
                <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition border border-slate-200 flex items-center gap-1.5" title="Reset standard P1-P12 Plans">
                    <i class="fas fa-arrows-rotate text-slate-500 text-[10px]"></i>
                    <span>Sync P1-P12</span>
                </button>
            </form>

            <button type="button" @click="createModalOpen = true" class="px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold transition shadow-xs flex items-center gap-1.5">
                <i class="fas fa-plus text-[10px]"></i>
                <span>Add Pricing Plan</span>
            </button>
        </div>
    </div>

    <!-- Global KPI Cards -->
    <div class="owner-kpi-grid">
        <x-owner.kpi-card title="Total Plans" :value="$plans->count()" icon="fa-layer-group" theme="blue" />
        <x-owner.kpi-card title="Active Plans" :value="$activePlansCount" icon="fa-circle-check" theme="emerald" />
        <x-owner.kpi-card title="Subscribers" :value="$totalSubscribers" icon="fa-users" theme="cyan" />
        <x-owner.kpi-card title="Starting Price" :value="currency($minPrice)" icon="fa-arrow-down-short-wide" theme="slate" />
        <x-owner.kpi-card title="Top Price" :value="currency($maxPrice)" icon="fa-arrow-up-wide-short" theme="purple" />
        <x-owner.kpi-card title="Popular Plan" :value="$popularPlan->name ?? '—'" icon="fa-star" theme="amber" />
    </div>

    <!-- Search & Filter Controls -->
    <div class="bg-white p-2.5 rounded-xl border border-slate-200/90 shadow-2xs space-y-2.5">
        <div class="flex flex-col md:flex-row items-stretch md:items-center gap-2">
            <div class="relative flex-1 min-w-[200px]">
                <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-[11px]" :class="searchQuery ? 'text-cyan-600 font-bold' : 'text-slate-400'"></i>
                <input type="text" x-model="searchQuery" placeholder="Search plan code, name or capacity..." class="w-full h-[34px] pl-8 pr-7 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden">
                <button type="button" x-show="searchQuery" x-cloak @click="searchQuery = ''" class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-rose-600"><i class="fas fa-times"></i></button>
            </div>
            <select x-model="statusFilter" class="h-[34px] md:w-36 px-2.5 py-0 text-xs rounded-lg border border-slate-200 bg-slate-50 font-semibold text-slate-700">
                <option value="all">All Tiers</option>
                <option value="active">Active Only</option>
                <option value="inactive">Disabled Only</option>
            </select>
            <select class="h-[34px] md:w-28 px-2.5 py-0 text-xs rounded-lg border border-slate-200 bg-slate-50 font-semibold text-slate-700"><option>All Plans</option></select>
            <button type="button" @click="showMoreFilters = !showMoreFilters" class="h-[34px] py-0 px-3 rounded-lg border border-slate-200 bg-slate-50 text-slate-700 text-xs font-semibold flex items-center justify-center gap-1.5"><i class="fas" :class="showMoreFilters ? 'fa-chevron-up text-cyan-600' : 'fa-chevron-down text-slate-400'"></i> More Filters</button>
            <div class="flex items-center gap-1.5 md:ml-auto">
                <button type="button" class="h-[34px] bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-0 px-3 rounded-lg shadow-xs flex items-center justify-center gap-1.5"><i class="fas fa-filter text-[10px]"></i><span>Filter</span></button>
                <button type="button" @click.prevent="resetFilters()" class="h-[34px] bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-0 px-2.5 rounded-lg border border-slate-200/80 flex items-center justify-center gap-1"><i class="fas fa-rotate-left text-[10px]"></i><span>Reset</span></button>
            </div>
        </div>
        <div x-show="showMoreFilters" x-cloak x-transition class="pt-2 border-t border-slate-100 flex flex-wrap items-center gap-2">
            <span class="text-[10px] text-slate-500 font-medium">Use the table headers to sort plans by name, capacity, price, or subscribers.</span>
        </div>
    </div>

    <!-- High-Density Administrative Matrix Table with Column/Row Borders and Sortable Headers -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr class="bg-slate-100/90 text-slate-700 text-[11px] font-bold uppercase tracking-wider border-b border-slate-200 select-none">
                        <!-- Sortable Index / SL -->
                        <th @click="sortBy('sort_order')" class="py-2.5 px-3 border-r border-slate-200 cursor-pointer hover:bg-slate-200/80 transition whitespace-nowrap text-center w-12">
                            <div class="flex items-center justify-center gap-1">
                                <span>#</span>
                                <i class="fas text-[9px]" :class="sortColumn === 'sort_order' ? (sortAsc ? 'fa-arrow-up text-blue-600' : 'fa-arrow-down text-blue-600') : 'fa-sort text-slate-400'"></i>
                            </div>
                        </th>

                        <!-- Sortable Plan Name -->
                        <th @click="sortBy('name')" class="py-2.5 px-3 border-r border-slate-200 text-center cursor-pointer hover:bg-slate-200/80 transition whitespace-nowrap">
                            <div class="flex items-center justify-between gap-1.5">
                                <span>Plan Name</span>
                                <i class="fas text-[9px]" :class="sortColumn === 'name' ? (sortAsc ? 'fa-arrow-up text-blue-600' : 'fa-arrow-down text-blue-600') : 'fa-sort text-slate-400'"></i>
                            </div>
                        </th>

                        <!-- Sortable Customer Capacity -->
                        <th @click="sortBy('customer_limit')" class="py-2.5 px-3 border-r border-slate-200 text-center cursor-pointer hover:bg-slate-200/80 transition whitespace-nowrap">
                            <div class="flex items-center justify-end gap-1.5">
                                <span>Customer Cap</span>
                                <i class="fas text-[9px]" :class="sortColumn === 'customer_limit' ? (sortAsc ? 'fa-arrow-up text-blue-600' : 'fa-arrow-down text-blue-600') : 'fa-sort text-slate-400'"></i>
                            </div>
                        </th>

                        <!-- Sortable Standard Monthly -->
                        <th @click="sortBy('monthly_price')" class="py-2.5 px-3 border-r border-slate-200 text-center cursor-pointer hover:bg-slate-200/80 transition whitespace-nowrap">
                            <div class="flex items-center justify-end gap-1.5">
                                <span>Standard / Mo</span>
                                <i class="fas text-[9px]" :class="sortColumn === 'monthly_price' ? (sortAsc ? 'fa-arrow-up text-blue-600' : 'fa-arrow-down text-blue-600') : 'fa-sort text-slate-400'"></i>
                            </div>
                        </th>

                        <!-- Status Column -->
                        <th class="py-2.5 px-3 border-r border-slate-200 text-center whitespace-nowrap">
                            <span>Status</span>
                        </th>

                        <!-- Actions Column -->
                        <th class="py-2.5 px-3 text-center whitespace-nowrap">
                            <span>Action</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <template x-for="(plan, index) in filteredPlans" :key="plan.id">
                        <tr class="hover:bg-slate-50 transition" :class="plan.is_popular ? 'bg-amber-50/20' : ''">
                            
                            <!-- Index Number Cell -->
                            <td class="py-2 px-3 border-r border-slate-200 text-center font-mono font-bold text-slate-500 whitespace-nowrap">
                                <span x-text="index + 1"></span>
                            </td>

                            <!-- Plan Name Cell -->
                            <td class="py-2 px-3 border-r border-slate-200 whitespace-nowrap">
                                <span class="font-extrabold text-slate-900 text-xs font-mono" x-text="plan.name"></span>
                            </td>

                            <!-- Customer Capacity Cell -->
                            <td class="py-2 px-3 border-r border-slate-200 text-right font-mono font-extrabold text-blue-700 whitespace-nowrap">
                                <span x-text="Number(plan.customer_limit).toLocaleString() + ' Users'"></span>
                            </td>

                            <!-- Standard Monthly Price Cell -->
                            <td class="py-2 px-3 border-r border-slate-200 text-right font-mono font-extrabold text-slate-900 whitespace-nowrap">
                                <span x-text="formatCurrency(plan.monthly_price)"></span>
                            </td>

                            <!-- Status Cell -->
                            <td class="py-2 px-3 border-r border-slate-200 text-center whitespace-nowrap">
                                <form :action="'/owner/plans/' + plan.id + '/toggle'" method="POST" class="inline-block">
                                    @csrf
                                    <button type="submit" class="px-2 py-0.5 rounded text-[10px] font-bold transition"
                                            :class="plan.is_active ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200' : 'bg-slate-100 text-slate-500 hover:bg-slate-200 border border-slate-200'"
                                            x-text="plan.is_active ? 'Active' : 'Disabled'">
                                    </button>
                                </form>
                            </td>

                            <!-- Actions Cell (Details + Edit + Delete) -->
                            <td class="py-2 px-3 text-center whitespace-nowrap">
                                <button type="button" @click="toggleMenu(plan.id, $event)" class="w-7 h-7 rounded-lg border border-slate-200 bg-slate-50 text-slate-600 hover:bg-slate-100 inline-flex items-center justify-center" aria-label="Plan actions">
                                    <i class="fas fa-ellipsis-v text-[10px]"></i>
                                </button>
                                <template x-teleport="body">
                                    <div x-show="activeMenu === plan.id" x-cloak :style="menuPos" @click.outside="activeMenu = null" class="fixed z-50 w-44 rounded-lg border border-slate-200 bg-white p-1 text-xs shadow-lg">
                                        <button type="button" @click="activeMenu = null; openDetailsModal(plan)" class="flex w-full items-center gap-2 rounded-md px-2 py-1.5 hover:bg-slate-50"><i class="fas fa-eye fa-fw"></i> View Details</button>
                                        <button type="button" @click="activeMenu = null; openEditModal(plan)" class="flex w-full items-center gap-2 rounded-md px-2 py-1.5 hover:bg-slate-50"><i class="fas fa-edit fa-fw"></i> Edit Configuration</button>
                                        <button type="button" @click="activeMenu = null; confirmDeletePlan(plan)" class="flex w-full items-center gap-2 rounded-md border-t border-slate-100 px-2 py-1.5 text-rose-700 hover:bg-rose-50"><i class="fas fa-trash-alt fa-fw"></i> Delete Plan</button>
                                    </div>
                                </template>
                            </td>

                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 1. VIEW DETAILS MODAL -->
    <div x-show="detailsModalOpen" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div @click.away="detailsModalOpen = false" class="bg-white rounded-2xl border border-slate-200/80 shadow-2xl max-w-xl w-full p-5 space-y-4">
            <template x-if="selectedPlan">
                <div>
                    <!-- Header -->
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-1 rounded-md font-extrabold text-xs font-mono text-white"
                                  :class="selectedPlan.is_popular ? 'bg-amber-500' : 'bg-blue-600'"
                                  x-text="selectedPlan.code || ('P' + selectedPlan.id)">
                            </span>
                            <div>
                                <h3 class="font-bold text-sm text-slate-900" x-text="selectedPlan.name"></h3>
                                <span class="text-[10.5px] text-slate-400" x-text="(selectedPlan.trial_days || 14) + '-Day Free Trial Active'"></span>
                            </div>
                        </div>
                        <button type="button" @click="detailsModalOpen = false" class="text-slate-400 hover:text-slate-600 text-sm">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <!-- Pricing & Capacity Specs -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 pt-3">
                        <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200/70">
                            <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider block">Customer Limit</span>
                            <span class="text-xs font-extrabold text-blue-700 font-mono" x-text="Number(selectedPlan.customer_limit).toLocaleString() + ' Users'"></span>
                        </div>

                        <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200/70">
                            <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider block">OTC Setup Fee</span>
                            <span class="text-xs font-bold text-slate-800 font-mono" x-text="formatCurrency(selectedPlan.otc_charge)"></span>
                        </div>

                        <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200/70">
                            <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider block">Standard Monthly</span>
                            <span class="text-xs font-bold text-slate-900 font-mono" x-text="formatCurrency(selectedPlan.monthly_price)"></span>
                        </div>

                        <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200/70">
                            <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider block">Premium Monthly</span>
                            <span class="text-xs font-bold text-indigo-700 font-mono" x-text="formatCurrency(selectedPlan.premium_monthly_price || (selectedPlan.monthly_price * 1.2))"></span>
                        </div>
                    </div>

                    <!-- Hardware & Network Device Limits -->
                    <div class="p-3 bg-slate-50/80 rounded-xl border border-slate-200/70 space-y-2 mt-3 text-xs">
                        <span class="font-bold text-slate-800 text-[11px] block border-b border-slate-200/70 pb-1 flex items-center gap-1.5">
                            <i class="fas fa-server text-emerald-600"></i>
                            <span>Hardware & Network Limits</span>
                        </span>
                        <div class="grid grid-cols-3 gap-2 text-[11px]">
                            <div>
                                <span class="text-slate-400 block text-[10px]">OLT Devices</span>
                                <span class="font-bold text-purple-700 font-mono" x-text="(selectedPlan.olt_limit || 1) + ' Device(s)'"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[10px]">MikroTik Routers</span>
                                <span class="font-bold text-emerald-700 font-mono" x-text="selectedPlan.mikrotik_limit + ' Router(s)'"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[10px]">Reseller Sub-Accounts</span>
                                <span class="font-bold text-slate-700 font-mono" x-text="selectedPlan.reseller_limit + ' Reseller(s)'"></span>
                            </div>
                        </div>

                        <!-- Network Sub-Engine Entitlements -->
                        <div class="pt-2 border-t border-slate-200/70 flex flex-wrap items-center gap-1.5">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold"
                                  :class="selectedPlan.allow_radius ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-400 line-through'">
                                <i class="fas fa-key text-[9px] mr-1"></i> RADIUS AAA
                            </span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold"
                                  :class="selectedPlan.allow_wireguard ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-400 line-through'">
                                <i class="fas fa-shield-virus text-[9px] mr-1"></i> WireGuard Tunnel
                            </span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold"
                                  :class="selectedPlan.allow_snmp_monitoring ? 'bg-violet-100 text-violet-800' : 'bg-slate-100 text-slate-400 line-through'">
                                <i class="fas fa-chart-line text-[9px] mr-1"></i> SNMP Polling
                            </span>
                        </div>
                    </div>

                    <!-- Feature Capabilities -->
                    <div class="space-y-1.5 pt-3">
                        <span class="font-bold text-xs text-slate-800 block">Package Features & Capabilities:</span>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5">
                            <template x-for="feat in (selectedPlan.features || [])" :key="feat">
                                <div class="flex items-center gap-1.5 text-xs text-slate-700 bg-slate-50 px-2 py-1 rounded-lg border border-slate-200/60">
                                    <i class="fas fa-check-circle text-emerald-600 text-[10px]"></i>
                                    <span x-text="feat" class="truncate"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Footer Action -->
                    <div class="flex items-center justify-between pt-4 border-t border-slate-100 mt-4">
                        <span class="text-[11px] text-slate-500 font-semibold" x-text="(selectedPlan.tenants_count || 0) + ' ISP Tenants Subscribed'"></span>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="detailsModalOpen = false; openEditModal(selectedPlan);" class="px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs transition flex items-center gap-1">
                                <i class="fas fa-edit text-[10px]"></i>
                                <span>Edit This Plan</span>
                            </button>
                            <button type="button" @click="detailsModalOpen = false" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- 2. CREATE TIER MODAL -->
    <div x-show="createModalOpen" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div @click.away="createModalOpen = false" class="bg-white rounded-2xl border border-slate-200/80 shadow-2xl max-w-2xl w-full p-5 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-xs">
                        <i class="fas fa-plus"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-sm text-slate-900">Create New Pricing Plan</h3>
                        <p class="text-[11px] text-slate-500">Define customer limits, OTC charges, and hardware capacities</p>
                    </div>
                </div>
                <button type="button" @click="createModalOpen = false" class="text-slate-400 hover:text-slate-600 text-sm">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form action="{{ route('owner.plans.store') }}" method="POST" class="space-y-4">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-[10.5px] font-semibold text-slate-600 mb-1">Tier Code (e.g. P13) *</label>
                        <input type="text" name="code" placeholder="P13" class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50 font-mono">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-[10.5px] font-semibold text-slate-600 mb-1">Plan Name *</label>
                        <input type="text" name="name" required placeholder="e.g. P13 - Ultra Enterprise" class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50">
                    </div>

                    <div>
                        <label class="block text-[10.5px] font-semibold text-slate-600 mb-1">Customer Capacity *</label>
                        <input type="number" name="customer_limit" required value="6000" class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50 font-mono font-bold text-blue-700">
                    </div>

                    <div>
                        <label class="block text-[10.5px] font-semibold text-slate-600 mb-1">OTC Setup Charge (BDT) *</label>
                        <input type="number" name="otc_charge" required value="10000" class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50 font-mono font-bold">
                    </div>

                    <div>
                        <label class="block text-[10.5px] font-semibold text-slate-600 mb-1">Standard Monthly (BDT) *</label>
                        <input type="number" name="monthly_price" required value="9000" class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50 font-mono font-bold text-slate-900">
                    </div>

                    <div>
                        <label class="block text-[10.5px] font-semibold text-slate-600 mb-1">Premium Monthly (BDT)</label>
                        <input type="number" name="premium_monthly_price" value="10000" class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50 font-mono font-bold text-indigo-700">
                    </div>

                    <div>
                        <label class="block text-[10.5px] font-semibold text-slate-600 mb-1">Yearly Price (BDT)</label>
                        <input type="number" name="yearly_price" value="90000" class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50 font-mono">
                    </div>

                    <div>
                        <label class="block text-[10.5px] font-semibold text-slate-600 mb-1">OLT Limit *</label>
                        <input type="number" name="olt_limit" required value="15" class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50 font-mono font-bold text-purple-700">
                    </div>

                    <div>
                        <label class="block text-[10.5px] font-semibold text-slate-600 mb-1">MikroTik Limit *</label>
                        <input type="number" name="mikrotik_limit" required value="25" class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50 font-mono font-bold text-emerald-700">
                    </div>

                    <div>
                        <label class="block text-[10.5px] font-semibold text-slate-600 mb-1">Reseller Limit *</label>
                        <input type="number" name="reseller_limit" required value="100" class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50 font-mono">
                    </div>

                    <div>
                        <label class="block text-[10.5px] font-semibold text-slate-600 mb-1">Trial Period (Days)</label>
                        <input type="number" name="trial_days" value="14" class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50 font-mono">
                    </div>

                    <div class="sm:col-span-3">
                        <label class="flex items-center gap-1.5 cursor-pointer text-xs font-semibold text-slate-700">
                            <input type="checkbox" name="is_popular" value="1" class="w-4 h-4 rounded text-amber-500 focus:ring-0">
                            <span>Mark as Popular ⭐ Plan</span>
                        </label>
                    </div>

                    <!-- Network Sub-Engine Quotas -->
                    <div class="sm:col-span-3 p-2.5 bg-slate-50 rounded-lg border border-slate-200/80 space-y-1.5">
                        <span class="font-bold text-slate-800 text-[10.5px] block">Carrier Network Sub-Engine Access:</span>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs">
                            <label class="flex items-center gap-1.5 cursor-pointer">
                                <input type="checkbox" name="allow_radius" value="1" class="rounded text-blue-600 focus:ring-0">
                                <span class="font-medium text-slate-700">Enable RADIUS AAA</span>
                            </label>
                            <label class="flex items-center gap-1.5 cursor-pointer">
                                <input type="checkbox" name="allow_wireguard" value="1" class="rounded text-blue-600 focus:ring-0">
                                <span class="font-medium text-slate-700">Enable WireGuard VPN</span>
                            </label>
                            <label class="flex items-center gap-1.5 cursor-pointer">
                                <input type="checkbox" name="allow_snmp_monitoring" value="1" checked class="rounded text-blue-600 focus:ring-0">
                                <span class="font-medium text-slate-700">Enable SNMP Polling</span>
                            </label>
                        </div>
                    </div>

                    <div class="sm:col-span-3">
                        <label class="block text-[10.5px] font-semibold text-slate-600 mb-1">Features (Comma Separated)</label>
                        <input type="text" name="features" placeholder="15 OLT Devices, 25 MikroTik Routers, SMS Routing, White-label" class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="createModalOpen = false" class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs shadow-xs transition flex items-center gap-1.5">
                        <i class="fas fa-save text-[10px]"></i>
                        <span>Save Pricing Tier</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 3. EDIT TIER MODAL -->
    <div x-show="editModalOpen" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div @click.away="editModalOpen = false" class="bg-white rounded-2xl border border-slate-200/80 shadow-2xl max-w-2xl w-full p-5 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-xs">
                        <i class="fas fa-edit"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-sm text-slate-900">Edit Pricing Plan: <span x-text="editData.name" class="text-blue-600"></span></h3>
                        <p class="text-[11px] text-slate-500">Update capacity limits, pricing rates, and feature list</p>
                    </div>
                </div>
                <button type="button" @click="editModalOpen = false" class="text-slate-400 hover:text-slate-600 text-sm">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form :action="editData.action_url" method="POST" class="space-y-4">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-[10.5px] font-semibold text-slate-600 mb-1">Tier Code *</label>
                        <input type="text" name="code" x-model="editData.code" class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50 font-mono">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-[10.5px] font-semibold text-slate-600 mb-1">Plan Name *</label>
                        <input type="text" name="name" required x-model="editData.name" class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50">
                    </div>

                    <div>
                        <label class="block text-[10.5px] font-semibold text-slate-600 mb-1">Customer Capacity *</label>
                        <input type="number" name="customer_limit" required x-model="editData.customer_limit" class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50 font-mono font-bold text-blue-700">
                    </div>

                    <div>
                        <label class="block text-[10.5px] font-semibold text-slate-600 mb-1">OTC Setup Charge (BDT) *</label>
                        <input type="number" name="otc_charge" required x-model="editData.otc_charge" class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50 font-mono font-bold">
                    </div>

                    <div>
                        <label class="block text-[10.5px] font-semibold text-slate-600 mb-1">Standard Monthly (BDT) *</label>
                        <input type="number" name="monthly_price" required x-model="editData.monthly_price" class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50 font-mono font-bold text-slate-900">
                    </div>

                    <div>
                        <label class="block text-[10.5px] font-semibold text-slate-600 mb-1">Premium Monthly (BDT)</label>
                        <input type="number" name="premium_monthly_price" x-model="editData.premium_monthly_price" class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50 font-mono font-bold text-indigo-700">
                    </div>

                    <div>
                        <label class="block text-[10.5px] font-semibold text-slate-600 mb-1">Yearly Price (BDT)</label>
                        <input type="number" name="yearly_price" x-model="editData.yearly_price" class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50 font-mono">
                    </div>

                    <div>
                        <label class="block text-[10.5px] font-semibold text-slate-600 mb-1">OLT Limit *</label>
                        <input type="number" name="olt_limit" required x-model="editData.olt_limit" class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50 font-mono font-bold text-purple-700">
                    </div>

                    <div>
                        <label class="block text-[10.5px] font-semibold text-slate-600 mb-1">MikroTik Limit *</label>
                        <input type="number" name="mikrotik_limit" required x-model="editData.mikrotik_limit" class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50 font-mono font-bold text-emerald-700">
                    </div>

                    <div>
                        <label class="block text-[10.5px] font-semibold text-slate-600 mb-1">Reseller Limit *</label>
                        <input type="number" name="reseller_limit" required x-model="editData.reseller_limit" class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50 font-mono">
                    </div>

                    <div>
                        <label class="block text-[10.5px] font-semibold text-slate-600 mb-1">Trial Period (Days)</label>
                        <input type="number" name="trial_days" x-model="editData.trial_days" class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50 font-mono">
                    </div>

                    <div class="sm:col-span-3">
                        <label class="flex items-center gap-1.5 cursor-pointer text-xs font-semibold text-slate-700">
                            <input type="checkbox" name="is_popular" value="1" x-model="editData.is_popular" class="w-4 h-4 rounded text-amber-500 focus:ring-0">
                            <span>Mark as Popular ⭐ Plan</span>
                        </label>
                    </div>

                    <!-- Network Sub-Engine Quotas -->
                    <div class="sm:col-span-3 p-2.5 bg-slate-50 rounded-lg border border-slate-200/80 space-y-1.5">
                        <span class="font-bold text-slate-800 text-[10.5px] block">Carrier Network Sub-Engine Access:</span>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs">
                            <label class="flex items-center gap-1.5 cursor-pointer">
                                <input type="checkbox" name="allow_radius" value="1" x-model="editData.allow_radius" class="rounded text-blue-600 focus:ring-0">
                                <span class="font-medium text-slate-700">Enable RADIUS AAA</span>
                            </label>
                            <label class="flex items-center gap-1.5 cursor-pointer">
                                <input type="checkbox" name="allow_wireguard" value="1" x-model="editData.allow_wireguard" class="rounded text-blue-600 focus:ring-0">
                                <span class="font-medium text-slate-700">Enable WireGuard VPN</span>
                            </label>
                            <label class="flex items-center gap-1.5 cursor-pointer">
                                <input type="checkbox" name="allow_snmp_monitoring" value="1" x-model="editData.allow_snmp_monitoring" class="rounded text-blue-600 focus:ring-0">
                                <span class="font-medium text-slate-700">Enable SNMP Polling</span>
                            </label>
                        </div>
                    </div>

                    <div class="sm:col-span-3">
                        <label class="block text-[10.5px] font-semibold text-slate-600 mb-1">Features List (Comma Separated)</label>
                        <input type="text" name="features" x-model="editData.features_str" class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="editModalOpen = false" class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs shadow-xs transition flex items-center gap-1.5">
                        <i class="fas fa-save text-[10px]"></i>
                        <span>Update Plan Changes</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    function confirmDeletePlan(plan) {
        Swal.fire({
            title: 'Delete Pricing Plan?',
            html: '<p class="text-left text-xs text-slate-600">This will permanently delete <strong>' + String(plan.name).replace(/[&<>"']/g, function (char) { return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[char]; }) + '</strong>.</p>',
            icon: 'warning',
            iconColor: '#e11d48',
            showCancelButton: true,
            confirmButtonColor: '#e11d48',
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="fas fa-trash-alt mr-1"></i> Yes, Delete Plan',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            customClass: {
                popup: 'rounded-2xl shadow-2xl border border-slate-200',
                title: 'text-sm font-bold text-slate-900',
                confirmButton: 'rounded-lg text-xs font-bold px-4 py-2 shadow-xs',
                cancelButton: 'rounded-lg text-xs font-semibold px-4 py-2 bg-slate-100 text-slate-700 border-0'
            }
        }).then(function (result) {
            if (!result.isConfirmed) return;

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/owner/plans/' + plan.id;
            form.innerHTML = '<input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="_method" value="DELETE">';
            document.body.appendChild(form);
            form.submit();
        });
    }
</script>
@endpush
