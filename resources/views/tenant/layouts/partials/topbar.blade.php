@php
    $authUser = auth()->user();
    $tenantName = $tenant->company_name ?? ($tenant->name ?? 'ISP Portal');
    $planName = $tenant->plan->name ?? 'Carrier Plan';
    $isActiveTenant = ($tenant->status ?? 'active') === 'active';
    $currentLocale = app()->getLocale();

    $canViewCustomers = $authUser && !$authUser->isCollector() && !$authUser->isTechnician()
        && ($authUser->isOwner() || $authUser->isIspAdmin() || $authUser->hasPermission('customers.view'));
    $canViewTickets = $authUser && !$authUser->isCollector() && !$authUser->isTechnician()
        && ($authUser->isOwner() || $authUser->isIspAdmin() || $authUser->hasPermission('support.tickets'));

    $firstName = trim(explode(' ', trim($authUser->name ?? 'Admin'))[0] ?? 'Admin');
    $avatarInitial = mb_strtoupper(mb_substr(trim($authUser->name ?? 'A'), 0, 1, 'UTF-8'), 'UTF-8') ?: 'A';
@endphp
<!-- Enterprise Sticky Top Navbar -->
<header class="h-14 bg-white/95 backdrop-blur-md border-b border-slate-200 px-3 sm:px-5 flex items-center justify-between gap-2 sticky top-0 z-40 shadow-2xs">

    <!-- Left Cluster: Sidebar Controls, Workspace Pill & Quick Search -->
    <div class="flex items-center gap-2 min-w-0">
        <!-- Mobile Drawer Toggle -->
        <button type="button"
                @click="mobileSidebar = true"
                class="lg:hidden w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200/90 bg-white text-slate-600 hover:text-cyan-700 hover:border-cyan-200 hover:bg-cyan-50/40 transition cursor-pointer flex-shrink-0"
                title="{{ __('Toggle Menu') }}">
            <i class="fas fa-bars text-xs"></i>
        </button>

        <!-- Desktop Sidebar Collapse / Expand -->
        <button type="button"
                @click="toggleSidebar()"
                class="hidden lg:flex w-8 h-8 items-center justify-center rounded-lg border border-slate-200/90 bg-white text-slate-600 hover:text-cyan-700 hover:border-cyan-300/80 hover:bg-cyan-50/40 transition cursor-pointer shadow-2xs flex-shrink-0"
                :title='sidebarCollapsed ? {!! json_encode(__('Expand Sidebar (Ctrl+B)'), JSON_HEX_APOS) !!} : {!! json_encode(__('Collapse Sidebar (Ctrl+B)'), JSON_HEX_APOS) !!}'>
            <i class="fas text-xs transition-transform duration-200"
               :class="sidebarCollapsed ? 'fa-bars text-cyan-600' : 'fa-bars-staggered'"></i>
        </button>

        <div class="h-4 w-px bg-slate-200/90 hidden sm:block flex-shrink-0"></div>

        <!-- Workspace Brand & Plan Status Pill -->
        <div class="flex items-center gap-2 min-w-0">
            <span class="font-bold text-slate-800 text-xs sm:text-[13px] tracking-[-0.01em] truncate max-w-[135px] sm:max-w-[200px] lg:hidden xl:inline">
                {{ $tenantName }}
            </span>
            <span class="hidden sm:inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-[10px] font-semibold border shadow-2xs flex-shrink-0 {{ $isActiveTenant ? 'bg-slate-50 text-slate-700 border-slate-200/90' : 'bg-amber-50 text-amber-800 border-amber-200' }}"
                  title="{{ __('Active Plan') }}: {{ $planName }}">
                <span class="w-1.5 h-1.5 rounded-full {{ $isActiveTenant ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                <span class="font-mono tracking-tight">{{ $planName }}</span>
            </span>
        </div>

        @if($canViewCustomers)
        <!-- Global Customer Live Quick Search Filter (Shortcut: /) -->
        <div class="hidden md:flex items-center relative ml-1"
             x-data="topbarQuickSearch()"
             @click.outside="open = false"
             @keydown.window="if ($event.key === '/' && !$event.ctrlKey && !$event.metaKey && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName)) { $event.preventDefault(); $refs.quickSearch.focus(); }">
            <form action="{{ route('tenant.customers.index') }}"
                  method="GET"
                  x-ref="searchForm"
                  @submit="if (selectedIndex >= 0 && results[selectedIndex]) { $event.preventDefault(); window.location.href = results[selectedIndex].url; }"
                  class="relative flex items-center">
                <i class="fas text-[10px] absolute left-2.5 top-1/2 -translate-y-1/2 pointer-events-none transition-colors"
                   :class="loading ? 'fa-circle-notch fa-spin text-cyan-600' : (query ? 'fa-search text-cyan-600' : 'fa-search text-slate-400')"></i>
                <input x-ref="quickSearch"
                       type="text"
                       name="search"
                       x-model="query"
                       @input="onInput()"
                       @focus="onFocus()"
                       @keydown.arrow-down.prevent="moveSelection(1)"
                       @keydown.arrow-up.prevent="moveSelection(-1)"
                       @keydown.escape.prevent="closeDropdown()"
                       placeholder="{{ __('Search customer (ID, Phone, PPPoE)...') }}"
                       autocomplete="off"
                       class="h-8 w-48 lg:w-60 xl:w-72 pl-7 pr-8 rounded-lg bg-slate-50/90 border border-slate-200/90 text-[11px] text-slate-700 placeholder:text-slate-400 focus:bg-white focus:outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/15 transition shadow-2xs">
                <button type="button"
                        x-show="query && query.length > 0"
                        x-cloak
                        @click="clear()"
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-rose-500 p-0.5 text-[11px] transition cursor-pointer"
                        title="{{ __('Clear Search') }}">
                    <i class="fas fa-times-circle"></i>
                </button>
                <kbd x-show="!query || query.length === 0"
                     class="hidden xl:inline-flex items-center justify-center h-4 min-w-[16px] px-1 text-[9px] font-mono font-semibold text-slate-400 bg-white border border-slate-200 rounded absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none">/</kbd>
            </form>

            <!-- Live Quick Filter Results Flyout -->
            <div x-show="open && query.trim().length > 0"
                 x-cloak
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 translate-y-1 scale-98"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                 x-transition:leave-end="opacity-0 translate-y-1 scale-98"
                 class="absolute left-0 top-full mt-2 w-80 sm:w-[420px] max-w-[92vw] bg-white rounded-2xl border border-slate-200 shadow-2xl z-50 overflow-hidden ring-1 ring-slate-900/5"
                 style="display: none;">

                <!-- Header Strip -->
                <div class="px-3.5 py-2 bg-slate-50/90 border-b border-slate-100 flex items-center justify-between text-[10.5px]">
                    <span class="font-bold text-slate-700 flex items-center gap-1.5">
                        <i class="fas fa-users text-cyan-600 text-[10px]"></i>
                        <span>{{ __('Matching Customers') }}</span>
                    </span>
                    <span class="px-1.5 py-0.2 rounded-full bg-cyan-50 text-cyan-700 border border-cyan-200/80 font-mono font-bold text-[9.5px]"
                          x-text="loading ? '...' : totalCount"></span>
                </div>

                <!-- Results List -->
                <div class="max-h-80 overflow-y-auto divide-y divide-slate-100">
                    <template x-if="loading && results.length === 0">
                        <div class="py-7 text-center text-slate-400 text-xs flex flex-col items-center justify-center gap-1.5">
                            <i class="fas fa-circle-notch fa-spin text-cyan-600 text-base"></i>
                            <span>{{ __('Searching...') }}</span>
                        </div>
                    </template>

                    <template x-if="!loading && results.length === 0">
                        <div class="py-7 text-center text-slate-400 text-xs px-4">
                            <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-2 text-sm">
                                <i class="fas fa-user-slash"></i>
                            </div>
                            <p class="font-semibold text-slate-700 text-xs">{{ __('No matching customers found') }}</p>
                        </div>
                    </template>

                    <template x-for="(item, idx) in results" :key="item.id">
                        <a :href="item.url"
                           @mouseenter="selectedIndex = idx"
                           class="flex items-center justify-between gap-2.5 px-3.5 py-2.5 transition cursor-pointer"
                           :class="selectedIndex === idx ? 'bg-cyan-50/80' : 'hover:bg-slate-50'">
                            <div class="flex items-center gap-2.5 min-w-0 flex-1">
                                <div class="relative w-8 h-8 rounded-lg bg-slate-100 text-slate-700 border border-slate-200/80 flex items-center justify-center font-bold text-[11px] flex-shrink-0">
                                    <span x-text="(item.name || 'C').charAt(0).toUpperCase()"></span>
                                    <span class="w-2 h-2 rounded-full ring-2 ring-white absolute -bottom-0.5 -right-0.5"
                                          :class="item.is_online ? 'bg-emerald-500' : 'bg-slate-300'"></span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-1.5 min-w-0">
                                        <span class="font-bold text-slate-900 text-[11.5px] truncate" x-text="item.name"></span>
                                        <span class="px-1.5 py-0.2 rounded bg-slate-100 text-slate-700 border border-slate-200 font-mono font-semibold text-[9.5px] flex-shrink-0"
                                              x-text="item.customer_id"></span>
                                    </div>
                                    <div class="flex items-center gap-2 text-[10px] text-slate-500 mt-0.5 truncate">
                                        <span x-show="item.username" class="font-mono text-cyan-700" x-text="item.username"></span>
                                        <span x-show="item.phone" class="font-mono" x-text="item.phone"></span>
                                        <span x-show="item.package" class="truncate" x-text="item.package"></span>
                                        <span x-show="item.zone" class="text-slate-400 truncate" x-text="item.zone"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col items-end gap-1 flex-shrink-0">
                                <span class="px-1.5 py-0.2 rounded text-[9px] font-bold border"
                                      :class="item.status === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : (item.status === 'due' ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-rose-50 text-rose-700 border-rose-200')"
                                      x-text="item.status_label"></span>
                                <span x-show="item.due_amount > 0"
                                      class="text-[10px] font-mono font-bold text-rose-600"
                                      x-text="item.due_formatted"></span>
                            </div>
                        </a>
                    </template>
                </div>

                <!-- Dropdown Footer -->
                <div class="px-3.5 py-2 bg-slate-50/90 border-t border-slate-100 flex items-center justify-between gap-2 text-[10.5px]">
                    <a :href="fullSearchUrl()"
                       class="font-bold text-cyan-700 hover:text-cyan-900 transition flex items-center gap-1">
                        <span x-text="totalCount > 0 ? i18n.viewAllCount.replace(':count', totalCount) : i18n.viewAll"></span>
                        <i class="fas fa-arrow-right text-[9px]"></i>
                    </a>
                    <div class="hidden sm:flex items-center gap-2 text-[9.5px] text-slate-400">
                        <span><kbd class="px-1 py-0.2 bg-white border border-slate-200 rounded font-mono">↑↓</kbd> {{ __('Navigate') }}</span>
                        <span><kbd class="px-1 py-0.2 bg-white border border-slate-200 rounded font-mono">↵</kbd> {{ __('Open') }}</span>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- Right Action Cluster (Uniform h-8 Height, Harmonized Slate + Cyan/Blue) -->
    <div class="flex items-center gap-1.5 sm:gap-2 flex-shrink-0">

        @if(session()->has('impersonator_id'))
            <a href="{{ route('owner.impersonate.leave') }}"
               class="h-8 px-2.5 rounded-lg bg-amber-500 hover:bg-amber-600 text-white font-bold text-[11px] shadow-xs flex items-center gap-1.5 transition cursor-pointer">
                <i class="fas fa-sign-out-alt text-[10px]"></i>
                <span class="hidden sm:inline">{{ __('Exit Impersonation') }}</span>
                <span class="sm:hidden">{{ __('Exit') }}</span>
            </a>
        @endif

        <!-- Segmented Bilingual Switcher (বাংলা / EN) -->
        <div class="h-8 inline-flex items-center bg-slate-100/90 p-0.5 rounded-lg border border-slate-200/80 text-[11px] font-semibold">
            <a href="{{ route('locale.switch', 'bn') }}"
               class="h-full px-2 rounded-md transition flex items-center whitespace-nowrap {{ $currentLocale === 'bn' ? 'bg-white text-cyan-700 font-bold shadow-2xs border border-slate-200/70' : 'text-slate-500 hover:text-slate-800' }}"
               title="বাংলায় দেখুন">
                বাংলা
            </a>
            <a href="{{ route('locale.switch', 'en') }}"
               class="h-full px-2 rounded-md transition flex items-center whitespace-nowrap {{ $currentLocale === 'en' ? 'bg-white text-cyan-700 font-bold shadow-2xs border border-slate-200/70' : 'text-slate-500 hover:text-slate-800' }}"
               title="Switch to English">
                EN
            </a>
        </div>

        @if($authUser && !$authUser->isCollector() && !$authUser->isTechnician())
            <!-- Live Notifications Center -->
            <div class="relative" x-data="topbarNotifications()" @click.outside="open = false">

                <button type="button"
                        @click="toggle()"
                        class="relative w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200/90 bg-white text-slate-600 hover:text-cyan-700 hover:border-cyan-300/80 hover:bg-cyan-50/40 active:scale-95 transition focus:outline-none cursor-pointer shadow-2xs"
                        title="{{ __('Notifications') }}">
                    <i class="fas fa-bell text-xs"></i>
                    <template x-if="unreadCount > 0">
                        <span class="absolute -top-1 -right-1 min-w-[16px] h-4 px-1 rounded-full bg-rose-500 text-white font-black text-[9px] font-mono flex items-center justify-center shadow-xs ring-2 ring-white animate-pulse"
                              x-text="unreadCount > 99 ? '99+' : unreadCount">
                        </span>
                    </template>
                </button>

                <!-- Notifications Dropdown Panel -->
                <div x-show="open"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 translate-y-1 scale-98"
                     x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                     x-transition:leave-end="opacity-0 translate-y-1 scale-98"
                     class="absolute right-0 mt-2 w-80 sm:w-96 bg-white rounded-2xl border border-slate-200 shadow-2xl z-50 overflow-hidden ring-1 ring-slate-900/5"
                     style="display: none;"
                     x-cloak>

                    <!-- Panel Header -->
                    <div class="px-4 py-3 bg-slate-50/90 border-b border-slate-100 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="font-extrabold text-xs text-slate-900 tracking-tight">{{ __('Notifications') }}</span>
                            <span class="px-2 py-0.5 rounded-full bg-cyan-50 text-cyan-700 border border-cyan-200 font-bold text-[9.5px] font-mono"
                                  x-text="unreadCount + ' ' + i18n.newSuffix"></span>
                        </div>
                        <button type="button"
                                @click="markAllAsRead()"
                                class="text-[10.5px] font-semibold text-cyan-700 hover:text-cyan-900 transition hover:underline cursor-pointer">
                            {{ __('Mark all as read') }}
                        </button>
                    </div>

                    <!-- Notifications List -->
                    <div class="max-h-80 overflow-y-auto divide-y divide-slate-100">
                        <template x-if="loading">
                            <div class="py-8 text-center text-slate-400 text-xs flex flex-col items-center justify-center gap-2">
                                <i class="fas fa-circle-notch fa-spin text-cyan-600 text-base"></i>
                                <span>{{ __('Loading updates...') }}</span>
                            </div>
                        </template>

                        <template x-if="!loading && notifications.length === 0">
                            <div class="py-9 text-center text-slate-400 text-xs px-4">
                                <div class="w-10 h-10 rounded-full bg-cyan-50 text-cyan-600 flex items-center justify-center mx-auto mb-2 text-sm">
                                    <i class="fas fa-check-double"></i>
                                </div>
                                <p class="font-bold text-slate-700 text-xs">{{ __('All caught up!') }}</p>
                                <span class="text-[11px] text-slate-400">{{ __('No unread alerts or tickets right now.') }}</span>
                            </div>
                        </template>

                        <template x-for="notif in notifications" :key="notif.id">
                            <div @click="markRead(notif)"
                                 class="p-3 hover:bg-slate-50/80 transition cursor-pointer flex items-start gap-3 group relative"
                                 :class="!notif.read ? 'bg-cyan-50/25' : ''">
                                <div class="w-7 h-7 rounded-lg flex-shrink-0 flex items-center justify-center text-xs font-bold"
                                     :class="notif.priority === 'urgent' ? 'bg-rose-100 text-rose-700' : (notif.type === 'ticket_reply' ? 'bg-blue-100 text-blue-700' : 'bg-cyan-100 text-cyan-700')">
                                    <i :class="notif.priority === 'urgent' ? 'fas fa-fire' : (notif.type === 'ticket_reply' ? 'fas fa-reply' : 'fas fa-ticket')"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between gap-1">
                                        <span class="font-bold text-slate-800 text-[11px] truncate group-hover:text-cyan-700 transition" x-text="notif.title"></span>
                                        <span class="text-[9.5px] text-slate-400 font-mono flex-shrink-0" x-text="notif.time_ago"></span>
                                    </div>
                                    <p class="text-[10.5px] text-slate-600 mt-0.5 line-clamp-2" x-text="notif.message"></p>
                                </div>
                                <template x-if="!notif.read">
                                    <span class="w-1.5 h-1.5 rounded-full bg-cyan-600 flex-shrink-0 mt-1"></span>
                                </template>
                            </div>
                        </template>
                    </div>

                    <!-- Panel Footer -->
                    <div class="p-2.5 border-t border-slate-100 bg-slate-50/70 text-center flex items-center justify-between px-4">
                        <a href="{{ route('tenant.tickets.index') }}" class="text-[11px] font-bold text-cyan-700 hover:text-cyan-900 transition flex items-center gap-1">
                            <span>{{ __('View Support Desk') }}</span>
                            <i class="fas fa-arrow-right text-[9px]"></i>
                        </a>
                        <a href="{{ route('tenant.tickets.escalations') }}" class="text-[11px] font-medium text-slate-500 hover:text-slate-800 transition">
                            {{ __('Escalations') }}
                        </a>
                    </div>
                </div>
            </div>
        @endif

        <!-- User Account Chip & Dropdown -->
        <div class="relative" x-data="{ profileOpen: false }">
            <button type="button"
                    @click="profileOpen = !profileOpen"
                    class="h-8 pl-1 pr-2 rounded-lg border border-slate-200/90 bg-white hover:bg-slate-50 hover:border-slate-300 flex items-center gap-1.5 transition cursor-pointer shadow-2xs">
                @if(!empty($authUser?->avatar_url))
                    <img src="{{ $authUser->avatar_url }}" alt="{{ $authUser->name ?? 'User' }}" class="w-6 h-6 rounded-md object-cover flex-shrink-0">
                @else
                    <div class="w-6 h-6 rounded-md bg-gradient-to-br from-cyan-600 to-blue-600 text-white flex items-center justify-center font-bold text-[11px] flex-shrink-0 shadow-2xs">
                        {{ $avatarInitial }}
                    </div>
                @endif
                <span class="hidden md:inline text-[11px] font-semibold text-slate-700 max-w-[85px] truncate">
                    {{ $firstName }}
                </span>
                <i class="fas fa-chevron-down text-[9px] text-slate-400 transition-transform" :class="profileOpen ? 'rotate-180 text-cyan-600' : ''"></i>
            </button>

            <!-- Profile Dropdown Menu -->
            <div x-show="profileOpen"
                 @click.outside="profileOpen = false"
                 x-transition:enter="transition ease-out duration-100"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-75"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="absolute right-0 mt-2 w-60 bg-white rounded-xl border border-slate-200 shadow-xl p-2 z-50 text-xs space-y-1 ring-1 ring-slate-900/5"
                 style="display: none;"
                 x-cloak>
                <div class="px-2.5 py-2 bg-slate-50 rounded-lg border border-slate-100 flex items-center gap-2.5">
                    @if(!empty($authUser?->avatar_url))
                        <img src="{{ $authUser->avatar_url }}" alt="{{ $authUser->name ?? 'User' }}" class="w-9 h-9 rounded-lg object-cover shadow-2xs flex-shrink-0 border border-slate-200">
                    @else
                        <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-cyan-600 to-blue-600 text-white flex items-center justify-center font-bold text-xs shadow-2xs flex-shrink-0">
                            {{ $avatarInitial }}
                        </div>
                    @endif
                    <div class="min-w-0 flex-1">
                        <div class="font-bold text-slate-900 truncate text-[11.5px] leading-tight">{{ $authUser->name ?? 'Administrator' }}</div>
                        <div class="text-[10px] text-slate-500 truncate leading-tight mt-0.5">{{ $authUser->email ?? '' }}</div>
                        <span class="inline-block mt-1 px-1.5 py-0.5 rounded text-[9px] font-semibold bg-cyan-50 text-cyan-700 border border-cyan-200/70">
                            {{ __($authUser?->role_badge['label'] ?? 'ISP Staff') }}
                        </span>
                    </div>
                </div>

                <a href="{{ route('tenant.profile') }}"
                   class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg hover:bg-slate-50 text-slate-700 font-medium transition">
                    <i class="fas fa-user-gear w-4 text-slate-400 text-[11px]"></i>
                    <span>{{ __('My Profile') }}</span>
                </a>

                @if($authUser && !$authUser->isCollector() && !$authUser->isTechnician())
                    <a href="{{ route('tenant.billing.dashboard') }}" class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg hover:bg-slate-50 text-slate-700 font-medium transition">
                        <i class="fas fa-credit-card w-4 text-slate-400 text-[11px]"></i>
                        <span>{{ __('SaaS Subscription') }}</span>
                    </a>

                    @if($canViewTickets)
                    <a href="{{ route('tenant.tickets.index') }}" class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg hover:bg-slate-50 text-slate-700 font-medium transition">
                        <i class="fas fa-life-ring w-4 text-slate-400 text-[11px]"></i>
                        <span>{{ __('Support Desk') }}</span>
                    </a>
                    @endif
                @endif

                <div class="border-t border-slate-100 my-1"></div>

                <form action="{{ route('tenant.logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-rose-600 hover:bg-rose-50 font-semibold transition cursor-pointer text-left">
                        <i class="fas fa-power-off w-4 text-[11px]"></i>
                        <span>{{ __('Sign Out') }}</span>
                    </button>
                </form>
            </div>
        </div>

    </div>

</header>

@once
@push('scripts')
<script>
    window.topbarQuickSearch = function () {
        return {
            query: @json(request()->routeIs('tenant.customers*') ? (string) request('search', '') : ''),
            open: false,
            loading: false,
            results: [],
            totalCount: 0,
            selectedIndex: -1,
            debounceTimer: null,
            abortController: null,
            endpoint: @json(route('tenant.customers.quick-search')),
            indexUrl: @json(route('tenant.customers.index')),
            i18n: {
                viewAllCount: @json(__('View all :count results')),
                viewAll: @json(__('View all results'))
            },
            onFocus() {
                if (this.query.trim().length > 0) {
                    this.open = true;
                    if (this.results.length === 0 && !this.loading) {
                        this.fetchResults();
                    }
                }
            },
            onInput() {
                this.selectedIndex = -1;
                const trimmed = this.query.trim();
                clearTimeout(this.debounceTimer);
                if (!trimmed) {
                    this.open = false;
                    this.results = [];
                    this.totalCount = 0;
                    this.loading = false;
                    if (this.abortController) {
                        this.abortController.abort();
                    }
                    return;
                }
                this.open = true;
                this.loading = true;
                this.debounceTimer = setTimeout(() => {
                    this.fetchResults();
                }, 220);
            },
            async fetchResults() {
                const q = this.query.trim();
                if (!q) return;

                if (this.abortController) {
                    this.abortController.abort();
                }
                this.abortController = new AbortController();
                this.loading = true;

                try {
                    const url = this.endpoint + '?q=' + encodeURIComponent(q);
                    const res = await fetch(url, {
                        signal: this.abortController.signal,
                        headers: { 'Accept': 'application/json' }
                    });
                    if (res.ok) {
                        const data = await res.json();
                        this.results = data.results || [];
                        this.totalCount = data.total || 0;
                    }
                } catch (e) {
                    if (e.name !== 'AbortError') {
                        console.error('Quick search failed:', e);
                    }
                } finally {
                    this.loading = false;
                }
            },
            moveSelection(delta) {
                if (!this.open || this.results.length === 0) return;
                const next = this.selectedIndex + delta;
                if (next < 0) {
                    this.selectedIndex = this.results.length - 1;
                } else if (next >= this.results.length) {
                    this.selectedIndex = 0;
                } else {
                    this.selectedIndex = next;
                }
            },
            closeDropdown() {
                this.open = false;
                this.selectedIndex = -1;
                this.$refs.quickSearch?.blur();
            },
            clear() {
                this.query = '';
                this.results = [];
                this.totalCount = 0;
                this.selectedIndex = -1;
                this.open = false;
                this.loading = false;
                if (this.abortController) {
                    this.abortController.abort();
                }
                this.$nextTick(() => this.$refs.quickSearch?.focus());
            },
            fullSearchUrl() {
                return this.indexUrl + '?search=' + encodeURIComponent(this.query.trim());
            }
        };
    };

    window.topbarNotifications = function () {
        return {
            open: false,
            loading: false,
            unreadCount: 0,
            notifications: [],
            knownIds: new Set(),
            i18n: {
                newAlert: @json(__('New Support Alert')),
                viewBtn: @json(__('View')),
                newSuffix: @json(__('new'))
            },
            async init() {
                if ('Notification' in window && Notification.permission === 'default') {
                    setTimeout(() => {
                        Notification.requestPermission();
                    }, 2500);
                }

                await this.loadNotifications(true);

                setInterval(() => {
                    this.loadNotifications(false);
                }, 6000);
            },
            playNotificationSound() {
                try {
                    const AudioContext = window.AudioContext || window.webkitAudioContext;
                    if (!AudioContext) return;
                    const ctx = new AudioContext();
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();

                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(587.33, ctx.currentTime);
                    osc.frequency.setValueAtTime(880, ctx.currentTime + 0.1);

                    gain.gain.setValueAtTime(0.15, ctx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.4);

                    osc.connect(gain);
                    gain.connect(ctx.destination);

                    osc.start(ctx.currentTime);
                    osc.stop(ctx.currentTime + 0.4);
                } catch (e) {}
            },
            sendDesktopPush(title, message, link) {
                if ('Notification' in window && Notification.permission === 'granted') {
                    try {
                        const n = new Notification(title, {
                            body: message,
                            icon: '/favicon.ico',
                            badge: '/favicon.ico'
                        });
                        n.onclick = () => {
                            window.focus();
                            if (link && link !== '#') {
                                window.location.href = link;
                            }
                        };
                    } catch (e) {}
                }
            },
            showLiveToast(notif) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'info',
                        title: notif.title || this.i18n.newAlert,
                        text: notif.message || '',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: true,
                        confirmButtonText: this.i18n.viewBtn,
                        timer: 7000,
                        timerProgressBar: true,
                        customClass: {
                            confirmButton: 'bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs px-2.5 py-1 rounded-md cursor-pointer'
                        },
                        buttonsStyling: false
                    }).then((res) => {
                        if (res.isConfirmed && notif.link && notif.link !== '#') {
                            this.markRead(notif);
                        }
                    });
                }
            },
            async loadNotifications(isFirst = false) {
                try {
                    const res = await fetch('{{ route('tenant.notifications.unread') }}');
                    if (res.ok) {
                        const data = await res.json();
                        const newNotifs = data.notifications || [];
                        const newUnreadCount = data.unread_count || 0;

                        if (!isFirst && this.knownIds.size > 0) {
                            const freshNotifs = newNotifs.filter(n => !this.knownIds.has(n.id) && !n.read);
                            if (freshNotifs.length > 0) {
                                const latest = freshNotifs[0];
                                this.playNotificationSound();
                                this.sendDesktopPush(latest.title, latest.message, latest.link);
                                this.showLiveToast(latest);
                            }
                        }

                        newNotifs.forEach(n => this.knownIds.add(n.id));
                        this.notifications = newNotifs;
                        this.unreadCount = newUnreadCount;
                    }
                } catch (e) {
                    console.error('Failed to load notifications:', e);
                }
            },
            toggle() {
                this.open = !this.open;
                if (this.open) {
                    if ('Notification' in window && Notification.permission === 'default') {
                        Notification.requestPermission();
                    }
                    this.loadNotifications(false);
                }
            },
            async markRead(notif) {
                try {
                    await fetch('/admin/notifications/' + notif.id + '/read', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    });
                    notif.read = true;
                    this.unreadCount = Math.max(0, this.unreadCount - 1);
                    if (notif.link && notif.link !== '#') {
                        window.location.href = notif.link;
                    }
                } catch (e) {
                    console.error('Failed to mark read:', e);
                }
            },
            async markAllAsRead() {
                try {
                    await fetch('{{ route('tenant.notifications.mark-all-read') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    });
                    this.unreadCount = 0;
                    this.notifications.forEach(n => n.read = true);
                } catch (e) {
                    console.error('Failed to mark all read:', e);
                }
            }
        };
    };
</script>
@endpush
@endonce
