@extends('reseller.layouts.app')

@section('title', 'Installations - ' . ($reseller->name ?? 'Sub-ISP Portal'))

@push('styles')
    {{-- Page Specific Styles --}}
@endpush

@section('content')
<div class="space-y-3" x-data="resellerInstallationsManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">
    
    {{-- 1. TOP HEADER BAR (AGENTS.md Rule 2.A: Icon + Title + Action buttons ONLY) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 bg-white px-4 py-2.5 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <a href="{{ route('reseller.tech.dashboard') }}" class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center text-xs transition shadow-2xs">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div class="w-8 h-8 rounded-lg bg-teal-50 text-teal-600 border border-teal-100 flex items-center justify-center text-sm shadow-2xs flex-shrink-0">
                <i class="fas fa-network-wired"></i>
            </div>
            <h1 class="text-sm font-bold text-slate-800 tracking-tight">{{ __('Reseller Installations Queue') }}</h1>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('reseller.tech.dashboard') }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition inline-flex items-center gap-1.5 shadow-2xs">
                <i class="fas fa-gauge-high text-xs text-slate-500"></i>
                <span>{{ __('Tech Hub') }}</span>
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
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Active Lines') }}</span>
                <span class="text-[13px] font-bold font-mono text-emerald-600 leading-tight block truncate">{{ number_format($stats['active_lines']) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-circle-check"></i>
            </div>
        </div>

        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Total Customers') }}</span>
                <span class="text-[13px] font-bold font-mono text-slate-900 leading-tight block truncate">{{ number_format($stats['total_subscribers']) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-slate-200 bg-slate-50 text-slate-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-users"></i>
            </div>
        </div>

        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('New This Month') }}</span>
                <span class="text-[13px] font-bold font-mono text-purple-700 leading-tight block truncate">{{ number_format($stats['new_this_month']) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-purple-200 bg-purple-50 text-purple-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-user-plus"></i>
            </div>
        </div>

        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Due Lines') }}</span>
                <span class="text-[13px] font-bold font-mono text-rose-600 leading-tight block truncate">{{ number_format($stats['due_lines']) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-rose-200 bg-rose-50 text-rose-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
        </div>

        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Expired Lines') }}</span>
                <span class="text-[13px] font-bold font-mono text-slate-700 leading-tight block truncate">{{ number_format($stats['expired_lines']) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-slate-200 bg-slate-50 text-slate-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-clock"></i>
            </div>
        </div>
    </div>

    {{-- 3. SEARCH & MULTI-FILTER TOOLBAR (AGENTS.md Rule 2.C) --}}
    <form method="GET" action="{{ route('reseller.tech.installations') }}" class="bg-white p-2.5 rounded-xl border border-slate-200 shadow-xs flex flex-wrap items-center justify-between gap-2">
        <div class="flex items-center gap-2 flex-1 min-w-[240px]">
            <div class="relative flex-1">
                <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" name="search" value="{{ $search }}" placeholder="{{ __('Search Username, Phone, Address...') }}" class="w-full pl-8 pr-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-teal-500 shadow-2xs">
            </div>
            <select name="per_page" onchange="this.form.submit()" class="bg-slate-50 border border-slate-200 rounded-lg text-xs px-2 py-1.5 text-slate-700 focus:bg-white focus:border-teal-500">
                <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10</option>
                <option value="20" {{ $perPage == 20 ? 'selected' : '' }}>20</option>
                <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
            </select>
        </div>

        <div class="flex items-center gap-1.5">
            <button type="submit" class="bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-1.5 px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                <i class="fas fa-filter text-[10px]"></i>
                <span>{{ __('Filter') }}</span>
            </button>
            <a href="{{ route('reseller.tech.installations') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-1.5 px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer">
                <i class="fas fa-rotate-left text-[10px]"></i>
                <span>{{ __('Reset') }}</span>
            </a>
        </div>
    </form>

    {{-- 4. MASTER COMPACT TABLE (AGENTS.md Rule 2.D: Max 5-7 Minimal Columns) --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-10 text-center">#</th>
                        <th>{{ __('Username') }}</th>
                        <th>{{ __('Phone Number') }}</th>
                        <th>{{ __('Package') }}</th>
                        <th>{{ __('Zone / Location') }}</th>
                        <th class="text-center">{{ __('Status') }}</th>
                        <th class="w-24 text-center no-sort">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($installations as $index => $customer)
                        <tr>
                            <td class="text-center font-mono text-slate-500">{{ $installations->firstItem() + $index }}</td>
                            <td class="font-semibold text-slate-800">{{ $customer->username ?? $customer->name }}</td>
                            <td class="font-mono text-slate-600">{{ $customer->phone ?? '—' }}</td>
                            <td>{{ $customer->package_display_name }}</td>
                            <td class="text-slate-600 text-xs">{{ $customer->address ?: ($customer->coverageZone?->name ?? '—') }}</td>
                            <td class="text-center">
                                @if($customer->status === 'active')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold font-mono bg-emerald-50 text-emerald-700 border border-emerald-200">{{ __('Active') }}</span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold font-mono bg-teal-50 text-teal-700 border border-teal-200">{{ __('Pending Line') }}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <button type="button" 
                                        @click="openActivateModal({
                                            id: {{ $customer->id }},
                                            username: '{{ addslashes($customer->username ?? $customer->name) }}',
                                            onu_mac: '{{ addslashes($customer->onu_mac_sn ?? '') }}',
                                            fiber_route: '{{ addslashes($customer->fiber_route_info ?? '') }}'
                                        })"
                                        class="px-2 py-1 bg-teal-600 hover:bg-teal-700 text-white rounded text-[11px] font-semibold transition cursor-pointer inline-flex items-center gap-1 shadow-2xs">
                                    <i class="fas fa-play text-[10px]"></i>
                                    <span>{{ __('Setup') }}</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-6 text-slate-400">
                                <i class="fas fa-satellite-dish text-2xl mb-1.5 block text-slate-300"></i>
                                <span>{{ __('No pending reseller installations in queue.') }}</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($installations->hasPages())
            <div class="p-3 border-t border-slate-200 bg-slate-50/50">
                {{ $installations->links() }}
            </div>
        @endif
    </div>

    {{-- 5. SETUP & ACTIVATION SOFT NATURAL MODAL (AGENTS.md Rule 3) --}}
    <div x-show="showActivateModal" 
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
             @click.outside="showActivateModal = false">
            
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-teal-50 text-teal-600 border border-teal-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-network-wired"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800" x-text="'Setup Line: ' + selectedCustomer?.username"></h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">{{ __('Provision Reseller Customer on Field') }}</p>
                    </div>
                </div>
                <button type="button" @click="showActivateModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <form :action="'/reseller/tech/installations/' + selectedCustomer?.id + '/activate'" method="POST" class="p-4 space-y-3">
                @csrf
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                        {{ __('ONU Serial / MAC Address') }} <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="onu_mac_sn" :value="selectedCustomer?.onu_mac" placeholder="e.g. FHTT-9B8A7C" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-teal-500 shadow-2xs font-mono">
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                        {{ __('Fiber Route & Splitter Box Info') }}
                    </label>
                    <input type="text" name="fiber_route_info" :value="selectedCustomer?.fiber_route" placeholder="e.g. Sub-ISP TJ 02, Port 4" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-teal-500 shadow-2xs">
                </div>

                <div class="pt-3 border-t border-slate-200 flex items-center justify-end gap-2 -mx-4 -mb-4 p-3 bg-slate-50/80">
                    <button type="button" @click="showActivateModal = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit" class="bg-teal-600 hover:bg-teal-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition cursor-pointer">
                        {{ __('Activate & Provision') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function resellerInstallationsManager() {
    return {
        showActivateModal: false,
        selectedCustomer: null,

        openActivateModal(customer) {
            this.selectedCustomer = customer;
            this.showActivateModal = true;
        }
    };
}
</script>
@endpush
