@extends('reseller.layouts.app')

@section('title', 'Customers - ' . ($reseller->name ?? 'Reseller Portal'))

@push('styles')
    {{-- Page-specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" 
     x-data="customerMasterManager()" 
     @scroll.window="activeMenu = null" 
     @resize.window="activeMenu = null">
    
    <!-- Toast Notification Banner -->
    <div x-show="toast.show" 
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

    <!-- 1. Top Header Bar (AGENTS.md Rule 2.A: Icon + Title + Action Buttons ONLY) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-3">
            <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                <i class="fas fa-users-line"></i>
            </div>
            <h1 class="text-xs sm:text-sm font-bold text-slate-800 tracking-tight">{{ __('Customers') }}</h1>
        </div>

        @if($authUser && $authUser->isResellerAdmin())
        <div class="flex items-center gap-2 w-full sm:w-auto justify-end flex-shrink-0">
            <!-- Bulk Payments Page -->
            <a href="{{ route('reseller.customers.bulk-payments') }}" 
               class="px-3 py-1.5 rounded-lg bg-purple-50 hover:bg-purple-100 text-purple-700 font-semibold text-xs transition inline-flex items-center justify-center gap-1.5 shadow-2xs border border-purple-200 cursor-pointer"
               title="{{ __('Bulk Payments') }}">
                <i class="fas fa-layer-group text-purple-600 text-[11px]"></i>
                <span>{{ __('Bulk Payments') }}</span>
            </a>

            <!-- Export Database Modal -->
            <button type="button" 
                    @click="openExportModal()"
                    class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition inline-flex items-center justify-center gap-1.5 shadow-2xs border border-slate-200/80 cursor-pointer"
                    title="{{ __('Export CSV') }}">
                <i class="fas fa-file-arrow-down text-slate-600 text-[11px]"></i>
                <span>{{ __('Export CSV') }}</span>
            </button>

            <!-- Onboard New Customer -->
            <a href="{{ route('reseller.customers.create') }}" 
               class="px-3.5 py-1.5 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs shadow-xs transition inline-flex items-center justify-center gap-1.5 cursor-pointer">
                <i class="fas fa-user-plus text-[11px]"></i>
                <span>+ {{ __('Add Customer') }}</span>
            </a>
        </div>
        @endif
    </div>

    <!-- 2. KPI Summary Strip (Strictly 6 Cards - AGENTS.md Rule 2.B) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        <!-- Metric 1: Total Subscribers -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Total Customers') }}</span>
                <span class="text-[13px] font-bold font-mono text-slate-900 leading-tight block truncate">{{ number_format($stats['total_subscribers']) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-slate-200 bg-slate-50 text-slate-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-users"></i>
            </div>
        </div>

        <!-- Metric 2: Live Online Sessions -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-emerald-700 block truncate">{{ __('Active') }}</span>
                <span class="text-[13px] font-bold font-mono text-emerald-600 leading-tight block truncate">{{ number_format($stats['online_sessions']) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-signal"></i>
            </div>
        </div>

        <!-- Metric 3: Active Subscribers -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-blue-700 block truncate">{{ __('Active Subscribers') }}</span>
                <span class="text-[13px] font-bold font-mono text-blue-600 leading-tight block truncate">{{ number_format($stats['active_subscribers']) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-blue-200 bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-user-check"></i>
            </div>
        </div>

        <!-- Metric 4: Due / Expired -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-amber-700 block truncate">{{ __('Due / Unpaid') }}</span>
                <span class="text-[13px] font-bold font-mono text-amber-600 leading-tight block truncate">{{ number_format($stats['due_expired_subscribers']) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-amber-200 bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-clock"></i>
            </div>
        </div>

        <!-- Metric 5: Monthly Billing (MRR) -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-purple-700 block truncate">{{ __('Monthly Fee') }}</span>
                <span class="text-[13px] font-bold font-mono text-purple-600 leading-tight block truncate">@currency($stats['monthly_mrr'])</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-purple-200 bg-purple-50 text-purple-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-file-invoice-dollar"></i>
            </div>
        </div>

        <!-- Metric 6: Total Dues -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-rose-700 block truncate">{{ __('Due Amount') }}</span>
                <span class="text-[13px] font-bold font-mono text-rose-600 leading-tight block truncate">@currency($stats['total_due'])</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-rose-200 bg-rose-50 text-rose-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-hand-holding-dollar"></i>
            </div>
        </div>
    </div>

    <!-- 3. Search & Multi-Filter Toolbar (AGENTS.md Rule 2.C) -->
    <div class="bg-white p-2.5 rounded-xl border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('reseller.customers.index') }}" class="flex flex-wrap items-center gap-2">
            
            <!-- Search Box -->
            <div class="relative flex-1 min-w-[160px] sm:max-w-xs">
                <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                <input type="text" 
                       name="search" 
                       value="{{ $search }}" 
                       placeholder="{{ __('Search') }} ({{ __('Username') }}, {{ __('Customer Name') }}, {{ __('Phone Number') }})..." 
                       class="w-full pl-8 pr-3 py-1.5 rounded-lg border border-slate-200 bg-slate-50 text-xs focus:bg-white focus:outline-none focus:border-cyan-500 shadow-2xs">
            </div>

            <!-- Status Filter -->
            <div class="w-28 sm:w-32">
                <select name="status" onchange="this.form.submit()" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 bg-slate-50 text-xs focus:bg-white focus:outline-none focus:border-cyan-500 shadow-2xs">
                    <option value="all" {{ $status === 'all' ? 'selected' : '' }}>{{ __('All Status') }}</option>
                    <option value="active" {{ $status === 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                    <option value="due" {{ $status === 'due' ? 'selected' : '' }}>{{ __('Due / Unpaid') }}</option>
                    <option value="expired" {{ $status === 'expired' ? 'selected' : '' }}>{{ __('Expired') }}</option>
                    <option value="disabled" {{ $status === 'disabled' ? 'selected' : '' }}>{{ __('Disabled') }}</option>
                    <option value="disconnected" {{ $status === 'disconnected' ? 'selected' : '' }}>{{ __('Disconnected') }}</option>
                </select>
            </div>

            <!-- Package Filter -->
            <div class="w-32 sm:w-36">
                <select name="package_id" onchange="this.form.submit()" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 bg-slate-50 text-xs focus:bg-white focus:outline-none focus:border-cyan-500 shadow-2xs">
                    <option value="all" {{ $packageId === 'all' ? 'selected' : '' }}>{{ __('All Packages') }}</option>
                    @foreach($packages as $pkg)
                        <option value="{{ $pkg->id }}" {{ (string)$packageId === (string)$pkg->id ? 'selected' : '' }}>
                            {{ $pkg->name ?: ($pkg->mikrotik_profile ?: ($pkg->package_name ?: 'Package #' . $pkg->id)) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Zone Filter -->
            <div class="w-28 sm:w-32">
                <select name="zone" onchange="this.form.submit()" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 bg-slate-50 text-xs focus:bg-white focus:outline-none focus:border-cyan-500 shadow-2xs">
                    <option value="all" {{ $zone === 'all' ? 'selected' : '' }}>{{ __('All Zones') }}</option>
                    @foreach($zones as $z)
                        <option value="{{ $z }}" {{ $zone === $z ? 'selected' : '' }}>{{ $z }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Live Session Filter -->
            <div class="w-24 sm:w-28">
                <select name="online_status" onchange="this.form.submit()" class="w-full px-2 py-1.5 rounded-lg border border-slate-200 bg-slate-50 text-xs focus:bg-white focus:outline-none focus:border-cyan-500 shadow-2xs">
                    <option value="all" {{ $onlineStatus === 'all' ? 'selected' : '' }}>{{ __('All Live') ?? 'All' }}</option>
                    <option value="online" {{ $onlineStatus === 'online' ? 'selected' : '' }}>🟢 {{ __('Online') }}</option>
                    <option value="offline" {{ $onlineStatus === 'offline' ? 'selected' : '' }}>⚪ {{ __('Offline') }}</option>
                </select>
            </div>

            <!-- Per Page Selector -->
            <div class="w-20 sm:w-24">
                <select name="per_page" onchange="this.form.submit()" class="w-full px-2 py-1.5 rounded-lg border border-slate-200 bg-slate-50 text-xs focus:bg-white focus:outline-none focus:border-cyan-500 shadow-2xs">
                    <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10 / pg</option>
                    <option value="20" {{ $perPage == 20 ? 'selected' : '' }}>20 / pg</option>
                    <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50 / pg</option>
                    <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100 / pg</option>
                </select>
            </div>

            <!-- Strict Filter & Reset Sequence (AGENTS.md Rule 2.C) -->
            <div class="flex items-center gap-1.5 ml-auto flex-shrink-0">
                <button type="submit" class="bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-1.5 px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                    <i class="fas fa-filter text-[10px]"></i>
                    <span>{{ __('Filter') }}</span>
                </button>
                <a href="{{ route('reseller.customers.index') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-1.5 px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer">
                    <i class="fas fa-rotate-left text-[10px]"></i>
                    <span>{{ __('Reset') }}</span>
                </a>
            </div>

        </form>
    </div>

    <!-- 4. Master Table (<table class="saas-table"> Pure CSS System) -->
    <div class="bg-white rounded-md border border-slate-200 overflow-hidden shadow-2xs">
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th class="w-20 text-center">{{ __('Live') }}</th>
                        <th class="w-20 text-center">MikroTik</th>
                        <th>{{ __('Customer Name') }}</th>
                        <th>{{ __('Phone Number') }}</th>
                        <th>{{ __('Username') }}</th>
                        <th>{{ __('Zone') }}</th>
                        <th>{{ __('Package') }}</th>
                        <th>{{ __('Monthly Fee') }}</th>
                        <th class="text-center">{{ __('Status') }}</th>
                        <th>{{ __('Expiry Date') }}</th>
                        <th class="w-14 text-center no-sort">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $index => $customer)
                        @php
                            $badge = $customer->status_badge;
                            $packageName = $customer->package_display_name ?: ($customer->package?->name ?: ($customer->package?->package_name ?: ($customer->package_name ?? 'N/A')));
                            $initialMikrotik = $customer->mikrotik_status ?: 'enabled';
                            $rawCustomerData = [
                                'id' => $customer->id,
                                'customer_id' => $customer->customer_id,
                                'name' => $customer->name,
                                'username' => $customer->username,
                                'phone' => $customer->phone,
                                'zone' => $customer->zone ?: '--',
                                'monthly_bill' => (float) $customer->monthly_bill,
                                'due_amount' => (float) $customer->due_amount,
                                'status' => $customer->effective_status,
                                'is_expired' => $customer->is_expired,
                                'online_status' => $customer->online_status,
                                'expiry_date' => $customer->expiry_date ? \Carbon\Carbon::parse($customer->expiry_date)->format('d M Y') : '--',
                                'package_name' => $packageName,
                            ];
                        @endphp
                        <tr>
                            <!-- 1. Customer ID -->
                            <td class="font-mono text-cyan-800 font-semibold">
                                <a href="{{ route('reseller.customers.show', $customer->id) }}" class="hover:underline hover:text-cyan-600">
                                    {{ $customer->customer_id }}
                                </a>
                            </td>

                            <!-- 2. Live Status (Real-time Online / Offline) -->
                            <td class="text-center">
                                <template x-if="getOnlineStatus('{{ $customer->username }}', '{{ $customer->online_status }}') === 'online'">
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-[10px] font-semibold border bg-emerald-50 text-emerald-700 border-emerald-200 shadow-2xs">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 ring-2 ring-emerald-300 animate-pulse"></span>
                                        <span>{{ __('Online') }}</span>
                                    </span>
                                </template>
                                <template x-if="getOnlineStatus('{{ $customer->username }}', '{{ $customer->online_status }}') !== 'online'">
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-[10px] font-medium border bg-slate-100 text-slate-500 border-slate-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                        <span>{{ __('Offline') }}</span>
                                    </span>
                                </template>
                            </td>

                            <!-- 3. MikroTik (Direct Real-time MikroTik /ppp/secret State) -->
                            <td class="text-center">
                                @if($authUser && $authUser->isResellerAdmin())
                                <button type="button" 
                                        @click="toggleMikrotikState({{ $customer->id }}, '{{ $customer->username }}', '{{ $initialMikrotik }}', '{{ $customer->effective_status }}')" 
                                        :title="getMikrotikState('{{ $customer->username }}', '{{ $initialMikrotik }}') === 'enabled' ? 'MikroTik Secret: ENABLED (Click to Disable)' : 'MikroTik Secret: DISABLED (Click to Enable)'"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-[10px] font-semibold border transition cursor-pointer shadow-2xs"
                                        :class="getMikrotikState('{{ $customer->username }}', '{{ $initialMikrotik }}') === 'enabled'
                                            ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' 
                                            : 'bg-rose-50 text-rose-700 border-rose-200 hover:bg-rose-100'">
                                    <span class="w-1.5 h-1.5 rounded-full"
                                          :class="getMikrotikState('{{ $customer->username }}', '{{ $initialMikrotik }}') === 'enabled'
                                              ? 'bg-emerald-500 ring-2 ring-emerald-300' 
                                              : 'bg-rose-500'"></span>
                                    <span x-text="getMikrotikState('{{ $customer->username }}', '{{ $initialMikrotik }}') === 'enabled' ? '{{ __('Active') }}' : '{{ __('Disabled') }}'">
                                        {{ $initialMikrotik === 'enabled' ? __('Active') : __('Disabled') }}
                                    </span>
                                </button>
                                @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-[10px] font-semibold border shadow-2xs"
                                      :class="getMikrotikState('{{ $customer->username }}', '{{ $initialMikrotik }}') === 'enabled'
                                          ? 'bg-emerald-50 text-emerald-700 border-emerald-200' 
                                          : 'bg-rose-50 text-rose-700 border-rose-200'">
                                    <span class="w-1.5 h-1.5 rounded-full"
                                          :class="getMikrotikState('{{ $customer->username }}', '{{ $initialMikrotik }}') === 'enabled'
                                              ? 'bg-emerald-500 ring-2 ring-emerald-300' 
                                              : 'bg-rose-500'"></span>
                                    <span x-text="getMikrotikState('{{ $customer->username }}', '{{ $initialMikrotik }}') === 'enabled' ? '{{ __('Active') }}' : '{{ __('Disabled') }}'">
                                        {{ $initialMikrotik === 'enabled' ? __('Active') : __('Disabled') }}
                                    </span>
                                </span>
                                @endif
                            </td>

                            <!-- 4. Name -->
                            <td class="font-medium text-slate-900">
                                <a href="{{ route('reseller.customers.show', $customer->id) }}" class="hover:underline hover:text-cyan-600">
                                    {{ $customer->name }}
                                </a>
                            </td>

                            <!-- 5. Mobile -->
                            <td class="font-mono text-slate-700">
                                {{ $customer->phone }}
                            </td>

                            <!-- 6. PPPoE Username -->
                            <td class="font-mono text-cyan-900 font-semibold">
                                {{ $customer->username }}
                            </td>

                            <!-- 7. Zone -->
                            <td class="text-center font-medium text-slate-700">
                                {{ $customer->zone ?: '--' }}
                            </td>

                            <!-- 8. Package Name -->
                            <td class="text-slate-800 text-center">
                                {{ $packageName }}
                            </td>

                            <!-- 9. Monthly Bill -->
                            <td class="text-right font-mono text-slate-800">
                                @currency($customer->monthly_bill)
                            </td>

                            <!-- 10. Status (Active / Expired / Suspended / Due) -->
                            <td class="text-center">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-semibold border {{ $badge['class'] }} shadow-2xs">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $badge['dot'] }}"></span>
                                    <span>{{ $badge['label'] }}</span>
                                </span>
                            </td>

                            <!-- 11. Expiry Date -->
                            <td class="font-mono text-slate-600">
                                {{ $customer->expiry_date ? \Carbon\Carbon::parse($customer->expiry_date)->format('d M Y') : '--' }}
                            </td>

                            <!-- 12. Action (3-Dot Floating Action Menu) -->
                            <td class="text-center">
                                <button type="button" 
                                        @click="toggleMenu({{ $customer->id }}, $event, {{ Js::from($rawCustomerData) }})" 
                                        class="w-6 h-6 rounded-md hover:bg-slate-100 text-slate-400 hover:text-slate-700 transition cursor-pointer inline-flex items-center justify-center">
                                    <i class="fas fa-ellipsis-v text-[10px]"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="px-4 py-8 text-center text-slate-400 text-xs italic bg-slate-50/50">
                                <i class="fas fa-users-slash text-2xl text-slate-300 mb-2 block"></i>
                                {{ __('No customer records found matching your criteria.') ?? 'No records found.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($customers->hasPages())
            <div class="px-4 py-2 border-t border-slate-200/80 bg-slate-50/50 flex items-center justify-between">
                <span class="text-[11px] text-slate-500 font-mono">
                    {{ __('Showing') ?? 'Showing' }} {{ $customers->firstItem() }} - {{ $customers->lastItem() }} / {{ $customers->total() }}
                </span>
                <div>
                    {{ $customers->links() }}
                </div>
            </div>
        @endif
    </div>

    <!-- 5. FLOATING 3-DOT ACTION MENU (AGENTS.md Rule 2.E) -->
    <div x-show="activeMenu !== null" 
         x-cloak 
         @click.away="activeMenu = null"
         :style="menuPos ? `position: fixed; top: ${menuPos.top}; bottom: ${menuPos.bottom}; right: ${menuPos.right}; left: ${menuPos.left};` : ''"
         class="z-50 w-52 bg-white rounded-xl shadow-xl border border-slate-200/90 py-1 text-xs text-slate-700 animate-in fade-in duration-100 divide-y divide-slate-100">
        
        <!-- Group 1: Diagnostics & Customer Operations -->
        <div class="py-1">
            <a :href="'{{ url('reseller/customers') }}/' + (activeMenu ? activeMenu.id : '')" 
               class="w-full text-left px-3 py-1.5 hover:bg-slate-50 flex items-center gap-2 text-slate-700 font-medium transition cursor-pointer">
                <i class="fas fa-arrow-up-right-from-square text-cyan-600 w-4 text-center"></i>
                <span>{{ __('View Details') }}</span>
            </a>
            @if(!$authUser || !$authUser->isResellerTech())
            <button type="button" 
                    @click="openSinglePayModal(activeMenu); activeMenu = null" 
                    class="w-full text-left px-3 py-1.5 hover:bg-emerald-50 text-emerald-700 font-medium flex items-center gap-2 transition cursor-pointer">
                <i class="fas fa-money-bill-wave text-emerald-600 w-4 text-center"></i>
                <span>{{ __('Pay Bill') }}</span>
            </button>
            @endif
            @if($authUser && $authUser->isResellerAdmin())
            <button type="button" 
                    @click="openRenewModal(activeMenu); activeMenu = null" 
                    class="w-full text-left px-3 py-1.5 hover:bg-teal-50 text-teal-700 font-medium flex items-center gap-2 transition cursor-pointer">
                <i class="fas fa-bolt text-teal-600 w-4 text-center"></i>
                <span>{{ __('Renew Subscription') }}</span>
            </button>
            @endif
        </div>

        @if($authUser && ($authUser->isResellerAdmin() || $authUser->isResellerTech()))
        <!-- Group 2: Network & Diagnostics (Admin & Technician) -->
        <div class="py-1">
            <button type="button" 
                    @click="syncSingleMikrotik(activeMenu.id, activeMenu.name); activeMenu = null" 
                    class="w-full text-left px-3 py-1.5 hover:bg-slate-50 flex items-center gap-2 text-slate-700 font-medium transition cursor-pointer">
                <i class="fas fa-arrows-rotate text-blue-600 w-4 text-center"></i>
                <span>{{ __('Sync MikroTik') }}</span>
            </button>
            <button type="button" 
                    @click="disconnectSession(activeMenu.id); activeMenu = null" 
                    class="w-full text-left px-3 py-1.5 hover:bg-rose-50 text-rose-600 font-medium flex items-center gap-2 transition cursor-pointer">
                <i class="fas fa-plug-circle-xmark text-rose-500 w-4 text-center"></i>
                <span>{{ __('Kick Session') }}</span>
            </button>
            <button type="button" 
                    @click="runPingTest(activeMenu.id, activeMenu.name, activeMenu.username); activeMenu = null" 
                    class="w-full text-left px-3 py-1.5 hover:bg-slate-50 text-cyan-700 font-medium flex items-center gap-2 transition cursor-pointer">
                <i class="fas fa-terminal text-cyan-600 w-4 text-center"></i>
                <span>{{ __('Ping') }}</span>
            </button>
        </div>
        @endif

        @if($authUser && $authUser->isResellerAdmin())
        <!-- Group 3: Configuration & Account Lifecycle (Admin Only) -->
        <div class="py-1">
            <a :href="`/reseller/customers/${activeMenu?.id}/edit`" 
               class="w-full text-left px-3 py-1.5 hover:bg-slate-50 flex items-center gap-2 text-slate-700 font-medium transition cursor-pointer">
                <i class="fas fa-pen-to-square text-indigo-600 w-4 text-center"></i>
                <span>{{ __('Edit Customer') }}</span>
            </a>
            <button type="button" 
                    @click="toggleStatus(activeMenu); activeMenu = null" 
                    class="w-full text-left px-3 py-1.5 flex items-center gap-2 font-medium transition cursor-pointer"
                    :class="{
                        'opacity-50 cursor-not-allowed text-slate-400 bg-slate-50/50': (activeMenu?.status === 'expired' || activeMenu?.is_expired),
                        'text-amber-600 hover:bg-amber-50': (activeMenu?.status === 'active' && !activeMenu?.is_expired),
                        'text-emerald-600 hover:bg-emerald-50': activeMenu?.status !== 'active' && !activeMenu?.is_expired && activeMenu?.status !== 'expired'
                    }"
                    :title="(activeMenu?.status === 'expired' || activeMenu?.is_expired) ? 'Expired subscribers cannot be enabled/disabled. Please renew line or collect bill first.' : ''">
                <i class="fas w-4 text-center" :class="{
                    'fa-ban text-slate-400': (activeMenu?.status === 'expired' || activeMenu?.is_expired),
                    'fa-toggle-off text-amber-600': (activeMenu?.status === 'active' && !activeMenu?.is_expired),
                    'fa-toggle-on text-emerald-600': activeMenu?.status !== 'active' && !activeMenu?.is_expired && activeMenu?.status !== 'expired'
                }"></i>
                <span x-text="(activeMenu?.status === 'expired' || activeMenu?.is_expired) ? '{{ __('Expired') }}' : (activeMenu?.status === 'active' ? '{{ __('Disabled') }}' : '{{ __('Active') }}')"></span>
            </button>
            <button type="button" 
                    @click="deleteCustomer(activeMenu.id, activeMenu.name); activeMenu = null" 
                    class="w-full text-left px-3 py-1.5 hover:bg-rose-50 text-rose-600 font-medium flex items-center gap-2 transition cursor-pointer">
                <i class="fas fa-trash-can text-rose-500 w-4 text-center"></i>
                <span>{{ __('Delete') }}</span>
            </button>
        </div>
        @endif

    </div>

    <!-- ========================================================================= -->
    <!-- PRODUCTION-GRADE NATURAL MODALS                                           -->
    <!-- ========================================================================= -->

    <!-- MODAL 1: DIRECT SINGLE BILL PAYMENT MODAL -->
    <div x-show="payModal.open" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;">
        
        <div class="bg-white text-slate-800 rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden"
             @click.away="if (!payModal.loading) payModal.open = false"
             x-transition:enter="transition ease-out duration-200 transform"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150 transform"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">
            
            <!-- Soft Natural Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-teal-50 text-teal-600 border border-teal-100 flex items-center justify-center text-xs flex-shrink-0 shadow-2xs">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div>
                        <h4 class="text-xs font-semibold text-slate-800">Receive Customer Bill Payment</h4>
                        <p class="text-[10.5px] text-slate-500 font-normal">
                            Subscriber: <span class="font-mono font-bold text-slate-700" x-text="payModal.username || 'Client'"></span> &bull; 
                            ID: <span class="font-mono text-slate-600" x-text="payModal.customer_code || ('CUST-' + payModal.customer_id)"></span>
                        </p>
                    </div>
                </div>
                <button type="button" 
                        @click="payModal.open = false" 
                        :disabled="payModal.loading"
                        class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <!-- Form Body -->
            <form @submit.prevent="submitSinglePayment()">
                <div class="p-4 space-y-3.5 text-xs max-h-[75vh] overflow-y-auto">

                    <!-- 1. Customer Financial Snapshot Card -->
                    <div class="p-3 rounded-xl bg-slate-50/90 border border-slate-200/90 flex items-center justify-between gap-3">
                        <div class="space-y-0.5">
                            <span class="text-[10px] uppercase font-semibold text-slate-400 tracking-wider">Current Due Amount</span>
                            <div class="text-base font-bold font-mono flex items-baseline gap-1" :class="Number(payModal.due_amount) > 0 ? 'text-rose-600' : 'text-emerald-600'">
                                <span class="text-xs font-sans opacity-70">{{ $currencySymbol ?? '৳' }}</span>
                                <span x-text="Number(payModal.due_amount || 0).toFixed(2)"></span>
                            </div>
                            <div class="text-[10px] text-slate-500">
                                Monthly Plan: <strong class="text-slate-700 font-mono inline-flex items-baseline gap-0.5"><span class="text-[10px] opacity-70">{{ $currencySymbol ?? '৳' }}</span><span x-text="Number(payModal.monthly_bill || 0).toFixed(2)"></span></strong>
                            </div>
                        </div>

                        <div class="text-right space-y-0.5 font-mono flex-shrink-0">
                            <span class="text-[10px] uppercase font-sans font-semibold text-slate-400 tracking-wider">Expiry Status</span>
                            <div class="text-xs font-bold text-slate-800">
                                <span x-text="payModal.expiry_date || '--'"></span>
                            </div>
                            <div class="text-[10px]">
                                <span class="text-slate-500 font-sans" x-text="payModal.customer_name"></span>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Payment Type / Mode Selector -->
                    <div class="space-y-1.5">
                        <label class="block text-slate-700 font-semibold text-[11px]">
                            Payment Mode (পেমেন্টের ধরন) <span class="text-rose-500">*</span>
                        </label>
                        <div class="grid grid-cols-3 gap-2">
                            <!-- Mode 1: Due / Regular Bill -->
                            <label class="relative flex flex-col p-2 rounded-lg border cursor-pointer transition select-none text-center"
                                   :class="payModal.payment_mode === 'due' ? 'bg-teal-50/70 border-teal-500 ring-1 ring-teal-400 text-teal-900' : 'bg-slate-50 border-slate-200 hover:bg-slate-100/70 text-slate-700'">
                                <input type="radio" 
                                       name="index_pay_mode" 
                                       value="due" 
                                       x-model="payModal.payment_mode" 
                                       @change="onPaymentModeChange()"
                                       class="sr-only">
                                <i class="fas fa-file-invoice text-teal-600 text-xs mb-1"></i>
                                <span class="font-bold text-[11px]">Due / Regular</span>
                                <span class="text-[9.5px] text-slate-500">বকেয়া / মাসিক বিল</span>
                            </label>

                            <!-- Mode 2: Advance Multi-Month Recharge -->
                            <label class="relative flex flex-col p-2 rounded-lg border cursor-pointer transition select-none text-center"
                                   :class="payModal.payment_mode === 'advance' ? 'bg-teal-50/70 border-teal-500 ring-1 ring-teal-400 text-teal-900' : 'bg-slate-50 border-slate-200 hover:bg-slate-100/70 text-slate-700'">
                                <input type="radio" 
                                       name="index_pay_mode" 
                                       value="advance" 
                                       x-model="payModal.payment_mode" 
                                       @change="onPaymentModeChange()"
                                       class="sr-only">
                                <i class="fas fa-calendar-plus text-teal-600 text-xs mb-1"></i>
                                <span class="font-bold text-[11px]">Advance Pay</span>
                                <span class="text-[9.5px] text-slate-500">অগ্রিম রিচার্জ</span>
                            </label>

                            <!-- Mode 3: Custom Amount -->
                            <label class="relative flex flex-col p-2 rounded-lg border cursor-pointer transition select-none text-center"
                                   :class="payModal.payment_mode === 'custom' ? 'bg-teal-50/70 border-teal-500 ring-1 ring-teal-400 text-teal-900' : 'bg-slate-50 border-slate-200 hover:bg-slate-100/70 text-slate-700'">
                                <input type="radio" 
                                       name="index_pay_mode" 
                                       value="custom" 
                                       x-model="payModal.payment_mode" 
                                       @change="onPaymentModeChange()"
                                       class="sr-only">
                                <i class="fas fa-coins text-teal-600 text-xs mb-1"></i>
                                <span class="font-bold text-[11px]">Custom Amount</span>
                                <span class="text-[9.5px] text-slate-500">আংশিক / কাস্টম</span>
                            </label>
                        </div>
                    </div>

                    <!-- 3. Advance Multi-Month Selector (When mode == advance) -->
                    <template x-if="payModal.payment_mode === 'advance'">
                        <div class="space-y-1.5 p-2.5 rounded-lg bg-teal-50/40 border border-teal-100">
                            <label class="block text-teal-900 font-semibold text-[11px]">
                                Select Advance Duration (কত মাসের অগ্রিম বিল?)
                            </label>
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <template x-for="m in [1, 2, 3, 6, 12]" :key="m">
                                    <button type="button" 
                                            @click="setAdvanceMonths(m)"
                                            class="px-2.5 py-1 rounded-md text-xs font-semibold font-mono transition border cursor-pointer"
                                            :class="payModal.months === m ? 'bg-teal-600 text-white border-teal-700 shadow-2xs' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'">
                                        <span x-text="m === 12 ? '1 Year (12m)' : m + ' Month' + (m > 1 ? 's' : '')"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>

                    <!-- 4. Amount & Discount Grid -->
                    <div class="grid grid-cols-2 gap-3">
                        <!-- Received Amount -->
                        <div class="space-y-1.5">
                            <label class="block text-slate-700 font-semibold text-[11px]">
                                Collected Amount (টাকার পরিমাণ) <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative rounded-lg shadow-2xs">
                                <div class="pointer-events-none absolute inset-y-0 left-0 w-8 flex items-center justify-center text-slate-400 font-mono font-bold text-xs select-none">
                                    {{ $currencySymbol ?? '৳' }}
                                </div>
                                <input type="number" 
                                       step="0.01" 
                                       min="0.01" 
                                       required
                                       x-model="payModal.amount" 
                                       placeholder="0.00"
                                       class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs pl-8 pr-3 py-2 text-slate-900 font-mono font-bold focus:bg-white focus:border-teal-500 focus:ring-1 focus:ring-teal-500 transition">
                            </div>
                        </div>

                        <!-- Discount / Waiver -->
                        <div class="space-y-1.5">
                            <label class="block text-slate-700 font-semibold text-[11px]">
                                Special Discount (ছাড় / মওকুফ)
                            </label>
                            <div class="relative rounded-lg shadow-2xs">
                                <div class="pointer-events-none absolute inset-y-0 left-0 w-8 flex items-center justify-center text-slate-400 font-mono font-bold text-xs select-none">
                                    {{ $currencySymbol ?? '৳' }}
                                </div>
                                <input type="number" 
                                       step="0.01" 
                                       min="0" 
                                       x-model="payModal.discount" 
                                       placeholder="0.00"
                                       class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs pl-8 pr-3 py-2 text-slate-900 font-mono focus:bg-white focus:border-teal-500 focus:ring-1 focus:ring-teal-500 transition">
                            </div>
                        </div>
                    </div>

                    <!-- 5. Payment Channel Selection -->
                    <div class="space-y-1.5">
                        <label class="block text-slate-700 font-semibold text-[11px]">
                            Payment Method (পরিশোধের মাধ্যম) <span class="text-rose-500">*</span>
                        </label>
                        <div class="grid grid-cols-3 sm:grid-cols-6 gap-1.5">
                            <!-- Cash -->
                            <button type="button" 
                                    @click="payModal.payment_method = 'cash'"
                                    class="p-2 rounded-lg border text-center transition flex flex-col items-center gap-0.5 cursor-pointer"
                                    :class="payModal.payment_method === 'cash' ? 'bg-teal-50 border-teal-500 ring-1 ring-teal-400 text-teal-800' : 'bg-slate-50 border-slate-200 hover:bg-slate-100 text-slate-700'">
                                <i class="fas fa-money-bill text-emerald-600 text-xs"></i>
                                <span class="text-[10px] font-semibold">Cash</span>
                            </button>

                            <!-- bKash -->
                            <button type="button" 
                                    @click="payModal.payment_method = 'bkash'"
                                    class="p-2 rounded-lg border text-center transition flex flex-col items-center gap-0.5 cursor-pointer"
                                    :class="payModal.payment_method === 'bkash' ? 'bg-pink-50 border-pink-500 ring-1 ring-pink-400 text-pink-800' : 'bg-slate-50 border-slate-200 hover:bg-slate-100 text-slate-700'">
                                <i class="fas fa-mobile-alt text-pink-600 text-xs"></i>
                                <span class="text-[10px] font-semibold">bKash</span>
                            </button>

                            <!-- Nagad -->
                            <button type="button" 
                                    @click="payModal.payment_method = 'nagad'"
                                    class="p-2 rounded-lg border text-center transition flex flex-col items-center gap-0.5 cursor-pointer"
                                    :class="payModal.payment_method === 'nagad' ? 'bg-orange-50 border-orange-500 ring-1 ring-orange-400 text-orange-800' : 'bg-slate-50 border-slate-200 hover:bg-slate-100 text-slate-700'">
                                <i class="fas fa-wallet text-orange-600 text-xs"></i>
                                <span class="text-[10px] font-semibold">Nagad</span>
                            </button>

                            <!-- Rocket -->
                            <button type="button" 
                                    @click="payModal.payment_method = 'rocket'"
                                    class="p-2 rounded-lg border text-center transition flex flex-col items-center gap-0.5 cursor-pointer"
                                    :class="payModal.payment_method === 'rocket' ? 'bg-purple-50 border-purple-500 ring-1 ring-purple-400 text-purple-800' : 'bg-slate-50 border-slate-200 hover:bg-slate-100 text-slate-700'">
                                <i class="fas fa-rocket text-purple-600 text-xs"></i>
                                <span class="text-[10px] font-semibold">Rocket</span>
                            </button>

                            <!-- Bank -->
                            <button type="button" 
                                    @click="payModal.payment_method = 'bank_transfer'"
                                    class="p-2 rounded-lg border text-center transition flex flex-col items-center gap-0.5 cursor-pointer"
                                    :class="payModal.payment_method === 'bank_transfer' ? 'bg-blue-50 border-blue-500 ring-1 ring-blue-400 text-blue-800' : 'bg-slate-50 border-slate-200 hover:bg-slate-100 text-slate-700'">
                                <i class="fas fa-university text-blue-600 text-xs"></i>
                                <span class="text-[10px] font-semibold">Bank</span>
                            </button>

                            <!-- Card / POS -->
                            <button type="button" 
                                    @click="payModal.payment_method = 'pos'"
                                    class="p-2 rounded-lg border text-center transition flex flex-col items-center gap-0.5 cursor-pointer"
                                    :class="payModal.payment_method === 'pos' ? 'bg-indigo-50 border-indigo-500 ring-1 ring-indigo-400 text-indigo-800' : 'bg-slate-50 border-slate-200 hover:bg-slate-100 text-slate-700'">
                                <i class="fas fa-credit-card text-indigo-600 text-xs"></i>
                                <span class="text-[10px] font-semibold">POS</span>
                            </button>
                        </div>
                    </div>

                    <!-- 6. Billing Month & Trx ID Grid -->
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <label class="block text-slate-700 font-semibold text-[11px]">
                                Billing Month (বিলের মাস)
                            </label>
                            <input type="text" 
                                   x-model="payModal.billing_month" 
                                   placeholder="e.g. {{ date('F Y') }}"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 text-slate-800 focus:bg-white focus:border-teal-500 transition">
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-slate-700 font-semibold text-[11px]">
                                Trx ID / Bank Ref <span class="text-slate-400 font-normal">(Optional)</span>
                            </label>
                            <input type="text" 
                                   x-model="payModal.transaction_id" 
                                   placeholder="e.g. 9J28KLM..."
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 text-slate-800 font-mono focus:bg-white focus:border-teal-500 transition">
                        </div>
                    </div>

                    <!-- 7. Remarks / Note -->
                    <div class="space-y-1.5">
                        <label class="block text-slate-700 font-semibold text-[11px]">
                            Payment Note / Remarks <span class="text-slate-400 font-normal">(Optional)</span>
                        </label>
                        <input type="text" 
                               x-model="payModal.notes" 
                               placeholder="e.g. Collected by Area Counter..."
                               class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 text-slate-800 focus:bg-white focus:border-teal-500 transition">
                    </div>

                    <!-- 8. Options & Switches -->
                    <div class="space-y-2 pt-1 border-t border-slate-100">
                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox" 
                                   x-model="payModal.extend_validity" 
                                   class="rounded border-slate-300 text-teal-600 focus:ring-teal-500 cursor-pointer">
                            <span class="text-slate-700 text-[11px] font-medium">
                                Extend package validity / expiry date automatically
                            </span>
                        </label>
                    </div>

                </div>

                <!-- Footer Buttons -->
                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                    <button type="button" 
                            @click="payModal.open = false" 
                            :disabled="payModal.loading"
                            class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition shadow-2xs cursor-pointer">
                        Cancel
                    </button>

                    <button type="submit" 
                            :disabled="payModal.loading || !payModal.amount || Number(payModal.amount) <= 0"
                            class="bg-teal-600 hover:bg-teal-700 disabled:opacity-50 text-white font-medium text-xs px-4 py-1.5 rounded-lg transition flex items-center gap-1.5 shadow-xs cursor-pointer">
                        <i class="fas" :class="payModal.loading ? 'fa-spinner fa-spin' : 'fa-receipt'"></i>
                        <span x-text="payModal.loading ? 'Processing...' : 'Confirm & Collect Payment'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: QUICK RENEW / RECHARGE MODAL -->
    <div x-show="renewModal.open" 
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;">
        
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-md overflow-hidden"
             @click.away="renewModal.open = false">
            
            <!-- Soft Natural Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Instant Subscription Renewal</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">Extend validity &amp; update billing ledger</p>
                    </div>
                </div>
                <button type="button" @click="renewModal.open = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <form @submit.prevent="submitRenewal()">
                <div class="p-4 space-y-3.5 text-xs">
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <div class="flex items-center justify-between">
                            <span class="font-semibold text-slate-800" x-text="renewModal.name"></span>
                            <span class="font-mono font-bold text-emerald-600">{{ $currencySymbol ?? '৳' }}<span x-text="renewModal.monthly_bill"></span>/mo</span>
                        </div>
                        <span class="text-[10.5px] text-slate-500 block mt-0.5">Current Expiry: <span class="font-medium text-slate-700" x-text="renewModal.expiry_date"></span></span>
                    </div>

                    <div class="grid grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-[11px] font-medium text-slate-700 mb-1">Billing Duration</label>
                            <select x-model="renewModal.months" 
                                    @change="renewModal.amount_paid = renewModal.monthly_bill * renewModal.months"
                                    class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-medium focus:bg-white focus:border-emerald-500 focus:outline-hidden transition">
                                <option value="1">1 Month (30 Days)</option>
                                <option value="2">2 Months (60 Days)</option>
                                <option value="3">3 Months (Quarterly)</option>
                                <option value="6">6 Months (Half-Yearly)</option>
                                <option value="12">12 Months (Yearly)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[11px] font-medium text-slate-700 mb-1">Collected Amount ({{ $currencySymbol ?? '৳' }})</label>
                            <input type="number" 
                                   step="0.01" 
                                   x-model="renewModal.amount_paid" 
                                   required
                                   class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono font-semibold focus:bg-white focus:border-emerald-500 focus:outline-hidden transition">
                        </div>
                    </div>
                </div>

                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="renewModal.open = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg cursor-pointer transition">
                        Cancel
                    </button>
                    <button type="submit" 
                            :disabled="renewModal.loading"
                            class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer disabled:opacity-50">
                        <i class="fas fa-spinner fa-spin text-xs" x-show="renewModal.loading" style="display: none;"></i>
                        <span>Confirm &amp; Renew Line</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 3: PRODUCTION-GRADE REAL-TIME ICMP PING DIAGNOSTICS -->
    <div x-show="pingModal.open" 
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
             @click.away="if (!pingModal.loading) pingModal.open = false">
            
            <!-- Soft Natural Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-terminal"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">ICMP Ping Network Diagnostics</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">Real-time latency test to client ONU / Router</p>
                    </div>
                </div>
                <button type="button" @click="pingModal.open = false" :disabled="pingModal.loading" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <div class="p-4 space-y-3 text-xs">
                <!-- Target Details -->
                <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80 flex items-center justify-between">
                    <div>
                        <span class="font-semibold text-slate-800" x-text="pingModal.customer_name"></span>
                        <span class="text-[10.5px] text-slate-500 font-mono block" x-text="'Username: ' + pingModal.username"></span>
                    </div>
                    <div class="text-right">
                        <span class="font-mono font-bold text-cyan-800 text-xs" x-text="pingModal.ip || 'Auto-Detecting IP...'"></span>
                        <span class="text-[10px] text-slate-400 block" x-text="'Gateway: ' + (pingModal.gateway || 'Router')"></span>
                    </div>
                </div>

                <!-- Packet & Latency Stats -->
                <div class="grid grid-cols-4 gap-2 text-center">
                    <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[9.5px] text-slate-500 block uppercase font-medium">Packets</span>
                        <span class="font-mono font-bold text-slate-800" x-text="pingModal.received + '/' + pingModal.sent"></span>
                    </div>
                    <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[9.5px] text-slate-500 block uppercase font-medium">Loss</span>
                        <span class="font-mono font-bold" :class="pingModal.loss_percent > 0 ? 'text-rose-600' : 'text-emerald-600'" x-text="pingModal.loss_percent + '%'"></span>
                    </div>
                    <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[9.5px] text-slate-500 block uppercase font-medium">Avg RTT</span>
                        <span class="font-mono font-bold text-cyan-700" x-text="pingModal.rtt_avg"></span>
                    </div>
                    <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[9.5px] text-slate-500 block uppercase font-medium">Status</span>
                        <span class="font-mono font-bold" :class="pingModal.loss_percent === 0 ? 'text-emerald-600' : (pingModal.loss_percent < 100 ? 'text-amber-600' : 'text-rose-600')" x-text="pingModal.loss_percent === 0 ? 'Online' : (pingModal.loss_percent < 100 ? 'Loss' : 'Offline')"></span>
                    </div>
                </div>

                <!-- Terminal Console -->
                <div class="bg-slate-900 rounded-lg p-3 text-cyan-300 font-mono text-[11px] h-36 overflow-y-auto space-y-1 shadow-inner border border-slate-800">
                    <template x-for="(line, idx) in pingModal.lines" :key="idx">
                        <div x-text="line" class="leading-relaxed"></div>
                    </template>
                    <template x-if="pingModal.loading">
                        <div class="text-amber-400 flex items-center gap-1.5 animate-pulse">
                            <i class="fas fa-spinner fa-spin text-xs"></i>
                            <span>Sending ICMP echo requests...</span>
                        </div>
                    </template>
                </div>
            </div>

            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                <button type="button" @click="pingModal.open = false" :disabled="pingModal.loading" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg cursor-pointer transition">
                    Close
                </button>
                <button type="button" 
                        @click="executePing()" 
                        :disabled="pingModal.loading"
                        class="bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer disabled:opacity-50">
                    <i class="fas fa-play text-xs" :class="{'fa-spinner fa-spin': pingModal.loading}"></i>
                    <span x-text="pingModal.loading ? 'Pinging...' : 'Repeat Ping Test'"></span>
                </button>
            </div>
        </div>
    </div>



    <!-- MODAL 5: PRODUCTION-GRADE SUBSCRIBER EXPORT MODAL -->
    <div x-show="exportModal.open" 
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
             @click.away="exportModal.open = false">
            
            <!-- Soft Natural Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-file-arrow-down"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Export Customer Database</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">Download structured records formatted for Excel &amp; Google Sheets</p>
                    </div>
                </div>
                <button type="button" @click="exportModal.open = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <!-- Export Body -->
            <div class="p-4 space-y-3.5 text-xs text-slate-800">
                
                <!-- Preset Selection -->
                <div class="space-y-2">
                    <label class="block text-[11px] font-semibold text-slate-700 uppercase tracking-wider">Select Export Preset</label>
                    <div class="grid grid-cols-2 gap-2.5">
                        
                        <!-- Preset 1: Full Master Dump -->
                        <div class="p-2.5 rounded-lg border cursor-pointer transition flex items-start gap-2.5"
                             :class="exportModal.preset === 'master' ? 'border-emerald-500 bg-emerald-50/40 ring-1 ring-emerald-400' : 'border-slate-200 hover:border-slate-300 bg-white'"
                             @click="exportModal.preset = 'master'">
                            <i class="fas fa-database text-emerald-600 mt-0.5"></i>
                            <div class="min-w-0">
                                <span class="font-semibold text-slate-900 block">Master Dump</span>
                                <span class="text-[10px] text-slate-500 block leading-tight">All columns (Profiles, Billing, IP, MikroTik &amp; ONU)</span>
                            </div>
                        </div>

                        <!-- Preset 2: Billing & Ledger -->
                        <div class="p-2.5 rounded-lg border cursor-pointer transition flex items-start gap-2.5"
                             :class="exportModal.preset === 'billing' ? 'border-emerald-500 bg-emerald-50/40 ring-1 ring-emerald-400' : 'border-slate-200 hover:border-slate-300 bg-white'"
                             @click="exportModal.preset = 'billing'">
                            <i class="fas fa-file-invoice-dollar text-indigo-600 mt-0.5"></i>
                            <div class="min-w-0">
                                <span class="font-semibold text-slate-900 block">Billing &amp; Ledger</span>
                                <span class="text-[10px] text-slate-500 block leading-tight">Monthly bills, due amounts, wallet balance &amp; expiry</span>
                            </div>
                        </div>

                        <!-- Preset 3: Technical & MikroTik -->
                        <div class="p-2.5 rounded-lg border cursor-pointer transition flex items-start gap-2.5"
                             :class="exportModal.preset === 'technical' ? 'border-emerald-500 bg-emerald-50/40 ring-1 ring-emerald-400' : 'border-slate-200 hover:border-slate-300 bg-white'"
                             @click="exportModal.preset = 'technical'">
                            <i class="fas fa-network-wired text-cyan-600 mt-0.5"></i>
                            <div class="min-w-0">
                                <span class="font-semibold text-slate-900 block">Technical &amp; Router</span>
                                <span class="text-[10px] text-slate-500 block leading-tight">PPPoE passwords, Framed IP, ONU MAC &amp; Router</span>
                            </div>
                        </div>

                        <!-- Preset 4: Contact Directory -->
                        <div class="p-2.5 rounded-lg border cursor-pointer transition flex items-start gap-2.5"
                             :class="exportModal.preset === 'contacts' ? 'border-emerald-500 bg-emerald-50/40 ring-1 ring-emerald-400' : 'border-slate-200 hover:border-slate-300 bg-white'"
                             @click="exportModal.preset = 'contacts'">
                            <i class="fas fa-address-book text-amber-600 mt-0.5"></i>
                            <div class="min-w-0">
                                <span class="font-semibold text-slate-900 block">Contacts Directory</span>
                                <span class="text-[10px] text-slate-500 block leading-tight">Names, mobile phones, email, NID &amp; address</span>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Status Filter -->
                <div class="pt-2 border-t border-slate-100">
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">Account Status Filter</label>
                    <select x-model="exportModal.status" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-medium focus:bg-white focus:border-emerald-500">
                        <option value="all">All Statuses</option>
                        <option value="active">Active Only</option>
                        <option value="due">Due / Unpaid Only</option>
                        <option value="expired">Expired Only</option>
                        <option value="disabled">Disabled / Suspended Only</option>
                    </select>
                </div>

                <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200/80 text-[10.5px] text-slate-600 flex items-center gap-2">
                    <i class="fas fa-circle-info text-cyan-600 text-xs flex-shrink-0"></i>
                    <span>Exported files automatically include UTF-8 BOM encoding for seamless display in Microsoft Excel and Google Sheets without font distortion.</span>
                </div>

            </div>

            <!-- Modal Footer -->
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end gap-2">
                <button type="button" @click="exportModal.open = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg cursor-pointer transition">
                    Cancel
                </button>
                <button type="button" 
                        @click="triggerExport()" 
                        class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                    <i class="fas fa-file-arrow-down text-xs"></i>
                    <span>Download CSV</span>
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function customerMasterManager() {
    return {
        activeMenu: null,
        menuPos: null,
        onlineMap: {},
        mikrotikMap: {},

        // Toast
        toast: {
            show: false,
            message: '',
            type: 'success',
            timeout: null
        },

        showToast(msg, type = 'success') {
            this.toast.message = msg;
            this.toast.type = type;
            this.toast.show = true;
            if (this.toast.timeout) clearTimeout(this.toast.timeout);
            this.toast.timeout = setTimeout(() => {
                this.toast.show = false;
            }, 4000);
        },

        // Direct Pay Modal
        payModal: {
            open: false,
            loading: false,
            customer_id: null,
            customer_code: '',
            customer_name: '',
            username: '',
            monthly_bill: 0,
            due_amount: 0,
            expiry_date: '--',
            payment_mode: 'due',
            amount: 0,
            discount: 0,
            months: 1,
            payment_method: 'cash',
            transaction_id: '',
            billing_month: '{{ date("F Y") }}',
            notes: '',
            extend_validity: true
        },

        // Renew Modal
        renewModal: {
            open: false,
            loading: false,
            customer_id: null,
            name: '',
            monthly_bill: 0,
            expiry_date: '--',
            months: 1,
            amount_paid: 0
        },

        // Ping Modal
        pingModal: {
            open: false,
            loading: false,
            customer_id: null,
            customer_name: '',
            username: '',
            ip: '',
            gateway: '',
            sent: 4,
            received: 0,
            loss_percent: 0,
            rtt_avg: '--',
            lines: []
        },

        // Export Modal
        exportModal: {
            open: false,
            preset: 'master',
            status: 'all'
        },

        init() {
            this.pollLiveSessions(false);
            setInterval(() => {
                this.pollLiveSessions(false);
            }, 15000);
        },

        async pollLiveSessions(showNotification = false) {
            try {
                const res = await fetch(`{{ route('reseller.customers.sync-online') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                if (data.success) {
                    if (Array.isArray(data.online_usernames)) {
                        const newMap = {};
                        data.online_usernames.forEach(u => {
                            newMap[u] = 'online';
                        });
                        this.onlineMap = newMap;
                    }
                    if (data.mikrotik_status_map && typeof data.mikrotik_status_map === 'object') {
                        this.mikrotikMap = Object.assign({}, this.mikrotikMap, data.mikrotik_status_map);
                    }
                    if (showNotification) {
                        this.showToast(`Live status synced: ${data.active_count || 0} active sessions online.`, 'success');
                    }
                }
            } catch (err) {
                // Background poll silent
            }
        },

        getOnlineStatus(username, fallback) {
            if (this.onlineMap[username] !== undefined) {
                return this.onlineMap[username];
            }
            return fallback || 'offline';
        },

        getMikrotikState(username, fallback) {
            if (this.mikrotikMap[username] !== undefined) {
                return this.mikrotikMap[username];
            }
            return fallback || 'enabled';
        },

        async syncSingleMikrotik(id, name) {
            try {
                this.showToast(`Syncing ${name} to MikroTik & FreeRADIUS...`, 'info');
                const res = await fetch(`/reseller/customers/${id}/sync-mikrotik`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                if (data.success) {
                    this.showToast(`Subscriber '${name}' synced to MikroTik successfully!`, 'success');
                } else {
                    this.showToast(data.message || 'Sync failed.', 'error');
                }
            } catch (e) {
                this.showToast('Failed to connect to router.', 'error');
            }
        },

        async toggleMikrotikState(id, username, initialFallback, status) {
            if (status === 'expired') {
                this.showToast('গ্রাহকের মেয়াদ শেষ হয়ে গেছে। দয়া করে আগে বিল পরিশোধ বা লাইন রিনিউ করুন।', 'error');
                return;
            }
            try {
                const res = await fetch(`/reseller/customers/${id}/toggle-mikrotik`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                if (data.success) {
                    this.mikrotikMap[username] = data.mikrotik_status;
                    this.showToast(data.message, 'success');
                } else {
                    this.showToast(data.message || 'গ্রাহকের মেয়াদ শেষ হয়ে গেছে। দয়া করে আগে বিল পরিশোধ করুন।', 'error');
                }
            } catch (err) {
                this.showToast('গ্রাহকের মেয়াদ শেষ হয়ে গেছে। দয়া করে আগে বিল পরিশোধ করুন।', 'error');
            }
        },

        toggleMenu(id, event, data) {
            if (this.activeMenu?.id === id) {
                this.activeMenu = null;
                return;
            }
            this.activeMenu = data;
            const rect = event.currentTarget.getBoundingClientRect();
            const dropdownHeight = 240;
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

        // --- Single Direct Payment Modal Handling ---
        openSinglePayModal(cust) {
            this.activeMenu = null;
            this.payModal.customer_id = cust.id;
            this.payModal.customer_code = cust.customer_id || ('CUST-' + cust.id);
            this.payModal.customer_name = cust.name;
            this.payModal.username = cust.username;
            this.payModal.monthly_bill = parseFloat(cust.monthly_bill || 0);
            this.payModal.due_amount = parseFloat(cust.due_amount || 0);
            this.payModal.expiry_date = cust.expiry_date || '--';
            this.payModal.payment_mode = (this.payModal.due_amount > 0) ? 'due' : 'advance';
            this.payModal.discount = 0;
            this.payModal.months = 1;
            this.payModal.payment_method = 'cash';
            this.payModal.transaction_id = '';
            this.payModal.billing_month = '{{ date("F Y") }}';
            this.payModal.notes = '';
            this.payModal.extend_validity = true;
            this.onPaymentModeChange();
            this.payModal.open = true;
        },

        onPaymentModeChange() {
            if (this.payModal.payment_mode === 'due') {
                this.payModal.amount = (this.payModal.due_amount > 0) ? this.payModal.due_amount : (this.payModal.monthly_bill > 0 ? this.payModal.monthly_bill : 0);
                this.payModal.discount = 0;
                this.payModal.months = 1;
            } else if (this.payModal.payment_mode === 'advance') {
                this.payModal.months = 1;
                this.payModal.amount = this.payModal.monthly_bill > 0 ? this.payModal.monthly_bill : 0;
                this.payModal.discount = 0;
            } else if (this.payModal.payment_mode === 'custom') {
                this.payModal.amount = (this.payModal.due_amount > 0) ? this.payModal.due_amount : (this.payModal.monthly_bill > 0 ? this.payModal.monthly_bill : 0);
            }
        },

        setAdvanceMonths(m) {
            this.payModal.months = m;
            this.payModal.amount = (this.payModal.monthly_bill * parseInt(m || 1)).toFixed(2);
        },

        async submitSinglePayment() {
            this.payModal.loading = true;
            try {
                const res = await fetch(`/reseller/customers/${this.payModal.customer_id}/pay-bill`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        payment_mode: this.payModal.payment_mode,
                        amount: this.payModal.amount,
                        discount: this.payModal.discount,
                        months: this.payModal.months,
                        payment_method: this.payModal.payment_method,
                        transaction_id: this.payModal.transaction_id,
                        billing_month: this.payModal.billing_month,
                        notes: this.payModal.notes,
                        extend_validity: this.payModal.extend_validity
                    })
                });
                const data = await res.json();
                this.payModal.loading = false;
                if (data.success) {
                    this.payModal.open = false;
                    this.showToast(data.message, 'success');
                    setTimeout(() => window.location.reload(), 800);
                } else {
                    this.showToast(data.message || 'Payment recording failed.', 'error');
                }
            } catch (err) {
                this.payModal.loading = false;
                this.showToast('Error connecting to payment gateway.', 'error');
            }
        },

        // --- Quick Renew Modal Handling ---
        openRenewModal(cust) {
            this.activeMenu = null;
            this.renewModal.customer_id = cust.id;
            this.renewModal.name = cust.name;
            this.renewModal.monthly_bill = parseFloat(cust.monthly_bill || 0);
            this.renewModal.expiry_date = cust.expiry_date || '--';
            this.renewModal.months = 1;
            this.renewModal.amount_paid = this.renewModal.monthly_bill;
            this.renewModal.open = true;
        },

        async submitRenewal() {
            this.renewModal.loading = true;
            try {
                const res = await fetch(`/reseller/customers/${this.renewModal.customer_id}/renew`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        months: this.renewModal.months,
                        amount_paid: this.renewModal.amount_paid
                    })
                });
                const data = await res.json();
                this.renewModal.loading = false;
                if (data.success) {
                    this.renewModal.open = false;
                    this.showToast(data.message, 'success');
                    setTimeout(() => window.location.reload(), 800);
                } else {
                    this.showToast(data.message || 'Line renewal failed.', 'error');
                }
            } catch (err) {
                this.renewModal.loading = false;
                this.showToast('Network error during renewal.', 'error');
            }
        },

        // --- Ping Modal Diagnostics ---
        runPingTest(id, name, username) {
            this.activeMenu = null;
            this.pingModal.customer_id = id;
            this.pingModal.customer_name = name;
            this.pingModal.username = username;
            this.pingModal.ip = '';
            this.pingModal.gateway = '';
            this.pingModal.sent = 4;
            this.pingModal.received = 0;
            this.pingModal.loss_percent = 0;
            this.pingModal.rtt_avg = '--';
            this.pingModal.lines = ['Initiating ICMP Ping handshake...'];
            this.pingModal.open = true;
            this.executePing();
        },

        async executePing() {
            this.pingModal.loading = true;
            try {
                const res = await fetch(`/reseller/customers/${this.pingModal.customer_id}/ping`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ count: 4 })
                });
                const data = await res.json();
                this.pingModal.loading = false;
                this.pingModal.ip = data.ip || 'No IP';
                this.pingModal.gateway = data.gateway || 'Core Gateway';
                this.pingModal.sent = data.sent || 4;
                this.pingModal.received = data.received || 0;
                this.pingModal.loss_percent = data.loss_percent !== undefined ? data.loss_percent : (data.success ? 0 : 100);
                this.pingModal.rtt_avg = data.rtt_avg || (data.success ? '< 5ms' : 'Timeout');
                this.pingModal.lines = Array.isArray(data.lines) && data.lines.length > 0 ? data.lines : [data.message];
            } catch (e) {
                this.pingModal.loading = false;
                this.pingModal.lines = ['Error communicating with gateway ping engine.'];
            }
        },



        // --- Export Modal ---
        openExportModal() {
            this.exportModal.open = true;
        },

        triggerExport() {
            const params = new URLSearchParams({
                preset: this.exportModal.preset,
                status: this.exportModal.status,
                search: '{{ $search }}',
                package_id: '{{ $packageId }}',
                zone: '{{ $zone }}',
                online_status: '{{ $onlineStatus }}'
            });
            window.location.href = `{{ route('reseller.customers.export') }}?` + params.toString();
            this.exportModal.open = false;
            this.showToast('Starting CSV download...', 'info');
        },

        toggleStatus(target) {
            const customer = (typeof target === 'object' && target !== null) ? target : { id: target };
            if (customer.status === 'expired' || customer.is_expired) {
                this.showToast('গ্রাহকের মেয়াদ শেষ হয়ে গেছে। দয়া করে আগে বিল পরিশোধ বা লাইন রিনিউ করুন।', 'error');
                return;
            }

            fetch(`/reseller/customers/${customer.id}/toggle-status`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    this.showToast(data.message, 'success');
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    this.showToast(data.message || 'Failed to update status.', 'error');
                }
            })
            .catch(() => {
                this.showToast('Network error while updating status.', 'error');
            });
        },

        disconnectSession(id) {
            if (!confirm('Are you sure you want to disconnect this customer active live PPPoE session?')) return;
            fetch(`/reseller/customers/${id}/disconnect`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(res => res.json())
            .then(data => {
                this.showToast(data.message, data.success ? 'success' : 'error');
            });
        },

        deleteCustomer(id, name) {
            if (!confirm(`Are you sure you want to permanently delete customer "${name}" and remove their MikroTik PPP secret?`)) return;
            
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/reseller/customers/${id}`;
            form.innerHTML = `
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <input type="hidden" name="_method" value="DELETE">
            `;
            document.body.appendChild(form);
            form.submit();
        },

        formatCurrency(num) {
            const n = parseFloat(num || 0);
            return '{{ $currencySymbol ?? "৳" }} ' + n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    };
}
</script>
@endpush
