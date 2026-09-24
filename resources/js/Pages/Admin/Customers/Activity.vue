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
    ArrowPathIcon,
    GlobeAltIcon,
    KeyIcon,
    EyeIcon,
    XMarkIcon,
    CheckIcon,
    UserIcon,
    SparklesIcon,
    CodeBracketIcon,
    ClipboardDocumentIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    activityLogs: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    recentLogins: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            total_events: 0,
            events_today: 0,
            unique_users_active: 0,
            recent_logins_count: 0,
            system_health: '100% Protected',
        }),
    },
    distinctActions: {
        type: Array,
        default: () => [],
    },
    usersList: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({ search: '', action: '', user_id: '' }),
    },
})

const activeTab = ref('audit_logs')

// Filters State
const search = ref(props.filters?.search || '')
const actionFilter = ref(props.filters?.action || '')
const userFilter = ref(props.filters?.user_id || '')

const applyFilters = () => {
    router.get(route('admin.customers.activity'), {
        search: search.value || undefined,
        action: actionFilter.value || undefined,
        user_id: userFilter.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const resetFilters = () => {
    search.value = ''
    actionFilter.value = ''
    userFilter.value = ''
    applyFilters()
}

// Payload Inspector Modal State
const showPayloadModal = ref(false)
const selectedLog = ref(null)
const copiedPayload = ref(false)

const openPayloadModal = (log) => {
    selectedLog.value = log
    copiedPayload.value = false
    showPayloadModal.value = true
}

const copyPayloadToClipboard = () => {
    if (!selectedLog.value) return
    const dataToCopy = JSON.stringify({
        id: selectedLog.value.id,
        action: selectedLog.value.action,
        description: selectedLog.value.description,
        user: selectedLog.value.user?.name,
        ip_address: selectedLog.value.ip_address,
        user_agent: selectedLog.value.user_agent,
        properties: selectedLog.value.properties || selectedLog.value.payload,
        created_at: selectedLog.value.created_at,
    }, null, 2)
    navigator.clipboard.writeText(dataToCopy)
    copiedPayload.value = true
    setTimeout(() => { copiedPayload.value = false }, 2000)
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
    if (a.includes('login') || a.includes('auth') || a.includes('session')) {
        return 'bg-emerald-50 text-emerald-700 border-emerald-200'
    }
    if (a.includes('failed') || a.includes('blocked') || a.includes('deny')) {
        return 'bg-rose-50 text-rose-700 border-rose-200'
    }
    if (a.includes('suspend') || a.includes('lock') || a.includes('warn')) {
        return 'bg-amber-50 text-amber-700 border-amber-200'
    }
    if (a.includes('create') || a.includes('store') || a.includes('provision')) {
        return 'bg-blue-50 text-blue-700 border-blue-200'
    }
    return 'bg-purple-50 text-purple-700 border-purple-200'
}
</script>

<template>
    <Head title="Customer Activity & Audit Trail - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Customers', href: '#' },
                    { label: 'Activity & Audit Logs' }
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
                    title="Total Events Logged"
                    :value="String(stats.total_events || 0)"
                    badge="Audits"
                    badgeType="info"
                    color="blue"
                    :icon="ClockIcon"
                />

                <InfoCard
                    title="Logged Today"
                    :value="String(stats.events_today || 0)"
                    badge="Last 24h"
                    badgeType="success"
                    color="emerald"
                    :icon="SparklesIcon"
                />

                <InfoCard
                    title="Active Identifiers"
                    :value="String(stats.unique_users_active || 0)"
                    badge="Users"
                    badgeType="info"
                    color="sky"
                    :icon="UserIcon"
                />

                <InfoCard
                    title="Recent Logins"
                    :value="String(stats.recent_logins_count || 0)"
                    badge="Sessions"
                    badgeType="success"
                    color="purple"
                    :icon="ShieldCheckIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search events by description, IP address, user, or action..."
                @search="applyFilters"
                @filter="applyFilters"
                @reset="resetFilters"
            >
                <FilterSelect
                    v-if="distinctActions && distinctActions.length > 0"
                    v-model="actionFilter"
                    label="Action Type"
                    :options="distinctActions.map(act => ({ label: formatActionName(act), value: act }))"
                    placeholder="All Event Types"
                    @change="applyFilters"
                />

                <FilterSelect
                    v-if="usersList && usersList.length > 0"
                    v-model="userFilter"
                    label="Customer"
                    :options="usersList.map(u => ({ label: `${u.name} (${u.email})`, value: u.id }))"
                    placeholder="All Customers"
                    @change="applyFilters"
                />
            </DataTableFilter>

            <!-- 4. Tabbed Table Container -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <!-- Tab Switchers -->
                <div class="flex items-center border-b border-slate-200 bg-slate-50/70 px-3">
                    <button
                        type="button"
                        @click="activeTab = 'audit_logs'"
                        :class="activeTab === 'audit_logs' ? 'border-blue-600 text-blue-600 font-bold bg-white' : 'border-transparent text-slate-600 hover:text-slate-900'"
                        class="py-2.5 px-3 text-xs border-b-2 transition cursor-pointer flex items-center gap-1.5"
                    >
                        <ClockIcon class="w-3.5 h-3.5" />
                        <span>Audit Logs & Actions ({{ activityLogs.total || activityLogs.data?.length || 0 }})</span>
                    </button>

                    <button
                        type="button"
                        @click="activeTab = 'logins'"
                        :class="activeTab === 'logins' ? 'border-blue-600 text-blue-600 font-bold bg-white' : 'border-transparent text-slate-600 hover:text-slate-900'"
                        class="py-2.5 px-3 text-xs border-b-2 transition cursor-pointer flex items-center gap-1.5"
                    >
                        <KeyIcon class="w-3.5 h-3.5" />
                        <span>Recent Authentication & Logins ({{ recentLogins?.length || 0 }})</span>
                    </button>
                </div>

                <!-- Tab 1: Audit Logs -->
                <div v-if="activeTab === 'audit_logs'" class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Timestamp</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Actor / Customer</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Action Code</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Event Description</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">IP Address</th>
                                <th class="py-2.5 px-3 w-16">Payload</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(log, index) in activityLogs.data" :key="log.id" class="hover:bg-blue-50/30 transition">
                                <!-- # -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ ((activityLogs.current_page || 1) - 1) * (activityLogs.per_page || 15) + (index + 1) }}
                                </td>

                                <!-- Timestamp -->
                                <td class="py-2.5 px-3 border-r border-slate-100 font-mono text-slate-500 text-[10.5px] whitespace-nowrap">
                                    {{ formatDate(log.created_at) }}
                                </td>

                                <!-- Actor -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap">
                                    <div v-if="log.user" class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-[3px] bg-slate-100 text-slate-700 flex items-center justify-center font-bold text-[10px] border border-slate-200 shrink-0">
                                            {{ (log.user.name || log.user.first_name || 'U').charAt(0).toUpperCase() }}
                                        </div>
                                        <div>
                                            <Link :href="route('admin.users.show', log.user.id)" class="font-bold text-slate-900 hover:text-blue-600 block leading-tight">
                                                {{ log.user.name }}
                                            </Link>
                                            <span class="text-[10px] font-mono text-slate-400 block">{{ log.user.email }}</span>
                                        </div>
                                    </div>
                                    <span v-else class="text-slate-400 italic">System Automation</span>
                                </td>

                                <!-- Action Code -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="getActionBadgeClass(log.action)"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono whitespace-nowrap"
                                    >
                                        {{ formatActionName(log.action) }}
                                    </span>
                                </td>

                                <!-- Event Description -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap">
                                    <span class="text-slate-800 font-medium">
                                        {{ log.description || 'Action performed.' }}
                                    </span>
                                </td>

                                <!-- IP Address -->
                                <td class="py-2.5 px-3 border-r border-slate-100 font-mono text-slate-500 text-[10.5px] whitespace-nowrap">
                                    {{ log.ip_address || '127.0.0.1' }}
                                </td>

                                <!-- Payload Action -->
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <button
                                        type="button"
                                        @click="openPayloadModal(log)"
                                        class="px-2 py-1 bg-white hover:bg-slate-100 text-slate-700 rounded-[3px] border border-slate-200 text-xs font-bold transition flex items-center gap-1 mx-auto shadow-2xs cursor-pointer"
                                        title="Inspect Payload"
                                    >
                                        <CodeBracketIcon class="w-3.5 h-3.5 text-slate-500" />
                                        <span>Inspect</span>
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="!activityLogs.data || activityLogs.data.length === 0">
                                <td colspan="7" class="py-12 text-center text-slate-400">
                                    No customer activity logs recorded matching criteria.
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <Pagination :links="activityLogs.links" :from="activityLogs.from" :to="activityLogs.to" :total="activityLogs.total" />
                </div>

                <!-- Tab 2: Recent Logins -->
                <div v-if="activeTab === 'logins'" class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Login Timestamp</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Customer / Account</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Role</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">IP Origin</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Browser / Platform</th>
                                <th class="py-2.5 px-3 w-16">Profile</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(login, index) in recentLogins" :key="login.id || index" class="hover:bg-blue-50/30 transition">
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">{{ index + 1 }}</td>
                                <td class="py-2.5 px-3 border-r border-slate-100 font-mono text-slate-500 text-[10.5px] whitespace-nowrap">{{ formatDate(login.created_at || login.login_at) }}</td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap">
                                    <div v-if="login.user" class="space-y-0.5">
                                        <Link :href="route('admin.users.show', login.user.id)" class="font-bold text-slate-900 hover:text-blue-600 block leading-tight">
                                            {{ login.user.name }}
                                        </Link>
                                        <span class="text-[10px] font-mono text-slate-400 block">{{ login.user.email }}</span>
                                    </div>
                                    <span v-else class="text-slate-400 italic">Unknown Identity</span>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border bg-slate-100 text-slate-700 font-mono">
                                        {{ login.user?.role || 'User' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 font-mono text-slate-500 text-[10.5px] whitespace-nowrap">{{ login.ip_address || '127.0.0.1' }}</td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left font-mono text-[10.5px] text-slate-500 max-w-xs truncate" :title="login.user_agent">
                                    {{ login.user_agent || 'Standard Web Browser' }}
                                </td>
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <Link v-if="login.user" :href="route('admin.users.show', login.user.id)" class="text-blue-600 hover:text-blue-800 font-bold text-xs">
                                        View
                                    </Link>
                                    <span v-else class="text-slate-300">-</span>
                                </td>
                            </tr>
                            <tr v-if="!recentLogins || recentLogins.length === 0">
                                <td colspan="7" class="py-12 text-center text-slate-400">No recent customer logins recorded.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
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
                            Activity Payload Details
                        </h3>
                    </div>
                    <button @click="showPayloadModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-3 text-xs">
                    <div class="flex items-center justify-between text-[11px] text-slate-500 font-mono">
                        <span>Event: <strong>{{ formatActionName(selectedLog?.action) }}</strong></span>
                        <span>{{ formatDate(selectedLog?.created_at) }}</span>
                    </div>

                    <div class="bg-slate-900 text-emerald-400 p-3.5 rounded-[3px] font-mono text-[11px] max-h-72 overflow-y-auto border border-slate-800">
                        <pre>{{ JSON.stringify(selectedLog?.properties || selectedLog?.payload || { action: selectedLog?.action, description: selectedLog?.description, ip: selectedLog?.ip_address }, null, 2) }}</pre>
                    </div>
                </div>

                <div class="px-4 py-2.5 bg-slate-50/70 border-t border-slate-100 flex items-center justify-between text-xs">
                    <button
                        type="button"
                        @click="copyPayloadToClipboard"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer flex items-center gap-1.5"
                    >
                        <CheckIcon v-if="copiedPayload" class="w-3.5 h-3.5 text-emerald-600" />
                        <ClipboardDocumentIcon v-else class="w-3.5 h-3.5 text-slate-500" />
                        <span>{{ copiedPayload ? 'Copied!' : 'Copy JSON' }}</span>
                    </button>

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
