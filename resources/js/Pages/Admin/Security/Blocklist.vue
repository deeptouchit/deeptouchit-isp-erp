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
    NoSymbolIcon,
    ShieldExclamationIcon,
    PlusIcon,
    PencilSquareIcon,
    TrashIcon,
    ArrowPathIcon,
    XMarkIcon,
    CheckIcon,
    DocumentDuplicateIcon,
    ArrowDownTrayIcon,
    ClockIcon,
    GlobeAltIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    ipBlocks: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    stats: {
        type: Object,
        default: () => ({
            total_blocks: 0,
            active_blocks: 0,
            subnet_blocks: 0,
            temporary_blocks: 0,
            permanent_blocks: 0,
        }),
    },
    subscriptions: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({ search: '', filter: 'all' }),
    },
})

// Search & Filter State
const search = ref(props.filters.search || '')
const activeFilter = ref(props.filters.filter || 'all')
const feedbackMsg = ref('')

const blockList = computed(() => {
    if (Array.isArray(props.ipBlocks)) return props.ipBlocks
    if (props.ipBlocks?.data && Array.isArray(props.ipBlocks.data)) return props.ipBlocks.data
    return []
})

const filteredBlocks = computed(() => {
    let list = blockList.value

    if (search.value.trim()) {
        const q = search.value.toLowerCase()
        list = list.filter(b =>
            b.ip_address.toLowerCase().includes(q) ||
            (b.reason || '').toLowerCase().includes(q) ||
            (b.subscription?.domain || '').toLowerCase().includes(q)
        )
    }

    if (activeFilter.value !== 'all') {
        if (activeFilter.value === 'permanent') list = list.filter(b => b.duration === 'permanent' || !b.expires_at)
        else if (activeFilter.value === 'temporary') list = list.filter(b => b.duration === 'temporary' || b.expires_at)
        else if (activeFilter.value === 'subnet') list = list.filter(b => b.ip_address.includes('/'))
    }

    return list
})

const resetFilters = () => {
    search.value = ''
    activeFilter.value = 'all'
}

// 1. BLOCK SINGLE IP MODAL
const showBlockModal = ref(false)
const blockForm = useForm({
    ip_address: '',
    reason: 'Malicious activity / Brute-force scanner',
    duration: 'permanent',
    subscription_id: '',
})

const openBlockModal = () => {
    blockForm.reset()
    blockForm.reason = 'Malicious activity / Brute-force scanner'
    blockForm.duration = 'permanent'
    showBlockModal.value = true
}

const submitBlock = () => {
    blockForm.post(route('admin.security.blocklist.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showBlockModal.value = false
            feedbackMsg.value = `IP address ${blockForm.ip_address} blocked at firewall level.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        },
    })
}

// 2. BULK BLOCK MODAL
const showBulkModal = ref(false)
const bulkForm = useForm({
    ip_list: '',
    reason: 'Bulk Threat Mitigation',
    duration: 'permanent',
})

const openBulkModal = () => {
    bulkForm.reset()
    bulkForm.reason = 'Bulk Threat Mitigation'
    bulkForm.duration = 'permanent'
    showBulkModal.value = true
}

const submitBulk = () => {
    bulkForm.post(route('admin.security.blocklist.bulk'), {
        preserveScroll: true,
        onSuccess: () => {
            showBulkModal.value = false
            feedbackMsg.value = 'Bulk IP addresses added to firewall blocklist.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        },
    })
}

// 3. EDIT BLOCK MODAL
const showEditModal = ref(false)
const selectedBlock = ref(null)
const editForm = useForm({
    reason: '',
    duration: 'permanent',
})

const openEditModal = (b) => {
    selectedBlock.value = b
    editForm.reason = b.reason || ''
    editForm.duration = b.duration || 'permanent'
    showEditModal.value = true
}

const submitEdit = () => {
    if (!selectedBlock.value) return
    editForm.put(route('admin.security.blocklist.update', selectedBlock.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false
            feedbackMsg.value = `Block rule for ${selectedBlock.value.ip_address} updated.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 4. UNBLOCK IP
const showUnblockModal = ref(false)
const blockToDelete = ref(null)
const unblockForm = useForm({})

const openUnblockModal = (b) => {
    blockToDelete.value = b
    showUnblockModal.value = true
}

const submitUnblock = () => {
    if (!blockToDelete.value) return
    unblockForm.delete(route('admin.security.blocklist.destroy', blockToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showUnblockModal.value = false
            feedbackMsg.value = `IP ${blockToDelete.value.ip_address} unblocked.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}
</script>

<template>
    <Head title="IP Blocklist - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Security', href: '#' },
                    { label: 'Security & Data Protection', href: route('admin.security.ssl') },
                    { label: 'IP Blocklist & Threat Ban' }
                ]"
            >
                <template #actions>
                    <a
                        :href="route('admin.security.blocklist.export')"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowDownTrayIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Export CSV</span>
                    </a>

                    <button 
                        type="button" 
                        @click="openBulkModal"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <PlusIcon class="w-3.5 h-3.5 text-rose-600" />
                        <span>Bulk Block IPs</span>
                    </button>

                    <button 
                        type="button" 
                        @click="openBlockModal"
                        class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <NoSymbolIcon class="w-3.5 h-3.5" />
                        <span>Block IP Address</span>
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
                    title="Total Blocked IPs"
                    :value="String(stats.total_blocks || blockList.length || 0)"
                    badge="Blocked"
                    badgeType="danger"
                    color="rose"
                    :icon="NoSymbolIcon"
                />

                <InfoCard
                    title="Active iptables Drops"
                    :value="String(stats.active_blocks || 0)"
                    badge="Enforced"
                    badgeType="success"
                    color="emerald"
                    :icon="ShieldExclamationIcon"
                />

                <InfoCard
                    title="Subnet CIDR Ranges"
                    :value="String(stats.subnet_blocks || 0)"
                    badge="CIDR Subnets"
                    badgeType="info"
                    color="purple"
                    :icon="GlobeAltIcon"
                />

                <InfoCard
                    title="Permanent Firewall Bans"
                    :value="String(stats.permanent_blocks || 0)"
                    badge="Permanent"
                    badgeType="warning"
                    color="blue"
                    :icon="ClockIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search blocked IP address, subnet CIDR, or reason..."
                @search="() => {}"
                @filter="() => {}"
                @reset="resetFilters"
            >
                <FilterSelect
                    v-model="activeFilter"
                    label="Filter Type"
                    :options="[
                        { label: 'All Blocked Entities', value: 'all' },
                        { label: 'Permanent Bans', value: 'permanent' },
                        { label: 'Temporary Bans', value: 'temporary' },
                        { label: 'Subnet CIDR Blocks', value: 'subnet' }
                    ]"
                    placeholder="All Blocks"
                />
            </DataTableFilter>

            <!-- 4. Standard Data Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Blocked IP Address / CIDR</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Reason / Trigger Payload</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Associated Scope</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Duration</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Banned At</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Expires</th>
                                <th class="py-2.5 px-3 w-28">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(b, index) in filteredBlocks" :key="b.id || index" class="hover:bg-rose-50/20 transition">
                                <!-- # -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>

                                <!-- IP -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono font-bold text-rose-700">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-[3px] bg-rose-50 text-rose-600 flex items-center justify-center font-bold text-xs border border-rose-100 shrink-0">
                                            <NoSymbolIcon class="w-3.5 h-3.5" />
                                        </div>
                                        <span>{{ b.ip_address }}</span>
                                    </div>
                                </td>

                                <!-- Reason -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap text-slate-800 max-w-sm truncate" :title="b.reason">
                                    {{ b.reason || 'Malicious traffic / Brute force' }}
                                </td>

                                <!-- Scope -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap">
                                    <span v-if="b.subscription" class="font-bold text-slate-800 font-mono text-xs block leading-tight">{{ b.subscription.domain }}</span>
                                    <span v-else class="text-slate-400 italic">Global Firewall</span>
                                </td>

                                <!-- Duration -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="b.duration === 'permanent' ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-amber-50 text-amber-700 border-amber-200'"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ b.duration || 'Permanent' }}
                                    </span>
                                </td>

                                <!-- Banned At -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[10.5px] text-slate-500">
                                    {{ b.created_at ? new Date(b.created_at).toLocaleDateString() : 'Active' }}
                                </td>

                                <!-- Expires -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[10.5px] text-slate-500">
                                    {{ b.expires_at ? new Date(b.expires_at).toLocaleDateString() : 'Never' }}
                                </td>

                                <!-- Action -->
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1">
                                        <button 
                                            type="button" 
                                            @click="openUnblockModal(b)"
                                            class="px-2 py-1 bg-white hover:bg-slate-50 text-emerald-700 font-bold rounded-[3px] text-xs border border-slate-200 shadow-2xs transition cursor-pointer"
                                            title="Unblock IP"
                                        >
                                            Unblock 🔓
                                        </button>

                                        <RowActionDropdown>
                                            <button
                                                type="button"
                                                @click="openEditModal(b)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <PencilSquareIcon class="w-3.5 h-3.5 text-slate-500" />
                                                <span>Edit Reason & Expiry</span>
                                            </button>

                                            <button
                                                type="button"
                                                @click="openUnblockModal(b)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-emerald-600 hover:bg-emerald-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <TrashIcon class="w-3.5 h-3.5" />
                                                <span>Remove & Unblock IP</span>
                                            </button>
                                        </RowActionDropdown>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!filteredBlocks || filteredBlocks.length === 0">
                                <td colspan="8" class="py-12 text-center text-slate-400">
                                    No IP addresses in blocklist.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 1. BLOCK SINGLE IP MODAL -->
        <div v-if="showBlockModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-rose-50 text-rose-600 flex items-center justify-center font-bold border border-rose-100">
                            <NoSymbolIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Block IP Address
                        </h3>
                    </div>
                    <button @click="showBlockModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitBlock" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">IP Address / CIDR Range <span class="text-rose-500">*</span></label>
                        <input v-model="blockForm.ip_address" type="text" required placeholder="192.168.1.100 or 10.0.0.0/24" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-rose-500" />
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Reason / Description</label>
                        <input v-model="blockForm.reason" type="text" placeholder="e.g. SSH Brute-force scanner" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-rose-500" />
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Ban Duration</label>
                        <select v-model="blockForm.duration" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-rose-500 cursor-pointer">
                            <option value="permanent">Permanent (Until manual removal)</option>
                            <option value="1_hour">1 Hour</option>
                            <option value="24_hours">24 Hours</option>
                            <option value="7_days">7 Days</option>
                            <option value="30_days">30 Days</option>
                        </select>
                    </div>

                    <div class="px-4 py-2.5 -mx-4 -mb-4 mt-4 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                        <button
                            type="button"
                            @click="showBlockModal = false"
                            class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="blockForm.processing"
                            class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ blockForm.processing ? 'Blocking...' : 'Block IP Address' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 2. BULK BLOCK MODAL -->
        <div v-if="showBulkModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-rose-50 text-rose-600 flex items-center justify-center font-bold border border-rose-100">
                            <NoSymbolIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Bulk IP Address Blocking
                        </h3>
                    </div>
                    <button @click="showBulkModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitBulk" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">IP List (One IP / CIDR per line) <span class="text-rose-500">*</span></label>
                        <textarea v-model="bulkForm.ip_list" rows="5" required placeholder="192.168.1.10&#10;192.168.1.11&#10;10.0.0.0/24" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-rose-500"></textarea>
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Reason</label>
                        <input v-model="bulkForm.reason" type="text" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-rose-500" />
                    </div>

                    <div class="px-4 py-2.5 -mx-4 -mb-4 mt-4 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                        <button
                            type="button"
                            @click="showBulkModal = false"
                            class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="bulkForm.processing"
                            class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ bulkForm.processing ? 'Applying...' : 'Block All IPs' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 3. EDIT BLOCK MODAL -->
        <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-rose-50 text-rose-600 flex items-center justify-center font-bold border border-rose-100">
                            <PencilSquareIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Edit Block: {{ selectedBlock?.ip_address }}
                        </h3>
                    </div>
                    <button @click="showEditModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitEdit" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Reason / Description</label>
                        <input v-model="editForm.reason" type="text" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-rose-500" />
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Ban Duration</label>
                        <select v-model="editForm.duration" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-rose-500 cursor-pointer">
                            <option value="permanent">Permanent</option>
                            <option value="1_hour">1 Hour</option>
                            <option value="24_hours">24 Hours</option>
                            <option value="7_days">7 Days</option>
                            <option value="30_days">30 Days</option>
                        </select>
                    </div>

                    <div class="px-4 py-2.5 -mx-4 -mb-4 mt-4 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                        <button
                            type="button"
                            @click="showEditModal = false"
                            class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="editForm.processing"
                            class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ editForm.processing ? 'Saving...' : 'Save Changes' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 4. UNBLOCK MODAL -->
        <div v-if="showUnblockModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold border border-emerald-100">
                            <CheckIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Unblock IP Address
                        </h3>
                    </div>
                    <button @click="showUnblockModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-2.5 text-xs">
                    <p class="text-slate-600">
                        Are you sure you want to unblock <strong class="text-slate-900 font-mono">[{{ blockToDelete?.ip_address }}]</strong>? Traffic from this IP will be permitted through the firewall immediately.
                    </p>
                </div>

                <div class="px-4 py-2.5 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                    <button
                        type="button"
                        @click="showUnblockModal = false"
                        class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="submitUnblock"
                        :disabled="unblockForm.processing"
                        class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                    >
                        {{ unblockForm.processing ? 'Unblocking...' : 'Unblock IP' }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
