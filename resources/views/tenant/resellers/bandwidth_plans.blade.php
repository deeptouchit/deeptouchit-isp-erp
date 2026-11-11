@extends('tenant.layouts.app')

@section('title', __('Bandwidth Rate Plans') . ' - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
    {{-- Page specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="bandwidthPlansManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">

    {{-- Floating Global Toast Notification --}}
    <div x-show="toast.show" 
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         class="fixed top-4 right-4 z-50 flex items-center gap-2 px-3.5 py-2 rounded-xl shadow-lg border text-xs font-semibold"
         :class="{
             'bg-slate-900 text-white border-slate-700': toast.type === 'info',
             'bg-emerald-600 text-white border-emerald-500': toast.type === 'success',
             'bg-rose-600 text-white border-rose-500': toast.type === 'error'
         }"
         style="display: none;">
        <i class="fas text-xs" :class="{
            'fa-circle-info': toast.type === 'info',
            'fa-circle-check': toast.type === 'success',
            'fa-circle-exclamation': toast.type === 'error'
        }"></i>
        <span x-text="toast.message"></span>
    </div>

    {{-- 1. Top Header Bar (Strict AGENTS.md Rule 2.A: Icon + Title + Actions ONLY - No Subtitle) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 bg-white px-3.5 py-2.5 rounded-xl border border-slate-200/90 shadow-2xs">
        <div class="flex items-center gap-2.5 min-w-0">
            <div class="w-8 h-8 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-sm shadow-2xs shrink-0">
                <i class="fas fa-tags"></i>
            </div>
            <div class="flex items-center gap-2 min-w-0">
                <h1 class="text-sm font-bold text-slate-900 tracking-tight whitespace-nowrap">{{ __('Bandwidth Rate Plans') }}</h1>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                    {{ __(':count Slabs', ['count' => $totalPlansCount]) }}
                </span>
            </div>
        </div>

        <div class="flex items-center gap-1.5 flex-wrap sm:flex-nowrap shrink-0">
            {{-- Wholesale Allocations Link --}}
            <a href="{{ route('tenant.resellers.bandwidth') }}" 
               class="h-[34px] bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-0 px-2.5 rounded-lg border border-slate-200/80 shadow-2xs transition flex items-center justify-center gap-1.5"
               title="{{ __('View Reseller Bandwidth Allocations') }}">
                <i class="fas fa-network-wired text-cyan-600 text-[10px]"></i>
                <span class="hidden md:inline">{{ __('Wholesale Allocations') }}</span>
            </a>

            {{-- Create Rate Slab Button --}}
            <button type="button" 
                    @click="openCreateModal()"
                    class="h-[34px] bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-0 px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                <i class="fas fa-plus text-[10px]"></i>
                <span>{{ __('Create Rate Slab') }}</span>
            </button>
        </div>
    </div>

    {{-- 2. KPI Summary Strip (Strict AGENTS.md Rule 2.B: Strictly 6 Cards) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        {{-- Card 1: Total Rate Plans --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">{{ __('Total Slabs') }}</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-slate-900 truncate">{{ number_format($totalPlansCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-cyan-50 text-cyan-600 border-cyan-100 flex items-center justify-center">
                <i class="fas fa-layer-group"></i>
            </div>
        </div>

        {{-- Card 2: Active Plans --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-emerald-600">{{ __('Active Slabs') }}</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-emerald-700 truncate">{{ number_format($activePlansCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-emerald-50 text-emerald-600 border-emerald-100 flex items-center justify-center">
                <i class="fas fa-circle-check"></i>
            </div>
        </div>

        {{-- Card 3: Avg Global Rate --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-blue-600">{{ __('Avg Global') }}</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-blue-700 truncate">@currency($avgGlobalRate)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-blue-50 text-blue-600 border-blue-100 flex items-center justify-center">
                <i class="fas fa-globe"></i>
            </div>
        </div>

        {{-- Card 4: Avg CDN Rate --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-purple-600">{{ __('Avg CDN') }}</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-purple-700 truncate">@currency($avgCdnRate)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-purple-50 text-purple-600 border-purple-100 flex items-center justify-center">
                <i class="fas fa-cloud-arrow-down"></i>
            </div>
        </div>

        {{-- Card 5: Avg BDIX Rate --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-indigo-600">{{ __('Avg BDIX') }}</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-indigo-700 truncate">@currency($avgBdixRate)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-indigo-50 text-indigo-600 border-indigo-100 flex items-center justify-center">
                <i class="fas fa-bolt"></i>
            </div>
        </div>

        {{-- Card 6: Assigned Lines --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5 min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">{{ __('Assigned Lines') }}</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-slate-800 truncate">{{ number_format($totalAssignedResellers) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-slate-100 text-slate-600 border-slate-200 flex items-center justify-center">
                <i class="fas fa-users-gear"></i>
            </div>
        </div>
    </div>

    {{-- 3. Search & Multi-Filter Toolbar (Strict Customer-Standard 2-Tier Filter System) --}}
    <form method="GET" action="{{ route('tenant.resellers.bandwidth-plans') }}" 
          class="bg-white p-2.5 rounded-xl border border-slate-200/90 shadow-2xs space-y-2.5"
          x-data="{ 
              showMoreFilters: {{ $isMoreOpen ? 'true' : 'false' }}, 
              searchQuery: '{{ addslashes(request('search', '')) }}' 
          }">
        
        {{-- Tier 1: Primary Filter Bar (Uniform h-[34px] controls) --}}
        <div class="flex flex-col md:flex-row items-stretch md:items-center gap-2">
            {{-- Intelligent Live Search Box --}}
            <div class="relative flex-1 min-w-[200px]">
                <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-xs transition-colors duration-200"
                   :class="searchQuery ? 'text-cyan-600 font-bold' : 'text-slate-400'"></i>
                <input type="text" 
                       name="search" 
                       x-model="searchQuery" 
                       placeholder="{{ __('Search Rate Slab Name, Code, Description...') }}" 
                       value="{{ request('search') }}"
                       autocomplete="off"
                       class="w-full h-[34px] pl-8 pr-7 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition font-normal" />
                <button type="button" 
                        x-show="searchQuery && searchQuery.length > 0" 
                        @click="searchQuery = ''; $nextTick(() => $el.closest('form').submit())"
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-0.5 cursor-pointer"
                        style="display: none;">
                    <i class="fas fa-times-circle text-xs"></i>
                </button>
            </div>

            {{-- Primary Filter: Status Filter --}}
            <select name="status" 
                    class="h-[34px] w-full md:w-36 text-xs bg-slate-50 border border-slate-200 rounded-lg px-2.5 text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden font-normal shrink-0">
                <option value="">{{ __('All Statuses') }}</option>
                <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>{{ __('Active Slabs') }}</option>
                <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>{{ __('Inactive Slabs') }}</option>
            </select>

            {{-- Pagination Selector (per_page) --}}
            @php $perPageVal = (int)request('per_page', 20); @endphp
            <select name="per_page" 
                    class="h-[34px] w-full md:w-28 text-xs bg-slate-50 border border-slate-200 rounded-lg px-2.5 text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden font-normal shrink-0">
                <option value="10" {{ $perPageVal === 10 ? 'selected' : '' }}>10 / {{ __('page') }}</option>
                <option value="20" {{ $perPageVal === 20 ? 'selected' : '' }}>20 / {{ __('page') }}</option>
                <option value="50" {{ $perPageVal === 50 ? 'selected' : '' }}>50 / {{ __('page') }}</option>
                <option value="100" {{ $perPageVal === 100 ? 'selected' : '' }}>100 / {{ __('page') }}</option>
            </select>

            {{-- More Filters Accordion Toggle Button --}}
            <button type="button" 
                    @click="showMoreFilters = !showMoreFilters"
                    class="h-[34px] py-0 px-2.5 rounded-lg border text-xs font-medium transition flex items-center justify-center gap-1.5 cursor-pointer shrink-0"
                    :class="showMoreFilters || {{ $activeMoreCount }} > 0 ? 'bg-cyan-50/70 border-cyan-200 text-cyan-800' : 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100'">
                <i class="fas fa-sliders text-[11px] text-slate-500"></i>
                <span>{{ __('More Filters') }}</span>
                @if($activeMoreCount > 0)
                    <span class="w-4 h-4 rounded-full bg-cyan-600 text-white text-[9px] font-bold flex items-center justify-center">{{ $activeMoreCount }}</span>
                @endif
                <i class="fas text-[10px] transition-transform duration-200" :class="showMoreFilters ? 'fa-chevron-up text-cyan-600' : 'fa-chevron-down text-slate-400'"></i>
            </button>

            {{-- Strict Filter & Reset Button Sequence (Filter First, Reset Second - Always Visible) --}}
            <div class="flex items-center gap-1.5 shrink-0 md:ml-auto">
                <button type="submit" 
                        class="h-[34px] bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-0 px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                    <i class="fas fa-filter text-[10px]"></i>
                    <span>{{ __('Filter') }}</span>
                </button>

                <a href="{{ route('tenant.resellers.bandwidth-plans') }}" 
                   class="h-[34px] bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-0 px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer">
                    <i class="fas fa-rotate-left text-[10px]"></i>
                    <span>{{ __('Reset') }}</span>
                </a>
            </div>
        </div>

        {{-- Tier 2: Secondary Filters Drawer --}}
        <div x-show="showMoreFilters" 
             x-cloak 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-1"
             class="pt-2 border-t border-slate-100 flex flex-wrap items-center gap-2">
            
            {{-- Volume Range Filter --}}
            <div class="w-full sm:w-auto">
                <select name="volume_range" 
                        class="h-[34px] w-full sm:w-52 text-xs bg-slate-50 border border-slate-200 rounded-lg px-2.5 text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden font-normal">
                    <option value="">{{ __('All Volume Slabs') }}</option>
                    <option value="low" {{ request('volume_range') === 'low' ? 'selected' : '' }}>{{ __('Small Tier (≤ 500 Mbps)') }}</option>
                    <option value="mid" {{ request('volume_range') === 'mid' ? 'selected' : '' }}>{{ __('Medium Tier (501 - 2000 Mbps)') }}</option>
                    <option value="high" {{ request('volume_range') === 'high' ? 'selected' : '' }}>{{ __('Large Tier (> 2000 Mbps)') }}</option>
                </select>
            </div>

            {{-- Assigned Reseller Lines Filter --}}
            <div class="w-full sm:w-auto">
                <select name="has_allocations" 
                        class="h-[34px] w-full sm:w-48 text-xs bg-slate-50 border border-slate-200 rounded-lg px-2.5 text-slate-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden font-normal">
                    <option value="">{{ __('All Allocation States') }}</option>
                    <option value="yes" {{ request('has_allocations') === 'yes' ? 'selected' : '' }}>{{ __('Has Assigned Resellers') }}</option>
                    <option value="no" {{ request('has_allocations') === 'no' ? 'selected' : '' }}>{{ __('No Assigned Resellers') }}</option>
                </select>
            </div>
        </div>
    </form>

    {{-- 4. Master Compact Table (Pure CSS System Compliant - Strict AGENTS.md Rule 2.D) --}}
    <div class="bg-white rounded-xl border border-slate-200/90 overflow-hidden shadow-2xs">
        <div class="px-3.5 py-2 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
            <span class="font-bold text-slate-800 text-[11px] uppercase tracking-wider flex items-center gap-1.5">
                <i class="fas fa-tags text-slate-400 text-xs"></i>
                <span>{{ __('Bandwidth Rate Slabs & Tariffs') }}</span>
            </span>
            <span class="text-[11px] text-slate-500 font-mono font-normal">
                {{ __('Total :count Slabs', ['count' => $plans->total()]) }}
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="saas-table w-full">
                <thead>
                    <tr>
                        <th class="w-10 text-center font-semibold">#</th>
                        <th class="text-center font-semibold">{{ __('Rate Slab Name') }}</th>
                        <th class="w-36 text-center font-semibold font-mono">{{ __('Volume Slab (Range)') }}</th>
                        <th class="text-center font-semibold w-28">{{ __('Global Internet') }}</th>
                        <th class="text-center font-semibold w-24">{{ __('CDN / Cache') }}</th>
                        <th class="text-center font-semibold w-24">{{ __('BDIX Peering') }}</th>
                        <th class="text-center font-semibold w-24">{{ __('GGC (Google)') }}</th>
                        <th class="text-center font-semibold w-24">{{ __('FNA (Meta)') }}</th>
                        <th class="text-center font-semibold w-24">{{ __('Others Cache') }}</th>
                        <th class="text-center font-semibold w-20">{{ __('Status') }}</th>
                        <th class="w-10 text-center font-semibold no-sort">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($plans as $index => $plan)
                        <tr>
                            {{-- 1. Index --}}
                            <td class="text-center font-mono text-slate-400 whitespace-nowrap">
                                {{ $plans->firstItem() + $index }}
                            </td>

                            {{-- 2. Rate Plan Name --}}
                            <td class="text-left font-medium text-slate-900 whitespace-nowrap">
                                <button type="button" 
                                        @click="openDetailsModal({{ Js::from($plan) }})" 
                                        class="hover:underline text-cyan-700 hover:text-cyan-800 font-semibold cursor-pointer text-left">
                                    {{ $plan->name }}
                                </button>
                            </td>

                            {{-- 3. Bandwidth Volume Slab Range --}}
                            <td class="text-center font-mono text-xs font-semibold text-slate-700 whitespace-nowrap">
                                {{ $plan->formatted_slab_range }}
                            </td>

                            {{-- 4. Global Rate --}}
                            <td class="text-right font-mono font-bold text-blue-700 whitespace-nowrap">
                                @currency($plan->global_rate_per_mbps)
                            </td>

                            {{-- 5. CDN Rate --}}
                            <td class="text-right font-mono font-bold text-purple-700 whitespace-nowrap">
                                @currency($plan->cdn_rate_per_mbps)
                            </td>

                            {{-- 6. BDIX Rate --}}
                            <td class="text-right font-mono font-bold text-indigo-700 whitespace-nowrap">
                                @currency($plan->bdix_rate_per_mbps)
                            </td>

                            {{-- 7. GGC Rate --}}
                            <td class="text-right font-mono font-bold text-amber-700 whitespace-nowrap">
                                @currency($plan->ggc_rate_per_mbps)
                            </td>

                            {{-- 8. FNA Rate --}}
                            <td class="text-right font-mono font-bold text-sky-700 whitespace-nowrap">
                                @currency($plan->fna_rate_per_mbps)
                            </td>

                            {{-- 9. Others Rate --}}
                            <td class="text-right font-mono font-bold text-emerald-700 whitespace-nowrap">
                                @currency($plan->others_rate_per_mbps)
                            </td>

                            {{-- 10. Status --}}
                            <td class="text-center whitespace-nowrap">
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-[10px] font-semibold border {{ $plan->status_badge['class'] }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $plan->status_badge['dot'] }}"></span>
                                    <span>{{ $plan->status_badge['label'] }}</span>
                                </span>
                            </td>

                            {{-- 11. Action (3-Dot Menu) --}}
                            <td class="w-10 text-center whitespace-nowrap">
                                <button type="button" 
                                        @click.stop="toggleMenu({{ Js::from($plan) }}, $event)"
                                        class="w-6 h-6 mx-auto rounded-md hover:bg-slate-100 text-slate-500 hover:text-cyan-700 transition flex items-center justify-center cursor-pointer text-[10px]"
                                        title="{{ __('Actions') }}">
                                    <i class="fas fa-ellipsis-v text-[10px] pointer-events-none"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="p-8 text-center">
                                <div class="max-w-md mx-auto space-y-2">
                                    <div class="w-10 h-10 rounded-xl bg-cyan-50 text-cyan-600 mx-auto flex items-center justify-center text-base border border-cyan-100 shadow-2xs">
                                        <i class="fas fa-tags"></i>
                                    </div>
                                    <h3 class="text-xs font-bold text-slate-800">{{ __('No Bandwidth Rate Plans Found') }}</h3>
                                    <p class="text-[11px] text-slate-400 font-normal">{{ __('Define volume slabs to start allocating bandwidth tariffs to resellers.') }}</p>
                                    <div class="pt-1 flex items-center justify-center gap-2">
                                        <button type="button" 
                                                @click="openCreateModal()" 
                                                class="bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs px-3.5 py-1.5 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                                            <i class="fas fa-plus text-[10px]"></i>
                                            <span>{{ __('Create Rate Slab') }}</span>
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($plans->hasPages())
            <div class="px-3.5 py-2.5 bg-slate-50/80 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-slate-600">
                <span class="text-[11px] text-slate-500 font-normal">
                    {{ __('Showing :first to :last of :total entries', ['first' => $plans->firstItem() ?? 0, 'last' => $plans->lastItem() ?? 0, 'total' => $plans->total()]) }}
                </span>
                <div>
                    {{ $plans->links() }}
                </div>
            </div>
        @endif
    </div>

    {{-- 5. Floating Action Dropdown Menu (Strict AGENTS.md Rule 2.E) --}}
    <div x-show="activeMenu !== null" 
         x-cloak 
         @click.outside="activeMenu = null"
         :style="{
             top: menuPos.top,
             bottom: menuPos.bottom,
             right: menuPos.right,
             left: menuPos.left
         }"
         class="fixed z-50 w-44 bg-white rounded-xl border border-slate-200 shadow-2xl py-1 text-xs text-slate-700 font-normal space-y-0.5 divide-y divide-slate-100"
         style="display: none;">
        
        <div class="py-0.5">
            <button type="button" 
                    @click="const p = activeMenu; activeMenu = null; openDetailsModal(p)" 
                    class="w-full px-3 py-1.5 text-left hover:bg-slate-50 hover:text-cyan-700 flex items-center gap-2 cursor-pointer transition text-[11px]">
                <i class="fas fa-circle-info text-cyan-600 w-3.5 text-[10px]"></i>
                <span>{{ __('View Details') }}</span>
            </button>

            <button type="button" 
                    @click="const p = activeMenu; activeMenu = null; openEditModal(p)" 
                    class="w-full px-3 py-1.5 text-left hover:bg-slate-50 hover:text-cyan-700 flex items-center gap-2 cursor-pointer transition text-[11px]">
                <i class="fas fa-pen-to-square text-cyan-600 w-3.5 text-[10px]"></i>
                <span>{{ __('Edit Plan') }}</span>
            </button>

            <button type="button" 
                    @click="const p = activeMenu; activeMenu = null; toggleStatus(p)" 
                    class="w-full px-3 py-1.5 text-left hover:bg-slate-50 flex items-center gap-2 cursor-pointer transition text-[11px]"
                    :class="activeMenu?.is_active ? 'text-amber-600 hover:text-amber-700' : 'text-emerald-600 hover:text-emerald-700'">
                <i class="fas w-3.5 text-[10px]" :class="activeMenu?.is_active ? 'fa-ban text-amber-600' : 'fa-check text-emerald-600'"></i>
                <span x-text="activeMenu?.is_active ? i18n.disablePlan : i18n.enablePlan"></span>
            </button>
        </div>

        <div class="py-0.5">
            <button type="button" 
                    @click="const p = activeMenu; activeMenu = null; deletePlan(p)" 
                    class="w-full px-3 py-1.5 text-left hover:bg-rose-50 hover:text-rose-700 text-rose-600 flex items-center gap-2 cursor-pointer transition text-[11px]">
                <i class="fas fa-trash-can text-rose-600 w-3.5 text-[10px]"></i>
                <span>{{ __('Delete Plan') }}</span>
            </button>
        </div>
    </div>

    {{-- 6. Add/Edit Modal (Strict AGENTS.md Rule 3) --}}
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
            
            {{-- Soft Natural Header --}}
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs shrink-0">
                        <i class="fas fa-tags"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-bold text-slate-800" x-text="modal.isEdit ? i18n.editBandwidthRateSlab : i18n.createBandwidthRateSlab"></h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">{{ __('Define volume tier and wholesale tariff per Mbps for each traffic category') }}</p>
                    </div>
                </div>
                <button type="button" @click="modal.open = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            {{-- Form --}}
            <form @submit.prevent="submitForm()" class="p-4 space-y-3.5 text-xs">
                {{-- Row 1: Name & Code --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="sm:col-span-2">
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            {{ __('Slab / Plan Name') }} <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" 
                               x-model="form.name" 
                               required 
                               placeholder="{{ __('e.g. Slab 1 (1 - 500 Mbps)') }}" 
                               class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 font-normal outline-hidden transition">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            {{ __('Slab Code') }}
                        </label>
                        <input type="text" 
                               x-model="form.code" 
                               placeholder="{{ __('e.g. SLAB-500M') }}" 
                               class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono px-3 py-1.5 focus:bg-white focus:border-cyan-500 font-normal outline-hidden transition">
                    </div>
                </div>

                {{-- Row 2: Bandwidth Volume Slab (Min - Max Mbps) --}}
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-2">
                    <span class="text-[11px] font-bold text-slate-800 block">{{ __('Bandwidth Volume Slab (Min - Max)') }}</span>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-600 mb-0.5">
                                {{ __('Min Bandwidth (Mbps)') }}
                            </label>
                            <input type="number" 
                                   step="1" 
                                   min="0" 
                                   x-model="form.min_bandwidth_mbps" 
                                   placeholder="{{ __('e.g. 1 or 500') }}" 
                                   class="w-full bg-white border border-slate-200 rounded-lg text-xs font-mono px-2.5 py-1.5 focus:border-cyan-500 font-normal outline-hidden transition">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-600 mb-0.5">
                                {{ __('Max Bandwidth (Mbps)') }} <span class="text-[9px] text-slate-400 font-normal">{{ __('(Leave blank for unlimited)') }}</span>
                            </label>
                            <input type="number" 
                                   step="1" 
                                   min="0" 
                                   x-model="form.max_bandwidth_mbps" 
                                   placeholder="{{ __('e.g. 500, 1000 (1GB), 2000 (2GB)') }}" 
                                   class="w-full bg-white border border-slate-200 rounded-lg text-xs font-mono px-2.5 py-1.5 focus:border-cyan-500 font-normal outline-hidden transition">
                        </div>
                    </div>
                </div>

                {{-- Row 3: 6 Traffic Category Rates (Rate per Mbps) --}}
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-2">
                    <span class="text-[11px] font-bold text-slate-800 block">{{ __('Traffic Category Rates (:currency per Mbps / Month)', ['currency' => $currencySymbol ?? '৳']) }}</span>
                    
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                        {{-- 1. Global Internet --}}
                        <div>
                            <label class="block text-[10px] font-semibold text-blue-700 mb-0.5">
                                {{ __('Global Internet') }} <span class="text-rose-500">*</span>
                            </label>
                            <input type="number" step="0.01" min="0" x-model="form.global_rate_per_mbps" required placeholder="{{ __('e.g. 280') }}" class="w-full bg-white border border-slate-200 rounded-lg text-xs font-mono px-2.5 py-1.5 focus:border-cyan-500 font-normal outline-hidden transition">
                        </div>

                        {{-- 2. CDN / Cache --}}
                        <div>
                            <label class="block text-[10px] font-semibold text-purple-700 mb-0.5">
                                {{ __('CDN / Cache') }}
                            </label>
                            <input type="number" step="0.01" min="0" x-model="form.cdn_rate_per_mbps" placeholder="{{ __('e.g. 35') }}" class="w-full bg-white border border-slate-200 rounded-lg text-xs font-mono px-2.5 py-1.5 focus:border-cyan-500 font-normal outline-hidden transition">
                        </div>

                        {{-- 3. BDIX Peering --}}
                        <div>
                            <label class="block text-[10px] font-semibold text-indigo-700 mb-0.5">
                                {{ __('BDIX Peering') }}
                            </label>
                            <input type="number" step="0.01" min="0" x-model="form.bdix_rate_per_mbps" placeholder="{{ __('e.g. 45') }}" class="w-full bg-white border border-slate-200 rounded-lg text-xs font-mono px-2.5 py-1.5 focus:border-cyan-500 font-normal outline-hidden transition">
                        </div>

                        {{-- 4. GGC Cache --}}
                        <div>
                            <label class="block text-[10px] font-semibold text-amber-700 mb-0.5">
                                {{ __('GGC (Google Cache)') }}
                            </label>
                            <input type="number" step="0.01" min="0" x-model="form.ggc_rate_per_mbps" placeholder="{{ __('e.g. 25') }}" class="w-full bg-white border border-slate-200 rounded-lg text-xs font-mono px-2.5 py-1.5 focus:border-cyan-500 font-normal outline-hidden transition">
                        </div>

                        {{-- 5. FNA Cache --}}
                        <div>
                            <label class="block text-[10px] font-semibold text-sky-700 mb-0.5">
                                {{ __('FNA (Meta / FB)') }}
                            </label>
                            <input type="number" step="0.01" min="0" x-model="form.fna_rate_per_mbps" placeholder="{{ __('e.g. 25') }}" class="w-full bg-white border border-slate-200 rounded-lg text-xs font-mono px-2.5 py-1.5 focus:border-cyan-500 font-normal outline-hidden transition">
                        </div>

                        {{-- 6. OTHERS Cache --}}
                        <div>
                            <label class="block text-[10px] font-semibold text-emerald-700 mb-0.5">
                                {{ __('OTHERS Cache') }}
                            </label>
                            <input type="number" step="0.01" min="0" x-model="form.others_rate_per_mbps" placeholder="{{ __('e.g. 20') }}" class="w-full bg-white border border-slate-200 rounded-lg text-xs font-mono px-2.5 py-1.5 focus:border-cyan-500 font-normal outline-hidden transition">
                        </div>
                    </div>
                </div>

                {{-- Row 4: Notes --}}
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">{{ __('Notes / Description') }}</label>
                    <input type="text" x-model="form.notes" placeholder="{{ __('e.g. Wholesale bandwidth pricing slab for 1 - 500 Mbps volume') }}" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 font-normal outline-hidden transition">
                </div>

                {{-- Row 5: Active Status Toggle --}}
                <div class="flex items-center gap-2 pt-1">
                    <input type="checkbox" id="plan_active" x-model="form.is_active" class="w-4 h-4 text-cyan-600 rounded border-slate-300 focus:ring-cyan-500 cursor-pointer">
                    <label for="plan_active" class="text-xs font-medium text-slate-700 cursor-pointer">{{ __('Active and Available for Bandwidth Allocation') }}</label>
                </div>

                {{-- Footer Actions --}}
                <div class="pt-2 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="modal.open = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit" 
                            :disabled="submitting"
                            class="bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition inline-flex items-center gap-1.5 disabled:opacity-50 cursor-pointer">
                        <i class="fas fa-check text-xs" :class="{ 'fa-spin fa-spinner': submitting }"></i>
                        <span x-text="submitting ? i18n.saving : (modal.isEdit ? i18n.updateRateSlab : i18n.saveRateSlab)"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- 7. View Details Modal (Strict AGENTS.md Rule 3) --}}
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
            
            {{-- Header --}}
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs shrink-0">
                        <i class="fas fa-tags"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-bold text-slate-800" x-text="detailsModal.data?.name"></h3>
                        <p class="text-[10.5px] text-slate-500 font-mono font-normal" x-text="detailsModal.data?.code || i18n.bandwidthRateSlabDetails"></p>
                    </div>
                </div>
                <button type="button" @click="detailsModal.open = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            {{-- Body --}}
            <div class="p-4 space-y-3.5 text-xs">
                {{-- Volume Slab Badge Card --}}
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] text-slate-500 uppercase font-semibold block">{{ __('Volume Tier Range') }}</span>
                        <span class="text-sm font-bold font-mono text-slate-800 mt-0.5 block" x-text="detailsModal.data?.formatted_slab_range"></span>
                    </div>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[10px] font-semibold border"
                          :class="detailsModal.data?.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-600 border-slate-200'">
                        <span class="w-1.5 h-1.5 rounded-full" :class="detailsModal.data?.is_active ? 'bg-emerald-500' : 'bg-slate-400'"></span>
                        <span x-text="detailsModal.data?.is_active ? i18n.active : i18n.inactive"></span>
                    </span>
                </div>

                {{-- 6 Category Rates Breakdown --}}
                <div>
                    <span class="text-[11px] font-bold text-slate-800 mb-2 block">{{ __('Traffic Rates Breakdown (Per Mbps)') }}</span>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                        <div class="p-2.5 bg-blue-50/50 rounded-lg border border-blue-100">
                            <span class="text-[10px] text-blue-700 uppercase font-semibold block">{{ __('Global Internet') }}</span>
                            <span class="text-xs font-bold font-mono text-blue-900 mt-0.5 block" x-text="formatCurrency(detailsModal.data?.global_rate_per_mbps)"></span>
                        </div>
                        <div class="p-2.5 bg-purple-50/50 rounded-lg border border-purple-100">
                            <span class="text-[10px] text-purple-700 uppercase font-semibold block">{{ __('CDN / Cache') }}</span>
                            <span class="text-xs font-bold font-mono text-purple-900 mt-0.5 block" x-text="formatCurrency(detailsModal.data?.cdn_rate_per_mbps)"></span>
                        </div>
                        <div class="p-2.5 bg-indigo-50/50 rounded-lg border border-indigo-100">
                            <span class="text-[10px] text-indigo-700 uppercase font-semibold block">{{ __('BDIX Peering') }}</span>
                            <span class="text-xs font-bold font-mono text-indigo-900 mt-0.5 block" x-text="formatCurrency(detailsModal.data?.bdix_rate_per_mbps)"></span>
                        </div>
                        <div class="p-2.5 bg-amber-50/50 rounded-lg border border-amber-100">
                            <span class="text-[10px] text-amber-700 uppercase font-semibold block">{{ __('GGC (Google)') }}</span>
                            <span class="text-xs font-bold font-mono text-amber-900 mt-0.5 block" x-text="formatCurrency(detailsModal.data?.ggc_rate_per_mbps)"></span>
                        </div>
                        <div class="p-2.5 bg-sky-50/50 rounded-lg border border-sky-100">
                            <span class="text-[10px] text-sky-700 uppercase font-semibold block">{{ __('FNA (Meta / FB)') }}</span>
                            <span class="text-xs font-bold font-mono text-sky-900 mt-0.5 block" x-text="formatCurrency(detailsModal.data?.fna_rate_per_mbps)"></span>
                        </div>
                        <div class="p-2.5 bg-emerald-50/50 rounded-lg border border-emerald-100">
                            <span class="text-[10px] text-emerald-700 uppercase font-semibold block">{{ __('OTHERS Cache') }}</span>
                            <span class="text-xs font-bold font-mono text-emerald-900 mt-0.5 block" x-text="formatCurrency(detailsModal.data?.others_rate_per_mbps)"></span>
                        </div>
                    </div>
                </div>

                {{-- Notes --}}
                <template x-if="detailsModal.data?.notes">
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200 text-slate-700">
                        <span class="text-[10px] font-semibold text-slate-500 uppercase block mb-0.5">{{ __('Notes') }}</span>
                        <span class="text-xs" x-text="detailsModal.data?.notes"></span>
                    </div>
                </template>
            </div>

            {{-- Footer --}}
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                <button type="button" 
                        @click="const p = detailsModal.data; detailsModal.open = false; openEditModal(p)"
                        class="bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs px-3.5 py-1.5 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                    <i class="fas fa-pen-to-square text-xs"></i>
                    <span>{{ __('Edit This Slab') }}</span>
                </button>
                <button type="button" @click="detailsModal.open = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                    {{ __('Close') }}
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
        currencySymbol: @json($currencySymbol ?? '৳'),
        currencyPosition: @json($currencyPosition ?? 'left'),
        toast: { show: false, message: '', type: 'success' },
        i18n: {
            disablePlan: @json(__('Disable Plan')),
            enablePlan: @json(__('Enable Plan')),
            editBandwidthRateSlab: @json(__('Edit Bandwidth Rate Slab')),
            createBandwidthRateSlab: @json(__('Create Bandwidth Rate Slab')),
            saving: @json(__('Saving...')),
            updateRateSlab: @json(__('Update Rate Slab')),
            saveRateSlab: @json(__('Save Rate Slab')),
            bandwidthRateSlabDetails: @json(__('Bandwidth Rate Slab Details')),
            active: @json(__('Active')),
            inactive: @json(__('Inactive')),
            rateSlabSavedSuccess: @json(__('Rate slab saved successfully!')),
            failedSaveRateSlab: @json(__('Failed to save rate slab.')),
            networkErrorResponse: @json(__('Network error or invalid server response.')),
            statusUpdated: @json(__('Status updated!')),
            failedToggleStatus: @json(__('Failed to toggle status.')),
            deleteRateSlabTitle: @json(__('Delete Rate Slab?')),
            deleteRateSlabConfirm: @json(__('Are you sure you want to delete :name?')),
            deleteRateSlabWarning: @json(__('This rate slab will no longer be available for bandwidth allocation.')),
            yesDeleteSlab: @json(__('Yes, Delete Slab')),
            cancel: @json(__('Cancel')),
            rateSlabDeleted: @json(__('Rate slab deleted.')),
            failedDeleteRateSlab: @json(__('Failed to delete rate slab.'))
        },

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

        init() {
            const flashSuccess = @json(session('success'));
            const flashError = @json(session('error'));
            if (flashSuccess) {
                this.showToast(flashSuccess, 'success');
            } else if (flashError) {
                this.showToast(flashError, 'error');
            }
        },

        escapeHtml(str) {
            return String(str ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        },

        showToast(message, type = 'success') {
            this.toast.message = message;
            this.toast.type = type;
            this.toast.show = true;
            setTimeout(() => { this.toast.show = false; }, 3500);
        },

        formatCurrency(amount) {
            const val = parseFloat(amount || 0);
            const formatted = val.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            return this.currencyPosition === 'right'
                ? `${formatted} ${this.currencySymbol}`
                : `${this.currencySymbol} ${formatted}`;
        },

        toggleMenu(item, event) {
            if (this.activeMenu?.id === item.id) {
                this.activeMenu = null;
                return;
            }
            this.activeMenu = item;
            const targetEl = event.currentTarget || event.target.closest('button');
            if (!targetEl) return;

            const rect = targetEl.getBoundingClientRect();
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
                    this.showToast(data.message || this.i18n.rateSlabSavedSuccess, 'success');
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    this.showToast(data.message || this.i18n.failedSaveRateSlab, 'error');
                }
            } catch (err) {
                this.showToast(this.i18n.networkErrorResponse, 'error');
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
                    this.showToast(data.message || this.i18n.statusUpdated, 'success');
                    setTimeout(() => window.location.reload(), 500);
                } else {
                    this.showToast(data.message || this.i18n.failedToggleStatus, 'error');
                }
            } catch (err) {
                this.showToast(this.i18n.failedToggleStatus, 'error');
            }
        },

        async deletePlan(plan) {
            if (typeof Swal !== 'undefined') {
                const safeNameHtml = `<strong class="text-slate-900 font-semibold">${this.escapeHtml(plan.name)}</strong>`;
                const confirmText = this.escapeHtml(this.i18n.deleteRateSlabConfirm).replace(':name', safeNameHtml);
                const result = await Swal.fire({
                    title: this.i18n.deleteRateSlabTitle,
                    html: `<div class="text-xs text-slate-600 mt-1">${confirmText}<br><span class="text-[11px] text-rose-500 mt-1 block">${this.escapeHtml(this.i18n.deleteRateSlabWarning)}</span></div>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e11d48',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: this.i18n.yesDeleteSlab,
                    cancelButtonText: this.i18n.cancel,
                    reverseButtons: true,
                    customClass: {
                        popup: 'rounded-2xl shadow-xl border border-slate-200 p-5',
                        confirmButton: 'px-4 py-2 rounded-xl text-xs font-semibold shadow-xs',
                        cancelButton: 'px-4 py-2 rounded-xl text-xs font-semibold'
                    }
                });

                if (result.isConfirmed) {
                    this.executeDelete(plan.id);
                }
            } else if (confirm(this.i18n.deleteRateSlabConfirm.replace(':name', plan.name))) {
                this.executeDelete(plan.id);
            }
        },

        async executeDelete(id) {
            try {
                const response = await fetch(`/admin/resellers/bandwidth-plans/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    this.showToast(data.message || this.i18n.rateSlabDeleted, 'success');
                    setTimeout(() => window.location.reload(), 500);
                } else {
                    this.showToast(data.message || this.i18n.failedDeleteRateSlab, 'error');
                }
            } catch (err) {
                this.showToast(this.i18n.failedDeleteRateSlab, 'error');
            }
        }
    };
}
</script>
@endpush
