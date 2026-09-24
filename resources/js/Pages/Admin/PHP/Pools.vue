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
    ServerIcon,
    SparklesIcon,
    PlusIcon,
    TrashIcon,
    ArrowLeftIcon,
    XMarkIcon,
    WrenchScrewdriverIcon,
    AdjustmentsHorizontalIcon,
    ArrowPathIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    pools: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            total_pools: 0,
            total_max_workers: 0,
            pm_counts: { dynamic: 0, ondemand: 0, static: 0 },
            active_daemons: 0,
            total_versions: 0,
        }),
    },
    installedVersions: {
        type: Array,
        default: () => [],
    },
    defaultVersion: {
        type: String,
        default: '8.3',
    },
})

// Flash Feedback
const feedbackMsg = ref('')

// Filters
const search = ref('')
const selectedVersionFilter = ref('all')
const selectedPmFilter = ref('all')

const filteredPools = computed(() => {
    let list = props.pools || []

    if (search.value.trim()) {
        const q = search.value.toLowerCase()
        list = list.filter(p => 
            p.name.toLowerCase().includes(q) ||
            p.version.includes(q) ||
            (p.listen || '').toLowerCase().includes(q) ||
            (p.user || '').toLowerCase().includes(q)
        )
    }

    if (selectedVersionFilter.value !== 'all') {
        list = list.filter(p => p.version === selectedVersionFilter.value)
    }

    if (selectedPmFilter.value !== 'all') {
        list = list.filter(p => p.pm === selectedPmFilter.value)
    }

    return list
})

const resetFilters = () => {
    search.value = ''
    selectedVersionFilter.value = 'all'
    selectedPmFilter.value = 'all'
}

// 1. TUNE POOL MODAL
const showTuneModal = ref(false)
const selectedPoolForTune = ref(null)
const tuneForm = useForm({
    version: '',
    pool_name: '',
    pm: 'dynamic',
    pm_max_children: 50,
    pm_start_servers: 5,
    pm_min_spare_servers: 5,
    pm_max_spare_servers: 35,
    pm_process_idle_timeout: '10s',
    pm_max_requests: 500,
    request_terminate_timeout: '0',
    memory_limit: '512M',
})

const openTuneModal = (pool) => {
    selectedPoolForTune.value = pool
    tuneForm.version = pool.version
    tuneForm.pool_name = pool.name
    tuneForm.pm = pool.pm || 'dynamic'
    tuneForm.pm_max_children = pool.pm_max_children || 50
    tuneForm.pm_start_servers = pool.pm_start_servers || 5
    tuneForm.pm_min_spare_servers = pool.pm_min_spare_servers || 5
    tuneForm.pm_max_spare_servers = pool.pm_max_spare_servers || 35
    tuneForm.pm_process_idle_timeout = pool.pm_process_idle_timeout || '10s'
    tuneForm.pm_max_requests = pool.pm_max_requests || 500
    tuneForm.request_terminate_timeout = pool.request_terminate_timeout || '0'
    tuneForm.memory_limit = pool.memory_limit || '512M'
    showTuneModal.value = true
}

const submitTunePool = () => {
    tuneForm.post(route('admin.php.update-pool'), {
        preserveScroll: true,
        onSuccess: () => {
            showTuneModal.value = false
            feedbackMsg.value = `FPM pool '${tuneForm.pool_name}' (PHP ${tuneForm.version}) tuned and reloaded.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 2. CREATE CUSTOM POOL MODAL
const showCreateModal = ref(false)
const createForm = useForm({
    version: props.defaultVersion || props.installedVersions[0] || '8.3',
    pool_name: '',
    user: 'www-data',
    group: 'www-data',
    pm: 'dynamic',
    pm_max_children: 50,
    pm_start_servers: 5,
    pm_min_spare_servers: 5,
    pm_max_spare_servers: 35,
    pm_max_requests: 500,
})

const submitCreatePool = () => {
    createForm.post(route('admin.php.create-pool'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false
            createForm.reset()
            feedbackMsg.value = `Custom FPM pool '${createForm.pool_name}' provisioned.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 3. DELETE POOL MODAL
const showDeleteModal = ref(false)
const poolToDelete = ref(null)
const deleteForm = useForm({})

const openDeleteModal = (pool) => {
    poolToDelete.value = pool
    showDeleteModal.value = true
}

const submitDeletePool = () => {
    if (!poolToDelete.value) return
    deleteForm.post(route('admin.php.delete-pool', {
        version: poolToDelete.value.version,
        pool_name: poolToDelete.value.name,
    }), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false
            feedbackMsg.value = `Pool '${poolToDelete.value.name}' deleted.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 4. RELOAD FPM SERVICE
const reloadFpmService = (version) => {
    useForm({ version, action: 'reload' }).post(route('admin.php.service-action'), {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = `PHP ${version} FPM reloaded.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}
</script>

<template>
    <Head title="PHP-FPM Pool Manager - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'PHP Manager', href: route('admin.php.index') },
                    { label: 'FPM Pools' }
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
                        type="button" 
                        @click="showCreateModal = true"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <PlusIcon class="w-3.5 h-3.5" />
                        <span>Create Pool</span>
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
                    title="Total FPM Pools"
                    :value="String(stats.total_pools || 0)"
                    badge="Pools"
                    badgeType="info"
                    color="blue"
                    :icon="AdjustmentsHorizontalIcon"
                />

                <InfoCard
                    title="Max Concurrent Capacity"
                    :value="`${stats.total_max_workers || 0}`"
                    badge="Workers"
                    badgeType="success"
                    color="emerald"
                    :icon="CpuChipIcon"
                />

                <InfoCard
                    title="Active Daemons"
                    :value="`${stats.active_daemons || 0} / ${stats.total_versions || 0}`"
                    badge="Healthy"
                    badgeType="info"
                    color="sky"
                    :icon="ServerIcon"
                />

                <InfoCard
                    title="Dynamic vs Static"
                    :value="`${stats.pm_counts?.dynamic || 0} dyn / ${stats.pm_counts?.static || 0} st`"
                    badge="Strategies"
                    badgeType="success"
                    color="purple"
                    :icon="SparklesIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search pools by name, PHP version, socket, or user..."
                @search="() => {}"
                @filter="() => {}"
                @reset="resetFilters"
            >
                <FilterSelect
                    v-model="selectedVersionFilter"
                    label="PHP Runtime"
                    :options="[
                        { label: 'All PHP Versions', value: 'all' },
                        ...installedVersions.map(v => ({ label: `PHP ${v}`, value: v }))
                    ]"
                    placeholder="All PHP Runtimes"
                />

                <FilterSelect
                    v-model="selectedPmFilter"
                    label="PM Strategy"
                    :options="[
                        { label: 'All PM Strategies', value: 'all' },
                        { label: 'Dynamic (Adaptive)', value: 'dynamic' },
                        { label: 'On-Demand (Sleep Mode)', value: 'ondemand' },
                        { label: 'Static (Fixed Worker)', value: 'static' }
                    ]"
                    placeholder="All Strategies"
                />
            </DataTableFilter>

            <!-- 4. Standard Data Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Pool Name & Runtime</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Process Manager</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Max Children</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Start / Min / Max</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Listen Socket</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Execution User</th>
                                <th class="py-2.5 px-3 w-28">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(pool, index) in filteredPools" :key="`${pool.version}-${pool.name}`" class="hover:bg-blue-50/30 transition">
                                <!-- # -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>

                                <!-- Pool Name & Runtime -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs border border-blue-100 shrink-0">
                                            {{ pool.version }}
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-1.5">
                                                <span class="font-bold text-slate-900 leading-tight">[{{ pool.name }}]</span>
                                                <span class="px-1.5 py-0.2 rounded-[2px] text-[9px] font-bold uppercase bg-purple-50 text-purple-700 border border-purple-200">
                                                    PHP {{ pool.version }}
                                                </span>
                                            </div>
                                            <span class="text-[10px] font-mono text-slate-400 block">{{ pool.config_file }}</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- PM Strategy -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="[
                                            pool.pm === 'dynamic' ? 'bg-blue-50 text-blue-700 border-blue-200' :
                                            pool.pm === 'ondemand' ? 'bg-amber-50 text-amber-700 border-amber-200' :
                                            'bg-purple-50 text-purple-700 border-purple-200'
                                        ]"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ pool.pm }}
                                    </span>
                                </td>

                                <!-- Max Children -->
                                <td class="py-2.5 px-3 border-r border-slate-100 font-mono font-bold text-slate-900 whitespace-nowrap text-center">
                                    {{ pool.pm_max_children }} workers
                                </td>

                                <!-- Start / Min / Max -->
                                <td class="py-2.5 px-3 border-r border-slate-100 font-mono text-[10.5px] text-slate-600 whitespace-nowrap text-center">
                                    {{ pool.pm_start_servers || '-' }} / {{ pool.pm_min_spare_servers || '-' }} / {{ pool.pm_max_spare_servers || '-' }}
                                </td>

                                <!-- Socket -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left font-mono text-[10.5px] text-slate-600 max-w-xs truncate" :title="pool.listen">
                                    {{ pool.listen }}
                                </td>

                                <!-- User -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap font-mono text-[10.5px] text-slate-600 text-center">
                                    {{ pool.user }}:{{ pool.group }}
                                </td>

                                <!-- Actions -->
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1">
                                        <button 
                                            type="button" 
                                            @click="openTuneModal(pool)"
                                            class="px-2 py-1 bg-white hover:bg-slate-50 text-blue-700 font-bold rounded-[3px] text-xs border border-slate-200 shadow-2xs transition cursor-pointer"
                                            title="Tune FPM Pool Parameters"
                                        >
                                            Tune ⚙
                                        </button>

                                        <RowActionDropdown>
                                            <button
                                                type="button"
                                                @click="reloadFpmService(pool.version)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <ArrowPathIcon class="w-3.5 h-3.5 text-slate-500" />
                                                <span>Reload PHP {{ pool.version }} FPM</span>
                                            </button>

                                            <button
                                                v-if="pool.name !== 'www'"
                                                type="button"
                                                @click="openDeleteModal(pool)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-rose-600 hover:bg-rose-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <TrashIcon class="w-3.5 h-3.5" />
                                                <span>Delete Custom Pool</span>
                                            </button>
                                        </RowActionDropdown>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!filteredPools || filteredPools.length === 0">
                                <td colspan="8" class="py-12 text-center text-slate-400">
                                    No FPM worker pools found matching criteria.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 1. TUNE POOL MODAL -->
        <div v-if="showTuneModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-lg overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <WrenchScrewdriverIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Tune Pool: [{{ tuneForm.pool_name }}] (PHP {{ tuneForm.version }})
                        </h3>
                    </div>
                    <button @click="showTuneModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitTunePool" class="p-4 space-y-3.5 text-xs">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Process Manager Strategy</label>
                            <select v-model="tuneForm.pm" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500">
                                <option value="dynamic">dynamic (Adaptive Concurrency)</option>
                                <option value="static">static (Fixed Pre-forked)</option>
                                <option value="ondemand">ondemand (Fork on Request)</option>
                            </select>
                        </div>

                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Max Children (pm.max_children)</label>
                            <input v-model="tuneForm.pm_max_children" type="number" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 font-mono" />
                        </div>

                        <div v-if="tuneForm.pm === 'dynamic'" class="space-y-1">
                            <label class="block font-bold text-slate-700">Start Servers</label>
                            <input v-model="tuneForm.pm_start_servers" type="number" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 font-mono" />
                        </div>

                        <div v-if="tuneForm.pm === 'dynamic'" class="space-y-1">
                            <label class="block font-bold text-slate-700">Min / Max Spare Servers</label>
                            <div class="grid grid-cols-2 gap-1.5">
                                <input v-model="tuneForm.pm_min_spare_servers" type="number" placeholder="Min" class="w-full px-2 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono" />
                                <input v-model="tuneForm.pm_max_spare_servers" type="number" placeholder="Max" class="w-full px-2 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono" />
                            </div>
                        </div>

                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Max Requests (Recycle)</label>
                            <input v-model="tuneForm.pm_max_requests" type="number" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 font-mono" />
                        </div>

                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Memory Limit Override</label>
                            <input v-model="tuneForm.memory_limit" type="text" placeholder="512M" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 font-mono" />
                        </div>
                    </div>

                    <div class="pt-2 flex items-center justify-end gap-2 border-t border-slate-100">
                        <button
                            type="button"
                            @click="showTuneModal = false"
                            class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="tuneForm.processing"
                            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ tuneForm.processing ? 'Saving...' : 'Save & Reload FPM' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 2. CREATE CUSTOM POOL MODAL -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-lg overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <PlusIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Create Custom PHP-FPM Pool
                        </h3>
                    </div>
                    <button @click="showCreateModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitCreatePool" class="p-4 space-y-3.5 text-xs">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Target PHP Runtime <span class="text-rose-500">*</span></label>
                            <select v-model="createForm.version" required class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                                <option v-for="v in installedVersions" :key="v" :value="v">PHP {{ v }}</option>
                            </select>
                        </div>

                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Pool Name <span class="text-rose-500">*</span></label>
                            <input v-model="createForm.pool_name" type="text" required placeholder="e.g. client_alpha" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 font-mono" />
                        </div>

                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Linux User</label>
                            <input v-model="createForm.user" type="text" placeholder="www-data" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono" />
                        </div>

                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Process Manager</label>
                            <select v-model="createForm.pm" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900">
                                <option value="dynamic">dynamic</option>
                                <option value="ondemand">ondemand</option>
                                <option value="static">static</option>
                            </select>
                        </div>
                    </div>

                    <div class="pt-2 flex items-center justify-end gap-2 border-t border-slate-100">
                        <button
                            type="button"
                            @click="showCreateModal = false"
                            class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="createForm.processing"
                            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ createForm.processing ? 'Deploying...' : 'Deploy Pool' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 3. DELETE MODAL -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-rose-50 text-rose-600 flex items-center justify-center font-bold border border-rose-100">
                            <TrashIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Delete Custom Pool
                        </h3>
                    </div>
                    <button @click="showDeleteModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-2.5 text-xs">
                    <p class="text-slate-600">
                        Delete custom FPM pool <strong class="text-slate-900">[{{ poolToDelete?.name }}]</strong> for PHP {{ poolToDelete?.version }}?
                    </p>
                </div>

                <div class="px-4 py-2.5 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                    <button
                        type="button"
                        @click="showDeleteModal = false"
                        class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="submitDeletePool"
                        :disabled="deleteForm.processing"
                        class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                    >
                        {{ deleteForm.processing ? 'Deleting...' : 'Delete Pool' }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
