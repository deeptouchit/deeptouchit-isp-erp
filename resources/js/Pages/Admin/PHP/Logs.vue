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

import {
    CpuChipIcon,
    ExclamationTriangleIcon,
    ArrowLeftIcon,
    XMarkIcon,
    DocumentTextIcon,
    ClockIcon,
    CodeBracketIcon,
    CheckIcon,
    TrashIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    fpmLogs: {
        type: Object,
        default: () => ({
            file: '',
            size: '0 KB',
            entries: [],
            raw: '',
        }),
    },
    auditLogs: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            total_entries: 0,
            error_count: 0,
            warning_count: 0,
            notice_count: 0,
            audit_count: 0,
            log_size: '0 KB',
            log_file: '',
        }),
    },
    selectedVersion: {
        type: String,
        default: '8.3',
    },
    installedVersions: {
        type: Array,
        default: () => [],
    },
    defaultVersion: {
        type: String,
        default: '8.3',
    },
    currentLines: {
        type: Number,
        default: 100,
    },
    currentLevel: {
        type: String,
        default: 'all',
    },
})

// Toast Feedback
const feedbackMsg = ref('')

// Active Tab Mode
const activeTab = ref('fpm')

// Filters
const search = ref('')
const selectedLevel = ref(props.currentLevel || 'all')
const selectedLines = ref(props.currentLines || 100)

// Audit Log Diff Modal
const showDiffModal = ref(false)
const selectedAuditItem = ref(null)

const openDiffModal = (item) => {
    selectedAuditItem.value = item
    showDiffModal.value = true
}

const switchVersion = (version) => {
    router.get(route('admin.php.logs'), {
        version,
        lines: selectedLines.value,
        level: selectedLevel.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const applyFilters = () => {
    router.get(route('admin.php.logs'), {
        version: props.selectedVersion,
        lines: selectedLines.value,
        level: selectedLevel.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const resetFilters = () => {
    search.value = ''
    selectedLevel.value = 'all'
    selectedLines.value = 100
    applyFilters()
}

// Filtered FPM Log entries
const filteredFpmEntries = computed(() => {
    let list = props.fpmLogs?.entries || []

    if (search.value.trim()) {
        const q = search.value.toLowerCase()
        list = list.filter(e => 
            e.message.toLowerCase().includes(q) ||
            e.timestamp.toLowerCase().includes(q) ||
            (e.level || '').toLowerCase().includes(q)
        )
    }

    if (selectedLevel.value !== 'all') {
        list = list.filter(e => e.level === selectedLevel.value)
    }

    return list
})

// Clear Logs
const clearLogFile = () => {
    if (confirm(`Truncate and clear log file for PHP ${props.selectedVersion}?`)) {
        useForm({ version: props.selectedVersion }).post(route('admin.php.clear-logs'), {
            preserveScroll: true,
            onSuccess: () => {
                feedbackMsg.value = `Log file for PHP ${props.selectedVersion} truncated.`
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    }
}
</script>

<template>
    <Head :title="`PHP ${selectedVersion} Logs & Audits - DeepTouchHost`" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'PHP Manager', href: route('admin.php.index') },
                    { label: 'Logs' }
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

                    <!-- Version Switcher -->
                    <div class="flex items-center gap-1.5 bg-white px-2.5 py-1.5 rounded-[3px] border border-slate-200 shadow-2xs text-xs">
                        <span class="font-bold text-slate-500">PHP Version:</span>
                        <select 
                            :value="selectedVersion"
                            @change="switchVersion($event.target.value)"
                            class="bg-blue-50 border border-blue-200 text-xs font-bold text-blue-700 rounded-[2px] py-0.5 px-2 focus:ring-1 focus:ring-blue-500 cursor-pointer outline-none"
                        >
                            <option v-for="ver in installedVersions" :key="ver" :value="ver">
                                PHP {{ ver }}
                            </option>
                        </select>
                    </div>

                    <button
                        type="button"
                        @click="clearLogFile"
                        class="px-2.5 py-1.5 bg-white hover:bg-rose-50 text-rose-600 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-rose-200 shadow-2xs transition cursor-pointer"
                    >
                        <TrashIcon class="w-3.5 h-3.5" />
                        <span>Clear Logs</span>
                    </button>

                    <RefreshButton />
                </template>
            </PageHeader>

            <!-- Feedback Banner -->
            <div v-if="feedbackMsg" class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-3 py-2 rounded-[3px] text-xs font-bold flex items-center justify-between shadow-2xs">
                <div class="flex items-center gap-2">
                    <CheckIcon class="w-4 h-4 text-emerald-600" />
                    <span>{{ feedbackMsg }}</span>
                </div>
                <button @click="feedbackMsg = ''" class="text-emerald-600 hover:text-emerald-800 text-xs cursor-pointer">✕</button>
            </div>

            <!-- 2. Ultra-Compact KPI Info Cards (56px) -->
            <InfoCardsGrid :cols="4">
                <InfoCard
                    title="Total Log Entries"
                    :value="String(stats.total_entries || 0)"
                    badge="Lines"
                    badgeType="info"
                    color="blue"
                    :icon="DocumentTextIcon"
                />

                <InfoCard
                    title="Critical Errors"
                    :value="String(stats.error_count || 0)"
                    :badge="stats.error_count > 0 ? 'Alert' : 'Zero'"
                    :badgeType="stats.error_count > 0 ? 'danger' : 'success'"
                    color="rose"
                    :icon="ExclamationTriangleIcon"
                />

                <InfoCard
                    title="Warnings & Slow Traces"
                    :value="String(stats.warning_count || 0)"
                    badge="Notice"
                    badgeType="warning"
                    color="amber"
                    :icon="ClockIcon"
                />

                <InfoCard
                    title="Log Storage Size"
                    :value="stats.log_size || '0 KB'"
                    badge="Disk"
                    badgeType="info"
                    color="purple"
                    :icon="CpuChipIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search log messages, timestamps, process IDs..."
                @search="() => {}"
                @filter="applyFilters"
                @reset="resetFilters"
            >
                <FilterSelect
                    v-model="selectedLevel"
                    label="Log Severity"
                    :options="[
                        { label: 'All Log Severities', value: 'all' },
                        { label: 'Errors & Alerts Only', value: 'error' },
                        { label: 'Warnings Only', value: 'warning' },
                        { label: 'Notices Only', value: 'notice' }
                    ]"
                    placeholder="All Severities"
                    @change="applyFilters"
                />

                <FilterSelect
                    v-model="selectedLines"
                    label="Lines Buffer"
                    :options="[
                        { label: 'Last 50 Lines', value: 50 },
                        { label: 'Last 100 Lines', value: 100 },
                        { label: 'Last 250 Lines', value: 250 },
                        { label: 'Last 500 Lines', value: 500 }
                    ]"
                    placeholder="Line Limit"
                    @change="applyFilters"
                />
            </DataTableFilter>

            <!-- 4. Tabbed Table Container -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <!-- Tab Switchers -->
                <div class="flex items-center border-b border-slate-200 bg-slate-50/70 px-3">
                    <button
                        type="button"
                        @click="activeTab = 'fpm'"
                        :class="activeTab === 'fpm' ? 'border-blue-600 text-blue-600 font-bold bg-white' : 'border-transparent text-slate-600 hover:text-slate-900'"
                        class="py-2.5 px-3 text-xs border-b-2 transition cursor-pointer flex items-center gap-1.5"
                    >
                        <DocumentTextIcon class="w-3.5 h-3.5" />
                        <span>PHP-FPM System & Slow Logs ({{ filteredFpmEntries.length }})</span>
                    </button>

                    <button
                        type="button"
                        @click="activeTab = 'audit'"
                        :class="activeTab === 'audit' ? 'border-blue-600 text-blue-600 font-bold bg-white' : 'border-transparent text-slate-600 hover:text-slate-900'"
                        class="py-2.5 px-3 text-xs border-b-2 transition cursor-pointer flex items-center gap-1.5"
                    >
                        <ClockIcon class="w-3.5 h-3.5" />
                        <span>Configuration Mutation Audits ({{ auditLogs?.length || 0 }})</span>
                    </button>
                </div>

                <!-- Tab 1: FPM Logs Table -->
                <div v-if="activeTab === 'fpm'" class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Timestamp</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Severity</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Process ID</th>
                                <th class="py-2.5 px-3 text-left">Message & Trace Content</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(entry, index) in filteredFpmEntries" :key="index" class="hover:bg-blue-50/30 transition">
                                <!-- # -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>

                                <!-- Timestamp -->
                                <td class="py-2.5 px-3 border-r border-slate-100 font-mono text-slate-500 text-[10.5px] whitespace-nowrap">
                                    {{ entry.timestamp }}
                                </td>

                                <!-- Severity -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="[
                                            entry.level === 'error' ? 'bg-rose-50 text-rose-700 border-rose-200' :
                                            entry.level === 'warning' ? 'bg-amber-50 text-amber-700 border-amber-200' :
                                            'bg-slate-100 text-slate-600 border-slate-200'
                                        ]"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ entry.level }}
                                    </span>
                                </td>

                                <!-- PID -->
                                <td class="py-2.5 px-3 border-r border-slate-100 font-mono text-slate-500 text-[10.5px] whitespace-nowrap text-center">
                                    {{ entry.pid || 'FPM-DAEMON' }}
                                </td>

                                <!-- Message -->
                                <td class="py-2.5 px-3 text-left font-mono text-[11px] text-slate-800 whitespace-normal">
                                    {{ entry.message }}
                                </td>
                            </tr>

                            <tr v-if="!filteredFpmEntries || filteredFpmEntries.length === 0">
                                <td colspan="5" class="py-12 text-center text-slate-400">
                                    No log entries found matching criteria.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Tab 2: Audit Logs Table -->
                <div v-if="activeTab === 'audit'" class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Timestamp</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Administrator</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Action Code</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Mutation Summary</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 font-mono">IP Address</th>
                                <th class="py-2.5 px-3 w-16">Diff</th>
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
                                <td class="py-2.5 px-3 border-r border-slate-100 font-mono text-[10.5px] text-slate-500 whitespace-nowrap">
                                    {{ log.ip_address }}
                                </td>
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <button
                                        type="button"
                                        @click="openDiffModal(log)"
                                        class="px-2 py-1 bg-white hover:bg-slate-100 text-slate-700 rounded-[3px] border border-slate-200 text-xs font-bold transition flex items-center gap-1 mx-auto shadow-2xs cursor-pointer"
                                    >
                                        <CodeBracketIcon class="w-3.5 h-3.5 text-slate-500" />
                                        <span>Diff</span>
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="!auditLogs || auditLogs.length === 0">
                                <td colspan="7" class="py-12 text-center text-slate-400">
                                    No configuration mutation audits recorded.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- DIFF INSPECTOR MODAL -->
        <div v-if="showDiffModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-lg overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <CodeBracketIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Mutation Diff: {{ selectedAuditItem?.action }}
                        </h3>
                    </div>
                    <button @click="showDiffModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-3 text-xs">
                    <div class="bg-slate-900 text-emerald-400 p-3.5 rounded-[3px] font-mono text-[11px] max-h-72 overflow-y-auto border border-slate-800">
                        <pre>{{ JSON.stringify(selectedAuditItem?.new_values || {}, null, 2) }}</pre>
                    </div>
                </div>

                <div class="px-4 py-2.5 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end text-xs">
                    <button
                        type="button"
                        @click="showDiffModal = false"
                        class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
