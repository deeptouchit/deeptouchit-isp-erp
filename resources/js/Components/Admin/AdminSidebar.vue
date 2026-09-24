<script setup>
import { ref, computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { 
    MagnifyingGlassIcon,
    ChevronRightIcon,
    ArrowTopRightOnSquareIcon,
    ArrowLeftOnRectangleIcon
} from '@heroicons/vue/24/outline'
import { getSidebarNavigation } from '@/Config/sidebar'

const props = defineProps({
    user: {
        type: Object,
        default: () => ({})
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

const emit = defineEmits(['update:isCollapsed', 'update:sidebarSearch'])

const page = usePage()

// Dynamic Brand Name, Tagline & Logo from Backend System Settings
const appName = computed(() => page.props.app_name || 'HostingOS')
const appTagline = computed(() => page.props.app_tagline || 'Enterprise Cloud Engine')
const appLogo = computed(() => page.props.app_logo || null)

// Dynamic Accordion Expansion state
const manuallyExpanded = ref([])
const manuallyClosed = ref([])

const currentRouteName = computed(() => {
    try {
        return route().current()
    } catch {
        return ''
    }
})

// Generate navigation from decoupled configuration
const navigation = computed(() => getSidebarNavigation(route))

const isGroupOpen = (cat) => {
    if (manuallyExpanded.value.includes(cat.id)) return true
    if (manuallyClosed.value.includes(cat.id)) return false
    return cat.active
}

const toggleGroup = (cat) => {
    const currentlyOpen = isGroupOpen(cat)
    if (currentlyOpen) {
        manuallyClosed.value.push(cat.id)
        manuallyExpanded.value = manuallyExpanded.value.filter(id => id !== cat.id)
    } else {
        manuallyExpanded.value.push(cat.id)
        manuallyClosed.value = manuallyClosed.value.filter(id => id !== cat.id)
    }
}

// Search filter across all 20 modules and sub-routes
const filteredNavigation = computed(() => {
    if (!props.sidebarSearch || !props.sidebarSearch.trim()) {
        return navigation.value
    }
    const q = props.sidebarSearch.toLowerCase().trim()
    return navigation.value.filter(item => {
        const matchesTitle = item.title.toLowerCase().includes(q)
        const matchesSub = item.subItems.some(s => s.name.toLowerCase().includes(q))
        return matchesTitle || matchesSub
    }).map(item => {
        const matchesTitle = item.title.toLowerCase().includes(q)
        if (matchesTitle) return item
        return {
            ...item,
            subItems: item.subItems.filter(s => s.name.toLowerCase().includes(q))
        }
    })
})
</script>

<template>
    <aside 
        :class="[
            'hidden lg:flex flex-col bg-white border-r border-[#E2E8F0] transition-all duration-300 z-30 sticky top-0 h-screen select-none',
            isCollapsed ? 'w-16' : 'w-[230px]'
        ]"
    >
        <!-- Dynamic Brand Header (Exact h-14 to align horizontally with Top Navbar border) -->
        <div class="h-14 px-3.5 border-b border-[#E2E8F0] flex items-center justify-between flex-shrink-0">
            <Link :href="route('admin.dashboard')" class="flex items-center gap-2.5 overflow-hidden group">
                <!-- Dynamic Logo or Signature Icon -->
                <div v-if="appLogo" class="w-8 h-8 rounded-[5px] overflow-hidden flex items-center justify-center flex-shrink-0 bg-white border border-[#E2E8F0]">
                    <img :src="appLogo" :alt="appName" class="w-full h-full object-contain" />
                </div>
                <div v-else class="w-8 h-8 rounded-[5px] bg-gradient-to-tr from-blue-600 to-indigo-600 text-white flex items-center justify-center font-extrabold text-sm shadow-[0_2px_8px_rgba(37,99,235,0.3)] flex-shrink-0 group-hover:scale-105 transition-transform">
                    {{ (appName || 'H')[0].toUpperCase() }}
                </div>
                <div v-if="!isCollapsed" class="flex flex-col overflow-hidden leading-tight">
                    <div class="flex items-center gap-1.5">
                        <span class="text-[13.5px] font-black text-slate-900 tracking-tight truncate">{{ appName }}</span>
                        <span class="text-[8px] font-bold uppercase px-1.5 py-0.2 rounded-[3px] bg-blue-50 text-blue-700 border border-blue-200 flex-shrink-0">ROOT</span>
                    </div>
                    <span class="text-[10px] font-medium text-slate-400 truncate">{{ appTagline }}</span>
                </div>
            </Link>
        </div>

        <!-- Sidebar Search Input -->
        <div v-if="!isCollapsed" class="px-2 pt-2 pb-1 flex-shrink-0">
            <div class="relative">
                <MagnifyingGlassIcon class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2" />
                <input 
                    :value="sidebarSearch"
                    @input="emit('update:sidebarSearch', $event.target.value)"
                    type="text" 
                    placeholder="Filter menu..." 
                    class="w-full bg-[#F8FAFC] border border-[#E2E8F0] rounded-[4px] pl-7 pr-2.5 py-1 text-[11.5px] font-medium text-slate-800 placeholder:text-slate-400 focus:bg-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500/20 transition"
                />
            </div>
        </div>

        <!-- 20 Nested Categories Navigation (Crisp Rectangular Boxed Style) -->
        <div class="flex-1 overflow-y-auto px-2 py-1.5 space-y-1">
            <div v-for="cat in filteredNavigation" :key="cat.id" class="space-y-0.5">
                <!-- Direct Link Card (e.g. Executive Dashboard) -->
                <Link 
                    v-if="cat.subItems.length === 0"
                    :href="cat.href"
                    :class="[
                        'w-full flex items-center justify-between px-2.5 py-1 rounded-[4px] border text-[12.5px] transition-colors duration-150 select-none group',
                        cat.active 
                            ? 'bg-[#EFF6FF] border-[#BFDBFE] border-l-[3.5px] border-l-[#2563EB] text-[#1D4ED8] font-semibold' 
                            : 'bg-white border-[#E2E8F0] text-[#334155] hover:bg-[#F8FAFC] hover:border-[#CBD5E1] hover:text-[#0F172A]',
                        isCollapsed ? 'justify-center px-1.5' : ''
                    ]"
                    :title="isCollapsed ? cat.title : ''"
                >
                    <div class="flex items-center gap-2 overflow-hidden">
                        <component 
                            :is="cat.icon" 
                            :class="[
                                'w-4 h-4 flex-shrink-0 transition-colors',
                                cat.active ? 'text-[#2563EB]' : 'text-slate-600 group-hover:text-slate-900'
                            ]" 
                        />
                        <span v-if="!isCollapsed" class="text-[12.5px] font-medium tracking-tight truncate" :class="cat.active ? 'text-[#1D4ED8] font-semibold' : 'text-[#334155]'">
                            {{ cat.title }}
                        </span>
                    </div>
                </Link>

                <!-- Accordion Parent Category with Sub-items -->
                <div v-else class="space-y-0.5">
                    <button
                        type="button"
                        @click="isCollapsed ? emit('update:isCollapsed', false) : toggleGroup(cat)"
                        :class="[
                            'w-full flex items-center justify-between px-2.5 py-1 rounded-[4px] border text-[12.5px] transition-colors duration-150 cursor-pointer select-none text-left group',
                            cat.active 
                                ? 'bg-[#EFF6FF] border-[#BFDBFE] border-l-[3.5px] border-l-[#2563EB] text-[#1D4ED8] font-semibold' 
                                : 'bg-white border-[#E2E8F0] text-[#334155] hover:bg-[#F8FAFC] hover:border-[#CBD5E1] hover:text-[#0F172A]',
                            isCollapsed ? 'justify-center px-1.5' : ''
                        ]"
                        :title="isCollapsed ? cat.title : ''"
                    >
                        <div class="flex items-center gap-2 overflow-hidden">
                            <component 
                                :is="cat.icon" 
                                :class="[
                                    'w-4 h-4 flex-shrink-0 transition-colors',
                                    cat.active ? 'text-[#2563EB]' : 'text-slate-600 group-hover:text-slate-900'
                                ]" 
                            />
                            <span v-if="!isCollapsed" class="text-[12.5px] font-medium tracking-tight truncate" :class="cat.active ? 'text-[#1D4ED8] font-semibold' : 'text-[#334155]'">
                                {{ cat.title }}
                            </span>
                        </div>

                        <div v-if="!isCollapsed" class="flex items-center gap-1.5 flex-shrink-0">
                            <span v-if="cat.badge" class="text-[8px] font-bold uppercase px-1.5 py-0.2 rounded-[3px] bg-slate-100 text-slate-600 border border-slate-200">
                                {{ cat.badge }}
                            </span>
                            <ChevronRightIcon 
                                :class="[
                                    'w-3.5 h-3.5 text-slate-400 transition-transform duration-200',
                                    isGroupOpen(cat) ? 'transform rotate-90 text-[#2563EB]' : 'group-hover:text-slate-600'
                                ]" 
                            />
                        </div>
                    </button>

                    <!-- Nested Sub-Items (Boxed Cards with Border matching Main Menu) -->
                    <div 
                        v-show="!isCollapsed && isGroupOpen(cat)" 
                        class="my-0.5 pl-2.5 space-y-1 animate-in slide-in-from-top-1 duration-150"
                    >
                        <template v-for="sub in cat.subItems" :key="sub.name">
                            <!-- External sub-link -->
                            <a
                                v-if="sub.external"
                                :href="sub.href"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="flex items-center justify-between px-2.5 py-1 text-[12px] font-medium rounded-[4px] bg-white border border-[#E2E8F0] text-[#334155] hover:bg-[#F8FAFC] hover:border-[#CBD5E1] hover:text-[#0F172A] transition duration-150 group"
                            >
                                <span class="truncate">{{ sub.name }}</span>
                                <div class="flex items-center gap-1">
                                    <span v-if="sub.badge" class="text-[7.5px] font-bold uppercase px-1 py-0.2 rounded-[3px] bg-amber-50 text-amber-700 border border-amber-200">
                                        {{ sub.badge }}
                                    </span>
                                    <ArrowTopRightOnSquareIcon class="w-3 h-3 text-slate-400 group-hover:text-[#2563EB] flex-shrink-0" />
                                </div>
                            </a>

                            <!-- Internal Inertia sub-link -->
                            <Link
                                v-else
                                :href="sub.href"
                                :class="[
                                    'flex items-center justify-between px-2.5 py-1 text-[12px] rounded-[4px] transition duration-150 group',
                                    sub.active 
                                        ? 'bg-[#EFF6FF] text-[#1D4ED8] font-semibold border border-[#BFDBFE]' 
                                        : 'bg-white border border-[#E2E8F0] text-[#334155] hover:bg-[#F8FAFC] hover:border-[#CBD5E1] hover:text-[#0F172A] font-medium'
                                ]"
                            >
                                <span class="truncate">{{ sub.name }}</span>
                                <span v-if="sub.badge" class="text-[7.5px] font-bold uppercase px-1 py-0.2 rounded-[3px] bg-slate-100 text-slate-600 border border-slate-200">
                                    {{ sub.badge }}
                                </span>
                                <span v-else-if="sub.active" class="w-1.5 h-1.5 rounded-full bg-[#2563EB]"></span>
                            </Link>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom User Profile Drawer (Compact) -->
        <div class="p-2 border-t border-[#E2E8F0] bg-white flex-shrink-0">
            <div class="flex items-center justify-between p-1.5 rounded-[4px] bg-[#F8FAFC] border border-[#E2E8F0]">
                <div class="flex items-center gap-2 overflow-hidden">
                    <div class="w-7 h-7 rounded-[4px] bg-[#2563EB] text-white flex items-center justify-center font-bold text-xs flex-shrink-0">
                        {{ (user.username || 'A')[0].toUpperCase() }}
                    </div>
                    <div v-if="!isCollapsed" class="overflow-hidden leading-tight">
                        <p class="text-xs font-semibold text-slate-800 truncate">{{ user.username || 'admin' }}</p>
                        <p class="text-[9px] text-[#2563EB] font-bold uppercase tracking-wider">ROOT OWNER</p>
                    </div>
                </div>

                <Link 
                    v-if="!isCollapsed"
                    :href="route('admin.logout')" 
                    method="post" 
                    as="button" 
                    class="text-slate-400 hover:text-red-600 p-1 rounded-[4px] hover:bg-red-50 transition cursor-pointer"
                    title="Sign Out"
                >
                    <ArrowLeftOnRectangleIcon class="w-4 h-4" />
                </Link>
            </div>
        </div>
    </aside>
</template>
