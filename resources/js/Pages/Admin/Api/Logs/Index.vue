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
import RowActionDropdown from '@/Components/UI/RowActionDropdown.vue'

import {
    ArrowPathIcon,
    TrashIcon,
    XMarkIcon,
    CommandLineIcon,
    CheckIcon,
    ExclamationTriangleIcon,
    ClockIcon,
    EyeIcon,
    KeyIcon,
    DocumentDuplicateIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    logs: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    stats: {
        type: Object,
        default: () => ({
            total_requests: 0,
            success_count: 0,
            success_rate: 100,
            client_errors: 0,
            avg_latency: 0,
        }),
    },
    filters: {
        type: Object,
        default: () => ({ search: '', method: 'all', status: 'all' }),
    },
})

// Flash Feedback Toast
const feedbackMsg = ref('')

// Filter State
const search = ref(props.filters?.search || '')
const currentMethod = ref(props.filters?.method || 'all')
const currentStatus = ref(props.filters?.status || 'all')

const applyFilters = () => {
    router.get(route('admin.api.logs'), {
        search: search.value || undefined,
        method: currentMethod.value !== 'all' ? currentMethod.value : undefined,
        status: currentStatus.value !== 'all' ? currentStatus.value : undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const selectMethod = (method) => {
    currentMethod.value = method
    applyFilters()
}

const resetFilters = () => {
    search.value = ''
    currentMethod.value = 'all'
    currentStatus.value = 'all'
    applyFilters()
}

// Clear Logs
const clearLogs = () => {
    if (confirm('Are you sure you want to purge all API request logs?')) {
        router.post(route('admin.api.logs.clear'), {}, {
            preserveScroll: true,
            onSuccess: () => {
                feedbackMsg.value = 'API logs purged.'
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    }
}

// Inspect Modal
const showInspectModal = ref(false)
const selectedLog = ref(null)

const openInspectModal = (log) => {
    selectedLog.value = log
    showInspectModal.value = true
}

const copyEndpoint = (endpoint) => {
    navigator.clipboard.writeText(endpoint)
    feedbackMsg.value = 'Endpoint path copied.'
    setTimeout(() => { feedbackMsg.value = '' }, 2500)
}
</script>

<template>
    <Head title="REST API Logs - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'API', href: route('admin.api.logs') },
                    { label: 'API Logs' }
                ]"
            >
                <template #actions>
                    <Link
                        :href="route('admin.api.keys')"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <KeyIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>API Keys</span>
                    </Link>

                    <button 
                        type="button" 
                        @click="clearLogs"
                        class="px-2.5 py-1.5 bg-white hover:bg-rose-50 text-rose-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <TrashIcon class="w-3.5 h-3.5" />
                        <span>Purge Logs</span>
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
                    title="Logged API Calls"
                    :value="String(stats.total_requests || logs.data?.length || 0)"
                    badge="Requests"
                    badgeType="info"
                    color="blue"
                    :icon="CommandLineIcon"
                />

                <InfoCard
                    title="Success Rate (2xx)"
                    :value="`${stats.success_rate || 100}%`"
                    badge="Healthy"
                    badgeType="success"
                    color="emerald"
                    :icon="CheckIcon"
                />

                <InfoCard
                    title="Client / Auth Errors"
                    :value="String(stats.client_errors || 0)"
                    badge="4xx/5xx"
                    badgeType="warning"
                    color="rose"
                    :icon="ExclamationTriangleIcon"
                />

                <InfoCard
                    title="Avg Request Latency"
                    :value="`${stats.avg_latency || 24} ms`"
                    badge="Speed"
                    badgeType="info"
                    color="sky"
                    :icon="ClockIcon"
                />
            </InfoCardsGrid>

            <!-- 3. HTTP Method Tabs -->
            <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        v-for="m in ['all', 'GET', 'POST', 'PUT', 'DELETE']"
                        :key="m"
                        type="button"
                        @click="selectMethod(m)"
                        :class="currentMethod === m ? 'bg-blue-600 text-white' : 'bg-white text-slate-700 hover:bg-slate-50 border border-slate-200'"
                        class="px-3 py-1.5 rounded-[3px] font-bold text-xs uppercase font-mono transition cursor-pointer shadow-2xs"
                    >
                        {{ m }}
                    </button>
                </div>
            </div>

            <!-- 4. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search endpoint URI, IP address, client key..."
                @search="applyFilters"
                @filter="applyFilters"
                @reset="resetFilters"
            >
                <FilterSelect
                    v-model="currentStatus"
                    label="Status Code"
                    :options="[
                        { label: 'All Statuses', value: 'all' },
                        { label: '200 OK (Success)', value: '200' },
                        { label: '401 / 403 (Auth Failure)', value: '401' },
                        { label: '429 (Rate Limited)', value: '429' },
                        { label: '500 (Server Error)', value: '500' }
                    ]"
                    placeholder="All Statuses"
                />
            </DataTableFilter>

            <!-- 5. Standard Data Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 w-20">Method</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Request Endpoint</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Client IP / Key</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">HTTP Status</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Latency</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Timestamp</th>
                                <th class="py-2.5 px-3 w-14 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(log, index) in logs.data" :key="log.id || index" class="hover:bg-blue-50/30 transition">
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="[
                                            log.method === 'GET' ? 'bg-blue-50 text-blue-700 border-blue-200' :
                                            log.method === 'POST' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                                            log.method === 'PUT' ? 'bg-amber-50 text-amber-700 border-amber-200' :
                                            'bg-rose-50 text-rose-700 border-rose-200'
                                        ]"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ log.method }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono font-bold text-slate-900 max-w-sm truncate" :title="log.endpoint || log.path">
                                    {{ log.endpoint || log.path }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono text-[11px] text-slate-600">
                                    <div>
                                        <span>{{ log.ip_address || '127.0.0.1' }}</span>
                                        <span v-if="log.api_key_name" class="text-[10px] text-slate-400 block font-sans">({{ log.api_key_name }})</span>
                                    </div>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono font-bold text-[11px]">
                                    <span 
                                        :class="[
                                            log.status >= 200 && log.status < 300 ? 'text-emerald-700' :
                                            log.status >= 400 && log.status < 500 ? 'text-amber-700' :
                                            'text-rose-700'
                                        ]"
                                    >
                                        {{ log.status || 200 }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[11px] text-slate-600">
                                    {{ log.latency_ms || log.duration || 12 }} ms
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[10.5px] text-slate-500">
                                    {{ log.created_at ? new Date(log.created_at).toLocaleString() : 'Recent' }}
                                </td>
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <RowActionDropdown>
                                        <button 
                                            type="button" 
                                            @click="openInspectModal(log)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-blue-600 hover:bg-blue-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <EyeIcon class="w-3.5 h-3.5 text-blue-500" />
                                            <span>Inspect Payload & Headers</span>
                                        </button>

                                        <button
                                            type="button"
                                            @click="copyEndpoint(log.endpoint || log.path)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <DocumentDuplicateIcon class="w-3.5 h-3.5 text-slate-500" />
                                            <span>Copy Request URI</span>
                                        </button>
                                    </RowActionDropdown>
                                </td>
                            </tr>

                            <tr v-if="!logs.data || logs.data.length === 0">
                                <td colspan="8" class="py-12 text-center text-slate-400 font-sans">
                                    No REST API audit logs found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- INSPECT PAYLOAD MODAL -->
        <div v-if="showInspectModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-lg overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <EyeIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            API Request Telemetry Inspector
                        </h3>
                    </div>
                    <button @click="showInspectModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-3 text-xs">
                    <div class="bg-slate-900 text-slate-100 p-3 rounded-[3px] font-mono text-[11px] overflow-x-auto space-y-1">
                        <div class="text-emerald-400 font-bold">{{ selectedLog?.method }} {{ selectedLog?.endpoint || selectedLog?.path }}</div>
                        <div class="text-slate-400">Status: {{ selectedLog?.status }} | Duration: {{ selectedLog?.latency_ms || 12 }}ms</div>
                        <div class="text-slate-400">IP: {{ selectedLog?.ip_address }} | User-Agent: {{ selectedLog?.user_agent || 'cURL / SDK' }}</div>
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Request Headers & Payload</label>
                        <pre class="bg-slate-50 p-2 rounded-[3px] border border-slate-200 text-[11px] font-mono max-h-40 overflow-y-auto text-slate-800">{{ JSON.stringify(selectedLog?.payload || selectedLog?.request_data || { message: "No request body passed" }, null, 2) }}</pre>
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Response Payload</label>
                        <pre class="bg-slate-50 p-2 rounded-[3px] border border-slate-200 text-[11px] font-mono max-h-40 overflow-y-auto text-slate-800">{{ JSON.stringify(selectedLog?.response || selectedLog?.response_data || { status: "success", code: 200 }, null, 2) }}</pre>
                    </div>
                </div>

                <div class="px-4 py-2.5 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                    <button
                        type="button"
                        @click="showInspectModal = false"
                        class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
