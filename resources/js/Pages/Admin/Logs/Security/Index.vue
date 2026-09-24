<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import RefreshButton from '@/Components/UI/RefreshButton.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'
import DataTableFilter from '@/Components/UI/DataTableFilter.vue'
import FilterSelect from '@/Components/UI/FilterSelect.vue'

import {
    ShieldCheckIcon,
    ShieldExclamationIcon,
    FireIcon,
    LockClosedIcon,
    CheckIcon,
    ArrowDownTrayIcon,
    DocumentDuplicateIcon,
    NoSymbolIcon,
    XMarkIcon,
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
            ban_count: 0,
            block_count: 0,
            auth_fails: 0,
            firewall_health: { fail2ban: 'active', ufw: 'active', status: 'Protected' },
            active_source: 'fail2ban',
        }),
    },
    filters: {
        type: Object,
        default: () => ({ source: 'fail2ban', lines: 100, search: '', action_type: 'all' }),
    },
})

// Flash Feedback Toast
const feedbackMsg = ref('')

// Filter State
const search = ref(props.filters?.search || '')
const currentSource = ref(props.filters?.source || 'fail2ban')
const currentLines = ref(props.filters?.lines || 100)
const currentAction = ref(props.filters?.action_type || 'all')

const applyFilters = () => {
    router.get(route('admin.logs.security'), {
        source: currentSource.value,
        lines: currentLines.value,
        search: search.value || undefined,
        action_type: currentAction.value !== 'all' ? currentAction.value : undefined,
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
    currentAction.value = 'all'
    currentLines.value = 100
    applyFilters()
}

const downloadLog = () => {
    window.location.href = route('admin.logs.security.download', { source: currentSource.value })
}

// Quick Ban IP Modal
const showBanModal = ref(false)
const banForm = useForm({
    ip_address: '',
    reason: 'Malicious Brute-Force Activity',
})

const submitBanForm = () => {
    banForm.post(route('admin.security.blocklist.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showBanModal.value = false
            feedbackMsg.value = `IP ${banForm.ip_address} has been banned in firewall.`
            banForm.reset()
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

const quickBanIp = (ip) => {
    banForm.ip_address = ip
    showBanModal.value = true
}

const copyLine = (log) => {
    navigator.clipboard.writeText(log.raw || log.message)
    feedbackMsg.value = `Log line copied to clipboard.`
    setTimeout(() => { feedbackMsg.value = '' }, 2500)
}
</script>

<template>
    <Head title="Security & Intrusion Logs - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'System Logs', href: '#' },
                    { label: 'Unified Server Logs', href: route('admin.logs.system') },
                    { label: 'Firewall, Intrusion & Auth Logs' }
                ]"
            >
                <template #actions>
                    <button
                        type="button"
                        @click="showBanModal = true"
                        class="px-2.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-[3px] text-xs flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <NoSymbolIcon class="w-3.5 h-3.5" />
                        <span>Ban Offender IP</span>
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
                    :badge="currentSource"
                    badgeType="info"
                    color="blue"
                    :icon="ShieldCheckIcon"
                />

                <InfoCard
                    title="Fail2ban IP Bans"
                    :value="String(stats.ban_count || 0)"
                    :badge="stats.ban_count > 0 ? 'Bans Active' : 'Zero'"
                    :badgeType="stats.ban_count > 0 ? 'danger' : 'success'"
                    color="rose"
                    :icon="ShieldExclamationIcon"
                />

                <InfoCard
                    title="Firewall Drops (UFW)"
                    :value="String(stats.block_count || 0)"
                    badge="Blocked"
                    badgeType="warning"
                    color="purple"
                    :icon="FireIcon"
                />

                <InfoCard
                    title="Failed Auth Events"
                    :value="String(stats.auth_fails || 0)"
                    badge="SSH/Auth"
                    badgeType="warning"
                    color="sky"
                    :icon="LockClosedIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Source Selection Tabs -->
            <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        v-for="src in ['fail2ban', 'auth', 'ufw', 'audit']"
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
                searchPlaceholder="Search attacker IP address, SSH user, jail name, or payload..."
                @search="applyFilters"
                @filter="applyFilters"
                @reset="resetFilters"
            >
                <FilterSelect
                    v-model="currentAction"
                    label="Security Action"
                    :options="[
                        { label: 'All Actions', value: 'all' },
                        { label: 'Ban Events', value: 'ban' },
                        { label: 'Unban Events', value: 'unban' },
                        { label: 'Failed Password', value: 'failed' },
                        { label: 'Firewall Block', value: 'block' }
                    ]"
                    placeholder="All Actions"
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
                                <th class="py-2.5 px-3 border-r border-slate-200 w-28">Subsystem / Jail</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 w-24">Action</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Security Event Payload</th>
                                <th class="py-2.5 px-3 w-28">Action</th>
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
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-bold text-slate-900">
                                    {{ log.jail || log.facility || 'sshd' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-sans">
                                    <span 
                                        :class="[
                                            (log.action === 'Ban' || log.action === 'block') ? 'bg-rose-50 text-rose-700 border-rose-200' :
                                            (log.action === 'Unban' || log.action === 'allow') ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                                            'bg-amber-50 text-amber-700 border-amber-200'
                                        ]"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ log.action || 'Event' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap text-slate-800 max-w-xl truncate" :title="log.raw || log.message">
                                    {{ log.message || log.raw }}
                                </td>
                                <td class="py-2.5 px-3 text-center whitespace-nowrap font-sans">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button 
                                            v-if="log.ip"
                                            type="button" 
                                            @click="quickBanIp(log.ip)"
                                            class="px-1.5 py-0.5 bg-white hover:bg-rose-50 text-rose-700 font-bold rounded-[2px] text-[10.5px] border border-rose-200 shadow-2xs transition cursor-pointer"
                                            title="Ban IP"
                                        >
                                            Ban IP
                                        </button>
                                        <button 
                                            type="button" 
                                            @click="copyLine(log)"
                                            class="p-1 hover:bg-slate-100 text-slate-500 hover:text-slate-900 rounded-[2px] cursor-pointer"
                                            title="Copy Line"
                                        >
                                            <DocumentDuplicateIcon class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!logs || logs.length === 0">
                                <td colspan="6" class="py-12 text-center text-slate-400 font-sans">
                                    No security log events found in {{ currentSource }}.log.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- QUICK BAN IP MODAL -->
        <div v-if="showBanModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-rose-50 text-rose-600 flex items-center justify-center font-bold border border-rose-100">
                            <NoSymbolIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Block Attacker IP
                        </h3>
                    </div>
                    <button @click="showBanModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitBanForm" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">IP Address / CIDR <span class="text-rose-500">*</span></label>
                        <input v-model="banForm.ip_address" type="text" required placeholder="192.168.1.100" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Reason / Incident Note</label>
                        <input v-model="banForm.reason" type="text" placeholder="Brute-force attacker" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="px-4 py-2.5 -mx-4 -mb-4 mt-4 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                        <button
                            type="button"
                            @click="showBanModal = false"
                            class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="banForm.processing"
                            class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ banForm.processing ? 'Blocking...' : 'Block IP' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
