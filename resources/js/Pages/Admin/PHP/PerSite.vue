<script setup>
import { ref, computed } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import RefreshButton from '@/Components/UI/RefreshButton.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'
import DataTableFilter from '@/Components/UI/DataTableFilter.vue'
import FilterSelect from '@/Components/UI/FilterSelect.vue'
import RowActionDropdown from '@/Components/UI/RowActionDropdown.vue'

import {
    CpuChipIcon,
    ShieldCheckIcon,
    SparklesIcon,
    TrashIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    ArrowLeftIcon,
    XMarkIcon,
    GlobeAltIcon,
    ArrowTopRightOnSquareIcon,
    ArrowsRightLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    websites: {
        type: Array,
        default: () => [],
    },
    installedVersions: {
        type: Array,
        default: () => [],
    },
    defaultVersion: {
        type: String,
        default: '8.3',
    },
    driftReports: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            total_websites: 0,
            default_count: 0,
            legacy_count: 0,
            drift_count: 0,
            default_version: '8.3',
        }),
    },
})

// Toast Feedback
const feedbackMsg = ref('')

// Filters
const search = ref('')
const selectedVersionFilter = ref('all')
const selectedStatusFilter = ref('all')

// Multi-Selection for Batch Actions
const selectedSiteIds = ref([])
const isAllSelected = computed(() => {
    return filteredWebsites.value.length > 0 && 
        filteredWebsites.value.every(w => selectedSiteIds.value.includes(w.id))
})

const toggleSelectAll = () => {
    if (isAllSelected.value) {
        selectedSiteIds.value = []
    } else {
        selectedSiteIds.value = filteredWebsites.value.map(w => w.id)
    }
}

const filteredWebsites = computed(() => {
    let list = props.websites || []

    if (search.value.trim()) {
        const q = search.value.toLowerCase()
        list = list.filter(w => 
            w.domain.toLowerCase().includes(q) ||
            (w.subscription?.user?.name || '').toLowerCase().includes(q) ||
            (w.subscription?.user?.email || '').toLowerCase().includes(q) ||
            (w.php_version || '').includes(q)
        )
    }

    if (selectedVersionFilter.value !== 'all') {
        list = list.filter(w => w.php_version === selectedVersionFilter.value)
    }

    if (selectedStatusFilter.value !== 'all') {
        list = list.filter(w => w.status === selectedStatusFilter.value)
    }

    return list
})

const resetFilters = () => {
    search.value = ''
    selectedVersionFilter.value = 'all'
    selectedStatusFilter.value = 'all'
}

// 1. SINGLE SWITCH MODAL
const showSwitchModal = ref(false)
const selectedWebsite = ref(null)
const targetVersion = ref('8.3')
const compatibilityReport = ref(null)
const isCheckingCompatibility = ref(false)
const switchForm = useForm({
    website_id: '',
    target_version: '',
})

const openSwitchModal = (site) => {
    selectedWebsite.value = site
    targetVersion.value = site.php_version === '8.3' ? '8.4' : '8.3'
    compatibilityReport.value = null
    showSwitchModal.value = true
    fetchCompatibility(site.id, targetVersion.value)
}

const fetchCompatibility = async (siteId, version) => {
    isCheckingCompatibility.value = true
    try {
        const res = await fetch(route('admin.php.compatibility'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({ website_id: siteId, target_version: version })
        })
        compatibilityReport.value = await res.json()
    } catch (e) {
        console.error(e)
    } finally {
        isCheckingCompatibility.value = false
    }
}

const submitSingleSwitch = () => {
    if (!selectedWebsite.value) return
    switchForm.website_id = selectedWebsite.value.id
    switchForm.target_version = targetVersion.value

    switchForm.post(route('admin.php.switch-site'), {
        preserveScroll: true,
        onSuccess: () => {
            showSwitchModal.value = false
            feedbackMsg.value = `Domain '${selectedWebsite.value.domain}' switched to PHP ${targetVersion.value}.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 2. BATCH SWITCH MODAL
const showBatchModal = ref(false)
const batchTargetVersion = ref(props.defaultVersion || '8.3')
const batchForm = useForm({
    website_ids: [],
    target_version: '',
})

const openBatchModal = () => {
    if (selectedSiteIds.value.length === 0) return
    showBatchModal.value = true
}

const submitBatchSwitch = () => {
    batchForm.website_ids = selectedSiteIds.value
    batchForm.target_version = batchTargetVersion.value

    batchForm.post(route('admin.php.batch-switch'), {
        preserveScroll: true,
        onSuccess: () => {
            showBatchModal.value = false
            selectedSiteIds.value = []
            feedbackMsg.value = `Bulk switched ${batchForm.website_ids.length} websites to PHP ${batchTargetVersion.value}.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 3. DRIFT REPAIR
const repairDrift = (siteId) => {
    useForm({ website_id: siteId }).post(route('admin.php.drift.repair'), {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = 'Nginx VirtualHost socket drift repaired.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}
</script>

<template>
    <Head title="Per-Site Multi-PHP Switcher - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'PHP Manager', href: route('admin.php.index') },
                    { label: 'Per-Site PHP' }
                ]"
            >
                <template #actions>
                    <Link
                        :href="route('admin.php.index')"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowLeftIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>PHP Overview</span>
                    </Link>

                    <button
                        v-if="selectedSiteIds.length > 0"
                        type="button"
                        @click="openBatchModal"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowsRightLeftIcon class="w-3.5 h-3.5" />
                        <span>Batch Switch ({{ selectedSiteIds.length }})</span>
                    </button>

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
                    title="Configured VirtualHosts"
                    :value="String(stats.total_websites || 0)"
                    badge="Domains"
                    badgeType="info"
                    color="blue"
                    :icon="GlobeAltIcon"
                />

                <InfoCard
                    title="Using Default Engine"
                    :value="String(stats.default_count || 0)"
                    :badge="`PHP ${stats.default_version || '8.3'}`"
                    badgeType="success"
                    color="emerald"
                    :icon="SparklesIcon"
                />

                <InfoCard
                    title="Legacy PHP Runtimes"
                    :value="String(stats.legacy_count || 0)"
                    :badge="stats.legacy_count > 0 ? 'Legacy' : 'Clean'"
                    :badgeType="stats.legacy_count > 0 ? 'warning' : 'success'"
                    color="amber"
                    :icon="CpuChipIcon"
                />

                <InfoCard
                    title="Configuration Drifts"
                    :value="String(stats.drift_count || 0)"
                    :badge="stats.drift_count > 0 ? 'Drift' : 'In Sync'"
                    :badgeType="stats.drift_count > 0 ? 'danger' : 'success'"
                    color="purple"
                    :icon="ShieldCheckIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search virtualhost domain, subscriber username, or PHP version..."
                @search="() => {}"
                @filter="() => {}"
                @reset="resetFilters"
            >
                <FilterSelect
                    v-model="selectedVersionFilter"
                    label="PHP Version"
                    :options="[
                        { label: 'All PHP Versions', value: 'all' },
                        ...installedVersions.map(v => ({ label: `PHP ${v}`, value: v }))
                    ]"
                    placeholder="All PHP Versions"
                />

                <FilterSelect
                    v-model="selectedStatusFilter"
                    label="Website Status"
                    :options="[
                        { label: 'All Statuses', value: 'all' },
                        { label: 'Active', value: 'active' },
                        { label: 'Suspended', value: 'suspended' }
                    ]"
                    placeholder="All Statuses"
                />
            </DataTableFilter>

            <!-- 4. Standard Data Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-10 text-center">
                                    <input 
                                        type="checkbox" 
                                        :checked="isAllSelected" 
                                        @change="toggleSelectAll" 
                                        class="w-3.5 h-3.5 rounded text-blue-600 focus:ring-blue-500 border-slate-300"
                                    />
                                </th>
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">VirtualHost & Domain</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Hosting Account / Owner</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Active PHP Runtime</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Socket Synchronization</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Status</th>
                                <th class="py-2.5 px-3 w-28">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(site, index) in filteredWebsites" :key="site.id" class="hover:bg-blue-50/30 transition">
                                <!-- Checkbox -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-center whitespace-nowrap">
                                    <input 
                                        type="checkbox" 
                                        :value="site.id" 
                                        v-model="selectedSiteIds" 
                                        class="w-3.5 h-3.5 rounded text-blue-600 focus:ring-blue-500 border-slate-300"
                                    />
                                </td>

                                <!-- # -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>

                                <!-- Domain -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs border border-blue-100 shrink-0">
                                            <GlobeAltIcon class="w-3.5 h-3.5" />
                                        </div>
                                        <div>
                                            <a :href="`http://${site.domain}`" target="_blank" class="font-bold text-slate-900 hover:text-blue-600 inline-flex items-center gap-1 leading-tight">
                                                <span>{{ site.domain }}</span>
                                                <ArrowTopRightOnSquareIcon class="w-3 h-3 text-slate-400" />
                                            </a>
                                            <span class="text-[10px] font-mono text-slate-400 block truncate max-w-xs">{{ site.document_root }}</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Owner -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap">
                                    <div v-if="site.subscription" class="space-y-0.5">
                                        <span class="font-bold text-slate-900 block leading-tight">{{ site.subscription.user?.name || site.subscription.domain }}</span>
                                        <span class="text-[10px] font-mono text-slate-400 block">@{{ site.subscription.username }}</span>
                                    </div>
                                    <span v-else class="text-slate-400 italic">Standalone</span>
                                </td>

                                <!-- Active PHP -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span class="px-2.5 py-0.5 rounded-[3px] text-[10.5px] font-bold font-mono bg-purple-50 text-purple-700 border border-purple-200">
                                        PHP {{ site.php_version || '8.3' }}
                                    </span>
                                </td>

                                <!-- Drift Sync State -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        v-if="!site.has_drift"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-[3px] text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200"
                                    >
                                        <CheckCircleIcon class="w-3.5 h-3.5 text-emerald-600" />
                                        <span>Synchronized</span>
                                    </span>
                                    <button 
                                        v-else
                                        type="button"
                                        @click="repairDrift(site.id)"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-[3px] text-[10px] font-bold bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 transition cursor-pointer"
                                        title="Click to repair Nginx socket drift"
                                    >
                                        <ExclamationTriangleIcon class="w-3.5 h-3.5 text-rose-600" />
                                        <span>Repair Drift</span>
                                    </button>
                                </td>

                                <!-- Status -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="site.status === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200'"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-[3px] text-[10px] font-bold capitalize border"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full" :class="site.status === 'active' ? 'bg-emerald-500' : 'bg-amber-500'"></span>
                                        {{ site.status }}
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <button 
                                        type="button" 
                                        @click="openSwitchModal(site)"
                                        class="px-2.5 py-1 bg-white hover:bg-slate-50 text-blue-700 font-bold rounded-[3px] text-xs border border-slate-200 shadow-2xs transition cursor-pointer"
                                    >
                                        Switch ✎
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="!filteredWebsites || filteredWebsites.length === 0">
                                <td colspan="8" class="py-12 text-center text-slate-400">
                                    No websites found matching criteria.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 1. SINGLE SWITCH MODAL -->
        <div v-if="showSwitchModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <CpuChipIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Switch Runtime: {{ selectedWebsite?.domain }}
                        </h3>
                    </div>
                    <button @click="showSwitchModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-3 text-xs">
                    <div class="p-2.5 bg-slate-50 rounded-[3px] border border-slate-200 space-y-0.5">
                        <span class="text-[10px] font-bold uppercase text-slate-400 block">Current Version</span>
                        <span class="font-mono font-bold text-slate-900 text-xs">PHP {{ selectedWebsite?.php_version || '8.3' }}</span>
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Target PHP Runtime</label>
                        <select 
                            v-model="targetVersion" 
                            @change="fetchCompatibility(selectedWebsite.id, targetVersion)"
                            class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer"
                        >
                            <option v-for="ver in installedVersions" :key="ver" :value="ver">
                                PHP {{ ver }} (PHP-FPM Socket)
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
                            <span class="font-bold text-[10.5px]">App Type: {{ compatibilityReport.app_type }}</span>
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
                            <span>Zero-Downtime Socket Rebinding</span>
                        </p>
                        <p>Nginx will test syntax before gracefully reloading fastcgi pass sockets.</p>
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
                        @click="submitSingleSwitch"
                        class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer"
                    >
                        Switch to PHP {{ targetVersion }}
                    </button>
                </div>
            </div>
        </div>

        <!-- 2. BATCH SWITCH MODAL -->
        <div v-if="showBatchModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <ArrowsRightLeftIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Batch Switch: {{ selectedSiteIds.length }} VirtualHosts
                        </h3>
                    </div>
                    <button @click="showBatchModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-3 text-xs">
                    <p class="text-slate-600">
                        Migrate fastcgi pass sockets for <strong class="text-slate-900">{{ selectedSiteIds.length }} selected domains</strong> simultaneously.
                    </p>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Target PHP Runtime</label>
                        <select 
                            v-model="batchTargetVersion" 
                            class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer"
                        >
                            <option v-for="ver in installedVersions" :key="ver" :value="ver">
                                PHP {{ ver }} (FPM Pool Socket)
                            </option>
                        </select>
                    </div>
                </div>

                <div class="px-4 py-2.5 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                    <button 
                        type="button" 
                        @click="showBatchModal = false"
                        class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button 
                        type="button" 
                        @click="submitBatchSwitch"
                        :disabled="batchForm.processing"
                        class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                    >
                        {{ batchForm.processing ? 'Migrating...' : `Batch Switch to PHP ${batchTargetVersion}` }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
