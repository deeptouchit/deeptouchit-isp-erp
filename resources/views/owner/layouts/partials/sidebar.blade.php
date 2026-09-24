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
        <a href="{{ route('owner.dashboard') }}" class="flex items-center gap-2.5 overflow-hidden">
            @if($appLogo)
                <img src="{{ $appLogo }}" alt="{{ $appShortName }}" class="h-7 max-w-[120px] object-contain">
            @else
                <div class="w-7 h-7 rounded-lg bg-blue-600 flex-shrink-0 flex items-center justify-center text-white font-bold text-xs shadow-xs">
                    <i class="fas fa-crown text-amber-300 text-[10px]"></i>
                </div>
            @endif
            <div class="flex flex-col overflow-hidden leading-tight">
                <span class="font-bold text-slate-900 text-xs tracking-tight truncate">{{ $appShortName }}</span>
                <span class="text-[9.5px] font-semibold text-blue-600 uppercase tracking-wider">Owner Console</span>
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
                   name="owner_menu_search_no_autofill"
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

    <!-- Navigation Menu Container -->
    <div class="flex-1 overflow-y-auto p-2 space-y-1 scrollbar-thin">
        
        <!-- 1. Top Standalone Menu: Dashboard -->
        <a href="{{ route('owner.dashboard') }}" 
           x-show="!filterQuery || 'dashboard overview stats home analytics'.includes(filterQuery.toLowerCase())"
           class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg border transition text-[11.5px] font-semibold shadow-2xs {{ request()->routeIs('owner.dashboard') ? 'border-blue-200 bg-blue-50/80 text-blue-600 font-bold border-l-4 border-l-blue-600' : 'border-slate-200/90 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300' }}">
            <i class="fas fa-house-chimney w-4 text-center text-xs {{ request()->routeIs('owner.dashboard') ? 'text-blue-600' : 'text-slate-500' }}"></i>
            <span>Dashboard</span>
        </a>

        <!-- 2. Main Menu: Tenants & Plans (Collapsible Sub-menu) -->
        @php
            $isTenantsActive = request()->routeIs('owner.tenants*', 'owner.users*', 'owner.plans*', 'owner.wallets*');
        @endphp
        <div x-data="{ open: {{ $isTenantsActive ? 'true' : 'false' }} }" 
             x-show="!filterQuery || 'tenants users accounts admins staff plans wallets clients pricing password'.includes(filterQuery.toLowerCase())"
             class="space-y-1">
            <button type="button" 
                    @click="open = !open" 
                    class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg border transition text-[11.5px] font-semibold shadow-2xs cursor-pointer {{ $isTenantsActive ? 'border-blue-200 bg-blue-50/80 text-blue-600 font-bold border-l-4 border-l-blue-600' : 'border-slate-200/90 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300' }}">
                <div class="flex items-center gap-2">
                    <i class="fas fa-building w-4 text-center text-xs {{ $isTenantsActive ? 'text-blue-600' : 'text-slate-500' }}"></i>
                    <span>Tenants & Plans</span>
                </div>
                <i class="fas fa-chevron-right text-[8.5px] text-slate-400 transition-transform duration-150" :class="open ? 'rotate-90 text-slate-600' : ''"></i>
            </button>

            <!-- Sub-menu Items -->
            <div x-show="open || (filterQuery && filterQuery.trim() !== '')" class="pl-2.5 pr-0.5 space-y-1">
                <a href="{{ route('owner.tenants.index') }}" 
                   x-show="!filterQuery || 'tenants isp companies organizations clients'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('owner.tenants*') ? 'border-blue-200 bg-blue-50/80 text-blue-600 font-bold border-l-4 border-l-blue-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    ISP Tenants
                </a>

                <a href="{{ route('owner.users.index') }}" 
                   x-show="!filterQuery || 'users accounts admins staff technicians collectors credentials password reset members'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('owner.users*') ? 'border-blue-200 bg-blue-50/80 text-blue-600 font-bold border-l-4 border-l-blue-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    User Management
                </a>

                <a href="{{ route('owner.plans.index') }}" 
                   x-show="!filterQuery || 'plans packages subscription pricing tiers'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('owner.plans*') ? 'border-blue-200 bg-blue-50/80 text-blue-600 font-bold border-l-4 border-l-blue-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    SaaS Plans
                </a>

                <a href="{{ route('owner.wallets.index') }}" 
                   x-show="!filterQuery || 'wallets advance credits prepaid balance topup'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('owner.wallets*') ? 'border-blue-200 bg-blue-50/80 text-blue-600 font-bold border-l-4 border-l-blue-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Tenant Wallets
                </a>
            </div>
        </div>

        <!-- 3. Main Menu: Carrier Network Engines (Collapsible Sub-menu) -->
        @php
            $isNetworkActive = request()->routeIs('owner.network-engines*');
        @endphp
        <div x-data="{ open: {{ $isNetworkActive ? 'true' : 'false' }} }" 
             x-show="!filterQuery || 'network mikrotik router olt pon radius wireguard vpn onu fleet vault'.includes(filterQuery.toLowerCase())"
             class="space-y-1">
            <button type="button" 
                    @click="open = !open" 
                    class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg border transition text-[11.5px] font-semibold shadow-2xs cursor-pointer {{ $isNetworkActive ? 'border-blue-200 bg-blue-50/80 text-blue-600 font-bold border-l-4 border-l-blue-600' : 'border-slate-200/90 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300' }}">
                <div class="flex items-center gap-2">
                    <i class="fas fa-network-wired w-4 text-center text-xs {{ $isNetworkActive ? 'text-blue-600' : 'text-slate-500' }}"></i>
                    <span>Network Engines</span>
                </div>
                <i class="fas fa-chevron-right text-[8.5px] text-slate-400 transition-transform duration-150" :class="open ? 'rotate-90 text-slate-600' : ''"></i>
            </button>

            <!-- Sub-menu Items -->
            <div x-show="open || (filterQuery && filterQuery.trim() !== '')" class="pl-2.5 pr-0.5 space-y-1">
                <a href="{{ route('owner.network-engines.index') }}" 
                   x-show="!filterQuery || 'fleet mikrotik routers olts devices telemetry'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('owner.network-engines.index') ? 'border-blue-200 bg-blue-50/80 text-blue-600 font-bold border-l-4 border-l-blue-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Fleet Overview
                </a>

                <a href="{{ route('owner.network-engines.settings') }}" 
                   x-show="!filterQuery || 'infrastructure vault settings radius wireguard snmp timeouts'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('owner.network-engines.settings') ? 'border-blue-200 bg-blue-50/80 text-blue-600 font-bold border-l-4 border-l-blue-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Infrastructure & Vault
                </a>

                <a href="{{ route('owner.network-engines.audit-logs') }}" 
                   x-show="!filterQuery || 'network audit trail logs coa reboot reset vlan history'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('owner.network-engines.audit-logs') ? 'border-blue-200 bg-blue-50/80 text-blue-600 font-bold border-l-4 border-l-blue-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Network Audit Trail
                </a>
            </div>
        </div>

        <!-- 4. Main Menu: Billing & Finance (Collapsible Sub-menu) -->
        @php
            $isBillingActive = request()->routeIs('owner.billing*', 'owner.invoices*', 'owner.subscriptions*', 'owner.payments*', 'owner.transactions*');
        @endphp
        <div x-data="{ open: {{ $isBillingActive ? 'true' : 'false' }} }" 
             x-show="!filterQuery || 'billing finance invoices subscriptions payments transactions revenue'.includes(filterQuery.toLowerCase())"
             class="space-y-1">
            <button type="button" 
                    @click="open = !open" 
                    class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg border transition text-[11.5px] font-semibold shadow-2xs cursor-pointer {{ $isBillingActive ? 'border-blue-200 bg-blue-50/80 text-blue-600 font-bold border-l-4 border-l-blue-600' : 'border-slate-200/90 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300' }}">
                <div class="flex items-center gap-2">
                    <i class="fas fa-file-invoice-dollar w-4 text-center text-xs {{ $isBillingActive ? 'text-blue-600' : 'text-slate-500' }}"></i>
                    <span>Billing & Finance</span>
                </div>
                <i class="fas fa-chevron-right text-[8.5px] text-slate-400 transition-transform duration-150" :class="open ? 'rotate-90 text-slate-600' : ''"></i>
            </button>

            <!-- Sub-menu Items -->
            <div x-show="open || (filterQuery && filterQuery.trim() !== '')" class="pl-2.5 pr-0.5 space-y-1">
                <a href="{{ route('owner.billing.index') }}" 
                   x-show="!filterQuery || 'invoices billing receipts revenue due vouchers'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('owner.billing.index', 'owner.invoices*') ? 'border-blue-200 bg-blue-50/80 text-blue-600 font-bold border-l-4 border-l-blue-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Invoices & Billing
                </a>

                <a href="{{ route('owner.subscriptions.index') }}" 
                   x-show="!filterQuery || 'subscriptions lifecycle active renewal duration expiry'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('owner.subscriptions*') ? 'border-blue-200 bg-blue-50/80 text-blue-600 font-bold border-l-4 border-l-blue-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Subscriptions
                </a>

                <a href="{{ route('owner.payments.index') }}" 
                   x-show="!filterQuery || 'payments settled revenue collections cash receipt'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('owner.payments*') ? 'border-blue-200 bg-blue-50/80 text-blue-600 font-bold border-l-4 border-l-blue-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Settled Payments
                </a>

                <a href="{{ route('owner.transactions.index') }}" 
                   x-show="!filterQuery || 'transactions gateways handshakes pgw log online'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('owner.transactions*') ? 'border-blue-200 bg-blue-50/80 text-blue-600 font-bold border-l-4 border-l-blue-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Gateway Transactions
                </a>
            </div>
        </div>

        <!-- 5. Standalone Menu: Support Tickets -->
        <a href="{{ route('owner.tickets.index') }}" 
           x-show="!filterQuery || 'tickets support helpdesk issues inquiries reports'.includes(filterQuery.toLowerCase())"
           class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg border transition text-[11.5px] font-semibold shadow-2xs {{ request()->routeIs('owner.tickets*') ? 'border-blue-200 bg-blue-50/80 text-blue-600 font-bold border-l-4 border-l-blue-600' : 'border-slate-200/90 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300' }}">
            <i class="fas fa-headset w-4 text-center text-xs {{ request()->routeIs('owner.tickets*') ? 'text-blue-600' : 'text-slate-500' }}"></i>
            <span>Support Tickets</span>
        </a>

        <!-- 6. Main Menu: Gateways & Channels (Collapsible Sub-menu) -->
        @php
            $isGatewaysActive = request()->routeIs('owner.payment-gateways*', 'owner.sms-gateways*', 'owner.mail-settings*');
        @endphp
        <div x-data="{ open: {{ $isGatewaysActive ? 'true' : 'false' }} }" 
             x-show="!filterQuery || 'gateways channels pgw sms mail smtp'.includes(filterQuery.toLowerCase())"
             class="space-y-1">
            <button type="button" 
                    @click="open = !open" 
                    class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg border transition text-[11.5px] font-semibold shadow-2xs cursor-pointer {{ $isGatewaysActive ? 'border-blue-200 bg-blue-50/80 text-blue-600 font-bold border-l-4 border-l-blue-600' : 'border-slate-200/90 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300' }}">
                <div class="flex items-center gap-2">
                    <i class="fas fa-satellite-dish w-4 text-center text-xs {{ $isGatewaysActive ? 'text-blue-600' : 'text-slate-500' }}"></i>
                    <span>Gateways & Channels</span>
                </div>
                <i class="fas fa-chevron-right text-[8.5px] text-slate-400 transition-transform duration-150" :class="open ? 'rotate-90 text-slate-600' : ''"></i>
            </button>

            <!-- Sub-menu Items -->
            <div x-show="open || (filterQuery && filterQuery.trim() !== '')" class="pl-2.5 pr-0.5 space-y-1">
                <a href="{{ route('owner.payment-gateways.index') }}" 
                   x-show="!filterQuery || 'payment gateways pgw bkash nagad rocket bank merchant credentials'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('owner.payment-gateways*') ? 'border-blue-200 bg-blue-50/80 text-blue-600 font-bold border-l-4 border-l-blue-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Payment Gateways
                </a>

                <a href="{{ route('owner.sms-gateways.index') }}" 
                   x-show="!filterQuery || 'sms gateways bulksmsbd greenweb onnorokom elitbuzz messages balance alerts'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('owner.sms-gateways*') ? 'border-blue-200 bg-blue-50/80 text-blue-600 font-bold border-l-4 border-l-blue-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    SMS Gateways
                </a>

                <a href="{{ route('owner.mail-settings.index') }}" 
                   x-show="!filterQuery || 'mail smtp email host port mailgun ses encryption'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('owner.mail-settings*') ? 'border-blue-200 bg-blue-50/80 text-blue-600 font-bold border-l-4 border-l-blue-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Mail & SMTP
                </a>
            </div>
        </div>

        <!-- 7. Main Menu: Reports & Logs (Collapsible Sub-menu) -->
        @php
            $isLogsActive = request()->routeIs('owner.revenue-report*', 'owner.reports.revenue*', 'owner.activity-logs*', 'owner.sms-logs*');
        @endphp
        <div x-data="{ open: {{ $isLogsActive ? 'true' : 'false' }} }" 
             x-show="!filterQuery || 'reports logs activity audit ledger history income revenue financial statement profit'.includes(filterQuery.toLowerCase())"
             class="space-y-1">
            <button type="button" 
                    @click="open = !open" 
                    class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg border transition text-[11.5px] font-semibold shadow-2xs cursor-pointer {{ $isLogsActive ? 'border-blue-200 bg-blue-50/80 text-blue-600 font-bold border-l-4 border-l-blue-600' : 'border-slate-200/90 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300' }}">
                <div class="flex items-center gap-2">
                    <i class="fas fa-chart-line w-4 text-center text-xs {{ $isLogsActive ? 'text-blue-600' : 'text-slate-500' }}"></i>
                    <span>Reports & Logs</span>
                </div>
                <i class="fas fa-chevron-right text-[8.5px] text-slate-400 transition-transform duration-150" :class="open ? 'rotate-90 text-slate-600' : ''"></i>
            </button>

            <!-- Sub-menu Items -->
            <div x-show="open || (filterQuery && filterQuery.trim() !== '')" class="pl-2.5 pr-0.5 space-y-1">
                <a href="{{ route('owner.revenue-report.index') }}" 
                   x-show="!filterQuery || 'income revenue statement financial profit earnings collections'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('owner.revenue-report*', 'owner.reports.revenue*') ? 'border-blue-200 bg-blue-50/80 text-blue-600 font-bold border-l-4 border-l-blue-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Revenue & Income
                </a>

                <a href="{{ route('owner.activity-logs.index') }}" 
                   x-show="!filterQuery || 'activity audit logs trail events history'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('owner.activity-logs*') ? 'border-blue-200 bg-blue-50/80 text-blue-600 font-bold border-l-4 border-l-blue-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Activity & Audit Logs
                </a>

                <a href="{{ route('owner.sms-logs.index') }}" 
                   x-show="!filterQuery || 'sms logs delivery cost parts ledger audit history messages'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('owner.sms-logs*') ? 'border-blue-200 bg-blue-50/80 text-blue-600 font-bold border-l-4 border-l-blue-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    SMS Logs & Ledger
                </a>
            </div>
        </div>

        <!-- 8. Main Menu: System & Settings (Collapsible Sub-menu) -->
        @php
            $isSystemActive = request()->routeIs('owner.automation*', 'owner.security-backup*', 'owner.settings*');
        @endphp
        <div x-data="{ open: {{ $isSystemActive ? 'true' : 'false' }} }" 
             x-show="!filterQuery || 'system settings automation cron security backup'.includes(filterQuery.toLowerCase())"
             class="space-y-1">
            <button type="button" 
                    @click="open = !open" 
                    class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg border transition text-[11.5px] font-semibold shadow-2xs cursor-pointer {{ $isSystemActive ? 'border-blue-200 bg-blue-50/80 text-blue-600 font-bold border-l-4 border-l-blue-600' : 'border-slate-200/90 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300' }}">
                <div class="flex items-center gap-2">
                    <i class="fas fa-gear w-4 text-center text-xs {{ $isSystemActive ? 'text-blue-600' : 'text-slate-500' }}"></i>
                    <span>System & Settings</span>
                </div>
                <i class="fas fa-chevron-right text-[8.5px] text-slate-400 transition-transform duration-150" :class="open ? 'rotate-90 text-slate-600' : ''"></i>
            </button>

            <!-- Sub-menu Items -->
            <div x-show="open || (filterQuery && filterQuery.trim() !== '')" class="pl-2.5 pr-0.5 space-y-1">
                <a href="{{ route('owner.automation.index') }}" 
                   x-show="!filterQuery || 'automation cron scheduler heartbeat worker engine'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('owner.automation*') ? 'border-blue-200 bg-blue-50/80 text-blue-600 font-bold border-l-4 border-l-blue-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Automation & Cron
                </a>

                <a href="{{ route('owner.security-backup.index') }}" 
                   x-show="!filterQuery || 'security backup database dump 2fa ip whitelist sql'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('owner.security-backup*') ? 'border-blue-200 bg-blue-50/80 text-blue-600 font-bold border-l-4 border-l-blue-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Security & Backup
                </a>

                <a href="{{ route('owner.settings.index') }}" 
                   x-show="!filterQuery || 'platform settings general branding logo company copyright'.includes(filterQuery.toLowerCase())"
                   class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ request()->routeIs('owner.settings*') ? 'border-blue-200 bg-blue-50/80 text-blue-600 font-bold border-l-4 border-l-blue-600' : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                    Platform Settings
                </a>
            </div>
        </div>

    </div>

    <!-- Sidebar User Footer -->
    <div class="p-2.5 border-t border-slate-100 bg-slate-50/70 flex items-center justify-between">
        <div class="flex items-center gap-2 overflow-hidden">
            <div class="w-6 h-6 rounded-md bg-blue-100 text-blue-700 flex-shrink-0 flex items-center justify-center font-bold text-[10px]">
                {{ strtoupper(substr($ownerUser->name ?? 'O', 0, 1)) }}
            </div>
            <div class="flex flex-col overflow-hidden leading-none">
                <span class="text-[11px] font-bold text-slate-800 truncate">{{ $ownerUser->name ?? 'Owner' }}</span>
                <span class="text-[9px] text-slate-400">Super Admin</span>
            </div>
        </div>
        <form action="{{ route('owner.logout') }}" method="POST">
            @csrf
            <button type="submit" title="Sign Out" class="p-1 text-slate-400 hover:text-rose-600 transition cursor-pointer">
                <i class="fas fa-sign-out-alt text-xs"></i>
            </button>
        </form>
    </div>
</aside>
