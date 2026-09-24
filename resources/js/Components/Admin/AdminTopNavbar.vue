<script setup>
import { ref, computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { 
    ServerIcon, 
    Bars3Icon, 
    ChevronLeftIcon, 
    ChevronRightIcon, 
    ChevronDownIcon,
    MagnifyingGlassIcon,
    CommandLineIcon,
    CircleStackIcon,
    BellIcon,
    CheckIcon,
    ClipboardDocumentIcon,
    Cog6ToothIcon,
    ShieldCheckIcon,
    ArrowLeftOnRectangleIcon,
    ArrowTopRightOnSquareIcon,
    LifebuoyIcon,
    BanknotesIcon,
    ArrowRightIcon,
    EnvelopeIcon
} from '@heroicons/vue/24/outline'

const page = usePage()

const props = defineProps({
    user: {
        type: Object,
        default: () => ({})
    },
    serverPublicIp: {
        type: String,
        default: '103.59.177.138'
    },
    isCollapsed: {
        type: Boolean,
        default: false
    },
    sidebarSearch: {
        type: String,
        default: ''
    }
})

const emit = defineEmits(['update:isCollapsed', 'update:sidebarSearch', 'toggleMobileSidebar'])

const userMenuOpen = ref(false)
const notificationsOpen = ref(false)
const copiedIp = ref(false)

const liveNotifications = computed(() => page.props.admin_notifications || [])
const unreadTicketsCount = computed(() => page.props.admin_unread_ticket_count || 0)

const copyServerIp = async () => {
    try {
        await navigator.clipboard.writeText(props.serverPublicIp)
        copiedIp.value = true
        setTimeout(() => {
            copiedIp.value = false
        }, 2000)
    } catch (e) {
        console.error('Failed to copy server IP', e)
    }
}
</script>

<template>
    <!-- Production-Grade World-Class Top Navigation Bar (Clean Rectangular Cards, No Oval/Egg shapes) -->
    <header class="h-14 bg-white border-b border-[#E2E8F0] sticky top-0 z-20 px-4 sm:px-5 flex items-center justify-between gap-3 select-none">
        <!-- Left Group: Toggle, Server IP, Live Health -->
        <div class="flex items-center gap-2 flex-shrink-0">
            <!-- Desktop Sidebar Collapse Toggle Button -->
            <button 
                type="button"
                @click="emit('update:isCollapsed', !isCollapsed)" 
                class="hidden lg:flex items-center justify-center w-8 h-8 rounded-[5px] text-slate-600 hover:text-slate-900 bg-white hover:bg-slate-50 border border-[#E2E8F0] transition shadow-2xs cursor-pointer"
                :title="isCollapsed ? 'Expand sidebar (Ctrl+B)' : 'Collapse sidebar (Ctrl+B)'"
            >
                <ChevronLeftIcon v-if="!isCollapsed" class="w-4 h-4" />
                <ChevronRightIcon v-else class="w-4 h-4" />
            </button>

            <!-- Mobile Hamburger Toggle Button -->
            <button 
                type="button"
                @click="emit('toggleMobileSidebar')" 
                class="flex lg:hidden items-center justify-center w-8 h-8 rounded-[5px] text-slate-600 hover:text-slate-900 bg-white hover:bg-slate-50 border border-[#E2E8F0] transition shadow-2xs cursor-pointer"
                title="Open mobile menu"
            >
                <Bars3Icon class="w-4 h-4" />
            </button>

            <!-- Server IP Badge with 1-Click Copy -->
            <div 
                class="flex items-center bg-[#F8FAFC] border border-[#E2E8F0] rounded-[5px] px-2 py-1 text-xs font-mono text-slate-700 shadow-2xs"
                :title="'Server Public IP: ' + serverPublicIp"
            >
                <ServerIcon class="w-3.5 h-3.5 mr-1.5 text-slate-500" />
                <span class="font-bold text-slate-900">{{ serverPublicIp }}</span>
                <button 
                    type="button"
                    @click="copyServerIp" 
                    class="ml-2 text-slate-400 hover:text-slate-700 cursor-pointer p-0.5 rounded transition"
                    title="Copy Public IP"
                >
                    <CheckIcon v-if="copiedIp" class="w-3 h-3 text-emerald-600" />
                    <ClipboardDocumentIcon v-else class="w-3 h-3" />
                </button>
            </div>

            <!-- Health Pulse Pill -->
            <div class="hidden sm:flex items-center gap-1.5 px-2 py-1 bg-emerald-50/90 border border-emerald-200/80 rounded-[5px] text-[11px] font-bold text-emerald-700 shadow-2xs">
                <span class="relative flex h-1.5 w-1.5">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span>
                </span>
                <span>Services Healthy</span>
            </div>
        </div>

        <!-- Center Group: Global Search (Fast Filter) -->
        <div class="hidden md:flex flex-1 max-w-md mx-2">
            <div class="relative w-full">
                <MagnifyingGlassIcon class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2" />
                <input 
                    type="text" 
                    :value="sidebarSearch"
                    @input="emit('update:sidebarSearch', $event.target.value)"
                    placeholder="Search tools, modules, logs or domains (Ctrl + K)..." 
                    class="w-full bg-[#F8FAFC] focus:bg-white border border-[#E2E8F0] rounded-[5px] pl-8 pr-12 py-1.5 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-[#2563EB] focus:border-[#2563EB] transition font-medium shadow-2xs"
                />
                <span class="absolute right-2 top-1/2 -translate-y-1/2 text-[10px] font-mono font-semibold bg-white border border-slate-200 text-slate-400 px-1 py-0.2 rounded">⌘K</span>
            </div>
        </div>

        <!-- Right Group: Quick Tools, Terminal, SSO, Notifications, Profile -->
        <div class="flex items-center gap-2 flex-shrink-0">
            <!-- Terminal Launcher Quick Button -->
            <Link 
                :href="route().has('admin.root-tools.terminal') ? route('admin.root-tools.terminal') : '/admin/root-tools/terminal'" 
                class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 bg-[#F8FAFC] hover:bg-slate-100 text-slate-700 border border-[#E2E8F0] rounded-[5px] text-xs font-semibold transition shadow-2xs"
                :class="{ 'bg-blue-50 border-blue-200 text-blue-700': route().current('admin.root-tools.terminal') }"
                title="Launch Web Root Terminal"
            >
                <CommandLineIcon class="w-3.5 h-3.5 text-slate-600" />
                <span>Terminal</span>
            </Link>

            <!-- phpMyAdmin SSO Quick Link -->
            <a 
                href="/phpmyadmin" 
                target="_blank" 
                class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 bg-[#EFF6FF] hover:bg-blue-100/70 text-blue-700 border border-[#BFDBFE] rounded-[5px] text-xs font-semibold transition shadow-2xs"
                title="Open phpMyAdmin Single Sign-On"
            >
                <CircleStackIcon class="w-3.5 h-3.5 text-blue-600" />
                <span>phpMyAdmin SSO</span>
                <ArrowTopRightOnSquareIcon class="w-3 h-3 text-blue-400" />
            </a>

            <!-- Notification Bell with Interactive Dropdown & Live Badges -->
            <div class="relative">
                <button 
                    type="button"
                    @click="notificationsOpen = !notificationsOpen; userMenuOpen = false"
                    class="relative flex items-center justify-center w-8 h-8 rounded-[5px] text-slate-600 hover:text-slate-900 bg-white hover:bg-slate-50 border border-[#E2E8F0] transition shadow-2xs cursor-pointer"
                    title="System & Support Notifications"
                >
                    <BellIcon class="w-4 h-4" />
                    
                    <!-- Dynamic Notification Badge -->
                    <span 
                        v-if="unreadTicketsCount > 0 || liveNotifications.length > 0"
                        class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-rose-600 text-white text-[10px] font-mono font-bold flex items-center justify-center ring-2 ring-white shadow-xs"
                    >
                        {{ unreadTicketsCount > 9 ? '9+' : (unreadTicketsCount || liveNotifications.length) }}
                    </span>
                </button>

                <!-- Notifications Dropdown -->
                <div 
                    v-if="notificationsOpen" 
                    class="absolute right-0 mt-2 w-88 bg-white rounded-lg border border-[#E2E8F0] shadow-xl py-2 z-50 animate-in fade-in zoom-in-95 duration-100 divide-y divide-slate-100"
                >
                    <!-- Dropdown Header -->
                    <div class="px-4 py-2 flex items-center justify-between">
                        <div class="flex items-center gap-1.5">
                            <span class="text-xs font-bold text-slate-900">Support & Alerts</span>
                            <span 
                                v-if="unreadTicketsCount > 0"
                                class="text-[10px] font-bold text-rose-700 bg-rose-50 px-1.5 py-0.2 rounded border border-rose-200 font-mono"
                            >
                                {{ unreadTicketsCount }} Open
                            </span>
                        </div>

                        <Link 
                            :href="route().has('admin.tickets.index') ? route('admin.tickets.index') : '/admin/tickets'" 
                            @click="notificationsOpen = false"
                            class="text-[11px] font-bold text-blue-600 hover:text-blue-800 flex items-center gap-1"
                        >
                            <span>View All</span>
                            <ArrowRightIcon class="w-3 h-3" />
                        </Link>
                    </div>

                    <!-- Notification Items List -->
                    <div class="divide-y divide-slate-100 max-h-80 overflow-y-auto">
                        <div v-if="liveNotifications.length === 0" class="p-6 text-center text-xs text-slate-400 space-y-1">
                            <ShieldCheckIcon class="w-8 h-8 text-emerald-500 mx-auto" />
                            <p class="font-bold text-slate-700">All Inquiries Reconciled</p>
                            <p class="text-[11px]">No pending tickets or unhandled alerts.</p>
                        </div>

                        <Link 
                            v-for="notif in liveNotifications" 
                            :key="notif.id" 
                            :href="notif.url || (route().has('admin.tickets.index') ? route('admin.tickets.index') : '/admin/tickets')"
                            @click="notificationsOpen = false"
                            class="p-3 hover:bg-slate-50 transition flex items-start gap-2.5 block text-left"
                        >
                            <div 
                                class="w-7 h-7 rounded-[4px] flex items-center justify-center shrink-0 mt-0.5"
                                :class="notif.type === 'ticket' ? 'bg-indigo-50 text-indigo-600 border border-indigo-100' : 'bg-amber-50 text-amber-600 border border-amber-100'"
                            >
                                <LifebuoyIcon v-if="notif.type === 'ticket'" class="w-4 h-4" />
                                <BanknotesIcon v-else class="w-4 h-4" />
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between mb-0.5">
                                    <span class="text-xs font-bold text-slate-900 truncate">{{ notif.title }}</span>
                                    <span class="text-[10px] text-slate-400 font-mono shrink-0 ml-1">{{ notif.time }}</span>
                                </div>
                                <p class="text-[11px] text-slate-600 truncate">{{ notif.desc }}</p>
                                <div class="flex items-center gap-1.5 mt-1">
                                    <span 
                                        v-if="notif.priority"
                                        class="text-[9.5px] font-bold px-1.5 py-0.2 rounded font-mono uppercase"
                                        :class="notif.priority === 'critical' || notif.priority === 'high' ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-slate-100 text-slate-600'"
                                    >
                                        {{ notif.priority }}
                                    </span>
                                    <span v-if="notif.department" class="text-[9.5px] text-slate-400 font-mono">
                                        {{ notif.department }}
                                    </span>
                                </div>
                            </div>
                        </Link>
                    </div>

                    <!-- Dropdown Footer -->
                    <div class="px-4 py-2 bg-slate-50 flex items-center justify-between text-[11px]">
                        <Link 
                            :href="route().has('admin.tickets.index') ? route('admin.tickets.index') : '/admin/tickets'" 
                            @click="notificationsOpen = false"
                            class="text-slate-600 hover:text-blue-600 font-bold flex items-center gap-1"
                        >
                            <span>Open Tickets Desk</span>
                        </Link>
                        <Link 
                            :href="route().has('admin.billing.invoices') ? route('admin.billing.invoices') : '/admin/billing/invoices'" 
                            @click="notificationsOpen = false"
                            class="text-slate-500 hover:text-slate-800"
                        >
                            Invoices
                        </Link>
                    </div>
                </div>
            </div>

            <!-- User Profile Button & Dropdown -->
            <div class="relative">
                <button 
                    type="button"
                    @click="userMenuOpen = !userMenuOpen; notificationsOpen = false"
                    class="flex items-center gap-2 px-2 py-1 rounded-[5px] bg-white hover:bg-slate-50 border border-[#E2E8F0] transition shadow-2xs cursor-pointer"
                >
                    <div class="w-6 h-6 rounded-[4px] bg-[#2563EB] text-white flex items-center justify-center font-bold text-[11px] shadow-2xs">
                        {{ (user.username || 'A')[0].toUpperCase() }}
                    </div>
                    <div class="hidden md:flex flex-col text-left leading-tight">
                        <span class="text-xs font-bold text-slate-800">{{ user.username || 'admin' }}</span>
                        <span class="text-[8.5px] text-blue-600 font-bold uppercase tracking-wider">ROOT OWNER</span>
                    </div>
                    <ChevronDownIcon :class="['w-3.5 h-3.5 text-slate-400 transition-transform duration-200', userMenuOpen ? 'rotate-180 text-blue-600' : '']" />
                </button>

                <!-- Profile Menu Dropdown -->
                <div 
                    v-if="userMenuOpen" 
                    class="absolute right-0 mt-2 w-56 bg-white rounded-[6px] border border-[#E2E8F0] shadow-xl py-1.5 z-50 animate-in fade-in zoom-in-95 duration-100"
                >
                    <div class="px-4 py-2.5 border-b border-slate-100">
                        <p class="text-xs font-bold text-slate-900">{{ user.username || 'Root Admin' }}</p>
                        <p class="text-[11px] text-slate-500 truncate">{{ user.email || 'admin@deeptouchhost.com' }}</p>
                        <span class="inline-block mt-1 text-[8.5px] font-bold uppercase tracking-wider px-1.5 py-0.2 bg-blue-50 text-blue-700 border border-blue-200 rounded-[3px]">
                            Superuser Root
                        </span>
                    </div>

                    <div class="py-1">
                        <Link :href="route('admin.settings.general')" class="flex items-center gap-2.5 px-4 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-blue-600 transition">
                            <Cog6ToothIcon class="w-4 h-4 text-slate-400" />
                            <span>System Settings</span>
                        </Link>

                        <Link :href="route('admin.security.index')" class="flex items-center gap-2.5 px-4 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-blue-600 transition">
                            <ShieldCheckIcon class="w-4 h-4 text-slate-400" />
                            <span>Security & Firewall</span>
                        </Link>

                        <Link :href="route('admin.backups.schedules')" class="flex items-center gap-2.5 px-4 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-blue-600 transition">
                            <CircleStackIcon class="w-4 h-4 text-slate-400" />
                            <span>Backup Schedules</span>
                        </Link>
                    </div>

                    <div class="border-t border-slate-100 my-1"></div>

                    <Link :href="route('admin.logout')" method="post" as="button" class="w-full flex items-center gap-2.5 px-4 py-1.5 text-xs font-bold text-red-600 hover:bg-red-50 text-left transition">
                        <ArrowLeftOnRectangleIcon class="w-4 h-4" />
                        <span>Sign Out</span>
                    </Link>
                </div>
            </div>
        </div>
    </header>
</template>
