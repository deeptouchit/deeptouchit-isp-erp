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
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">
        <section class="lg:col-span-2 bg-white rounded-xl border border-slate-200 shadow-xs p-3">
            <div class="flex items-center justify-between border-b border-slate-100 pb-2 mb-3">
                <h2 class="text-xs font-bold text-slate-800">{{ __('Payment Collections & Receipts') }}</h2>
                <span class="text-[10px] text-slate-500">{{ __('Today') }}</span>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                <div class="p-2.5 rounded-lg bg-cyan-50 border border-cyan-100"><span class="text-[10px] text-cyan-700 block">{{ __('Due Customers') }}</span><strong class="text-lg text-cyan-800">{{ number_format($stats['due']) }}</strong></div>
                <div class="p-2.5 rounded-lg bg-emerald-50 border border-emerald-100"><span class="text-[10px] text-emerald-700 block">{{ __('Today Collection') }}</span><strong class="text-lg text-emerald-800">@currency($stats['today'])</strong></div>
                <div class="p-2.5 rounded-lg bg-amber-50 border border-amber-100"><span class="text-[10px] text-amber-700 block">{{ __('Cash to Hand Over') }}</span><strong class="text-lg text-amber-800">@currency($stats['cash'])</strong></div>
                <div class="p-2.5 rounded-lg bg-purple-50 border border-purple-100"><span class="text-[10px] text-purple-700 block">{{ __('Pending Handovers') }}</span><strong class="text-lg text-purple-800">{{ number_format($stats['handovers']) }}</strong></div>
            </div>
        </section>
        <section class="bg-white rounded-xl border border-slate-200 shadow-xs p-3">
            <h2 class="text-xs font-bold text-slate-800 border-b border-slate-100 pb-2 mb-3">{{ __('Actions') }}</h2>
            <div class="grid grid-cols-2 gap-2">
                <a href="{{ route('reseller.customers.index') }}" class="rounded-lg border border-slate-200 bg-slate-50 p-2 text-xs font-semibold text-slate-700 hover:border-cyan-300 hover:text-cyan-700"><i class="fas fa-users text-cyan-600 mr-1"></i>{{ __('Customers') }}</a>
                <a href="{{ route('reseller.collections.index') }}" class="rounded-lg border border-slate-200 bg-slate-50 p-2 text-xs font-semibold text-slate-700 hover:border-cyan-300 hover:text-cyan-700"><i class="fas fa-receipt text-cyan-600 mr-1"></i>{{ __('Payment Receipts') }}</a>
                <a href="{{ route('reseller.finance.cash-handover') }}" class="rounded-lg border border-slate-200 bg-slate-50 p-2 text-xs font-semibold text-slate-700 hover:border-cyan-300 hover:text-cyan-700"><i class="fas fa-vault text-cyan-600 mr-1"></i>{{ __('Cash Handover') }}</a>
                <a href="{{ route('reseller.tickets.index') }}" class="rounded-lg border border-slate-200 bg-slate-50 p-2 text-xs font-semibold text-slate-700 hover:border-cyan-300 hover:text-cyan-700"><i class="fas fa-headset text-cyan-600 mr-1"></i>{{ __('Support') }}</a>
            </div>
        </section>
    </div>
</div>
@endsection
