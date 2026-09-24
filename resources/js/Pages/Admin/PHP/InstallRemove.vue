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
    ShieldCheckIcon,
    SparklesIcon,
    PlusIcon,
    TrashIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    ArrowLeftIcon,
    XMarkIcon,
    CommandLineIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    catalog: {
        type: Object,
        default: () => ({}),
    },
    stats: {
        type: Object,
        default: () => ({
            installed_count: 0,
            available_count: 0,
            supported_count: 0,
            default_version: '8.3',
        }),
    },
})

// Flash Feedback
const feedbackMsg = ref('')

// Filter State
const search = ref('')
const filterType = ref('all')

const filteredCatalog = computed(() => {
    let list = Object.values(props.catalog)

    if (search.value.trim()) {
        const q = search.value.toLowerCase()
        list = list.filter(item => 
            item.name.toLowerCase().includes(q) || 
            item.version.includes(q) ||
            (item.description || '').toLowerCase().includes(q) ||
            item.status.toLowerCase().includes(q)
        )
    }

    if (filterType.value === 'installed') {
        list = list.filter(item => item.is_installed)
    } else if (filterType.value === 'available') {
        list = list.filter(item => !item.is_installed)
    } else if (filterType.value === 'active') {
        list = list.filter(item => item.status_type === 'latest' || item.status_type === 'active')
    }

    return list
})

const resetFilters = () => {
    search.value = ''
    filterType.value = 'all'
}

// 1. INSTALL ENGINE MODAL
const showInstallModal = ref(false)
const versionToInstall = ref(null)
const installForm = useForm({ version: '' })

const openInstallModal = (item) => {
    versionToInstall.value = item
    installForm.version = item.version
    showInstallModal.value = true
}

const submitInstallVersion = () => {
    installForm.post(route('admin.php.install-version'), {
        preserveScroll: true,
        onSuccess: () => {
            showInstallModal.value = false
            feedbackMsg.value = `PHP ${versionToInstall.value?.version} and standard hosting bundle installed successfully.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 2. UNINSTALL ENGINE MODAL
const showUninstallModal = ref(false)
const versionToUninstall = ref(null)
const uninstallForm = useForm({ version: '' })

const openUninstallModal = (item) => {
    versionToUninstall.value = item
    uninstallForm.version = item.version
    showUninstallModal.value = true
}

const submitUninstallVersion = () => {
    uninstallForm.post(route('admin.php.uninstall-version'), {
        preserveScroll: true,
        onSuccess: () => {
            showUninstallModal.value = false
            feedbackMsg.value = `PHP ${versionToUninstall.value?.version} removed cleanly.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 3. SET AS DEFAULT
const setAsDefault = (item) => {
    if (confirm(`Set PHP ${item.version} as the platform global default engine?`)) {
        router.post(route('admin.php.set-default'), { version: item.version }, {
            preserveScroll: true,
            onSuccess: () => {
                feedbackMsg.value = `PHP ${item.version} set as default runtime.`
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    }
}
</script>

<template>
    <Head title="Install & Remove PHP Versions - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'PHP Manager', href: route('admin.php.index') },
                    { label: 'Install & Remove' }
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
                    title="Installed Runtimes"
                    :value="String(stats.installed_count || 0)"
                    badge="Active"
                    badgeType="success"
                    color="emerald"
                    :icon="CpuChipIcon"
                />

                <InfoCard
                    title="Available to Deploy"
                    :value="String(stats.available_count || 0)"
                    badge="Repository"
                    badgeType="info"
                    color="blue"
                    :icon="PlusIcon"
                />

                <InfoCard
                    title="Default System Engine"
                    :value="`PHP ${stats.default_version || '8.3'}`"
                    badge="Default"
                    badgeType="info"
                    color="purple"
                    :icon="SparklesIcon"
                />

                <InfoCard
                    title="Supported Versions"
                    :value="String(stats.supported_count || 0)"
                    badge="Multi-FPM"
                    badgeType="success"
                    color="sky"
                    :icon="ServerIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search PHP versions, support tier, or release state..."
                @search="() => {}"
                @filter="() => {}"
                @reset="resetFilters"
            >
                <FilterSelect
                    v-model="filterType"
                    label="Installation Status"
                    :options="[
                        { label: 'All Catalog Versions', value: 'all' },
                        { label: 'Installed Only', value: 'installed' },
                        { label: 'Available to Install', value: 'available' },
                        { label: 'Active Support Tier', value: 'active' }
                    ]"
                    placeholder="Filter by Status"
                />
            </DataTableFilter>

            <!-- 4. Standard Data Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">PHP Version & Release</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Support Lifecycle</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Installed State</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">FPM Service / Package Name</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Default Runtime</th>
                                <th class="py-2.5 px-3 w-28">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(item, index) in filteredCatalog" :key="item.version" class="hover:bg-blue-50/30 transition">
                                <!-- # -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>

                                <!-- Version Name -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div 
                                            :class="item.is_installed ? 'bg-blue-50 text-blue-600 border-blue-100' : 'bg-slate-100 text-slate-400 border-slate-200'"
                                            class="w-7 h-7 rounded-[3px] flex items-center justify-center font-black text-xs border shrink-0"
                                        >
                                            {{ item.version }}
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-1.5">
                                                <span class="font-bold text-slate-900 leading-tight">PHP {{ item.version }}</span>
                                                <span v-if="item.is_default" class="px-1.5 py-0.2 rounded-[2px] text-[9px] font-bold uppercase bg-blue-50 text-blue-700 border border-blue-200">
                                                    Default
                                                </span>
                                            </div>
                                            <span class="text-[10px] text-slate-400 block">{{ item.description }}</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Lifecycle -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="[
                                            item.status_type === 'latest' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                                            item.status_type === 'active' ? 'bg-blue-50 text-blue-700 border-blue-200' :
                                            item.status_type === 'security' ? 'bg-amber-50 text-amber-700 border-amber-200' :
                                            'bg-rose-50 text-rose-700 border-rose-200'
                                        ]"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold border font-mono"
                                    >
                                        {{ item.status }}
                                    </span>
                                </td>

                                <!-- Installed State -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        v-if="item.is_installed"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-[3px] text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200"
                                    >
                                        <CheckCircleIcon class="w-3.5 h-3.5 text-emerald-600" />
                                        <span>Installed</span>
                                    </span>
                                    <span 
                                        v-else
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-[3px] text-[10px] font-bold bg-slate-100 text-slate-500 border border-slate-200"
                                    >
                                        <span>Not Installed</span>
                                    </span>
                                </td>

                                <!-- Package Name -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left font-mono text-[10.5px] text-slate-600 whitespace-nowrap">
                                    {{ item.package_name || `php${item.version}-fpm` }}
                                </td>

                                <!-- Default Radio -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <button
                                        v-if="item.is_installed && !item.is_default"
                                        type="button"
                                        @click="setAsDefault(item)"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold text-slate-600 hover:text-blue-600 hover:bg-slate-100 border border-slate-200 transition cursor-pointer"
                                    >
                                        Set Default
                                    </button>
                                    <span v-else-if="item.is_default" class="text-blue-700 font-bold text-[10.5px]">✓ Active</span>
                                    <span v-else class="text-slate-300 text-xs font-mono">-</span>
                                </td>

                                <!-- Action -->
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <button
                                        v-if="!item.is_installed"
                                        type="button"
                                        @click="openInstallModal(item)"
                                        class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] text-xs transition shadow-2xs cursor-pointer flex items-center gap-1 mx-auto"
                                    >
                                        <PlusIcon class="w-3.5 h-3.5" />
                                        <span>Install</span>
                                    </button>

                                    <button
                                        v-else-if="!item.is_default"
                                        type="button"
                                        @click="openUninstallModal(item)"
                                        class="px-2.5 py-1 bg-white hover:bg-rose-50 text-rose-600 font-bold rounded-[3px] text-xs border border-rose-200 transition shadow-2xs cursor-pointer flex items-center gap-1 mx-auto"
                                    >
                                        <TrashIcon class="w-3.5 h-3.5" />
                                        <span>Uninstall</span>
                                    </button>

                                    <span v-else class="text-slate-400 text-[10.5px] italic">Protected Default</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 1. INSTALL MODAL -->
        <div v-if="showInstallModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <PlusIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Deploy PHP {{ versionToInstall?.version }} Runtime
                        </h3>
                    </div>
                    <button @click="showInstallModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-2.5 text-xs">
                    <p class="text-slate-600">
                        Deploy and activate <strong class="text-slate-900">PHP {{ versionToInstall?.version }}</strong> along with the standard hosting bundle (FPM, CLI, cURL, MySQL, GD, XML, Zip, OPcache)?
                    </p>
                    <div class="p-2.5 bg-blue-50 border border-blue-200 text-blue-800 rounded-[3px]">
                        <p class="font-bold text-[11px]">📦 Automated Bundle Deployment</p>
                        <p class="text-[10.5px] mt-0.5">The system will install packages, configure FPM pools, and register socket listeners automatically.</p>
                    </div>
                </div>

                <div class="px-4 py-2.5 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                    <button
                        type="button"
                        @click="showInstallModal = false"
                        class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="submitInstallVersion"
                        :disabled="installForm.processing"
                        class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                    >
                        {{ installForm.processing ? 'Deploying...' : `Install PHP ${versionToInstall?.version}` }}
                    </button>
                </div>
            </div>
        </div>

        <!-- 2. UNINSTALL MODAL -->
        <div v-if="showUninstallModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-rose-50 text-rose-600 flex items-center justify-center font-bold border border-rose-100">
                            <TrashIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Uninstall PHP {{ versionToUninstall?.version }}
                        </h3>
                    </div>
                    <button @click="showUninstallModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-2.5 text-xs">
                    <p class="text-slate-600">
                        Are you sure you want to remove <strong class="text-slate-900">PHP {{ versionToUninstall?.version }}</strong>?
                    </p>
                    <div class="p-2.5 bg-rose-50 border border-rose-200 text-rose-800 rounded-[3px]">
                        <p class="font-bold text-[11px]">⚠️ Runtime Removal</p>
                        <p class="text-[10.5px] mt-0.5">Any sites using this version should be switched to another version first to avoid 502 Bad Gateway errors.</p>
                    </div>
                </div>

                <div class="px-4 py-2.5 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                    <button
                        type="button"
                        @click="showUninstallModal = false"
                        class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="submitUninstallVersion"
                        :disabled="uninstallForm.processing"
                        class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                    >
                        {{ uninstallForm.processing ? 'Removing...' : `Uninstall PHP ${versionToUninstall?.version}` }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
