@extends('tenant.layouts.app')

@section('title', __('Coverage Zones') . ' - ' . ($tenant->company_name ?? $tenant->name))

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
        $cityFilter ?? null,
        $staffFilter ?? null,
        $subAreasFilter ?? null,
        $occupancyFilter ?? null,
    ])->filter(fn ($v) => $v !== null && $v !== '')->count();
    $isMoreOpen = $activeMoreCount > 0;
@endphp

<div class="space-y-3"
     x-data="zoneManager()"
     @scroll.window="activeMenu = null"
     @resize.window="activeMenu = null">

    <!-- Toast Notification Banner -->
    <div x-show="toast.show"
         x-cloak
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         class="fixed top-4 right-4 z-50 flex items-center gap-2 px-4 py-2.5 rounded-xl shadow-lg border text-xs font-medium"
         :class="{
             'bg-emerald-50 text-emerald-800 border-emerald-200': toast.type === 'success',
             'bg-rose-50 text-rose-800 border-rose-200': toast.type === 'error',
             'bg-blue-50 text-blue-800 border-blue-200': toast.type === 'info'
         }"
         style="display: none;">
        <i class="fas text-sm" :class="{
            'fa-check-circle text-emerald-600': toast.type === 'success',
            'fa-exclamation-circle text-rose-600': toast.type === 'error',
            'fa-info-circle text-blue-600': toast.type === 'info'
        }"></i>
        <span x-text="toast.message"></span>
    </div>

    <!-- 1. Top Header Bar (Icon + Title + Action Buttons ONLY - Zero Subtitle) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-3 rounded-xl border border-slate-200/90 shadow-2xs">
        <div class="flex items-center gap-2.5">
            <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                <i class="fas fa-location-dot"></i>
            </div>
            <h1 class="text-xs sm:text-sm font-bold text-slate-900 tracking-tight">{{ __('Coverage Zones') }}</h1>
        </div>

        <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
            <a href="{{ route('tenant.customers.zones.import-template') }}"
               class="px-3 py-1.5 rounded-lg bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs border border-slate-200 shadow-2xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-file-excel text-emerald-600 text-[11px]"></i>
                <span>{{ __('Excel Template') }}</span>
            </a>
            <button type="button"
                    @click="openImportModal()"
                    class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-file-import text-[11px]"></i>
                <span>{{ __('Import Excel / CSV') }}</span>
            </button>
            <button type="button"
                    @click="openCreateModal()"
                    class="px-3 py-1.5 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-plus text-[11px]"></i>
                <span>{{ __('Add New Zone') }}</span>
            </button>
        </div>
    </div>

    <!-- 2. KPI Summary Strip (Strictly 6 Cards) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between gap-2">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-slate-500 uppercase tracking-wider block truncate">{{ __('Total Zones') }}</span>
                <span class="text-[13px] font-bold text-slate-900 font-mono leading-tight block">{{ number_format($totalZones) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-cyan-50 text-cyan-600 flex items-center justify-center text-[10px] border border-cyan-100 flex-shrink-0">
                <i class="fas fa-map-location-dot"></i>
            </div>
        </div>

        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between gap-2">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-slate-500 uppercase tracking-wider block truncate">{{ __('Active Zones') }}</span>
                <span class="text-[13px] font-bold text-emerald-600 font-mono leading-tight block">{{ number_format($activeZones) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-emerald-50 text-emerald-600 flex items-center justify-center text-[10px] border border-emerald-100 flex-shrink-0">
                <i class="fas fa-check-circle"></i>
            </div>
        </div>

        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between gap-2">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-slate-500 uppercase tracking-wider block truncate">{{ __('Inactive Zones') }}</span>
                <span class="text-[13px] font-bold text-rose-600 font-mono leading-tight block">{{ number_format($inactiveZones) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-rose-50 text-rose-600 flex items-center justify-center text-[10px] border border-rose-100 flex-shrink-0">
                <i class="fas fa-ban"></i>
            </div>
        </div>

        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between gap-2">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-slate-500 uppercase tracking-wider block truncate">{{ __('Subscribers') }}</span>
                <span class="text-[13px] font-bold text-blue-600 font-mono leading-tight block">{{ number_format($totalSubscribersAssigned) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-blue-50 text-blue-600 flex items-center justify-center text-[10px] border border-blue-100 flex-shrink-0">
                <i class="fas fa-users"></i>
            </div>
        </div>

        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between gap-2">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-slate-500 uppercase tracking-wider block truncate">{{ __('Top Density Zone') }}</span>
                <span class="text-[11px] font-bold text-slate-800 font-mono leading-tight block truncate" title="{{ $topZoneName }}">{{ $topZoneName }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-amber-50 text-amber-600 flex items-center justify-center text-[10px] border border-amber-100 flex-shrink-0">
                <i class="fas fa-ranking-star"></i>
            </div>
        </div>

        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between gap-2">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-slate-500 uppercase tracking-wider block truncate">{{ __('Assigned Staff') }}</span>
                <span class="text-[13px] font-bold text-purple-600 font-mono leading-tight block">{{ number_format($assignedStaffCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-purple-50 text-purple-600 flex items-center justify-center text-[10px] border border-purple-100 flex-shrink-0">
                <i class="fas fa-user-gear"></i>
            </div>
        </div>
    </div>

    <!-- 3. Search & Multi-Filter Toolbar (Customer-Standard 2-Tier System, Strict Uniform h-[34px]) -->
    <div class="bg-white p-2.5 rounded-xl border border-slate-200/90 shadow-2xs space-y-2.5"
         x-data='{!! json_encode(["showMoreFilters" => $isMoreOpen, "searchQuery" => $search ?? ""], JSON_HEX_APOS) !!}'>
        <form method="GET" action="{{ route('tenant.customers.zones') }}" class="space-y-2.5">
            <!-- Tier 1: Primary Filter Bar -->
            <div class="flex flex-col md:flex-row items-stretch md:items-center gap-2">
                <!-- Live Search Input -->
                <div class="relative flex-1 min-w-[200px]">
                    <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-xs pointer-events-none transition-colors"
                       :class="searchQuery ? 'text-cyan-600 font-bold' : 'text-slate-400'"></i>
                    <input type="text"
                           name="search"
                           x-model="searchQuery"
                           placeholder="{{ __('Search zone name, code, sub-area, staff...') }}"
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

                @if(!$isResellerUser)
                    <!-- Primary Dropdown 1: Territory / Partner Scope -->
                    <div class="w-full md:w-44 flex-shrink-0">
                        <select name="reseller_id"
                                onchange="this.form.submit()"
                                class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                            <option value="all" {{ ($selectedResellerId ?? 'all') === 'all' ? 'selected' : '' }}>{{ __('All Partners / Direct') }}</option>
                            <option value="isp" {{ ($selectedResellerId ?? '') === 'isp' ? 'selected' : '' }}>{{ __('HQ Direct Areas') }}</option>
                            @foreach($allResellers ?? [] as $r)
                                <option value="{{ $r->id }}" {{ (string)($selectedResellerId ?? '') === (string)$r->id ? 'selected' : '' }}>
                                    {{ __('Partner') }}: {{ $r->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <!-- Primary Dropdown 2: Status -->
                <div class="w-full md:w-32 flex-shrink-0">
                    <select name="status"
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="all" {{ ($statusFilter ?? 'all') === 'all' ? 'selected' : '' }}>{{ __('All Status') }}</option>
                        <option value="active" {{ ($statusFilter ?? '') === 'active' ? 'selected' : '' }}>{{ __('Active Only') }}</option>
                        <option value="inactive" {{ ($statusFilter ?? '') === 'inactive' ? 'selected' : '' }}>{{ __('Inactive Only') }}</option>
                    </select>
                </div>

                <!-- Pagination Selector -->
                <div class="w-full md:w-28 flex-shrink-0">
                    <select name="per_page"
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition font-medium text-slate-700">
                        @foreach([15, 20, 30, 50, 100, 250, 500] as $pp)
                            <option value="{{ $pp }}" {{ ($perPage ?? 20) == $pp ? 'selected' : '' }}>{{ $pp }} / {{ __('page') }}</option>
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
                    <a href="{{ route('tenant.customers.zones') }}"
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

                <!-- City / Upazila -->
                <div class="flex-1 min-w-[150px]">
                    <select name="city_upazila"
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="">{{ __('All Cities / Upazilas') }}</option>
                        @foreach($distinctCities ?? [] as $cityOpt)
                            <option value="{{ $cityOpt }}" {{ ($cityFilter ?? '') === $cityOpt ? 'selected' : '' }}>{{ $cityOpt }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Assigned Staff -->
                <div class="flex-1 min-w-[160px]">
                    <select name="staff_id"
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="">{{ __('All Assigned Staff') }}</option>
                        <option value="unassigned" {{ ($staffFilter ?? '') === 'unassigned' ? 'selected' : '' }}>{{ __('Unassigned Staff') }}</option>
                        @foreach($allStaff ?? [] as $staffOpt)
                            <option value="{{ $staffOpt->id }}" {{ (string)($staffFilter ?? '') === (string)$staffOpt->id ? 'selected' : '' }}>{{ $staffOpt->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Sub-Areas Presence -->
                <div class="flex-1 min-w-[150px]">
                    <select name="has_sub_areas"
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="">{{ __('All Sub-Area States') }}</option>
                        <option value="with_sub" {{ ($subAreasFilter ?? '') === 'with_sub' ? 'selected' : '' }}>{{ __('Has Sub-Areas') }}</option>
                        <option value="without_sub" {{ ($subAreasFilter ?? '') === 'without_sub' ? 'selected' : '' }}>{{ __('No Sub-Areas') }}</option>
                    </select>
                </div>

                <!-- Subscriber Occupancy -->
                <div class="flex-1 min-w-[150px]">
                    <select name="occupancy"
                            onchange="this.form.submit()"
                            class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="">{{ __('All Occupancy') }}</option>
                        <option value="populated" {{ ($occupancyFilter ?? '') === 'populated' ? 'selected' : '' }}>{{ __('Has Subscribers') }}</option>
                        <option value="empty" {{ ($occupancyFilter ?? '') === 'empty' ? 'selected' : '' }}>{{ __('Empty Zones') }}</option>
                    </select>
                </div>
            </div>
        </form>
    </div>

    <!-- 4. Master Compact Table (<table class="saas-table"> — 7 Minimal Core Columns, Natural Height, Zero min-h Void) -->
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-2xs">
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-24 text-center font-semibold">{{ __('Code') }}</th>
                        <th class="text-center font-semibold">{{ __('Zone / Area') }}</th>
                        <th class="text-center font-semibold">{{ __('Sub Areas') }}</th>
                        <th class="text-center font-semibold">{{ __('Assigned Staff') }}</th>
                        <th class="w-28 text-center font-semibold">{{ __('Subscribers') }}</th>
                        <th class="w-24 text-center font-semibold">{{ __('Status') }}</th>
                        <th class="w-14 text-center font-semibold no-sort">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($zones as $z)
                        @php
                            $metrics = $zoneMetrics[$z->id] ?? ['subscribers_count' => 0, 'active_subscribers' => 0, 'mrr' => 0.0];
                            $subCount = (int) $metrics['subscribers_count'];
                            $activeSubs = (int) $metrics['active_subscribers'];
                            $mrrAmount = (float) $metrics['mrr'];

                            $assignedStaffName = $z->assignedStaff?->name ?: ($z->in_charge_name ?: '');
                            $assignedStaffPhone = $z->assignedStaff?->phone ?: ($z->in_charge_phone ?: '');
                            $assignedStaffRole = $z->assignedStaff?->role_badge['label'] ?? '';

                            $subAreasList = $z->sub_areas_list;
                            $zoneData = [
                                'id' => $z->id,
                                'code' => $z->code,
                                'name' => $z->name,
                                'sub_areas' => $subAreasList,
                                'sub_areas_count' => count($subAreasList),
                                'reseller_id' => $z->reseller_id ? (string)$z->reseller_id : '',
                                'reseller_name' => $z->reseller?->name ?? __('HQ Direct'),
                                'reseller_code' => $z->reseller?->code ?? '',
                                'staff_id' => $z->staff_id ? (string)$z->staff_id : '',
                                'staff_name' => $assignedStaffName,
                                'staff_phone' => $assignedStaffPhone,
                                'staff_role' => $assignedStaffRole ? __($assignedStaffRole) : '',
                                'city_upazila' => $z->city_upazila ?? '',
                                'in_charge_name' => $z->in_charge_name ?? '',
                                'in_charge_phone' => $z->in_charge_phone ?? '',
                                'description' => $z->description ?? '',
                                'is_active' => (bool)$z->is_active,
                                'subscribers_count' => $subCount,
                                'active_subscribers' => $activeSubs,
                                'mrr_formatted' => $formatCurrency($mrrAmount),
                                'created_at' => $z->created_at?->format('d M, Y h:i A') ?? '--',
                            ];
                        @endphp
                        <tr>
                            <!-- 1. Code -->
                            <td class="text-center font-mono text-cyan-800 font-semibold">
                                {{ $z->code }}
                            </td>

                            <!-- 2. Zone Name (Strictly Single Datum) -->
                            <td class="text-left font-semibold text-slate-900">
                                {{ $z->name }}
                            </td>

                            <!-- 3. Sub Areas (Single-Line Chips + "+X more" modal trigger) -->
                            <td class="text-left">
                                @if(!empty($subAreasList))
                                    <div class="flex items-center gap-1 flex-nowrap whitespace-nowrap">
                                        @foreach(array_slice($subAreasList, 0, 2) as $sa)
                                            <span class="inline-block px-1.5 py-0.5 bg-slate-100 text-slate-700 rounded text-[10px] font-medium border border-slate-200/80 whitespace-nowrap">{{ $sa }}</span>
                                        @endforeach
                                        @if(count($subAreasList) > 2)
                                            <button type="button"
                                                    @click="openSubAreasModal({{ Js::from($zoneData) }})"
                                                    class="text-[10px] font-semibold text-cyan-700 bg-cyan-50 hover:bg-cyan-100 px-1.5 py-0.5 rounded border border-cyan-200/70 transition cursor-pointer whitespace-nowrap"
                                                    title="{{ implode(', ', array_slice($subAreasList, 2)) }}">
                                                +{{ count($subAreasList) - 2 }} {{ __('more') }}
                                            </button>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-slate-400">--</span>
                                @endif
                            </td>

                            <!-- 4. Assigned Staff (Strictly Single Datum) -->
                            <td class="text-left text-slate-700">
                                {{ $assignedStaffName ?: '--' }}
                            </td>

                            <!-- 5. Subscribers Count -->
                            <td class="text-center font-mono">
                                <a href="{{ route('tenant.customers.index', ['zone' => $z->name]) }}"
                                   class="inline-flex items-center gap-1 font-bold text-xs {{ $subCount > 0 ? 'text-cyan-700 hover:text-cyan-900 underline' : 'text-slate-400' }}"
                                   title="{{ __('View Subscribers') }}">
                                    <span>{{ $subCount }}</span>
                                </a>
                            </td>

                            <!-- 6. Status Badge (Toggle via Centralized 3-Dot Action Menu) -->
                            <td class="text-center">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-semibold border shadow-2xs {{ $z->is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $z->is_active ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                                    <span>{{ $z->is_active ? __('Active') : __('Inactive') }}</span>
                                </span>
                            </td>

                            <!-- 7. Action (Global Floating 3-Dot Menu) -->
                            <td class="text-center">
                                <button type="button"
                                        @click="toggleMenu({{ Js::from($zoneData) }}, $event)"
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
                                        <i class="fas fa-map-location-dot"></i>
                                    </div>
                                    <div class="space-y-0.5">
                                        <p class="text-xs font-bold text-slate-800">{{ __('No coverage zones found') }}</p>
                                        <p class="text-[11px] text-slate-500 whitespace-normal">{{ __('Define your operational areas or import zones from Excel / CSV.') }}</p>
                                    </div>
                                    <div class="flex items-center gap-2 pt-1">
                                        <button type="button"
                                                @click="openCreateModal()"
                                                class="px-3 py-1.5 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                                            <i class="fas fa-plus text-[10px]"></i>
                                            <span>{{ __('Add New Zone') }}</span>
                                        </button>
                                        <button type="button"
                                                @click="openImportModal()"
                                                class="px-3 py-1.5 rounded-lg bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs border border-slate-200 shadow-2xs transition inline-flex items-center gap-1.5 cursor-pointer">
                                            <i class="fas fa-file-import text-emerald-600 text-[10px]"></i>
                                            <span>{{ __('Import Excel / CSV') }}</span>
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($zones->hasPages())
            <div class="px-3 py-2 border-t border-slate-200 bg-slate-50/50">
                {{ $zones->links() }}
            </div>
        @endif
    </div>

    <!-- 5. Floating Action Dropdown Menu -->
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
                <span>{{ __('View Details') }}</span>
            </button>

            <button type="button"
                    @click="const item = activeMenu; activeMenu = null; openSubAreasModal(item)"
                    class="w-full px-3 py-1.5 hover:bg-slate-50 text-slate-700 flex items-center gap-2 transition text-left cursor-pointer">
                <i class="fas fa-layer-group text-emerald-600 w-3.5 text-center text-[11px]"></i>
                <span>{{ __('Manage Sub Areas') }}</span>
            </button>

            <a :href="'{{ url('admin/customers') }}?zone=' + encodeURIComponent(activeMenu ? activeMenu.name : '')"
               class="w-full px-3 py-1.5 hover:bg-slate-50 text-slate-700 flex items-center gap-2 transition text-left cursor-pointer">
                <i class="fas fa-users text-blue-600 w-3.5 text-center text-[11px]"></i>
                <span>{{ __('View Subscribers') }}</span>
            </a>
        </div>

        <div class="py-1">
            <button type="button"
                    @click="const item = activeMenu; activeMenu = null; toggleStatus(item.id, item.name)"
                    class="w-full px-3 py-1.5 hover:bg-slate-50 flex items-center gap-2 transition text-left cursor-pointer"
                    :class="activeMenu?.is_active ? 'text-rose-700 hover:text-rose-800' : 'text-emerald-700 hover:text-emerald-800'">
                <i class="fas w-3.5 text-center text-[11px]" :class="activeMenu?.is_active ? 'fa-toggle-off text-rose-500' : 'fa-toggle-on text-emerald-600'"></i>
                <span x-text="activeMenu?.is_active ? i18n.disableZone : i18n.enableZone"></span>
            </button>

            <button type="button"
                    @click="const item = activeMenu; activeMenu = null; openEditModal(item)"
                    class="w-full px-3 py-1.5 hover:bg-slate-50 text-slate-700 flex items-center gap-2 transition text-left cursor-pointer">
                <i class="fas fa-pen-to-square text-amber-600 w-3.5 text-center text-[11px]"></i>
                <span>{{ __('Edit Zone') }}</span>
            </button>

            <button type="button"
                    @click="const item = activeMenu; activeMenu = null; confirmDelete(item.id, item.name, item.subscribers_count)"
                    class="w-full px-3 py-1.5 hover:bg-rose-50 text-rose-600 flex items-center gap-2 transition text-left cursor-pointer">
                <i class="fas fa-trash-can text-rose-500 w-3.5 text-center text-[11px]"></i>
                <span>{{ __('Delete Zone') }}</span>
            </button>
        </div>
    </div>

    <!-- Modal 1: Add & Edit Coverage Zone -->
    <div x-show="modal.open"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;">

        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden"
             @click.away="if (!modal.loading) modal.open = false"
             x-transition:enter="transition ease-out duration-200 transform"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150 transform"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">

            <!-- Soft Natural Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0 shadow-2xs">
                        <i class="fas fa-location-dot"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800" x-text="modal.isEdit ? i18n.editCoverageZone : i18n.createCoverageZone"></h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">{{ __('Define area details, territory scope, and assigned technician') }}</p>
                    </div>
                </div>
                <button type="button"
                        @click="modal.open = false"
                        :disabled="modal.loading"
                        class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <form @submit.prevent="submitModal()">
                <div class="p-4 space-y-3.5 text-xs max-h-[75vh] overflow-y-auto">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="sm:col-span-2 space-y-1">
                            <label class="block text-slate-700 font-semibold text-[11px]">
                                {{ __('Zone / Area Name') }} <span class="text-rose-500">*</span>
                            </label>
                            <input type="text"
                                   x-model="modal.form.name"
                                   required
                                   placeholder="{{ __('Dhanmondi / Mirpur-10') }}"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 text-slate-800 focus:bg-white focus:border-cyan-500 transition">
                        </div>

                        <div class="space-y-1">
                            <label class="block text-slate-700 font-semibold text-[11px]">
                                {{ __('Zone Code') }}
                            </label>
                            <input type="text"
                                   x-model="modal.form.code"
                                   placeholder="{{ __('ZN-01') }}"
                                   title="{{ __('Auto-generated if empty') }}"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 text-slate-800 font-mono focus:bg-white focus:border-cyan-500 transition">
                        </div>
                    </div>

                    @if(!$isResellerUser)
                        <div class="space-y-1">
                            <label class="block text-slate-700 font-semibold text-[11px]">
                                {{ __('Assigned Partner / Territory') }}
                            </label>
                            <select x-model="modal.form.reseller_id"
                                    @change="onResellerScopeChange()"
                                    class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 text-slate-800 focus:bg-white focus:border-cyan-500 transition">
                                <option value="">{{ __('ISP HQ Direct (Retail Territory)') }}</option>
                                @foreach($allResellers ?? [] as $r)
                                    <option value="{{ $r->id }}">
                                        {{ __('Partner') }}: {{ $r->code ? "[{$r->code}] " : '' }}{{ $r->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="space-y-1">
                        <label class="block text-slate-700 font-semibold text-[11px]">
                            {{ __('City / Upazila') }} <span class="text-slate-400 font-normal">({{ __('Optional') }})</span>
                        </label>
                        <input type="text"
                               x-model="modal.form.city_upazila"
                               placeholder="{{ __('Dhaka North / Gazipur Sadar') }}"
                               class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 text-slate-800 focus:bg-white focus:border-cyan-500 transition">
                    </div>

                    <div class="space-y-1">
                        <label class="block text-slate-700 font-semibold text-[11px] flex items-center justify-between">
                            <span>{{ __('Assigned Field Technician / Staff') }}</span>
                            <span class="text-[10px] text-cyan-600 font-normal">{{ __('From Staff Directory') }}</span>
                        </label>
                        <select x-model="modal.form.staff_id"
                                @change="onStaffSelected()"
                                class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 text-slate-800 focus:bg-white focus:border-cyan-500 transition">
                            <option value="">-- {{ __('Unassigned') }} --</option>
                            <template x-for="s in filteredStaffList" :key="s.id">
                                <option :value="s.id" x-text="(s.staff_id ? '[' + s.staff_id + '] ' : '') + s.name + ' (' + s.role_label + ')'"></option>
                            </template>
                        </select>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="block text-slate-700 font-semibold text-[11px]">
                                {{ __('Contact Phone') }}
                            </label>
                            <input type="text"
                                   x-model="modal.form.in_charge_phone"
                                   placeholder="{{ __('01712-345678') }}"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 text-slate-800 font-mono focus:bg-white focus:border-cyan-500 transition">
                        </div>

                        <div class="space-y-1">
                            <label class="block text-slate-700 font-semibold text-[11px]">
                                {{ __('Area In-Charge Name') }} <span class="text-slate-400 font-normal">({{ __('Optional') }})</span>
                            </label>
                            <input type="text"
                                   x-model="modal.form.in_charge_name"
                                   placeholder="{{ __('Md. Rafiqul Islam') }}"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 text-slate-800 focus:bg-white focus:border-cyan-500 transition">
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="block text-slate-700 font-semibold text-[11px]">
                            {{ __('Sub Areas (Comma separated)') }}
                        </label>
                        <textarea x-model="modal.form.description"
                                  rows="2"
                                  placeholder="{{ __('Road-1, Block-A, Bazar Area...') }}"
                                  class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 text-slate-800 focus:bg-white focus:border-cyan-500 transition"></textarea>
                    </div>

                    <div class="pt-1">
                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox"
                                   x-model="modal.form.is_active"
                                   class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-500 cursor-pointer">
                            <span class="text-slate-700 text-[11px] font-medium">{{ __('Zone is active and selectable for customers') }}</span>
                        </label>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                    <button type="button"
                            @click="modal.open = false"
                            :disabled="modal.loading"
                            class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition shadow-2xs cursor-pointer">
                        {{ __('Cancel') }}
                    </button>

                    <button type="submit"
                            :disabled="modal.loading || !modal.form.name"
                            class="bg-cyan-600 hover:bg-cyan-700 disabled:opacity-50 text-white font-medium text-xs px-4 py-1.5 rounded-lg transition flex items-center gap-1.5 shadow-xs cursor-pointer">
                        <i class="fas" :class="modal.loading ? 'fa-spinner fa-spin' : (modal.isEdit ? 'fa-check' : 'fa-plus')"></i>
                        <span x-text="modal.loading ? i18n.saving : (modal.isEdit ? i18n.updateZone : i18n.saveZone)"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 2: View Details (Houses Secondary Info: Territory, City/Upazila, Phone, Role, MRR, Sub-Areas) -->
    <div x-show="detailsModal.open"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;">

        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden"
             @click.away="detailsModal.open = false"
             x-transition:enter="transition ease-out duration-200 transform"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150 transform"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">

            <!-- Soft Natural Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0 shadow-2xs">
                        <i class="fas fa-map-location-dot"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800" x-text="detailsModal.zone?.name || i18n.zoneDetails"></h3>
                        <p class="text-[10.5px] text-slate-500 font-normal font-mono" x-text="(i18n.code + ': ') + (detailsModal.zone?.code || '--')"></p>
                    </div>
                </div>
                <button type="button"
                        @click="detailsModal.open = false"
                        class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <!-- Details Body Grid -->
            <div class="p-4 space-y-3.5 text-xs max-h-[75vh] overflow-y-auto">
                <div class="grid grid-cols-3 gap-2">
                    <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80 text-center">
                        <span class="text-[9px] font-medium uppercase text-slate-500 block">{{ __('Total Subscribers') }}</span>
                        <span class="text-xs font-bold text-slate-900 font-mono" x-text="detailsModal.zone?.subscribers_count ?? 0"></span>
                    </div>
                    <div class="p-2 bg-emerald-50/50 rounded-lg border border-emerald-100 text-center">
                        <span class="text-[9px] font-medium uppercase text-emerald-700 block">{{ __('Active Subscribers') }}</span>
                        <span class="text-xs font-bold text-emerald-700 font-mono" x-text="detailsModal.zone?.active_subscribers ?? 0"></span>
                    </div>
                    <div class="p-2 bg-cyan-50/50 rounded-lg border border-cyan-100 text-center">
                        <span class="text-[9px] font-medium uppercase text-cyan-700 block">{{ __('Monthly Revenue') }}</span>
                        <span class="text-xs font-bold text-cyan-800 font-mono" x-text="detailsModal.zone?.mrr_formatted || '--'"></span>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2.5">
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80 space-y-0.5">
                        <span class="text-[9.5px] font-semibold text-slate-400 uppercase tracking-wider block">{{ __('Territory / Partner') }}</span>
                        <span class="text-xs font-medium text-slate-800 block" x-text="detailsModal.zone?.reseller_name || i18n.hqDirect"></span>
                    </div>

                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80 space-y-0.5">
                        <span class="text-[9.5px] font-semibold text-slate-400 uppercase tracking-wider block">{{ __('Status') }}</span>
                        <span class="inline-flex items-center gap-1 text-[11px] font-semibold" :class="detailsModal.zone?.is_active ? 'text-emerald-700' : 'text-rose-600'">
                            <i class="fas fa-circle text-[7px]"></i>
                            <span x-text="detailsModal.zone?.is_active ? i18n.active : i18n.inactive"></span>
                        </span>
                    </div>

                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80 space-y-0.5">
                        <span class="text-[9.5px] font-semibold text-slate-400 uppercase tracking-wider block">{{ __('Assigned Staff') }}</span>
                        <span class="text-xs font-semibold text-slate-800 block" x-text="detailsModal.zone?.staff_name || '--'"></span>
                        <span class="text-[10px] text-cyan-700 block" x-show="detailsModal.zone?.staff_role" x-text="detailsModal.zone?.staff_role"></span>
                    </div>

                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80 space-y-0.5">
                        <span class="text-[9.5px] font-semibold text-slate-400 uppercase tracking-wider block">{{ __('Contact Phone') }}</span>
                        <span class="text-xs font-mono font-medium text-slate-800 block" x-text="detailsModal.zone?.staff_phone || '--'"></span>
                    </div>

                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80 space-y-0.5 col-span-2">
                        <span class="text-[9.5px] font-semibold text-slate-400 uppercase tracking-wider block">{{ __('City / Upazila') }}</span>
                        <span class="text-xs font-medium text-slate-800 block" x-text="detailsModal.zone?.city_upazila || '--'"></span>
                    </div>

                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80 space-y-1.5 col-span-2">
                        <div class="flex items-center justify-between">
                            <span class="text-[9.5px] font-semibold text-slate-500 uppercase tracking-wider block">
                                {{ __('Sub Areas') }} (<span x-text="detailsModal.zone?.sub_areas?.length ?? 0"></span>)
                            </span>
                            <button type="button"
                                    @click="const z = detailsModal.zone; detailsModal.open = false; openSubAreasModal(z)"
                                    class="text-[10px] text-cyan-700 hover:text-cyan-900 font-semibold inline-flex items-center gap-1 cursor-pointer">
                                <i class="fas fa-layer-group text-[9px]"></i>
                                <span>{{ __('Manage Sub Areas') }}</span>
                            </button>
                        </div>
                        <div class="flex items-center gap-1.5 flex-wrap" x-show="detailsModal.zone?.sub_areas && detailsModal.zone?.sub_areas?.length > 0">
                            <template x-for="(sa, idx) in (detailsModal.zone?.sub_areas ?? [])" :key="idx">
                                <span class="px-2 py-0.5 bg-white border border-slate-200 rounded-md text-[11px] font-medium text-slate-700 shadow-2xs" x-text="sa"></span>
                            </template>
                        </div>
                        <p class="text-xs text-slate-400 italic" x-show="!detailsModal.zone?.sub_areas || detailsModal.zone?.sub_areas?.length === 0">
                            {{ __('No sub-areas defined for this zone.') }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                <button type="button"
                        @click="detailsModal.open = false"
                        class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition shadow-2xs cursor-pointer">
                    {{ __('Close') }}
                </button>

                <button type="button"
                        @click="const item = detailsModal.zone; detailsModal.open = false; openEditModal(item)"
                        class="bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg transition flex items-center gap-1.5 shadow-xs cursor-pointer">
                    <i class="fas fa-pen-to-square text-[11px]"></i>
                    <span>{{ __('Edit Zone') }}</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Modal 3: Manage Sub Areas -->
    <div x-show="subAreasModal.open"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;">

        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden"
             @click.away="if (!subAreasModal.loading) subAreasModal.open = false"
             x-transition:enter="transition ease-out duration-200 transform"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150 transform"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">

            <!-- Soft Natural Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-xs flex-shrink-0 shadow-2xs">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">
                            {{ __('Sub Areas') }} - <span class="text-cyan-700" x-text="subAreasModal.zone?.name"></span>
                        </h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">{{ __('Add, edit, or delete sub-areas within this zone') }}</p>
                    </div>
                </div>
                <button type="button"
                        @click="subAreasModal.open = false"
                        :disabled="subAreasModal.loading"
                        class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <div class="p-4 space-y-4 text-xs">
                <div class="space-y-1">
                    <label class="block text-slate-700 font-semibold text-[11px]">
                        {{ __('Add New Sub-Area') }}
                    </label>
                    <div class="flex items-center gap-2">
                        <input type="text"
                               x-model="subAreasModal.newSubArea"
                               @keydown.enter.prevent="addSubArea()"
                               placeholder="{{ __('Block-A, Bazar Road, Main Crossing...') }}"
                               class="flex-1 bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 text-slate-800 focus:bg-white focus:border-cyan-500 transition">
                        <button type="button"
                                @click="addSubArea()"
                                class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs px-3.5 py-1.5 rounded-lg shadow-xs transition flex items-center gap-1 cursor-pointer">
                            <i class="fas fa-plus text-[10px]"></i>
                            <span>{{ __('Add') }}</span>
                        </button>
                    </div>
                </div>

                <div class="space-y-2">
                    <div class="flex items-center justify-between text-[11px] text-slate-600 font-semibold">
                        <span>{{ __('Current Sub-Areas') }} (<span x-text="subAreasModal.items.length"></span>)</span>
                        <span class="text-slate-400 font-normal text-[10px]">{{ __('Click edit to rename or trash to remove') }}</span>
                    </div>

                    <div class="max-h-60 overflow-y-auto space-y-1.5 pr-1 divide-y divide-slate-100 border border-slate-200 rounded-lg p-2 bg-slate-50/50">
                        <template x-for="(item, idx) in subAreasModal.items" :key="idx">
                            <div class="pt-1.5 first:pt-0 flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2 flex-1 min-w-0" x-show="subAreasModal.editingIndex !== idx">
                                    <span class="w-5 h-5 rounded-full bg-slate-200/80 text-slate-600 flex items-center justify-center text-[10px] font-mono flex-shrink-0" x-text="idx + 1"></span>
                                    <span class="text-xs font-medium text-slate-800 truncate" x-text="item"></span>
                                </div>

                                <div class="flex items-center gap-1.5 flex-1 min-w-0" x-show="subAreasModal.editingIndex === idx">
                                    <input type="text"
                                           x-model="subAreasModal.editingValue"
                                           @keydown.enter.prevent="saveEditSubArea(idx)"
                                           class="w-full bg-white border border-cyan-400 rounded-md text-xs px-2 py-1 text-slate-800 focus:outline-hidden">
                                    <button type="button"
                                            @click="saveEditSubArea(idx)"
                                            class="w-6 h-6 rounded bg-emerald-600 text-white flex items-center justify-center text-[10px] hover:bg-emerald-700 cursor-pointer">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button type="button"
                                            @click="subAreasModal.editingIndex = null"
                                            class="w-6 h-6 rounded bg-slate-200 text-slate-600 flex items-center justify-center text-[10px] hover:bg-slate-300 cursor-pointer">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>

                                <div class="flex items-center gap-1 flex-shrink-0" x-show="subAreasModal.editingIndex !== idx">
                                    <button type="button"
                                            @click="startEditSubArea(idx, item)"
                                            class="w-6 h-6 rounded hover:bg-slate-200 text-slate-500 hover:text-cyan-700 transition flex items-center justify-center text-[10px] cursor-pointer">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                    <button type="button"
                                            @click="deleteSubArea(idx)"
                                            class="w-6 h-6 rounded hover:bg-rose-100 text-slate-400 hover:text-rose-600 transition flex items-center justify-center text-[10px] cursor-pointer">
                                        <i class="fas fa-trash-can"></i>
                                    </button>
                                </div>
                            </div>
                        </template>

                        <div x-show="subAreasModal.items.length === 0" class="py-6 text-center text-slate-400 text-xs italic">
                            <i class="fas fa-map-pin text-xl text-slate-300 mb-1.5 block"></i>
                            {{ __('No sub-areas added yet. Type a name above to add.') }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                <button type="button"
                        @click="subAreasModal.open = false"
                        :disabled="subAreasModal.loading"
                        class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition shadow-2xs cursor-pointer">
                    {{ __('Cancel') }}
                </button>

                <button type="button"
                        @click="saveSubAreasToServer()"
                        :disabled="subAreasModal.loading"
                        class="bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                    <i class="fas" :class="subAreasModal.loading ? 'fa-spinner fa-spin' : 'fa-save'"></i>
                    <span x-text="subAreasModal.loading ? i18n.saving : i18n.saveSubAreas"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Modal 4: Import Excel / CSV -->
    <div x-show="importModal.open"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;">

        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-xl overflow-hidden"
             @click.away="if (!importModal.loading) importModal.open = false"
             x-transition:enter="transition ease-out duration-200 transform"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150 transform"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">

            <!-- Soft Natural Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-xs flex-shrink-0 shadow-2xs">
                        <i class="fas fa-file-excel"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">{{ __('Import Coverage Zones & Areas') }}</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">{{ __('Upload Excel or CSV file to import operational areas & sub-areas') }}</p>
                    </div>
                </div>
                <button type="button"
                        @click="importModal.open = false"
                        :disabled="importModal.loading"
                        class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <form @submit.prevent="submitImport()">
                <div class="p-4 space-y-3.5 text-xs">
                    <div class="flex items-center justify-between p-2.5 bg-cyan-50/60 border border-cyan-100 rounded-lg text-cyan-800">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-info-circle text-cyan-600"></i>
                            <span class="text-[11px] font-medium">{{ __('Need formatting guidelines? Download standard template.') }}</span>
                        </div>
                        <a href="{{ route('tenant.customers.zones.import-template') }}"
                           class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-white border border-cyan-200 hover:bg-cyan-50 text-cyan-700 text-[10.5px] font-semibold transition shadow-2xs">
                            <i class="fas fa-download text-[10px]"></i>
                            <span>{{ __('Template (CSV)') }}</span>
                        </a>
                    </div>

                    <div class="relative border-2 border-dashed rounded-xl p-5 text-center transition cursor-pointer"
                         :class="importModal.isDragging ? 'border-emerald-500 bg-emerald-50/40' : 'border-slate-200 hover:border-emerald-400 bg-slate-50/50 hover:bg-slate-50'"
                         @dragover.prevent="importModal.isDragging = true"
                         @dragleave.prevent="importModal.isDragging = false"
                         @drop.prevent="handleImportDrop($event)">

                        <input type="file"
                               id="zone_import_file"
                               accept=".csv,.xlsx,.xls"
                               class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                               @change="handleImportFileSelect($event)">

                        <template x-if="!importModal.fileName">
                            <div class="flex flex-col items-center justify-center space-y-1.5 pointer-events-none">
                                <div class="w-10 h-10 rounded-full bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-lg shadow-xs">
                                    <i class="fas fa-cloud-arrow-up"></i>
                                </div>
                                <p class="text-xs font-bold text-slate-800">{{ __('Click to browse or drag and drop your file here') }}</p>
                                <p class="text-[10.5px] text-slate-400">{{ __('Supports CSV & UTF-8 encoded spreadsheets up to 10MB') }}</p>
                            </div>
                        </template>

                        <template x-if="importModal.fileName">
                            <div class="flex items-center justify-center gap-3 pointer-events-none">
                                <div class="w-9 h-9 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-base">
                                    <i class="fas fa-file-csv"></i>
                                </div>
                                <div class="text-left">
                                    <p class="text-xs font-bold text-slate-800" x-text="importModal.fileName"></p>
                                    <p class="text-[10.5px] text-emerald-700 font-mono font-semibold" x-text="importModal.fileSize"></p>
                                </div>
                                <button type="button"
                                        @click.stop="clearImportFile()"
                                        class="pointer-events-auto ml-2 w-5 h-5 rounded-full bg-slate-200 hover:bg-rose-100 hover:text-rose-600 text-slate-500 flex items-center justify-center text-[9px] transition">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </template>
                    </div>

                    <div class="grid grid-cols-1 {{ !$isResellerUser ? 'sm:grid-cols-2' : '' }} gap-3 pt-1">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">{{ __('Duplicate Handling') }}</label>
                            <select x-model="importModal.duplicateAction" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                                <option value="skip">{{ __('Skip Duplicates') }}</option>
                                <option value="update">{{ __('Update Existing by Name/Code') }}</option>
                            </select>
                        </div>

                        @if(!$isResellerUser)
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">{{ __('Default Partner Scope') }}</label>
                            <select x-model="importModal.defaultResellerId" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                                <option value="isp">{{ __('ISP Headquarters (HQ Direct)') }}</option>
                                @foreach($allResellers as $r)
                                    <option value="{{ $r->id }}">{{ $r->name }} ({{ $r->code ?: 'RES-' . $r->id }})</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                    </div>
                </div>

                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button"
                            @click="importModal.open = false"
                            :disabled="importModal.loading"
                            class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition shadow-2xs cursor-pointer">
                        {{ __('Cancel') }}
                    </button>

                    <button type="submit"
                            :disabled="importModal.loading || !importModal.file"
                            class="bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-medium text-xs px-4 py-1.5 rounded-lg transition flex items-center gap-1.5 shadow-xs cursor-pointer">
                        <i class="fas" :class="importModal.loading ? 'fa-spinner fa-spin' : 'fa-upload'"></i>
                        <span x-text="importModal.loading ? i18n.importing : i18n.uploadAndImport"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function zoneManager() {
    return {
        activeMenu: null,
        menuPos: { top: 'auto', bottom: 'auto', right: '10px', left: 'auto' },

        staffList: @json($staffListJson ?? []),

        i18n: {
            enableZone: @json(__('Enable Zone')),
            disableZone: @json(__('Disable Zone')),
            createCoverageZone: @json(__('Create Coverage Zone')),
            editCoverageZone: @json(__('Edit Coverage Zone')),
            saveZone: @json(__('Save Zone')),
            updateZone: @json(__('Update Zone')),
            saving: @json(__('Saving...')),
            saveSubAreas: @json(__('Save Sub Areas')),
            importing: @json(__('Importing...')),
            uploadAndImport: @json(__('Upload & Import')),
            zoneDetails: @json(__('Zone Details')),
            code: @json(__('Code')),
            hqDirect: @json(__('HQ Direct')),
            active: @json(__('Active')),
            inactive: @json(__('Inactive')),
            subAreaErr: @json(__('Failed to update sub-areas.')),
            subAreaNetErr: @json(__('Network error while saving sub-areas.')),
            opFailed: @json(__('Operation failed. Please check inputs.')),
            zoneSaveNetErr: @json(__('Network error while saving zone details.')),
            statusFail: @json(__('Status toggle failed.')),
            statusNetErr: @json(__('Network error while updating status.')),
            cannotDeleteTitle: @json(__('Cannot Delete Zone')),
            cannotDeleteHint: @json(__('Please reassign assigned subscribers before deleting this zone.')),
            deleteTitle: @json(__('Delete Coverage Zone?')),
            deleteText: @json(__('This operational area will be permanently removed.')),
            yesDelete: @json(__('Yes, Delete Zone')),
            cancel: @json(__('Cancel')),
            deletedTitle: @json(__('Deleted!')),
            deleteFailed: @json(__('Delete Failed')),
            deleteNetErr: @json(__('Network error while deleting zone.')),
            selectImportFile: @json(__('Please select a CSV or Excel file to import.')),
            importFailed: @json(__('Import failed. Check file format.')),
            importNetErr: @json(__('Network error while importing file.'))
        },

        toast: {
            show: false,
            message: '',
            type: 'success',
            timeout: null
        },

        showToast(message, type = 'success') {
            this.toast.message = message;
            this.toast.type = type;
            this.toast.show = true;
            if (this.toast.timeout) clearTimeout(this.toast.timeout);
            this.toast.timeout = setTimeout(() => {
                this.toast.show = false;
            }, 3500);
        },

        modal: {
            open: false,
            isEdit: false,
            loading: false,
            editId: null,
            form: {
                name: '',
                code: '',
                reseller_id: '',
                staff_id: '',
                city_upazila: '',
                in_charge_name: '',
                in_charge_phone: '',
                description: '',
                is_active: true
            }
        },

        detailsModal: {
            open: false,
            zone: null
        },

        subAreasModal: {
            open: false,
            loading: false,
            zone: null,
            items: [],
            newSubArea: '',
            editingIndex: null,
            editingValue: ''
        },

        importModal: {
            open: false,
            loading: false,
            isDragging: false,
            file: null,
            fileName: '',
            fileSize: '',
            duplicateAction: 'skip',
            defaultResellerId: 'isp'
        },

        get filteredStaffList() {
            if (!this.modal.form.reseller_id) {
                return this.staffList.filter(s => !s.reseller_id);
            }
            return this.staffList.filter(s => s.reseller_id === String(this.modal.form.reseller_id) || !s.reseller_id);
        },

        onResellerScopeChange() {
            if (this.modal.form.staff_id) {
                const isValid = this.filteredStaffList.some(s => String(s.id) === String(this.modal.form.staff_id));
                if (!isValid) {
                    this.modal.form.staff_id = '';
                }
            }
        },

        onStaffSelected() {
            if (!this.modal.form.staff_id) return;
            const staff = this.staffList.find(s => String(s.id) === String(this.modal.form.staff_id));
            if (staff) {
                this.modal.form.in_charge_name = staff.name;
                if (staff.phone) {
                    this.modal.form.in_charge_phone = staff.phone;
                }
            }
        },

        openCreateModal() {
            this.modal.isEdit = false;
            this.modal.editId = null;
            this.modal.form = {
                name: '',
                code: '',
                reseller_id: '',
                staff_id: '',
                city_upazila: '',
                in_charge_name: '',
                in_charge_phone: '',
                description: '',
                is_active: true
            };
            this.modal.open = true;
        },

        openEditModal(item) {
            this.modal.isEdit = true;
            this.modal.editId = item.id;
            this.modal.form = {
                name: item.name || '',
                code: item.code || '',
                reseller_id: item.reseller_id || '',
                staff_id: item.staff_id || '',
                city_upazila: item.city_upazila || '',
                in_charge_name: item.in_charge_name || item.staff_name || '',
                in_charge_phone: item.in_charge_phone || item.staff_phone || '',
                description: item.description || '',
                is_active: item.is_active !== undefined ? item.is_active : true
            };
            this.modal.open = true;
        },

        openDetailsModal(item) {
            this.detailsModal.zone = item;
            this.detailsModal.open = true;
        },

        openSubAreasModal(zone) {
            this.subAreasModal.zone = zone;
            this.subAreasModal.items = Array.isArray(zone.sub_areas) ? [...zone.sub_areas] : [];
            this.subAreasModal.newSubArea = '';
            this.subAreasModal.editingIndex = null;
            this.subAreasModal.editingValue = '';
            this.subAreasModal.open = true;
        },

        addSubArea() {
            const val = this.subAreasModal.newSubArea.trim();
            if (!val) return;
            const splitVals = val.split(/[,;\n]+/).map(s => s.trim()).filter(Boolean);
            splitVals.forEach(s => {
                if (!this.subAreasModal.items.includes(s)) {
                    this.subAreasModal.items.push(s);
                }
            });
            this.subAreasModal.newSubArea = '';
        },

        startEditSubArea(idx, currentVal) {
            this.subAreasModal.editingIndex = idx;
            this.subAreasModal.editingValue = currentVal;
        },

        saveEditSubArea(idx) {
            const val = this.subAreasModal.editingValue.trim();
            if (val) {
                this.subAreasModal.items[idx] = val;
            }
            this.subAreasModal.editingIndex = null;
            this.subAreasModal.editingValue = '';
        },

        deleteSubArea(idx) {
            this.subAreasModal.items.splice(idx, 1);
            if (this.subAreasModal.editingIndex === idx) {
                this.subAreasModal.editingIndex = null;
            }
        },

        async saveSubAreasToServer() {
            if (!this.subAreasModal.zone) return;
            this.subAreasModal.loading = true;
            try {
                const res = await fetch(`{{ url('admin/customers/zones') }}/${this.subAreasModal.zone.id}/sub-areas`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        sub_areas: this.subAreasModal.items
                    })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.showToast(data.message, 'success');
                    this.subAreasModal.open = false;
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    this.showToast(data.message || this.i18n.subAreaErr, 'error');
                }
            } catch (err) {
                this.showToast(this.i18n.subAreaNetErr, 'error');
            } finally {
                this.subAreasModal.loading = false;
            }
        },

        async submitModal() {
            this.modal.loading = true;
            const url = this.modal.isEdit
                ? `{{ url('admin/customers/zones') }}/${this.modal.editId}`
                : `{{ route('tenant.customers.zones.store') }}`;
            const method = this.modal.isEdit ? 'PUT' : 'POST';

            try {
                const res = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.modal.form)
                });

                const data = await res.json();
                if (res.ok && data.success) {
                    this.showToast(data.message, 'success');
                    this.modal.open = false;
                    setTimeout(() => window.location.reload(), 700);
                } else {
                    this.showToast(data.message || this.i18n.opFailed, 'error');
                }
            } catch (err) {
                this.showToast(this.i18n.zoneSaveNetErr, 'error');
            } finally {
                this.modal.loading = false;
            }
        },

        toggleMenu(item, event) {
            if (this.activeMenu?.id === item.id) {
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

        async toggleStatus(id, name) {
            try {
                const res = await fetch(`{{ url('admin/customers/zones') }}/${id}/toggle-status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.showToast(data.message, 'success');
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    this.showToast(data.message || this.i18n.statusFail, 'error');
                }
            } catch (err) {
                this.showToast(this.i18n.statusNetErr, 'error');
            }
        },

        async confirmDelete(id, name, subCount) {
            if (subCount > 0) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: this.i18n.cannotDeleteTitle,
                        text: `${name} (${subCount}) — ${this.i18n.cannotDeleteHint}`,
                        confirmButtonColor: '#0891b2'
                    });
                } else {
                    this.showToast(`${name} (${subCount}) — ${this.i18n.cannotDeleteHint}`, 'error');
                }
                return;
            }

            let isConfirmed = false;
            if (typeof Swal !== 'undefined') {
                const res = await Swal.fire({
                    title: `${this.i18n.deleteTitle} (${name})`,
                    text: this.i18n.deleteText,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e11d48',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: this.i18n.yesDelete,
                    cancelButtonText: this.i18n.cancel
                });
                isConfirmed = res.isConfirmed;
            } else {
                isConfirmed = confirm(`${this.i18n.deleteTitle} (${name})`);
            }

            if (!isConfirmed) return;

            try {
                const res = await fetch(`{{ url('admin/customers/zones') }}/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    if (typeof Swal !== 'undefined') {
                        await Swal.fire({
                            icon: 'success',
                            title: this.i18n.deletedTitle,
                            text: data.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                    }
                    window.location.reload();
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: this.i18n.deleteFailed,
                            text: data.message,
                            confirmButtonColor: '#e11d48'
                        });
                    } else {
                        this.showToast(data.message, 'error');
                    }
                }
            } catch (err) {
                this.showToast(this.i18n.deleteNetErr, 'error');
            }
        },

        openImportModal() {
            this.importModal.open = true;
            this.clearImportFile();
        },

        handleImportFileSelect(e) {
            const files = e.target.files;
            if (files && files[0]) {
                this.setImportFile(files[0]);
            }
        },

        handleImportDrop(e) {
            this.importModal.isDragging = false;
            const files = e.dataTransfer.files;
            if (files && files[0]) {
                this.setImportFile(files[0]);
            }
        },

        setImportFile(file) {
            this.importModal.file = file;
            this.importModal.fileName = file.name;
            const sizeKb = (file.size / 1024).toFixed(1);
            this.importModal.fileSize = sizeKb > 1024 ? `${(sizeKb / 1024).toFixed(2)} MB` : `${sizeKb} KB`;
        },

        clearImportFile() {
            this.importModal.file = null;
            this.importModal.fileName = '';
            this.importModal.fileSize = '';
            const input = document.getElementById('zone_import_file');
            if (input) input.value = '';
        },

        async submitImport() {
            if (!this.importModal.file) {
                this.showToast(this.i18n.selectImportFile, 'error');
                return;
            }

            this.importModal.loading = true;
            const formData = new FormData();
            formData.append('file', this.importModal.file);
            formData.append('duplicate_action', this.importModal.duplicateAction);
            formData.append('default_reseller_id', this.importModal.defaultResellerId);

            try {
                const res = await fetch(`{{ route('tenant.customers.zones.import') }}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const data = await res.json();
                if (res.ok && data.success) {
                    this.showToast(data.message, 'success');
                    this.importModal.open = false;
                    setTimeout(() => window.location.reload(), 800);
                } else {
                    this.showToast(data.message || this.i18n.importFailed, 'error');
                }
            } catch (err) {
                this.showToast(this.i18n.importNetErr, 'error');
            } finally {
                this.importModal.loading = false;
            }
        }
    };
}
</script>
@endpush
