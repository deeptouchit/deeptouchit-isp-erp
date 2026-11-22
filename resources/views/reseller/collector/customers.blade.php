@extends('reseller.layouts.app')

@section('title', __($filters['status'] === 'due' ? 'Due Customers' : 'Assigned Customers'))

@section('content')
<div class="space-y-3">
    <div class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-slate-200 bg-white p-3 shadow-xs">
        <h1 class="flex items-center gap-2 text-sm font-bold text-slate-800">
            <i class="fas fa-users text-cyan-600" aria-hidden="true"></i>{{ __($filters['status'] === 'due' ? 'Due Customers' : 'Assigned Customers') }}
        </h1>
        <div class="flex gap-2">
            <a class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold" href="{{ route('reseller.customers.export', $filters) }}">{{ __('Export CSV') }}</a>
            <a class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold" target="_blank" rel="noopener" href="{{ route('reseller.customers.print', $filters) }}">{{ __('Print Report') }}</a>
        </div>
    </div>
    @include('reseller.collector.kpis', ['cards' => [
        ['Assigned Customers', 'customers', 'fa-users', false],
        ['Due Customers', 'due', 'fa-file-invoice-dollar', false],
        ['Active Customers', 'active', 'fa-user-check', false],
        ['Expired Customers', 'expired', 'fa-user-clock', false],
        ['Due Amount', 'outstanding', 'fa-coins', true],
        ['Matching Customers', 'matches', 'fa-filter', false],
    ]])
    <form method="GET" action="{{ route('reseller.customers.index') }}"
          x-data="{ showMoreFilters: @js($filters['minimum_due'] !== null), searchQuery: @js($filters['search']) }"
          class="bg-white p-2.5 rounded-xl border border-slate-200/90 shadow-2xs space-y-2.5">
        <div class="flex flex-col md:flex-row items-stretch md:items-center gap-2">
            <div class="relative flex-1 min-w-[200px]">
                <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-xs pointer-events-none" :class="searchQuery ? 'text-cyan-600' : 'text-slate-400'" aria-hidden="true"></i>
                <input type="search" name="search" value="{{ $filters['search'] }}" x-model="searchQuery"
                       aria-label="{{ __('Search') }}" placeholder="{{ __('Search by name, ID or phone') }}"
                       class="h-[34px] w-full pl-8 pr-7 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500">
                <button type="button" x-show="searchQuery" x-cloak @click="searchQuery = ''; $nextTick(() => $el.closest('form').requestSubmit())"
                        aria-label="{{ __('Clear Search') }}" class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-500"><i class="fas fa-times" aria-hidden="true"></i></button>
            </div>
            <select name="status" aria-label="{{ __('Status') }}" class="h-[34px] w-full md:w-32 px-2 rounded-lg border border-slate-200 bg-slate-50 text-xs">
                @foreach(['all' => 'All Status', 'due' => 'Due', 'active' => 'Active', 'expired' => 'Expired'] as $key => $label)
                    <option value="{{ $key }}" @selected($filters['status'] === $key)>{{ __($label) }}</option>
                @endforeach
            </select>
            <select name="connection_type" aria-label="{{ __('Connection Type') }}" class="h-[34px] w-full md:w-32 px-2 rounded-lg border border-slate-200 bg-slate-50 text-xs">
                <option value="">{{ __('All Service Types') }}</option>
                @foreach(['pppoe' => 'PPPoE', 'static_ip' => 'Static IP', 'hotspot' => 'Hotspot'] as $key => $label)
                    <option value="{{ $key }}" @selected($filters['connection_type'] === $key)>{{ __($label) }}</option>
                @endforeach
            </select>
            <select name="per_page" aria-label="{{ __('Rows per page') }}" onchange="this.form.requestSubmit()" class="h-[34px] w-full md:w-28 px-2 rounded-lg border border-slate-200 bg-slate-50 text-xs">
                @foreach($pageSizes as $size)
                    <option value="{{ $size }}" @selected($size === $perPage)>{{ __(':count / page', ['count' => $size]) }}</option>
                @endforeach
            </select>
            <button type="button" @click="showMoreFilters = !showMoreFilters" :aria-expanded="showMoreFilters"
                    class="h-[34px] py-0 px-2.5 rounded-lg border border-slate-200 bg-slate-50 text-xs font-semibold flex items-center justify-center gap-1.5">
                <i class="fas fa-sliders-h text-[10px]" aria-hidden="true"></i>{{ __('More Filters') }}
                @if($filters['minimum_due'] !== null)<span class="rounded-full bg-cyan-600 px-1.5 text-white">1</span>@endif
                <i class="fas text-[9px]" :class="showMoreFilters ? 'fa-chevron-up text-cyan-600' : 'fa-chevron-down text-slate-400'" aria-hidden="true"></i>
            </button>
            <div class="flex items-center gap-1.5 flex-shrink-0 md:ml-auto">
                <button type="submit" class="h-[34px] py-0 px-3 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white text-xs font-semibold flex items-center gap-1.5">
                    <i class="fas fa-filter text-[10px]" aria-hidden="true"></i>{{ __('Filter') }}
                </button>
                <a href="{{ route('reseller.customers.index') }}" class="h-[34px] py-0 px-2.5 rounded-lg border border-slate-200 bg-slate-100 hover:bg-slate-200 text-xs font-semibold flex items-center gap-1">
                    <i class="fas fa-rotate-left text-[10px]" aria-hidden="true"></i>{{ __('Reset') }}
                </a>
            </div>
        </div>
        <div x-show="showMoreFilters" x-cloak x-transition class="pt-2 border-t border-slate-100 flex flex-wrap items-center gap-2">
            <label for="collector-minimum-due" class="text-xs font-medium">{{ __('Minimum Due Amount') }}</label>
            <input id="collector-minimum-due" name="minimum_due" type="number" min="0" step="0.01" value="{{ $filters['minimum_due'] }}"
                   class="h-[34px] w-36 px-2 rounded-lg border border-slate-200 bg-slate-50 text-xs">
        </div>
    </form>

    @include('reseller.collector.table', ['offset' => ($customers->firstItem() ?? 1) - 1])
    {{ $customers->links() }}
</div>
@endsection
