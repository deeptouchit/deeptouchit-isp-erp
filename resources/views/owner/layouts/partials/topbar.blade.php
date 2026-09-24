<!-- Professional Production Sticky Topbar -->
<header class="sticky top-0 z-30 h-14 bg-white/90 backdrop-blur-md border-b border-slate-200/80 px-3.5 sm:px-6 flex items-center justify-between shadow-xs transition-all">
    
    <!-- Left Section: Mobile Toggle, Breadcrumb & Context Title -->
    <div class="flex items-center gap-3 min-w-0">
        <!-- Mobile Sidebar Toggle -->
        <button type="button" 
                @click="mobileSidebar = true" 
                class="lg:hidden p-2 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 active:scale-95 transition focus:outline-none"
                aria-label="Open Navigation Menu">
            <i class="fas fa-bars-staggered text-sm"></i>
        </button>

    </div>

    <!-- Right Section: System Actions, Live Feed & User Menu -->
    <div class="flex items-center gap-2 sm:gap-2.5 flex-shrink-0">


        <!-- Notification Bell Widget -->
        <div class="relative" x-data="{
            open: false,
            loading: false,
            unreadCount: {{ $unreadNotifsCount }},
            notifications: [],
            async loadNotifications() {
                this.loading = true;
                try {
                    const res = await fetch('{{ route('owner.notifications.index') }}');
                    const data = await res.json();
                    this.notifications = data.notifications || [];
                    this.unreadCount = data.unread_count || 0;
                } catch (e) {
                    console.error('Failed to load notifications:', e);
                } finally {
                    this.loading = false;
                }
            },
            toggle() {
                this.open = !this.open;
                if (this.open) {
                    this.loadNotifications();
                }
            },
            async markRead(id, link) {
                try {
                    await fetch('/owner/notifications/' + id + '/read', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    });
                    this.unreadCount = Math.max(0, this.unreadCount - 1);
                    if (link && link !== '#') {
                        window.location.href = link;
                    }
                } catch (e) {
                    console.error('Failed to mark notification read:', e);
                }
            },
            async markAllAsRead() {
                try {
                    await fetch('{{ route('owner.notifications.mark-all-read') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    });
                    this.unreadCount = 0;
                    this.notifications = [];
                } catch (e) {
                    console.error('Failed to mark all read:', e);
                }
            }
        }" @click.outside="open = false">
            
            <button type="button" 
                    @click="toggle()" 
                    class="relative p-2 rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-100/90 active:scale-95 transition focus:outline-none"
                    title="System Notifications & Alerts">
                <i class="fas fa-bell text-sm"></i>
                <template x-if="unreadCount > 0">
                    <span class="absolute 1.5 top-1 -right-0.5 min-w-[17px] h-4 px-1 rounded-full bg-rose-500 text-white font-black text-[9px] font-mono flex items-center justify-center shadow-xs ring-2 ring-white animate-pulse" 
                          x-text="unreadCount > 99 ? '99+' : unreadCount">
                    </span>
                </template>
            </button>

            <!-- Dropdown Notification Flyout Panel -->
            <div x-show="open" 
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 translate-y-1 scale-98"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                 x-transition:leave-end="opacity-0 translate-y-1 scale-98"
                 class="absolute right-0 mt-2 w-80 sm:w-96 bg-white rounded-2xl border border-slate-200 shadow-2xl z-50 overflow-hidden ring-1 ring-slate-900/5"
                 x-cloak>
                 
                <!-- Panel Header -->
                <div class="px-4 py-3 bg-gradient-to-r from-slate-50 to-white border-b border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="font-extrabold text-xs text-slate-900 tracking-tight">System Alerts</span>
                        <span class="px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 border border-blue-200 font-bold text-[9.5px] font-mono" x-text="unreadCount + ' unread'"></span>
                    </div>
                    <button type="button" 
                            @click="markAllAsRead()" 
                            class="text-[10.5px] font-semibold text-blue-600 hover:text-blue-800 transition hover:underline">
                        Mark all as read
                    </button>
                </div>

                <!-- Notifications Scroll Area -->
                <div class="max-h-80 overflow-y-auto divide-y divide-slate-100">
                    <template x-if="loading">
                        <div class="py-8 text-center text-slate-400 text-xs flex flex-col items-center justify-center gap-2">
                            <i class="fas fa-circle-notch fa-spin text-blue-600 text-base"></i>
                            <span>Syncing notification feed...</span>
                        </div>
                    </template>

                    <template x-if="!loading && notifications.length === 0">
                        <div class="py-9 text-center text-slate-400 text-xs px-4">
                            <div class="w-10 h-10 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-2 text-sm">
                                <i class="fas fa-check-double text-emerald-500"></i>
                            </div>
                            <p class="font-bold text-slate-700 text-xs">All caught up!</p>
                            <span class="text-[11px] text-slate-400">No unread alerts or pending tickets right now.</span>
                        </div>
                    </template>

                    <template x-for="notif in notifications" :key="notif.id">
                        <div @click="markRead(notif.id, notif.link)" 
                             class="p-3 hover:bg-slate-50/80 transition cursor-pointer flex items-start gap-3 group">
                            <div class="w-7 h-7 rounded-lg flex-shrink-0 flex items-center justify-center text-xs font-bold"
                                 :class="notif.priority === 'urgent' ? 'bg-rose-100 text-rose-700' : 'bg-blue-100 text-blue-700'">
                                <i :class="notif.priority === 'urgent' ? 'fas fa-fire' : 'fas fa-ticket'"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-1">
                                    <span class="font-bold text-slate-800 text-[11px] truncate group-hover:text-blue-600 transition" x-text="notif.title"></span>
                                    <span class="text-[9.5px] text-slate-400 font-mono flex-shrink-0" x-text="notif.time_ago"></span>
                                </div>
                                <p class="text-[10.5px] text-slate-600 mt-0.5 line-clamp-2" x-text="notif.message"></p>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Panel Footer -->
                <div class="p-2.5 border-t border-slate-100 bg-slate-50/70 text-center flex items-center justify-between px-4">
                    <a href="{{ route('owner.tickets.index') }}" class="text-[11px] font-bold text-blue-600 hover:text-blue-800 transition flex items-center gap-1">
                        <span>View All Tickets</span>
                        <i class="fas fa-arrow-right text-[9px]"></i>
                    </a>
                    <a href="{{ route('owner.activity-logs.index') }}" class="text-[11px] font-medium text-slate-500 hover:text-slate-800 transition">
                        Audit Logs
                    </a>
                </div>
            </div>
        </div>

        <!-- Vertical Separator -->
        <div class="h-6 w-px bg-slate-200 hidden sm:block"></div>

        <!-- Production Profile & System Dropdown -->
        <div class="relative" x-data="{ userMenuOpen: false }" @click.outside="userMenuOpen = false">
            <button type="button" 
                    @click="userMenuOpen = !userMenuOpen" 
                    class="flex items-center gap-2 p-1 pl-1.5 pr-2 rounded-xl hover:bg-slate-100/80 active:scale-98 transition focus:outline-none border border-transparent hover:border-slate-200">
                <div class="w-7 h-7 rounded-lg bg-gradient-to-tr from-blue-600 to-indigo-600 text-white flex items-center justify-center font-bold text-xs shadow-xs">
                    {{ strtoupper(substr($ownerUser->name ?? 'O', 0, 1)) }}
                </div>
                <div class="hidden sm:flex flex-col text-left leading-tight">
                    <span class="text-[11.5px] font-bold text-slate-800 truncate max-w-[100px]">{{ $ownerUser->name ?? 'Owner' }}</span>
                    <span class="text-[9px] font-bold text-blue-600 uppercase tracking-wider">Super Admin</span>
                </div>
                <i class="fas fa-chevron-down text-[9px] text-slate-400 transition-transform duration-150" :class="userMenuOpen ? 'rotate-180 text-slate-600' : ''"></i>
            </button>

            <!-- Profile Dropdown Menu -->
            <div x-show="userMenuOpen" 
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 translate-y-1 scale-98"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                 x-transition:leave-end="opacity-0 translate-y-1 scale-98"
                 class="absolute right-0 mt-2 w-56 bg-white rounded-2xl border border-slate-200 shadow-2xl z-50 overflow-hidden ring-1 ring-slate-900/5 divide-y divide-slate-100"
                 x-cloak>
                
                <!-- Account Summary -->
                <div class="p-3 bg-gradient-to-r from-slate-50 to-white">
                    <div class="font-bold text-xs text-slate-800 truncate">{{ $ownerUser->name ?? 'Platform Owner' }}</div>
                    <div class="text-[10px] text-slate-500 font-mono truncate mt-0.5">{{ $ownerUser->email ?? 'admin@somitysoft.com' }}</div>
                    <div class="mt-2 inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 text-[9px] font-bold uppercase tracking-wider border border-blue-200/60">
                        <i class="fas fa-crown text-[8px] text-amber-500"></i>
                        <span>Root SaaS Controller</span>
                    </div>
                </div>

                <!-- Shortcuts -->
                <div class="p-1.5 space-y-0.5 text-xs">
                    <a href="{{ route('owner.settings.index') }}" 
                       class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-slate-700 hover:bg-slate-50 hover:text-blue-600 transition font-medium">
                        <i class="fas fa-sliders text-slate-400 text-xs w-4 text-center"></i>
                        <span>Platform Settings</span>
                    </a>
                    <a href="{{ route('owner.mail-settings.index') }}" 
                       class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-slate-700 hover:bg-slate-50 hover:text-blue-600 transition font-medium">
                        <i class="fas fa-envelope text-slate-400 text-xs w-4 text-center"></i>
                        <span>Mail & SMTP</span>
                    </a>
                    <a href="{{ route('owner.security-backup.index') }}" 
                       class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-slate-700 hover:bg-slate-50 hover:text-blue-600 transition font-medium">
                        <i class="fas fa-shield-halved text-slate-400 text-xs w-4 text-center"></i>
                        <span>Security & Backup</span>
                    </a>
                    <a href="{{ route('owner.automation.index') }}" 
                       class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-slate-700 hover:bg-slate-50 hover:text-blue-600 transition font-medium">
                        <i class="fas fa-microchip text-slate-400 text-xs w-4 text-center"></i>
                        <span>Automation / Cron</span>
                    </a>
                </div>

                <!-- Logout Form -->
                <div class="p-1.5">
                    <form action="{{ route('owner.logout') }}" method="POST">
                        @csrf
                        <button type="submit" 
                                class="w-full flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-rose-600 hover:bg-rose-50 transition font-semibold text-xs">
                            <i class="fas fa-arrow-right-from-bracket text-xs w-4 text-center"></i>
                            <span>Sign Out from Console</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>
</header>
