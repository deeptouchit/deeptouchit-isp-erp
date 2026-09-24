<script setup>
import { ref, computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { 
    GlobeAltIcon, 
    CircleStackIcon, 
    Bars3Icon, 
    ChevronDownIcon,
    ChevronLeftIcon, 
    ChevronRightIcon, 
    ArrowLeftOnRectangleIcon,
    ArrowTopRightOnSquareIcon,
    BellIcon,
    FolderIcon,
    EnvelopeIcon,
    LifebuoyIcon,
    BanknotesIcon,
    UserCircleIcon,
    CheckIcon,
    ShieldCheckIcon,
    ArrowRightIcon
} from '@heroicons/vue/24/outline'

const page = usePage()

const props = defineProps({
    user: {
        type: Object,
        default: () => ({})
    },
    isCollapsed: {
        type: Boolean,
        default: false
    },
    currentSubscription: {
        type: Object,
        default: () => ({})
    }
})

const emit = defineEmits(['update:isCollapsed', 'toggleMobileSidebar'])

const userMenuOpen = ref(false)
const notificationsOpen = ref(false)

const clientNotifications = computed(() => page.props.client_notifications || [])
const unreadCount = computed(() => page.props.client_unread_count || 0)
</script>

<template>
    <!-- Production-Grade World-Class Client Top Navigation Bar (Crisp Rectangular Design, No Oval/Egg shapes) -->
    <header class="h-14 bg-white border-b border-[#E2E8F0] sticky top-0 z-20 px-4 sm:px-5 flex items-center justify-between gap-3 select-none">
        <!-- Left Group: Sidebar Toggle & Domain Status Pill -->
        <div class="flex items-center gap-2 flex-shrink-0">
            <!-- Desktop Sidebar Collapse Toggle Button -->
            <button 
                type="button"
                @click="emit('update:isCollapsed', !isCollapsed)" 
                class="hidden lg:flex items-center justify-center w-8 h-8 rounded-[4px] text-slate-600 hover:text-slate-900 bg-white hover:bg-slate-50 border border-[#E2E8F0] transition shadow-2xs cursor-pointer"
                :title="isCollapsed ? 'Expand sidebar (Ctrl+B)' : 'Collapse sidebar (Ctrl+B)'"
            >
                <ChevronLeftIcon v-if="!isCollapsed" class="w-4 h-4" />
                <ChevronRightIcon v-else class="w-4 h-4" />
            </button>

            <!-- Mobile Hamburger Toggle Button -->
            <button 
                type="button"
                @click="emit('toggleMobileSidebar')" 
                class="flex lg:hidden items-center justify-center w-8 h-8 rounded-[4px] text-slate-600 hover:text-slate-900 bg-white hover:bg-slate-50 border border-[#E2E8F0] transition shadow-2xs cursor-pointer"
                title="Open mobile menu"
            >
                <Bars3Icon class="w-4 h-4" />
            </button>

            <!-- Active Domain & PHP Badge -->
            <div 
                v-if="currentSubscription?.domain" 
                class="flex items-center gap-2 px-2.5 py-1 bg-[#F8FAFC] border border-[#E2E8F0] rounded-[4px] text-xs font-semibold text-slate-800 shadow-2xs"
            >
                <GlobeAltIcon class="w-3.5 h-3.5 text-blue-600" />
                <span class="font-bold text-slate-900 truncate max-w-[160px] sm:max-w-xs">{{ currentSubscription.domain }}</span>
                <span class="text-slate-300">|</span>
                <span class="font-mono text-[11px] text-slate-500 font-bold">PHP {{ currentSubscription.php_version || '8.2' }}</span>
            </div>

            <!-- Services Healthy Indicator -->
            <div class="hidden sm:flex items-center gap-1.5 px-2 py-1 bg-emerald-50/90 border border-emerald-200/80 rounded-[4px] text-[11px] font-bold text-emerald-700 shadow-2xs">
                <span class="relative flex h-1.5 w-1.5">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span>
                </span>
                <span>Active</span>
            </div>
        </div>

        <!-- Right Group: Quick Tools, Notifications, User Profile -->
        <div class="flex items-center gap-2 flex-shrink-0">
            <!-- File Manager Quick Link -->
            <Link 
                :href="route().has('file.browse') ? route('file.browse') : '/client/file-manager/browse'" 
                class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 bg-[#F8FAFC] hover:bg-slate-100 text-slate-700 border border-[#E2E8F0] rounded-[4px] text-xs font-semibold transition shadow-2xs"
                title="Open Web File Manager"
            >
                <FolderIcon class="w-3.5 h-3.5 text-amber-600" />
                <span>File Manager</span>
            </Link>

            <!-- Notification Bell with Interactive Dropdown -->
            <div class="relative">
                <button 
                    type="button"
                    @click="notificationsOpen = !notificationsOpen; userMenuOpen = false"
                    class="relative flex items-center justify-center w-8 h-8 rounded-[4px] text-slate-600 hover:text-slate-900 bg-white hover:bg-slate-50 border border-[#E2E8F0] transition shadow-2xs cursor-pointer"
                    title="Account Notifications & Ticket Updates"
                >
                    <BellIcon class="w-4 h-4" />
                    
                    <!-- Dynamic Notification Badge -->
                    <span 
                        v-if="unreadCount > 0 || clientNotifications.length > 0"
                        class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-blue-600 text-white text-[10px] font-mono font-bold flex items-center justify-center ring-2 ring-white shadow-xs"
                    >
                        {{ unreadCount > 9 ? '9+' : (unreadCount || clientNotifications.length) }}
                    </span>
                </button>

                <!-- Notifications Dropdown -->
                <div 
                    v-if="notificationsOpen" 
                    class="absolute right-0 mt-2 w-84 bg-white rounded-lg border border-[#E2E8F0] shadow-xl py-2 z-50 animate-in fade-in zoom-in-95 duration-100 divide-y divide-slate-100"
                >
                    <!-- Dropdown Header -->
                    <div class="px-4 py-2 flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-900">Notifications & Updates</span>
                        <Link 
                            :href="route().has('tickets.index') ? route('tickets.index') : '/client/tickets'" 
                            @click="notificationsOpen = false"
                            class="text-[11px] font-bold text-blue-600 hover:text-blue-800 flex items-center gap-1"
                        >
                            <span>Support Desk</span>
                            <ArrowRightIcon class="w-3 h-3" />
                        </Link>
                    </div>

                    <!-- Notification Items List -->
                    <div class="divide-y divide-slate-100 max-h-80 overflow-y-auto">
                        <div v-if="clientNotifications.length === 0" class="p-6 text-center text-xs text-slate-400 space-y-1">
                            <ShieldCheckIcon class="w-8 h-8 text-emerald-500 mx-auto" />
                            <p class="font-bold text-slate-700">All Updates Caught Up</p>
                            <p class="text-[11px]">No pending ticket replies or invoice notices.</p>
                        </div>

                        <Link 
                            v-for="notif in clientNotifications" 
                            :key="notif.id" 
                            :href="notif.url || (route().has('tickets.index') ? route('tickets.index') : '/client/tickets')"
                            @click="notificationsOpen = false"
                            class="p-3 hover:bg-slate-50 transition flex items-start gap-2.5 block text-left"
                        >
                            <div 
                                class="w-7 h-7 rounded-[4px] flex items-center justify-center shrink-0 mt-0.5"
                                :class="notif.type === 'ticket' ? 'bg-blue-50 text-blue-600 border border-blue-100' : 'bg-amber-50 text-amber-600 border border-amber-100'"
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
                            </div>
                        </Link>
                    </div>

                    <!-- Dropdown Footer -->
                    <div class="px-4 py-2 bg-slate-50 flex items-center justify-between text-[11px]">
                        <Link 
                            :href="route().has('tickets.index') ? route('tickets.index') : '/client/tickets'" 
                            @click="notificationsOpen = false"
                            class="text-slate-600 hover:text-blue-600 font-bold"
                        >
                            Tickets
                        </Link>
                        <Link 
                            :href="route().has('billing.invoices') ? route('billing.invoices') : '/client/billing/invoices'" 
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
                    class="flex items-center gap-2 px-2 py-1 rounded-[4px] bg-white hover:bg-slate-50 border border-[#E2E8F0] transition shadow-2xs cursor-pointer"
                >
                    <div class="w-6 h-6 rounded-[3px] bg-[#2563EB] text-white flex items-center justify-center font-bold text-[11px] shadow-2xs">
                        {{ ((user.first_name || user.name || user.username || 'C')[0]).toUpperCase() }}
                    </div>
                    <div class="hidden md:flex flex-col text-left leading-tight">
                        <span class="text-xs font-bold text-slate-800">{{ user.first_name || user.name || user.username || 'Client' }}</span>
                        <span class="text-[8.5px] text-blue-600 font-bold uppercase tracking-wider">MEMBER</span>
                    </div>
                    <ChevronDownIcon :class="['w-3.5 h-3.5 text-slate-400 transition-transform duration-200', userMenuOpen ? 'rotate-180 text-blue-600' : '']" />
                </button>

                <!-- Profile Menu Dropdown -->
                <div 
                    v-if="userMenuOpen" 
                    class="absolute right-0 mt-2 w-56 bg-white rounded-[6px] border border-[#E2E8F0] shadow-xl py-1.5 z-50 animate-in fade-in zoom-in-95 duration-100 divide-y divide-slate-100"
                >
                    <div class="px-4 py-2.5">
                        <p class="text-xs font-bold text-slate-900">{{ user.first_name || user.name || user.username || 'Client' }}</p>
                        <p class="text-[11px] text-slate-500 truncate">{{ user.email }}</p>
                        <span class="inline-block mt-1 text-[8.5px] font-bold uppercase tracking-wider px-1.5 py-0.2 bg-blue-50 text-blue-700 border border-blue-200 rounded-[2px]">
                            Hosting Client
                        </span>
                    </div>

                    <div class="py-1">
                        <Link 
                            :href="route().has('profile.edit') ? route('profile.edit') : '/client/profile'" 
                            @click="userMenuOpen = false"
                            class="flex items-center gap-2.5 px-4 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-blue-600 transition"
                        >
                            <UserCircleIcon class="w-4 h-4 text-slate-400" />
                            <span>Account Profile</span>
                        </Link>

                        <Link 
                            :href="route().has('billing.invoices') ? route('billing.invoices') : '/client/billing/invoices'" 
                            @click="userMenuOpen = false"
                            class="flex items-center gap-2.5 px-4 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-blue-600 transition"
                        >
                            <BanknotesIcon class="w-4 h-4 text-slate-400" />
                            <span>Billing & Invoices</span>
                        </Link>

                        <Link 
                            :href="route().has('tickets.index') ? route('tickets.index') : '/client/tickets'" 
                            @click="userMenuOpen = false"
                            class="flex items-center gap-2.5 px-4 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-blue-600 transition"
                        >
                            <LifebuoyIcon class="w-4 h-4 text-slate-400" />
                            <span>Support Tickets</span>
                        </Link>
                    </div>

                    <div class="py-1">
                        <Link 
                            :href="route('logout')" 
                            method="post" 
                            as="button" 
                            class="w-full flex items-center gap-2.5 px-4 py-1.5 text-xs font-bold text-red-600 hover:bg-red-50 text-left transition"
                        >
                            <ArrowLeftOnRectangleIcon class="w-4 h-4" />
                            <span>Sign Out</span>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </header>
</template>
