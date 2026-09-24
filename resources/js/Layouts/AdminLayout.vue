<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { 
    ServerIcon, 
    HomeIcon, 
    Squares2X2Icon, 
    UsersIcon, 
    Cog6ToothIcon, 
    ArrowLeftOnRectangleIcon, 
    Bars3Icon, 
    XMarkIcon,
    ShieldCheckIcon,
    GlobeAltIcon,
    CommandLineIcon,
    CircleStackIcon,
    CreditCardIcon,
    LifebuoyIcon,
    MagnifyingGlassIcon,
    ChevronDownIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    ArrowTopRightOnSquareIcon,
    CpuChipIcon,
    BellIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    EnvelopeIcon,
    FolderIcon,
    ArrowPathIcon,
    ChartBarIcon,
    BoltIcon,
    DocumentTextIcon,
    KeyIcon,
    UserGroupIcon,
    WrenchIcon,
    AdjustmentsHorizontalIcon,
    SparklesIcon,
    ClipboardDocumentIcon,
    CheckIcon
} from '@heroicons/vue/24/outline'
import { getSidebarNavigation } from '@/Config/sidebar'
import AdminTopNavbar from '@/Components/Admin/AdminTopNavbar.vue'
import AdminSidebar from '@/Components/Admin/AdminSidebar.vue'
import AdminFooter from '@/Components/Admin/AdminFooter.vue'

const page = usePage()
const mobileSidebarOpen = ref(false)
const isCollapsed = ref(false)
const appName = computed(() => page.props.app_name || 'HostingOS')
const appLogo = computed(() => page.props.app_logo || null)
const userMenuOpen = ref(false)
const notificationsOpen = ref(false)
const sidebarSearch = ref('')
const copiedIp = ref(false)

const notifications = ref([
    { id: 1, title: 'Automated Backup Completed', time: '12m ago', desc: 'Daily snapshot for active domains completed.' },
    { id: 2, title: 'AutoSSL Certificate Active', time: '1h ago', desc: 'Wildcard SSL renewed for master domains.' },
    { id: 3, title: 'Server Health 100% Optimal', time: '3h ago', desc: 'Load average 0.12 across all cores.' },
])

// Dynamic Accordion Expansion state
const manuallyExpanded = ref([])
const manuallyClosed = ref([])

// Toast notification state
const showSuccessToast = ref(false)
const showErrorToast = ref(false)
const toastMessage = ref('')

const user = computed(() => page.props.auth?.user || {})
const flash = computed(() => page.props.flash || {})

watch(() => flash.value?.success, (msg) => {
    if (msg) {
        toastMessage.value = msg
        showSuccessToast.value = true
        setTimeout(() => { showSuccessToast.value = false }, 4000)
    }
}, { immediate: true })

watch(() => flash.value?.error, (msg) => {
    if (msg) {
        toastMessage.value = msg
        showErrorToast.value = true
        setTimeout(() => { showErrorToast.value = false }, 5000)
    }
}, { immediate: true })

const serverPublicIp = computed(() => page.props.server_public_ip || '103.59.177.138')

const copyServerIp = () => {
    navigator.clipboard.writeText(serverPublicIp.value)
    copiedIp.value = true
    setTimeout(() => { copiedIp.value = false }, 2000)
}

// 20 Root Admin Categories loaded from dedicated sidebar configuration file
const navigationStructure = computed(() => getSidebarNavigation(route))

// Helper to determine if an item is the first in its section to display section header
const isFirstInSection = (item, list) => {
    const index = list.findIndex(c => c.id === item.id)
    if (index === 0) return true
    return list[index - 1]?.section !== item.section
}

// Helper to determine if an accordion group should be open
const isGroupOpen = (cat) => {
    if (sidebarSearch.value.trim()) return true
    if (manuallyExpanded.value.includes(cat.id)) return true
    if (cat.active && !manuallyClosed.value.includes(cat.id)) return true
    return false
}

// Toggle accordion open/closed
const toggleGroup = (cat) => {
    if (isGroupOpen(cat)) {
        // User is closing it
        manuallyExpanded.value = manuallyExpanded.value.filter(id => id !== cat.id)
        if (cat.active && !manuallyClosed.value.includes(cat.id)) {
            manuallyClosed.value.push(cat.id)
        }
    } else {
        // User is opening it
        if (!manuallyExpanded.value.includes(cat.id)) {
            manuallyExpanded.value.push(cat.id)
        }
        manuallyClosed.value = manuallyClosed.value.filter(id => id !== cat.id)
    }
}

// Search filtering across all 20 modules and sub-items
const filteredNavigation = computed(() => {
    if (!sidebarSearch.value.trim()) return navigationStructure.value
    const q = sidebarSearch.value.toLowerCase()

    return navigationStructure.value.filter(cat => {
        const titleMatch = cat.title.toLowerCase().includes(q)
        const subMatch = cat.subItems.some(sub => sub.name.toLowerCase().includes(q))
        return titleMatch || subMatch
    }).map(cat => {
        if (cat.title.toLowerCase().includes(q)) return cat
        return {
            ...cat,
            subItems: cat.subItems.filter(sub => sub.name.toLowerCase().includes(q))
        }
    })
})
</script>

<template>
    <div class="min-h-screen bg-[#F8F9FC] flex flex-col font-sans text-[#1D1B26] antialiased selection:bg-[#EDE8FC] selection:text-[#673DE6]">
        <!-- Floating Toast Notifications -->
        <Teleport to="body">
            <div class="fixed top-4 right-4 z-50 flex flex-col gap-2 max-w-sm w-full pointer-events-none">
                <Transition
                    enter-active-class="transform ease-out duration-300 transition"
                    enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
                    enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
                    leave-active-class="transition ease-in duration-100"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                >
                    <div 
                        v-if="showSuccessToast" 
                        class="pointer-events-auto bg-white border border-[#D1FADF] p-4 rounded-2xl shadow-[0_10px_25px_-5px_rgba(2,122,72,0.12)] flex items-start gap-3"
                    >
                        <div class="w-8 h-8 rounded-xl bg-[#ECFDF3] text-[#027A48] flex items-center justify-center flex-shrink-0 border border-[#D1FADF]">
                            <CheckCircleIcon class="w-5 h-5" />
                        </div>
                        <div class="flex-1">
                            <p class="text-xs font-bold text-[#1D1B26]">Action Successful</p>
                            <p class="text-[11px] text-[#72717A] mt-0.5">{{ toastMessage }}</p>
                        </div>
                        <button @click="showSuccessToast = false" class="text-[#72717A] hover:text-[#1D1B26] text-xs">✕</button>
                    </div>
                </Transition>

                <Transition
                    enter-active-class="transform ease-out duration-300 transition"
                    enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
                    enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
                    leave-active-class="transition ease-in duration-100"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                >
                    <div 
                        v-if="showErrorToast" 
                        class="pointer-events-auto bg-white border border-[#FECDCA] p-4 rounded-2xl shadow-[0_10px_25px_-5px_rgba(217,45,32,0.12)] flex items-start gap-3"
                    >
                        <div class="w-8 h-8 rounded-xl bg-[#FEF3F2] text-[#D92D20] flex items-center justify-center flex-shrink-0 border border-[#FECDCA]">
                            <ExclamationTriangleIcon class="w-5 h-5" />
                        </div>
                        <div class="flex-1">
                            <p class="text-xs font-bold text-[#D92D20]">Error Occurred</p>
                            <p class="text-[11px] text-[#72717A] mt-0.5">{{ toastMessage }}</p>
                        </div>
                        <button @click="showErrorToast = false" class="text-[#72717A] hover:text-[#1D1B26] text-xs">✕</button>
                    </div>
                </Transition>
            </div>
        </Teleport>

        <div class="flex flex-1">
            <!-- Sidebar Navigation Component (Decoupled, Dynamic Logo & Name) -->
            <AdminSidebar 
                :user="user"
                v-model:is-collapsed="isCollapsed"
                v-model:sidebar-search="sidebarSearch"
            />

            <!-- Main Content Area -->
            <div class="flex-1 flex flex-col min-w-0">
                <!-- Topbar Navigation Component (Decoupled, Clean Architecture) -->
                <AdminTopNavbar 
                    :user="user"
                    :server-public-ip="serverPublicIp"
                    v-model:is-collapsed="isCollapsed"
                    v-model:sidebar-search="sidebarSearch"
                    @toggle-mobile-sidebar="mobileSidebarOpen = true"
                />

                <!-- Page Header Slot -->
                <div v-if="$slots.header" class="bg-white border-b border-[#EBEBEB] px-4 sm:px-8 py-6">
                    <slot name="header" />
                </div>

                <!-- Page Main Body -->
                <main class="flex-1 p-4 sm:p-8 space-y-6">
                    <slot />
                </main>

                <!-- Admin Footer Component -->
                <AdminFooter />
            </div>
        </div>

        <!-- Mobile Drawer Navigation -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition-opacity ease-linear duration-300"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-opacity ease-linear duration-300"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="mobileSidebarOpen" class="fixed inset-0 bg-[#0F172A]/60 z-50 flex">
                    <div class="w-80 bg-white h-full flex flex-col p-4 space-y-4">
                        <div class="flex justify-between items-center pb-3 border-b border-[#E2E8F0]">
                            <div class="flex items-center gap-2">
                                <div v-if="appLogo" class="w-8 h-8 rounded-[5px] overflow-hidden flex items-center justify-center flex-shrink-0 bg-white border border-[#E2E8F0]">
                                    <img :src="appLogo" :alt="appName" class="w-full h-full object-contain" />
                                </div>
                                <div v-else class="w-8 h-8 rounded-[5px] bg-gradient-to-tr from-blue-600 to-indigo-600 text-white flex items-center justify-center font-extrabold text-sm shadow-[0_2px_8px_rgba(37,99,235,0.3)] flex-shrink-0">
                                    {{ (appName || 'H')[0].toUpperCase() }}
                                </div>
                                <div class="flex flex-col leading-tight">
                                    <span class="font-black text-sm text-slate-900">{{ appName }}</span>
                                    <span class="text-[9px] font-semibold text-slate-400">Control Panel</span>
                                </div>
                            </div>
                            <button @click="mobileSidebarOpen = false" class="p-1.5 text-slate-500 hover:text-slate-900">
                                <XMarkIcon class="w-5 h-5" />
                            </button>
                        </div>

                        <div class="flex-1 overflow-y-auto space-y-1 p-1">
                            <div v-for="cat in filteredNavigation" :key="cat.id" class="space-y-0.5">
                                <Link 
                                    v-if="cat.subItems.length === 0"
                                    :href="cat.href"
                                    @click="mobileSidebarOpen = false"
                                    :class="[
                                        'w-full flex items-center justify-between px-3 py-1.5 rounded-[4px] border text-[12.5px] transition-colors',
                                        cat.active 
                                            ? 'bg-[#EFF6FF] border-[#BFDBFE] border-l-[3.5px] border-l-[#2563EB] text-[#1D4ED8] font-semibold' 
                                            : 'bg-white border-[#E2E8F0] text-[#334155]'
                                    ]"
                                >
                                    <div class="flex items-center gap-2.5">
                                        <component :is="cat.icon" :class="['w-4 h-4', cat.active ? 'text-[#2563EB]' : 'text-slate-600']" />
                                        <span class="text-xs font-semibold">{{ cat.title }}</span>
                                    </div>
                                </Link>

                                <div v-else class="space-y-0.5">
                                    <button 
                                        @click="toggleGroup(cat)"
                                        :class="[
                                            'w-full flex items-center justify-between px-3 py-1.5 rounded-[4px] border text-[12.5px] transition-colors text-left',
                                            cat.active 
                                                ? 'bg-[#EFF6FF] border-[#BFDBFE] border-l-[3.5px] border-l-[#2563EB] text-[#1D4ED8] font-semibold' 
                                                : 'bg-white border-[#E2E8F0] text-[#334155]'
                                        ]"
                                    >
                                        <div class="flex items-center gap-2.5">
                                            <component :is="cat.icon" :class="['w-4 h-4', cat.active ? 'text-[#2563EB]' : 'text-slate-600']" />
                                            <span class="text-xs font-semibold">{{ cat.title }}</span>
                                        </div>
                                        <ChevronRightIcon :class="['w-3.5 h-3.5 text-slate-400 transition-transform duration-200', isGroupOpen(cat) ? 'transform rotate-90 text-[#2563EB]' : '']" />
                                    </button>

                                    <div v-show="isGroupOpen(cat)" class="my-0.5 pl-3 space-y-0.5">
                                        <Link 
                                            v-for="sub in cat.subItems" 
                                            :key="sub.name"
                                            :href="sub.href"
                                            @click="mobileSidebarOpen = false"
                                            :class="[
                                                'block px-2.5 py-1 text-[12px] rounded-[4px] transition-colors',
                                                sub.active 
                                                    ? 'bg-[#EFF6FF] text-[#1D4ED8] font-semibold border border-[#BFDBFE]' 
                                                    : 'bg-white border border-[#E2E8F0] text-[#334155] hover:bg-[#F8FAFC]'
                                            ]"
                                        >
                                            {{ sub.name }}
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>
