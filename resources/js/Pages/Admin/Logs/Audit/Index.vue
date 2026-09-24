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
    ClipboardDocumentListIcon,
    ShieldCheckIcon,
    Cog6ToothIcon,
    CheckIcon,
    TrashIcon,
    XMarkIcon,
    ArrowDownTrayIcon,
    DocumentDuplicateIcon,
    UserCircleIcon,
    CodeBracketIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    activityLogs: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    stats: {
        type: Object,
        default: () => ({
            total_activities: 0,
            admin_actions: 0,
            security_events: 0,
            unique_users: 1,
        }),
    },
    filters: {
        type: Object,
        default: () => ({ search: '', category: 'all' }),
    },
})

// Flash Feedback Toast
const feedbackMsg = ref('')

// Filter State
const search = ref(props.filters?.search || '')
const currentCategory = ref(props.filters?.category || 'all')

const applyFilters = () => {
    router.get(route('admin.logs.audit'), {
        search: search.value || undefined,
        category: currentCategory.value !== 'all' ? currentCategory.value : undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const selectCategory = (cat) => {
    currentCategory.value = cat
    applyFilters()
}

const resetFilters = () => {
    search.value = ''
    currentCategory.value = 'all'
    applyFilters()
}

const exportCsv = () => {
    window.location.href = route('admin.logs.audit.export')
}

// Clear Logs Modal
const showClearModal = ref(false)
const clearDays = ref(30)

const submitClearLogs = () => {
    router.post(route('admin.logs.audit.clear'), { days: clearDays.value }, {
        preserveScroll: true,
        onSuccess: () => {
            showClearModal.value = false
            feedbackMsg.value = `Activity audit records purged.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// JSON Payload Modal
const selectedLog = ref(null)
const showDiffModal = ref(false)

const openDiffModal = (log) => {
    selectedLog.value = log
    showDiffModal.value = true
}

const copyDiffJson = () => {
    navigator.clipboard.writeText(JSON.stringify(selectedLog.value?.properties || {}, null, 2))
    feedbackMsg.value = `JSON payload copied to clipboard.`
    setTimeout(() => { feedbackMsg.value = '' }, 2500)
}
</script>

<template>
    <Head title="Audit Trail Logs - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'System Logs', href: '#' },
                    { label: 'Unified Server Logs', href: route('admin.logs.system') },
                    { label: 'Administrative Audit Trail & Mutations' }
                ]"
            >
                <template #actions>
                    <button
                        type="button"
                        @click="showClearModal = true"
                        class="px-2.5 py-1.5 bg-white hover:bg-rose-50 text-rose-600 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-rose-200 shadow-2xs transition cursor-pointer"
                    >
                        <TrashIcon class="w-3.5 h-3.5" />
                        <span>Purge Audit</span>
                    </button>

                    <button
                        type="button"
                        @click="exportCsv"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowDownTrayIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Export CSV</span>
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
                    :value="String(stats.total_activities || 0)"
                    badge="Mutations"
                    badgeType="info"
                    color="blue"
                    :icon="ClipboardDocumentListIcon"
                />

                <InfoCard
                    title="Admin State Changes"
                    :value="String(stats.admin_actions || 0)"
                    badge="Admin CRUD"
                    badgeType="success"
                    color="emerald"
                    :icon="Cog6ToothIcon"
                />

                <InfoCard
                    title="Security Audits"
                    :value="String(stats.security_events || 0)"
                    badge="Guards"
                    badgeType="warning"
                    color="purple"
                    :icon="ShieldCheckIcon"
                />

                <InfoCard
                    title="Active Operators"
                    :value="String(stats.unique_users || 1)"
                    badge="Users"
                    badgeType="info"
                    color="sky"
                    :icon="UserCircleIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Category Selection Tabs -->
            <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        v-for="cat in ['all', 'system', 'security', 'billing', 'hosting']"
                        :key="cat"
                        type="button"
                        @click="selectCategory(cat)"
                        :class="currentCategory === cat ? 'bg-blue-600 text-white' : 'bg-white text-slate-700 hover:bg-slate-50 border border-slate-200'"
                        class="px-3 py-1.5 rounded-[3px] font-bold text-xs uppercase font-mono transition cursor-pointer shadow-2xs"
                    >
                        {{ cat }}
                    </button>
                </div>
            </div>

            <!-- 4. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search audit description, operator name, subject, or IP address..."
                @search="applyFilters"
                @filter="applyFilters"
                @reset="resetFilters"
            />

            <!-- 5. Standard Data Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Operator / User</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Action Event Description</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Log Scope</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Target Subject</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Remote IP</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Timestamp</th>
                                <th class="py-2.5 px-3 w-20">Payload</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(log, index) in activityLogs.data" :key="log.id || index" class="hover:bg-blue-50/30 transition">
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-bold text-slate-900">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs border border-blue-100 shrink-0">
                                            <UserCircleIcon class="w-3.5 h-3.5" />
                                        </div>
                                        <div>
                                            <span class="block leading-tight">{{ log.causer?.name || log.causer_name || 'System Auto' }}</span>
                                            <span class="text-[10px] text-slate-400 font-mono block">{{ log.causer?.email || 'daemon' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-medium text-slate-800 max-w-sm truncate" :title="log.description">
                                    {{ log.description }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase bg-slate-100 text-slate-700 border border-slate-200 font-mono">
                                        {{ log.log_name || 'system' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono text-[11px] text-blue-700 font-bold">
                                    {{ log.subject_type ? log.subject_type.split('\\').pop() + ' #' + log.subject_id : '-' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono text-[11px] text-slate-600">
                                    {{ log.properties?.ip || log.ip_address || '127.0.0.1' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[10.5px] text-slate-500">
                                    {{ log.created_at ? new Date(log.created_at).toLocaleString() : 'Recent' }}
                                </td>
                                <td class="py-2.5 px-3 text-center whitespace-nowrap font-sans">
                                    <button 
                                        type="button" 
                                        @click="openDiffModal(log)"
                                        class="px-2 py-1 bg-white hover:bg-slate-50 text-blue-700 font-bold rounded-[3px] text-xs border border-slate-200 shadow-2xs transition cursor-pointer"
                                        title="View Payload JSON"
                                    >
                                        JSON 🔍
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="!activityLogs.data || activityLogs.data.length === 0">
                                <td colspan="8" class="py-12 text-center text-slate-400 font-sans">
                                    No administrative audit trail records found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- JSON DIFF / PROPERTIES MODAL -->
        <div v-if="showDiffModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-lg overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <CodeBracketIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Activity Payload & Attributes
                        </h3>
                    </div>
                    <button @click="showDiffModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-3 text-xs">
                    <div class="flex items-center justify-between text-[11px] text-slate-500 font-mono">
                        <span>Event ID: #{{ selectedLog?.id }}</span>
                        <button type="button" @click="copyDiffJson" class="text-blue-600 hover:underline font-bold cursor-pointer">Copy JSON 📋</button>
                    </div>

                    <pre class="bg-slate-900 text-slate-100 p-3 rounded-[3px] text-[11px] font-mono overflow-x-auto max-h-72">{{ JSON.stringify(selectedLog?.properties || {}, null, 2) }}</pre>
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

        <!-- PURGE AUDIT MODAL -->
        <div v-if="showClearModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-rose-50 text-rose-600 flex items-center justify-center font-bold border border-rose-100">
                            <TrashIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Purge Audit Trail
                        </h3>
                    </div>
                    <button @click="showClearModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Purge records older than</label>
                        <select v-model="clearDays" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                            <option :value="7">Older than 7 Days</option>
                            <option :value="30">Older than 30 Days</option>
                            <option :value="60">Older than 60 Days</option>
                            <option :value="90">Older than 90 Days</option>
                        </select>
                    </div>
                </div>

                <div class="px-4 py-2.5 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                    <button
                        type="button"
                        @click="showClearModal = false"
                        class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="submitClearLogs"
                        class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer"
                    >
                        Purge Records
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
