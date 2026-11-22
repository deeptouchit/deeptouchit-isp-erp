@extends('reseller.layouts.app')

@section('title', __('Dashboard'))

@section('content')
<div class="space-y-3">
    <div class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-slate-200 bg-white p-3 shadow-xs">
        <h1 class="flex items-center gap-2 text-sm font-bold text-slate-800"><i class="fas fa-chart-pie text-cyan-600" aria-hidden="true"></i>{{ __('Dashboard') }}</h1>
        <div class="flex flex-wrap gap-2">
            <a class="rounded-lg bg-cyan-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-cyan-700" href="{{ route('reseller.customers.index', ['status' => 'due']) }}">{{ __('Due Customers') }}</a>
            <a class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100" href="{{ route('reseller.finance.cash-handover') }}">{{ __('Cash Handover') }}</a>
        </div>
    </div>
    @include('reseller.collector.kpis', ['cards' => [
        ['Assigned Customers', 'customers', 'fa-users', false],
        ['Due Customers', 'due', 'fa-file-invoice-dollar', false],
        ['Online Customers', 'online', 'fa-wifi', false],
        ['Today Collection', 'today', 'fa-coins', true],
        ['Cash to Hand Over', 'cash', 'fa-money-bill-wave', true],
        ['Pending Handovers', 'handovers', 'fa-vault', false],
    ]])
    <section class="space-y-2">
        <h2 class="text-xs font-bold text-slate-800">{{ __('Due Customers') }}</h2>
        @include('reseller.collector.table')
    </section>
</div>
@endsection
