@php
    $speedFilterCount = (int) ($speedTier !== 'all');
@endphp
<div class="bg-white p-2.5 rounded-xl border border-slate-200/90 shadow-2xs space-y-2.5"
     x-data="{ showMoreFilters: @js($speedFilterCount > 0), searchQuery: @js($search) }">
    <form method="GET" action="{{ route($filterRoute) }}" class="space-y-2.5">
        <div class="flex flex-col md:flex-row items-stretch md:items-center gap-2 flex-wrap">
            <div class="relative flex-1 min-w-[200px]">
                <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-xs pointer-events-none"
                   :class="searchQuery ? 'text-cyan-600' : 'text-slate-400'"></i>
                <input type="search" name="search" value="{{ $search }}" x-model="searchQuery"
                       placeholder="{{ __('Search package name, profile, or code') }}"
                       class="h-[34px] w-full pl-8 pr-7 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden">
                <button type="button" x-show="searchQuery" x-cloak aria-label="{{ __('Clear Search') }}"
                        @click="searchQuery = ''; $nextTick(() => $el.closest('form').requestSubmit())"
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-rose-500">
                    <i class="fas fa-times-circle"></i>
                </button>
            </div>
            <select name="service_type" aria-label="{{ __('Service Type') }}" onchange="this.form.requestSubmit()"
                    class="h-[34px] w-full md:w-36 px-2 rounded-lg border border-slate-200 bg-slate-50 text-xs">
                @foreach(['all' => __('All Service Types'), 'PPPOE' => 'PPPoE', 'STATIC_IP' => __('Static IP'), 'HOTSPOT' => __('Hotspot')] as $value => $label)
                    <option value="{{ $value }}" @selected($serviceType === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="per_page" aria-label="{{ __('Rows per page') }}" onchange="this.form.requestSubmit()"
                    class="h-[34px] w-full md:w-28 px-2 rounded-lg border border-slate-200 bg-slate-50 text-xs">
                @foreach($pageSizes as $limit)
                    <option value="{{ $limit }}" @selected($perPage === $limit)>{{ __(':count / page', ['count' => $limit]) }}</option>
                @endforeach
            </select>
            <button type="button" @click="showMoreFilters = !showMoreFilters" :aria-expanded="showMoreFilters"
                    class="h-[34px] py-0 px-2.5 rounded-lg border border-slate-200 bg-slate-50 text-xs font-semibold flex items-center justify-center gap-1.5">
                <i class="fas fa-sliders-h text-[10px]"></i><span>{{ __('More Filters') }}</span>
                @if($speedFilterCount)
                    <span class="px-1.5 rounded-full bg-cyan-600 text-white text-[9px]">{{ $speedFilterCount }}</span>
                @endif
                <i class="fas text-[9px]" :class="showMoreFilters ? 'fa-chevron-up text-cyan-600' : 'fa-chevron-down text-slate-400'"></i>
            </button>
            <div class="flex items-center gap-1.5 md:ml-auto">
                <button type="submit" class="h-[34px] py-0 px-3 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white text-xs font-semibold flex items-center gap-1.5">
                    <i class="fas fa-filter text-[10px]"></i><span>{{ __('Filter') }}</span>
                </button>
                <a href="{{ route($filterRoute) }}" class="h-[34px] py-0 px-2.5 rounded-lg border border-slate-200 bg-slate-100 hover:bg-slate-200 text-xs font-semibold flex items-center gap-1">
                    <i class="fas fa-rotate-left text-[10px]"></i><span>{{ __('Reset') }}</span>
                </a>
            </div>
        </div>
        <div x-show="showMoreFilters" x-cloak x-transition class="pt-2 border-t border-slate-100 flex flex-wrap items-center gap-2">
            <select name="speed" aria-label="{{ __('Download Speed') }}" onchange="this.form.requestSubmit()"
                    class="h-[34px] w-full sm:w-44 px-2 rounded-lg border border-slate-200 bg-slate-50 text-xs">
                @foreach(['all' => __('All Speeds'), '10' => __('Up to 10 Mbps'), '20' => __('Over 10 to 20 Mbps'), '35' => __('Over 20 to 35 Mbps'), '50+' => __('Over 35 Mbps')] as $value => $label)
                    <option value="{{ $value }}" @selected((string) $value === $speedTier)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </form>
</div>
