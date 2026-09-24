@extends('reseller.layouts.app')

@section('title', 'Field Tech Hub - ' . ($reseller->name ?? 'Sub-ISP Portal'))

@push('styles')
    {{-- Page Specific Styles --}}
@endpush

@section('content')
<div class="space-y-3" x-data="resellerTechManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">
    
    {{-- 1. TOP HEADER BAR (AGENTS.md Rule 2.A: Icon + Title + Action buttons ONLY) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 bg-white px-4 py-2.5 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-teal-50 text-teal-600 border border-teal-100 flex items-center justify-center text-sm shadow-2xs flex-shrink-0">
                <i class="fas fa-wrench"></i>
            </div>
            <h1 class="text-sm font-bold text-slate-800 tracking-tight">{{ __('Reseller Field Tech Hub') }}</h1>
        </div>
        <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
            <a href="{{ route('reseller.tech.installations') }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition inline-flex items-center gap-1.5 shadow-2xs cursor-pointer">
                <i class="fas fa-network-wired text-teal-600 text-xs"></i>
                <span>{{ __('Installations') }}</span>
            </a>
            <a href="{{ route('reseller.tech.tickets') }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition inline-flex items-center gap-1.5 shadow-2xs cursor-pointer">
                <i class="fas fa-headset text-amber-600 text-xs"></i>
                <span>{{ __('Support Tickets') }}</span>
            </a>
        </div>
    </div>

    {{-- 2. KPI SUMMARY STRIP (Strictly 6 Cards - AGENTS.md Rule 2.B) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Pending Install') }}</span>
                <span class="text-[13px] font-bold font-mono text-cyan-600 leading-tight block truncate">{{ number_format($stats['pending_installations']) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-cyan-200 bg-cyan-50 text-cyan-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-satellite-dish"></i>
            </div>
        </div>

        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Assigned Tickets') }}</span>
                <span class="text-[13px] font-bold font-mono text-amber-600 leading-tight block truncate">{{ number_format($stats['assigned_tickets']) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-amber-200 bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-ticket"></i>
            </div>
        </div>

        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Resolved Today') }}</span>
                <span class="text-[13px] font-bold font-mono text-emerald-600 leading-tight block truncate">{{ number_format($stats['resolved_today']) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-circle-check"></i>
            </div>
        </div>

        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Live Active') }}</span>
                <span class="text-[13px] font-bold font-mono text-teal-700 leading-tight block truncate">{{ number_format($stats['active_sessions']) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-teal-200 bg-teal-50 text-teal-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-signal"></i>
            </div>
        </div>

        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Offline Lines') }}</span>
                <span class="text-[13px] font-bold font-mono text-rose-600 leading-tight block truncate">{{ number_format($stats['offline_customers']) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-rose-200 bg-rose-50 text-rose-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
        </div>

        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Total Customers') }}</span>
                <span class="text-[13px] font-bold font-mono text-slate-900 leading-tight block truncate">{{ number_format($stats['total_customers']) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-slate-200 bg-slate-50 text-slate-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-users"></i>
            </div>
        </div>
    </div>

    {{-- 3. MODULE OVERVIEW & QUICK ACTIONS --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
        
        <!-- Quick Installation Queue -->
        <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs space-y-2.5">
            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                <div class="flex items-center gap-2">
                    <i class="fas fa-network-wired text-teal-600 text-xs"></i>
                    <h3 class="text-xs font-bold text-slate-800">{{ __('Reseller Installation Queue') }}</h3>
                </div>
                <a href="{{ route('reseller.tech.installations') }}" class="text-[10px] font-semibold text-teal-600 hover:underline">
                    {{ __('View All') }} &rarr;
                </a>
            </div>

            <div class="space-y-1.5">
                @forelse($recentCustomers as $customer)
                    <div class="p-2 rounded-lg bg-slate-50 border border-slate-200/80 flex items-center justify-between text-xs">
                        <div class="min-w-0 pr-2">
                            <span class="font-bold text-slate-800 block truncate">{{ $customer->username ?? $customer->name }}</span>
                            <span class="text-[10px] text-slate-500 font-mono">{{ $customer->phone ?? 'No Phone' }} • {{ $customer->package_display_name }}</span>
                        </div>
                        <a href="{{ route('reseller.tech.installations') }}" class="px-2 py-1 rounded bg-teal-600 hover:bg-teal-700 text-white font-semibold text-[10.5px] transition shadow-2xs">
                            {{ __('Setup') }}
                        </a>
                    </div>
                @empty
                    <div class="py-4 text-center text-slate-400 text-xs">
                        {{ __('No pending installations for this reseller.') }}
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Assigned Support Tickets -->
        <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs space-y-2.5">
            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                <div class="flex items-center gap-2">
                    <i class="fas fa-ticket text-amber-600 text-xs"></i>
                    <h3 class="text-xs font-bold text-slate-800">{{ __('Open Reseller Tickets') }}</h3>
                </div>
                <a href="{{ route('reseller.tech.tickets') }}" class="text-[10px] font-semibold text-amber-600 hover:underline">
                    {{ __('View All') }} &rarr;
                </a>
            </div>

            <div class="space-y-1.5">
                @forelse($recentTickets as $ticket)
                    <div class="p-2 rounded-lg bg-slate-50 border border-slate-200/80 flex items-center justify-between text-xs">
                        <div class="min-w-0 pr-2">
                            <span class="font-bold text-slate-800 block truncate">#{{ $ticket->ticket_number }} • {{ $ticket->subject }}</span>
                            <span class="text-[10px] text-slate-500 capitalize">{{ $ticket->priority }} • {{ $ticket->status }}</span>
                        </div>
                        <a href="{{ route('reseller.tech.tickets') }}" class="px-2 py-1 rounded bg-amber-600 hover:bg-amber-700 text-white font-semibold text-[10.5px] transition shadow-2xs">
                            {{ __('Handle') }}
                        </a>
                    </div>
                @empty
                    <div class="py-4 text-center text-slate-400 text-xs">
                        {{ __('No open reseller tickets at the moment.') }}
                    </div>
                @endforelse
            </div>
        </div>

    </div>

</div>
@endsection

@push('scripts')
<script>
function resellerTechManager() {
    return {};
}
</script>
@endpush
