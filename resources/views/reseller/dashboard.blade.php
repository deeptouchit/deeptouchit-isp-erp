@extends('reseller.layouts.app')

@section('title', 'Dashboard - ' . ($tenant->company_name ?? ($reseller->name ?? 'Reseller Portal')))

@push('styles')
    {{-- Page-specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="dashboardManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">
    
@if($authUser && $authUser->isResellerCollector())
    {{-- ==================== RESELLER COLLECTOR DASHBOARD ==================== --}}
    
    {{-- 1. TOP HEADER BAR (Collector) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 bg-white px-4 py-2.5 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center text-sm shadow-2xs flex-shrink-0">
                <i class="fas fa-hand-holding-dollar"></i>
            </div>
            <h1 class="text-sm font-bold text-slate-800 tracking-tight">{{ __('Bill Collector Dashboard') }}</h1>
        </div>
        <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
            <a href="{{ route('reseller.customers.index', ['status' => 'due']) }}" class="px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-800 rounded-lg text-xs font-semibold border border-amber-200/80 transition inline-flex items-center gap-1.5 shadow-2xs cursor-pointer">
                <i class="fas fa-circle-exclamation text-amber-600 text-xs"></i>
                <span>{{ __('Due Customers') }}</span>
            </a>
            <a href="{{ route('reseller.customers.index') }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition inline-flex items-center gap-1.5 shadow-2xs cursor-pointer">
                <i class="fas fa-users text-cyan-600 text-xs"></i>
                <span>{{ __('All Customers') }}</span>
            </a>
            <a href="{{ route('reseller.collections.index') }}" class="px-3.5 py-1.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-receipt text-xs"></i>
                <span>{{ __('My Collections') }}</span>
            </a>
        </div>
    </div>

    {{-- 2. KPI SUMMARY STRIP (Strictly 6 Cards - AGENTS.md Rule 2.B) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        
        {{-- Card 1: Today's Collection --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __("Today's Collection") }}</span>
                <span class="text-[13px] font-bold font-mono text-emerald-600 leading-tight block truncate">
                    @currency($stats['today_collections'])
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-calendar-day"></i>
            </div>
        </div>

        {{-- Card 2: This Month's Collection --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __("This Month Collections") }}</span>
                <span class="text-[13px] font-bold font-mono text-purple-700 leading-tight block truncate">
                    @currency($stats['this_month_collections'])
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-purple-200 bg-purple-50 text-purple-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-calendar-check"></i>
            </div>
        </div>

        {{-- Card 3: Due Customers Count --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Due Customers') }}</span>
                <span class="text-[13px] font-bold font-mono text-rose-600 leading-tight block truncate">
                    {{ number_format($stats['due_customers']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-rose-200 bg-rose-50 text-rose-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-user-clock"></i>
            </div>
        </div>

        {{-- Card 4: Total Due Amount --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Total Due Amount') }}</span>
                <span class="text-[13px] font-bold font-mono text-amber-700 leading-tight block truncate">
                    @currency($stats['total_due_amount'])
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-amber-200 bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-money-bill-wave"></i>
            </div>
        </div>

        {{-- Card 5: Today's Receipts Count --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __("Today Receipts") }}</span>
                <span class="text-[13px] font-bold font-mono text-cyan-700 leading-tight block truncate">
                    {{ number_format($stats['today_receipts_count']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-cyan-200 bg-cyan-50 text-cyan-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-receipt"></i>
            </div>
        </div>

        {{-- Card 6: Total Customers --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Total Customers') }}</span>
                <span class="text-[13px] font-bold font-mono text-slate-800 leading-tight block truncate">
                    {{ number_format($stats['total_customers']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-slate-200 bg-slate-50 text-slate-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-users"></i>
            </div>
        </div>

    </div>

    {{-- 3. COLLECTOR OVERVIEW MODULES (3 Cards) --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">
        
        <!-- Card 1: কালেকশন ও সারাংশ -->
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs space-y-3 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-chart-pie text-emerald-600 text-xs"></i>
                        <h3 class="text-xs font-bold text-slate-800">{{ __('Collection Summary') }}</h3>
                    </div>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold font-mono bg-emerald-50 text-emerald-700 border border-emerald-200">
                        {{ __('Live') }}
                    </span>
                </div>

                <div class="space-y-2 text-xs mt-3">
                    <div class="p-2.5 rounded-lg bg-emerald-50/60 border border-emerald-100 flex items-center justify-between">
                        <span class="text-slate-600 font-medium">{{ __("Today's Collection") }}</span>
                        <span class="font-bold font-mono text-emerald-800 text-sm">@currency($stats['today_collections'])</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-50">
                        <span class="text-slate-500">{{ __('This Month Collections') }}</span>
                        <span class="font-bold font-mono text-purple-700">@currency($stats['this_month_collections'])</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-50">
                        <span class="text-slate-500">{{ __("Today Receipts") }}</span>
                        <span class="font-bold font-mono text-slate-800">{{ $stats['today_receipts_count'] }}</span>
                    </div>
                    <div class="flex justify-between py-1">
                        <span class="text-slate-500">{{ __('Due Customers') }}</span>
                        <span class="font-bold font-mono text-rose-600">{{ $stats['due_customers'] }}</span>
                    </div>
                </div>
            </div>

            <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[11px]">
                <a href="{{ route('reseller.collections.index') }}" class="text-emerald-700 hover:text-emerald-900 font-semibold inline-flex items-center gap-1 transition">
                    <span>{{ __('My Collections') }}</span>
                    <i class="fas fa-chevron-right text-[9px]"></i>
                </a>
                <span class="text-slate-400 font-mono text-[10px]">{{ __('Receipts & Payments') }}</span>
            </div>
        </div>

        <!-- Card 2: জরুরি বকেয়া গ্রাহক তালিকা -->
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs space-y-3 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-user-clock text-rose-600 text-xs"></i>
                        <h3 class="text-xs font-bold text-slate-800">{{ __('Due Customers') }} ({{ __('Immediate') }})</h3>
                    </div>
                    <a href="{{ route('reseller.customers.index', ['status' => 'due']) }}" class="text-[10px] font-semibold text-cyan-700 hover:underline">
                        {{ __('View All') }}
                    </a>
                </div>

                <div class="space-y-1.5 mt-2.5">
                    @forelse($topDueCustomers as $dueCust)
                        <div class="p-2 rounded-lg bg-slate-50 hover:bg-amber-50/50 border border-slate-200/70 hover:border-amber-200 transition flex items-center justify-between text-xs">
                            <div class="min-w-0 pr-2">
                                <span class="font-bold text-slate-800 block truncate leading-tight">{{ $dueCust->name }}</span>
                                <span class="text-[10px] font-mono text-slate-500 block truncate">{{ $dueCust->mobile ?? $dueCust->username }}</span>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <span class="font-bold font-mono text-rose-600 block leading-tight">@currency($dueCust->due_amount)</span>
                                <a href="{{ route('reseller.customers.index', ['search' => $dueCust->username]) }}" class="text-[9.5px] text-cyan-700 font-semibold hover:underline">
                                    {{ __('Collect') }} <i class="fas fa-arrow-right text-[8px]"></i>
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="py-6 text-center text-slate-400 text-xs">
                            <i class="fas fa-check-circle text-emerald-500 text-base mb-1 block"></i>
                            <span>{{ __('No due customers found') }}</span>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[11px]">
                <a href="{{ route('reseller.customers.index', ['status' => 'due']) }}" class="text-rose-700 hover:text-rose-900 font-semibold inline-flex items-center gap-1 transition">
                    <span>{{ __('All Due Customers') }}</span>
                    <i class="fas fa-chevron-right text-[9px]"></i>
                </a>
                <span class="text-slate-400 font-mono text-[10px]">{{ __('Total Due') }}: @currency($stats['total_due_amount'])</span>
            </div>
        </div>

        <!-- Card 3: সাম্প্রতিক আদায় ও মানি রিসিট -->
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs space-y-3 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-receipt text-cyan-600 text-xs"></i>
                        <h3 class="text-xs font-bold text-slate-800">{{ __('Recent Collections') }}</h3>
                    </div>
                    <span class="text-[10px] font-mono text-slate-400">{{ __('Latest') }}</span>
                </div>

                <div class="space-y-1.5 mt-2.5">
                    @forelse($recentPayments as $payment)
                        <div class="p-2 rounded-lg bg-slate-50 hover:bg-cyan-50/50 border border-slate-200/70 hover:border-cyan-200 transition flex items-center justify-between text-xs">
                            <div class="min-w-0 pr-2">
                                <span class="font-bold text-slate-800 block truncate leading-tight">{{ $payment->customer->name ?? 'Customer #' . $payment->customer_id }}</span>
                                <span class="text-[10px] font-mono text-slate-500 block truncate">{{ $payment->created_at?->format('d M, h:i A') }}</span>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <span class="font-bold font-mono text-emerald-700 block leading-tight">@currency($payment->amount)</span>
                                <a href="{{ route('reseller.collections.receipt', $payment->id) }}" target="_blank" class="text-[9.5px] text-purple-700 font-semibold hover:underline">
                                    {{ __('Receipt') }} <i class="fas fa-print text-[8px]"></i>
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="py-6 text-center text-slate-400 text-xs">
                            <i class="fas fa-receipt text-slate-300 text-base mb-1 block"></i>
                            <span>{{ __('No collections yet') }}</span>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[11px]">
                <a href="{{ route('reseller.collections.index') }}" class="text-cyan-700 hover:text-cyan-900 font-semibold inline-flex items-center gap-1 transition">
                    <span>{{ __('View All Receipts') }}</span>
                    <i class="fas fa-chevron-right text-[9px]"></i>
                </a>
                <span class="text-slate-400 text-[10px] font-mono">{{ __('Daily Records') }}</span>
            </div>
        </div>

    </div>

@elseif($authUser && $authUser->isResellerTech())
    {{-- ==================== RESELLER TECHNICIAN DASHBOARD ==================== --}}
    
    {{-- 1. TOP HEADER BAR (Technician) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 bg-white px-4 py-2.5 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-teal-50 text-teal-600 border border-teal-100 flex items-center justify-center text-sm shadow-2xs flex-shrink-0">
                <i class="fas fa-screwdriver-wrench"></i>
            </div>
            <h1 class="text-sm font-bold text-slate-800 tracking-tight">{{ __('Technician Dashboard') }}</h1>
        </div>
        <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
            <a href="{{ route('reseller.tickets.index') }}" class="px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-800 rounded-lg text-xs font-semibold border border-amber-200/80 transition inline-flex items-center gap-1.5 shadow-2xs cursor-pointer">
                <i class="fas fa-headset text-amber-600 text-xs"></i>
                <span>{{ __('Support & Tickets') }}</span>
            </a>
            <a href="{{ route('reseller.customers.index', ['status' => 'active']) }}" class="px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 rounded-lg text-xs font-semibold border border-emerald-200/80 transition inline-flex items-center gap-1.5 shadow-2xs cursor-pointer">
                <i class="fas fa-circle-check text-emerald-600 text-xs"></i>
                <span>{{ __('Active Subscribers') }}</span>
            </a>
            <a href="{{ route('reseller.customers.index', ['status' => 'disconnected']) }}" class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-800 rounded-lg text-xs font-semibold border border-rose-200/80 transition inline-flex items-center gap-1.5 shadow-2xs cursor-pointer">
                <i class="fas fa-circle-xmark text-rose-600 text-xs"></i>
                <span>{{ __('Offline Subscribers') }}</span>
            </a>
            <a href="{{ route('reseller.bandwidth.index') }}" class="px-3.5 py-1.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-chart-line text-xs"></i>
                <span>{{ __('Bandwidth Usage') }}</span>
            </a>
        </div>
    </div>

    {{-- 2. KPI SUMMARY STRIP (Strictly 6 Cards - AGENTS.md Rule 2.B) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        
        {{-- Card 1: Open Tickets / Complaints --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Support Tickets') }}</span>
                <span class="text-[13px] font-bold font-mono text-amber-600 leading-tight block truncate">
                    {{ number_format($stats['open_tickets_count']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-amber-200 bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-headset"></i>
            </div>
        </div>

        {{-- Card 2: Online Subscribers --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Online Subscribers') }}</span>
                <span class="text-[13px] font-bold font-mono text-emerald-600 leading-tight block truncate">
                    {{ number_format($stats['online_sessions']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-circle-check"></i>
            </div>
        </div>

        {{-- Card 3: Offline / Disconnected --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Offline Subscribers') }}</span>
                <span class="text-[13px] font-bold font-mono text-rose-600 leading-tight block truncate">
                    {{ number_format($stats['offline_customers']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-rose-200 bg-rose-50 text-rose-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-circle-xmark"></i>
            </div>
        </div>

        {{-- Card 4: Total Customers --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Total Customers') }}</span>
                <span class="text-[13px] font-bold font-mono text-slate-800 leading-tight block truncate">
                    {{ number_format($stats['total_customers']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-slate-200 bg-slate-50 text-slate-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-users"></i>
            </div>
        </div>

        {{-- Card 5: Bandwidth Capacity --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Bandwidth Capacity') }}</span>
                <span class="text-[13px] font-bold font-mono text-purple-700 leading-tight block truncate">
                    {{ number_format($stats['total_allocated_mbps']) }} Mbps
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-purple-200 bg-purple-50 text-purple-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-gauge-high"></i>
            </div>
        </div>

        {{-- Card 6: Live Bandwidth Usage --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Bandwidth Usage') }}</span>
                <span class="text-[13px] font-bold font-mono text-cyan-800 leading-tight block truncate">
                    {{ number_format($stats['current_usage_mbps'], 1) }} Mbps
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-cyan-200 bg-cyan-50 text-cyan-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-chart-line"></i>
            </div>
        </div>

    </div>

    {{-- 3. TECHNICIAN OVERVIEW MODULES (3 Cards) --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">
        
        <!-- Card 1: সাম্প্রতিক সাপোর্ট ও অভিযোগ টিকিট -->
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs space-y-3 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-headset text-amber-600 text-xs"></i>
                        <h3 class="text-xs font-bold text-slate-800">{{ __('Support & Tickets') }}</h3>
                    </div>
                    <a href="{{ route('reseller.tickets.index') }}" class="text-[10px] font-semibold text-cyan-700 hover:underline">
                        {{ __('View All') }}
                    </a>
                </div>

                <div class="space-y-1.5 mt-2.5">
                    @forelse($recentTickets as $ticket)
                        <div class="p-2 rounded-lg bg-slate-50 hover:bg-amber-50/50 border border-slate-200/70 hover:border-amber-200 transition flex items-center justify-between text-xs">
                            <div class="min-w-0 pr-2">
                                <span class="font-bold text-slate-800 block truncate leading-tight">{{ $ticket->subject }}</span>
                                <span class="text-[10px] font-mono text-slate-500 block truncate">#{{ $ticket->ticket_number }} • {{ $ticket->created_at?->diffForHumans() }}</span>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase {{ $ticket->status === 'open' ? 'bg-amber-100 text-amber-800' : ($ticket->status === 'in_progress' ? 'bg-blue-100 text-blue-800' : 'bg-slate-100 text-slate-700') }}">
                                    {{ $ticket->status }}
                                </span>
                                <a href="{{ route('reseller.tickets.show', $ticket->id) }}" class="text-[9.5px] text-cyan-700 font-semibold hover:underline block mt-0.5">
                                    {{ __('Reply') }} <i class="fas fa-arrow-right text-[8px]"></i>
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="py-6 text-center text-slate-400 text-xs">
                            <i class="fas fa-check-circle text-emerald-500 text-base mb-1 block"></i>
                            <span>{{ __('No pending tickets') }}</span>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[11px]">
                <a href="{{ route('reseller.tickets.index') }}" class="text-amber-700 hover:text-amber-900 font-semibold inline-flex items-center gap-1 transition">
                    <span>{{ __('All Tickets') }}</span>
                    <i class="fas fa-chevron-right text-[9px]"></i>
                </a>
                <span class="text-slate-400 font-mono text-[10px]">{{ __('Active') }}: {{ $stats['open_tickets_count'] }}</span>
            </div>
        </div>

        <!-- Card 2: অফলাইন ও সংযোগ বিচ্ছিন্ন গ্রাহক তালিকা -->
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs space-y-3 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-circle-xmark text-rose-600 text-xs"></i>
                        <h3 class="text-xs font-bold text-slate-800">{{ __('Offline Subscribers') }}</h3>
                    </div>
                    <a href="{{ route('reseller.customers.index', ['status' => 'disconnected']) }}" class="text-[10px] font-semibold text-cyan-700 hover:underline">
                        {{ __('View All') }}
                    </a>
                </div>

                <div class="space-y-1.5 mt-2.5">
                    @forelse($offlineCustomerList as $offCust)
                        <div class="p-2 rounded-lg bg-slate-50 hover:bg-rose-50/50 border border-slate-200/70 hover:border-rose-200 transition flex items-center justify-between text-xs">
                            <div class="min-w-0 pr-2">
                                <span class="font-bold text-slate-800 block truncate leading-tight">{{ $offCust->name }}</span>
                                <span class="text-[10px] font-mono text-slate-500 block truncate">{{ $offCust->username }} • {{ $offCust->phone ?? $offCust->mobile ?? 'N/A' }}</span>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <span class="px-1.5 py-0.5 rounded text-[9px] font-semibold font-mono bg-rose-50 text-rose-700 border border-rose-200">
                                    {{ strtoupper($offCust->online_status ?? 'OFFLINE') }}
                                </span>
                                <a href="{{ route('reseller.customers.show', $offCust->id) }}" class="text-[9.5px] text-cyan-700 font-semibold hover:underline block mt-0.5">
                                    {{ __('Details') }} <i class="fas fa-arrow-right text-[8px]"></i>
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="py-6 text-center text-slate-400 text-xs">
                            <i class="fas fa-circle-check text-emerald-500 text-base mb-1 block"></i>
                            <span>{{ __('All subscribers are online') }}</span>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[11px]">
                <a href="{{ route('reseller.customers.index', ['status' => 'disconnected']) }}" class="text-rose-700 hover:text-rose-900 font-semibold inline-flex items-center gap-1 transition">
                    <span>{{ __('All Offline Lines') }}</span>
                    <i class="fas fa-chevron-right text-[9px]"></i>
                </a>
                <span class="text-slate-400 font-mono text-[10px]">{{ __('Offline') }}: {{ $stats['offline_customers'] }}</span>
            </div>
        </div>

        <!-- Card 3: নেটওয়ার্ক ও ব্যান্ডউইথ অবস্থা -->
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs space-y-3 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-chart-line text-cyan-600 text-xs"></i>
                        <h3 class="text-xs font-bold text-slate-800">{{ __('Network & Bandwidth Health') }}</h3>
                    </div>
                    <span class="text-[10px] font-mono text-emerald-600 font-bold bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">{{ __('Live') }}</span>
                </div>

                <div class="space-y-2.5 mt-3 text-xs">
                    <!-- Bandwidth progress bar -->
                    <div>
                        <div class="flex justify-between items-center text-[11px] mb-1">
                            <span class="text-slate-600 font-medium">{{ __('Utilization') }}</span>
                            <span class="font-bold font-mono text-cyan-800">{{ $stats['utilization_percent'] }}%</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                            <div class="bg-gradient-to-r from-cyan-500 to-indigo-600 h-2 rounded-full transition-all duration-500" style="width: {{ min(100, $stats['utilization_percent']) }}%"></div>
                        </div>
                    </div>

                    <div class="p-2 rounded-lg bg-slate-50 border border-slate-100 space-y-1 text-[11px]">
                        <div class="flex justify-between">
                            <span class="text-slate-500">{{ __('Total Capacity') }}:</span>
                            <span class="font-bold font-mono text-slate-800">{{ number_format($stats['total_allocated_mbps']) }} Mbps</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">{{ __('Current Usage') }}:</span>
                            <span class="font-bold font-mono text-cyan-700">{{ number_format($stats['current_usage_mbps'], 1) }} Mbps</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">{{ __('Active Sessions') }}:</span>
                            <span class="font-bold font-mono text-emerald-700">{{ $stats['online_sessions'] }} / {{ $stats['total_customers'] }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[11px]">
                <a href="{{ route('reseller.bandwidth.index') }}" class="text-cyan-700 hover:text-cyan-900 font-semibold inline-flex items-center gap-1 transition">
                    <span>{{ __('Bandwidth Telemetry') }}</span>
                    <i class="fas fa-chevron-right text-[9px]"></i>
                </a>
                <span class="text-slate-400 text-[10px] font-mono">{{ __('MRR & MRTG') }}</span>
            </div>
        </div>

    </div>

@else
    {{-- ==================== RESELLER ADMIN / PARTNER DASHBOARD ==================== --}}
    
    {{-- 1. TOP HEADER BAR (Reseller Admin) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 bg-white px-4 py-2.5 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 border border-purple-100 flex items-center justify-center text-sm shadow-2xs flex-shrink-0">
                <i class="fas fa-handshake-angle"></i>
            </div>
            <h1 class="text-sm font-bold text-slate-800 tracking-tight">{{ __('Reseller Partner Dashboard') }}</h1>
        </div>
        <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
            <a href="{{ route('reseller.customers.bulk-payments') }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition inline-flex items-center gap-1.5 shadow-2xs cursor-pointer">
                <i class="fas fa-layer-group text-purple-600 text-xs"></i>
                <span>{{ __('Bulk Payments') }}</span>
            </a>
            <a href="{{ route('reseller.customers.create') }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition inline-flex items-center gap-1.5 shadow-2xs cursor-pointer">
                <i class="fas fa-user-plus text-cyan-600 text-xs"></i>
                <span>{{ __('Add Customer') }}</span>
            </a>
            <button type="button" @click="openRechargeModal = true" class="px-3.5 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-xs font-semibold shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-wallet text-xs"></i>
                <span>{{ __('Recharge Wallet') }}</span>
            </button>
        </div>
    </div>

    {{-- 2. KPI SUMMARY STRIP (Strictly 6 Cards - AGENTS.md Rule 2.B) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        
        {{-- Card 1: Total Subscribers --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Total Customers') }}</span>
                <span class="text-[13px] font-bold font-mono text-slate-900 leading-tight block truncate">
                    {{ number_format($stats['total_customers']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-purple-200 bg-purple-50 text-purple-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-users"></i>
            </div>
        </div>

        {{-- Card 2: Active Subscribers --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Active Subscribers') }}</span>
                <span class="text-[13px] font-bold font-mono text-emerald-600 leading-tight block truncate">
                    {{ number_format($stats['active_customers']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-circle-check"></i>
            </div>
        </div>

        {{-- Card 3: New Connections (This Month) --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('New Connections') }}</span>
                <span class="text-[13px] font-bold font-mono text-cyan-600 leading-tight block truncate">
                    {{ number_format($stats['new_customers_this_month']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-cyan-200 bg-cyan-50 text-cyan-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-user-plus"></i>
            </div>
        </div>

        {{-- Card 4: Due Subscribers --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Due Customers') }}</span>
                <span class="text-[13px] font-bold font-mono text-rose-600 leading-tight block truncate">
                    {{ number_format($stats['due_customers']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-rose-200 bg-rose-50 text-rose-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-circle-exclamation"></i>
            </div>
        </div>

        {{-- Card 5: Available Balance --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Available Balance') }}</span>
                <span class="text-[13px] font-bold font-mono text-purple-700 leading-tight block truncate">
                    @currency($stats['available_balance'])
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-purple-200 bg-purple-50 text-purple-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-wallet"></i>
            </div>
        </div>

        {{-- Card 6: Monthly Revenue MRR --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Monthly MRR') }}</span>
                <span class="text-[13px] font-bold font-mono text-cyan-800 leading-tight block truncate">
                    @currency($stats['monthly_mrr'])
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-cyan-200 bg-cyan-50 text-cyan-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-chart-line"></i>
            </div>
        </div>

    </div>

    {{-- 3. MODULE OVERVIEW & TELEMETRY GRID --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">
        
        <!-- Module Card 1: Partner Business & Agreement -->
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs space-y-3 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-id-card text-purple-600 text-xs"></i>
                        <h3 class="text-xs font-bold text-slate-800">{{ __('Company Information') }}</h3>
                    </div>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold font-mono bg-purple-50 text-purple-700 border border-purple-200">
                        {{ $stats['partner_code'] }}
                    </span>
                </div>

                <div class="space-y-2 text-xs mt-3">
                    <div class="flex justify-between py-1 border-b border-slate-50">
                        <span class="text-slate-500">{{ __('Company Name') }}</span>
                        <span class="font-semibold text-slate-800 truncate max-w-[170px]">{{ $reseller->name ?? 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-50">
                        <span class="text-slate-500">{{ __('Owner Name') }}</span>
                        <span class="font-semibold text-slate-800">{{ $reseller->contact_person ?? 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-50">
                        <span class="text-slate-500">{{ __('Phone Number') }}</span>
                        <span class="font-mono text-slate-800">{{ $reseller->mobile ?? 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-50">
                        <span class="text-slate-500">{{ __('Commission Rate') }}</span>
                        <span class="font-bold font-mono text-purple-700">{{ $stats['commission_rate'] }}%</span>
                    </div>
                    <div class="flex justify-between py-1">
                        <span class="text-slate-500">{{ __('Billing Cycle') }}</span>
                        <span class="font-semibold capitalize text-slate-700">{{ $stats['billing_type'] }}</span>
                    </div>
                </div>
            </div>

            <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[11px]">
                <a href="{{ route('reseller.profile') }}" class="text-purple-700 hover:text-purple-900 font-semibold inline-flex items-center gap-1 transition">
                    <span>{{ __('Partner Profile') }}</span>
                    <i class="fas fa-chevron-right text-[9px]"></i>
                </a>
                <span class="text-slate-400 font-mono text-[10px]">{{ __('Host') }}: {{ $tenant->company_name ?? 'ISP' }}</span>
            </div>
        </div>

        <!-- Module Card 2: Wallet & Purchasing Power -->
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs space-y-3 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-wallet text-emerald-600 text-xs"></i>
                        <h3 class="text-xs font-bold text-slate-800">{{ __('Wallet & Balance') }}</h3>
                    </div>
                    <a href="{{ route('reseller.recharge.index') }}" class="text-[10px] font-semibold text-purple-600 hover:underline">
                        {{ __('Transaction Ledger') }}
                    </a>
                </div>

                <div class="space-y-2.5 mt-3">
                    <div class="p-3 rounded-lg bg-slate-50 border border-slate-200/80 space-y-1">
                        <span class="text-[10px] text-slate-500 font-medium block uppercase tracking-wider">{{ __('Available Balance') }}</span>
                        <span class="text-xl font-bold font-mono text-purple-900 block leading-tight">
                            @currency($stats['available_balance'])
                        </span>
                        <div class="flex items-center justify-between text-[10.5px] text-slate-500 pt-1 border-t border-slate-200/60 font-mono">
                            <span>{{ __('Wallet') }}: <b>@currency($stats['wallet_balance'])</b></span>
                            <span>{{ __('Credit Limit') }}: <b>@currency($stats['credit_limit'])</b></span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" @click="openRechargeModal = true" class="px-2.5 py-2 rounded-lg bg-purple-600 hover:bg-purple-700 text-white font-semibold text-[11px] transition text-center shadow-2xs cursor-pointer flex items-center justify-center gap-1">
                            <i class="fas fa-plus-circle text-xs"></i>
                            <span>{{ __('Recharge') }}</span>
                        </button>
                        <a href="{{ route('reseller.recharge.index') }}" class="px-2.5 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-[11px] border border-slate-200 transition text-center shadow-2xs flex items-center justify-center gap-1">
                            <i class="fas fa-receipt text-xs text-slate-500"></i>
                            <span>{{ __('History') }}</span>
                        </a>
                    </div>
                </div>
            </div>

            <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                <span class="flex items-center gap-1 text-emerald-600 font-medium">
                    <i class="fas fa-shield-check"></i>
                    <span>{{ __('Instant Automated Provisioning') }}</span>
                </span>
            </div>
        </div>

        <!-- Module Card 3: Operations & Quick Hub -->
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs space-y-3 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-gauge-high text-cyan-600 text-xs"></i>
                        <h3 class="text-xs font-bold text-slate-800">{{ __('Operations Telemetry') }}</h3>
                    </div>
                    <span class="text-[10px] font-mono text-slate-400">{{ __('Live Status') }}</span>
                </div>

                <div class="grid grid-cols-2 gap-2 mt-3">
                    
                    <!-- Collections -->
                    <a href="{{ route('reseller.collections.index') }}" class="p-2.5 rounded-lg bg-slate-50 hover:bg-emerald-50/50 border border-slate-200/80 hover:border-emerald-200 transition group block">
                        <span class="text-[9px] uppercase font-medium text-slate-500 block truncate">{{ __('This Month Collections') }}</span>
                        <span class="text-xs font-bold font-mono text-emerald-700 block mt-0.5">
                            @currency($stats['this_month_collections'])
                        </span>
                    </a>

                    <!-- New Connections -->
                    <a href="{{ route('reseller.customers.create') }}" class="p-2.5 rounded-lg bg-slate-50 hover:bg-cyan-50/50 border border-slate-200/80 hover:border-cyan-200 transition group block">
                        <span class="text-[9px] uppercase font-medium text-slate-500 block truncate">{{ __('New Connections') }}</span>
                        <span class="text-xs font-bold font-mono text-cyan-700 block mt-0.5">
                            {{ $stats['new_customers_this_month'] }} {{ __('New') }}
                        </span>
                    </a>

                    <!-- Staff & Collectors -->
                    <a href="{{ route('reseller.staff.index') }}" class="p-2.5 rounded-lg bg-slate-50 hover:bg-purple-50/50 border border-slate-200/80 hover:border-purple-200 transition group block">
                        <span class="text-[9px] uppercase font-medium text-slate-500 block truncate">{{ __('Staff & Collectors') }}</span>
                        <span class="text-xs font-bold font-mono text-purple-700 block mt-0.5">
                            {{ $stats['staff_count'] }} {{ __('Staff') }}
                        </span>
                    </a>

                    <!-- Support Tickets -->
                    <a href="{{ route('reseller.tickets.index') }}" class="p-2.5 rounded-lg bg-slate-50 hover:bg-amber-50/50 border border-slate-200/80 hover:border-amber-200 transition group block">
                        <span class="text-[9px] uppercase font-medium text-slate-500 block truncate">{{ __('Support Tickets') }}</span>
                        <span class="text-xs font-bold font-mono text-amber-700 block mt-0.5">
                            {{ $stats['open_tickets_count'] }} {{ __('Active') }}
                        </span>
                    </a>

                </div>
            </div>

            <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[11px]">
                <a href="{{ route('reseller.invoices.index') }}" class="text-cyan-700 hover:text-cyan-900 font-semibold inline-flex items-center gap-1 transition">
                    <span>{{ __('Wholesale Invoices') }}</span>
                    <i class="fas fa-chevron-right text-[9px]"></i>
                </a>
                <a href="{{ route('reseller.customers.bulk-payments') }}" class="text-purple-700 hover:text-purple-900 font-semibold inline-flex items-center gap-1 transition">
                    <span>{{ __('Bulk Bill Pay') }}</span>
                    <i class="fas fa-bolt text-[9px]"></i>
                </a>
            </div>
        </div>

    </div>
@endif

    {{-- 4. RECHARGE WALLET MODAL (AGENTS.md Rule 3: Natural Soft Modal) --}}
    <div x-show="openRechargeModal" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50"
         style="display: none;"
         x-cloak>
        
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-md overflow-hidden" 
             @click.outside="openRechargeModal = false">
            
            <!-- Soft Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-purple-50 text-purple-600 border border-purple-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">{{ __('Balance Recharge') }}</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">{{ __('Submit') }} {{ __('Recharge Wallet') }}</p>
                    </div>
                </div>
                <button type="button" @click="openRechargeModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <!-- Body Form -->
            <form action="{{ route('reseller.recharge.store') }}" method="POST" class="p-4 space-y-3">
                @csrf
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                        {{ __('Amount') }} ({{ $currencySymbol ?? '৳' }}) <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" name="amount" step="0.01" min="1" required placeholder="e.g. 5000" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-purple-500 shadow-2xs font-mono">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                        {{ __('Payment Method') }} <span class="text-rose-500">*</span>
                    </label>
                    <select name="payment_method" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-purple-500 shadow-2xs">
                        <option value="bkash">bKash</option>
                        <option value="nagad">Nagad</option>
                        <option value="rocket">Rocket</option>
                        <option value="bank">Bank Transfer</option>
                        <option value="cash">Cash In Hand</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                        {{ __('Transaction ID') }} / Reference
                    </label>
                    <input type="text" name="trx_id" placeholder="e.g. 9J83KA09X" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-purple-500 shadow-2xs font-mono">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                        {{ __('Notes') }}
                    </label>
                    <input type="text" name="notes" placeholder="Optional notes..." class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-purple-500 shadow-2xs">
                </div>

                <!-- Footer Buttons -->
                <div class="pt-3 border-t border-slate-200 flex items-center justify-end gap-2 -mx-4 -mb-4 p-3 bg-slate-50/80">
                    <button type="button" @click="openRechargeModal = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition cursor-pointer">
                        {{ __('Submit') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function dashboardManager() {
    return {
        openRechargeModal: false
    };
}
</script>
@endpush
