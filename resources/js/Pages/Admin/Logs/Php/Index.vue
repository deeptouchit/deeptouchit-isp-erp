<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import RefreshButton from '@/Components/UI/RefreshButton.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'
import DataTableFilter from '@/Components/UI/DataTableFilter.vue'
import FilterSelect from '@/Components/UI/FilterSelect.vue'

import {
    CpuChipIcon,
    ServerIcon,
    ExclamationTriangleIcon,
    CheckIcon,
    ArrowDownTrayIcon,
    DocumentDuplicateIcon,
    ShieldCheckIcon,
    ArrowPathIcon,
    DocumentTextIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    logs: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            total_lines: 0,
            error_count: 0,
            warn_count: 0,
            active_version: 'PHP 8.2',
            fpm_status: { service: 'php8.2-fpm', status: 'active', is_running: true, socket: '/run/php/php8.2-fpm.sock' },
            available_versions: ['8.4', '8.3', '8.2', '8.1', '7.4'],
        }),
    },
    filters: {
        type: Object,
        default: () => ({ version: '8.2', lines: 100, search: '', severity: 'all' }),
    },
})

// Flash Feedback Toast
const feedbackMsg = ref('')

// Filter State
const search = ref(props.filters?.search || '')
const currentVersion = ref(props.filters?.version || '8.2')
const currentLines = ref(props.filters?.lines || 100)
const currentSeverity = ref(props.filters?.severity || 'all')

const applyFilters = () => {
    router.get(route('admin.logs.php'), {
        version: currentVersion.value,
        lines: currentLines.value,
        search: search.value || undefined,
        severity: currentSeverity.value !== 'all' ? currentSeverity.value : undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const selectVersion = (ver) => {
    currentVersion.value = ver
    applyFilters()
}

const resetFilters = () => {
    search.value = ''
    currentSeverity.value = 'all'
    currentLines.value = 100
    applyFilters()
}

const downloadLog = () => {
    window.location.href = route('admin.logs.php.download', { version: currentVersion.value })
}

const restartFpm = () => {
    if (confirm(`Restart PHP ${currentVersion.value}-FPM FastCGI daemon service?`)) {
        router.post(route('admin.logs.php.restart'), { version: currentVersion.value }, {
            preserveScroll: true,
            onSuccess: () => {
                feedbackMsg.value = `PHP ${currentVersion.value}-FPM daemon restarted successfully.`
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    }
}

const copyLine = (log) => {
    navigator.clipboard.writeText(log.raw || log.message)
    feedbackMsg.value = `Log line copied to clipboard.`
    setTimeout(() => { feedbackMsg.value = '' }, 2500)
}
</script>

<template>
    <Head title="PHP System Logs - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'System Logs', href: '#' },
                    { label: 'Unified Server Logs', href: route('admin.logs.system') },
                    { label: 'Multi-PHP & FastCGI Engine Logs' }
                ]"
            >
                <template #actions>
                    <button
                        type="button"
                        @click="restartFpm"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowPathIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Restart PHP-FPM</span>
                    </button>

                    <button
                        type="button"
                        @click="downloadLog"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowDownTrayIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Download Log</span>
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
                    title="Buffer Lines Read"
                    :value="String(logs.length || stats.total_lines || 0)"
                    :badge="`PHP ${currentVersion}`"
                    badgeType="info"
                    color="blue"
                    :icon="CpuChipIcon"
                />

                <InfoCard
                    title="Fatal Error Exceptions"
                    :value="String(stats.error_count || 0)"
                    :badge="stats.error_count > 0 ? 'Exceptions' : 'Clean'"
                    :badgeType="stats.error_count > 0 ? 'danger' : 'success'"
                    color="rose"
                    :icon="ExclamationTriangleIcon"
                />

                <InfoCard
                    title="Warnings & Deprecations"
                    :value="String(stats.warn_count || 0)"
                    badge="Notices"
                    badgeType="warning"
                    color="purple"
                    :icon="ShieldCheckIcon"
                />

                <InfoCard
                    title="FastCGI Daemon Status"
                    value="php-fpm.sock Online"
                    badge="Active"
                    badgeType="success"
                    color="sky"
                    :icon="ServerIcon"
                />
            </InfoCardsGrid>

            <!-- 3. PHP Version Selection Tabs -->
            <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        v-for="ver in (stats.available_versions || ['8.4', '8.3', '8.2', '8.1', '7.4'])"
                        :key="ver"
                        type="button"
                        @click="selectVersion(ver)"
                        :class="currentVersion === ver ? 'bg-blue-600 text-white' : 'bg-white text-slate-700 hover:bg-slate-50 border border-slate-200'"
                        class="px-3 py-1.5 rounded-[3px] font-bold text-xs uppercase font-mono transition cursor-pointer shadow-2xs"
                    >
                        PHP {{ ver }}
                    </button>
                </div>

                <div class="flex items-center gap-2 text-xs">
                    <span class="text-slate-400">Tail Lines:</span>
                    <select v-model.number="currentLines" @change="applyFilters" class="px-2 py-1 rounded-[3px] border border-slate-200 bg-white font-mono text-xs cursor-pointer">
                        <option :value="50">50 Lines</option>
                        <option :value="100">100 Lines</option>
                        <option :value="250">250 Lines</option>
                        <option :value="500">500 Lines</option>
                    </select>
                </div>
            </div>

            <!-- 4. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search PHP exception, worker PID, or error payload..."
                @search="applyFilters"
                @filter="applyFilters"
                @reset="resetFilters"
            >
                <FilterSelect
                    v-model="currentSeverity"
                    label="Severity Filter"
                    :options="[
                        { label: 'All Severities', value: 'all' },
                        { label: 'Errors Only', value: 'error' },
                        { label: 'Warnings & Notices', value: 'warning' }
                    ]"
                    placeholder="All Severities"
                />
            </DataTableFilter>

            <!-- 5. Log Stream Data Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 w-36">Timestamp</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 w-24">Severity</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">PHP Log Message Payload</th>
                                <th class="py-2.5 px-3 w-16">Copy</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap font-mono text-[11px]">
                            <tr v-for="(log, index) in logs" :key="log.id || index" class="hover:bg-slate-50/80 transition">
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 whitespace-nowrap font-sans">
                                    {{ index + 1 }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center text-slate-500 text-[10.5px]">
                                    {{ log.timestamp || 'Live' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-sans">
                                    <span 
                                        :class="[
                                            log.severity === 'error' ? 'bg-rose-50 text-rose-700 border-rose-200' :
                                            log.severity === 'warning' ? 'bg-amber-50 text-amber-700 border-amber-200' :
                                            'bg-blue-50 text-blue-700 border-blue-200'
                                        ]"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ log.severity || 'info' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap text-slate-800 max-w-xl truncate" :title="log.raw || log.message">
                                    {{ log.message || log.raw }}
                                </td>
                                <td class="py-2.5 px-3 text-center whitespace-nowrap font-sans">
                                    <button 
                                        type="button" 
                                        @click="copyLine(log)"
                                        class="p-1 hover:bg-slate-100 text-slate-500 hover:text-slate-900 rounded-[2px] cursor-pointer"
                                        title="Copy Line"
                                    >
                                        <DocumentDuplicateIcon class="w-3.5 h-3.5" />
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="!logs || logs.length === 0">
                                <td colspan="5" class="py-12 text-center text-slate-400 font-sans">
                                    No log entries found for PHP {{ currentVersion }}.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
