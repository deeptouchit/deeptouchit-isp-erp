@extends('tenant.layouts.app')

@section('title', __('Monthly Analytics') . ' - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
    {{-- Page-specific CSS --}}
@endpush

@section('content')
@php
    $currSym = $currencySymbol ?? '৳';
    $currPos = $currencyPosition ?? 'left';
    $currDec = (int)($currencyDecimals ?? 2);
    $thSep = $thousandSeparator ?? ',';
    $decSep = $decimalSeparator ?? '.';

    $formatCurrency = static function (float $amount) use ($currSym, $currPos, $currDec, $thSep, $decSep): string {
        $amt = number_format($amount, $currDec, $decSep, $thSep);
        return $currPos === 'right' ? "{$amt} {$currSym}" : "{$currSym} {$amt}";
    };

    $activeMoreCount = collect([
        !$isResellerUser ? ($resellerId ?? null) : null,
        $rateTier ?? null,
        $activityFilter ?? null,
        ($sortOrder ?? 'newest') !== 'newest' ? $sortOrder : null,
    ])->filter(fn ($v) => $v !== null && $v !== '')->count();
    $isMoreOpen = $activeMoreCount > 0;
@endphp

<div class="space-y-3"
     x-data="monthlyAnalyticsManager()"
     @scroll.window="activeMenu = null"
     @resize.window="activeMenu = null">

    <!-- 1. Top Header Bar (Strict Icon + Title + Action Buttons ONLY - Zero Subtitle) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-3 rounded-xl border border-slate-200/90 shadow-2xs">
        <div class="flex items-center gap-2.5">
            <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                <i class="fas fa-chart-column"></i>
            </div>
            <h1 class="text-xs sm:text-sm font-bold text-slate-900 tracking-tight">{{ __('Monthly Analytics') }}</h1>
        </div>

        <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
            <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}"
               class="px-3 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-semibold text-xs border border-emerald-200 transition inline-flex items-center gap-1.5 shadow-2xs cursor-pointer"
               title="{{ __('Export Monthly Report as CSV') }}">
                <i class="fas fa-file-excel text-[11px]"></i>
                <span>{{ __('Export CSV') }}</span>
            </a>

            <a href="{{ route('tenant.customers.bulk-payments') }}"
               class="px-3 py-1.5 rounded-lg bg-cyan-50 hover:bg-cyan-100 text-cyan-700 font-semibold text-xs border border-cyan-200 transition inline-flex items-center gap-1.5 shadow-2xs cursor-pointer">
                <i class="fas fa-layer-group text-[10px]"></i>
                <span>{{ __('Bulk Payments') }}</span>
            </a>

            <a href="{{ route('tenant.customers.index') }}"
               class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs border border-slate-200/80 transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-users text-[10px]"></i>
                <span>{{ __('All Customers') }}</span>
            </a>
        </div>
    </div>

    <!-- 2. KPI Summary Strip (Strictly 6 Cards) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between gap-2">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-slate-500 uppercase tracking-wider block truncate">{{ __('Active Base') }}</span>
                <span class="text-[13px] font-bold text-slate-900 font-mono leading-tight block">{{ number_format($kpis['total_subscribers']) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-cyan-50 text-cyan-600 flex items-center justify-center text-[10px] border border-cyan-100 flex-shrink-0">
                <i class="fas fa-users"></i>
            </div>
        </div>

        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between gap-2">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-purple-700 uppercase tracking-wider block truncate">{{ __(':year Added', ['year' => $selectedYear]) }}</span>
                <span class="text-[13px] font-bold text-purple-700 font-mono leading-tight block">+{{ number_format($kpis['year_new']) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-purple-50 text-purple-600 flex items-center justify-center text-[10px] border border-purple-100 flex-shrink-0">
                <i class="fas fa-user-plus"></i>
            </div>
        </div>

        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between gap-2">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-indigo-700 uppercase tracking-wider block truncate">{{ __(':year Collection', ['year' => $selectedYear]) }}</span>
                <span class="text-[13px] font-bold text-indigo-700 font-mono leading-tight block">@currency($kpis['year_collection'])</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-indigo-50 text-indigo-600 flex items-center justify-center text-[10px] border border-indigo-100 flex-shrink-0">
                <i class="fas fa-coins"></i>
            </div>
        </div>

        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between gap-2">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-emerald-700 uppercase tracking-wider block truncate">{{ $isCurrentYear ? __('This Mo. Paid') : __('Last Mo. Paid') }}</span>
                <span class="text-[13px] font-bold text-emerald-600 font-mono leading-tight block">{{ number_format($kpis['ref_recharged']) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-emerald-50 text-emerald-600 flex items-center justify-center text-[10px] border border-emerald-100 flex-shrink-0">
                <i class="fas fa-circle-check"></i>
            </div>
        </div>

        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between gap-2">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-rose-700 uppercase tracking-wider block truncate">{{ $isCurrentYear ? __('This Mo. Unpaid') : __('Last Mo. Unpaid') }}</span>
                <span class="text-[13px] font-bold text-rose-600 font-mono leading-tight block">{{ number_format($kpis['ref_non_recharged']) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-rose-50 text-rose-600 flex items-center justify-center text-[10px] border border-rose-100 flex-shrink-0">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
        </div>

        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between gap-2">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-amber-700 uppercase tracking-wider block truncate">{{ $isCurrentYear ? __('This Mo. Collect') : __('Avg. Recharge Rate') }}</span>
                <span class="text-[13px] font-bold text-amber-700 font-mono leading-tight block">
                    @if($isCurrentYear)
                        @currency($kpis['ref_collection'])
                    @else
                        {{ $kpis['avg_recharge_rate'] }}%
                    @endif
                </span>
            </div>
            <div class="w-6 h-6 rounded-md bg-amber-50 text-amber-600 flex items-center justify-center text-[10px] border border-amber-100 flex-shrink-0">
                <i class="fas fa-file-invoice-dollar"></i>
            </div>
        </div>
    </div>

    <!-- 3. Search & Multi-Filter Toolbar (Customer-Standard 2-Tier System, Uniform h-[34px]) -->
    <div class="bg-white p-2.5 rounded-xl border border-slate-200/90 shadow-2xs space-y-2.5"
         x-data='{!! json_encode(["showMoreFilters" => $isMoreOpen, "searchQuery" => $search ?? ""], JSON_HEX_APOS) !!}'>
        <form method="GET" action="{{ route('tenant.customers.monthly-analytics') }}" class="space-y-2.5">
            <!-- Tier 1: Primary Filter Bar -->
            <div class="flex flex-col md:flex-row items-stretch md:items-center gap-2">
                <!-- Live Search Month -->
                <div class="relative flex-1 min-w-[200px]">
                    <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-xs pointer-events-none transition-colors"
                       :class="searchQuery ? 'text-cyan-600 font-bold' : 'text-slate-400'"></i>
                    <input type="text"
                           name="search"
                           x-model="searchQuery"
                           placeholder="{{ __('Search month (e.g. Jan, Nov)...') }}"
                           autocomplete="off"
                           class="w-full h-[34px] pl-8 pr-7 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition placeholder:text-slate-400">
                    <button type="button"
                            x-show="searchQuery && searchQuery.length > 0"
                            x-cloak
                            @click="searchQuery = ''; $nextTick(() => $el.closest('form').submit())"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-rose-500 p-0.5 text-xs transition cursor-pointer"
                            title="{{ __('Clear Search') }}">
                        <i class="fas fa-times-circle"></i>
                    </button>
                </div>

                <!-- Primary Dropdown 1: Year -->
                <div class="w-full md:w-32 flex-shrink-0">
                    <select name="year"
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-semibold text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        @foreach($years as $yr)
                            <option value="{{ $yr }}" {{ (int)$selectedYear === (int)$yr ? 'selected' : '' }}>
                                {{ __('Year: :year', ['year' => $yr]) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                @if(!$isResellerUser)
                    <!-- Primary Dropdown 2: Territory Scope -->
                    <div class="w-full md:w-44 flex-shrink-0">
                        <select name="scope"
                                onchange="this.form.submit()"
                                class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition text-slate-700 font-medium">
                            <option value="all" {{ $scope === 'all' ? 'selected' : '' }}>{{ __('All Scopes (Direct + Sub-ISP)') }}</option>
                            <option value="isp" {{ $scope === 'isp' ? 'selected' : '' }}>{{ __('ISP Direct Retail Only') }}</option>
                            <option value="reseller" {{ $scope === 'reseller' ? 'selected' : '' }}>{{ __('Sub-ISP Franchise Only') }}</option>
                        </select>
                    </div>
                @endif

                <!-- Primary Dropdown 3: Coverage Zone -->
                <div class="w-full md:w-40 flex-shrink-0">
                    <select name="zone"
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition text-slate-700">
                        <option value="">{{ __('All Zones / Areas') }}</option>
                        @foreach($zones as $z)
                            <option value="{{ $z }}" {{ $zone === $z ? 'selected' : '' }}>{{ $z }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- More Filters Accordion Toggle Button -->
                <button type="button"
                        @click="showMoreFilters = !showMoreFilters"
                        :class="showMoreFilters || {{ $activeMoreCount }} > 0 ? 'bg-cyan-50 text-cyan-700 border-cyan-300 ring-1 ring-cyan-200' : 'bg-slate-50 hover:bg-slate-100 text-slate-700 border-slate-200'"
                        class="h-[34px] py-0 px-2.5 rounded-lg border text-xs font-medium transition flex items-center justify-center gap-1.5 flex-shrink-0 cursor-pointer shadow-2xs"
                        title="{{ __('Toggle Advanced Filters') }}">
                    <i class="fas fa-sliders-h text-[10px]"></i>
                    <span>{{ __('More Filters') }}</span>
                    @if($activeMoreCount > 0)
                        <span class="px-1.5 py-0.2 rounded-full text-[9px] font-bold bg-cyan-600 text-white leading-none">
                            {{ $activeMoreCount }}
                        </span>
                    @endif
                    <i class="fas text-[9px] transition-transform duration-200"
                       :class="showMoreFilters ? 'fa-chevron-up text-cyan-600' : 'fa-chevron-down text-slate-400'"></i>
                </button>

                <!-- Strict Filter & Reset Button Sequence -->
                <div class="flex items-center gap-1.5 flex-shrink-0 md:ml-auto">
                    <button type="submit"
                            class="h-[34px] bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-0 px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer"
                            title="{{ __('Apply Filters') }}">
                        <i class="fas fa-filter text-[10px]"></i>
                        <span>{{ __('Filter') }}</span>
                    </button>
                    <a href="{{ route('tenant.customers.monthly-analytics') }}"
                       class="h-[34px] bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-0 px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer"
                       title="{{ __('Reset Filters') }}">
                        <i class="fas fa-rotate-left text-[10px]"></i>
                        <span>{{ __('Reset') }}</span>
                    </a>
                </div>
            </div>

            <!-- Tier 2: Secondary Filters Drawer -->
            <div x-show="showMoreFilters"
                 x-cloak
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 -translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 -translate-y-2"
                 class="pt-2 border-t border-slate-100 flex flex-wrap items-center gap-2">

                @if(!$isResellerUser)
                    <!-- Reseller / Partner Filter -->
                    <div class="flex-1 min-w-[170px]">
                        <select name="reseller_id"
                                onchange="this.form.submit()"
                                class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition text-slate-700">
                            <option value="">{{ __('All Reseller Partners') }}</option>
                            @foreach($resellers as $res)
                                <option value="{{ $res->id }}" {{ (string)$resellerId === (string)$res->id ? 'selected' : '' }}>
                                    {{ $res->name }} ({{ $res->code ?: 'RES-' . $res->id }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <!-- Recharge Rate Performance Tier -->
                <div class="flex-1 min-w-[160px]">
                    <select name="rate_tier"
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition text-slate-700">
                        <option value="">{{ __('All Performance Tiers') }}</option>
                        <option value="excellent" {{ ($rateTier ?? '') === 'excellent' ? 'selected' : '' }}>{{ __('Excellent (85%+)') }}</option>
                        <option value="moderate" {{ ($rateTier ?? '') === 'moderate' ? 'selected' : '' }}>{{ __('Moderate (60% - 84%)') }}</option>
                        <option value="low" {{ ($rateTier ?? '') === 'low' ? 'selected' : '' }}>{{ __('Low (< 60%)') }}</option>
                    </select>
                </div>

                <!-- Month Activity Filter -->
                <div class="flex-1 min-w-[160px]">
                    <select name="activity_filter"
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition text-slate-700">
                        <option value="">{{ __('All Month Activity') }}</option>
                        <option value="with_collections" {{ ($activityFilter ?? '') === 'with_collections' ? 'selected' : '' }}>{{ __('Has Collections') }}</option>
                        <option value="with_new" {{ ($activityFilter ?? '') === 'with_new' ? 'selected' : '' }}>{{ __('Has New Additions') }}</option>
                        <option value="has_unpaid" {{ ($activityFilter ?? '') === 'has_unpaid' ? 'selected' : '' }}>{{ __('Has Unpaid Accounts') }}</option>
                    </select>
                </div>

                <!-- Sort Order -->
                <div class="flex-1 min-w-[160px]">
                    <select name="sort_order"
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition text-slate-700">
                        <option value="newest" {{ ($sortOrder ?? 'newest') === 'newest' ? 'selected' : '' }}>{{ __('Latest Month First') }}</option>
                        <option value="oldest" {{ ($sortOrder ?? '') === 'oldest' ? 'selected' : '' }}>{{ __('Oldest Month First') }}</option>
                        <option value="highest_collection" {{ ($sortOrder ?? '') === 'highest_collection' ? 'selected' : '' }}>{{ __('Highest Collection') }}</option>
                        <option value="highest_rate" {{ ($sortOrder ?? '') === 'highest_rate' ? 'selected' : '' }}>{{ __('Highest Recharge Rate') }}</option>
                    </select>
                </div>
            </div>
        </form>
    </div>

    <!-- 4. Master Table (<table class="saas-table"> - 7 Minimal Core Columns, Natural Height, Strict Alignment) -->
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-2xs">
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-36 text-center font-semibold">{{ __('Month & Year') }}</th>
                        <th class="w-28 text-center font-semibold">{{ __('New Added') }}</th>
                        <th class="w-32 text-center font-semibold">{{ __('Paid (Recharged)') }}</th>
                        <th class="w-32 text-center font-semibold">{{ __('Unpaid (Due)') }}</th>
                        <th class="w-36 text-center font-semibold">{{ __('Total Collection') }}</th>
                        <th class="w-28 text-center font-semibold">{{ __('Recharge Rate') }}</th>
                        <th class="w-14 text-center font-semibold no-sort">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $r)
                        @php
                            $rate = $r['recharge_rate'];
                            $rateBadgeClass = $rate >= 85
                                ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                : ($rate >= 60 ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-rose-50 text-rose-700 border-rose-200');

                            $rowPayload = array_merge($r, [
                                'collection_formatted' => $formatCurrency((float) $r['collection']),
                                'new_url' => route('tenant.customers.index', ['created_month' => $r['month_key'], 'scope' => $scope, 'reseller_id' => $resellerId, 'zone' => $zone]),
                                'recharged_url' => route('tenant.customers.index', ['recharged_month' => $r['month_key'], 'scope' => $scope, 'reseller_id' => $resellerId, 'zone' => $zone]),
                                'unpaid_url' => route('tenant.customers.index', ['unpaid_month' => $r['month_key'], 'scope' => $scope, 'reseller_id' => $resellerId, 'zone' => $zone]),
                            ]);
                        @endphp
                        <tr>
                            <!-- 1. Month Label (Left Align, Strictly Single Datum, Chronological Sort Key) -->
                            <td class="text-left font-semibold text-slate-900" data-sort-value="{{ $r['month_key'] }}">
                                {{ $r['month_label'] }}
                            </td>

                            <!-- 2. New Customers Added (Center Align) -->
                            <td class="text-center font-mono font-bold {{ $r['new_customers'] > 0 ? 'text-purple-700' : 'text-slate-400' }}"
                                data-sort-value="{{ $r['new_customers'] }}">
                                {{ $r['new_customers'] > 0 ? '+' . number_format($r['new_customers']) : '0' }}
                            </td>

                            <!-- 3. Recharged / Paid (Center Align) -->
                            <td class="text-center font-mono font-bold {{ $r['recharged_customers'] > 0 ? 'text-emerald-700' : 'text-slate-400' }}"
                                data-sort-value="{{ $r['recharged_customers'] }}">
                                {{ number_format($r['recharged_customers']) }}
                            </td>

                            <!-- 4. Unpaid / Non-Recharged (Center Align) -->
                            <td class="text-center font-mono font-bold {{ $r['non_recharged_customers'] > 0 ? 'text-rose-600' : 'text-slate-400' }}"
                                data-sort-value="{{ $r['non_recharged_customers'] }}">
                                {{ number_format($r['non_recharged_customers']) }}
                            </td>

                            <!-- 5. Total Collection (Right Align Accounting Standard) -->
                            <td class="text-right font-mono font-bold text-slate-900"
                                data-sort-value="{{ (float) $r['collection'] }}">
                                @currency($r['collection'])
                            </td>

                            <!-- 6. Recharge Rate (Center Align) -->
                            <td class="text-center font-mono" data-sort-value="{{ $rate }}">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md border text-[10.5px] font-bold {{ $rateBadgeClass }}">
                                    {{ $rate }}%
                                </span>
                            </td>

                            <!-- 7. Action (Global Floating 3-Dot Menu) -->
                            <td class="text-center">
                                <button type="button"
                                        @click="toggleMenu({{ Js::from($rowPayload) }}, $event)"
                                        class="w-6 h-6 rounded-md hover:bg-slate-100 text-slate-400 hover:text-slate-700 transition cursor-pointer inline-flex items-center justify-center">
                                    <i class="fas fa-ellipsis-v text-[10px]"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-10 px-4 text-center bg-slate-50/40">
                                <div class="max-w-sm mx-auto flex flex-col items-center gap-2.5">
                                    <div class="w-11 h-11 rounded-xl bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-base shadow-2xs">
                                        <i class="fas fa-chart-line"></i>
                                    </div>
                                    <div class="space-y-0.5">
                                        <p class="text-xs font-bold text-slate-800">{{ __('No analytics records match your filters.') }}</p>
                                        <p class="text-[11px] text-slate-500 whitespace-normal">{{ __('Try adjusting the selected year, partner, zone, or filter criteria.') }}</p>
                                    </div>
                                    <div class="pt-1">
                                        <a href="{{ route('tenant.customers.monthly-analytics') }}"
                                           class="px-3 py-1.5 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                                            <i class="fas fa-rotate-left text-[10px]"></i>
                                            <span>{{ __('Reset Filters') }}</span>
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- 5. Global Floating 3-Dot Action Menu -->
    <div x-show="activeMenu !== null"
         x-cloak
         @click.outside="activeMenu = null"
         :style="{ top: menuPos.top, bottom: menuPos.bottom, right: menuPos.right, left: menuPos.left }"
         class="fixed z-50 w-48 bg-white rounded-xl shadow-2xl border border-slate-200 py-1 text-left text-xs divide-y divide-slate-100"
         style="display: none;">

        <div class="py-1">
            <button type="button"
                    @click="const item = activeMenu; activeMenu = null; openDetailsModal(item)"
                    class="w-full px-3 py-1.5 hover:bg-slate-50 text-slate-700 flex items-center gap-2 transition text-left cursor-pointer">
                <i class="fas fa-eye text-cyan-600 w-3.5 text-center text-[11px]"></i>
                <span>{{ __('View Breakdown') }}</span>
            </button>

            <a :href="activeMenu?.new_url || '#'"
               target="_blank"
               class="w-full px-3 py-1.5 hover:bg-slate-50 text-slate-700 flex items-center gap-2 transition text-left cursor-pointer">
                <i class="fas fa-user-plus text-purple-600 w-3.5 text-center text-[11px]"></i>
                <span>{{ __('View New Added') }}</span>
            </a>

            <a :href="activeMenu?.recharged_url || '#'"
               target="_blank"
               class="w-full px-3 py-1.5 hover:bg-slate-50 text-slate-700 flex items-center gap-2 transition text-left cursor-pointer">
                <i class="fas fa-circle-check text-emerald-600 w-3.5 text-center text-[11px]"></i>
                <span>{{ __('View Recharged') }}</span>
            </a>

            <a :href="activeMenu?.unpaid_url || '#'"
               target="_blank"
               class="w-full px-3 py-1.5 hover:bg-slate-50 text-slate-700 flex items-center gap-2 transition text-left cursor-pointer">
                <i class="fas fa-triangle-exclamation text-rose-600 w-3.5 text-center text-[11px]"></i>
                <span>{{ __('View Unpaid') }}</span>
            </a>
        </div>

        <div class="py-1">
            <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}"
               class="w-full px-3 py-1.5 hover:bg-emerald-50 text-emerald-700 flex items-center gap-2 transition text-left cursor-pointer">
                <i class="fas fa-file-excel text-emerald-600 w-3.5 text-center text-[11px]"></i>
                <span>{{ __('Export Year CSV') }}</span>
            </a>
        </div>
    </div>

    <!-- 6. Production-Grade Natural Modal: Month Breakdown Drilldown -->
    <div x-show="modalOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;">

        <div @click.away="modalOpen = false"
             x-transition:enter="transition ease-out duration-200 transform"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150 transform"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden">

            <!-- Soft Natural Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800" x-text="(activeMonth?.month_full || '') + ' — ' + i18n.perfSummary"></h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">{{ __('Monthly subscriber retention and collection summary') }}</p>
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
                <div class="grid grid-cols-3 gap-2">
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80 text-center">
                        <span class="text-[9.5px] text-slate-500 font-medium uppercase block">{{ __('Total Active Base') }}</span>
                        <span class="text-sm font-bold font-mono text-slate-800" x-text="activeMonth?.base_customers ?? 0"></span>
                    </div>
                    <div class="p-2.5 bg-purple-50/60 rounded-lg border border-purple-200/80 text-center">
                        <span class="text-[9.5px] text-purple-700 font-medium uppercase block">{{ __('New Onboarded') }}</span>
                        <span class="text-sm font-bold font-mono text-purple-800" x-text="'+' + (activeMonth?.new_customers ?? 0)"></span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80 text-center">
                        <span class="text-[9.5px] text-slate-500 font-medium uppercase block">{{ __('Transactions') }}</span>
                        <span class="text-sm font-bold font-mono text-slate-800" x-text="activeMonth?.transactions ?? 0"></span>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div class="p-2.5 bg-emerald-50/60 rounded-lg border border-emerald-200/80">
                        <span class="text-[10px] text-emerald-700 font-medium block">{{ __('Paid (Recharged)') }}</span>
                        <span class="text-sm font-bold font-mono text-emerald-800" x-text="activeMonth?.recharged_customers ?? 0"></span>
                    </div>
                    <div class="p-2.5 bg-rose-50/60 rounded-lg border border-rose-200/80">
                        <span class="text-[10px] text-rose-700 font-medium block">{{ __('Unpaid (Due)') }}</span>
                        <span class="text-sm font-bold font-mono text-rose-800" x-text="activeMonth?.non_recharged_customers ?? 0"></span>
                    </div>
                </div>

                <!-- Financial Summary Banner (Dynamic Global Currency Format) -->
                <div class="p-3 bg-cyan-50/50 rounded-lg border border-cyan-200 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] text-cyan-800 font-medium block">{{ __('Total Collection') }}</span>
                        <span class="text-base font-bold font-mono text-cyan-900" x-text="activeMonth?.collection_formatted || '--'"></span>
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] text-cyan-800 font-medium block">{{ __('Recharge Success Rate') }}</span>
                        <span class="text-sm font-bold font-mono text-cyan-700" x-text="(activeMonth?.recharge_rate ?? 0) + '%'"></span>
                    </div>
                </div>

                <!-- Fast Drilldown Links -->
                <div class="pt-2 border-t border-slate-100 flex flex-wrap gap-2">
                    <a :href="activeMonth?.new_url || '#'"
                       target="_blank"
                       class="flex-1 py-1.5 px-2.5 rounded-lg bg-purple-50 hover:bg-purple-100 text-purple-700 font-semibold text-center border border-purple-200 transition cursor-pointer">
                        <i class="fas fa-user-plus mr-1"></i> {{ __('View New Added') }}
                    </a>
                    <a :href="activeMonth?.recharged_url || '#'"
                       target="_blank"
                       class="flex-1 py-1.5 px-2.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-semibold text-center border border-emerald-200 transition cursor-pointer">
                        <i class="fas fa-circle-check mr-1"></i> {{ __('View Recharged') }}
                    </a>
                    <a :href="activeMonth?.unpaid_url || '#'"
                       target="_blank"
                       class="flex-1 py-1.5 px-2.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 font-semibold text-center border border-rose-200 transition cursor-pointer">
                        <i class="fas fa-circle-xmark mr-1"></i> {{ __('View Unpaid') }}
                    </a>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end">
                <button type="button"
                        @click="modalOpen = false"
                        class="px-3.5 py-1.5 rounded-lg border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs transition cursor-pointer">
                    {{ __('Close') }}
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
        activeMenu: null,
        menuPos: { top: 'auto', bottom: 'auto', right: '10px', left: 'auto' },
        modalOpen: false,
        activeMonth: null,

        i18n: {
            perfSummary: @json(__('Performance Summary'))
        },

        toggleMenu(item, event) {
            if (this.activeMenu?.month_key === item.month_key) {
                this.activeMenu = null;
                return;
            }
            this.activeMenu = item;
            const rect = event.currentTarget.getBoundingClientRect();
            const dropdownHeight = 200;
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

        openDetailsModal(row) {
            this.activeMonth = row;
            this.modalOpen = true;
        }
    };
}
</script>
@endpush
