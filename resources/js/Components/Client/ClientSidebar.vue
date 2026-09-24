<script setup>
import { ref, computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { 
    HomeIcon,
    ServerIcon,
    GlobeAltIcon,
    BoltIcon,
    ShieldCheckIcon,
    CpuChipIcon,
    FolderIcon,
    CircleStackIcon,
    EnvelopeIcon,
    CommandLineIcon,
    CreditCardIcon,
    LifebuoyIcon,
    MagnifyingGlassIcon,
    ChevronRightIcon,
    ArrowLeftOnRectangleIcon,
    ArrowTopRightOnSquareIcon
} from '@heroicons/vue/24/outline'

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

// Internal search state if not using v-model
const internalSearch = ref(props.sidebarSearch || '')

// Dynamic Brand Name, Tagline & Logo from Backend
const appName = computed(() => page.props.app_name || 'DeepTouch Host')
const appTagline = computed(() => page.props.app_tagline || 'Client Control Panel')
const appLogo = computed(() => page.props.app_logo || null)

// Dynamic Accordion Expansion state
const manuallyExpanded = ref([])
const manuallyClosed = ref([])

// Client Navigation Definition (Hostinger / cPanel Architecture with Admin Boxed Card Style)
const navigation = computed(() => [
    // 1. Dashboard
    {
        id: 'dashboard',
        title: 'Dashboard',
        href: route('client.dashboard'),
        icon: HomeIcon,
        active: route().current('client.dashboard'),
        subItems: []
    },

    // 2. Hosting Plan
    {
        id: 'hosting-plan',
        title: 'Hosting Plan',
        icon: ServerIcon,
        active: route().current('subscriptions.*') || route().current('hosting.*'),
        subItems: [
            {
                name: 'Plan Details',
                href: route('subscriptions.index'),
                active: route().current('subscriptions.index') || route().current('subscriptions.show')
            },
            {
                name: 'Resources Usage',
                href: route('hosting.resources'),
                active: route().current('hosting.resources')
            },
            {
                name: 'Renew',
                href: route('hosting.renew'),
                active: route().current('hosting.renew')
            },
            {
                name: 'Upgrade',
                href: route('hosting.upgrade'),
                active: route().current('hosting.upgrade')
            }
        ]
    },

    // 3. Domains
    {
        id: 'domains',
        title: 'Domains',
        icon: GlobeAltIcon,
        active: route().current('websites.*') || route().current('domains.*'),
        subItems: [
            {
                name: 'Domains',
                href: route('websites.index'),
                active: route().current('websites.index') || route().current('websites.show') || route().current('websites.create')
            },
            {
                name: 'Subdomains',
                href: route('domains.subdomains'),
                active: route().current('domains.subdomains')
            },
            {
                name: 'Redirects',
                href: route('domains.redirects'),
                active: route().current('domains.redirects')
            }
        ]
    },

    // 4. Performance
    {
        id: 'performance',
        title: 'Performance',
        icon: BoltIcon,
        active: route().current('performance.*'),
        subItems: [
            {
                name: 'AI Troubleshooter',
                href: route('performance.troubleshooter'),
                active: route().current('performance.troubleshooter')
            },
            {
                name: 'Page Speed',
                href: route('performance.pagespeed'),
                active: route().current('performance.pagespeed')
            },
            {
                name: 'CDN',
                href: route('performance.cdn'),
                active: route().current('performance.cdn')
            }
        ]
    },

    // 5. Security
    {
        id: 'security',
        title: 'Security',
        icon: ShieldCheckIcon,
        active: route().current('security.*'),
        subItems: [
            {
                name: 'Malware Scanner',
                href: route('security.malware'),
                active: route().current('security.malware')
            },
            {
                name: 'SSL',
                href: route('security.ssl'),
                active: route().current('security.ssl')
            }
        ]
    },

    // 6. Website
    {
        id: 'website',
        title: 'Website',
        icon: CpuChipIcon,
        active: route().current('website.*'),
        subItems: [
            {
                name: 'WordPress Install',
                href: route('website.wordpress'),
                active: route().current('website.wordpress')
            },
            {
                name: 'Auto Installer',
                href: route('website.installer'),
                active: route().current('website.installer')
            },
            {
                name: 'Migrate Website',
                href: route('website.migrate'),
                active: route().current('website.migrate')
            },
            {
                name: 'Error Pages',
                href: route('website.error-pages'),
                active: route().current('website.error-pages')
            },
            {
                name: 'Logo Maker',
                href: route('website.logo-maker'),
                active: route().current('website.logo-maker')
            }
        ]
    },

    // 7. Files
    {
        id: 'files',
        title: 'Files',
        icon: FolderIcon,
        active: route().current('file.*') || route().current('files.*') || route().current('ftp-accounts.*'),
        subItems: [
            {
                name: 'File Manager',
                href: route('file.browse'),
                active: route().current('file.*')
            },
            {
                name: 'Backups',
                href: route('files.backups'),
                active: route().current('files.backups')
            },
            {
                name: 'FTP Accounts',
                href: route('ftp-accounts.index'),
                active: route().current('ftp-accounts.*')
            }
        ]
    },

    // 8. Databases
    {
        id: 'databases',
        title: 'Databases',
        icon: CircleStackIcon,
        active: route().current('databases.*'),
        subItems: [
            {
                name: 'Management',
                href: route('databases.index'),
                active: route().current('databases.index')
            },
            {
                name: 'phpMyAdmin',
                href: route('databases.sso'),
                external: true
            },
            {
                name: 'Remote MySQL',
                href: route('databases.remote'),
                active: route().current('databases.remote')
            }
        ]
    },

    // 9. Email Services (Current Feature)
    {
        id: 'emails',
        title: 'Email Services',
        icon: EnvelopeIcon,
        active: route().current('email-accounts.*'),
        subItems: [
            {
                name: 'Email Accounts',
                href: route('email-accounts.index'),
                active: route().current('email-accounts.*')
            }
        ]
    },

    // 10. Advanced
    {
        id: 'advanced',
        title: 'Advanced',
        icon: CommandLineIcon,
        active: route().current('advanced.*'),
        subItems: [
            {
                name: 'SSH Access',
                href: route('advanced.ssh'),
                active: route().current('advanced.ssh')
            },
            {
                name: 'PHP Configuration',
                href: route('advanced.php-config'),
                active: route().current('advanced.php-config')
            },
            {
                name: 'DNS Zone Editor',
                href: route('advanced.dns'),
                active: route().current('advanced.dns')
            },
            {
                name: 'Cron Jobs',
                href: route('advanced.cron'),
                active: route().current('advanced.cron')
            },
            {
                name: 'PHP Info',
                href: route('advanced.phpinfo'),
                active: route().current('advanced.phpinfo')
            },
            {
                name: 'Cache Manager',
                href: route('advanced.cache'),
                active: route().current('advanced.cache')
            },
            {
                name: 'GIT',
                href: route('advanced.git'),
                active: route().current('advanced.git')
            },
            {
                name: 'Password Protect Directories',
                href: route('advanced.protected-dirs'),
                active: route().current('advanced.protected-dirs')
            },
            {
                name: 'IP Manager',
                href: route('advanced.ip-manager'),
                active: route().current('advanced.ip-manager')
            },
            {
                name: 'Hotlink Protection',
                href: route('advanced.hotlink'),
                active: route().current('advanced.hotlink')
            },
            {
                name: 'Folder Index Manager',
                href: route('advanced.folder-index'),
                active: route().current('advanced.folder-index')
            },
            {
                name: 'Fix File Ownership',
                href: route('advanced.fix-permissions'),
                active: route().current('advanced.fix-permissions')
            },
            {
                name: 'Activity Log',
                href: route('advanced.activity-log'),
                active: route().current('advanced.activity-log')
            }
        ]
    },

    // 11. Billing & Invoices (Current Feature)
    {
        id: 'billing',
        title: 'Billing & Invoices',
        icon: CreditCardIcon,
        active: route().current('billing.*'),
        subItems: [
            {
                name: 'Invoices & Payments',
                href: route('billing.invoices'),
                active: route().current('billing.*')
            }
        ]
    },

    // 12. Support & Helpdesk (Current Feature)
    {
        id: 'support',
        title: 'Support & Helpdesk',
        icon: LifebuoyIcon,
        active: route().current('tickets.*'),
        subItems: [
            {
                name: 'Support Tickets',
                href: route('tickets.index'),
                active: route().current('tickets.*')
            }
        ]
    }
])

const isGroupOpen = (cat) => {
    if (internalSearch.value.trim()) return true
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

// Search filter across all modules and sub-items
const filteredNavigation = computed(() => {
    const query = (props.sidebarSearch || internalSearch.value || '').toLowerCase().trim()
    if (!query) {
        return navigation.value
    }
    return navigation.value.filter(item => {
        const matchesTitle = item.title.toLowerCase().includes(query)
        const matchesSub = item.subItems.some(s => s.name.toLowerCase().includes(query))
        return matchesTitle || matchesSub
    }).map(item => {
        const matchesTitle = item.title.toLowerCase().includes(query)
        if (matchesTitle) return item
        return {
            ...item,
            subItems: item.subItems.filter(s => s.name.toLowerCase().includes(query))
        }
    })
})

const onSearchInput = (val) => {
    internalSearch.value = val
    emit('update:sidebarSearch', val)
}
</script>

<template>
    <aside 
        :class="[
            'hidden lg:flex flex-col bg-white border-r border-[#E2E8F0] transition-all duration-300 z-30 sticky top-0 h-screen select-none',
            isCollapsed ? 'w-16' : 'w-[235px]'
        ]"
    >
        <!-- Dynamic Brand Header (Exact h-14 to align horizontally with Top Navbar border) -->
        <div class="h-14 px-3.5 border-b border-[#E2E8F0] flex items-center justify-between flex-shrink-0">
            <Link :href="route('client.dashboard')" class="flex items-center gap-2.5 overflow-hidden group">
                <!-- Dynamic Logo or Signature Icon -->
                <div v-if="appLogo" class="w-8 h-8 rounded-[5px] overflow-hidden flex items-center justify-center flex-shrink-0 bg-white border border-[#E2E8F0]">
                    <img :src="appLogo" :alt="appName" class="w-full h-full object-contain" />
                </div>
                <div v-else class="w-8 h-8 rounded-[5px] bg-gradient-to-tr from-blue-600 to-indigo-600 text-white flex items-center justify-center font-extrabold text-sm shadow-[0_2px_8px_rgba(37,99,235,0.3)] flex-shrink-0 group-hover:scale-105 transition-transform">
                    {{ (appName || 'D')[0].toUpperCase() }}
                </div>
                <div v-if="!isCollapsed" class="flex flex-col overflow-hidden leading-tight">
                    <div class="flex items-center gap-1.5">
                        <span class="text-[13.5px] font-black text-slate-900 tracking-tight truncate">{{ appName }}</span>
                        <span class="text-[8px] font-bold uppercase px-1.5 py-0.2 rounded-[3px] bg-blue-50 text-blue-700 border border-blue-200 flex-shrink-0">CLIENT</span>
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
                    :value="props.sidebarSearch || internalSearch"
                    @input="onSearchInput($event.target.value)"
                    type="text" 
                    placeholder="Filter menu..." 
                    class="w-full bg-[#F8FAFC] border border-[#E2E8F0] rounded-[4px] pl-7 pr-2.5 py-1 text-[11.5px] font-medium text-slate-800 placeholder:text-slate-400 focus:bg-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500/20 transition"
                />
            </div>
        </div>

        <!-- Nested Categories Navigation (Admin Boxed Style) -->
        <div class="flex-1 overflow-y-auto px-2 py-1.5 space-y-1">
            <div v-for="cat in filteredNavigation" :key="cat.id" class="space-y-0.5">
                <!-- Direct Link Card (e.g. Dashboard) -->
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
                            <ChevronRightIcon 
                                :class="[
                                    'w-3.5 h-3.5 text-slate-400 transition-transform duration-200',
                                    isGroupOpen(cat) ? 'transform rotate-90 text-[#2563EB]' : 'group-hover:text-slate-600'
                                ]" 
                            />
                        </div>
                    </button>

                    <!-- Nested Sub-Items (Boxed Cards with Border matching Admin Menu) -->
                    <div 
                        v-show="!isCollapsed && isGroupOpen(cat)" 
                        class="my-0.5 pl-2.5 space-y-1 animate-in slide-in-from-top-1 duration-150"
                    >
                        <template v-for="sub in cat.subItems" :key="sub.name">
                            <!-- External sub-link (e.g. phpMyAdmin SSO) -->
                            <a
                                v-if="sub.external"
                                :href="sub.href"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="flex items-center justify-between px-2.5 py-1 text-[12px] font-medium rounded-[4px] bg-white border border-[#E2E8F0] text-[#334155] hover:bg-[#F8FAFC] hover:border-[#CBD5E1] hover:text-[#0F172A] transition duration-150 group"
                            >
                                <span class="truncate">{{ sub.name }}</span>
                                <ArrowTopRightOnSquareIcon class="w-3 h-3 text-slate-400 group-hover:text-[#2563EB] flex-shrink-0" />
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
                                <span v-if="sub.active" class="w-1.5 h-1.5 rounded-full bg-[#2563EB]"></span>
                            </Link>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom User Profile Drawer (Exact Admin Compact Style) -->
        <div class="p-2 border-t border-[#E2E8F0] bg-white flex-shrink-0">
            <div class="flex items-center justify-between p-1.5 rounded-[4px] bg-[#F8FAFC] border border-[#E2E8F0]">
                <div class="flex items-center gap-2 overflow-hidden">
                    <div class="w-7 h-7 rounded-[4px] bg-[#2563EB] text-white flex items-center justify-center font-bold text-xs flex-shrink-0">
                        {{ ((user.name || user.username || 'C')[0]).toUpperCase() }}
                    </div>
                    <div v-if="!isCollapsed" class="overflow-hidden leading-tight">
                        <p class="text-xs font-semibold text-slate-800 truncate">{{ user.name || user.username || 'Client' }}</p>
                        <p class="text-[9px] text-[#2563EB] font-bold uppercase tracking-wider">CLIENT ACCOUNT</p>
                    </div>
                </div>

                <Link 
                    v-if="!isCollapsed"
                    :href="route('logout')" 
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
