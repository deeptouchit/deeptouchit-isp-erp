<script setup>
import { ref, computed } from 'vue'
import { Head, useForm, router, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import RefreshButton from '@/Components/UI/RefreshButton.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'
import DataTableFilter from '@/Components/UI/DataTableFilter.vue'
import RowActionDropdown from '@/Components/UI/RowActionDropdown.vue'

import { 
    CpuChipIcon, 
    ServerIcon, 
    GlobeAltIcon, 
    WrenchScrewdriverIcon, 
    ArrowPathIcon, 
    BoltIcon,
    ShieldCheckIcon,
    CheckCircleIcon,
    CommandLineIcon,
    ClockIcon,
    AdjustmentsHorizontalIcon,
    XMarkIcon,
    PlayIcon,
    PauseIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    summary: {
        type: Object,
        default: () => ({})
    },
    discovered: {
        type: Object,
        default: () => ({})
    },
    selectedVersion: {
        type: String,
        default: '8.3'
    },
    currentTab: {
        type: String,
        default: 'versions'
    },
    directives: {
        type: Object,
        default: () => ({})
    },
    pools: {
        type: Object,
        default: () => ({})
    },
    websites: {
        type: Array,
        default: () => []
    },
    auditLogs: {
        type: Array,
        default: () => []
    },
    supportedDirectives: {
        type: Object,
        default: () => ({})
    }
})

// Flash Feedback
const feedbackMsg = ref('')

// Tabs State
const activeTab = ref(props.currentTab || 'versions')
const currentConfigVersion = ref(props.selectedVersion || Object.keys(props.discovered)[0] || '8.3')
const currentPoolVersion = ref(props.selectedVersion || Object.keys(props.discovered)[0] || '8.3')
const selectedExtVersion = ref(props.selectedVersion || Object.keys(props.discovered)[0] || '8.3')

// Search Queries
const siteSearchQuery = ref('')
const configSearchQuery = ref('')
const extensionSearchQuery = ref('')

// Site switch modal state
const showSwitchModal = ref(false)
const selectedWebsiteForSwitch = ref(null)
const targetPhpVersion = ref('8.3')
const compatibilityReport = ref(null)
const isCheckingCompatibility = ref(false)

// Config Form state
const configForm = useForm({
    version: currentConfigVersion.value,
    settings: {}
})

// Pool Form state
const selectedPoolName = ref('www')
const poolForm = useForm({
    version: currentPoolVersion.value,
    pool_name: 'www',
    settings: {
        pm: 'dynamic',
        pm_max_children: 50,
        pm_start_servers: 5,
        pm_min_spare_servers: 5,
        pm_max_spare_servers: 35,
        pm_max_requests: 500,
    }
})

// Initialize Directives form
const initConfigForm = (ver) => {
    currentConfigVersion.value = ver
    configForm.version = ver
    const newSettings = {}
    if (props.directives) {
        Object.keys(props.directives).forEach(k => {
            newSettings[k] = props.directives[k]?.value ?? ''
        })
    }
    configForm.settings = newSettings
}

const submitConfigForm = () => {
    configForm.post(route('admin.php.update-config'), {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = `php.ini for PHP ${configForm.version} saved & FPM reloaded.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// Pool form submit
const submitPoolForm = () => {
    poolForm.version = currentPoolVersion.value
    poolForm.post(route('admin.php.update-pool'), {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = `FPM Pool settings for PHP ${poolForm.version} updated.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// Change Default PHP
const changeDefaultVersion = (ver) => {
    router.post(route('admin.php.set-default'), { version: ver }, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = `Global system default PHP updated to PHP ${ver}.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// Service Action (restart, reload, stop, start)
const runServiceAction = (version, action) => {
    const actionLabel = action === 'restart' ? 'Restart' : (action === 'reload' ? 'Reload' : action)
    if (confirm(`${actionLabel} PHP-FPM service for PHP ${version}?`)) {
        useForm({ version, action }).post(route('admin.php.service-action'), {
            preserveScroll: true,
            onSuccess: () => {
                feedbackMsg.value = `PHP ${version} FPM service ${action}ed.`
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    }
}

// Flush OPcache
const flushOpcache = (version) => {
    if (confirm(`Flush Zend OPcache for PHP ${version}? This will trigger a graceful reload.`)) {
        useForm({ version }).post(route('admin.php.opcache-flush'), {
            preserveScroll: true,
            onSuccess: () => {
                feedbackMsg.value = `OPcache cache cleared for PHP ${version}.`
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    }
}

// Check Compatibility on target version change
const fetchCompatibility = async () => {
    if (!selectedWebsiteForSwitch.value) return
    isCheckingCompatibility.value = true
    try {
        const res = await fetch(route('admin.php.compatibility'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({
                website_id: selectedWebsiteForSwitch.value.id,
                target_version: targetPhpVersion.value
            })
        })
        compatibilityReport.value = await res.json()
    } catch (e) {
        console.error(e)
    } finally {
        isCheckingCompatibility.value = false
    }
}

// Open Site Switch Modal
const openSwitchModal = (site) => {
    selectedWebsiteForSwitch.value = site
    targetPhpVersion.value = site.php_version === '8.3' ? '8.4' : '8.3'
    compatibilityReport.value = null
    showSwitchModal.value = true
    fetchCompatibility()
}

const submitSiteSwitch = () => {
    if (!selectedWebsiteForSwitch.value) return

    useForm({
        website_id: selectedWebsiteForSwitch.value.id,
        target_version: targetPhpVersion.value
    }).post(route('admin.php.switch-site'), {
        preserveScroll: true,
        onSuccess: () => {
            showSwitchModal.value = false
            selectedWebsiteForSwitch.value = null
            feedbackMsg.value = 'PHP socket switched successfully.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// Filtered Sites
const filteredWebsites = computed(() => {
    if (!siteSearchQuery.value.trim()) return props.websites
    const q = siteSearchQuery.value.toLowerCase()
    return props.websites.filter(w => 
        w.domain.toLowerCase().includes(q) || 
        (w.subscription?.username || '').toLowerCase().includes(q) ||
        (w.php_version || '').toLowerCase().includes(q)
    )
})

// Filtered Directives
const filteredDirectives = computed(() => {
    if (!configSearchQuery.value.trim()) return props.directives
    const q = configSearchQuery.value.toLowerCase()
    const result = {}
    Object.keys(props.directives).forEach(k => {
        if (k.toLowerCase().includes(q) || (props.directives[k]?.value || '').toLowerCase().includes(q)) {
            result[k] = props.directives[k]
        }
    })
    return result
})

// Filtered Extensions
const currentExtensions = computed(() => {
    const exts = props.discovered[selectedExtVersion.value]?.extensions || []
    if (!extensionSearchQuery.value.trim()) return exts
    const q = extensionSearchQuery.value.toLowerCase()
    return exts.filter(e => e.toLowerCase().includes(q))
})

const tabsList = [
    { id: 'versions', name: 'PHP Versions', icon: CpuChipIcon, count: Object.keys(props.discovered || {}).length },
    { id: 'config', name: 'php.ini Directives', icon: WrenchScrewdriverIcon },
    { id: 'sites', name: 'Per-Site Switcher', icon: GlobeAltIcon, count: props.websites.length },
    { id: 'pools', name: 'FPM Pools & Workers', icon: AdjustmentsHorizontalIcon },
    { id: 'extensions', name: 'Loaded Extensions', icon: CommandLineIcon },
    { id: 'audit', name: 'Audit Logs', icon: ClockIcon, count: props.auditLogs.length },
]
</script>

<template>
    <Head title="PHP Manager & Multi-FPM Suite - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header with Active RefreshButton -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'PHP Manager' }
                ]"
            >
                <template #actions>
                    <!-- Default PHP Version Selector -->
                    <div class="flex items-center gap-1.5 bg-white px-2.5 py-1.5 rounded-[3px] border border-slate-200 shadow-2xs text-xs">
                        <span class="font-bold text-slate-500">Default:</span>
                        <select 
                            :value="summary.default_php_version"
                            @change="changeDefaultVersion($event.target.value)"
                            class="bg-blue-50 border border-blue-200 text-xs font-bold text-blue-700 rounded-[2px] py-0.5 px-2 focus:ring-1 focus:ring-blue-500 cursor-pointer outline-none"
                        >
                            <option v-for="(info, ver) in discovered" :key="ver" :value="ver">
                                PHP {{ ver }}
                            </option>
                        </select>
                    </div>

                    <!-- Enhanced Smart Refresh Button -->
                    <RefreshButton />
                </template>
            </PageHeader>

            <!-- Feedback Banner -->
            <div v-if="feedbackMsg" class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-3 py-2 rounded-[3px] text-xs font-bold flex items-center justify-between shadow-2xs">
                <div class="flex items-center gap-2">
                    <CheckCircleIcon class="w-4 h-4 text-emerald-600" />
                    <span>{{ feedbackMsg }}</span>
                </div>
                <button @click="feedbackMsg = ''" class="text-emerald-600 hover:text-emerald-800 text-xs cursor-pointer">✕</button>
            </div>

            <!-- 2. Ultra-Compact KPI Info Cards (56px) -->
            <InfoCardsGrid :cols="4">
                <InfoCard
                    title="Installed Engines"
                    :value="String(summary.total_installed || 0)"
                    badge="Runtimes"
                    badgeType="info"
                    color="blue"
                    :icon="CpuChipIcon"
                />

                <InfoCard
                    title="Active FPM Daemons"
                    :value="`${summary.running_fpm_services || 0} / ${summary.total_installed || 0}`"
                    badge="Active"
                    badgeType="success"
                    color="emerald"
                    :icon="ServerIcon"
                />

                <InfoCard
                    title="Mapped Websites"
                    :value="String(summary.total_websites || 0)"
                    badge="VHosts"
                    badgeType="info"
                    color="sky"
                    :icon="GlobeAltIcon"
                />

                <InfoCard
                    title="Zend OPcache"
                    value="Active"
                    badge="Accelerated"
                    badgeType="success"
                    color="purple"
                    :icon="BoltIcon"
                />
            </InfoCardsGrid>

            <!-- Navigation Tabs Bar -->
            <div class="bg-white p-1 rounded-[4px] border border-[#E2E8F0] shadow-2xs flex flex-wrap items-center gap-1">
                <button
                    v-for="tab in tabsList"
                    :key="tab.id"
                    type="button"
                    @click="activeTab = tab.id"
                    :class="activeTab === tab.id ? 'bg-blue-600 text-white font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50 font-medium'"
                    class="px-3 py-1.5 rounded-[3px] text-xs flex items-center gap-1.5 transition cursor-pointer"
                >
                    <component :is="tab.icon" class="w-3.5 h-3.5" />
                    <span>{{ tab.name }}</span>
                    <span 
                        v-if="tab.count !== undefined" 
                        :class="activeTab === tab.id ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-500 font-mono'"
                        class="px-1.5 py-0.2 rounded-full text-[9.5px] font-bold"
                    >
                        {{ tab.count }}
                    </span>
                </button>
            </div>

            <!-- 1. TAB: PHP Versions & FPM Services Grid -->
            <div v-show="activeTab === 'versions'" class="grid grid-cols-1 md:grid-cols-3 gap-3.5">
                <div 
                    v-for="(verData, ver) in summary.versions" 
                    :key="ver"
                    class="bg-white rounded-[4px] border border-[#E2E8F0] p-4 shadow-2xs hover:border-blue-300 transition flex flex-col justify-between space-y-3"
                >
                    <div>
                        <!-- Header -->
                        <div class="flex justify-between items-start pb-2.5 border-b border-slate-100">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-black text-sm border border-blue-100">
                                    {{ ver }}
                                </div>
                                <div>
                                    <div class="flex items-center gap-1.5">
                                        <h3 class="text-xs font-bold text-slate-900">PHP {{ ver }}</h3>
                                        <span v-if="ver === summary.default_php_version" class="px-1.5 py-0.2 rounded-[2px] text-[9px] font-bold uppercase bg-blue-50 text-blue-700 border border-blue-200">
                                            Default
                                        </span>
                                    </div>
                                    <p class="text-[10px] text-slate-400 font-mono">{{ verData.info.fpm_service }}</p>
                                </div>
                            </div>

                            <span 
                                :class="verData.info.fpm_status === 'running' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-[3px] text-[10px] font-bold border capitalize"
                            >
                                <span class="w-1.5 h-1.5 rounded-full" :class="verData.info.fpm_status === 'running' ? 'bg-emerald-500' : 'bg-rose-500'"></span>
                                {{ verData.info.fpm_status }}
                            </span>
                        </div>

                        <!-- Specs List -->
                        <div class="space-y-1.5 text-xs text-slate-600 font-medium pt-2">
                            <div class="flex justify-between items-center py-0.5 border-b border-slate-50">
                                <span class="text-slate-400 text-[11px]">Socket Path:</span>
                                <span class="font-bold text-slate-900 font-mono text-[10.5px] truncate max-w-[170px]" :title="verData.info.fpm_socket">
                                    {{ verData.info.fpm_socket }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center py-0.5 border-b border-slate-50">
                                <span class="text-slate-400 text-[11px]">Hosted Sites:</span>
                                <span class="font-bold text-blue-700 font-mono text-[11px]">{{ verData.hosted_sites }} Websites</span>
                            </div>
                            <div class="flex justify-between items-center py-0.5 border-b border-slate-50">
                                <span class="text-slate-400 text-[11px]">Active Workers:</span>
                                <span class="font-bold text-slate-900 font-mono text-[10.5px]">{{ verData.info.fpm_details?.workers || 0 }} Processes</span>
                            </div>
                            <div class="flex justify-between items-center py-0.5 border-b border-slate-50">
                                <span class="text-slate-400 text-[11px]">RAM Allocation:</span>
                                <span class="font-bold text-slate-900 font-mono text-[10.5px]">{{ verData.info.fpm_details?.memory_formatted || 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between items-center py-0.5">
                                <span class="text-slate-400 text-[11px]">Loaded Modules:</span>
                                <span class="font-bold text-slate-900 font-mono text-[10.5px]">{{ verData.info.extensions?.length || 0 }} Extensions</span>
                            </div>
                        </div>
                    </div>

                    <!-- Actions Toolbar -->
                    <div class="pt-2.5 border-t border-slate-100 flex items-center justify-between gap-1.5">
                        <button 
                            type="button" 
                            @click="runServiceAction(ver, 'reload')"
                            class="px-2 py-1 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs border border-slate-200 transition cursor-pointer flex items-center gap-1 shadow-2xs"
                            title="Reload FPM Service"
                        >
                            <ArrowPathIcon class="w-3.5 h-3.5 text-slate-500" />
                            <span>Reload</span>
                        </button>

                        <button 
                            type="button" 
                            @click="runServiceAction(ver, 'restart')"
                            class="px-2 py-1 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs border border-slate-200 transition cursor-pointer shadow-2xs"
                            title="Restart FPM Service"
                        >
                            <span>Restart</span>
                        </button>

                        <button 
                            type="button" 
                            @click="flushOpcache(ver)"
                            class="px-2 py-1 bg-blue-50 hover:bg-blue-100 text-blue-700 font-bold rounded-[3px] text-xs border border-blue-200 transition cursor-pointer flex items-center gap-1 shadow-2xs"
                            title="Flush Zend OPcache"
                        >
                            <BoltIcon class="w-3.5 h-3.5" />
                            <span>Flush</span>
                        </button>

                        <button 
                            type="button" 
                            @click="initConfigForm(ver); activeTab = 'config'"
                            class="px-2.5 py-1 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] text-xs transition shadow-2xs ml-auto cursor-pointer"
                        >
                            <span>php.ini ⚙</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- 2. TAB: php.ini Directives Configuration -->
            <div v-show="activeTab === 'config'" class="bg-white rounded-[4px] border border-[#E2E8F0] p-4 shadow-2xs space-y-3.5">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">php.ini Directive Editor</h3>
                        <p class="text-[11px] text-slate-400">Tune resource allocations, upload limits, execution timeouts, and debugging settings</p>
                    </div>

                    <div class="flex items-center gap-2">
                        <select 
                            :value="currentConfigVersion"
                            @change="initConfigForm($event.target.value)"
                            class="py-1.5 px-3 text-xs font-bold rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer"
                        >
                            <option v-for="(info, ver) in discovered" :key="ver" :value="ver">
                                PHP {{ ver }} php.ini
                            </option>
                        </select>

                        <input 
                            v-model="configSearchQuery" 
                            type="text" 
                            placeholder="Search directives..." 
                            class="px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 w-48 shadow-2xs"
                        />
                    </div>
                </div>

                <form @submit.prevent="submitConfigForm" class="space-y-3.5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div 
                            v-for="(meta, directive) in filteredDirectives" 
                            :key="directive"
                            class="p-3 rounded-[3px] border border-slate-200 bg-slate-50/30 hover:border-blue-300 transition space-y-1"
                        >
                            <div class="flex items-center justify-between">
                                <label class="text-xs font-bold text-slate-900 font-mono">{{ directive }}</label>
                                <span class="text-[9.5px] font-bold uppercase text-slate-400 font-mono">{{ meta.section || 'General' }}</span>
                            </div>
                            <p class="text-[10.5px] text-slate-500">{{ meta.description }}</p>
                            
                            <div class="pt-1">
                                <select 
                                    v-if="meta.type === 'select'"
                                    v-model="configForm.settings[directive]"
                                    class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-white text-slate-900 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                >
                                    <option v-for="opt in meta.options" :key="opt" :value="opt">{{ opt }}</option>
                                </select>

                                <select 
                                    v-else-if="meta.type === 'boolean'"
                                    v-model="configForm.settings[directive]"
                                    class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-white text-slate-900 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                >
                                    <option value="On">On</option>
                                    <option value="Off">Off</option>
                                </select>

                                <input 
                                    v-else
                                    v-model="configForm.settings[directive]"
                                    type="text"
                                    class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-white text-slate-900 font-mono focus:outline-none focus:ring-1 focus:ring-blue-500"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="pt-2 border-t border-slate-100 flex justify-end">
                        <button 
                            type="submit" 
                            :disabled="configForm.processing"
                            class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] text-xs transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ configForm.processing ? 'Saving...' : 'Save php.ini & Reload' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- 3. TAB: Per-Site PHP Version Switcher -->
            <div v-show="activeTab === 'sites'" class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="p-3 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 bg-slate-50/50">
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">Per-Site FastCGI Socket Mapping</h3>
                        <p class="text-[10.5px] text-slate-500">Switch PHP runtimes per domain with automated syntax testing and safety rollback</p>
                    </div>

                    <input 
                        v-model="siteSearchQuery" 
                        type="text" 
                        placeholder="Filter domains..." 
                        class="px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-white text-slate-900 focus:outline-none focus:ring-1 focus:ring-blue-500 w-56 shadow-2xs"
                    />
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Domain & VirtualHost</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Account / User</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Document Root</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Current PHP</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Status</th>
                                <th class="py-2.5 px-3 w-28">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(site, i) in filteredWebsites" :key="site.id" class="hover:bg-blue-50/30 transition">
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">{{ i + 1 }}</td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs border border-blue-100 shrink-0">
                                            <GlobeAltIcon class="w-3.5 h-3.5" />
                                        </div>
                                        <div>
                                            <span class="font-bold text-slate-900 block leading-tight">{{ site.domain }}</span>
                                            <span v-if="site.is_primary" class="text-[9.5px] text-blue-600 font-bold">Primary Domain</span>
                                        </div>
                                    </div>
                                </td>

                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap">
                                    <div v-if="site.subscription" class="space-y-0.5">
                                        <span class="font-bold text-slate-900 block leading-tight">@{{ site.subscription.username }}</span>
                                        <span class="text-[10px] font-mono text-slate-400 block">{{ site.subscription.domain }}</span>
                                    </div>
                                    <span v-else class="text-slate-400 italic">Standalone</span>
                                </td>

                                <td class="py-2.5 px-3 border-r border-slate-100 text-left font-mono text-[10.5px] text-slate-600 max-w-xs truncate" :title="site.document_root">
                                    {{ site.document_root }}
                                </td>

                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold font-mono bg-purple-50 text-purple-700 border border-purple-200">
                                        PHP {{ site.php_version || '8.3' }}
                                    </span>
                                </td>

                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="site.status === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200'"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-[3px] text-[10px] font-bold capitalize border"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full" :class="site.status === 'active' ? 'bg-emerald-500' : 'bg-amber-500'"></span>
                                        {{ site.status }}
                                    </span>
                                </td>

                                <td class="py-2.5 px-3 whitespace-nowrap text-center">
                                    <button 
                                        type="button" 
                                        @click="openSwitchModal(site)"
                                        class="px-2.5 py-1 bg-white hover:bg-slate-50 text-blue-700 font-bold rounded-[3px] text-xs border border-slate-200 shadow-2xs transition cursor-pointer"
                                    >
                                        Switch Engine ✎
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 4. TAB: FPM Process Pools -->
            <div v-show="activeTab === 'pools'" class="bg-white rounded-[4px] border border-[#E2E8F0] p-4 shadow-2xs space-y-3.5">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">PHP-FPM Worker Pool Tuning</h3>
                        <p class="text-[11px] text-slate-400">Configure process managers, child process concurrency, and worker recycling</p>
                    </div>

                    <select 
                        v-model="currentPoolVersion"
                        class="py-1.5 px-3 text-xs font-bold rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer"
                    >
                        <option v-for="(info, ver) in discovered" :key="ver" :value="ver">
                            PHP {{ ver }} FPM Pool
                        </option>
                    </select>
                </div>

                <form @submit.prevent="submitPoolForm" class="space-y-3 text-xs">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Process Manager (pm)</label>
                            <select v-model="poolForm.settings.pm" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500">
                                <option value="dynamic">dynamic (Recommended)</option>
                                <option value="static">static (Fixed Processes)</option>
                                <option value="ondemand">ondemand (Memory Saver)</option>
                            </select>
                        </div>
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Max Children (pm.max_children)</label>
                            <input v-model="poolForm.settings.pm_max_children" type="number" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 font-mono" />
                        </div>
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Start Servers (pm.start_servers)</label>
                            <input v-model="poolForm.settings.pm_start_servers" type="number" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 font-mono" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Min Spare Servers</label>
                            <input v-model="poolForm.settings.pm_min_spare_servers" type="number" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 font-mono" />
                        </div>
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Max Spare Servers</label>
                            <input v-model="poolForm.settings.pm_max_spare_servers" type="number" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 font-mono" />
                        </div>
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Max Requests (Worker Recycle)</label>
                            <input v-model="poolForm.settings.pm_max_requests" type="number" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 font-mono" />
                        </div>
                    </div>

                    <div class="pt-2 border-t border-slate-100 flex justify-end">
                        <button 
                            type="submit" 
                            :disabled="poolForm.processing"
                            class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] text-xs transition shadow-2xs cursor-pointer"
                        >
                            Save Pool Settings & Reload
                        </button>
                    </div>
                </form>
            </div>

            <!-- 5. TAB: Loaded Extensions Matrix -->
            <div v-show="activeTab === 'extensions'" class="bg-white rounded-[4px] border border-[#E2E8F0] p-4 shadow-2xs space-y-3.5">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">Loaded PHP Extensions & Modules</h3>
                        <p class="text-[11px] text-slate-400">Active bytecode modules and compiled C extensions per PHP runtime</p>
                    </div>

                    <div class="flex items-center gap-2">
                        <select 
                            v-model="selectedExtVersion"
                            class="py-1.5 px-3 text-xs font-bold rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer"
                        >
                            <option v-for="(info, ver) in discovered" :key="ver" :value="ver">
                                PHP {{ ver }} Modules ({{ info.extensions?.length || 0 }})
                            </option>
                        </select>

                        <input 
                            v-model="extensionSearchQuery" 
                            type="text" 
                            placeholder="Search modules..." 
                            class="px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 w-48 shadow-2xs"
                        />
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 gap-2">
                    <div 
                        v-for="ext in currentExtensions" 
                        :key="ext"
                        class="p-2 bg-slate-50 border border-slate-200 rounded-[3px] text-xs font-mono font-bold text-slate-800 flex items-center justify-between hover:border-blue-300 transition"
                    >
                        <span>{{ ext }}</span>
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    </div>
                </div>
            </div>

            <!-- 6. TAB: PHP Audit Logs -->
            <div v-show="activeTab === 'audit'" class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Timestamp</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Administrator</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Action Event</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Details & Changes</th>
                                <th class="py-2.5 px-3 font-mono">IP Address</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(log, idx) in auditLogs" :key="log.id || idx" class="hover:bg-blue-50/30 transition">
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">{{ idx + 1 }}</td>
                                <td class="py-2.5 px-3 border-r border-slate-100 font-mono text-slate-500 text-[10.5px] whitespace-nowrap">
                                    {{ new Date(log.created_at).toLocaleString() }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-bold text-slate-900">
                                    {{ log.user?.first_name || 'System Admin' }} (@{{ log.user?.username || 'admin' }})
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase bg-blue-50 text-blue-700 border border-blue-200 font-mono">
                                        {{ log.action }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left max-w-sm truncate text-slate-600 font-mono text-[10.5px]">
                                    {{ JSON.stringify(log.new_values) }}
                                </td>
                                <td class="py-2.5 px-3 whitespace-nowrap font-mono text-[10.5px] text-slate-500">
                                    {{ log.ip_address }}
                                </td>
                            </tr>
                            <tr v-if="!auditLogs || auditLogs.length === 0">
                                <td colspan="6" class="py-8 text-center text-slate-400">No PHP audit records found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- MODAL: Per-Site PHP Switcher with Compatibility Inspector -->
        <div v-if="showSwitchModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <CpuChipIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Switch Runtime: {{ selectedWebsiteForSwitch?.domain }}
                        </h3>
                    </div>
                    <button @click="showSwitchModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-3 text-xs">
                    <div class="p-2.5 bg-slate-50 rounded-[3px] border border-slate-200 space-y-0.5">
                        <span class="text-[10px] font-bold uppercase text-slate-400 block">Current Version</span>
                        <span class="font-mono font-bold text-slate-900 text-xs">PHP {{ selectedWebsiteForSwitch?.php_version || '8.3' }}</span>
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Select Target Runtime</label>
                        <select 
                            v-model="targetPhpVersion" 
                            @change="fetchCompatibility"
                            class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer"
                        >
                            <option v-for="(info, ver) in discovered" :key="ver" :value="ver">
                                PHP {{ ver }} ({{ info.fpm_status === 'running' ? 'Active Socket' : 'Offline' }})
                            </option>
                        </select>
                    </div>

                    <!-- Compatibility Report -->
                    <div v-if="compatibilityReport" class="p-2.5 rounded-[3px] border text-xs space-y-1" :class="[
                        compatibilityReport.status === 'compatible' ? 'bg-emerald-50 border-emerald-200 text-emerald-900' :
                        compatibilityReport.status === 'warning' ? 'bg-amber-50 border-amber-200 text-amber-900' :
                        'bg-rose-50 border-rose-200 text-rose-900'
                    ]">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-[10.5px]">Type: {{ compatibilityReport.app_type }}</span>
                            <span class="px-1.5 py-0.2 rounded text-[9.5px] font-bold capitalize bg-white/70">
                                {{ compatibilityReport.status }}
                            </span>
                        </div>
                        <div v-if="compatibilityReport.reasons?.length" class="space-y-0.5 pt-1 text-[10.5px]">
                            <p v-for="(r, idx) in compatibilityReport.reasons" :key="idx">• {{ r }}</p>
                        </div>
                    </div>

                    <div class="p-2.5 rounded-[3px] bg-blue-50/50 border border-blue-100 text-[10.5px] text-blue-800 space-y-0.5">
                        <p class="font-bold flex items-center gap-1">
                            <ShieldCheckIcon class="w-3.5 h-3.5 text-blue-600" />
                            <span>Zero-Downtime Socket Switch</span>
                        </p>
                        <p>Nginx will test configuration syntax before reloading with automatic rollback safety.</p>
                    </div>
                </div>

                <div class="px-4 py-2.5 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                    <button 
                        type="button" 
                        @click="showSwitchModal = false"
                        class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button 
                        type="button" 
                        @click="submitSiteSwitch"
                        class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer"
                    >
                        Switch to PHP {{ targetPhpVersion }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
