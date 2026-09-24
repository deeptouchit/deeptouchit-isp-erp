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
    DocumentTextIcon,
    ArrowPathIcon,
    TrashIcon,
    XMarkIcon,
    CheckIcon,
    ExclamationTriangleIcon,
    ShieldCheckIcon,
    CircleStackIcon,
    ClockIcon,
    ArchiveBoxIcon,
    CloudIcon,
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
            total_logs: 0,
            created_events: 0,
            restored_events: 0,
            storage_events: 0,
            retention_policy: '90 Days Auto-Rotate',
        }),
    },
    filters: {
        type: Object,
        default: () => ({ search: '', action: '' }),
    },
})

// Search & Filter
const search = ref(props.filters?.search || '')
const action = ref(props.filters?.action || '')
const feedbackMsg = ref('')

const applyFilters = () => {
    router.get(route('admin.backups.logs'), {
        search: search.value || undefined,
        action: action.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const resetFilters = () => {
    search.value = ''
    action.value = ''
    applyFilters()
}

// Details Modal
const showDetailsModal = ref(false)
const selectedLog = ref(null)

const openDetailsModal = (item) => {
    selectedLog.value = item
    showDetailsModal.value = true
}

// Flush Logs Modal
const showFlushModal = ref(false)
const isFlushing = ref(false)

const confirmFlush = () => {
    isFlushing.value = true
    router.post(route('admin.backups.logs.flush'), {}, {
        preserveScroll: true,
        onSuccess: () => {
            showFlushModal.value = false
            feedbackMsg.value = 'Backup audit logs flushed successfully.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        },
        onFinish: () => {
            isFlushing.value = false
        }
    })
}
</script>

<template>
    <Head title="Backup Audit Logs - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Security', href: '#' },
                    { label: 'Backups & Snapshots', href: route('admin.backups.index') },
                    { label: 'Backup & Recovery Audit Logs' }
                ]"
            >
                <template #actions>
                    <Link
                        :href="route('admin.backups.index')"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowLeftIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>All Snapshots</span>
                    </Link>

                    <button
                        type="button"
                        @click="showFlushModal = true"
                        class="px-2.5 py-1.5 bg-white hover:bg-rose-50 text-rose-600 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-rose-200 shadow-2xs transition cursor-pointer"
                    >
                        <TrashIcon class="w-3.5 h-3.5" />
                        <span>Flush Logs</span>
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
                    title="Total Audit Events"
                    :value="String(stats.total_logs || logs.data?.length || 0)"
                    badge="Audits"
                    badgeType="info"
                    color="blue"
                    :icon="DocumentTextIcon"
                />

                <InfoCard
                    title="Created Snapshots"
                    :value="String(stats.created_events || 0)"
                    badge="Generated"
                    badgeType="success"
                    color="emerald"
                    :icon="ArchiveBoxIcon"
                />

                <InfoCard
                    title="Restored Snapshots"
                    :value="String(stats.restored_events || 0)"
                    badge="Restores"
                    badgeType="warning"
                    color="purple"
                    :icon="ArrowPathIcon"
                />

                <InfoCard
                    title="Storage Health Events"
                    :value="String(stats.storage_events || 0)"
                    badge="Target IO"
                    badgeType="info"
                    color="sky"
                    :icon="CloudIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search backup log message, user, filename, or IP..."
                @search="applyFilters"
                @filter="applyFilters"
                @reset="resetFilters"
            >
                <FilterSelect
                    v-model="action"
                    label="Action Type"
                    :options="[
                        { label: 'All Action Types', value: '' },
                        { label: 'Backup Created', value: 'backup_created' },
                        { label: 'Backup Restored', value: 'backup_restored' },
                        { label: 'Backup Deleted', value: 'backup_deleted' },
                        { label: 'Storage Tested', value: 'storage_tested' }
                    ]"
                    placeholder="All Actions"
                />
            </DataTableFilter>

            <!-- 4. Standard Data Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Initiating Operator</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Action Event</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Backup Subject Details</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Remote IP</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Timestamp</th>
                                <th class="py-2.5 px-3 w-20">Details</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(log, index) in logs.data" :key="log.id || index" class="hover:bg-blue-50/30 transition">
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-bold text-slate-900">
                                    {{ log.user?.name || log.operator || 'System Daemon' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="[
                                            log.action?.includes('restored') ? 'bg-amber-50 text-amber-700 border-amber-200' :
                                            log.action?.includes('deleted') ? 'bg-rose-50 text-rose-700 border-rose-200' :
                                            'bg-emerald-50 text-emerald-700 border-emerald-200'
                                        ]"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ log.action || 'backup' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap text-slate-800 max-w-sm truncate" :title="log.description || log.filename">
                                    {{ log.description || log.filename || 'Snapshot event recorded' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono text-[11px] text-slate-600">
                                    {{ log.ip_address || '127.0.0.1' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[10.5px] text-slate-500">
                                    {{ log.created_at ? new Date(log.created_at).toLocaleString() : 'Recent' }}
                                </td>
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <button 
                                        type="button" 
                                        @click="openDetailsModal(log)"
                                        class="px-2 py-1 bg-white hover:bg-slate-50 text-blue-700 font-bold rounded-[3px] text-xs border border-slate-200 shadow-2xs transition cursor-pointer"
                                        title="View Details"
                                    >
                                        Inspect 🔍
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="!logs.data || logs.data.length === 0">
                                <td colspan="7" class="py-12 text-center text-slate-400 font-sans">
                                    No backup activity logs found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- DETAILS MODAL -->
        <div v-if="showDetailsModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <DocumentTextIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Backup Audit Event Details
                        </h3>
                    </div>
                    <button @click="showDetailsModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-2.5 text-xs">
                    <div class="flex justify-between border-b border-slate-100 pb-1.5">
                        <span class="text-slate-500">Event ID:</span>
                        <span class="font-mono font-bold text-blue-700">#{{ selectedLog?.id }}</span>
                    </div>
                    <div class="flex justify-between border-b border-slate-100 pb-1.5">
                        <span class="text-slate-500">Action:</span>
                        <span class="font-bold text-slate-900 uppercase font-mono">{{ selectedLog?.action }}</span>
                    </div>
                    <div class="flex justify-between border-b border-slate-100 pb-1.5">
                        <span class="text-slate-500">Operator:</span>
                        <span class="font-bold text-slate-800">{{ selectedLog?.user?.name || selectedLog?.operator || 'System' }}</span>
                    </div>
                    <div class="flex justify-between border-b border-slate-100 pb-1.5">
                        <span class="text-slate-500">IP Address:</span>
                        <span class="font-mono text-slate-700">{{ selectedLog?.ip_address || '127.0.0.1' }}</span>
                    </div>
                    <div class="flex justify-between border-b border-slate-100 pb-1.5">
                        <span class="text-slate-500">Timestamp:</span>
                        <span class="font-mono text-slate-700">{{ selectedLog?.created_at ? new Date(selectedLog.created_at).toLocaleString() : 'Recent' }}</span>
                    </div>

                    <div class="space-y-1 pt-1">
                        <span class="text-slate-500 block">Event Payload / Description:</span>
                        <p class="bg-slate-50 p-2 rounded-[3px] border border-slate-200 text-slate-800 font-mono text-[11px]">{{ selectedLog?.description || selectedLog?.filename }}</p>
                    </div>
                </div>

                <div class="px-4 py-2.5 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end text-xs">
                    <button
                        type="button"
                        @click="showDetailsModal = false"
                        class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>

        <!-- FLUSH CONFIRMATION MODAL -->
        <div v-if="showFlushModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-rose-50 text-rose-600 flex items-center justify-center font-bold border border-rose-100">
                            <TrashIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Flush Backup Audit Logs
                        </h3>
                    </div>
                    <button @click="showFlushModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-2.5 text-xs">
                    <p class="text-slate-600">
                        Are you sure you want to flush and purge all historical backup audit log records?
                    </p>
                </div>

                <div class="px-4 py-2.5 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                    <button
                        type="button"
                        @click="showFlushModal = false"
                        class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="confirmFlush"
                        :disabled="isFlushing"
                        class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                    >
                        {{ isFlushing ? 'Flushing...' : 'Flush Logs' }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
