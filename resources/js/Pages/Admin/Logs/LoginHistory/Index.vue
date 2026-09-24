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
    KeyIcon,
    ShieldCheckIcon,
    UserGroupIcon,
    GlobeAmericasIcon,
    CheckIcon,
    XCircleIcon,
    NoSymbolIcon,
    ClockIcon,
    TrashIcon,
    XMarkIcon,
    ArrowDownTrayIcon,
    ShieldExclamationIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    loginHistories: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    stats: {
        type: Object,
        default: () => ({
            total_logins: 0,
            success_count: 0,
            failed_count: 0,
            unique_ips: 0,
        }),
    },
    filters: {
        type: Object,
        default: () => ({ search: '', status: '', role: '' }),
    },
})

// Flash Feedback Toast
const feedbackMsg = ref('')

// Filter State
const search = ref(props.filters?.search || '')
const currentStatus = ref(props.filters?.status || 'all')
const currentRole = ref(props.filters?.role || 'all')

const applyFilters = () => {
    router.get(route('admin.logs.login-history'), {
        search: search.value || undefined,
        status: currentStatus.value !== 'all' ? currentStatus.value : undefined,
        role: currentRole.value !== 'all' ? currentRole.value : undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const resetFilters = () => {
    search.value = ''
    currentStatus.value = 'all'
    currentRole.value = 'all'
    applyFilters()
}

const exportCsv = () => {
    window.location.href = route('admin.logs.login-history.export')
}

// Clear Logs Modal
const showClearModal = ref(false)
const clearDays = ref(30)

const submitClearLogs = () => {
    router.post(route('admin.logs.login-history.clear'), { days: clearDays.value }, {
        preserveScroll: true,
        onSuccess: () => {
            showClearModal.value = false
            feedbackMsg.value = `Login history records purged.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

const quickBanIp = (ip) => {
    router.post(route('admin.security.blocklist.store'), { ip_address: ip, reason: 'Brute-force login failures' }, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = `IP ${ip} banned successfully.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}
</script>

<template>
    <Head title="Login History - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'System Logs', href: '#' },
                    { label: 'Unified Server Logs', href: route('admin.logs.system') },
                    { label: 'Authentication & User Login History' }
                ]"
            >
                <template #actions>
                    <button
                        type="button"
                        @click="showClearModal = true"
                        class="px-2.5 py-1.5 bg-white hover:bg-rose-50 text-rose-600 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-rose-200 shadow-2xs transition cursor-pointer"
                    >
                        <TrashIcon class="w-3.5 h-3.5" />
                        <span>Purge History</span>
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
                    title="Total Login Attempts"
                    :value="String(stats.total_logins || 0)"
                    badge="Attempts"
                    badgeType="info"
                    color="blue"
                    :icon="KeyIcon"
                />

                <InfoCard
                    title="Successful Logins"
                    :value="String(stats.success_count || 0)"
                    badge="Authenticated"
                    badgeType="success"
                    color="emerald"
                    :icon="ShieldCheckIcon"
                />

                <InfoCard
                    title="Failed Passwords"
                    :value="String(stats.failed_count || 0)"
                    :badge="stats.failed_count > 0 ? 'Failed' : 'Clean'"
                    :badgeType="stats.failed_count > 0 ? 'danger' : 'success'"
                    color="rose"
                    :icon="ShieldExclamationIcon"
                />

                <InfoCard
                    title="Unique Remote IPs"
                    :value="String(stats.unique_ips || 0)"
                    badge="IP Addresses"
                    badgeType="info"
                    color="purple"
                    :icon="GlobeAmericasIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search email, username, remote IP, or browser user agent..."
                @search="applyFilters"
                @filter="applyFilters"
                @reset="resetFilters"
            >
                <FilterSelect
                    v-model="currentStatus"
                    label="Login Status"
                    :options="[
                        { label: 'All Statuses', value: 'all' },
                        { label: 'Successful Logins', value: 'success' },
                        { label: 'Failed Attempts', value: 'failed' }
                    ]"
                    placeholder="All Statuses"
                />

                <FilterSelect
                    v-model="currentRole"
                    label="User Role"
                    :options="[
                        { label: 'All Roles', value: 'all' },
                        { label: 'Administrators', value: 'admin' },
                        { label: 'Customers', value: 'customer' }
                    ]"
                    placeholder="All Roles"
                />
            </DataTableFilter>

            <!-- 4. Standard Data Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">User Account</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Role</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Remote IP Address</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Authentication Status</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Browser User-Agent</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Timestamp</th>
                                <th class="py-2.5 px-3 w-20">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(item, index) in loginHistories.data" :key="item.id || index" class="hover:bg-blue-50/30 transition">
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-bold text-slate-900">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs border border-blue-100 shrink-0">
                                            <KeyIcon class="w-3.5 h-3.5" />
                                        </div>
                                        <div>
                                            <span class="block leading-tight">{{ item.user?.name || item.email || 'Unknown' }}</span>
                                            <span class="text-[10px] text-slate-400 font-mono block">{{ item.user?.email || item.email }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase bg-slate-100 text-slate-700 border border-slate-200 font-mono">
                                        {{ item.user?.role || item.role || 'User' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono font-bold text-blue-700">
                                    {{ item.ip_address }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="item.status === 'success' || item.is_successful ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ item.status || (item.is_successful ? 'Success' : 'Failed') }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono text-[10.5px] text-slate-500 max-w-xs truncate" :title="item.user_agent">
                                    {{ item.user_agent || 'Mozilla/5.0' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[10.5px] text-slate-500">
                                    {{ item.created_at ? new Date(item.created_at).toLocaleString() : 'Recent' }}
                                </td>
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <button 
                                        type="button" 
                                        @click="quickBanIp(item.ip_address)"
                                        class="px-2 py-1 bg-white hover:bg-rose-50 text-rose-700 font-bold rounded-[3px] text-xs border border-rose-200 shadow-2xs transition cursor-pointer"
                                        title="Ban IP"
                                    >
                                        Ban IP 🚫
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="!loginHistories.data || loginHistories.data.length === 0">
                                <td colspan="8" class="py-12 text-center text-slate-400 font-sans">
                                    No authentication login records found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- PURGE LOGS MODAL -->
        <div v-if="showClearModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-rose-50 text-rose-600 flex items-center justify-center font-bold border border-rose-100">
                            <TrashIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Purge Login History
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
