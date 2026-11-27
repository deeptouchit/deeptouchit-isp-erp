@props([
    'action',
    'method' => 'GET',
    'showMoreOpen' => false,
    'activeMoreCount' => 0,
    'searchValue' => '',
    'resetUrl' => null,
    'formId' => null,
])

<div class="owner-filter-toolbar bg-white p-2.5 rounded-xl border border-slate-200/90 shadow-2xs space-y-2.5"
     x-data="{ showMoreFilters: @js((bool) $showMoreOpen), searchQuery: @js($searchValue) }">
    <form method="{{ strtoupper($method) }}"
          action="{{ $action }}"
          @if($formId) id="{{ $formId }}" @endif
          class="space-y-2.5">
        <div class="owner-filter-primary flex flex-col md:flex-row items-stretch md:items-center gap-2">
            {{ $primary }}

            <button type="button"
                    @click="showMoreFilters = !showMoreFilters"
                    :aria-expanded="showMoreFilters.toString()"
                    aria-controls="{{ $formId ? $formId . '-more-filters' : 'owner-more-filters' }}"
                    class="h-[34px] py-0 px-2.5 rounded-lg border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-700 text-xs font-medium transition flex items-center justify-center gap-1.5 flex-shrink-0 cursor-pointer shadow-2xs">
                <i class="fas fa-sliders-h text-[10px]" aria-hidden="true"></i>
                <span>More Filters</span>
                @if((int) $activeMoreCount > 0)
                    <span class="px-1.5 rounded-full bg-cyan-600 text-white text-[9px] font-bold">{{ $activeMoreCount }}</span>
                @endif
                <i class="fas text-[9px]" :class="showMoreFilters ? 'fa-chevron-up text-cyan-600' : 'fa-chevron-down text-slate-400'" aria-hidden="true"></i>
            </button>

            <div class="owner-filter-submit flex items-center gap-1.5 flex-shrink-0 md:ml-auto">
                @isset($actions)
                    {{ $actions }}
                @else
                    <button type="submit" class="h-[34px] py-0 px-3 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs transition flex items-center justify-center gap-1.5 shadow-xs">
                        <i class="fas fa-filter text-[10px]" aria-hidden="true"></i>
                        <span>Filter</span>
                    </button>
                    <a href="{{ $resetUrl ?: $action }}" class="h-[34px] py-0 px-2.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition flex items-center justify-center gap-1 border border-slate-200/80">
                        <i class="fas fa-rotate-left text-[10px]" aria-hidden="true"></i>
                        <span>Reset</span>
                    </a>
                @endisset
            </div>
        </div>

        <div id="{{ $formId ? $formId . '-more-filters' : 'owner-more-filters' }}"
             x-show="showMoreFilters"
             :aria-hidden="(!showMoreFilters).toString()"
             x-cloak
             x-transition
             class="owner-filter-secondary pt-2 border-t border-slate-100 flex flex-wrap items-center gap-2">
            {{ $secondary }}
        </div>
    </form>
</div>
