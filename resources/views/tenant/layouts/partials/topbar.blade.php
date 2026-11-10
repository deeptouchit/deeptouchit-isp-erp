@php
    $authUser = auth()->user();
    $tenantName = $tenant->company_name ?? ($tenant->name ?? 'ISP Portal');
    $planName = $tenant->plan->name ?? 'Carrier Plan';
    $isActiveTenant = ($tenant->status ?? 'active') === 'active';
    $currentLocale = app()->getLocale();

    $canViewCustomers = $authUser && !$authUser->isCollector() && !$authUser->isTechnician()
        && ($authUser->isOwner() || $authUser->isIspAdmin() || $authUser->hasPermission('customers.view'));
    $canViewCustomerInvoices = $authUser && !$authUser->isCollector() && !$authUser->isTechnician()
        && Route::has('tenant.finance.invoices')
        && ($authUser->isOwner() || $authUser->isIspAdmin() || $authUser->hasPermission('billing.invoices'));
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
        <!-- Global Customer Quick Search (Shortcut: /) -->
        <form action="{{ route('tenant.customers.index') }}"
              method="GET"
              class="hidden lg:flex items-center relative ml-1.5"
              x-data
              @keydown.window="if ($event.key === '/' && !$event.ctrlKey && !$event.metaKey && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName)) { $event.preventDefault(); $refs.quickSearch.focus(); }">
            <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-[10px] pointer-events-none"></i>
            <input x-ref="quickSearch"
                   type="search"
                   name="search"
                   value="{{ request()->routeIs('tenant.customers*') ? request('search', '') : '' }}"
                   placeholder="{{ __('Search customer (ID, Phone, PPPoE)...') }}"
                   autocomplete="off"
                   class="h-8 w-52 xl:w-64 pl-7 pr-8 rounded-lg bg-slate-50/90 border border-slate-200/90 text-[11px] text-slate-700 placeholder:text-slate-400 focus:bg-white focus:outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/15 transition shadow-2xs">
            <kbd class="hidden xl:inline-flex items-center justify-center h-4 min-w-[16px] px-1 text-[9px] font-mono font-semibold text-slate-400 bg-white border border-slate-200 rounded absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none">/</kbd>
        </form>
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
            <!-- Quick Action: Due Customer Invoices (fallback SaaS Invoices) -->
            <a href="{{ $canViewCustomerInvoices ? route('tenant.finance.invoices', ['status' => 'due']) : route('tenant.billing.invoices') }}"
               class="hidden md:inline-flex h-8 items-center gap-1.5 px-2.5 rounded-lg border border-slate-200/90 bg-white hover:bg-cyan-50/40 hover:border-cyan-300/80 text-slate-700 hover:text-cyan-800 font-semibold text-[11.5px] shadow-2xs transition whitespace-nowrap">
                <i class="fas fa-file-invoice-dollar text-cyan-600 text-[11px]"></i>
                <span>{{ $canViewCustomerInvoices ? __('Due Invoices') : __('Invoices') }}</span>
            </a>

            @if($canViewTickets)
            <!-- Quick Action: Support Desk -->
            <a href="{{ route('tenant.tickets.index') }}"
               class="hidden sm:inline-flex h-8 items-center gap-1.5 px-2.5 rounded-lg bg-cyan-50/80 hover:bg-cyan-100/80 border border-cyan-200/70 text-cyan-800 font-semibold text-[11.5px] transition whitespace-nowrap shadow-2xs">
                <i class="fas fa-headset text-cyan-600 text-[11px]"></i>
                <span>{{ __('Support') }}</span>
            </a>
            @endif

            <!-- Live Notifications Center -->
            <div class="relative" x-data="{
                open: false,
                loading: false,
                unreadCount: 0,
                notifications: [],
                knownIds: new Set(),
                i18n: {
                    newAlert: {!! json_encode(__('New Support Alert'), JSON_HEX_APOS) !!},
                    viewBtn: {!! json_encode(__('View'), JSON_HEX_APOS) !!},
                    newSuffix: {!! json_encode(__('new'), JSON_HEX_APOS) !!}
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
            }" @click.outside="open = false">

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
