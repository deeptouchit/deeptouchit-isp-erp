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
    PlusIcon,
    PencilSquareIcon,
    TrashIcon,
    ArrowPathIcon,
    XMarkIcon,
    CheckIcon,
    DocumentDuplicateIcon,
    ArrowDownTrayIcon,
    GlobeAltIcon,
    LockClosedIcon,
    KeyIcon,
    ServerIcon,
    ComputerDesktopIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    ipAllowlists: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    stats: {
        type: Object,
        default: () => ({
            total_allows: 0,
            active_allows: 0,
            subnet_allows: 0,
            global_allows: 0,
            temporary_allows: 0,
        }),
    },
    currentIp: {
        type: String,
        default: '127.0.0.1',
    },
    isCurrentIpAllowed: {
        type: Boolean,
        default: false,
    },
    subscriptions: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({ search: '', scope: 'all' }),
    },
})

// Search & Filter State
const search = ref(props.filters.search || '')
const activeScope = ref(props.filters.scope || 'all')
const feedbackMsg = ref('')

const allowList = computed(() => {
    if (Array.isArray(props.ipAllowlists)) return props.ipAllowlists
    if (props.ipAllowlists?.data && Array.isArray(props.ipAllowlists.data)) return props.ipAllowlists.data
    return []
})

const filteredAllows = computed(() => {
    let list = allowList.value

    if (search.value.trim()) {
        const q = search.value.toLowerCase()
        list = list.filter(a =>
            a.ip_address.toLowerCase().includes(q) ||
            (a.label || '').toLowerCase().includes(q) ||
            (a.notes || '').toLowerCase().includes(q) ||
            (a.subscription?.domain || '').toLowerCase().includes(q)
        )
    }

    if (activeScope.value !== 'all') {
        list = list.filter(a => (a.scope || 'global') === activeScope.value)
    }

    return list
})

const resetFilters = () => {
    search.value = ''
    activeScope.value = 'all'
}

// 1. ADD TRUSTED IP MODAL
const showAddModal = ref(false)
const allowForm = useForm({
    ip_address: '',
    label: '',
    scope: 'global',
    duration: 'permanent',
    notes: '',
    subscription_id: '',
})

const openAddModal = () => {
    allowForm.reset()
    allowForm.scope = 'global'
    allowForm.duration = 'permanent'
    showAddModal.value = true
}

const openAddWithCurrentIp = () => {
    allowForm.reset()
    allowForm.ip_address = props.currentIp
    allowForm.label = 'Admin Station'
    allowForm.scope = 'global'
    allowForm.duration = 'permanent'
    showAddModal.value = true
}

const allowCurrentIpDirectly = () => {
    useForm({}).post(route('admin.security.allowlist.my-ip'), {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = `Current IP (${props.currentIp}) added to permanent global allowlist.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

const submitAdd = () => {
    allowForm.post(route('admin.security.allowlist.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showAddModal.value = false
            feedbackMsg.value = `Trusted IP ${allowForm.ip_address} added to allowlist.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 2. EDIT MODAL
const showEditModal = ref(false)
const selectedAllow = ref(null)
const editForm = useForm({
    label: '',
    notes: '',
    duration: 'permanent',
})

const openEditModal = (a) => {
    selectedAllow.value = a
    editForm.label = a.label || ''
    editForm.notes = a.notes || ''
    editForm.duration = a.duration || 'permanent'
    showEditModal.value = true
}

const submitEdit = () => {
    if (!selectedAllow.value) return
    editForm.put(route('admin.security.allowlist.update', selectedAllow.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false
            feedbackMsg.value = `Allowlist rule for ${selectedAllow.value.ip_address} updated.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 3. REMOVE MODAL
const showRemoveModal = ref(false)
const allowToDelete = ref(null)
const deleteForm = useForm({})

const openRemoveModal = (a) => {
    allowToDelete.value = a
    showRemoveModal.value = true
}

const submitRemove = () => {
    if (!allowToDelete.value) return
    deleteForm.delete(route('admin.security.allowlist.destroy', allowToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showRemoveModal.value = false
            feedbackMsg.value = `IP ${allowToDelete.value.ip_address} removed from allowlist.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}
</script>

<template>
    <Head title="IP Allowlist - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Security', href: '#' },
                    { label: 'Security & Data Protection', href: route('admin.security.ssl') },
                    { label: 'IP Allowlist & Trusted Whitelist' }
                ]"
            >
                <template #actions>
                    <a
                        :href="route('admin.security.allowlist.export')"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowDownTrayIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Export CSV</span>
                    </a>

                    <button 
                        v-if="!isCurrentIpAllowed"
                        type="button" 
                        @click="allowCurrentIpDirectly"
                        class="px-2.5 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-emerald-200 shadow-2xs transition cursor-pointer"
                    >
                        <ComputerDesktopIcon class="w-3.5 h-3.5 text-emerald-600" />
                        <span>Trust My IP ({{ currentIp }})</span>
                    </button>

                    <button 
                        type="button" 
                        @click="openAddModal"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <PlusIcon class="w-3.5 h-3.5" />
                        <span>Add Trusted IP</span>
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
                    title="Total Trusted IPs"
                    :value="String(stats.total_allows || allowList.length || 0)"
                    badge="Allowlisted"
                    badgeType="success"
                    color="emerald"
                    :icon="ShieldCheckIcon"
                />

                <InfoCard
                    title="Active Whitelist"
                    :value="String(stats.active_allows || 0)"
                    badge="Enforced"
                    badgeType="success"
                    color="blue"
                    :icon="CheckIcon"
                />

                <InfoCard
                    title="Global Admin Whitelist"
                    :value="String(stats.global_allows || 0)"
                    badge="Global Scope"
                    badgeType="info"
                    color="purple"
                    :icon="ComputerDesktopIcon"
                />

                <InfoCard
                    title="Subnet CIDR Ranges"
                    :value="String(stats.subnet_allows || 0)"
                    badge="Subnets"
                    badgeType="info"
                    color="sky"
                    :icon="GlobeAltIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search trusted IP address, subnet CIDR, label, or notes..."
                @search="() => {}"
                @filter="() => {}"
                @reset="resetFilters"
            >
                <FilterSelect
                    v-model="activeScope"
                    label="Filter Scope"
                    :options="[
                        { label: 'All Whitelist Scopes', value: 'all' },
                        { label: 'Global Server Scope', value: 'global' },
                        { label: 'Account-Specific Scope', value: 'account' }
                    ]"
                    placeholder="All Scopes"
                />
            </DataTableFilter>

            <!-- 4. Standard Data Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Trusted IP / Subnet CIDR</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Identifier Label</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Scope & Tenant</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Duration</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Security Notes</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Added Date</th>
                                <th class="py-2.5 px-3 w-28">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(a, index) in filteredAllows" :key="a.id || index" class="hover:bg-emerald-50/20 transition">
                                <!-- # -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>

                                <!-- IP -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono font-bold text-emerald-700">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-[3px] bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-xs border border-emerald-100 shrink-0">
                                            <ShieldCheckIcon class="w-3.5 h-3.5" />
                                        </div>
                                        <span>{{ a.ip_address }}</span>
                                        <span v-if="a.ip_address === currentIp" class="px-1.5 py-0.2 bg-emerald-100 text-emerald-800 rounded text-[9.5px] font-bold uppercase">You</span>
                                    </div>
                                </td>

                                <!-- Label -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-bold text-slate-900">
                                    {{ a.label || 'Trusted Device' }}
                                </td>

                                <!-- Scope -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap">
                                    <span v-if="a.subscription" class="font-bold text-slate-800 font-mono text-xs block leading-tight">{{ a.subscription.domain }}</span>
                                    <span v-else class="text-blue-700 font-bold text-xs block leading-tight">Global Bypass</span>
                                </td>

                                <!-- Duration -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[10.5px] text-slate-600">
                                    {{ a.duration || 'Permanent' }}
                                </td>

                                <!-- Notes -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap text-slate-600 max-w-xs truncate" :title="a.notes">
                                    {{ a.notes || '-' }}
                                </td>

                                <!-- Added -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[10.5px] text-slate-500">
                                    {{ a.created_at ? new Date(a.created_at).toLocaleDateString() : 'Active' }}
                                </td>

                                <!-- Action -->
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1">
                                        <button 
                                            type="button" 
                                            @click="openEditModal(a)"
                                            class="px-2 py-1 bg-white hover:bg-slate-50 text-blue-700 font-bold rounded-[3px] text-xs border border-slate-200 shadow-2xs transition cursor-pointer"
                                            title="Edit Rule"
                                        >
                                            Edit ✏️
                                        </button>

                                        <RowActionDropdown>
                                            <button
                                                type="button"
                                                @click="openRemoveModal(a)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-rose-600 hover:bg-rose-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <TrashIcon class="w-3.5 h-3.5" />
                                                <span>Remove from Allowlist</span>
                                            </button>
                                        </RowActionDropdown>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!filteredAllows || filteredAllows.length === 0">
                                <td colspan="8" class="py-12 text-center text-slate-400">
                                    No IP addresses in allowlist.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 1. ADD TRUSTED IP MODAL -->
        <div v-if="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold border border-emerald-100">
                            <ShieldCheckIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Add Trusted IP to Whitelist
                        </h3>
                    </div>
                    <button @click="showAddModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitAdd" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <div class="flex items-center justify-between">
                            <label class="font-bold text-slate-700">IP Address / CIDR Range <span class="text-rose-500">*</span></label>
                            <button type="button" @click="allowForm.ip_address = currentIp" class="text-[10px] text-blue-600 font-bold hover:underline cursor-pointer">Insert My IP ({{ currentIp }})</button>
                        </div>
                        <input v-model="allowForm.ip_address" type="text" required placeholder="192.168.1.100 or 10.0.0.0/24" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-emerald-500" />
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Identifier Label <span class="text-rose-500">*</span></label>
                        <input v-model="allowForm.label" type="text" required placeholder="e.g. Office Static IP or VPN Gateway" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-emerald-500" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Scope</label>
                            <select v-model="allowForm.scope" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-emerald-500 cursor-pointer">
                                <option value="global">Global (Full Bypass)</option>
                                <option value="account">Account Specific</option>
                            </select>
                        </div>

                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Duration</label>
                            <select v-model="allowForm.duration" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-emerald-500 cursor-pointer">
                                <option value="permanent">Permanent</option>
                                <option value="24_hours">24 Hours</option>
                                <option value="7_days">7 Days</option>
                                <option value="30_days">30 Days</option>
                            </select>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Security Notes (Optional)</label>
                        <textarea v-model="allowForm.notes" rows="2" placeholder="Audit reasoning..." class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-emerald-500"></textarea>
                    </div>

                    <div class="px-4 py-2.5 -mx-4 -mb-4 mt-4 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                        <button
                            type="button"
                            @click="showAddModal = false"
                            class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="allowForm.processing"
                            class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ allowForm.processing ? 'Adding...' : 'Add to Allowlist' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 2. EDIT MODAL -->
        <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <PencilSquareIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Edit Allowlist: {{ selectedAllow?.ip_address }}
                        </h3>
                    </div>
                    <button @click="showEditModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitEdit" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Identifier Label</label>
                        <input v-model="editForm.label" type="text" required class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Notes</label>
                        <textarea v-model="editForm.notes" rows="3" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"></textarea>
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
                            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ editForm.processing ? 'Saving...' : 'Save Changes' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 3. REMOVE MODAL -->
        <div v-if="showRemoveModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-rose-50 text-rose-600 flex items-center justify-center font-bold border border-rose-100">
                            <TrashIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Remove from Allowlist
                        </h3>
                    </div>
                    <button @click="showRemoveModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-2.5 text-xs">
                    <p class="text-slate-600">
                        Are you sure you want to remove <strong class="text-slate-900 font-mono">[{{ allowToDelete?.ip_address }}]</strong> from the trusted allowlist? Standard firewall and rate-limiting policies will apply.
                    </p>
                </div>

                <div class="px-4 py-2.5 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                    <button
                        type="button"
                        @click="showRemoveModal = false"
                        class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="submitRemove"
                        :disabled="deleteForm.processing"
                        class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                    >
                        {{ deleteForm.processing ? 'Removing...' : 'Remove IP' }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
