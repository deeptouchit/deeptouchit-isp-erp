@php
    $authUser = auth()->user();
@endphp

<!-- Mobile Off-Canvas Sidebar Backdrop -->
<div x-show="mobileSidebar" 
     x-transition:enter="transition-opacity ease-linear duration-150"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition-opacity ease-linear duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @click="mobileSidebar = false"
     class="fixed inset-0 z-40 bg-slate-900/40 backdrop-blur-xs lg:hidden" 
     x-cloak></div>

<!-- Sleek Compact Left Sidebar (60 Width on Desktop) -->
<aside class="fixed inset-y-0 left-0 z-50 w-60 bg-white border-r border-slate-200 flex flex-col transition-transform duration-200 ease-in-out lg:translate-x-0"
       :class="mobileSidebar ? 'translate-x-0 shadow-xl' : '-translate-x-full lg:translate-x-0'">
    
    <!-- Compact Brand Header -->
    <div class="h-14 px-4 flex items-center justify-between border-b border-slate-100 bg-white">
        <a href="{{ route('tenant.dashboard') }}" class="flex items-center gap-2.5 overflow-hidden">
            @if(isset($tenant) && $tenant && !empty($tenant->logo))
                <img src="{{ $tenant->logo }}" alt="{{ $tenant->company_name ?: $tenant->name }}" class="h-7 max-w-[120px] object-contain">
            @else
                <div class="w-7 h-7 rounded-lg bg-blue-600 flex-shrink-0 flex items-center justify-center text-white font-bold text-xs shadow-xs">
                    {{ strtoupper(substr($tenant->name ?? 'ISP', 0, 1)) }}
                </div>
            @endif
            <div class="flex flex-col overflow-hidden leading-tight">
                <span class="font-bold text-slate-900 text-xs tracking-tight truncate">{{ isset($tenant) && $tenant ? ($tenant->company_name ?: ($tenant->name ?? 'ISP Portal')) : 'ISP Portal' }}</span>
                <span class="text-[9.5px] font-semibold text-blue-600 uppercase tracking-wider">{{ $authUser && $authUser->isCollector() ? 'Bill Collector' : 'ISP Admin Console' }}</span>
            </div>
        </a>
        <button type="button" @click="mobileSidebar = false" class="lg:hidden p-1 rounded text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition cursor-pointer">
            <i class="fas fa-times text-xs"></i>
        </button>
    </div>

    <!-- Sleek Search Filter -->
    <div class="p-2.5 border-b border-slate-100 bg-slate-50/50">
        <div class="relative">
            <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-[10px]"></i>
            <input type="search" 
                   name="menu_quick_search_no_autofill"
                   autocomplete="off"
                   autocorrect="off"
                   autocapitalize="off"
                   spellcheck="false"
                   data-lpignore="true"
                   data-form-type="other"
                   readonly
                   onfocus="this.removeAttribute('readonly');"
                   x-model="filterQuery" 
                   placeholder="Filter menu..." 
                   class="w-full pl-7 pr-6 py-1.5 text-[11px] rounded-lg border border-slate-200 bg-white text-slate-700 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition placeholder:text-slate-400 shadow-2xs">
            <button type="button" 
                    x-show="filterQuery" 
                    @click="filterQuery = ''" 
                    class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 cursor-pointer"
                    style="display: none;">
                <i class="fas fa-times-circle text-[10px]"></i>
            </button>
        </div>
    </div>

    <!-- Navigation Menu -->
    <div class="flex-1 overflow-y-auto p-2 space-y-1">
        @if($authUser && $authUser->isCollector())
            <!-- 1. Main Dashboard -->
            <a href="{{ route('tenant.dashboard') }}" 
               x-show="!filterQuery || 'dashboard home overview metrics analytics ড্যাশবোর্ড'.includes(filterQuery.toLowerCase())"
               class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg border transition text-[11.5px] shadow-2xs cursor-pointer {{ request()->routeIs('tenant.dashboard*') ? 'border-purple-200 bg-purple-50/80 text-purple-700 font-bold border-l-4 border-l-purple-600' : 'border-slate-200/90 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-semibold' }}">
                <i class="fas fa-chart-pie w-4 text-center text-xs {{ request()->routeIs('tenant.dashboard*') ? 'text-purple-600' : 'text-slate-500' }}"></i>
                <span>{{ __('Dashboard') }}</span>
            </a>

            <!-- 2. Customer Management (Collapsible) -->
            @php
                $isCustActive = request()->routeIs('tenant.customers*');
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
                    <a href="{{ route('tenant.customers.index') }}" 
                       class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('tenant.customers*') && !request()->has('status') && !request()->routeIs('tenant.customers.due') && !request()->routeIs('tenant.customers.direct') ? 'border-cyan-300 bg-cyan-50 text-cyan-800 font-bold border-l-4 border-l-cyan-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 font-medium' }}">
                        {{ __('All Customers') }}
                    </a>
                    <a href="{{ route('tenant.customers.index', ['status' => 'due']) }}" 
                       class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->input('status') === 'due' || request()->routeIs('tenant.customers.due') ? 'border-amber-300 bg-amber-50 text-amber-800 font-bold border-l-4 border-l-amber-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 font-medium' }}">
                        <span class="flex items-center justify-between">
                            <span>{{ __('Due / Unpaid') }}</span>
                            <span class="px-1.5 py-0.2 rounded text-[9px] bg-amber-100 text-amber-800 font-bold">Collect</span>
                        </span>
                    </a>
                    <a href="{{ route('tenant.customers.index', ['status' => 'active']) }}" 
                       class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->input('status') === 'active' || request()->routeIs('tenant.customers.direct') ? 'border-cyan-300 bg-cyan-50 text-cyan-800 font-bold border-l-4 border-l-cyan-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 font-medium' }}">
                        {{ __('Active Subscribers') }}
                    </a>
                </div>
            </div>

            <!-- 3. Direct Collections Link for Collector -->
            <a href="{{ route('tenant.finance.payments') }}" 
               x-show="!filterQuery || 'collections payments receipts money রিসিট পেমেন্ট আদায় কালেকশন'.includes(filterQuery.toLowerCase())"
               class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg border transition text-[11.5px] shadow-2xs cursor-pointer {{ request()->routeIs('tenant.finance.payments*') ? 'border-blue-200 bg-blue-50/80 text-blue-700 font-bold border-l-4 border-l-blue-600' : 'border-slate-200/90 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-semibold' }}">
                <i class="fas fa-receipt w-4 text-center text-xs {{ request()->routeIs('tenant.finance.payments*') ? 'text-blue-600' : 'text-slate-500' }}"></i>
                <span>{{ __('Collections') }}</span>
            </a>

            <!-- 4. Support & Tickets -->
            <a href="{{ route('tenant.tickets.index') }}" 
               x-show="!filterQuery || 'support tickets helpdesk issues trouble complaints noc টিকিট সাপোর্ট অভিযোগ'.includes(filterQuery.toLowerCase())"
               class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg border transition text-[11.5px] shadow-2xs cursor-pointer {{ request()->routeIs('tenant.tickets*') ? 'border-purple-200 bg-purple-50/80 text-purple-700 font-bold border-l-4 border-l-purple-600' : 'border-slate-200/90 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-semibold' }}">
                <i class="fas fa-headset w-4 text-center text-xs {{ request()->routeIs('tenant.tickets*') ? 'text-purple-600' : 'text-slate-500' }}"></i>
                <span>{{ __('Support & Tickets') }}</span>
            </a>

            <!-- 5. My Profile -->
            <a href="{{ route('tenant.profile') }}" 
               x-show="!filterQuery || 'profile settings partner information account company security প্রোফাইল সেটিংস'.includes(filterQuery.toLowerCase())"
               class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg border transition text-[11.5px] shadow-2xs cursor-pointer {{ request()->routeIs('tenant.profile*') ? 'border-purple-200 bg-purple-50/80 text-purple-700 font-bold border-l-4 border-l-purple-600' : 'border-slate-200/90 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-semibold' }}">
                <i class="fas fa-id-card w-4 text-center text-xs {{ request()->routeIs('tenant.profile*') ? 'text-purple-600' : 'text-slate-500' }}"></i>
                <span>{{ __('My Profile') }}</span>
            </a>
        @else
            <!-- 1. Standalone Top Main Menu: Overview Dashboard -->
            <a href="{{ route('tenant.dashboard') }}" 
               x-show="!filterQuery || 'dashboard overview stats home analytics admin'.includes(filterQuery.toLowerCase())"
               class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg border transition text-[11.5px] font-semibold shadow-2xs {{ request()->routeIs('tenant.dashboard*') ? 'border-blue-200 bg-blue-50/80 text-blue-600 font-bold border-l-4 border-l-blue-600' : 'border-slate-200/90 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300' }}">
                <i class="fas fa-house-chimney w-4 text-center text-xs {{ request()->routeIs('tenant.dashboard*') ? 'text-blue-600' : 'text-slate-500' }}"></i>
                <span>Dashboard</span>
            </a>

        <!-- 2. Main Menu: Subscription & Billing (Collapsible Sub-menu) -->
        @if(auth()->user() && (auth()->user()->isOwner() || auth()->user()->isIspAdmin() || auth()->user()->hasRole('owner', 'isp_admin', 'isp_manager')))
        @php
            $isBillingActive = request()->routeIs('tenant.billing*');
        @endphp
        <div x-data="{ open: {{ $isBillingActive ? 'true' : 'false' }} }" 
             x-show="!filterQuery || 'billing subscription invoices payments packages renewal license plan overview my subscription'.includes(filterQuery.toLowerCase())"
             class="space-y-1">
            <button type="button" 
                    @click="open = !open" 
                    class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg border transition text-[11.5px] font-semibold shadow-2xs cursor-pointer {{ $isBillingActive ? 'border-blue-200 bg-blue-50/80 text-blue-600 font-bold border-l-4 border-l-blue-600' : 'border-slate-200/90 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300' }}">
                <div class="flex items-center gap-2">
                    <i class="fas fa-file-invoice-dollar w-4 text-center text-xs {{ $isBillingActive ? 'text-blue-600' : 'text-slate-500' }}"></i>
                    <span>My Subscription</span>
                </div>
                <i class="fas fa-chevron-right text-[8.5px] text-slate-400 transition-transform duration-150" :class="open ? 'rotate-90 text-slate-600' : ''"></i>
            </button>

            <!-- Sub-menu Items -->
            <div x-show="open || filterQuery" x-collapse class="pl-2.5 pr-0.5 space-y-1">
                <a href="{{ route('tenant.billing.dashboard') }}" 
                   x-show="!filterQuery || 'subscription status plan details expiry renewal overview license'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('tenant.billing.dashboard') ? 'border-blue-200 bg-blue-50/80 text-blue-600 font-bold border-l-4 border-l-blue-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Plan Overview
                </a>

                <a href="{{ route('tenant.billing.invoices') }}" 
                   x-show="!filterQuery || 'invoices bills receipts dues subscription bill software'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('tenant.billing.invoices*') || request()->routeIs('tenant.billing.invoice.show*') ? 'border-blue-200 bg-blue-50/80 text-blue-600 font-bold border-l-4 border-l-blue-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Invoices
                </a>

                <a href="{{ route('tenant.billing.payments') }}" 
                   x-show="!filterQuery || 'payment history settlements transaction trx bkash nagad receipts'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('tenant.billing.payments*') ? 'border-blue-200 bg-blue-50/80 text-blue-600 font-bold border-l-4 border-l-blue-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Payment History
                </a>
            </div>
        </div>
        @endif

        <!-- 3. Main Menu: 📡 Upstream Bandwidth (Directly Below My Subscription) -->
        @if(auth()->user() && (auth()->user()->isOwner() || auth()->user()->isIspAdmin() || auth()->user()->hasRole('owner', 'isp_admin', 'isp_manager')))
        @php
            $isUpstreamActive = request()->routeIs('tenant.upstream*');
        @endphp
        <div x-data="{ open: {{ $isUpstreamActive ? 'true' : 'false' }} }" 
             x-show="!filterQuery || 'upstream bandwidth carrier iig itc nttn bdix capacity bills payment vouchers transmission'.includes(filterQuery.toLowerCase())"
             class="space-y-1">
            <button type="button" 
                    @click="open = !open" 
                    class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg border transition text-[11.5px] font-semibold shadow-2xs cursor-pointer {{ $isUpstreamActive ? 'border-blue-200 bg-blue-50/80 text-blue-600 font-bold border-l-4 border-l-blue-600' : 'border-slate-200/90 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300' }}">
                <div class="flex items-center gap-2">
                    <i class="fas fa-tower-broadcast w-4 text-center text-xs {{ $isUpstreamActive ? 'text-blue-600' : 'text-slate-500' }}"></i>
                    <span>Upstream Bandwidth</span>
                </div>
                <i class="fas fa-chevron-right text-[8.5px] text-slate-400 transition-transform duration-150" :class="open ? 'rotate-90 text-slate-600' : ''"></i>
            </button>

            <!-- Sub-menu Items -->
            <div x-show="open || filterQuery" x-collapse class="pl-2.5 pr-0.5 space-y-1">
                <a href="{{ route('tenant.upstream.index') }}" 
                   x-show="!filterQuery || 'upstream carriers links capacity iig itc nttn bdix cdn accounting'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('tenant.upstream.index') ? 'border-blue-200 bg-blue-50/80 text-blue-600 font-bold border-l-4 border-l-blue-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Carriers &amp; Links
                </a>

                <a href="{{ route('tenant.upstream.invoices') }}" 
                   x-show="!filterQuery || 'carrier invoices bills monthly dues transmission nttn'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('tenant.upstream.invoices*') ? 'border-blue-200 bg-blue-50/80 text-blue-600 font-bold border-l-4 border-l-blue-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Carrier Bills
                </a>

                <a href="{{ route('tenant.upstream.payments') }}" 
                   x-show="!filterQuery || 'payment vouchers disbursements settlements bank transfer cheque rtgs receipts'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('tenant.upstream.payments*') || request()->routeIs('tenant.upstream.payment.voucher*') ? 'border-blue-200 bg-blue-50/80 text-blue-600 font-bold border-l-4 border-l-blue-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Payment Vouchers
                </a>
            </div>
        </div>
        @endif

        <!-- 4. Main Menu: 🌐 Network (Collapsible Sub-menu) -->
        @if(auth()->user() && (auth()->user()->isOwner() || auth()->user()->isIspAdmin() || auth()->user()->hasPermission('network.routers') || auth()->user()->hasPermission('network.radius') || auth()->user()->hasPermission('network.packages') || auth()->user()->hasPermission('network.olts_onus') || auth()->user()->hasPermission('network.ip_pools')))
        @php
            $isNetworkActive = request()->routeIs('tenant.network*');
        @endphp
        <div x-data="{ open: {{ $isNetworkActive ? 'true' : 'false' }} }" 
             x-show="!filterQuery || 'network mikrotik olt onu ont ip pools vlans routing map'.includes(filterQuery.toLowerCase())"
             class="space-y-1">
            <button type="button" 
                    @click="open = !open" 
                    class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg border transition text-[11.5px] font-semibold shadow-2xs cursor-pointer {{ $isNetworkActive ? 'border-blue-200 bg-blue-50/80 text-blue-600 font-bold border-l-4 border-l-blue-600' : 'border-slate-200/90 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300' }}">
                <div class="flex items-center gap-2">
                    <i class="fas fa-network-wired w-4 text-center text-xs {{ $isNetworkActive ? 'text-blue-600' : 'text-slate-500' }}"></i>
                    <span>Network &amp; Infra</span>
                </div>
                <i class="fas fa-chevron-right text-[8.5px] text-slate-400 transition-transform duration-150" :class="open ? 'rotate-90 text-slate-600' : ''"></i>
            </button>

            <!-- Sub-menu Items -->
            <div x-show="open || filterQuery" x-collapse class="pl-2.5 pr-0.5 space-y-1">
                <!-- 1. MikroTik Routers -->
                <a href="{{ route('tenant.network.mikrotik') }}" 
                   x-show="!filterQuery || 'mikrotik routers gateway ros rb pppoe queues'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('tenant.network.mikrotik*') ? 'border-blue-200 bg-blue-50/80 text-blue-600 font-bold border-l-4 border-l-blue-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    MikroTik Routers
                </a>

                <!-- 2. NAS & RADIUS -->
                <a href="{{ route('tenant.network.nas') }}" 
                   x-show="!filterQuery || 'nas network access servers radius aaa coa bras gateway'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('tenant.network.nas*') ? 'border-blue-200 bg-blue-50/80 text-blue-600 font-bold border-l-4 border-l-blue-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    NAS &amp; RADIUS
                </a>

                <!-- 3. Internet Packages -->
                <a href="{{ route('tenant.network.packages') }}" 
                   x-show="!filterQuery || 'packages internet plans bandwidth pppoe speed rate price'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('tenant.network.packages*') ? 'border-blue-200 bg-blue-50/80 text-blue-600 font-bold border-l-4 border-l-blue-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Internet Packages
                </a>

                <!-- 4. OLT Devices -->
                <a href="{{ route('tenant.network.olt') }}" 
                   x-show="!filterQuery || 'olt devices optical gpon epon bdcom zte huawei'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('tenant.network.olt*') ? 'border-blue-200 bg-blue-50/80 text-blue-600 font-bold border-l-4 border-l-blue-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    OLT Devices
                </a>

                <!-- 4b. OLT License Governance -->
                <a href="{{ route('tenant.network.license') }}" 
                   x-show="!filterQuery || 'license olt time lock duration unlimited unlock key'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('tenant.network.license*') ? 'border-blue-200 bg-blue-50/80 text-blue-600 font-bold border-l-4 border-l-blue-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    OLT Licenses
                </a>

                <!-- 5. ONU / ONT -->
                <a href="{{ route('tenant.network.onu') }}" 
                   x-show="!filterQuery || 'onu ont terminals signal optical dbm client'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('tenant.network.onu*') ? 'border-blue-200 bg-blue-50/80 text-blue-600 font-bold border-l-4 border-l-blue-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    ONU / ONT
                </a>

                <!-- 6. IP Pools -->
                <a href="{{ route('tenant.network.ip-pools') }}" 
                   x-show="!filterQuery || 'ip pools subnets ipv4 ipv6 cgnat dhcp'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('tenant.network.ip-pools*') ? 'border-blue-200 bg-blue-50/80 text-blue-600 font-bold border-l-4 border-l-blue-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    IP Pools
                </a>

                <!-- 7. VLANs -->
                <a href="{{ route('tenant.network.vlans') }}" 
                   x-show="!filterQuery || 'vlans 802.1q trunk access qinq broadcast'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('tenant.network.vlans*') ? 'border-blue-200 bg-blue-50/80 text-blue-600 font-bold border-l-4 border-l-blue-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    VLANs
                </a>

                <!-- 8. Routing & BGP -->
                <a href="{{ route('tenant.network.routing') }}" 
                   x-show="!filterQuery || 'networks routing bgp ospf static gateway transit'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('tenant.network.routing*') ? 'border-blue-200 bg-blue-50/80 text-blue-600 font-bold border-l-4 border-l-blue-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Routing &amp; BGP
                </a>

                <!-- 9. Network Map -->
                <a href="{{ route('tenant.network.map') }}" 
                   x-show="!filterQuery || 'network map topology gis fiber pop location'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('tenant.network.map*') ? 'border-blue-200 bg-blue-50/80 text-blue-600 font-bold border-l-4 border-l-blue-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Network Map
                </a>
            </div>
        </div>
        @endif

        <!-- 3. Main Menu: 🤝 Reseller Network (Collapsible Sub-menu) -->
        <!-- 3a. Main Menu: 🤝 Reseller Partners (Collapsible Sub-menu) -->
        @if(auth()->user() && (auth()->user()->isOwner() || auth()->user()->isIspAdmin() || auth()->user()->hasPermission('resellers.view')))
        @php
            $isPartnersActive = request()->routeIs('tenant.resellers.index*') || 
                                request()->routeIs('tenant.resellers.margins*') || 
                                request()->routeIs('tenant.resellers.subscriptions*');
        @endphp
        <div x-data="{ open: {{ $isPartnersActive ? 'true' : 'false' }} }" 
             x-show="!filterQuery || 'reseller partners directory sub-isps franchise add packages margins subscriptions licenses'.includes(filterQuery.toLowerCase())"
             class="space-y-1">
            <button type="button" 
                    @click="open = !open" 
                    class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg border transition text-[11.5px] font-semibold shadow-2xs cursor-pointer {{ $isPartnersActive ? 'border-cyan-200 bg-cyan-50/80 text-cyan-700 font-bold border-l-4 border-l-cyan-600' : 'border-slate-200/90 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300' }}">
                <div class="flex items-center gap-2">
                    <i class="fas fa-handshake w-4 text-center text-xs {{ $isPartnersActive ? 'text-cyan-600' : 'text-slate-500' }}"></i>
                    <span>Reseller Partners</span>
                </div>
                <i class="fas fa-chevron-right text-[8.5px] text-slate-400 transition-transform duration-150" :class="open ? 'rotate-90 text-slate-600' : ''"></i>
            </button>

            <!-- Sub-menu Items -->
            <div x-show="open || filterQuery" x-collapse class="pl-2.5 pr-0.5 space-y-1">
                <!-- 1. Directory -->
                <a href="{{ Route::has('tenant.resellers.index') ? route('tenant.resellers.index') : '#' }}" 
                   x-show="!filterQuery || 'reseller directory list profiles sub-isps franchise add retail'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('tenant.resellers.index*') ? 'border-cyan-200 bg-cyan-50/80 text-cyan-700 font-bold border-l-4 border-l-cyan-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Partner Directory
                </a>

                <!-- 2. Panel Licenses -->
                <a href="{{ Route::has('tenant.resellers.subscriptions') ? route('tenant.resellers.subscriptions') : '#' }}" 
                   x-show="!filterQuery || 'panel licenses software renewal status expiry reseller'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('tenant.resellers.subscriptions*') ? 'border-cyan-200 bg-cyan-50/80 text-cyan-700 font-bold border-l-4 border-l-cyan-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Panel Licenses
                </a>
            </div>
        </div>

        <!-- 3b. Main Menu: 🌐 Wholesale Bandwidth (Collapsible Sub-menu) -->
        @php
            $isWholesaleActive = request()->routeIs('tenant.resellers.bandwidth*') || 
                                 request()->routeIs('tenant.resellers.bandwidth-plans*') || 
                                 request()->routeIs('tenant.resellers.invoices*') || 
                                 request()->routeIs('tenant.resellers.capacity*');
        @endphp
        <div x-data="{ open: {{ $isWholesaleActive ? 'true' : 'false' }} }" 
             x-show="!filterQuery || 'wholesale bandwidth allocation rate plans tariff pricing capacity gateway mrtg traffic router core invoices bills payments collection'.includes(filterQuery.toLowerCase())"
             class="space-y-1">
            <button type="button" 
                    @click="open = !open" 
                    class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg border transition text-[11.5px] font-semibold shadow-2xs cursor-pointer {{ $isWholesaleActive ? 'border-cyan-200 bg-cyan-50/80 text-cyan-700 font-bold border-l-4 border-l-cyan-600' : 'border-slate-200/90 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300' }}">
                <div class="flex items-center gap-2">
                    <i class="fas fa-network-wired w-4 text-center text-xs {{ $isWholesaleActive ? 'text-cyan-600' : 'text-slate-500' }}"></i>
                    <span>Wholesale Bandwidth</span>
                </div>
                <i class="fas fa-chevron-right text-[8.5px] text-slate-400 transition-transform duration-150" :class="open ? 'rotate-90 text-slate-600' : ''"></i>
            </button>

            <!-- Sub-menu Items (Wholesale Bandwidth Ecosystem) -->
            <div x-show="open || filterQuery" x-collapse class="pl-2.5 pr-0.5 space-y-1">
                <!-- 1. Bandwidth Rate Plans -->
                <a href="{{ Route::has('tenant.resellers.bandwidth-plans') ? route('tenant.resellers.bandwidth-plans') : '#' }}" 
                   x-show="!filterQuery || 'bandwidth rate plans tariff pricing per mbps global bdix cdn wholesale packages'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('tenant.resellers.bandwidth-plans*') ? 'border-cyan-200 bg-cyan-50/80 text-cyan-700 font-bold border-l-4 border-l-cyan-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Bandwidth Rate Plans
                </a>

                <!-- 2. Bandwidth Allocation -->
                <a href="{{ Route::has('tenant.resellers.bandwidth') ? route('tenant.resellers.bandwidth') : '#' }}" 
                   x-show="!filterQuery || 'bandwidth allocation mbps wholesale mrtg traffic usage profiles cir mir'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('tenant.resellers.bandwidth') ? 'border-cyan-200 bg-cyan-50/80 text-cyan-700 font-bold border-l-4 border-l-cyan-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Bandwidth Allocation
                </a>

                <!-- 3. Wholesale Invoices & Payments -->
                <a href="{{ Route::has('tenant.resellers.invoices') ? route('tenant.resellers.invoices') : '#' }}" 
                   x-show="!filterQuery || 'wholesale invoices billing receipts money receipts bills payments dues collection'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('tenant.resellers.invoices*') ? 'border-cyan-200 bg-cyan-50/80 text-cyan-700 font-bold border-l-4 border-l-cyan-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Invoices &amp; Payments
                </a>

                <!-- 4. Gateway Capacity -->
                <a href="{{ Route::has('tenant.resellers.capacity') ? route('tenant.resellers.capacity') : '#' }}" 
                   x-show="!filterQuery || 'gateway capacity router distribution load wholesale bandwidth throughput'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('tenant.resellers.capacity*') ? 'border-cyan-200 bg-cyan-50/80 text-cyan-700 font-bold border-l-4 border-l-cyan-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Gateway Capacity
                </a>
            </div>
        </div>

        <!-- 3c. Main Menu: 💳 Reseller Accounts & Billing (Collapsible Sub-menu) -->
        @php
            $isBillingActive = request()->routeIs('tenant.resellers.recharge*') || 
                               request()->routeIs('tenant.resellers.wallets*') || 
                               request()->routeIs('tenant.resellers.ledger*');
        @endphp
        <div x-data="{ open: {{ $isBillingActive ? 'true' : 'false' }} }" 
             x-show="!filterQuery || 'reseller accounts billing recharge wallets credit adjustment ledger vouchers'.includes(filterQuery.toLowerCase())"
             class="space-y-1">
            <button type="button" 
                    @click="open = !open" 
                    class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg border transition text-[11.5px] font-semibold shadow-2xs cursor-pointer {{ $isBillingActive ? 'border-cyan-200 bg-cyan-50/80 text-cyan-700 font-bold border-l-4 border-l-cyan-600' : 'border-slate-200/90 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300' }}">
                <div class="flex items-center gap-2">
                    <i class="fas fa-wallet w-4 text-center text-xs {{ $isBillingActive ? 'text-cyan-600' : 'text-slate-500' }}"></i>
                    <span>Reseller Accounts</span>
                </div>
                <i class="fas fa-chevron-right text-[8.5px] text-slate-400 transition-transform duration-150" :class="open ? 'rotate-90 text-slate-600' : ''"></i>
            </button>

            <!-- Sub-menu Items -->
            <div x-show="open || filterQuery" x-collapse class="pl-2.5 pr-0.5 space-y-1">
                <!-- 1. Wallets & Credit -->
                <a href="{{ Route::has('tenant.resellers.wallets') ? route('tenant.resellers.wallets') : '#' }}" 
                   x-show="!filterQuery || 'wallets balances credit limit topup balance reseller'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('tenant.resellers.wallets*') ? 'border-cyan-200 bg-cyan-50/80 text-cyan-700 font-bold border-l-4 border-l-cyan-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Wallets &amp; Credit
                </a>

                <!-- 2. Recharge History -->
                <a href="{{ Route::has('tenant.resellers.recharge') ? route('tenant.resellers.recharge') : '#' }}" 
                   x-show="!filterQuery || 'recharge history online offline bank cash deposit gateway bkash nagad approval'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('tenant.resellers.recharge*') ? 'border-cyan-200 bg-cyan-50/80 text-cyan-700 font-bold border-l-4 border-l-cyan-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Recharge History
                </a>

                <!-- 3. Adjustment Ledger -->
                <a href="{{ Route::has('tenant.resellers.ledger') ? route('tenant.resellers.ledger') : '#' }}" 
                   x-show="!filterQuery || 'ledger audit logs transactions voucher recharge adjustment credit debit reseller'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('tenant.resellers.ledger*') ? 'border-cyan-200 bg-cyan-50/80 text-cyan-700 font-bold border-l-4 border-l-cyan-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Adjustment Ledger
                </a>
            </div>
        </div>
        @endif

        <!-- 4. Main Menu: Staff Management (HRM & Field Roster) -->
        @if(auth()->user() && (auth()->user()->isOwner() || auth()->user()->isIspAdmin() || auth()->user()->hasPermission('settings.rbac') || auth()->user()->hasRole('isp_admin', 'isp_manager')))
        @php
            $isStaffActive = request()->routeIs('tenant.staff*') || request()->routeIs('tenant.hrm*');
        @endphp
        <div x-data="{ open: {{ $isStaffActive ? 'true' : 'false' }} }" 
             x-show="!filterQuery || 'human resources staff hrm employees managers technicians collectors roster attendance commission'.includes(filterQuery.toLowerCase())"
             class="space-y-1">
            <button type="button" 
                    @click="open = !open" 
                    class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg border transition text-[11.5px] font-semibold shadow-2xs cursor-pointer {{ $isStaffActive ? 'border-indigo-200 bg-indigo-50/80 text-indigo-700 font-bold border-l-4 border-l-indigo-600' : 'border-slate-200/90 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300' }}">
                <div class="flex items-center gap-2">
                    <i class="fas fa-user-tie w-4 text-center text-xs {{ $isStaffActive ? 'text-indigo-600' : 'text-slate-500' }}"></i>
                    <span>Staff &amp; HRM</span>
                </div>
                <i class="fas fa-chevron-right text-[8.5px] text-slate-400 transition-transform duration-150" :class="open ? 'rotate-90 text-slate-600' : ''"></i>
            </button>

            <!-- Sub-menu Items -->
            <div x-show="open || filterQuery" x-collapse class="pl-2.5 pr-0.5 space-y-1">
                <!-- 1. Staff Directory -->
                <a href="{{ Route::has('tenant.staff.index') ? route('tenant.staff.index') : '#' }}" 
                   x-show="!filterQuery || 'all employees directory unified list staff users hrm profile'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('tenant.staff.index') ? 'border-indigo-200 bg-indigo-50/80 text-indigo-700 font-bold border-l-4 border-l-indigo-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Staff Directory
                </a>

                <!-- 2. Operations Staff -->
                <a href="{{ Route::has('tenant.staff.isp') ? route('tenant.staff.isp') : '#' }}" 
                   x-show="!filterQuery || 'isp operations staff managers noc engineers technicians collectors core'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('tenant.staff.isp*') ? 'border-indigo-200 bg-indigo-50/80 text-indigo-700 font-bold border-l-4 border-l-indigo-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Operations Staff
                </a>

                <!-- 3. Reseller Staff -->
                <a href="{{ Route::has('tenant.staff.resellers') ? route('tenant.staff.resellers') : '#' }}" 
                   x-show="!filterQuery || 'reseller staff roster sub-isp managers technicians collectors partner team'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('tenant.staff.resellers*') ? 'border-indigo-200 bg-indigo-50/80 text-indigo-700 font-bold border-l-4 border-l-indigo-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Reseller Staff
                </a>

                <!-- 4. Attendance -->
                <a href="{{ Route::has('tenant.staff.attendance') ? route('tenant.staff.attendance') : '#' }}" 
                   x-show="!filterQuery || 'staff attendance daily register punch present absent late leave shift'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('tenant.staff.attendance*') ? 'border-indigo-200 bg-indigo-50/80 text-indigo-700 font-bold border-l-4 border-l-indigo-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Attendance
                </a>

                <!-- 5. Activity Logs -->
                <a href="{{ Route::has('tenant.staff.logs') ? route('tenant.staff.logs') : '#' }}" 
                   x-show="!filterQuery || 'activity logs audit trail system security events telemetry history'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('tenant.staff.logs*') ? 'border-indigo-200 bg-indigo-50/80 text-indigo-700 font-bold border-l-4 border-l-indigo-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Activity Logs
                </a>

                <!-- 6. Targets & Commission -->
                <a href="{{ Route::has('tenant.staff.commissions') ? route('tenant.staff.commissions') : '#' }}" 
                   x-show="!filterQuery || 'staff target commission collector targets sales performance logs incentive'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('tenant.staff.commissions*') ? 'border-indigo-200 bg-indigo-50/80 text-indigo-700 font-bold border-l-4 border-l-indigo-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Targets &amp; Commission
                </a>
            </div>
        </div>
        @endif

        <!-- 5. Main Menu: Customer Management (CRM & Subscribers) -->
        @if(auth()->user() && (auth()->user()->isOwner() || auth()->user()->isIspAdmin() || auth()->user()->isCollector() || auth()->user()->hasPermission('customers.view')))
        @php
            $isCustomersActive = request()->routeIs('tenant.customers*');
        @endphp
        <div x-data="{ open: {{ $isCustomersActive ? 'true' : 'false' }} }" 
             x-show="!filterQuery || 'customers crm subscribers retail users pppoe online sessions expired due zones areas disconnected master database onboard new create add'.includes(filterQuery.toLowerCase())"
             class="space-y-1">
            <button type="button" 
                    @click="open = !open" 
                    class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg border transition text-[11.5px] font-semibold shadow-2xs cursor-pointer {{ $isCustomersActive ? 'border-cyan-200 bg-cyan-50/80 text-cyan-700 font-bold border-l-4 border-l-cyan-600' : 'border-slate-200/90 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300' }}">
                <div class="flex items-center gap-2">
                    <i class="fas fa-users-line w-4 text-center text-xs {{ $isCustomersActive ? 'text-cyan-600' : 'text-slate-500' }}"></i>
                    <span>Customers &amp; CRM</span>
                </div>
                <i class="fas fa-chevron-right text-[8.5px] text-slate-400 transition-transform duration-150" :class="open ? 'rotate-90 text-slate-600' : ''"></i>
            </button>

            <!-- Sub-menu Items -->
            <div x-show="open || filterQuery" x-collapse class="pl-2.5 pr-0.5 space-y-1">
                @if(!auth()->user()->isCollector() || auth()->user()->isIspAdmin() || auth()->user()->isOwner())
                <!-- 1. Coverage Zones -->
                <a href="{{ Route::has('tenant.customers.zones') ? route('tenant.customers.zones') : '#' }}" 
                   x-show="!filterQuery || 'customer zones areas division district thana pop zone area mapping coverage location territory'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('tenant.customers.zones*') ? 'border-cyan-200 bg-cyan-50/80 text-cyan-700 font-bold border-l-4 border-l-cyan-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Coverage Zones
                </a>
                @endif

                <!-- 2. All Customers -->
                <a href="{{ Route::has('tenant.customers.index') ? route('tenant.customers.index') : '#' }}" 
                   x-show="!filterQuery || 'all customers master database direct reseller subscribers list clients onboard create add'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ (request()->routeIs('tenant.customers.index') || request()->routeIs('tenant.customers.create') || request()->routeIs('tenant.customers.edit') || request()->routeIs('tenant.customers.show')) && !request()->has('status') && !request()->has('scope') && !request()->has('online_status') ? 'border-cyan-200 bg-cyan-50/80 text-cyan-700 font-bold border-l-4 border-l-cyan-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    All Customers
                </a>

                <!-- 3. Direct Customers -->
                <a href="{{ Route::has('tenant.customers.direct') ? route('tenant.customers.direct') : '#' }}" 
                   x-show="!filterQuery || 'isp direct customers retail clients subscribers direct billing company own'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('tenant.customers.direct*') ? 'border-cyan-200 bg-cyan-50/80 text-cyan-700 font-bold border-l-4 border-l-cyan-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Direct Customers
                </a>

                <!-- 4. Due & Expired -->
                <a href="{{ Route::has('tenant.customers.due') ? route('tenant.customers.due') : '#' }}" 
                   x-show="!filterQuery || 'due expired overdue grace period billing suspended unpaid customers'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('tenant.customers.due*') ? 'border-cyan-200 bg-cyan-50/80 text-cyan-700 font-bold border-l-4 border-l-cyan-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    <span class="flex items-center justify-between">
                        <span>Due &amp; Expired</span>
                        <span class="px-1.5 py-0.2 rounded text-[9px] bg-amber-100 text-amber-800 font-bold">Collect</span>
                    </span>
                </a>

                @if(!auth()->user()->isCollector() || auth()->user()->isIspAdmin() || auth()->user()->isOwner())
                <!-- 5. Bulk Payments -->
                <a href="{{ Route::has('tenant.customers.bulk-payments') ? route('tenant.customers.bulk-payments') : '#' }}" 
                   x-show="!filterQuery || 'bulk payments batch bill receive pay reseller commission dues'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('tenant.customers.bulk-payments*') ? 'border-cyan-200 bg-cyan-50/80 text-cyan-700 font-bold border-l-4 border-l-cyan-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Bulk Payments
                </a>
                @endif

                <!-- 6. Online Sessions -->
                <a href="{{ Route::has('tenant.customers.online') ? route('tenant.customers.online') : '#' }}" 
                   x-show="!filterQuery || 'online pppoe live active sessions uptime ip allocation nas radius'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('tenant.customers.online*') ? 'border-cyan-200 bg-cyan-50/80 text-cyan-700 font-bold border-l-4 border-l-cyan-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Online Sessions
                </a>

                <!-- 7. Disconnected -->
                <a href="{{ Route::has('tenant.customers.disconnected') ? route('tenant.customers.disconnected') : (Route::has('tenant.customers.archived') ? route('tenant.customers.archived') : '#') }}" 
                   x-show="!filterQuery || 'disconnected churned users left equipment return onu return status inactive'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ (request()->routeIs('tenant.customers.disconnected*') || request()->routeIs('tenant.customers.archived*')) ? 'border-cyan-200 bg-cyan-50/80 text-cyan-700 font-bold border-l-4 border-l-cyan-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Disconnected
                </a>
            </div>
        </div>
        @endif

        <!-- 6. Main Menu: Billing, Collection & Accounts (ERP & Finance) -->
        @if(auth()->user() && (auth()->user()->isOwner() || auth()->user()->isIspAdmin() || auth()->user()->isCollector() || auth()->user()->hasRole('isp_collector', 'collector') || auth()->user()->hasPermission('billing.invoices') || auth()->user()->hasPermission('billing.collect') || auth()->user()->hasPermission('billing.cash_handover') || auth()->user()->hasPermission('billing.wholesale') || auth()->user()->hasPermission('billing.gateways') || auth()->user()->hasPermission('billing.expenses') || auth()->user()->hasPermission('billing.ledger')))
        @php
            $isFinanceActive = request()->routeIs('tenant.finance*') || request()->routeIs('tenant.collector*') || request()->routeIs('tenant.accounts*') || request()->routeIs('tenant.erp*');
        @endphp
        <div x-data="{ open: {{ $isFinanceActive ? 'true' : 'false' }} }" 
             x-show="!filterQuery || 'billing collection accounts erp finance invoices bills receipts payments cash handover closing collector hub due wholesale gateway bkash nagad rocket sslcommerz income expense expenses ledger trial balance profit loss statement'.includes(filterQuery.toLowerCase())"
             class="space-y-1">
            <button type="button" 
                    @click="open = !open" 
                    class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg border transition text-[11.5px] font-semibold shadow-2xs cursor-pointer {{ $isFinanceActive ? 'border-amber-200 bg-amber-50/80 text-amber-700 font-bold border-l-4 border-l-amber-600' : 'border-slate-200/90 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300' }}">
                <div class="flex items-center gap-2">
                    <i class="fas fa-coins w-4 text-center text-xs {{ $isFinanceActive ? 'text-amber-600' : 'text-slate-500' }}"></i>
                    <span>Billing &amp; Payment</span>
                </div>
                <i class="fas fa-chevron-right text-[8.5px] text-slate-400 transition-transform duration-150" :class="open ? 'rotate-90 text-slate-600' : ''"></i>
            </button>

            <!-- Sub-menu Items -->
            <div x-show="open || filterQuery" x-collapse class="pl-2.5 pr-0.5 space-y-1">
                <!-- 1. Collector Hub -->
                <a href="{{ Route::has('tenant.collector.due-customers') ? route('tenant.collector.due-customers') : route('tenant.dashboard') }}" 
                   x-show="!filterQuery || 'collector hub field collection counter bill payments due'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('tenant.collector.due-customers') ? 'border-amber-200 bg-amber-50/80 text-amber-700 font-bold border-l-4 border-l-amber-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Collector Due List
                </a>

                @if(!auth()->user()->isCollector() || auth()->user()->isIspAdmin() || auth()->user()->isOwner())
                <!-- 2. Invoices -->
                <a href="{{ Route::has('tenant.finance.invoices') ? route('tenant.finance.invoices') : '#' }}" 
                   x-show="!filterQuery || 'customer invoices bills monthly automated invoices custom due dates billing'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('tenant.finance.invoices*') ? 'border-amber-200 bg-amber-50/80 text-amber-700 font-bold border-l-4 border-l-amber-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Invoices
                </a>
                @endif

                <!-- 3. Collections -->
                <a href="{{ Route::has('tenant.finance.payments') ? route('tenant.finance.payments') : '#' }}" 
                   x-show="!filterQuery || 'payment collections receipts instant money receipt sms notification pos collection bill receive'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('tenant.finance.payments*') ? 'border-amber-200 bg-amber-50/80 text-amber-700 font-bold border-l-4 border-l-amber-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Collections
                </a>

                <!-- 4. Cash Handover -->
                <a href="{{ Route::has('tenant.collector.handover') ? route('tenant.collector.handover') : (Route::has('tenant.finance.cash-handover') ? route('tenant.finance.cash-handover') : '#') }}" 
                   x-show="!filterQuery || 'daily cash handover closing collector manager verification cash approval vault'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('tenant.finance.cash-handover*') || request()->routeIs('tenant.collector.handover*') ? 'border-amber-200 bg-amber-50/80 text-amber-700 font-bold border-l-4 border-l-amber-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Cash Handover
                </a>

                @if(!auth()->user()->isCollector() || auth()->user()->isIspAdmin() || auth()->user()->isOwner())
                <!-- 5. Reseller Billing -->
                <a href="{{ Route::has('tenant.finance.wholesale-billing') ? route('tenant.finance.wholesale-billing') : '#' }}" 
                   x-show="!filterQuery || 'reseller wholesale billing monthly bandwidth package invoices sub-isp billing'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('tenant.finance.wholesale-billing*') ? 'border-amber-200 bg-amber-50/80 text-amber-700 font-bold border-l-4 border-l-amber-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Reseller Billing
                </a>

                <!-- 6. Gateway Payments -->
                <a href="{{ Route::has('tenant.finance.gateway-transactions') ? route('tenant.finance.gateway-transactions') : '#' }}" 
                   x-show="!filterQuery || 'online gateway transactions bkash nagad rocket sslcommerz shurjopay pgw logs payment gateway'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('tenant.finance.gateway-transactions*') ? 'border-amber-200 bg-amber-50/80 text-amber-700 font-bold border-l-4 border-l-amber-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Gateway Payments
                </a>

                <!-- 7. Income & Expenses -->
                <a href="{{ Route::has('tenant.finance.expenses') ? route('tenant.finance.expenses') : '#' }}" 
                   x-show="!filterQuery || 'income expense accounts company operational office rent bandwidth cost bills voucher'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('tenant.finance.expenses*') ? 'border-amber-200 bg-amber-50/80 text-amber-700 font-bold border-l-4 border-l-amber-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Income &amp; Expenses
                </a>

                <!-- 8. Accounts & Ledger -->
                <a href="{{ Route::has('tenant.finance.ledger') ? route('tenant.finance.ledger') : '#' }}" 
                   x-show="!filterQuery || 'financial statements ledger trial balance cash book profit loss balance sheet accounts'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('tenant.finance.ledger*') ? 'border-amber-200 bg-amber-50/80 text-amber-700 font-bold border-l-4 border-l-amber-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Accounts &amp; Ledger
                </a>
                @endif
            </div>
        </div>
        @endif

         <!-- 8. Main Menu: Reports, Analytics & Regulatory Compliance (Collapsible Sub-menu) -->
        @if(auth()->user() && !auth()->user()->isCollector() && !auth()->user()->isTechnician() && (auth()->user()->isOwner() || auth()->user()->isIspAdmin() || auth()->user()->hasPermission('reports.revenue') || auth()->user()->hasPermission('reports.collection') || auth()->user()->hasPermission('reports.mrtg') || auth()->user()->hasPermission('reports.btrc') || auth()->user()->hasPermission('reports.inventory')))
        @php
            $isReportsActive = request()->routeIs('tenant.reports*') || request()->routeIs('tenant.analytics*') || request()->routeIs('tenant.compliance*');
        @endphp
        <div x-data="{ open: {{ $isReportsActive ? 'true' : 'false' }} }" 
             x-show="!filterQuery || 'reports analytics regulatory compliance monthly revenue growth arpu new connections churn collection due breakdown bandwidth mrtg peak graph btrc govt log server subscriber list equipment inventory fiber splitters stock'.includes(filterQuery.toLowerCase())"
             class="space-y-1">
            <button type="button" 
                    @click="open = !open" 
                    class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg border transition text-[11.5px] font-semibold shadow-2xs cursor-pointer {{ $isReportsActive ? 'border-rose-200 bg-rose-50/80 text-rose-700 font-bold border-l-4 border-l-rose-600' : 'border-slate-200/90 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300' }}">
                <div class="flex items-center gap-2">
                    <i class="fas fa-chart-line w-4 text-center text-xs {{ $isReportsActive ? 'text-rose-600' : 'text-slate-500' }}"></i>
                    <span>Reports &amp; Analytics</span>
                </div>
                <i class="fas fa-chevron-right text-[8.5px] text-slate-400 transition-transform duration-150" :class="open ? 'rotate-90 text-slate-600' : ''"></i>
            </button>

            <!-- Sub-menu Items -->
            <div x-show="open || filterQuery" x-collapse class="pl-2.5 pr-0.5 space-y-1">
                <!-- 1. Revenue & Growth -->
                <a href="{{ Route::has('tenant.reports.revenue') ? route('tenant.reports.revenue') : '#' }}" 
                   x-show="!filterQuery || 'monthly revenue growth report arpu new connections churn rate sales growth sales revenue'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('tenant.reports.revenue*') ? 'border-rose-200 bg-rose-50/80 text-rose-700 font-bold border-l-4 border-l-rose-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Revenue &amp; Growth
                </a>

                <!-- 2. Collection & Due -->
                <a href="{{ Route::has('tenant.reports.collection') ? route('tenant.reports.collection') : '#' }}" 
                   x-show="!filterQuery || 'collection due breakdown zone collector package wise collection unpaid due report'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('tenant.reports.collection*') ? 'border-rose-200 bg-rose-50/80 text-rose-700 font-bold border-l-4 border-l-rose-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Collection &amp; Due
                </a>

                <!-- 3. Bandwidth & MRTG -->
                <a href="{{ Route::has('tenant.reports.mrtg') ? route('tenant.reports.mrtg') : '#' }}" 
                   x-show="!filterQuery || 'bandwidth mrtg peak graph total upstream consumption peak hours traffic analysis graph usage'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('tenant.reports.mrtg*') ? 'border-rose-200 bg-rose-50/80 text-rose-700 font-bold border-l-4 border-l-rose-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Bandwidth &amp; MRTG
                </a>

                <!-- 4. BTRC Compliance -->
                <a href="{{ Route::has('tenant.reports.btrc') ? route('tenant.reports.btrc') : '#' }}" 
                   x-show="!filterQuery || 'btrc regulatory compliance log subscriber list log server govt compliance audit regulatory'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('tenant.reports.btrc*') ? 'border-rose-200 bg-rose-50/80 text-rose-700 font-bold border-l-4 border-l-rose-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    BTRC Compliance
                </a>

                <!-- 5. Equipment & Inventory -->
                <a href="{{ Route::has('tenant.reports.inventory') ? route('tenant.reports.inventory') : '#' }}" 
                   x-show="!filterQuery || 'equipment inventory report fiber drum splitters onus patch cords stock hardware stock'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('tenant.reports.inventory*') ? 'border-rose-200 bg-rose-50/80 text-rose-700 font-bold border-l-4 border-l-rose-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Equipment &amp; Inventory
                </a>
            </div>
        </div>
        @endif

        <!-- 7. Main Menu: Helpdesk, NOC & Field Ticketing (Collapsible Sub-menu) -->
        @if(auth()->user() && (auth()->user()->isOwner() || auth()->user()->isIspAdmin() || auth()->user()->isCollector() || auth()->user()->hasPermission('support.tickets') || auth()->user()->hasPermission('support.field_jobs') || auth()->user()->hasPermission('support.installations') || auth()->user()->hasPermission('support.escalations') || auth()->user()->hasPermission('support.sla')))
        @php
            $isTicketsActive = request()->routeIs('tenant.tickets*') || request()->routeIs('tenant.helpdesk*') || request()->routeIs('tenant.support*');
        @endphp
        <div x-data="{ open: {{ $isTicketsActive ? 'true' : 'false' }} }" 
             x-show="!filterQuery || 'helpdesk support noc tickets ticketing complaints slow internet line disconnections fiber splicing onu replacement home visits field jobs new installation cable pulling reseller escalations sla performance analytics response resolution'.includes(filterQuery.toLowerCase())"
             class="space-y-1">
            <button type="button" 
                    @click="open = !open" 
                    class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg border transition text-[11.5px] font-semibold shadow-2xs cursor-pointer {{ $isTicketsActive ? 'border-blue-200 bg-blue-50/80 text-blue-600 font-bold border-l-4 border-l-blue-600' : 'border-slate-200/90 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300' }}">
                <div class="flex items-center gap-2">
                    <i class="fas fa-headset w-4 text-center text-xs {{ $isTicketsActive ? 'text-blue-600' : 'text-slate-500' }}"></i>
                    <span>Helpdesk &amp; Support</span>
                </div>
                <i class="fas fa-chevron-right text-[8.5px] text-slate-400 transition-transform duration-150" :class="open ? 'rotate-90 text-slate-600' : ''"></i>
            </button>

            <!-- Sub-menu Items -->
            <div x-show="open || filterQuery" x-collapse class="pl-2.5 pr-0.5 space-y-1">
                <!-- 1. Support Tickets -->
                <a href="{{ Route::has('tenant.tickets.index') ? route('tenant.tickets.index') : '#' }}" 
                   x-show="!filterQuery || 'all support tickets customer complaints slow internet line disconnections open closed pending'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ (request()->routeIs('tenant.tickets.index') || request()->routeIs('tenant.tickets.show') || request()->routeIs('tenant.tickets.create')) ? 'border-blue-200 bg-blue-50/80 text-blue-600 font-bold border-l-4 border-l-blue-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Support Tickets
                </a>

                @if(!auth()->user()->isCollector() || auth()->user()->isIspAdmin() || auth()->user()->isOwner())
                <!-- 2. Field Jobs -->
                <a href="{{ Route::has('tenant.tickets.field-jobs') ? route('tenant.tickets.field-jobs') : '#' }}" 
                   x-show="!filterQuery || 'assigned field jobs technician fiber splicing onu replacement home visits roster dispatch'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('tenant.tickets.field-jobs*') ? 'border-blue-200 bg-blue-50/80 text-blue-600 font-bold border-l-4 border-l-blue-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Field Jobs
                </a>

                <!-- 3. Installations -->
                <a href="{{ Route::has('tenant.tickets.installations') ? route('tenant.tickets.installations') : '#' }}" 
                   x-show="!filterQuery || 'new installation tasks pending physical connections cable pulling subscriber setup line active'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('tenant.tickets.installations*') ? 'border-blue-200 bg-blue-50/80 text-blue-600 font-bold border-l-4 border-l-blue-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Installations
                </a>

                <!-- 4. Escalations -->
                <a href="{{ Route::has('tenant.tickets.escalations') ? route('tenant.tickets.escalations') : '#' }}" 
                   x-show="!filterQuery || 'reseller escalations complex technical issues escalated by sub-isps franchise partner support'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('tenant.tickets.escalations*') ? 'border-blue-200 bg-blue-50/80 text-blue-600 font-bold border-l-4 border-l-blue-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Escalations
                </a>

                <!-- 5. SLA & Analytics -->
                <a href="{{ Route::has('tenant.tickets.sla-analytics') ? route('tenant.tickets.sla-analytics') : '#' }}" 
                   x-show="!filterQuery || 'sla performance analytics average response time ticket resolution rate engineer score kpi'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('tenant.tickets.sla-analytics*') ? 'border-blue-200 bg-blue-50/80 text-blue-600 font-bold border-l-4 border-l-blue-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    SLA &amp; Analytics
                </a>
                @endif
            </div>
        </div>
        @endif



        <!-- 9. Main Menu: System Settings & Administration (Collapsible Sub-menu) -->
        @if(auth()->user() && (auth()->user()->isOwner() || auth()->user()->isIspAdmin() || auth()->user()->hasPermission('settings.profile') || auth()->user()->hasPermission('settings.sms_gateway') || auth()->user()->hasPermission('settings.automation') || auth()->user()->hasPermission('settings.rbac') || auth()->user()->hasPermission('settings.backup') || auth()->user()->hasPermission('settings.audit_logs')))
        @php
            $isSettingsActive = request()->routeIs('tenant.settings*') || request()->routeIs('tenant.system*') || request()->routeIs('tenant.admin*');
        @endphp
        <div x-data="{ open: {{ $isSettingsActive ? 'true' : 'false' }} }" 
             x-show="!filterQuery || 'system settings administration company profile sms whatsapp gateway automation auto-cut rbac permissions roles backup audit trail logs'.includes(filterQuery.toLowerCase())"
             class="space-y-1">
            <button type="button" 
                    @click="open = !open" 
                    class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg border transition text-[11.5px] font-semibold shadow-2xs cursor-pointer {{ $isSettingsActive ? 'border-slate-300 bg-slate-100 text-slate-900 font-bold border-l-4 border-l-slate-700' : 'border-slate-200/90 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300' }}">
                <div class="flex items-center gap-2">
                    <i class="fas fa-gear w-4 text-center text-xs {{ $isSettingsActive ? 'text-slate-800' : 'text-slate-500' }}"></i>
                    <span>Settings &amp; Admin</span>
                </div>
                <i class="fas fa-chevron-right text-[8.5px] text-slate-400 transition-transform duration-150" :class="open ? 'rotate-90 text-slate-600' : ''"></i>
            </button>

            <!-- Sub-menu Items -->
            <div x-show="open || filterQuery" x-collapse class="pl-2.5 pr-0.5 space-y-1">
                <!-- 1. Company Profile -->
                <a href="{{ Route::has('tenant.settings.profile') ? route('tenant.settings.profile') : '#' }}" 
                   x-show="!filterQuery || 'isp company profile company name logo trade license contact info branding'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('tenant.settings.profile*') ? 'border-slate-300 bg-slate-100 text-slate-900 font-bold border-l-4 border-l-slate-700' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Company Profile
                </a>

                <!-- 2. SMS & Notifications -->
                <a href="{{ Route::has('tenant.settings.sms-gateway') ? route('tenant.settings.sms-gateway') : '#' }}" 
                   x-show="!filterQuery || 'sms whatsapp gateway bulk sms configuration payment expiry templates notifications'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('tenant.settings.sms*') ? 'border-slate-300 bg-slate-100 text-slate-900 font-bold border-l-4 border-l-slate-700' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    SMS &amp; Notifications
                </a>

                <!-- 3. Payment Gateways (bKash, Nagad, Bank/QR) -->
                <a href="{{ Route::has('tenant.settings.payment-gateways') ? route('tenant.settings.payment-gateways') : '#' }}" 
                   x-show="!filterQuery || 'payment gateways bkash nagad rocket merchant api bangla qr bank transfer gateway pgw'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('tenant.settings.payment-gateways*') ? 'border-slate-300 bg-slate-100 text-slate-900 font-bold border-l-4 border-l-slate-700' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Payment Gateways
                </a>

                <!-- 3. Automation Rules -->
                <a href="{{ Route::has('tenant.settings.automation') ? route('tenant.settings.automation') : '#' }}" 
                   x-show="!filterQuery || 'automation auto-cut rules auto suspend non-paid users grace days setting cron billing'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('tenant.settings.automation*') ? 'border-slate-300 bg-slate-100 text-slate-900 font-bold border-l-4 border-l-slate-700' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Automation Rules
                </a>

                <!-- 4. Roles & Permissions -->
                <a href="{{ Route::has('tenant.settings.rbac') ? route('tenant.settings.rbac') : '#' }}" 
                   x-show="!filterQuery || 'role based access control rbac permissions menu visibility roles acl'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('tenant.settings.rbac*') ? 'border-slate-300 bg-slate-100 text-slate-900 font-bold border-l-4 border-l-slate-700' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Roles &amp; Permissions
                </a>

                <!-- 5. Backup & Restore -->
                <a href="{{ Route::has('tenant.settings.backup') ? route('tenant.settings.backup') : '#' }}" 
                   x-show="!filterQuery || 'system backup maintenance automated cloud local db database restore snapshot'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('tenant.settings.backup*') ? 'border-slate-300 bg-slate-100 text-slate-900 font-bold border-l-4 border-l-slate-700' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Backup &amp; Restore
                </a>

                <!-- 6. Activity & Audit Trail -->
                <a href="{{ Route::has('tenant.settings.audit-logs') ? route('tenant.settings.audit-logs') : (Route::has('tenant.staff.attendance') ? route('tenant.staff.attendance', ['tab' => 'logs']) : '#') }}" 
                   x-show="!filterQuery || 'activity audit trail full audit logs admin staff actions security login logs telemetry'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('tenant.settings.audit*') || (request()->routeIs('tenant.staff.attendance*') && request('tab') === 'logs') ? 'border-slate-300 bg-slate-100 text-slate-900 font-bold border-l-4 border-l-slate-700' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Activity &amp; Audit Trail
                </a>
            </div>
        </div>
        @endif
        @endif

    </div>

    <!-- Sidebar User Footer -->
    <div class="p-2.5 border-t border-slate-100 bg-slate-50/70 flex items-center justify-between">
        <div class="flex items-center gap-2 overflow-hidden">
            <div class="w-6 h-6 rounded-md bg-blue-100 text-blue-700 flex-shrink-0 flex items-center justify-center font-bold text-[10px]">
                {{ strtoupper(substr($authUser->name ?? 'A', 0, 1)) }}
            </div>
            <div class="flex flex-col overflow-hidden leading-none">
                <span class="text-[11px] font-bold text-slate-800 truncate">{{ $authUser->name ?? 'Admin' }}</span>
                <span class="text-[9px] text-slate-400">{{ $authUser->role_badge['label'] ?? ($authUser->isCollector() ? 'Bill Collector' : 'ISP Staff') }}</span>
            </div>
        </div>
        <form action="{{ route('tenant.logout') }}" method="POST">
            @csrf
            <button type="submit" title="Sign Out" class="p-1 text-slate-400 hover:text-rose-600 transition cursor-pointer">
                <i class="fas fa-sign-out-alt text-xs"></i>
            </button>
        </form>
    </div>
</aside>
