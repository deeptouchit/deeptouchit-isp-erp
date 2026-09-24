@php
    $authUser = auth()->user();
    $currentReseller = $reseller ?? ($authUser?->reseller ?: \App\Models\TenantReseller::find($authUser?->reseller_id));
    if (!$currentReseller && $authUser?->tenant_id) {
        $currentReseller = \App\Models\TenantReseller::where('tenant_id', $authUser->tenant_id)->first();
    }
    $currentTenant = $tenant ?? ($currentReseller?->tenant ?: ($authUser?->tenant ?: \App\Models\Tenant::find($authUser?->tenant_id)));
    if (!$currentTenant) {
        $currentTenant = \App\Models\Tenant::first();
    }
    $resellerName = $currentReseller->name ?? 'রিসেলার পার্টনার';
    $resellerCode = $currentReseller->code ?? ($currentReseller->prefix ?? 'PARTNER');
    $walletBal = (float)($currentReseller->wallet_balance ?? 0);
    $availBal = (float)($currentReseller->total_available_balance ?? $walletBal);
@endphp

<!-- Mobile Backdrop -->
<div x-show="mobileSidebar" 
     x-transition:enter="transition-opacity ease-linear duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition-opacity ease-linear duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @click="mobileSidebar = false" 
     class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-40 lg:hidden" 
     style="display: none;" 
     x-cloak>
</div>

<!-- Reseller Sidebar Navigation Container -->
<aside :class="mobileSidebar ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'" 
       class="fixed inset-y-0 left-0 z-50 w-60 bg-white border-r border-slate-200 flex flex-col transition-transform duration-200 ease-in-out shadow-lg lg:shadow-none select-none">

    <!-- 1. Header: Brand Logo & Partner Identity -->
    <div class="h-14 px-3.5 border-b border-slate-200 flex items-center justify-between bg-slate-50/80">
        <a href="{{ route('reseller.dashboard') }}" class="flex items-center gap-2.5 min-w-0 group">
            <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-purple-600 to-indigo-600 text-white flex items-center justify-center font-bold text-sm shadow-xs flex-shrink-0 group-hover:scale-105 transition">
                <i class="fas fa-handshake-angle text-xs"></i>
            </div>
            <div class="min-w-0 flex-1">
                <span class="font-bold text-slate-900 text-xs truncate block leading-tight">
                    {{ $resellerName }}
                </span>
            </div>
        </a>
        <button type="button" 
                @click="mobileSidebar = false" 
                class="lg:hidden p-1 text-slate-400 hover:text-slate-600 rounded-md hover:bg-slate-200/60 transition cursor-pointer">
            <i class="fas fa-times text-xs"></i>
        </button>
    </div>

    <!-- 2. Search & Menu Filter Bar -->
    <div class="px-3 pt-2.5 pb-1">
        <div class="relative">
            <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-[10px]"></i>
            <input type="search" 
                   name="reseller_menu_search_no_autofill"
                   autocomplete="off"
                   autocorrect="off"
                   autocapitalize="off"
                   spellcheck="false"
                   data-lpignore="true"
                   data-form-type="other"
                   readonly
                   onfocus="this.removeAttribute('readonly');"
                   x-model="filterQuery" 
                   placeholder="{{ __('Quick find menu...') }}" 
                   class="w-full pl-7 pr-6 py-1 rounded-lg border border-slate-200 bg-slate-50/80 text-[11px] text-slate-700 placeholder:text-slate-400 focus:bg-white focus:outline-none focus:ring-1 focus:ring-purple-500 focus:border-purple-500 transition shadow-2xs">
            <button type="button" 
                    x-show="filterQuery" 
                    @click="filterQuery = ''" 
                    class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 cursor-pointer"
                    style="display: none;">
                <i class="fas fa-times-circle text-[10px]"></i>
            </button>
        </div>
    </div>

    <!-- 3. Navigation Links (Scrollable Menu) -->
    <div class="flex-1 overflow-y-auto px-2.5 py-2 space-y-1 text-xs">

        <!-- 1. Main Dashboard -->
        <a href="{{ route('reseller.dashboard') }}" 
           x-show="!filterQuery || 'dashboard home overview metrics analytics ড্যাশবোর্ড'.includes(filterQuery.toLowerCase())"
           class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg border transition text-[11.5px] shadow-2xs cursor-pointer {{ request()->routeIs('reseller.dashboard') ? 'border-purple-200 bg-purple-50/80 text-purple-700 font-bold border-l-4 border-l-purple-600' : 'border-slate-200/90 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-semibold' }}">
            <i class="fas fa-chart-pie w-4 text-center text-xs {{ request()->routeIs('reseller.dashboard') ? 'text-purple-600' : 'text-slate-500' }}"></i>
            <span>{{ __('Dashboard') }}</span>
        </a>

        <!-- 2. Customer Management (Collapsible) -->
        @php
            $isCustActive = request()->routeIs('reseller.customers*');
        @endphp
        <div x-data="{ open: {{ $isCustActive ? 'true' : 'false' }} }" 
             x-show="!filterQuery || 'customers subscribers retail pppoe online expired due disconnected direct users client গ্রাহক'.includes(filterQuery.toLowerCase())"
             class="space-y-1">
            <button type="button" 
                    @click="open = !open" 
                    class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg border transition text-[11.5px] font-semibold shadow-2xs cursor-pointer {{ $isCustActive ? 'border-cyan-200 bg-cyan-50/80 text-cyan-700 font-bold border-l-4 border-l-cyan-600' : 'border-slate-200/90 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300' }}">
                <div class="flex items-center gap-2">
                    <i class="fas fa-users w-4 text-center text-xs {{ $isCustActive ? 'text-cyan-600' : 'text-slate-500' }}"></i>
                    <span>{{ __('Customers') }}</span>
                </div>
                <i class="fas fa-chevron-right text-[8.5px] text-slate-400 transition-transform duration-150" :class="open ? 'rotate-90 text-slate-600' : ''"></i>
            </button>

            <!-- Sub-items -->
            <div x-show="open || filterQuery" x-collapse class="pl-2.5 pr-0.5 space-y-1">
                <a href="{{ route('reseller.customers.index') }}" 
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('reseller.customers*') && !request()->has('status') && !request()->routeIs('reseller.customers.bulk-payments*') ? 'border-cyan-300 bg-cyan-50 text-cyan-800 font-bold border-l-4 border-l-cyan-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 font-medium' }}">
                    {{ __('All Customers') }}
                </a>
                @if(!$authUser || !$authUser->isResellerTech())
                <a href="{{ route('reseller.customers.index', ['status' => 'due']) }}" 
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->input('status') === 'due' ? 'border-amber-300 bg-amber-50 text-amber-800 font-bold border-l-4 border-l-amber-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 font-medium' }}">
                    <span class="flex items-center justify-between">
                        <span>{{ __('Due / Unpaid') }}</span>
                        <span class="px-1.5 py-0.2 rounded text-[9px] bg-amber-100 text-amber-800 font-bold">Collect</span>
                    </span>
                </a>
                @endif
                <a href="{{ route('reseller.customers.index', ['status' => 'active']) }}" 
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->input('status') === 'active' ? 'border-cyan-300 bg-cyan-50 text-cyan-800 font-bold border-l-4 border-l-cyan-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 font-medium' }}">
                    {{ __('Active Subscribers') }}
                </a>
                @if($authUser && $authUser->isResellerAdmin())
                    <a href="{{ route('reseller.customers.bulk-payments') }}" 
                       class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('reseller.customers.bulk-payments*') ? 'border-purple-300 bg-purple-50 text-purple-800 font-bold border-l-4 border-l-purple-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 font-medium' }}">
                        <span class="flex items-center justify-between">
                            <span>{{ __('Bulk Payments') }}</span>
                            <span class="px-1.5 py-0.2 rounded text-[9px] bg-purple-100 text-purple-700 font-bold">Fast</span>
                        </span>
                    </a>
                @endif
                @if($authUser && ($authUser->isResellerAdmin() || $authUser->isResellerTech()))
                    <a href="{{ route('reseller.customers.index', ['status' => 'disconnected']) }}" 
                       class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->input('status') === 'disconnected' ? 'border-cyan-300 bg-cyan-50 text-cyan-800 font-bold border-l-4 border-l-cyan-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 font-medium' }}">
                        {{ __('Disconnected') }}
                    </a>
                @endif
            </div>
        </div>

        <!-- 3. Billing & Invoices (Admin: Full / Collector: Collections / Tech: Hidden) -->
        @if($authUser && $authUser->isResellerAdmin())
            @php
                $isBillingActive = request()->routeIs('reseller.customer-invoices*') || request()->routeIs('reseller.collections*') || request()->routeIs('reseller.invoices*');
            @endphp
            <div x-data="{ open: {{ $isBillingActive ? 'true' : 'false' }} }" 
                 x-show="!filterQuery || 'billing invoices bills payments collections receipts customer wholesale money statements বিল ইনভয়েস'.includes(filterQuery.toLowerCase())"
                 class="space-y-1">
                <button type="button" 
                        @click="open = !open" 
                        class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg border transition text-[11.5px] font-semibold shadow-2xs cursor-pointer {{ $isBillingActive ? 'border-blue-200 bg-blue-50/80 text-blue-700 font-bold border-l-4 border-l-blue-600' : 'border-slate-200/90 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300' }}">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-file-invoice-dollar w-4 text-center text-xs {{ $isBillingActive ? 'text-blue-600' : 'text-slate-500' }}"></i>
                        <span>{{ __('Billing & Invoices') }}</span>
                    </div>
                    <i class="fas fa-chevron-right text-[8.5px] text-slate-400 transition-transform duration-150" :class="open ? 'rotate-90 text-slate-600' : ''"></i>
                </button>

                <!-- Sub-items -->
                <div x-show="open || filterQuery" x-collapse class="pl-2.5 pr-0.5 space-y-1">
                    <a href="{{ route('reseller.customer-invoices.index') }}" 
                       class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('reseller.customer-invoices*') ? 'border-blue-300 bg-blue-50 text-blue-800 font-bold border-l-4 border-l-blue-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 font-medium' }}">
                        {{ __('Customer Invoices') }}
                    </a>
                    <a href="{{ route('reseller.collections.index') }}" 
                       class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('reseller.collections*') ? 'border-blue-300 bg-blue-50 text-blue-800 font-bold border-l-4 border-l-blue-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 font-medium' }}">
                        {{ __('Customer Payments') }}
                    </a>
                    <a href="{{ route('reseller.invoices.index') }}" 
                       class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('reseller.invoices*') ? 'border-blue-300 bg-blue-50 text-blue-800 font-bold border-l-4 border-l-blue-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 font-medium' }}">
                        {{ __('Wholesale Invoices') }}
                    </a>
                </div>
            </div>
        @elseif($authUser && $authUser->isResellerCollector())
            <!-- Direct Collections Link for Collector -->
            <a href="{{ route('reseller.collections.index') }}" 
               x-show="!filterQuery || 'collections payments receipts money রিসিট পেমেন্ট আদায় কালেকশন'.includes(filterQuery.toLowerCase())"
               class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg border transition text-[11.5px] shadow-2xs cursor-pointer {{ request()->routeIs('reseller.collections*') ? 'border-blue-200 bg-blue-50/80 text-blue-700 font-bold border-l-4 border-l-blue-600' : 'border-slate-200/90 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-semibold' }}">
                <i class="fas fa-receipt w-4 text-center text-xs {{ request()->routeIs('reseller.collections*') ? 'text-blue-600' : 'text-slate-500' }}"></i>
                <span>{{ __('Collections') }}</span>
            </a>
        @endif

        <!-- 4. Wallet & Balance (Reseller Admin Only) -->
        @if($authUser && $authUser->isResellerAdmin())
            @php
                $isWalletActive = request()->routeIs('reseller.recharge*') || (request()->routeIs('reseller.ledger*') && !request()->routeIs('reseller.invoices*'));
            @endphp
            <div x-data="{ open: {{ $isWalletActive ? 'true' : 'false' }} }" 
                 x-show="!filterQuery || 'wallet balance recharge decharge deductions ledger statement money transactions topup ব্যালেন্স রিচার্জ খতিয়ান'.includes(filterQuery.toLowerCase())"
                 class="space-y-1">
                <button type="button" 
                        @click="open = !open" 
                        class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg border transition text-[11.5px] font-semibold shadow-2xs cursor-pointer {{ $isWalletActive ? 'border-emerald-200 bg-emerald-50/80 text-emerald-700 font-bold border-l-4 border-l-emerald-600' : 'border-slate-200/90 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300' }}">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-wallet w-4 text-center text-xs {{ $isWalletActive ? 'text-emerald-600' : 'text-slate-500' }}"></i>
                        <span>{{ __('Wallet & Balance') }}</span>
                    </div>
                    <i class="fas fa-chevron-right text-[8.5px] text-slate-400 transition-transform duration-150" :class="open ? 'rotate-90 text-slate-600' : ''"></i>
                </button>

                <!-- Sub-items -->
                <div x-show="open || filterQuery" x-collapse class="pl-2.5 pr-0.5 space-y-1">
                    <a href="{{ route('reseller.recharge.index') }}" 
                       class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('reseller.recharge*') ? 'border-emerald-300 bg-emerald-50 text-emerald-800 font-bold border-l-4 border-l-emerald-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 font-medium' }}">
                        {{ __('Balance Recharge') }}
                    </a>
                    <a href="{{ route('reseller.ledger.index', ['type' => 'DEBIT']) }}" 
                       class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('reseller.ledger*') && request()->input('type') === 'DEBIT' ? 'border-emerald-300 bg-emerald-50 text-emerald-800 font-bold border-l-4 border-l-emerald-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 font-medium' }}">
                        {{ __('Balance Deductions') }}
                    </a>
                    <a href="{{ route('reseller.ledger.index') }}" 
                       class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('reseller.ledger*') && !request()->has('type') ? 'border-emerald-300 bg-emerald-50 text-emerald-800 font-bold border-l-4 border-l-emerald-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 font-medium' }}">
                        {{ __('Transaction Ledger') }}
                    </a>
                </div>
            </div>

            <!-- 5. Packages & Rates (Reseller Admin Only) -->
            @php
                $isPkgActive = request()->routeIs('reseller.packages*') || request()->routeIs('reseller.margins*') || request()->routeIs('reseller.pricing*');
            @endphp
            <div x-data="{ open: {{ $isPkgActive ? 'true' : 'false' }} }" 
                 x-show="!filterQuery || 'packages pricing rates speeds tariff bandwidth margin commission plans profit প্যাকেজ রেট মুনাফা'.includes(filterQuery.toLowerCase())"
                 class="space-y-1">
                <button type="button" 
                        @click="open = !open" 
                        class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg border transition text-[11.5px] font-semibold shadow-2xs cursor-pointer {{ $isPkgActive ? 'border-cyan-200 bg-cyan-50/80 text-cyan-700 font-bold border-l-4 border-l-cyan-600' : 'border-slate-200/90 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300' }}">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-boxes-stacked w-4 text-center text-xs {{ $isPkgActive ? 'text-cyan-600' : 'text-slate-500' }}"></i>
                        <span>{{ __('Packages & Rates') }}</span>
                    </div>
                    <i class="fas fa-chevron-right text-[8.5px] text-slate-400 transition-transform duration-150" :class="open ? 'rotate-90 text-slate-600' : ''"></i>
                </button>

                <!-- Sub-items -->
                <div x-show="open || filterQuery" x-collapse class="pl-2.5 pr-0.5 space-y-1">
                    <a href="{{ route('reseller.packages.index') }}" 
                       class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('reseller.packages*') ? 'border-cyan-300 bg-cyan-50 text-cyan-800 font-bold border-l-4 border-l-cyan-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 font-medium' }}">
                        {{ __('Assigned Packages') }}
                    </a>
                    <a href="{{ route('reseller.margins.index') }}" 
                       class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('reseller.margins*') ? 'border-cyan-300 bg-cyan-50 text-cyan-800 font-bold border-l-4 border-l-cyan-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 font-medium' }}">
                        {{ __('Margin & Profit Rates') }}
                    </a>
                </div>
            </div>

            <!-- 6. Staff & Collectors (Reseller Admin Only) -->
            @php
                $isStaffActive = request()->routeIs('reseller.staff*');
            @endphp
            <div x-data="{ open: {{ $isStaffActive ? 'true' : 'false' }} }" 
                 x-show="!filterQuery || 'staff collectors field agents employees technicians roster team কর্মী স্টাফ'.includes(filterQuery.toLowerCase())"
                 class="space-y-1">
                <button type="button" 
                        @click="open = !open" 
                        class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg border transition text-[11.5px] font-semibold shadow-2xs cursor-pointer {{ $isStaffActive ? 'border-cyan-200 bg-cyan-50/80 text-cyan-700 font-bold border-l-4 border-l-cyan-600' : 'border-slate-200/90 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300' }}">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-users-gear w-4 text-center text-xs {{ $isStaffActive ? 'text-cyan-600' : 'text-slate-500' }}"></i>
                        <span>{{ __('Staff & Team') }}</span>
                    </div>
                    <i class="fas fa-chevron-right text-[8.5px] text-slate-400 transition-transform duration-150" :class="open ? 'rotate-90 text-slate-600' : ''"></i>
                </button>

                <!-- Sub-items -->
                <div x-show="open || filterQuery" x-collapse class="pl-2.5 pr-0.5 space-y-1">
                    <a href="{{ route('reseller.staff.index') }}" 
                       class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('reseller.staff*') ? 'border-cyan-300 bg-cyan-50 text-cyan-800 font-bold border-l-4 border-l-cyan-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 font-medium' }}">
                        {{ __('Staff Directory') }}
                    </a>
                </div>
            </div>
        @endif

        <!-- Bandwidth & Usage (Reseller Admin & Technicians) -->
        @if($authUser && ($authUser->isResellerAdmin() || $authUser->isResellerTech()))
            <a href="{{ route('reseller.bandwidth.index') }}" 
               x-show="!filterQuery || 'bandwidth capacity traffic speed usage graph telemetry mikrotik pool ব্যান্ডউইথ ট্রাফিক'.includes(filterQuery.toLowerCase())"
               class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg border transition text-[11.5px] shadow-2xs cursor-pointer {{ request()->routeIs('reseller.bandwidth*') ? 'border-purple-200 bg-purple-50/80 text-purple-700 font-bold border-l-4 border-l-purple-600' : 'border-slate-200/90 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-semibold' }}">
                <i class="fas fa-chart-line w-4 text-center text-xs {{ request()->routeIs('reseller.bandwidth*') ? 'text-purple-600' : 'text-slate-500' }}"></i>
                <span>{{ __('Bandwidth Usage') }}</span>
            </a>
        @endif

        <!-- Support & Tickets (All Roles) -->
        <a href="{{ route('reseller.tickets.index') }}" 
           x-show="!filterQuery || 'support tickets helpdesk issues trouble complaints noc টিকিট সাপোর্ট অভিযোগ'.includes(filterQuery.toLowerCase())"
           class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg border transition text-[11.5px] shadow-2xs cursor-pointer {{ request()->routeIs('reseller.tickets*') ? 'border-purple-200 bg-purple-50/80 text-purple-700 font-bold border-l-4 border-l-purple-600' : 'border-slate-200/90 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-semibold' }}">
            <i class="fas fa-headset w-4 text-center text-xs {{ request()->routeIs('reseller.tickets*') ? 'text-purple-600' : 'text-slate-500' }}"></i>
            <span>{{ __('Support & Tickets') }}</span>
        </a>

        <!-- Partner / Staff Profile (All Roles) -->
        <a href="{{ route('reseller.profile') }}" 
           x-show="!filterQuery || 'profile settings partner information account company security প্রোফাইল সেটিংস'.includes(filterQuery.toLowerCase())"
           class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg border transition text-[11.5px] shadow-2xs cursor-pointer {{ request()->routeIs('reseller.profile*') ? 'border-purple-200 bg-purple-50/80 text-purple-700 font-bold border-l-4 border-l-purple-600' : 'border-slate-200/90 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-semibold' }}">
            <i class="fas fa-id-card w-4 text-center text-xs {{ request()->routeIs('reseller.profile*') ? 'text-purple-600' : 'text-slate-500' }}"></i>
            <span>{{ __('My Profile') }}</span>
        </a>

    </div>

</aside>
