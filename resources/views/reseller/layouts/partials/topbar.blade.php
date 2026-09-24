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
    $availBal = (float)($currentReseller->total_available_balance ?? ($currentReseller->wallet_balance ?? 0));
@endphp

<!-- Professional Sticky Topbar for Reseller Portal -->
<header class="h-14 bg-white/95 backdrop-blur-md border-b border-slate-200 px-3.5 sm:px-6 flex items-center justify-between sticky top-0 z-40 shadow-2xs">
    
    <!-- Left Section: Mobile Drawer Toggle & Wallet Balance Badge -->
    <div class="flex items-center gap-2.5 sm:gap-3.5">
        <button type="button" 
                @click="mobileSidebar = true" 
                class="lg:hidden p-1.5 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 transition cursor-pointer" 
                title="Toggle Menu">
            <i class="fas fa-bars text-sm"></i>
        </button>

        <!-- Wallet Balance Badge in Top Header (Reseller Admin Only) -->
        @if($authUser && $authUser->isResellerAdmin())
            <a href="{{ route('reseller.recharge.index') }}" 
               title="{{ __('Wallet Balance') }} - {{ __('Balance Recharge') }}" 
               class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-50 hover:bg-purple-50 border border-slate-200/90 hover:border-purple-200 transition shadow-2xs group cursor-pointer">
                <div class="w-5 h-5 rounded-md bg-purple-100 text-purple-700 flex items-center justify-center text-[10px] flex-shrink-0 group-hover:bg-purple-600 group-hover:text-white transition">
                    <i class="fas fa-wallet"></i>
                </div>
                <div class="flex items-center gap-1 text-xs">
                    <span class="text-[10px] uppercase font-semibold text-slate-500 hidden sm:inline">{{ __('Balance') }}:</span>
                    <span class="font-bold font-mono text-purple-900 group-hover:text-purple-700">@currency($availBal)</span>
                </div>
                <span class="inline-flex items-center justify-center w-3.5 h-3.5 rounded text-[8px] text-purple-600 bg-purple-100 group-hover:bg-purple-600 group-hover:text-white transition ml-0.5">
                    <i class="fas fa-plus"></i>
                </span>
            </a>
        @elseif($authUser && $authUser->isResellerCollector())
            <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-amber-50/80 border border-amber-200/90 shadow-2xs">
                <div class="w-5 h-5 rounded-md bg-amber-100 text-amber-700 flex items-center justify-center text-[10px] flex-shrink-0">
                    <i class="fas fa-hand-holding-dollar"></i>
                </div>
                <div class="text-xs font-semibold text-amber-900">
                    <span>{{ __('Bill Collector') }}</span>
                </div>
            </div>
        @endif
    </div>

    <!-- Right Section: Actions -->
    <div class="flex items-center gap-2">

        <!-- Language Switcher Pill (Bilingual বাংলা ⇄ EN) -->
        @php
            $currentLocale = app()->getLocale();
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

        <!-- Dedicated Support Tickets & Messages and Notifications (Reseller Admin Only) -->
        @if($authUser && $authUser->isResellerAdmin())
        <!-- Dedicated Support Tickets & Messages Dropdown -->
        <div class="relative" x-data="{
            open: false,
            loading: false,
            unreadCount: 0,
            totalOpen: 0,
            tickets: [],
            lastKnownAnswered: 0,
            timer: null,
            init() {
                this.loadMessages(true);
                // Polling for live support ticket updates every 10 seconds
                this.timer = setInterval(() => {
                    if (window.__isPageUnloading) return;
                    this.loadMessages(false);
                }, 10000);
            },
            async loadMessages(isFirst = false) {
                if (window.__isPageUnloading) return;
                try {
                    const res = await fetch('{{ route('reseller.tickets.topbar-messages') }}', {
                        headers: { 'Accept': 'application/json' }
                    });
                    if (res.ok) {
                        const data = await res.json();
                        const prevAnswered = this.unreadCount;
                        this.unreadCount = data.unread_count || 0;
                        this.totalOpen = data.total_open || 0;
                        this.tickets = data.tickets || [];

                        // If new reply received from Host ISP
                        if (!isFirst && this.unreadCount > prevAnswered) {
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    icon: 'info',
                                    title: 'Support Ticket Reply',
                                    text: 'Host ISP has replied to your support ticket.',
                                    toast: true,
                                    position: 'top-end',
                                    showConfirmButton: true,
                                    confirmButtonText: 'View Inbox',
                                    timer: 6000,
                                    timerProgressBar: true,
                                    customClass: {
                                        confirmButton: 'bg-purple-600 hover:bg-purple-700 text-white font-semibold text-xs px-2.5 py-1 rounded-md cursor-pointer'
                                    },
                                    buttonsStyling: false
                                }).then((res) => {
                                    if (res.isConfirmed) {
                                        window.location.href = '{{ route('reseller.tickets.index') }}';
                                    }
                                });
                            }
                        }
                    }
                } catch (e) {
                    // Suppress network abort errors when page is refreshing or navigating
                    if (!window.__isPageUnloading && e.name !== 'AbortError') {
                        // Silent fail
                    }
                }
            },
            toggle() {
                this.open = !this.open;
                if (this.open) {
                    this.loadMessages(false);
                }
            }
        }" @click.outside="open = false">
            
            <button type="button" 
                    @click="toggle()" 
                    class="relative p-2 rounded-lg text-slate-500 hover:text-purple-700 hover:bg-purple-50 active:scale-95 transition focus:outline-none cursor-pointer"
                    title="Support Messages & Tickets">
                <i class="fas fa-headset text-sm"></i>
                <template x-if="unreadCount > 0">
                    <span class="absolute top-1 right-0.5 min-w-[17px] h-4 px-1 rounded-full bg-emerald-600 text-white font-black text-[9px] font-mono flex items-center justify-center shadow-xs ring-2 ring-white animate-pulse" 
                          x-text="unreadCount > 99 ? '99+' : unreadCount">
                    </span>
                </template>
                <template x-if="unreadCount === 0 && totalOpen > 0">
                    <span class="absolute top-1.5 right-1 w-2 h-2 rounded-full bg-cyan-500 ring-2 ring-white"></span>
                </template>
            </button>

            <!-- Support Messages Dropdown Flyout Panel -->
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
                        <i class="fas fa-headset text-purple-600 text-xs"></i>
                        <span class="font-extrabold text-xs text-slate-900 tracking-tight">{{ __('Support Messages') }}</span>
                        <template x-if="unreadCount > 0">
                            <span class="px-1.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-bold text-[9px] font-mono" 
                                  x-text="unreadCount + ' {{ __('Answered') }}'">
                            </span>
                        </template>
                        <template x-if="unreadCount === 0 && totalOpen > 0">
                            <span class="px-1.5 py-0.5 rounded-full bg-slate-200 text-slate-700 font-semibold text-[9px] font-mono" 
                                  x-text="totalOpen + ' {{ __('Open') }}'">
                            </span>
                        </template>
                    </div>

                    <a href="{{ route('reseller.tickets.index') }}" 
                       class="text-[11px] font-semibold text-purple-700 hover:text-purple-900 hover:underline inline-flex items-center gap-1 transition">
                        <span>{{ __('All Tickets') }}</span>
                        <i class="fas fa-chevron-right text-[9px]"></i>
                    </a>
                </div>

                <!-- Messages List Body -->
                <div class="max-h-80 overflow-y-auto divide-y divide-slate-100 divide-dashed">
                    <template x-if="tickets.length === 0">
                        <div class="py-10 px-4 text-center">
                            <div class="w-10 h-10 rounded-full bg-purple-50 text-purple-500 flex items-center justify-center mx-auto mb-2 text-sm border border-purple-100">
                                <i class="fas fa-comments"></i>
                            </div>
                            <p class="text-xs font-semibold text-slate-700">{{ __('No active support messages') }}</p>
                            <p class="text-[11px] text-slate-400 mt-0.5">{{ __('Need help? Contact Host ISP NOC') }}</p>
                            <a href="{{ route('reseller.tickets.index') }}" class="mt-3 inline-flex items-center gap-1.5 px-3 py-1 bg-purple-600 hover:bg-purple-700 text-white font-semibold text-xs rounded-lg shadow-xs transition">
                                <i class="fas fa-plus text-[9px]"></i>
                                <span>{{ __('Create Ticket') }}</span>
                            </a>
                        </div>
                    </template>

                    <template x-for="t in tickets" :key="t.id">
                        <a :href="t.url" 
                           class="block px-4 py-3 hover:bg-slate-50/80 transition cursor-pointer group"
                           :class="t.is_answered ? 'bg-emerald-50/20' : ''">
                            <div class="flex items-start justify-between gap-2 mb-1">
                                <div class="flex items-center gap-1.5">
                                    <span class="px-1.5 py-0.5 rounded text-[9.5px] font-mono font-bold bg-purple-50 text-purple-700 border border-purple-200" 
                                          x-text="'#' + t.ticket_number"></span>
                                    <span class="text-[10px] text-slate-400 font-medium" x-text="t.department"></span>
                                </div>
                                
                                <div class="flex items-center gap-1.5">
                                    <template x-if="t.status === 'answered'">
                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-emerald-100 text-emerald-800 uppercase">Answered</span>
                                    </template>
                                    <template x-if="t.status === 'open'">
                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-cyan-100 text-cyan-800 uppercase">Open</span>
                                    </template>
                                    <template x-if="t.status === 'in_progress'">
                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-100 text-amber-800 uppercase">Progress</span>
                                    </template>
                                    <template x-if="t.status === 'closed' || t.status === 'resolved'">
                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-slate-100 text-slate-600 uppercase">Closed</span>
                                    </template>
                                    <span class="text-[10px] font-mono text-slate-400 whitespace-nowrap" x-text="t.time_ago"></span>
                                </div>
                            </div>

                            <h4 class="text-xs font-bold text-slate-800 group-hover:text-purple-700 transition truncate" x-text="t.subject"></h4>

                            <div class="mt-1 flex items-center gap-1.5 text-[11px] text-slate-500">
                                <span class="px-1 py-0.2 rounded text-[9px] font-semibold" 
                                      :class="t.is_from_host ? 'bg-indigo-50 text-indigo-700 border border-indigo-100' : 'bg-slate-100 text-slate-600'" 
                                      x-text="t.sender_name"></span>
                                <span class="truncate" x-text="t.last_message"></span>
                            </div>
                        </a>
                    </template>
                </div>

                <!-- Panel Footer -->
                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-100 flex items-center justify-between text-xs">
                    <a href="{{ route('reseller.tickets.index') }}" class="font-semibold text-slate-700 hover:text-purple-700 transition inline-flex items-center gap-1">
                        <i class="fas fa-ticket text-[10px] text-slate-400"></i>
                        <span>{{ __('Support Center') }}</span>
                    </a>
                    <a href="{{ route('reseller.tickets.index') }}" class="font-bold text-purple-700 hover:text-purple-900 transition inline-flex items-center gap-1">
                        <i class="fas fa-plus-circle text-[10px]"></i>
                        <span>{{ __('New Escalation') }}</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Live Notifications Bell Dropdown -->
        <div class="relative" x-data="{
            open: false,
            loading: false,
            unreadCount: 0,
            notifications: [],
            knownIds: new Set(),
            timer: null,
            init() {
                this.loadNotifications(true);

                // Safe polling (every 10 seconds)
                this.timer = setInterval(() => {
                    if (window.__isPageUnloading) return;
                    this.loadNotifications(false);
                }, 10000);
            },
            playNotificationSound() {
                try {
                    const AudioCtx = window.AudioContext || window.webkitAudioContext;
                    if (!AudioCtx) return;
                    if (!window.__resellerAudioCtx) {
                        window.__resellerAudioCtx = new AudioCtx();
                    }
                    if (window.__resellerAudioCtx.state === 'suspended') {
                        return; // Avoid unprompted autoplay policy error
                    }
                    const ctx = window.__resellerAudioCtx;
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
                        title: notif.title || '{{ __('New Escalation') }}',
                        text: notif.message || '',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: true,
                        confirmButtonText: '{{ __('View Details') }}',
                        timer: 7000,
                        timerProgressBar: true,
                        customClass: {
                            confirmButton: 'bg-purple-600 hover:bg-purple-700 text-white font-semibold text-xs px-2.5 py-1 rounded-md cursor-pointer'
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
                if (window.__isPageUnloading) return;
                try {
                    const res = await fetch('{{ route('reseller.notifications.unread') }}', {
                        headers: { 'Accept': 'application/json' }
                    });
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
                    if (!window.__isPageUnloading && e.name !== 'AbortError') {
                        // Silent fail
                    }
                }
            },
            toggle() {
                this.open = !this.open;
                if (this.open) {
                    if ('Notification' in window && Notification.permission === 'default') {
                        try {
                            Notification.requestPermission();
                        } catch (e) {}
                    }
                    this.loadNotifications(false);
                }
            },
            async markRead(notif) {
                try {
                    await fetch(`{{ url('/reseller/notifications') }}/${notif.id}/read`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    });
                } catch (e) {}

                notif.read = true;
                this.unreadCount = Math.max(0, this.unreadCount - 1);
                this.open = false;
                if (notif.link && notif.link !== '#') {
                    window.location.href = notif.link;
                }
            },
            async markAllAsRead() {
                try {
                    await fetch('{{ route('reseller.notifications.mark-all-read') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    });
                    this.notifications.forEach(n => n.read = true);
                    this.unreadCount = 0;
                } catch (e) {}
            }
        }" @click.outside="open = false">
            
            <button type="button" 
                    @click="open = !open" 
                    class="relative p-2 rounded-lg text-slate-500 hover:text-purple-700 hover:bg-purple-50 active:scale-95 transition focus:outline-none cursor-pointer"
                    title="Live Notifications">
                <i class="fas fa-bell text-sm"></i>
                <template x-if="unreadCount > 0">
                    <span class="absolute top-1 right-0.5 min-w-[17px] h-4 px-1 rounded-full bg-rose-600 text-white font-black text-[9px] font-mono flex items-center justify-center shadow-xs ring-2 ring-white animate-bounce" 
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
                        <span class="font-extrabold text-xs text-slate-900 tracking-tight">{{ __('Notifications') }}</span>
                        <span class="px-2 py-0.5 rounded-full bg-purple-50 text-purple-700 border border-purple-200 font-bold text-[9.5px] font-mono" 
                              x-text="unreadCount + ' new'"></span>
                    </div>
                    <button type="button" 
                            @click="markAllAsRead()" 
                            class="text-[10.5px] font-semibold text-purple-600 hover:text-purple-800 transition hover:underline cursor-pointer">
                        {{ __('Mark all as read') }}
                    </button>
                </div>

                <!-- Notifications List -->
                <div class="max-h-80 overflow-y-auto divide-y divide-slate-100">
                    <template x-if="loading">
                        <div class="py-8 text-center text-slate-400 text-xs flex flex-col items-center justify-center gap-2">
                            <i class="fas fa-circle-notch fa-spin text-purple-600 text-base"></i>
                            <span>Loading updates...</span>
                        </div>
                    </template>

                    <template x-if="!loading && notifications.length === 0">
                        <div class="py-9 text-center text-slate-400 text-xs px-4">
                            <div class="w-10 h-10 rounded-full bg-purple-50 text-purple-500 flex items-center justify-center mx-auto mb-2 text-sm">
                                <i class="fas fa-check-double"></i>
                            </div>
                            <p class="font-bold text-slate-700 text-xs">{{ __('All caught up!') }}</p>
                            <span class="text-[11px] text-slate-400">{{ __('No notifications') }}</span>
                        </div>
                    </template>

                    <template x-for="notif in notifications" :key="notif.id">
                        <div @click="markRead(notif)" 
                             class="p-3 hover:bg-slate-50/80 transition cursor-pointer flex items-start gap-3 group relative"
                             :class="!notif.read ? 'bg-purple-50/30' : ''">
                            <div class="w-7 h-7 rounded-lg flex-shrink-0 flex items-center justify-center text-xs font-bold"
                                 :class="notif.priority === 'urgent' ? 'bg-rose-100 text-rose-700' : (notif.type === 'ticket_reply' ? 'bg-indigo-100 text-indigo-700' : 'bg-purple-100 text-purple-700')">
                                <i :class="notif.priority === 'urgent' ? 'fas fa-fire' : (notif.type === 'ticket_reply' ? 'fas fa-reply' : 'fas fa-ticket')"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-1">
                                    <span class="font-bold text-slate-800 text-[11px] truncate group-hover:text-purple-700 transition" x-text="notif.title"></span>
                                    <span class="text-[9.5px] text-slate-400 font-mono flex-shrink-0" x-text="notif.time_ago"></span>
                                </div>
                                <p class="text-[10.5px] text-slate-600 mt-0.5 line-clamp-2" x-text="notif.message"></p>
                            </div>
                            <template x-if="!notif.read">
                                <span class="w-1.5 h-1.5 rounded-full bg-purple-600 flex-shrink-0 mt-1"></span>
                            </template>
                        </div>
                    </template>
                </div>

                <!-- Panel Footer -->
                <div class="p-2.5 border-t border-slate-100 bg-slate-50/70 text-center flex items-center justify-between px-4">
                    <a href="{{ route('reseller.tickets.index') }}" class="text-[11px] font-bold text-purple-600 hover:text-purple-800 transition flex items-center gap-1">
                        <span>{{ __('All Tickets') }}</span>
                        <i class="fas fa-arrow-right text-[9px]"></i>
                    </a>
                    <a href="{{ route('reseller.profile') }}" class="text-[11px] font-medium text-slate-500 hover:text-slate-800 transition">
                        {{ __('Partner Profile') }}
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
                        {{ strtoupper(substr($authUser->name ?? 'R', 0, 1)) }}
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
                            {{ strtoupper(substr($authUser->name ?? 'R', 0, 1)) }}
                        </div>
                    @endif
                    <div class="min-w-0 flex-1">
                        <div class="font-bold text-slate-900 truncate text-[11.5px] leading-tight">{{ $authUser->name ?? 'Reseller Admin' }}</div>
                        <div class="text-[10px] text-slate-500 truncate leading-tight mt-0.5">{{ $authUser->email ?? '' }}</div>
                        <span class="inline-block mt-1 px-1.5 py-0.2 rounded text-[9px] font-mono font-bold bg-purple-50 text-purple-700">
                            {{ strtoupper($authUser->role ?? 'RESELLER_ADMIN') }}
                        </span>
                    </div>
                </div>

                <a href="{{ route('reseller.profile') }}" 
                   class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg hover:bg-slate-50 text-slate-700 font-medium transition">
                    <i class="fas fa-user-gear w-4 text-slate-400"></i>
                    <span>{{ __('My Profile') }}</span>
                </a>

                <div class="border-t border-slate-100 my-1"></div>

                <form action="{{ route('reseller.logout') }}" method="POST">
                    @csrf
                    <button type="submit" 
                            class="w-full flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-rose-600 hover:bg-rose-50 font-semibold transition cursor-pointer text-left">
                        <i class="fas fa-sign-out-alt w-4"></i>
                        <span>{{ __('Log Out') }}</span>
                    </button>
                </form>
            </div>
        </div>

    </div>

</header>
