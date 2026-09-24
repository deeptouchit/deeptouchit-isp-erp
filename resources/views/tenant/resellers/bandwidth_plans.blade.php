@extends('tenant.layouts.app')

@section('title', 'Bandwidth Rate Plans - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
    {{-- Page specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="bandwidthPlansManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">

    <!-- 1. Top Header Bar (Strictly Icon + Title + Action Button ONLY - AGENTS.md Rule 2.A) -->
    <div class="flex items-center justify-between bg-white px-3.5 py-2.5 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-sm shadow-2xs flex-shrink-0">
                <i class="fas fa-tags"></i>
            </div>
            <h1 class="text-xs font-bold text-slate-800 tracking-tight">Bandwidth Rate Plans</h1>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" 
                    @click="openCreateModal()"
                    class="px-3.5 py-1.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-plus text-xs"></i>
                <span>Create Rate Slab</span>
            </button>
        </div>
    </div>

    <!-- 2. KPI Summary Strip (Strictly 6 Cards - AGENTS.md Rule 2.B & Rule 6) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        <!-- Card 1: Total Rate Plans -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Total Slabs</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-slate-900 truncate">{{ number_format($totalPlansCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-cyan-50 text-cyan-600 border-cyan-100 flex items-center justify-center">
                <i class="fas fa-layer-group"></i>
            </div>
        </div>

        <!-- Card 2: Active Plans -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Active Slabs</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-emerald-700 truncate">{{ number_format($activePlansCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-emerald-50 text-emerald-600 border-emerald-100 flex items-center justify-center">
                <i class="fas fa-circle-check"></i>
            </div>
        </div>

        <!-- Card 3: Avg Global Rate -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Avg Global</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-blue-700 truncate">@currency($avgGlobalRate)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-blue-50 text-blue-600 border-blue-100 flex items-center justify-center">
                <i class="fas fa-globe"></i>
            </div>
        </div>

        <!-- Card 4: Avg CDN Rate -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Avg CDN</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-purple-700 truncate">@currency($avgCdnRate)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-purple-50 text-purple-600 border-purple-100 flex items-center justify-center">
                <i class="fas fa-cloud-arrow-down"></i>
            </div>
        </div>

        <!-- Card 5: Avg BDIX Rate -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Avg BDIX</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-indigo-700 truncate">@currency($avgBdixRate)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-indigo-50 text-indigo-600 border-indigo-100 flex items-center justify-center">
                <i class="fas fa-bolt"></i>
            </div>
        </div>

        <!-- Card 6: Assigned Lines -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Assigned Lines</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-cyan-800 truncate">{{ number_format($totalAssignedResellers) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-cyan-50 text-cyan-600 border-cyan-100 flex items-center justify-center">
                <i class="fas fa-users-gear"></i>
            </div>
        </div>
    </div>

    <!-- 3. Search & Multi-Filter Toolbar (Strict Universal Standard - AGENTS.md Rule 2.C) -->
    <div class="bg-white p-2.5 rounded-xl border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('tenant.resellers.bandwidth-plans') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-12 gap-2 items-center">
            
            <!-- Search Input (MD: 7 cols) -->
            <div class="md:col-span-7 relative">
                <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                <input type="text" 
                       name="search" 
                       value="{{ request('search') }}" 
                       placeholder="Search Rate Slab Name, Code..." 
                       class="w-full pl-8 pr-3 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50 focus:bg-white focus:border-cyan-500 transition">
            </div>

            <!-- Status Filter (MD: 3 cols) -->
            <div class="md:col-span-3">
                <select name="status" 
                        onchange="this.form.submit()" 
                        class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50 focus:bg-white focus:border-cyan-500">
                    <option value="">All Statuses</option>
                    <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            <!-- Strict Filter & Reset Sequence (MD: 2 cols - AGENTS.md Rule 2.C) -->
            <div class="flex items-center gap-1.5 md:col-span-2">
                <button type="submit" 
                        class="w-1/2 bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-1.5 px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer" 
                        title="Apply Filters">
                    <i class="fas fa-filter text-[10px]"></i>
                    <span>Filter</span>
                </button>
                <a href="{{ route('tenant.resellers.bandwidth-plans') }}" 
                   class="w-1/2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-1.5 px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer" 
                   title="Reset Filters">
                    <i class="fas fa-rotate-left text-[10px]"></i>
                    <span>Reset</span>
                </a>
            </div>
        </form>
    </div>

    <!-- 4. Master Table (<table class="saas-table"> - AGENTS.md Rule 2.D) -->
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-10 text-center">#</th>
                        <th>Rate Slab Name</th>
                        <th class="w-36 text-center font-mono">Volume Slab (Range)</th>
                        <th class="text-right w-28">Global Internet</th>
                        <th class="text-right w-24">CDN / Cache</th>
                        <th class="text-right w-24">BDIX Peering</th>
                        <th class="text-right w-24">GGC (Google)</th>
                        <th class="text-right w-24">FNA (Meta)</th>
                        <th class="text-right w-24">Others Cache</th>
                        <th class="text-center w-20">Status</th>
                        <th class="w-10 text-center no-sort">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($plans as $index => $plan)
                        <tr>
                            <!-- 1. Index -->
                            <td class="text-center font-mono text-slate-400">
                                {{ $plans->firstItem() + $index }}
                            </td>

                            <!-- 2. Rate Plan Name -->
                            <td class="font-medium text-slate-900">
                                <button type="button" 
                                        @click="openDetailsModal({{ Js::from($plan) }})" 
                                        class="hover:underline text-cyan-700 hover:text-cyan-800 font-semibold cursor-pointer text-left">
                                    {{ $plan->name }}
                                </button>
                            </td>

                            <!-- 3. Bandwidth Volume Slab Range -->
                            <td class="text-center font-mono text-xs font-semibold text-slate-700">
                                {{ $plan->formatted_slab_range }}
                            </td>

                            <!-- 4. Global Rate -->
                            <td class="text-right font-mono font-bold text-blue-700">
                                @currency($plan->global_rate_per_mbps)
                            </td>

                            <!-- 5. CDN Rate -->
                            <td class="text-right font-mono font-bold text-purple-700">
                                @currency($plan->cdn_rate_per_mbps)
                            </td>

                            <!-- 6. BDIX Rate -->
                            <td class="text-right font-mono font-bold text-indigo-700">
                                @currency($plan->bdix_rate_per_mbps)
                            </td>

                            <!-- 7. GGC Rate -->
                            <td class="text-right font-mono font-bold text-amber-700">
                                @currency($plan->ggc_rate_per_mbps)
                            </td>

                            <!-- 8. FNA Rate -->
                            <td class="text-right font-mono font-bold text-sky-700">
                                @currency($plan->fna_rate_per_mbps)
                            </td>

                            <!-- 9. Others Rate -->
                            <td class="text-right font-mono font-bold text-emerald-700">
                                @currency($plan->others_rate_per_mbps)
                            </td>

                            <!-- 10. Status -->
                            <td class="text-center">
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-[10px] font-semibold border {{ $plan->status_badge['class'] }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $plan->status_badge['dot'] }}"></span>
                                    <span>{{ $plan->status_badge['label'] }}</span>
                                </span>
                            </td>

                            <!-- 11. Action (3-Dot Menu) -->
                            <td class="text-center">
                                <button type="button" 
                                        @click.stop="toggleMenu({{ Js::from($plan) }}, $event)"
                                        class="w-7 h-7 rounded-lg hover:bg-slate-100 text-slate-500 hover:text-slate-800 transition flex items-center justify-center mx-auto cursor-pointer"
                                        title="Actions">
                                    <i class="fas fa-ellipsis-v text-[10px]"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center py-8 text-slate-400 text-xs">
                                <i class="fas fa-layer-group text-3xl mb-2 block text-slate-300"></i>
                                <span>No Bandwidth Rate Plans found.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($plans->hasPages())
            <div class="px-3.5 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between text-xs text-slate-600">
                <div>
                    Showing {{ $plans->firstItem() }} to {{ $plans->lastItem() }} of {{ $plans->total() }} entries
                </div>
                <div>
                    {{ $plans->links() }}
                </div>
            </div>
        @endif
    </div>

    <!-- 5. Floating Action Dropdown Menu (AGENTS.md Rule 2.E) -->
    <div x-show="activeMenu !== null" 
         x-cloak 
         @click.outside="activeMenu = null"
         :style="menuPos"
         class="fixed z-50 w-44 bg-white rounded-xl border border-slate-200 shadow-xl py-1 text-xs text-slate-700 font-medium space-y-0.5"
         style="display: none;">
        
        <button type="button" 
                @click="const p = activeMenu; activeMenu = null; openDetailsModal(p)" 
                class="w-full px-3 py-1.5 text-left hover:bg-slate-50 hover:text-cyan-700 flex items-center gap-2 cursor-pointer transition">
            <i class="fas fa-circle-info text-cyan-600 w-4"></i>
            <span>View Details</span>
        </button>

        <button type="button" 
                @click="const p = activeMenu; activeMenu = null; openEditModal(p)" 
                class="w-full px-3 py-1.5 text-left hover:bg-slate-50 hover:text-cyan-700 flex items-center gap-2 cursor-pointer transition">
            <i class="fas fa-pen-to-square text-cyan-600 w-4"></i>
            <span>Edit Plan</span>
        </button>

        <button type="button" 
                @click="const p = activeMenu; activeMenu = null; toggleStatus(p)" 
                class="w-full px-3 py-1.5 text-left hover:bg-slate-50 flex items-center gap-2 cursor-pointer transition"
                :class="activeMenu?.is_active ? 'text-amber-600 hover:text-amber-700' : 'text-emerald-600 hover:text-emerald-700'">
            <i class="fas w-4" :class="activeMenu?.is_active ? 'fa-ban text-amber-600' : 'fa-check text-emerald-600'"></i>
            <span x-text="activeMenu?.is_active ? 'Disable Plan' : 'Enable Plan'"></span>
        </button>

        <button type="button" 
                @click="const p = activeMenu; activeMenu = null; deletePlan(p)" 
                class="w-full px-3 py-1.5 text-left hover:bg-rose-50 hover:text-rose-700 text-rose-600 flex items-center gap-2 cursor-pointer transition border-t border-slate-100">
            <i class="fas fa-trash-can text-rose-600 w-4"></i>
            <span>Delete Plan</span>
        </button>
    </div>

    <!-- 6. Add/Edit Modal (AGENTS.md Rule 3) -->
    <div x-show="modal.open" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50 overflow-y-auto"
         style="display: none;"
         x-cloak>
        
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-xl overflow-hidden my-6"
             @click.outside="modal.open = false">
            
            <!-- Soft Natural Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-tags"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800" x-text="modal.isEdit ? 'Edit Bandwidth Rate Slab' : 'Create Bandwidth Rate Slab'"></h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">Define volume tier and wholesale tariff per Mbps for each traffic category</p>
                    </div>
                </div>
                <button type="button" @click="modal.open = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <!-- Form -->
            <form @submit.prevent="submitForm()" class="p-4 space-y-3.5 text-xs">
                <!-- Row 1: Name & Code -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="sm:col-span-2">
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            Slab / Plan Name <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" 
                               x-model="form.name" 
                               required 
                               placeholder="e.g. Slab 1 (1 - 500 Mbps)" 
                               class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            Slab Code
                        </label>
                        <input type="text" 
                               x-model="form.code" 
                               placeholder="e.g. SLAB-500M" 
                               class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono px-3 py-1.5 focus:bg-white focus:border-cyan-500">
                    </div>
                </div>

                <!-- Row 2: Bandwidth Volume Slab (Min - Max Mbps) -->
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-2">
                    <span class="text-[11px] font-bold text-slate-800 block">Bandwidth Volume Slab (Min - Max)</span>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-600 mb-0.5">
                                Min Bandwidth (Mbps)
                            </label>
                            <input type="number" 
                                   step="1" 
                                   min="0" 
                                   x-model="form.min_bandwidth_mbps" 
                                   placeholder="e.g. 1 or 500" 
                                   class="w-full bg-white border border-slate-200 rounded-lg text-xs font-mono px-2.5 py-1.5 focus:border-cyan-500">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-600 mb-0.5">
                                Max Bandwidth (Mbps) <span class="text-[9px] text-slate-400 font-normal">(Leave blank for no upper limit)</span>
                            </label>
                            <input type="number" 
                                   step="1" 
                                   min="0" 
                                   x-model="form.max_bandwidth_mbps" 
                                   placeholder="e.g. 500, 1000 (1GB), 2000 (2GB)" 
                                   class="w-full bg-white border border-slate-200 rounded-lg text-xs font-mono px-2.5 py-1.5 focus:border-cyan-500">
                        </div>
                    </div>
                </div>

                <!-- Row 3: 6 Traffic Category Rates (Rate per Mbps) -->
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-2">
                    <span class="text-[11px] font-bold text-slate-800 block">Traffic Category Rates ({{ $currencySymbol ?? '৳' }} per Mbps / Month)</span>
                    
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                        <!-- 1. Global Internet -->
                        <div>
                            <label class="block text-[10px] font-semibold text-blue-700 mb-0.5">
                                Global Internet <span class="text-rose-500">*</span>
                            </label>
                            <input type="number" step="0.01" min="0" x-model="form.global_rate_per_mbps" required placeholder="e.g. 280" class="w-full bg-white border border-slate-200 rounded-lg text-xs font-mono px-2.5 py-1.5 focus:border-cyan-500">
                        </div>

                        <!-- 2. CDN / Cache -->
                        <div>
                            <label class="block text-[10px] font-semibold text-purple-700 mb-0.5">
                                CDN / Cache
                            </label>
                            <input type="number" step="0.01" min="0" x-model="form.cdn_rate_per_mbps" placeholder="e.g. 35" class="w-full bg-white border border-slate-200 rounded-lg text-xs font-mono px-2.5 py-1.5 focus:border-cyan-500">
                        </div>

                        <!-- 3. BDIX Peering -->
                        <div>
                            <label class="block text-[10px] font-semibold text-indigo-700 mb-0.5">
                                BDIX Peering
                            </label>
                            <input type="number" step="0.01" min="0" x-model="form.bdix_rate_per_mbps" placeholder="e.g. 45" class="w-full bg-white border border-slate-200 rounded-lg text-xs font-mono px-2.5 py-1.5 focus:border-cyan-500">
                        </div>

                        <!-- 4. GGC Cache -->
                        <div>
                            <label class="block text-[10px] font-semibold text-amber-700 mb-0.5">
                                GGC (Google Cache)
                            </label>
                            <input type="number" step="0.01" min="0" x-model="form.ggc_rate_per_mbps" placeholder="e.g. 25" class="w-full bg-white border border-slate-200 rounded-lg text-xs font-mono px-2.5 py-1.5 focus:border-cyan-500">
                        </div>

                        <!-- 5. FNA Cache -->
                        <div>
                            <label class="block text-[10px] font-semibold text-sky-700 mb-0.5">
                                FNA (Meta / FB)
                            </label>
                            <input type="number" step="0.01" min="0" x-model="form.fna_rate_per_mbps" placeholder="e.g. 25" class="w-full bg-white border border-slate-200 rounded-lg text-xs font-mono px-2.5 py-1.5 focus:border-cyan-500">
                        </div>

                        <!-- 6. OTHERS Cache -->
                        <div>
                            <label class="block text-[10px] font-semibold text-emerald-700 mb-0.5">
                                OTHERS Cache
                            </label>
                            <input type="number" step="0.01" min="0" x-model="form.others_rate_per_mbps" placeholder="e.g. 20" class="w-full bg-white border border-slate-200 rounded-lg text-xs font-mono px-2.5 py-1.5 focus:border-cyan-500">
                        </div>
                    </div>
                </div>

                <!-- Row 4: Notes -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">Notes / Description</label>
                    <input type="text" x-model="form.notes" placeholder="e.g. Wholesale bandwidth pricing slab for 1 - 500 Mbps volume" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500">
                </div>

                <!-- Row 5: Active Status Toggle -->
                <div class="flex items-center gap-2 pt-1">
                    <input type="checkbox" id="plan_active" x-model="form.is_active" class="w-4 h-4 text-cyan-600 rounded border-slate-300 focus:ring-cyan-500">
                    <label for="plan_active" class="text-xs font-medium text-slate-700 cursor-pointer">Active and Available for Bandwidth Allocation</label>
                </div>

                <!-- Footer Actions -->
                <div class="pt-2 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="modal.open = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" 
                            :disabled="submitting"
                            class="bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition inline-flex items-center gap-1.5 disabled:opacity-50 cursor-pointer">
                        <i class="fas fa-check text-xs" :class="{ 'fa-spin fa-spinner': submitting }"></i>
                        <span x-text="submitting ? 'Saving...' : (modal.isEdit ? 'Update Rate Slab' : 'Save Rate Slab')"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 7. View Details Modal -->
    <div x-show="detailsModal.open" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50 overflow-y-auto"
         style="display: none;"
         x-cloak>
        
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden my-6"
             @click.outside="detailsModal.open = false">
            
            <!-- Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-tags"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800" x-text="detailsModal.data?.name"></h3>
                        <p class="text-[10.5px] text-slate-500 font-normal" x-text="detailsModal.data?.code || 'Bandwidth Rate Slab Details'"></p>
                    </div>
                </div>
                <button type="button" @click="detailsModal.open = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <!-- Body -->
            <div class="p-4 space-y-3.5 text-xs">
                <!-- Volume Slab Badge Card -->
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] text-slate-500 uppercase font-semibold block">Volume Tier Range</span>
                        <span class="text-sm font-bold font-mono text-slate-800 mt-0.5 block" x-text="detailsModal.data?.formatted_slab_range"></span>
                    </div>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[10px] font-semibold border"
                          :class="detailsModal.data?.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-600 border-slate-200'">
                        <span class="w-1.5 h-1.5 rounded-full" :class="detailsModal.data?.is_active ? 'bg-emerald-500' : 'bg-slate-400'"></span>
                        <span x-text="detailsModal.data?.is_active ? 'Active' : 'Inactive'"></span>
                    </span>
                </div>

                <!-- 6 Category Rates Breakdown -->
                <div>
                    <span class="text-[11px] font-bold text-slate-800 mb-2 block">Traffic Rates Breakdown (Per Mbps)</span>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                        <div class="p-2.5 bg-blue-50/50 rounded-lg border border-blue-100">
                            <span class="text-[10px] text-blue-700 uppercase font-semibold block">Global Internet</span>
                            <span class="text-xs font-bold font-mono text-blue-900 mt-0.5 block" x-text="formatCurrency(detailsModal.data?.global_rate_per_mbps)"></span>
                        </div>
                        <div class="p-2.5 bg-purple-50/50 rounded-lg border border-purple-100">
                            <span class="text-[10px] text-purple-700 uppercase font-semibold block">CDN / Cache</span>
                            <span class="text-xs font-bold font-mono text-purple-900 mt-0.5 block" x-text="formatCurrency(detailsModal.data?.cdn_rate_per_mbps)"></span>
                        </div>
                        <div class="p-2.5 bg-indigo-50/50 rounded-lg border border-indigo-100">
                            <span class="text-[10px] text-indigo-700 uppercase font-semibold block">BDIX Peering</span>
                            <span class="text-xs font-bold font-mono text-indigo-900 mt-0.5 block" x-text="formatCurrency(detailsModal.data?.bdix_rate_per_mbps)"></span>
                        </div>
                        <div class="p-2.5 bg-amber-50/50 rounded-lg border border-amber-100">
                            <span class="text-[10px] text-amber-700 uppercase font-semibold block">GGC (Google)</span>
                            <span class="text-xs font-bold font-mono text-amber-900 mt-0.5 block" x-text="formatCurrency(detailsModal.data?.ggc_rate_per_mbps)"></span>
                        </div>
                        <div class="p-2.5 bg-sky-50/50 rounded-lg border border-sky-100">
                            <span class="text-[10px] text-sky-700 uppercase font-semibold block">FNA (Meta / FB)</span>
                            <span class="text-xs font-bold font-mono text-sky-900 mt-0.5 block" x-text="formatCurrency(detailsModal.data?.fna_rate_per_mbps)"></span>
                        </div>
                        <div class="p-2.5 bg-emerald-50/50 rounded-lg border border-emerald-100">
                            <span class="text-[10px] text-emerald-700 uppercase font-semibold block">OTHERS Cache</span>
                            <span class="text-xs font-bold font-mono text-emerald-900 mt-0.5 block" x-text="formatCurrency(detailsModal.data?.others_rate_per_mbps)"></span>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <template x-if="detailsModal.data?.notes">
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200 text-slate-700">
                        <span class="text-[10px] font-semibold text-slate-500 uppercase block mb-0.5">Notes</span>
                        <span class="text-xs" x-text="detailsModal.data?.notes"></span>
                    </div>
                </template>
            </div>

            <!-- Footer -->
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                <button type="button" 
                        @click="const p = detailsModal.data; detailsModal.open = false; openEditModal(p)"
                        class="bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs px-3.5 py-1.5 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                    <i class="fas fa-pen-to-square text-xs"></i>
                    <span>Edit This Slab</span>
                </button>
                <button type="button" @click="detailsModal.open = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function bandwidthPlansManager() {
    return {
        activeMenu: null,
        menuPos: { top: '0px', right: '10px', left: 'auto', bottom: 'auto' },
        submitting: false,
        currencySymbol: '{{ $currencySymbol ?? '৳' }}',

        modal: {
            open: false,
            isEdit: false,
            id: null
        },

        detailsModal: {
            open: false,
            data: null
        },

        form: {
            name: '',
            code: '',
            min_bandwidth_mbps: 0,
            max_bandwidth_mbps: null,
            global_rate_per_mbps: 0,
            cdn_rate_per_mbps: 0,
            bdix_rate_per_mbps: 0,
            ggc_rate_per_mbps: 0,
            fna_rate_per_mbps: 0,
            others_rate_per_mbps: 0,
            is_active: true,
            notes: ''
        },

        formatCurrency(amount) {
            const val = parseFloat(amount || 0);
            return `${this.currencySymbol} ${val.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        },

        toggleMenu(item, event) {
            if (this.activeMenu?.id === item.id) {
                this.activeMenu = null;
                return;
            }
            this.activeMenu = item;
            const rect = event.currentTarget.getBoundingClientRect();
            const dropdownHeight = 160;
            const right = Math.max(10, window.innerWidth - rect.right);
            let top = Math.round(rect.bottom) + 2;
            let bottom = 'auto';

            if (top + dropdownHeight > window.innerHeight) {
                top = 'auto';
                bottom = Math.max(10, window.innerHeight - Math.round(rect.top) + 2) + 'px';
            } else {
                top = `${top}px`;
            }

            this.menuPos = {
                top: top,
                bottom: bottom,
                right: `${right}px`,
                left: 'auto'
            };
        },

        openCreateModal() {
            this.modal.isEdit = false;
            this.modal.id = null;
            this.form = {
                name: '',
                code: '',
                min_bandwidth_mbps: 0,
                max_bandwidth_mbps: null,
                global_rate_per_mbps: 0,
                cdn_rate_per_mbps: 0,
                bdix_rate_per_mbps: 0,
                ggc_rate_per_mbps: 0,
                fna_rate_per_mbps: 0,
                others_rate_per_mbps: 0,
                is_active: true,
                notes: ''
            };
            this.modal.open = true;
        },

        openEditModal(plan) {
            this.modal.isEdit = true;
            this.modal.id = plan.id;
            this.form = {
                name: plan.name,
                code: plan.code || '',
                min_bandwidth_mbps: plan.min_bandwidth_mbps || 0,
                max_bandwidth_mbps: plan.max_bandwidth_mbps || null,
                global_rate_per_mbps: plan.global_rate_per_mbps || 0,
                cdn_rate_per_mbps: plan.cdn_rate_per_mbps || 0,
                bdix_rate_per_mbps: plan.bdix_rate_per_mbps || 0,
                ggc_rate_per_mbps: plan.ggc_rate_per_mbps || 0,
                fna_rate_per_mbps: plan.fna_rate_per_mbps || 0,
                others_rate_per_mbps: plan.others_rate_per_mbps || 0,
                is_active: Boolean(plan.is_active),
                notes: plan.notes || ''
            };
            this.modal.open = true;
        },

        openDetailsModal(plan) {
            this.detailsModal.data = plan;
            this.detailsModal.open = true;
        },

        async submitForm() {
            if (!this.form.name) return;
            this.submitting = true;
            const url = this.modal.isEdit 
                ? `/admin/resellers/bandwidth-plans/${this.modal.id}` 
                : '{{ route('tenant.resellers.bandwidth-plans.store') }}';
            
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.form)
                });
                const data = await response.json();
                if (data.success) {
                    this.modal.open = false;
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: data.message,
                        timer: 1800,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Failed to save rate slab.'
                    });
                }
            } catch (err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Network error or invalid server response.'
                });
            } finally {
                this.submitting = false;
            }
        },

        async toggleStatus(plan) {
            try {
                const response = await fetch(`/admin/resellers/bandwidth-plans/${plan.id}/toggle-status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    plan.is_active = data.is_active;
                    Swal.fire({
                        icon: 'success',
                        title: 'Status Updated',
                        text: data.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload();
                    });
                }
            } catch (err) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to toggle status.' });
            }
        },

        async deletePlan(plan) {
            const result = await Swal.fire({
                title: 'Delete Rate Slab?',
                text: `Are you sure you want to delete '${plan.name}'?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, Delete'
            });

            if (result.isConfirmed) {
                try {
                    const response = await fetch(`/admin/resellers/bandwidth-plans/${plan.id}`, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    });
                    const data = await response.json();
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted',
                            text: data.message,
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                    }
                } catch (err) {
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to delete rate slab.' });
                }
            }
        }
    }
}
</script>
@endpush
