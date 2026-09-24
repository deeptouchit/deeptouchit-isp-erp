@extends('tenant.layouts.app')

@section('title', 'Panel Licenses - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
{{-- Page-specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="panelLicensesManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">
    
    <!-- Floating Global Toast Notification -->
    <div x-show="toast.show" 
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         class="fixed top-5 right-5 z-50 flex items-center gap-2.5 px-4 py-2.5 rounded-xl shadow-xl border text-xs font-semibold"
         :class="{
             'bg-slate-900 text-white border-slate-700': toast.type === 'info',
             'bg-emerald-600 text-white border-emerald-500': toast.type === 'success',
             'bg-rose-600 text-white border-rose-500': toast.type === 'error'
         }"
         style="display: none;">
        <i class="fas text-xs" :class="{
            'fa-circle-info': toast.type === 'info',
            'fa-circle-check': toast.type === 'success',
            'fa-circle-exclamation': toast.type === 'error'
        }"></i>
        <span x-text="toast.message"></span>
    </div>

    <!-- 1. Top Header Bar (Strict Rules: Icon + Title + Actions ONLY - No Subtitle) -->
    <div class="flex items-center justify-between bg-white px-3.5 py-2.5 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-700 border border-purple-100 flex items-center justify-center text-sm shadow-xs flex-shrink-0">
                <i class="fas fa-key"></i>
            </div>
            <h1 class="text-xs font-bold text-slate-800 tracking-tight">Panel Licenses</h1>
        </div>

        <div class="flex items-center gap-2">
            <!-- Link to Subscription Invoices -->
            <a href="{{ route('tenant.resellers.invoices') }}" 
               class="px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold text-xs shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-file-invoice-dollar text-slate-400 text-xs"></i>
                <span>Subscription Invoices</span>
            </a>

            <!-- Generate Monthly Bills Button -->
            <button type="button" 
                    @click="openBulkBillModal()" 
                    class="px-3 py-1.5 rounded-lg border border-purple-200 bg-purple-50 hover:bg-purple-100 text-purple-700 font-semibold text-xs shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-wand-magic-sparkles text-purple-600 text-[11px]"></i>
                <span>Generate Invoices</span>
            </button>

            <!-- Renew / Extend License Button -->
            <button type="button" 
                    @click="openRenewModal()" 
                    class="px-3.5 py-1.5 rounded-lg bg-purple-600 hover:bg-purple-700 text-white font-semibold text-xs shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-arrows-rotate text-[10px]"></i>
                <span>Renew License</span>
            </button>
        </div>
    </div>

    <!-- 2. KPI Summary Strip (Strictly 6 Cards) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        <!-- Card 1: Software Monthly MRR -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Monthly MRR</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-slate-900">@currency($totalMonthlyMRR)</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-purple-50 text-purple-600 border-purple-100 flex items-center justify-center">
                <i class="fas fa-laptop-code"></i>
            </div>
        </div>

        <!-- Card 2: Active Licenses -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-emerald-600">Active Licenses</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-emerald-700">{{ number_format($activePanelsCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-emerald-50 text-emerald-600 border-emerald-100 flex items-center justify-center">
                <i class="fas fa-circle-check"></i>
            </div>
        </div>

        <!-- Card 3: Expiring Soon -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-amber-600">Expiring (≤ 7d)</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-amber-700">{{ number_format($expiringSoonCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-amber-50 text-amber-600 border-amber-100 flex items-center justify-center">
                <i class="fas fa-clock"></i>
            </div>
        </div>

        <!-- Card 4: Expired Panels -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-rose-600">Expired Panels</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-rose-700">{{ number_format($expiredPanelsCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-rose-50 text-rose-600 border-rose-100 flex items-center justify-center">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
        </div>

        <!-- Card 5: Grace Period -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-cyan-600">Grace Period</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-cyan-700">{{ number_format($gracePeriodCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-cyan-50 text-cyan-600 border-cyan-100 flex items-center justify-center">
                <i class="fas fa-shield-halved"></i>
            </div>
        </div>

        <!-- Card 6: Total Resellers -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Total Tracked</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-slate-800">{{ number_format($totalResellersCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 bg-slate-100 text-slate-600 border-slate-200 flex items-center justify-center">
                <i class="fas fa-handshake"></i>
            </div>
        </div>
    </div>

    <!-- 3. Search & Filter Bar -->
    <div class="p-3 bg-white rounded-xl border border-slate-200 shadow-xs">
        <form action="{{ route('tenant.resellers.subscriptions') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-7 gap-2">
            
            <!-- Search Input -->
            <div class="lg:col-span-3">
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-2.5 pointer-events-none text-slate-400 text-xs">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" 
                           name="search" 
                           value="{{ request('search') }}" 
                           placeholder="Search Reseller Name, Code, Prefix, Mobile..." 
                           class="w-full pl-8 pr-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-purple-500 outline-hidden transition font-normal" />
                </div>
            </div>

            <!-- Reseller Filter Dropdown -->
            <div>
                <select name="reseller_id" 
                        class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:bg-white focus:border-purple-500 outline-hidden transition font-normal">
                    <option value="">All Resellers</option>
                    @foreach($allResellers as $r)
                        <option value="{{ $r->id }}" {{ request('reseller_id') == $r->id ? 'selected' : '' }}>
                            {{ $r->name }} ({{ $r->code }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Panel Status Filter -->
            <div>
                <select name="panel_status" 
                        class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:bg-white focus:border-purple-500 outline-hidden transition font-normal">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('panel_status') === 'active' ? 'selected' : '' }}>Active Panels</option>
                    <option value="expiring_soon" {{ request('panel_status') === 'expiring_soon' ? 'selected' : '' }}>Expiring Soon (≤ 7d)</option>
                    <option value="expired" {{ request('panel_status') === 'expired' ? 'selected' : '' }}>Expired Panels</option>
                    <option value="grace_period" {{ request('panel_status') === 'grace_period' ? 'selected' : '' }}>Grace Period</option>
                </select>
            </div>

            <!-- Per Page Selector -->
            <div>
                <select name="per_page" 
                        class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:bg-white focus:border-purple-500 outline-hidden transition font-normal">
                    <option value="10" {{ request('per_page') == 10 ? 'selected' : '' }}>10 Per Page</option>
                    <option value="20" {{ request('per_page', 20) == 20 ? 'selected' : '' }}>20 Per Page</option>
                    <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50 Per Page</option>
                    <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100 Per Page</option>
                </select>
            </div>

            <!-- Action Buttons (Strict Universal Standard) -->
            <div class="flex items-center gap-1.5 flex-shrink-0">
                <button type="submit" 
                        class="flex-1 sm:flex-initial bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-1.5 px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer" 
                        title="Apply Filters">
                    <i class="fas fa-filter text-[10px]"></i>
                    <span>Filter</span>
                </button>
                <a href="{{ route('tenant.resellers.subscriptions') }}" 
                   class="flex-1 sm:flex-initial bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-1.5 px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer" 
                   title="Reset Filters">
                    <i class="fas fa-rotate-left text-[10px]"></i>
                    <span>Reset</span>
                </a>
            </div>
        </form>
    </div>

    <!-- 4. Master Compact Table (Pure CSS System Compliant) -->
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-xs">
        <div class="px-3.5 py-2 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
            <span class="font-normal text-slate-700 text-xs flex items-center gap-1.5">
                <i class="fas fa-key text-slate-400 text-xs"></i>
                <span>Panel Licenses</span>
            </span>
            <span class="text-[11px] text-slate-500 font-mono font-normal">
                Total {{ $resellers->total() }} Records
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-10">#</th>
                        <th>License Status</th>
                        <th>Code</th>
                        <th>Reseller Name</th>
                        <th class="text-center">Prefix</th>
                        <th>Billing Model</th>
                        <th class="text-right">Monthly Panel Fee</th>
                        <th class="text-center">Expiry Date</th>
                        <th class="text-center">Days Remaining</th>
                        <th class="text-right">Wallet Balance</th>
                        <th class="w-10 text-center no-sort">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-slate-700">
                    @forelse($resellers as $index => $res)
                        @php
                            $today = \Carbon\Carbon::today();
                            $expiryDate = $res->panel_expiry_date ? \Carbon\Carbon::parse($res->panel_expiry_date) : null;
                            $daysLeft = $expiryDate ? $today->diffInDays($expiryDate, false) : null;
                            $billingBadge = $res->billing_badge;
                            $panelBadge = $res->panel_badge;

                            $resPayload = [
                                'id' => $res->id,
                                'name' => $res->name,
                                'code' => $res->code,
                                'prefix' => $res->prefix,
                                'contact_person' => $res->contact_person,
                                'mobile' => $res->mobile,
                                'monthly_panel_charge' => (float)$res->monthly_panel_charge,
                                'panel_expiry_date' => $res->panel_expiry_date ? substr((string)$res->panel_expiry_date, 0, 10) : null,
                                'panel_billing_status' => $res->panel_billing_status,
                                'wallet_balance' => (float)$res->wallet_balance,
                                'billing_type' => $res->billing_type,
                            ];
                        @endphp
                        <tr>
                            <!-- 1. Index -->
                            <td class="font-mono text-slate-400">
                                {{ $resellers->firstItem() + $index }}
                            </td>

                            <!-- 2. License Status -->
                            <td>
                                @if($res->panel_billing_status === 'ACTIVE' && ($expiryDate === null || $daysLeft >= 0))
                                    @if($daysLeft !== null && $daysLeft <= 7)
                                        <span class="inline-flex items-center justify-center gap-1 px-2 py-0.5 rounded-full text-[10px] bg-amber-50 text-amber-700 border border-amber-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                            <span>Expiring Soon</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center justify-center gap-1 px-2 py-0.5 rounded-full text-[10px] bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            <span>Active</span>
                                        </span>
                                    @endif
                                @elseif($res->panel_billing_status === 'GRACE_PERIOD')
                                    <span class="inline-flex items-center justify-center gap-1 px-2 py-0.5 rounded-full text-[10px] bg-cyan-50 text-cyan-700 border border-cyan-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-cyan-500"></span>
                                        <span>Grace Period</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center justify-center gap-1 px-2 py-0.5 rounded-full text-[10px] bg-rose-50 text-rose-700 border border-rose-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        <span>Expired</span>
                                    </span>
                                @endif
                            </td>

                            <!-- 3. Code -->
                            <td class="font-mono text-purple-700 font-semibold">
                                <button type="button" 
                                        @click="openDetailsModal({{ Js::from($resPayload) }})" 
                                        class="hover:underline cursor-pointer">
                                    {{ $res->code }}
                                </button>
                            </td>

                            <!-- 4. Reseller Name -->
                            <td class="text-slate-800 font-medium">
                                <button type="button" 
                                        @click="openDetailsModal({{ Js::from($resPayload) }})" 
                                        class="hover:underline text-left cursor-pointer">
                                    {{ $res->name }}
                                </button>
                            </td>

                            <!-- 5. Prefix -->
                            <td class="text-center font-mono text-purple-700">
                                {{ $res->prefix ? $res->prefix . '_*' : '--' }}
                            </td>

                            <!-- 6. Billing Model -->
                            <td class="font-mono text-[10px]">
                                <span class="inline-flex px-1.5 py-0.5 rounded border {{ $billingBadge['class'] ?? 'bg-slate-50 text-slate-700 border-slate-200' }}">
                                    {{ $billingBadge['label'] ?? $res->billing_type }}
                                </span>
                            </td>

                            <!-- 7. Monthly Panel Fee -->
                            <td class="text-right font-mono font-bold text-slate-800">
                                @currency($res->monthly_panel_charge)
                            </td>

                            <!-- 8. Expiry Date -->
                            <td class="text-center font-mono text-slate-700">
                                @if($expiryDate)
                                    <span>{{ $expiryDate->format('d M, Y') }}</span>
                                @else
                                    <span class="text-slate-400">Unlimited / Unset</span>
                                @endif
                            </td>

                            <!-- 9. Days Remaining -->
                            <td class="text-center font-mono font-semibold">
                                @if($expiryDate)
                                    @if($daysLeft > 7)
                                        <span class="text-emerald-600">{{ $daysLeft }} days</span>
                                    @elseif($daysLeft >= 0)
                                        <span class="text-amber-600">{{ $daysLeft }} days left</span>
                                    @else
                                        <span class="text-rose-600">{{ abs($daysLeft) }}d overdue</span>
                                    @endif
                                @else
                                    <span class="text-slate-400">--</span>
                                @endif
                            </td>

                            <!-- 10. Wallet Balance -->
                            <td class="text-right font-mono text-emerald-700 font-bold">
                                @currency($res->wallet_balance)
                            </td>

                            <!-- 11. Actions (3-Dot Action Button) -->
                            <td class="w-10 text-center">
                                <button type="button" 
                                        @click.stop="toggleMenu({{ Js::from($resPayload) }}, $event)" 
                                        class="lic-action-btn w-5 h-5 mx-auto rounded hover:bg-slate-200 text-slate-500 hover:text-purple-700 transition cursor-pointer flex items-center justify-center text-[10px]"
                                        title="Actions">
                                    <i class="fas fa-ellipsis-v text-[10px] pointer-events-none"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="p-8 text-center">
                                <div class="max-w-md mx-auto space-y-2">
                                    <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 mx-auto flex items-center justify-center text-base border border-purple-100">
                                        <i class="fas fa-key"></i>
                                    </div>
                                    <h3 class="text-xs font-normal text-slate-800">No Panel Licenses Found</h3>
                                    <p class="text-[11px] text-slate-400 font-normal">Add resellers under Reseller Directory to manage licenses and software billing.</p>
                                    <div class="pt-1 flex items-center justify-center gap-2">
                                        <a href="{{ route('tenant.resellers.index') }}" 
                                           class="bg-purple-600 hover:bg-purple-700 text-white font-medium text-xs px-3.5 py-1.5 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                                            <i class="fas fa-handshake text-[10px]"></i>
                                            <span>Open Reseller Directory</span>
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Bar -->
        @if($resellers->hasPages())
            <div class="px-3.5 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                <span class="text-[11px] text-slate-500 font-normal">
                    Showing {{ $resellers->firstItem() }} to {{ $resellers->lastItem() }} of {{ $resellers->total() }} licenses
                </span>
                <div class="scale-90 origin-right">
                    {{ $resellers->links() }}
                </div>
            </div>
        @endif
    </div>

    <!-- 5. Global Floating 3-Dot Action Menu -->
    <div x-show="activeMenu !== null" 
         @click.away="activeMenu = null"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="fixed z-50 w-52 bg-white rounded-xl shadow-2xl border border-slate-200 py-1.5 text-xs divide-y divide-slate-100"
         :style="`top: ${menuPos.top}; bottom: ${menuPos.bottom}; right: ${menuPos.right}; left: ${menuPos.left};`"
         style="display: none;">

        <!-- Group 1: Subscription Actions -->
        <div class="py-1">
            <button type="button" 
                    @click="openRenewModal(activeMenu); activeMenu = null;" 
                    class="w-full text-left px-3.5 py-1.5 hover:bg-slate-50 text-slate-700 hover:text-purple-600 flex items-center gap-2 transition cursor-pointer">
                <i class="fas fa-arrows-rotate w-3.5 text-slate-400"></i>
                <span>Renew License</span>
            </button>
            <button type="button" 
                    @click="openEditSettingsModal(activeMenu); activeMenu = null;" 
                    class="w-full text-left px-3.5 py-1.5 hover:bg-slate-50 text-slate-700 hover:text-purple-600 flex items-center gap-2 transition cursor-pointer">
                <i class="fas fa-gear w-3.5 text-slate-400"></i>
                <span>Edit License Settings</span>
            </button>
            <a :href="`{{ route('tenant.resellers.invoices') }}?reseller_id=${activeMenu?.id || ''}`" 
               class="w-full text-left px-3.5 py-1.5 hover:bg-slate-50 text-slate-700 hover:text-purple-600 flex items-center gap-2 transition cursor-pointer">
                <i class="fas fa-file-invoice-dollar w-3.5 text-slate-400"></i>
                <span>View Reseller Invoices</span>
            </a>
        </div>

        <!-- Group 2: Access & Diagnostics -->
        <div class="py-1">
            <button type="button" 
                    @click="togglePanelStatus(activeMenu); activeMenu = null;" 
                    class="w-full text-left px-3.5 py-1.5 hover:bg-slate-50 text-slate-700 flex items-center gap-2 transition cursor-pointer"
                    :class="activeMenu?.panel_billing_status === 'ACTIVE' ? 'hover:text-amber-600' : 'hover:text-emerald-600'">
                <i class="fas w-3.5 text-slate-400" :class="activeMenu?.panel_billing_status === 'ACTIVE' ? 'fa-pause' : 'fa-play'"></i>
                <span x-text="activeMenu?.panel_billing_status === 'ACTIVE' ? 'Set to Expired / Block' : 'Activate Panel Access'"></span>
            </button>
            <button type="button" 
                    @click="openDetailsModal(activeMenu); activeMenu = null;" 
                    class="w-full text-left px-3.5 py-1.5 hover:bg-slate-50 text-slate-700 hover:text-purple-600 flex items-center gap-2 transition cursor-pointer">
                <i class="fas fa-circle-info w-3.5 text-slate-400"></i>
                <span>View Details</span>
            </button>
        </div>
    </div>

    <!-- 6. MODAL 1: Renew / Extend Software Subscription -->
    <div x-show="modals.renew" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50"
         style="display: none;">
        
        <div @click.away="modals.renew = false" 
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="transform opacity-0 scale-95"
             x-transition:enter-end="transform opacity-100 scale-100"
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden">
            
            <!-- Soft Natural Header -->
            <div class="px-4 py-3 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-purple-50 text-purple-600 border border-purple-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-arrows-rotate"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Renew Reseller License</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">Extend software access and record subscription payment</p>
                    </div>
                </div>
                <button type="button" @click="modals.renew = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center text-xs cursor-pointer">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>

            <!-- Modal Form Body -->
            <div class="p-4 space-y-3.5 text-xs">
                <!-- Select Reseller -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                        Select Reseller <span class="text-rose-500">*</span>
                    </label>
                    <select x-model="renewForm.reseller_id" 
                            @change="onResellerChange()"
                            class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-purple-500 outline-hidden transition font-normal">
                        <option value="">-- Choose Reseller --</option>
                        @foreach($allResellers as $r)
                            <option value="{{ $r->id }}" data-charge="{{ $r->monthly_panel_charge }}" data-expiry="{{ $r->panel_expiry_date }}" data-wallet="{{ $r->wallet_balance }}">
                                {{ $r->name }} ({{ $r->code }}) - Fee: ৳{{ number_format($r->monthly_panel_charge, 0) }}/mo
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Reseller Live Context Badge -->
                <div x-show="selectedResellerMeta" class="p-2.5 bg-purple-50/50 rounded-lg border border-purple-100 grid grid-cols-2 gap-2 text-[11px]">
                    <div>
                        <span class="text-slate-500 block">Current Expiry:</span>
                        <span class="font-semibold text-slate-800 font-mono" x-text="selectedResellerMeta?.expiry || 'Unset / Expired'"></span>
                    </div>
                    <div>
                        <span class="text-slate-500 block">Wallet Balance:</span>
                        <span class="font-semibold text-emerald-700 font-mono" x-text="'৳' + Number(selectedResellerMeta?.wallet || 0).toFixed(2)"></span>
                    </div>
                </div>

                <!-- Renewal Months & Amount -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            Duration (Months) <span class="text-rose-500">*</span>
                        </label>
                        <select x-model="renewForm.months" 
                                @change="calcRenewAmount()"
                                class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-purple-500 outline-hidden transition font-normal">
                            <option value="1">1 Month</option>
                            <option value="2">2 Months</option>
                            <option value="3">3 Months (Quarterly)</option>
                            <option value="6">6 Months (Half-Yearly)</option>
                            <option value="12">12 Months (1 Year)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                            Total Fee (৳) <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" 
                               step="0.01" 
                               x-model="renewForm.amount" 
                               placeholder="0.00" 
                               class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono font-bold text-slate-800 focus:bg-white focus:border-purple-500 outline-hidden transition" />
                    </div>
                </div>

                <!-- Payment Method -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                        Payment Method <span class="text-rose-500">*</span>
                    </label>
                    <select x-model="renewForm.payment_method" 
                            class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-purple-500 outline-hidden transition font-normal">
                        <option value="WALLET_DEDUCT">Deduct from Reseller Wallet Balance</option>
                        <option value="CASH">Cash Payment</option>
                        <option value="BANK">Bank Transfer / Deposit</option>
                        <option value="BKASH">bKash Merchant / Direct</option>
                        <option value="NAGAD">Nagad</option>
                        <option value="DUE">Mark as Due / Postpaid Invoice</option>
                    </select>
                </div>

                <!-- Remarks / Notes -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">Notes / Remarks</label>
                    <input type="text" 
                           x-model="renewForm.notes" 
                           placeholder="e.g. Renewed via bKash TrxID #892182" 
                           class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-purple-500 outline-hidden transition font-normal" />
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                <button type="button" 
                        @click="modals.renew = false" 
                        class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                    Cancel
                </button>
                <button type="button" 
                        @click="submitRenew()" 
                        :disabled="saving"
                        class="bg-purple-600 hover:bg-purple-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50">
                    <i class="fas fa-arrows-rotate text-[10px]" :class="saving ? 'fa-spin' : ''"></i>
                    <span x-text="saving ? 'Processing...' : 'Confirm Renewal'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- 7. MODAL 2: Edit License Settings (Monthly Fee & Expiry) -->
    <div x-show="modals.editSettings" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50"
         style="display: none;">
        
        <div @click.away="modals.editSettings = false" 
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-md overflow-hidden">
            
            <!-- Soft Natural Header -->
            <div class="px-4 py-3 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-purple-50 text-purple-600 border border-purple-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-gear"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Edit License Settings</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal" x-text="editForm.reseller_name"></p>
                    </div>
                </div>
                <button type="button" @click="modals.editSettings = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center text-xs cursor-pointer">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>

            <!-- Modal Form Body -->
            <div class="p-4 space-y-3.5 text-xs">
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                        Monthly Panel Software Charge (৳) <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" 
                           step="0.01" 
                           x-model="editForm.monthly_panel_charge" 
                           class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono font-bold text-slate-800 focus:bg-white focus:border-purple-500 outline-hidden transition" />
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">Panel Expiry Date</label>
                    <input type="date" 
                           x-model="editForm.panel_expiry_date" 
                           class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:bg-white focus:border-purple-500 outline-hidden transition" />
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">Panel Access State</label>
                    <select x-model="editForm.panel_billing_status" 
                            class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-purple-500 outline-hidden transition font-normal">
                        <option value="ACTIVE">ACTIVE (Full Portal Access)</option>
                        <option value="GRACE_PERIOD">GRACE PERIOD (Temporary Overdue Access)</option>
                        <option value="EXPIRED">EXPIRED (Suspended Access)</option>
                    </select>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                <button type="button" 
                        @click="modals.editSettings = false" 
                        class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                    Cancel
                </button>
                <button type="button" 
                        @click="submitEditSettings()" 
                        :disabled="saving"
                        class="bg-purple-600 hover:bg-purple-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50">
                    <i class="fas fa-check text-[10px]"></i>
                    <span x-text="saving ? 'Saving...' : 'Save Settings'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- 8. MODAL 3: Bulk Generate Monthly Invoices -->
    <div x-show="modals.bulkBill" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50"
         style="display: none;">
        
        <div @click.away="modals.bulkBill = false" 
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-md overflow-hidden">
            
            <!-- Soft Natural Header -->
            <div class="px-4 py-3 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-purple-50 text-purple-600 border border-purple-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-wand-magic-sparkles"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Generate Monthly Invoices</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">Auto-create invoices for active reseller panel licenses</p>
                    </div>
                </div>
                <button type="button" @click="modals.bulkBill = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center text-xs cursor-pointer">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>

            <!-- Modal Form Body -->
            <div class="p-4 space-y-3.5 text-xs">
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                        Billing Month <span class="text-rose-500">*</span>
                    </label>
                    <input type="month" 
                           x-model="bulkForm.billing_month" 
                           class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:bg-white focus:border-purple-500 outline-hidden transition" />
                </div>

                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 space-y-2">
                    <label class="flex items-start gap-2 cursor-pointer">
                        <input type="checkbox" 
                               x-model="bulkForm.auto_deduct_wallet" 
                               class="mt-0.5 rounded border-slate-300 text-purple-600 focus:ring-purple-500" />
                        <div>
                            <span class="font-semibold text-slate-800 block text-xs">Auto-deduct from Reseller Wallet</span>
                            <span class="text-[10.5px] text-slate-500">If checked and the reseller has sufficient balance, invoice will be marked as PAID immediately.</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                <button type="button" 
                        @click="modals.bulkBill = false" 
                        class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                    Cancel
                </button>
                <button type="button" 
                        @click="submitBulkBill()" 
                        :disabled="saving"
                        class="bg-purple-600 hover:bg-purple-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50">
                    <i class="fas fa-wand-magic-sparkles text-[10px]"></i>
                    <span x-text="saving ? 'Generating...' : 'Generate Invoices'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- 9. MODAL 4: Reseller License Details -->
    <div x-show="modals.details" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50"
         style="display: none;">
        
        <div @click.away="modals.details = false" 
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden">
            
            <!-- Soft Natural Header -->
            <div class="px-4 py-3 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-purple-50 text-purple-600 border border-purple-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-circle-info"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800" x-text="detailsItem?.name"></h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">Panel license details & technical overview</p>
                    </div>
                </div>
                <button type="button" @click="modals.details = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center text-xs cursor-pointer">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>

            <!-- Details Body -->
            <div class="p-4 space-y-3 text-xs">
                <div class="grid grid-cols-2 gap-2.5">
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] text-slate-500 uppercase tracking-wider block">Reseller Code</span>
                        <span class="font-mono font-bold text-purple-700 text-xs" x-text="detailsItem?.code"></span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] text-slate-500 uppercase tracking-wider block">Prefix</span>
                        <span class="font-mono font-bold text-slate-800 text-xs" x-text="detailsItem?.prefix ? detailsItem.prefix + '_*' : '--'"></span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] text-slate-500 uppercase tracking-wider block">Contact Person</span>
                        <span class="font-semibold text-slate-800 text-xs" x-text="detailsItem?.contact_person"></span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] text-slate-500 uppercase tracking-wider block">Mobile</span>
                        <span class="font-mono font-semibold text-slate-800 text-xs" x-text="detailsItem?.mobile"></span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] text-slate-500 uppercase tracking-wider block">Monthly Charge</span>
                        <span class="font-mono font-bold text-slate-900 text-xs" x-text="'৳' + Number(detailsItem?.monthly_panel_charge || 0).toFixed(2)"></span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] text-slate-500 uppercase tracking-wider block">Expiry Date</span>
                        <span class="font-mono font-bold text-slate-800 text-xs" x-text="detailsItem?.panel_expiry_date || 'Unset'"></span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] text-slate-500 uppercase tracking-wider block">Wallet Balance</span>
                        <span class="font-mono font-bold text-emerald-700 text-xs" x-text="'৳' + Number(detailsItem?.wallet_balance || 0).toFixed(2)"></span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] text-slate-500 uppercase tracking-wider block">License State</span>
                        <span class="font-semibold text-xs" :class="detailsItem?.panel_billing_status === 'ACTIVE' ? 'text-emerald-600' : 'text-rose-600'" x-text="detailsItem?.panel_billing_status"></span>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end">
                <button type="button" 
                        @click="modals.details = false" 
                        class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-4 py-1.5 rounded-lg transition cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    function panelLicensesManager() {
        return {
            activeMenu: null,
            menuPos: { top: '0px', bottom: 'auto', right: '10px', left: 'auto' },
            saving: false,
            toast: { show: false, message: '', type: 'success' },
            modals: {
                renew: false,
                editSettings: false,
                bulkBill: false,
                details: false
            },
            selectedResellerMeta: null,
            detailsItem: null,
            renewForm: {
                reseller_id: '',
                months: 1,
                amount: 0,
                payment_method: 'WALLET_DEDUCT',
                notes: ''
            },
            editForm: {
                reseller_id: null,
                reseller_name: '',
                monthly_panel_charge: 0,
                panel_expiry_date: '',
                panel_billing_status: 'ACTIVE'
            },
            bulkForm: {
                billing_month: new Date().toISOString().slice(0, 7),
                auto_deduct_wallet: true
            },

            showToast(message, type = 'success') {
                this.toast.message = message;
                this.toast.type = type;
                this.toast.show = true;
                setTimeout(() => { this.toast.show = false; }, 3500);
            },

            toggleMenu(item, event) {
                if (this.activeMenu?.id === item.id) {
                    this.activeMenu = null;
                    return;
                }
                this.activeMenu = item;
                const rect = event.currentTarget.getBoundingClientRect();
                const dropdownHeight = 220;
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

            openRenewModal(item = null) {
                this.renewForm = {
                    reseller_id: item ? item.id : '',
                    months: 1,
                    amount: item ? item.monthly_panel_charge : 0,
                    payment_method: 'WALLET_DEDUCT',
                    notes: ''
                };
                this.selectedResellerMeta = item ? {
                    charge: item.monthly_panel_charge,
                    expiry: item.panel_expiry_date,
                    wallet: item.wallet_balance
                } : null;
                this.modals.renew = true;
            },

            onResellerChange() {
                const sel = document.querySelector(`select[x-model="renewForm.reseller_id"]`);
                if (sel && sel.selectedOptions[0]) {
                    const opt = sel.selectedOptions[0];
                    const charge = parseFloat(opt.getAttribute('data-charge') || 0);
                    const expiry = opt.getAttribute('data-expiry') || '';
                    const wallet = parseFloat(opt.getAttribute('data-wallet') || 0);
                    this.selectedResellerMeta = { charge, expiry, wallet };
                    this.calcRenewAmount();
                } else {
                    this.selectedResellerMeta = null;
                }
            },

            calcRenewAmount() {
                const charge = this.selectedResellerMeta ? this.selectedResellerMeta.charge : 0;
                this.renewForm.amount = charge * parseInt(this.renewForm.months || 1);
            },

            async submitRenew() {
                if (!this.renewForm.reseller_id) {
                    this.showToast('Please select a reseller.', 'error');
                    return;
                }
                this.saving = true;
                try {
                    const res = await fetch("{{ route('tenant.resellers.subscriptions.renew') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(this.renewForm)
                    });
                    const data = await res.json();
                    if (res.ok && data.success) {
                        this.showToast(data.message || 'License renewed successfully!', 'success');
                        this.modals.renew = false;
                        setTimeout(() => window.location.reload(), 600);
                    } else {
                        this.showToast(data.message || 'Renewal failed.', 'error');
                    }
                } catch (e) {
                    this.showToast('Error processing renewal request', 'error');
                } finally {
                    this.saving = false;
                }
            },

            openEditSettingsModal(item) {
                this.editForm = {
                    reseller_id: item.id,
                    reseller_name: `${item.name} (${item.code})`,
                    monthly_panel_charge: item.monthly_panel_charge,
                    panel_expiry_date: item.panel_expiry_date || '',
                    panel_billing_status: item.panel_billing_status || 'ACTIVE'
                };
                this.modals.editSettings = true;
            },

            async submitEditSettings() {
                this.saving = true;
                try {
                    const res = await fetch("{{ route('tenant.resellers.subscriptions.update-charge') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(this.editForm)
                    });
                    const data = await res.json();
                    if (res.ok && data.success) {
                        this.showToast(data.message || 'Settings updated successfully!', 'success');
                        this.modals.editSettings = false;
                        setTimeout(() => window.location.reload(), 600);
                    } else {
                        this.showToast(data.message || 'Failed to update settings.', 'error');
                    }
                } catch (e) {
                    this.showToast('Error updating license settings', 'error');
                } finally {
                    this.saving = false;
                }
            },

            openBulkBillModal() {
                this.bulkForm = {
                    billing_month: new Date().toISOString().slice(0, 7),
                    auto_deduct_wallet: true
                };
                this.modals.bulkBill = true;
            },

            async submitBulkBill() {
                this.saving = true;
                try {
                    const res = await fetch("{{ route('tenant.resellers.subscriptions.generate-bills') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(this.bulkForm)
                    });
                    const data = await res.json();
                    if (res.ok && data.success) {
                        this.showToast(data.message || 'Invoices generated successfully!', 'success');
                        this.modals.bulkBill = false;
                        setTimeout(() => window.location.reload(), 600);
                    } else {
                        this.showToast(data.message || 'Failed to generate invoices.', 'error');
                    }
                } catch (e) {
                    this.showToast('Error generating bulk invoices', 'error');
                } finally {
                    this.saving = false;
                }
            },

            async togglePanelStatus(item) {
                try {
                    const res = await fetch(`/admin/resellers/subscriptions/${item.id}/toggle-status`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    });
                    const data = await res.json();
                    if (res.ok && data.success) {
                        this.showToast(data.message || 'Status updated!', 'success');
                        setTimeout(() => window.location.reload(), 500);
                    } else {
                        this.showToast(data.message || 'Status toggle failed.', 'error');
                    }
                } catch (e) {
                    this.showToast('Error toggling license status', 'error');
                }
            },

            openDetailsModal(item) {
                this.detailsItem = item;
                this.modals.details = true;
            }
        };
    }
</script>
@endpush
