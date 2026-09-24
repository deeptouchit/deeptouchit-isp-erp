<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'
import DataTableFilter from '@/Components/UI/DataTableFilter.vue'
import FilterSelect from '@/Components/UI/FilterSelect.vue'
import Pagination from '@/Components/UI/Pagination.vue'

import {
    ClockIcon,
    ShieldCheckIcon,
    CpuChipIcon,
    CubeIcon,
    ArrowPathIcon,
    CodeBracketIcon,
    XMarkIcon,
    GlobeAltIcon,
    BoltIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    activities: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    stats: {
        type: Object,
        default: () => ({
            total_events: 0,
            provisioning_ops: 0,
            security_ops: 0,
            runtime_ops: 0,
        }),
    },
    filters: {
        type: Object,
        default: () => ({ search: '', action_type: '', date_range: '' }),
    },
})

// Filter State
const search = ref(props.filters?.search || '')
const actionType = ref(props.filters?.action_type || '')
const dateRange = ref(props.filters?.date_range || '')

const applyFilters = () => {
    router.get(route('admin.hosting.activity'), {
        search: search.value || undefined,
        action_type: actionType.value || undefined,
        date_range: dateRange.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const resetFilters = () => {
    search.value = ''
    actionType.value = ''
    dateRange.value = ''
    applyFilters()
}

// JSON Payload Inspector Modal
const showPayloadModal = ref(false)
const selectedActivity = ref(null)

const inspectPayload = (activity) => {
    selectedActivity.value = activity
    showPayloadModal.value = true
}

// Helpers
const formatDate = (dateStr) => {
    if (!dateStr) return 'Never'
    try {
        const d = new Date(dateStr)
        if (isNaN(d.getTime())) return dateStr
        return new Intl.DateTimeFormat('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
        }).format(d)
    } catch {
        return dateStr
    }
}

const formatActionName = (action) => {
    if (!action) return 'Event'
    return action
        .replace(/_/g, ' ')
        .replace(/\b\w/g, l => l.toUpperCase())
}

const getActionBadgeClass = (action) => {
    if (!action) return 'bg-slate-100 text-slate-700 border-slate-200'
    const a = action.toLowerCase()
    if (a.includes('create') || a.includes('provision') || a.includes('store')) {
        return 'bg-emerald-50 text-emerald-700 border-emerald-200'
    }
    if (a.includes('delete') || a.includes('terminate') || a.includes('destroy')) {
        return 'bg-rose-50 text-rose-700 border-rose-200'
    }
    if (a.includes('suspend') || a.includes('unsuspend') || a.includes('lock')) {
        return 'bg-amber-50 text-amber-700 border-amber-200'
    }
    if (a.includes('ssl') || a.includes('cert') || a.includes('security')) {
        return 'bg-teal-50 text-teal-700 border-teal-200'
    }
    if (a.includes('php') || a.includes('runtime') || a.includes('plan')) {
        return 'bg-purple-50 text-purple-700 border-purple-200'
    }
    return 'bg-blue-50 text-blue-700 border-blue-200'
}
</script>

<template>
    <Head title="Hosting Activity & Audit Trail - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Hosting', href: '#' },
                    { label: 'Activity Logs' }
                ]"
            >
                <template #actions>
                    <button 
                        type="button" 
                        @click="router.reload({ preserveScroll: true })"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowPathIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Refresh</span>
                    </button>
                </template>
            </PageHeader>

            <!-- 2. Ultra-Compact KPI Info Cards (56px) -->
            <InfoCardsGrid :cols="4">
                <InfoCard
                    title="Total Audit Logs"
                    :value="String(stats.total_events || 0)"
                    badge="Logged"
                    badgeType="info"
                    color="blue"
                    :icon="ClockIcon"
                />

                <InfoCard
                    title="Provisioning Ops"
                    :value="String(stats.provisioning_ops || 0)"
                    badge="VHosts"
                    badgeType="success"
                    color="emerald"
                    :icon="CubeIcon"
                />

                <InfoCard
                    title="Security & SSL"
                    :value="String(stats.security_ops || 0)"
                    badge="Audits"
                    badgeType="info"
                    color="sky"
                    :icon="ShieldCheckIcon"
                />

                <InfoCard
                    title="Runtime & Config"
                    :value="String(stats.runtime_ops || 0)"
                    badge="Mutations"
                    badgeType="success"
                    color="purple"
                    :icon="CpuChipIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search by action, description, IP, or user..."
                @search="applyFilters"
                @filter="applyFilters"
                @reset="resetFilters"
            >
                <FilterSelect
                    v-model="actionType"
                    label="Category"
                    :options="[
                        { label: 'Provisioning & Creation', value: 'provision' },
                        { label: 'Plan & Quota Mutations', value: 'plan' },
                        { label: 'PHP & Engine Runtime', value: 'php' },
                        { label: 'SSL & Security Events', value: 'ssl' },
                        { label: 'Suspension / Lock Lifecycle', value: 'status' },
                        { label: 'Termination & Deletion', value: 'delete' }
                    ]"
                    placeholder="All Event Categories"
                    @change="applyFilters"
                />

                <FilterSelect
                    v-model="dateRange"
                    label="Timespan"
                    :options="[
                        { label: 'Today (Last 24h)', value: 'today' },
                        { label: 'Last 7 Days', value: '7d' },
                        { label: 'Last 30 Days', value: '30d' }
                    ]"
                    placeholder="All Timespans"
                    @change="applyFilters"
                />
            </DataTableFilter>

            <!-- 4. Standard Data Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Timestamp</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Actor / User</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Event Action</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Event Description & Context</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">IP Address</th>
                                <th class="py-2.5 px-3 w-16">Payload</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(act, index) in activities.data" :key="act.id" class="hover:bg-blue-50/30 transition">
                                <!-- # -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ ((activities.current_page || 1) - 1) * (activities.per_page || 15) + (index + 1) }}
                                </td>

                                <!-- Timestamp -->
                                <td class="py-2.5 px-3 border-r border-slate-100 font-mono text-slate-500 text-[10.5px] whitespace-nowrap">
                                    {{ formatDate(act.created_at) }}
                                </td>

                                <!-- Actor / User -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap">
                                    <div v-if="act.user" class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-[3px] bg-slate-100 text-slate-700 flex items-center justify-center font-bold text-[10px] border border-slate-200 shrink-0">
                                            {{ (act.user.first_name || act.user.name || 'A').charAt(0).toUpperCase() }}
                                        </div>
                                        <div>
                                            <span class="font-bold text-slate-900 block leading-tight">{{ act.user.first_name }} {{ act.user.last_name }}</span>
                                            <span class="text-[10px] font-mono text-slate-400 block">{{ act.user.email }}</span>
                                        </div>
                                    </div>
                                    <div v-else class="flex items-center gap-1.5 text-slate-400 italic">
                                        <span>System Daemon</span>
                                    </div>
                                </td>

                                <!-- Action Badge -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="getActionBadgeClass(act.action)"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono whitespace-nowrap"
                                    >
                                        {{ formatActionName(act.action) }}
                                    </span>
                                </td>

                                <!-- Description -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap">
                                    <span class="text-slate-800 font-medium">
                                        {{ act.description || 'System operation executed.' }}
                                    </span>
                                </td>

                                <!-- IP -->
                                <td class="py-2.5 px-3 border-r border-slate-100 font-mono text-slate-500 text-[10.5px] whitespace-nowrap">
                                    {{ act.ip_address || '127.0.0.1' }}
                                </td>

                                <!-- Payload Action -->
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <button
                                        v-if="act.properties || act.payload"
                                        type="button"
                                        @click="inspectPayload(act)"
                                        class="px-2 py-1 bg-white hover:bg-slate-100 text-slate-700 rounded-[3px] border border-slate-200 text-xs font-bold transition flex items-center gap-1 mx-auto shadow-2xs cursor-pointer"
                                        title="Inspect JSON Payload"
                                    >
                                        <CodeBracketIcon class="w-3.5 h-3.5 text-slate-500" />
                                        <span>Inspect</span>
                                    </button>
                                    <span v-else class="text-slate-300 text-xs font-mono">-</span>
                                </td>
                            </tr>

                            <tr v-if="!activities.data || activities.data.length === 0">
                                <td colspan="7" class="py-12 text-center text-slate-400">
                                    No audit events recorded for the selected criteria.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <Pagination :links="activities.links" :from="activities.from" :to="activities.to" :total="activities.total" />
            </div>
        </div>

        <!-- JSON PAYLOAD INSPECTOR MODAL -->
        <div v-if="showPayloadModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-lg overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <CodeBracketIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Event Payload Inspector
                        </h3>
                    </div>
                    <button @click="showPayloadModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-3 text-xs">
                    <div class="flex items-center justify-between text-[11px] text-slate-500 font-mono">
                        <span>Event: <strong>{{ formatActionName(selectedActivity?.action) }}</strong></span>
                        <span>{{ formatDate(selectedActivity?.created_at) }}</span>
                    </div>

                    <div class="bg-slate-900 text-emerald-400 p-3.5 rounded-[3px] font-mono text-[11px] max-h-72 overflow-y-auto border border-slate-800">
                        <pre>{{ JSON.stringify(selectedActivity?.properties || selectedActivity?.payload || {}, null, 2) }}</pre>
                    </div>
                </div>

                <div class="px-4 py-2.5 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end text-xs">
                    <button
                        type="button"
                        @click="showPayloadModal = false"
                        class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
