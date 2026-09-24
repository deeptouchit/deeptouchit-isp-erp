<script setup>
import { ref, computed } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import RefreshButton from '@/Components/UI/RefreshButton.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'
import DataTableFilter from '@/Components/UI/DataTableFilter.vue'
import RowActionDropdown from '@/Components/UI/RowActionDropdown.vue'

import {
    ShieldCheckIcon,
    ShieldExclamationIcon,
    PlusIcon,
    TrashIcon,
    ArrowPathIcon,
    XMarkIcon,
    CheckIcon,
    DocumentDuplicateIcon,
    BoltIcon,
    ServerIcon,
    LockClosedIcon,
    NoSymbolIcon,
    ArrowUpCircleIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    overview: {
        type: Object,
        default: () => ({ is_active: true, status_text: 'Active', jails: [] }),
    },
    jails: {
        type: Array,
        default: () => [],
    },
    bannedIps: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            is_active: true,
            active_jails: 0,
            currently_banned: 0,
            total_banned: 0,
            total_failed: 0,
        }),
    },
    filters: {
        type: Object,
        default: () => ({ search: '' }),
    },
})

// Search & Feedback State
const search = ref(props.filters.search || '')
const feedbackMsg = ref('')

const filteredBanned = computed(() => {
    let list = props.bannedIps || []
    if (search.value.trim()) {
        const q = search.value.toLowerCase()
        list = list.filter(item =>
            item.ip.toLowerCase().includes(q) ||
            item.jail.toLowerCase().includes(q)
        )
    }
    return list
})

// 1. MANUAL BAN IP MODAL
const showBanModal = ref(false)
const banForm = useForm({
    jail: props.jails[0]?.name || 'sshd',
    ip: '',
})

const openBanModal = () => {
    banForm.reset()
    banForm.jail = props.jails[0]?.name || 'sshd'
    showBanModal.value = true
}

const submitBan = () => {
    banForm.post(route('admin.security.fail2ban.ban'), {
        preserveScroll: true,
        onSuccess: () => {
            showBanModal.value = false
            feedbackMsg.value = `IP address ${banForm.ip} banned in '${banForm.jail}' jail.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        },
    })
}

// 2. UNBAN IP
const unbanIp = (item) => {
    router.post(route('admin.security.fail2ban.unban'), {
        jail: item.jail,
        ip: item.ip,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = `IP ${item.ip} unbanned from '${item.jail}'.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 3. ELEVATE TO PERMANENT BLOCKLIST
const elevateToPermanent = (item) => {
    router.post(route('admin.security.fail2ban.elevate'), {
        ip: item.ip,
        reason: `Elevated from Fail2ban ${item.jail} jail`,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = `IP ${item.ip} elevated to permanent firewall blocklist.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 4. WHITELIST IP
const whitelistIp = (item) => {
    router.post(route('admin.security.fail2ban.whitelist'), {
        ip: item.ip,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = `IP ${item.ip} unbanned and added to trusted allowlist.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 5. UNBAN ALL
const isUnbanningAll = ref(false)
const unbanAll = () => {
    if (confirm('Unban all currently jailed IP addresses across all active jails?')) {
        isUnbanningAll.value = true
        router.post(route('admin.security.fail2ban.unban-all'), {}, {
            preserveScroll: true,
            onSuccess: () => {
                feedbackMsg.value = 'All banned IPs have been unbanned.'
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            },
            onFinish: () => {
                isUnbanningAll.value = false
            }
        })
    }
}

// 6. RESTART DAEMON
const isRestarting = ref(false)
const restartDaemon = () => {
    isRestarting.value = true
    router.post(route('admin.security.fail2ban.restart'), {}, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = 'Fail2ban daemon service restarted.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        },
        onFinish: () => {
            isRestarting.value = false
        }
    })
}
</script>

<template>
    <Head title="Fail2ban Intrusion Defense - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Security', href: '#' },
                    { label: 'Security & Data Protection', href: route('admin.security.ssl') },
                    { label: 'Fail2ban Intrusion Defense' }
                ]"
            >
                <template #actions>
                    <button
                        type="button"
                        @click="restartDaemon"
                        :disabled="isRestarting"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer disabled:opacity-50"
                    >
                        <ArrowPathIcon class="w-3.5 h-3.5 text-slate-500" :class="{ 'animate-spin': isRestarting }" />
                        <span>Restart Daemon</span>
                    </button>

                    <button
                        type="button"
                        @click="unbanAll"
                        :disabled="isUnbanningAll || bannedIps.length === 0"
                        class="px-2.5 py-1.5 bg-white hover:bg-emerald-50 text-emerald-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-emerald-200 shadow-2xs transition cursor-pointer disabled:opacity-50"
                    >
                        <CheckIcon class="w-3.5 h-3.5" />
                        <span>Unban All ({{ bannedIps.length }})</span>
                    </button>

                    <button 
                        type="button" 
                        @click="openBanModal"
                        class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <PlusIcon class="w-3.5 h-3.5" />
                        <span>Manual Ban IP</span>
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
                    title="Daemon Engine"
                    :value="overview.status_text || 'Active & Watching'"
                    :badge="overview.is_active ? 'Online' : 'Stopped'"
                    :badgeType="overview.is_active ? 'success' : 'danger'"
                    color="emerald"
                    :icon="ShieldCheckIcon"
                />

                <InfoCard
                    title="Active Jails"
                    :value="String(stats.active_jails || jails.length || 0)"
                    badge="Monitored"
                    badgeType="info"
                    color="blue"
                    :icon="LockClosedIcon"
                />

                <InfoCard
                    title="Currently Jailed"
                    :value="String(stats.currently_banned || bannedIps.length || 0)"
                    :badge="bannedIps.length > 0 ? 'Banned' : 'Clean'"
                    :badgeType="bannedIps.length > 0 ? 'danger' : 'success'"
                    color="rose"
                    :icon="ShieldExclamationIcon"
                />

                <InfoCard
                    title="Failed Attempt Probes"
                    :value="String(stats.total_failed || 0)"
                    badge="Mitigated"
                    badgeType="info"
                    color="purple"
                    :icon="ServerIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Active Monitored Jails Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-2.5">
                <div 
                    v-for="(j, ji) in jails" 
                    :key="ji" 
                    class="bg-white rounded-[4px] border border-[#E2E8F0] p-3 shadow-2xs space-y-1"
                >
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-slate-900 font-mono text-xs">{{ j.name }}</span>
                        <span 
                            :class="j.currently_banned > 0 ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-slate-100 text-slate-500 border-slate-200'"
                            class="px-1.5 py-0.2 rounded text-[10px] font-bold border font-mono"
                        >
                            {{ j.currently_banned }} Banned
                        </span>
                    </div>
                    <div class="flex items-center justify-between text-[10.5px] text-slate-400 font-mono">
                        <span>Max Retry: {{ j.max_retry || 5 }}</span>
                        <span>Ban: {{ j.ban_time ? (j.ban_time / 60) + 'm' : '10m' }}</span>
                    </div>
                </div>
            </div>

            <!-- 4. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search banned IP address or jail name..."
                @search="() => {}"
                @filter="() => {}"
                @reset="search = ''"
            />

            <!-- 5. Standard Data Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Banned Attacker IP</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Triggered Jail Subsystem</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Protocol Filter</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Banned At</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Release / Expiry</th>
                                <th class="py-2.5 px-3 w-28">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(item, index) in filteredBanned" :key="index" class="hover:bg-rose-50/20 transition">
                                <!-- # -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>

                                <!-- IP -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono font-bold text-rose-700">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-[3px] bg-rose-50 text-rose-600 flex items-center justify-center font-bold text-xs border border-rose-100 shrink-0">
                                            <ShieldExclamationIcon class="w-3.5 h-3.5" />
                                        </div>
                                        <span>{{ item.ip }}</span>
                                    </div>
                                </td>

                                <!-- Jail -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono font-bold text-slate-900">
                                    {{ item.jail }}
                                </td>

                                <!-- Filter -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[10.5px] text-slate-500">
                                    iptables-multiport
                                </td>

                                <!-- Banned At -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[10.5px] text-slate-500">
                                    {{ item.banned_at || 'Just Now' }}
                                </td>

                                <!-- Expiry -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[10.5px] text-slate-500">
                                    {{ item.expires_in || 'Active Jail Ban' }}
                                </td>

                                <!-- Action -->
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1">
                                        <button 
                                            type="button" 
                                            @click="unbanIp(item)"
                                            class="px-2 py-1 bg-white hover:bg-slate-50 text-emerald-700 font-bold rounded-[3px] text-xs border border-slate-200 shadow-2xs transition cursor-pointer"
                                            title="Unban IP"
                                        >
                                            Unban 🔓
                                        </button>

                                        <RowActionDropdown>
                                            <button
                                                type="button"
                                                @click="elevateToPermanent(item)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-rose-700 hover:bg-rose-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <NoSymbolIcon class="w-3.5 h-3.5 text-rose-500" />
                                                <span>Elevate to Permanent Block</span>
                                            </button>

                                            <button
                                                type="button"
                                                @click="whitelistIp(item)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-emerald-700 hover:bg-emerald-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <ShieldCheckIcon class="w-3.5 h-3.5 text-emerald-500" />
                                                <span>Unban & Add to Allowlist</span>
                                            </button>
                                        </RowActionDropdown>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!filteredBanned || filteredBanned.length === 0">
                                <td colspan="7" class="py-12 text-center text-slate-400">
                                    No IP addresses currently banned in Fail2ban jails.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- MANUAL BAN IP MODAL -->
        <div v-if="showBanModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-rose-50 text-rose-600 flex items-center justify-center font-bold border border-rose-100">
                            <PlusIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Manual Jail Ban
                        </h3>
                    </div>
                    <button @click="showBanModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitBan" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Target Fail2ban Jail <span class="text-rose-500">*</span></label>
                        <select v-model="banForm.jail" required class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-rose-500 cursor-pointer font-mono font-bold">
                            <option v-for="j in jails" :key="j.name" :value="j.name">
                                {{ j.name }}
                            </option>
                        </select>
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">IP Address <span class="text-rose-500">*</span></label>
                        <input v-model="banForm.ip" type="text" required placeholder="192.168.1.100" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-rose-500" />
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
                            {{ banForm.processing ? 'Banning...' : 'Ban IP in Jail' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
