@php
    $tenantName = $tenant->company_name ?? ($tenant->name ?? 'ISP Portal');
    $planName = $tenant->plan->name ?? 'Carrier Plan';
    $tenantSlug = $tenant->slug ?? 'tenant';
    $isActiveTenant = ($tenant->status ?? 'active') === 'active';
    $remainingDays = $daysRemaining ?? null;
@endphp
<!-- Professional Sticky Top Navbar for ISP Tenant Portal -->
<header class="h-14 bg-white/95 backdrop-blur-md border-b border-slate-200 px-3.5 sm:px-6 flex items-center justify-between sticky top-0 z-40 shadow-2xs">
    
    <!-- Left Section: Mobile Drawer Toggle & Breadcrumb / Company Info -->
    <div class="flex items-center gap-3">
        <button type="button" 
                @click="mobileSidebar = true" 
                class="lg:hidden p-1.5 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 transition cursor-pointer" 
                title="Toggle Menu">
            <i class="fas fa-bars text-sm"></i>
        </button>

        <div class="flex items-center gap-2">
            <span class="font-bold text-slate-900 text-xs sm:text-sm leading-tight truncate max-w-[180px] sm:max-w-xs">
                {{ $tenantName }}
            </span>
            <span class="hidden sm:inline-flex items-center px-2 py-0.5 rounded text-[10px] font-mono font-semibold {{ $isActiveTenant ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                {{ $planName }}
            </span>
        </div>
    </div>

    <!-- Right Section: Impersonation Notice & Actions -->
    <div class="flex items-center gap-2">

        <!-- Super Admin Impersonation Exit Button -->
        @if(session()->has('impersonator_id'))
            <a href="{{ route('owner.impersonate.leave') }}" 
               class="px-2.5 py-1 rounded-lg bg-amber-500 hover:bg-amber-600 text-white font-bold text-[11px] shadow-xs flex items-center gap-1.5 transition cursor-pointer animate-pulse">
                <i class="fas fa-sign-out-alt"></i>
                <span class="hidden sm:inline">Exit Impersonation</span>
                <span class="sm:hidden">Exit</span>
            </a>
        @endif

        <!-- Language Switcher Pill (Bilingual বাংলা ⇄ EN) -->
        @php
            $currentLocale = app()->getLocale();
            $authUser = auth()->user();
        @endphp
        <div class="flex items-center bg-slate-100 p-0.5 rounded-lg border border-slate-200/80 text-[11px] font-semibold">
            <a href="{{ route('locale.switch', 'bn') }}" 
               class="px-2 py-1 rounded-md transition flex items-center gap-1 {{ $currentLocale === 'bn' ? 'bg-white text-purple-700 font-bold shadow-xs border border-slate-200/60' : 'text-slate-500 hover:text-slate-800' }}"
               title="বাংলায় দেখুন">
                <span>বাংলা</span>
            </a>
            <a href="{{ route('locale.switch', 'en') }}" 
               class="px-2 py-1 rounded-md transition flex items-center gap-1 {{ $currentLocale === 'en' ? 'bg-white text-purple-700 font-bold shadow-xs border border-slate-200/60' : 'text-slate-500 hover:text-slate-800' }}"
               title="Switch to English">
                <span>EN</span>
            </a>
        </div>

        @if($authUser && !$authUser->isCollector() && !$authUser->isTechnician())
        <!-- Quick Action: Billing Invoices -->
        <a href="{{ route('tenant.billing.invoices') }}" 
           class="hidden md:inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs shadow-2xs transition">
            <i class="fas fa-file-invoice text-slate-500 text-[10px]"></i>
            <span>Invoices</span>
        </a>

        <!-- Quick Action: Support -->
        <a href="{{ route('tenant.tickets.index') }}" 
           class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 font-semibold text-xs transition">
            <i class="fas fa-headset text-[10px]"></i>
            <span>Support</span>
        </a>

        <!-- Live Notifications Bell Dropdown -->
        <div class="relative" x-data="{
            open: false,
            loading: false,
            unreadCount: 0,
            notifications: [],
            knownIds: new Set(),
            async init() {
                // Request desktop push notification permission if default
                if ('Notification' in window && Notification.permission === 'default') {
                    setTimeout(() => {
                        Notification.requestPermission();
                    }, 2500);
                }

                await this.loadNotifications(true);

                // High-frequency reactive live push polling (every 6 seconds)
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
                    osc.frequency.setValueAtTime(587.33, ctx.currentTime); // D5
                    osc.frequency.setValueAtTime(880, ctx.currentTime + 0.1); // A5
                    
                    gain.gain.setValueAtTime(0.15, ctx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.4);
                    
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    
                    osc.start(ctx.currentTime);
                    osc.stop(ctx.currentTime + 0.4);
                } catch (e) {
                    // Audio autoplay policy fallback
                }
            },
            sendDesktopPush(title, message, link) {
                if ('Notification' in window) {
                    if (Notification.permission === 'granted') {
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
                }
            },
            showLiveToast(notif) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'info',
                        title: notif.title || 'New Support Alert',
                        text: notif.message || '',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: true,
                        confirmButtonText: 'View',
                        timer: 7000,
                        timerProgressBar: true,
                        customClass: {
                            confirmButton: 'bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs px-2.5 py-1 rounded-md cursor-pointer'
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
                    class="relative p-2 rounded-lg text-slate-500 hover:text-blue-700 hover:bg-blue-50 active:scale-95 transition focus:outline-none cursor-pointer"
                    title="Notifications">
                <i class="fas fa-bell text-sm"></i>
                <template x-if="unreadCount > 0">
                    <span class="absolute top-1 right-0.5 min-w-[17px] h-4 px-1 rounded-full bg-rose-500 text-white font-black text-[9px] font-mono flex items-center justify-center shadow-xs ring-2 ring-white animate-pulse" 
                          x-text="unreadCount > 99 ? '99+' : unreadCount">
                    </span>
                </template>
            </button>

            <!-- Dropdown Flyout Panel -->
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
                <div class="px-4 py-3 bg-slate-50/80 border-b border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="font-extrabold text-xs text-slate-900 tracking-tight">Notifications</span>
                        <span class="px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 border border-blue-200 font-bold text-[9.5px] font-mono" 
                              x-text="unreadCount + ' new'"></span>
                    </div>
                    <button type="button" 
                            @click="markAllAsRead()" 
                            class="text-[10.5px] font-semibold text-blue-600 hover:text-blue-800 transition hover:underline cursor-pointer">
                        Mark all as read
                    </button>
                </div>

                <!-- Notifications List -->
                <div class="max-h-80 overflow-y-auto divide-y divide-slate-100">
                    <template x-if="loading">
                        <div class="py-8 text-center text-slate-400 text-xs flex flex-col items-center justify-center gap-2">
                            <i class="fas fa-circle-notch fa-spin text-blue-600 text-base"></i>
                            <span>Loading updates...</span>
                        </div>
                    </template>

                    <template x-if="!loading && notifications.length === 0">
                        <div class="py-9 text-center text-slate-400 text-xs px-4">
                            <div class="w-10 h-10 rounded-full bg-blue-50 text-blue-500 flex items-center justify-center mx-auto mb-2 text-sm">
                                <i class="fas fa-check-double"></i>
                            </div>
                            <p class="font-bold text-slate-700 text-xs">All caught up!</p>
                            <span class="text-[11px] text-slate-400">No unread alerts or tickets right now.</span>
                        </div>
                    </template>

                    <template x-for="notif in notifications" :key="notif.id">
                        <div @click="markRead(notif)" 
                             class="p-3 hover:bg-slate-50/80 transition cursor-pointer flex items-start gap-3 group relative"
                             :class="!notif.read ? 'bg-blue-50/30' : ''">
                            <div class="w-7 h-7 rounded-lg flex-shrink-0 flex items-center justify-center text-xs font-bold"
                                 :class="notif.priority === 'urgent' ? 'bg-rose-100 text-rose-700' : (notif.type === 'ticket_reply' ? 'bg-indigo-100 text-indigo-700' : 'bg-blue-100 text-blue-700')">
                                <i :class="notif.priority === 'urgent' ? 'fas fa-fire' : (notif.type === 'ticket_reply' ? 'fas fa-reply' : 'fas fa-ticket')"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-1">
                                    <span class="font-bold text-slate-800 text-[11px] truncate group-hover:text-blue-700 transition" x-text="notif.title"></span>
                                    <span class="text-[9.5px] text-slate-400 font-mono flex-shrink-0" x-text="notif.time_ago"></span>
                                </div>
                                <p class="text-[10.5px] text-slate-600 mt-0.5 line-clamp-2" x-text="notif.message"></p>
                            </div>
                            <template x-if="!notif.read">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-600 flex-shrink-0 mt-1"></span>
                            </template>
                        </div>
                    </template>
                </div>

                <!-- Panel Footer -->
                <div class="p-2.5 border-t border-slate-100 bg-slate-50/70 text-center flex items-center justify-between px-4">
                    <a href="{{ route('tenant.tickets.index') }}" class="text-[11px] font-bold text-blue-600 hover:text-blue-800 transition flex items-center gap-1">
                        <span>View Support Desk</span>
                        <i class="fas fa-arrow-right text-[9px]"></i>
                    </a>
                    <a href="{{ route('tenant.tickets.escalations') }}" class="text-[11px] font-medium text-slate-500 hover:text-slate-800 transition">
                        Escalations
                    </a>
                </div>
            </div>
        </div>
        @endif

        <!-- User Profile Dropdown -->
        <div class="relative ml-1" x-data="{ profileOpen: false }">
            <button type="button" 
                    @click="profileOpen = !profileOpen" 
                    class="flex items-center gap-1.5 p-1 rounded-full hover:bg-slate-50 transition cursor-pointer">
                @if(!empty($authUser?->avatar_url))
                    <img src="{{ $authUser->avatar_url }}" alt="{{ $authUser->name ?? 'User' }}" class="w-7 h-7 rounded-full object-cover ring-1.5 ring-purple-200 shadow-xs">
                @else
                    <div class="w-7 h-7 rounded-full bg-gradient-to-tr from-purple-600 to-indigo-600 text-white flex items-center justify-center font-bold text-xs shadow-xs">
                        {{ strtoupper(substr($authUser->name ?? 'A', 0, 1)) }}
                    </div>
                @endif
                <i class="fas fa-chevron-down text-[9px] text-slate-400 transition-transform" :class="profileOpen ? 'rotate-180' : ''"></i>
            </button>

            <!-- Dropdown Menu -->
            <div x-show="profileOpen" 
                 @click.outside="profileOpen = false"
                 x-transition:enter="transition ease-out duration-100"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-75"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="absolute right-0 mt-2 w-56 bg-white rounded-xl border border-slate-200 shadow-xl p-2 z-50 text-xs space-y-1"
                 style="display: none;"
                 x-cloak>
                <div class="px-2.5 py-2 bg-slate-50 rounded-lg border border-slate-100 flex items-center gap-2.5">
                    @if(!empty($authUser?->avatar_url))
                        <img src="{{ $authUser->avatar_url }}" alt="{{ $authUser->name ?? 'User' }}" class="w-9 h-9 rounded-full object-cover ring-1.5 ring-purple-200 shadow-xs flex-shrink-0">
                    @else
                        <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-purple-600 to-indigo-600 text-white flex items-center justify-center font-bold text-xs shadow-xs flex-shrink-0">
                            {{ strtoupper(substr($authUser->name ?? 'A', 0, 1)) }}
                        </div>
                    @endif
                    <div class="min-w-0 flex-1">
                        <div class="font-bold text-slate-900 truncate text-[11.5px] leading-tight">{{ $authUser->name ?? 'Administrator' }}</div>
                        <div class="text-[10px] text-slate-500 truncate leading-tight mt-0.5">{{ $authUser->email ?? '' }}</div>
                        <span class="inline-block mt-1 px-1.5 py-0.2 rounded text-[9px] font-mono font-bold bg-purple-50 text-purple-700">
                            {{ strtoupper($authUser->role ?? 'ISP_ADMIN') }}
                        </span>
                    </div>
                </div>

                @if($authUser && ($authUser->isCollector() || $authUser->isTechnician()))
                    <a href="{{ route('tenant.profile') }}" 
                       class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg hover:bg-slate-50 text-slate-700 font-medium transition">
                        <i class="fas fa-user-gear w-4 text-slate-400"></i>
                        <span>{{ __('My Profile') }}</span>
                    </a>
                @else
                    <a href="{{ route('tenant.billing.dashboard') }}" class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg hover:bg-slate-50 text-slate-700 font-medium transition">
                        <i class="fas fa-credit-card w-4 text-slate-400"></i>
                        <span>{{ __('SaaS Subscription') }}</span>
                    </a>

                    <a href="{{ route('tenant.tickets.index') }}" class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg hover:bg-slate-50 text-slate-700 font-medium transition">
                        <i class="fas fa-life-ring w-4 text-slate-400"></i>
                        <span>{{ __('Support Desk') }}</span>
                    </a>
                @endif

                <div class="border-t border-slate-100 my-1"></div>

                <form action="{{ route('tenant.logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-rose-600 hover:bg-rose-50 font-semibold transition cursor-pointer text-left">
                        <i class="fas fa-power-off w-4"></i>
                        <span>{{ __('Sign Out') }}</span>
                    </button>
                </form>
            </div>
        </div>

    </div>

</header>
