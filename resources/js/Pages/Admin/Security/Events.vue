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
    ShieldCheckIcon,
    ShieldExclamationIcon,
    TrashIcon,
    ArrowPathIcon,
    XMarkIcon,
    CheckIcon,
    DocumentDuplicateIcon,
    ArrowDownTrayIcon,
    ClockIcon,
    ServerIcon,
    BoltIcon,
    NoSymbolIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    auditLogs: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    systemEvents: {
        type: Array,
        default: () => [],
    },
    metrics: {
        type: Object,
        default: () => ({
            total_events: 0,
            today_events: 0,
            unique_ips: 0,
            security_actions: 0,
        }),
    },
    filters: {
        type: Object,
        default: () => ({ search: '', action: 'all', view: 'audit' }),
    },
})

// Search, View Tab & Filter State
const search = ref(props.filters.search || '')
const actionFilter = ref(props.filters.action || 'all')
const activeView = ref(props.filters.view || 'audit') // 'audit' or 'system'
const feedbackMsg = ref('')

const logList = computed(() => {
    if (Array.isArray(props.auditLogs)) return props.auditLogs
    if (props.auditLogs?.data && Array.isArray(props.auditLogs.data)) return props.auditLogs.data
    return []
})

const filteredLogs = computed(() => {
    let list = logList.value

    if (search.value.trim()) {
        const q = search.value.toLowerCase()
        list = list.filter(l =>
            (l.action || '').toLowerCase().includes(q) ||
            (l.description || '').toLowerCase().includes(q) ||
            (l.ip_address || '').toLowerCase().includes(q) ||
            (l.user?.name || '').toLowerCase().includes(q)
        )
    }

    if (actionFilter.value !== 'all') {
        list = list.filter(l => (l.action || '').toLowerCase().includes(actionFilter.value.toLowerCase()))
    }

    return list
})

const filteredSystemEvents = computed(() => {
    let list = props.systemEvents || []
    if (search.value.trim()) {
        const q = search.value.toLowerCase()
        list = list.filter(e =>
            (e.raw || '').toLowerCase().includes(q) ||
            (e.service || '').toLowerCase().includes(q) ||
            (e.ip || '').toLowerCase().includes(q)
        )
    }
    return list
})

const resetFilters = () => {
    search.value = ''
    actionFilter.value = 'all'
}

// 1. PURGE LOGS MODAL
const showPurgeModal = ref(false)
const purgeForm = useForm({
    days: 30,
})

const submitPurge = () => {
    purgeForm.post(route('admin.security.events.purge'), {
        preserveScroll: true,
        onSuccess: () => {
            showPurgeModal.value = false
            feedbackMsg.value = `Audit logs older than ${purgeForm.days} days purged.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        },
    })
}

// 2. QUICK BLOCK IP
const quickBlock = (ip) => {
    if (!ip) return
    router.post(route('admin.security.events.quick-block'), {
        ip: ip,
        reason: 'Blocked directly from security events stream',
    }, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = `IP ${ip} blocked in firewall.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

const copyToClipboard = (text, label) => {
    navigator.clipboard.writeText(text)
    feedbackMsg.value = `${label} copied to clipboard!`
    setTimeout(() => { feedbackMsg.value = '' }, 2500)
}
</script>

<template>
    <Head title="Security Events & Audit - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Security', href: '#' },
                    { label: 'Security & Data Protection', href: route('admin.security.ssl') },
                    { label: 'Security Audit & Events Trail' }
                ]"
            >
                <template #actions>
                    <a
                        :href="route('admin.security.events.export')"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowDownTrayIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Export CSV</span>
                    </a>

                    <button 
                        type="button" 
                        @click="showPurgeModal = true"
                        class="px-2.5 py-1.5 bg-white hover:bg-rose-50 text-rose-600 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-rose-200 shadow-2xs transition cursor-pointer"
                    >
                        <TrashIcon class="w-3.5 h-3.5" />
                        <span>Purge Old Logs</span>
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
                    title="Audit Events Logged"
                    :value="String(metrics.total_events || logList.length || 0)"
                    badge="Total"
                    badgeType="info"
                    color="blue"
                    :icon="ShieldCheckIcon"
                />

                <InfoCard
                    title="Events Past 24 Hours"
                    :value="String(metrics.today_events || 0)"
                    badge="Recent"
                    badgeType="success"
                    color="emerald"
                    :icon="ClockIcon"
                />

                <InfoCard
                    title="Unique Client IPs"
                    :value="String(metrics.unique_ips || 0)"
                    badge="Origin IPs"
                    badgeType="info"
                    color="purple"
                    :icon="ServerIcon"
                />

                <InfoCard
                    title="Security Actions"
                    :value="String(metrics.security_actions || 0)"
                    badge="Critical"
                    badgeType="warning"
                    color="sky"
                    :icon="BoltIcon"
                />
            </InfoCardsGrid>

            <!-- 3. View Switcher Tabs -->
            <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="activeView = 'audit'"
                        :class="activeView === 'audit' ? 'bg-blue-600 text-white' : 'bg-white text-slate-700 hover:bg-slate-50 border border-slate-200'"
                        class="px-3 py-1.5 rounded-[3px] font-bold text-xs transition cursor-pointer shadow-2xs"
                    >
                        Administrative Audit Trail
                    </button>
                    <button
                        type="button"
                        @click="activeView = 'system'"
                        :class="activeView === 'system' ? 'bg-blue-600 text-white' : 'bg-white text-slate-700 hover:bg-slate-50 border border-slate-200'"
                        class="px-3 py-1.5 rounded-[3px] font-bold text-xs transition cursor-pointer shadow-2xs"
                    >
                        Live System Auth Stream (auth.log)
                    </button>
                </div>
            </div>

            <!-- 4. Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                :searchPlaceholder="activeView === 'audit' ? 'Search action, description, operator, or IP...' : 'Search log line, subsystem, or IP...'"
                @search="() => {}"
                @filter="() => {}"
                @reset="resetFilters"
            >
                <FilterSelect
                    v-if="activeView === 'audit'"
                    v-model="actionFilter"
                    label="Action Category"
                    :options="[
                        { label: 'All Actions', value: 'all' },
                        { label: 'Authentication (Login / Logout)', value: 'auth' },
                        { label: 'Security & Firewall', value: 'security' },
                        { label: 'Database Operations', value: 'database' },
                        { label: 'VHost & Domain Config', value: 'domain' }
                    ]"
                    placeholder="All Actions"
                />
            </DataTableFilter>

            <!-- 5. Data Tables -->
            <!-- VIEW A: Administrative Audit Trail -->
            <div v-if="activeView === 'audit'" class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Action Trigger</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Description / Details</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Admin Operator</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Source IP</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Timestamp</th>
                                <th class="py-2.5 px-3 w-28">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(log, index) in filteredLogs" :key="log.id || index" class="hover:bg-blue-50/30 transition">
                                <!-- # -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>

                                <!-- Action -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono font-bold text-blue-700">
                                    {{ log.action }}
                                </td>

                                <!-- Description -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap text-slate-800 max-w-sm truncate" :title="log.description">
                                    {{ log.description }}
                                </td>

                                <!-- User -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-bold text-slate-900">
                                    {{ log.user ? log.user.name : (log.user_id ? `User #${log.user_id}` : 'System Agent') }}
                                </td>

                                <!-- IP -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono text-[11px] text-slate-600">
                                    {{ log.ip_address || '127.0.0.1' }}
                                </td>

                                <!-- Time -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[10.5px] text-slate-500">
                                    {{ log.created_at ? new Date(log.created_at).toLocaleString() : 'Recent' }}
                                </td>

                                <!-- Action -->
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1">
                                        <button 
                                            v-if="log.ip_address && log.ip_address !== '127.0.0.1'"
                                            type="button" 
                                            @click="quickBlock(log.ip_address)"
                                            class="px-2 py-1 bg-white hover:bg-rose-50 text-rose-700 font-bold rounded-[3px] text-xs border border-slate-200 shadow-2xs transition cursor-pointer"
                                            title="Block IP in Firewall"
                                        >
                                            Block 🚫
                                        </button>

                                        <RowActionDropdown>
                                            <button
                                                type="button"
                                                @click="copyToClipboard(log.ip_address, 'IP Address')"
                                                class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <DocumentDuplicateIcon class="w-3.5 h-3.5 text-slate-500" />
                                                <span>Copy IP Address</span>
                                            </button>
                                        </RowActionDropdown>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!filteredLogs || filteredLogs.length === 0">
                                <td colspan="7" class="py-12 text-center text-slate-400">
                                    No administrative audit logs recorded.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- VIEW B: Live System Auth Stream -->
            <div v-else class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Service Subsystem</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">System Log Line</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Extracted Attacker IP</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Event Time</th>
                                <th class="py-2.5 px-3 w-28">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(ev, index) in filteredSystemEvents" :key="index" class="hover:bg-slate-50/70 transition font-mono text-[11px]">
                                <!-- # -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>

                                <!-- Service -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-bold text-slate-900">
                                    {{ ev.service || 'sshd' }}
                                </td>

                                <!-- Raw Log -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap text-slate-700 max-w-md truncate" :title="ev.raw">
                                    {{ ev.raw }}
                                </td>

                                <!-- IP -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-bold text-rose-700">
                                    {{ ev.ip || '-' }}
                                </td>

                                <!-- Time -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center text-slate-500 text-[10.5px]">
                                    {{ ev.timestamp || 'Live' }}
                                </td>

                                <!-- Action -->
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <button 
                                        v-if="ev.ip"
                                        type="button" 
                                        @click="quickBlock(ev.ip)"
                                        class="px-2 py-1 bg-white hover:bg-rose-50 text-rose-700 font-bold rounded-[3px] text-xs border border-slate-200 shadow-2xs transition cursor-pointer"
                                    >
                                        Block 🚫
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="!filteredSystemEvents || filteredSystemEvents.length === 0">
                                <td colspan="6" class="py-12 text-center text-slate-400 font-sans">
                                    No auth.log events found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- PURGE LOGS MODAL -->
        <div v-if="showPurgeModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-rose-50 text-rose-600 flex items-center justify-center font-bold border border-rose-100">
                            <TrashIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Purge Security Audit Logs
                        </h3>
                    </div>
                    <button @click="showPurgeModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitPurge" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Purge Retention Period</label>
                        <select v-model="purgeForm.days" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-rose-500 cursor-pointer">
                            <option :value="7">Purge logs older than 7 days</option>
                            <option :value="30">Purge logs older than 30 days</option>
                            <option :value="90">Purge logs older than 90 days</option>
                            <option :value="0">Purge ALL historical logs</option>
                        </select>
                    </div>

                    <div class="px-4 py-2.5 -mx-4 -mb-4 mt-4 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                        <button
                            type="button"
                            @click="showPurgeModal = false"
                            class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="purgeForm.processing"
                            class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ purgeForm.processing ? 'Purging...' : 'Purge Logs' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
