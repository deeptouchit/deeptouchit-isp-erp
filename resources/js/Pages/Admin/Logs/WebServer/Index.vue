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
    GlobeAltIcon,
    ServerIcon,
    ExclamationTriangleIcon,
    CheckIcon,
    ArrowDownTrayIcon,
    DocumentDuplicateIcon,
    ShieldExclamationIcon,
    ArrowsRightLeftIcon,
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
            total_requests: 0,
            success_rate: 100,
            client_errors: 0,
            server_errors: 0,
            active_source: 'nginx_access',
            is_error_log: false,
        }),
    },
    filters: {
        type: Object,
        default: () => ({ source: 'nginx_access', lines: 100, search: '', status_code: 'all', method: 'all' }),
    },
})

// Flash Feedback Toast
const feedbackMsg = ref('')

// Filter State
const search = ref(props.filters?.search || '')
const currentSource = ref(props.filters?.source || 'nginx_access')
const currentLines = ref(props.filters?.lines || 100)
const currentStatus = ref(props.filters?.status_code || 'all')
const currentMethod = ref(props.filters?.method || 'all')

const applyFilters = () => {
    router.get(route('admin.logs.web-server'), {
        source: currentSource.value,
        lines: currentLines.value,
        search: search.value || undefined,
        status_code: currentStatus.value !== 'all' ? currentStatus.value : undefined,
        method: currentMethod.value !== 'all' ? currentMethod.value : undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const selectSource = (src) => {
    currentSource.value = src
    applyFilters()
}

const resetFilters = () => {
    search.value = ''
    currentStatus.value = 'all'
    currentMethod.value = 'all'
    currentLines.value = 100
    applyFilters()
}

const downloadLog = () => {
    window.location.href = route('admin.logs.web-server.download', { source: currentSource.value })
}

const copyLine = (log) => {
    navigator.clipboard.writeText(log.raw || log.message)
    feedbackMsg.value = `Log line copied to clipboard.`
    setTimeout(() => { feedbackMsg.value = '' }, 2500)
}
</script>

<template>
    <Head title="Web Server Logs - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'System Logs', href: '#' },
                    { label: 'Unified Server Logs', href: route('admin.logs.system') },
                    { label: 'Web Server Traffic & Error Logs' }
                ]"
            >
                <template #actions>
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
                    title="Total HTTP Requests"
                    :value="String(logs.length || stats.total_requests || 0)"
                    :badge="stats.active_source || currentSource"
                    badgeType="info"
                    color="blue"
                    :icon="GlobeAltIcon"
                />

                <InfoCard
                    title="HTTP Success Rate"
                    :value="`${stats.success_rate || 100}%`"
                    badge="2xx / 3xx"
                    badgeType="success"
                    color="emerald"
                    :icon="CheckIcon"
                />

                <InfoCard
                    title="Client Errors"
                    :value="String(stats.client_errors || 0)"
                    :badge="stats.client_errors > 0 ? '4xx HTTP' : 'Clean'"
                    :badgeType="stats.client_errors > 0 ? 'warning' : 'success'"
                    color="purple"
                    :icon="ShieldExclamationIcon"
                />

                <InfoCard
                    title="Server Errors"
                    :value="String(stats.server_errors || 0)"
                    :badge="stats.server_errors > 0 ? '5xx Fatal' : 'Zero'"
                    :badgeType="stats.server_errors > 0 ? 'danger' : 'success'"
                    color="rose"
                    :icon="ExclamationTriangleIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Source Selection Tabs -->
            <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        v-for="src in ['nginx_access', 'nginx_error', 'apache_access', 'apache_error']"
                        :key="src"
                        type="button"
                        @click="selectSource(src)"
                        :class="currentSource === src ? 'bg-blue-600 text-white' : 'bg-white text-slate-700 hover:bg-slate-50 border border-slate-200'"
                        class="px-3 py-1.5 rounded-[3px] font-bold text-xs uppercase font-mono transition cursor-pointer shadow-2xs"
                    >
                        {{ src }}.log
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
                searchPlaceholder="Search request URI, IP address, user-agent, or status code..."
                @search="applyFilters"
                @filter="applyFilters"
                @reset="resetFilters"
            >
                <FilterSelect
                    v-model="currentStatus"
                    label="Status Code"
                    :options="[
                        { label: 'All HTTP Statuses', value: 'all' },
                        { label: '2xx Success', value: '2xx' },
                        { label: '3xx Redirects', value: '3xx' },
                        { label: '4xx Client Errors', value: '4xx' },
                        { label: '5xx Server Errors', value: '5xx' }
                    ]"
                    placeholder="All Statuses"
                />

                <FilterSelect
                    v-model="currentMethod"
                    label="HTTP Method"
                    :options="[
                        { label: 'All Methods', value: 'all' },
                        { label: 'GET', value: 'GET' },
                        { label: 'POST', value: 'POST' },
                        { label: 'PUT', value: 'PUT' },
                        { label: 'DELETE', value: 'DELETE' }
                    ]"
                    placeholder="All Methods"
                />
            </DataTableFilter>

            <!-- 5. Log Stream Data Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left w-32">Client IP</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 w-20">Method</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Request URI Target</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 w-20">Status</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 w-24">Bytes</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">User Agent / Details</th>
                                <th class="py-2.5 px-3 w-16">Copy</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap font-mono text-[11px]">
                            <tr v-for="(log, index) in logs" :key="log.id || index" class="hover:bg-slate-50/80 transition">
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 whitespace-nowrap font-sans">
                                    {{ index + 1 }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-bold text-slate-900">
                                    {{ log.ip || '127.0.0.1' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span class="px-1.5 py-0.2 rounded text-[10px] font-bold uppercase bg-slate-100 text-slate-700 border border-slate-200 font-mono">
                                        {{ log.method || 'GET' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap text-blue-700 font-bold max-w-xs truncate" :title="log.uri">
                                    {{ log.uri || '/' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-sans">
                                    <span 
                                        :class="[
                                            (log.status || 200) >= 500 ? 'bg-rose-50 text-rose-700 border-rose-200' :
                                            (log.status || 200) >= 400 ? 'bg-amber-50 text-amber-700 border-amber-200' :
                                            (log.status || 200) >= 300 ? 'bg-blue-50 text-blue-700 border-blue-200' :
                                            'bg-emerald-50 text-emerald-700 border-emerald-200'
                                        ]"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold border font-mono"
                                    >
                                        {{ log.status || 200 }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center text-slate-600 text-[10.5px]">
                                    {{ log.bytes ? log.bytes + ' B' : '1.2 KB' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap text-slate-600 max-w-sm truncate" :title="log.user_agent || log.raw">
                                    {{ log.user_agent || log.raw }}
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
                                <td colspan="8" class="py-12 text-center text-slate-400 font-sans">
                                    No log entries found in {{ currentSource }}.log.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
