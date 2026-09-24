<script setup>
import { ref, onUnmounted, computed } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import RefreshButton from '@/Components/UI/RefreshButton.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'
import DataTableFilter from '@/Components/UI/DataTableFilter.vue'
import FilterSelect from '@/Components/UI/FilterSelect.vue'

import {
    CommandLineIcon,
    TrashIcon,
    ArrowPathIcon,
    CheckIcon,
    ServerIcon,
    ClockIcon,
    DocumentTextIcon,
    EnvelopeIcon,
    ShieldCheckIcon,
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
            postfix_count: 0,
            dovecot_count: 0,
            security_count: 0,
        }),
    },
    auditLogs: {
        type: Array,
        default: () => [],
    },
    currentMode: {
        type: String,
        default: 'daemon',
    },
    currentComponent: {
        type: String,
        default: 'all',
    },
    search: {
        type: String,
        default: '',
    },
    limit: {
        type: Number,
        default: 150,
    },
})

// Toast Feedback
const feedbackMsg = ref('')

// State
const activeMode = ref(props.currentMode || 'daemon') // 'daemon' | 'audit'
const selectedComponent = ref(props.currentComponent || 'all')
const searchQuery = ref(props.search || '')
const selectedLimit = ref(props.limit || 150)
const isAutoRefresh = ref(false)
let refreshTimer = null

const toggleAutoRefresh = () => {
    isAutoRefresh.value = !isAutoRefresh.value
    if (isAutoRefresh.value) {
        refreshTimer = setInterval(() => {
            fetchLogs(false)
        }, 5000)
    } else {
        clearInterval(refreshTimer)
    }
}

onUnmounted(() => {
    if (refreshTimer) clearInterval(refreshTimer)
})

const fetchLogs = (preserveState = true) => {
    router.get(
        route('admin.email.logs'),
        {
            mode: activeMode.value,
            component: selectedComponent.value,
            search: searchQuery.value,
            limit: selectedLimit.value,
        },
        {
            preserveState: preserveState,
            preserveScroll: true,
            only: ['logs', 'stats', 'auditLogs'],
        }
    )
}

const resetFilters = () => {
    searchQuery.value = ''
    selectedComponent.value = 'all'
    selectedLimit.value = 150
    fetchLogs(true)
}

// Clear Logs
const clearLogs = () => {
    if (confirm('Truncate and clear the active email log file buffer?')) {
        useForm({}).post(route('admin.email.logs.clear'), {
            preserveScroll: true,
            onSuccess: () => {
                feedbackMsg.value = 'Email log buffer truncated.'
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    }
}
</script>

<template>
    <Head title="Mail Logs & Queue Telemetry - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Services', href: '#' },
                    { label: 'Email Services', href: route('admin.email.domains') },
                    { label: 'Mail Logs & Queue Telemetry' }
                ]"
            >
                <template #actions>
                    <Link
                        :href="route('admin.email.domains')"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowLeftIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Mail Domains</span>
                    </Link>

                    <button
                        type="button"
                        @click="toggleAutoRefresh"
                        :class="isAutoRefresh ? 'bg-emerald-50 text-emerald-700 border-emerald-300' : 'bg-white text-slate-700 border-slate-200'"
                        class="px-2.5 py-1.5 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border shadow-2xs transition cursor-pointer"
                    >
                        <ArrowPathIcon class="w-3.5 h-3.5" :class="{ 'animate-spin': isAutoRefresh }" />
                        <span>{{ isAutoRefresh ? 'Auto 5s ON' : 'Live Stream' }}</span>
                    </button>

                    <button
                        type="button"
                        @click="clearLogs"
                        class="px-2.5 py-1.5 bg-white hover:bg-rose-50 text-rose-600 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-rose-200 shadow-2xs transition cursor-pointer"
                    >
                        <TrashIcon class="w-3.5 h-3.5" />
                        <span>Clear Log Buffer</span>
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
                    :value="String(stats.total_lines || logs.length || 0)"
                    badge="Lines"
                    badgeType="info"
                    color="blue"
                    :icon="DocumentTextIcon"
                />

                <InfoCard
                    title="Postfix MTA Activity"
                    :value="String(stats.postfix_count || 0)"
                    badge="MTA Relay"
                    badgeType="success"
                    color="emerald"
                    :icon="EnvelopeIcon"
                />

                <InfoCard
                    title="Dovecot IMAP / POP3"
                    :value="String(stats.dovecot_count || 0)"
                    badge="Mailbox"
                    badgeType="info"
                    color="purple"
                    :icon="ServerIcon"
                />

                <InfoCard
                    title="Security / Auth Events"
                    :value="String(stats.security_count || 0)"
                    :badge="stats.security_count > 0 ? 'Noticed' : 'Clean'"
                    badgeType="warning"
                    color="sky"
                    :icon="ShieldCheckIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="searchQuery"
                searchPlaceholder="Search message ID, client IP, sender, recipient, or queue..."
                @search="() => fetchLogs(true)"
                @filter="() => fetchLogs(true)"
                @reset="resetFilters"
            >
                <FilterSelect
                    v-model="selectedComponent"
                    label="Service Subsystem"
                    :options="[
                        { label: 'All Subsystems', value: 'all' },
                        { label: 'Postfix (MTA)', value: 'postfix' },
                        { label: 'Dovecot (IMAP/POP3)', value: 'dovecot' },
                        { label: 'Security & Auth', value: 'security' }
                    ]"
                    placeholder="All Services"
                    @change="fetchLogs(true)"
                />

                <FilterSelect
                    v-model="selectedLimit"
                    label="Line Buffer"
                    :options="[
                        { label: '50 Lines', value: 50 },
                        { label: '100 Lines', value: 100 },
                        { label: '150 Lines', value: 150 },
                        { label: '300 Lines', value: 300 }
                    ]"
                    placeholder="Buffer Size"
                    @change="fetchLogs(true)"
                />
            </DataTableFilter>

            <!-- 4. Tabbed Table Container -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <!-- Tab Switchers -->
                <div class="flex items-center border-b border-slate-200 bg-slate-50/70 px-3">
                    <button
                        type="button"
                        @click="activeMode = 'daemon'; fetchLogs(true)"
                        :class="activeMode === 'daemon' ? 'border-blue-600 text-blue-600 font-bold bg-white' : 'border-transparent text-slate-600 hover:text-slate-900'"
                        class="py-2.5 px-3 text-xs border-b-2 transition cursor-pointer flex items-center gap-1.5"
                    >
                        <CommandLineIcon class="w-3.5 h-3.5" />
                        <span>Daemon Log Stream ({{ logs.length }})</span>
                    </button>

                    <button
                        type="button"
                        @click="activeMode = 'audit'; fetchLogs(true)"
                        :class="activeMode === 'audit' ? 'border-blue-600 text-blue-600 font-bold bg-white' : 'border-transparent text-slate-600 hover:text-slate-900'"
                        class="py-2.5 px-3 text-xs border-b-2 transition cursor-pointer flex items-center gap-1.5"
                    >
                        <ClockIcon class="w-3.5 h-3.5" />
                        <span>Email Mutation Audits ({{ auditLogs?.length || 0 }})</span>
                    </button>
                </div>

                <!-- Tab 1: Daemon Log Table -->
                <div v-if="activeMode === 'daemon'" class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Timestamp</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Subsystem</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Process / PID</th>
                                <th class="py-2.5 px-3 text-left">Message Diagnostic Payload</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(line, index) in logs" :key="index" class="hover:bg-blue-50/30 transition">
                                <!-- # -->
                                <td class="py-2 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>

                                <!-- Timestamp -->
                                <td class="py-2 px-3 border-r border-slate-100 font-mono text-slate-500 text-[10.5px] whitespace-nowrap text-center">
                                    {{ line.timestamp || '-' }}
                                </td>

                                <!-- Component -->
                                <td class="py-2 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="[
                                            line.component === 'postfix' ? 'bg-blue-50 text-blue-700 border-blue-200' :
                                            line.component === 'dovecot' ? 'bg-purple-50 text-purple-700 border-purple-200' :
                                            'bg-slate-100 text-slate-600 border-slate-200'
                                        ]"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ line.component || 'SYSTEM' }}
                                    </span>
                                </td>

                                <!-- Process -->
                                <td class="py-2 px-3 border-r border-slate-100 font-mono text-slate-500 text-[10.5px] whitespace-nowrap text-center">
                                    {{ line.process || 'daemon' }}
                                </td>

                                <!-- Message -->
                                <td class="py-2 px-3 text-left font-mono text-[11px] text-slate-800 whitespace-normal">
                                    {{ line.message }}
                                </td>
                            </tr>

                            <tr v-if="!logs || logs.length === 0">
                                <td colspan="5" class="py-12 text-center text-slate-400">
                                    No email log entries found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Tab 2: Audit Logs Table -->
                <div v-if="activeMode === 'audit'" class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Timestamp</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Administrator</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Action Code</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Target Entity</th>
                                <th class="py-2.5 px-3 text-left">Details</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(log, idx) in auditLogs" :key="log.id || idx" class="hover:bg-blue-50/30 transition">
                                <td class="py-2 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">{{ idx + 1 }}</td>
                                <td class="py-2 px-3 border-r border-slate-100 font-mono text-slate-500 text-[10.5px] whitespace-nowrap">
                                    {{ new Date(log.created_at).toLocaleString() }}
                                </td>
                                <td class="py-2 px-3 border-r border-slate-100 text-left whitespace-nowrap font-bold text-slate-900">
                                    {{ log.user?.first_name || 'System Admin' }} (@{{ log.user?.username || 'admin' }})
                                </td>
                                <td class="py-2 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase bg-blue-50 text-blue-700 border border-blue-200 font-mono">
                                        {{ log.action }}
                                    </span>
                                </td>
                                <td class="py-2 px-3 border-r border-slate-100 text-left font-mono text-[11px] text-slate-700">
                                    {{ log.auditable_type?.split('\\').pop() }} #{{ log.auditable_id }}
                                </td>
                                <td class="py-2 px-3 text-left max-w-sm truncate text-slate-600 font-mono text-[10.5px]">
                                    {{ JSON.stringify(log.new_values) }}
                                </td>
                            </tr>

                            <tr v-if="!auditLogs || auditLogs.length === 0">
                                <td colspan="6" class="py-12 text-center text-slate-400">
                                    No email mutation audit entries found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
