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
import RowActionDropdown from '@/Components/UI/RowActionDropdown.vue'

import {
    PlusIcon,
    ArrowPathIcon,
    CheckIcon,
    XMarkIcon,
    KeyIcon,
    CommandLineIcon,
    ShieldCheckIcon,
    NoSymbolIcon,
    ClockIcon,
    TrashIcon,
    DocumentDuplicateIcon,
    ExclamationTriangleIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    apiKeys: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    stats: {
        type: Object,
        default: () => ({
            total_keys: 0,
            active_keys: 0,
            revoked_count: 0,
            ip_locked_count: 0,
            total_requests: 0,
        }),
    },
    filters: {
        type: Object,
        default: () => ({ search: '', status: 'all' }),
    },
    newKeyToken: {
        type: String,
        default: null,
    },
    newKeyName: {
        type: String,
        default: null,
    },
})

// Flash Feedback Toast
const feedbackMsg = ref('')

// Filter State
const search = ref(props.filters?.search || '')
const currentStatus = ref(props.filters?.status || 'all')

const applyFilters = () => {
    router.get(route('admin.api.keys'), {
        search: search.value || undefined,
        status: currentStatus.value !== 'all' ? currentStatus.value : undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const selectStatus = (status) => {
    currentStatus.value = status
    applyFilters()
}

const resetFilters = () => {
    search.value = ''
    currentStatus.value = 'all'
    applyFilters()
}

// Available Scopes
const availableAbilities = [
    { key: '*', label: 'Full Access (*)' },
    { key: 'servers:manage', label: 'Manage Servers & Nodes' },
    { key: 'accounts:manage', label: 'Provision / Suspend Accounts' },
    { key: 'dns:write', label: 'Modify DNS Zones' },
    { key: 'billing:read', label: 'Read Invoices & Transactions' },
    { key: 'security:manage', label: 'Manage Firewall & IP Bans' },
]

// One-time Token Reveal
const revealedToken = ref(props.newKeyToken)
const copyToken = (token) => {
    navigator.clipboard.writeText(token)
    feedbackMsg.value = 'API Key copied to clipboard!'
    setTimeout(() => { feedbackMsg.value = '' }, 3000)
}

// 1. GENERATE API KEY MODAL
const showModal = ref(false)
const keyForm = useForm({
    name: '',
    abilities: ['*'],
    allowed_ips: '',
    expires_in_days: 90,
})

const openCreateModal = () => {
    keyForm.reset()
    keyForm.abilities = ['*']
    keyForm.expires_in_days = 90
    showModal.value = true
}

const submitKey = () => {
    keyForm.post(route('admin.api.keys.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showModal.value = false
            feedbackMsg.value = 'New API Key generated.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 2. TOGGLE / REVOKE KEY
const toggleKey = (key) => {
    router.post(route('admin.api.keys.toggle', key.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = 'API Key status updated.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 3. DELETE MODAL
const showDeleteModal = ref(false)
const keyToDelete = ref(null)
const deleteForm = useForm({})

const openDeleteModal = (key) => {
    keyToDelete.value = key
    showDeleteModal.value = true
}

const submitDelete = () => {
    if (!keyToDelete.value) return
    deleteForm.delete(route('admin.api.keys.destroy', keyToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false
            feedbackMsg.value = 'API Key permanently deleted.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}
</script>

<template>
    <Head title="REST API Keys - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'API', href: route('admin.api.keys') },
                    { label: 'API Keys' }
                ]"
            >
                <template #actions>
                    <Link
                        :href="route('admin.api.webhooks')"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <CommandLineIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Webhooks</span>
                    </Link>

                    <button 
                        type="button" 
                        @click="openCreateModal"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <PlusIcon class="w-3.5 h-3.5" />
                        <span>Generate API Key</span>
                    </button>

                    <RefreshButton />
                </template>
            </PageHeader>

            <!-- One-Time Revealed Token Banner -->
            <div v-if="revealedToken" class="bg-amber-50 border border-amber-300 rounded-[3px] p-3 text-xs shadow-xs space-y-2">
                <div class="flex items-center justify-between font-bold text-amber-900">
                    <div class="flex items-center gap-1.5">
                        <ExclamationTriangleIcon class="w-4 h-4 text-amber-600" />
                        <span>Save Your New API Key Token (Will Not Be Shown Again)</span>
                    </div>
                    <button @click="revealedToken = null" class="text-amber-700 hover:text-amber-900 cursor-pointer">✕</button>
                </div>
                <div class="flex items-center gap-2">
                    <code class="flex-1 bg-white px-2.5 py-1.5 rounded-[2px] border border-amber-200 font-mono text-slate-900 text-xs select-all">{{ revealedToken }}</code>
                    <button @click="copyToken(revealedToken)" class="px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-[2px] cursor-pointer shrink-0">
                        Copy Token
                    </button>
                </div>
            </div>

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
                    title="Configured API Keys"
                    :value="String(stats.total_keys || apiKeys.data?.length || 0)"
                    badge="Keys"
                    badgeType="info"
                    color="blue"
                    :icon="KeyIcon"
                />

                <InfoCard
                    title="Active Live Tokens"
                    :value="String(stats.active_keys || apiKeys.data?.filter(k => !k.is_revoked).length || 0)"
                    badge="Active"
                    badgeType="success"
                    color="emerald"
                    :icon="ShieldCheckIcon"
                />

                <InfoCard
                    title="IP Restricted Keys"
                    :value="String(stats.ip_locked_count || 0)"
                    badge="Locked"
                    badgeType="info"
                    color="purple"
                    :icon="CommandLineIcon"
                />

                <InfoCard
                    title="Total API Calls"
                    :value="String(stats.total_requests || 0)"
                    badge="Requests"
                    badgeType="info"
                    color="sky"
                    :icon="ClockIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Status Tabs -->
            <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        v-for="st in ['all', 'active', 'revoked']"
                        :key="st"
                        type="button"
                        @click="selectStatus(st)"
                        :class="currentStatus === st ? 'bg-blue-600 text-white' : 'bg-white text-slate-700 hover:bg-slate-50 border border-slate-200'"
                        class="px-3 py-1.5 rounded-[3px] font-bold text-xs uppercase font-mono transition cursor-pointer shadow-2xs"
                    >
                        {{ st }}
                    </button>
                </div>
            </div>

            <!-- 4. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search key label name, token prefix, or user..."
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
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Key Identifier / Name</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Token Prefix</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Scopes</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">IP Restrictions</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Last Used</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Status</th>
                                <th class="py-2.5 px-3 w-14 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(key, index) in apiKeys.data" :key="key.id || index" class="hover:bg-blue-50/30 transition">
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-bold text-slate-900">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs border border-blue-100 shrink-0">
                                            <KeyIcon class="w-3.5 h-3.5" />
                                        </div>
                                        <div>
                                            <span class="block leading-tight font-bold">{{ key.name }}</span>
                                            <span class="text-[10px] text-slate-400 font-mono block">{{ key.user?.email || 'System Token' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono text-[11px] text-slate-600">
                                    <code class="bg-slate-100 px-1.5 py-0.5 rounded-[2px]">{{ key.token_prefix || 'dth_live_...' }}</code>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase bg-slate-100 text-slate-700 border border-slate-200 font-mono">
                                        {{ key.abilities?.includes('*') ? 'All Scopes (*)' : `${key.abilities?.length || 1} Scopes` }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono text-[11px] text-slate-600">
                                    {{ key.allowed_ips || 'Any IP (0.0.0.0/0)' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[10.5px] text-slate-500">
                                    {{ key.last_used_at ? new Date(key.last_used_at).toLocaleDateString() : 'Never' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="!key.is_revoked ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ !key.is_revoked ? 'Active' : 'Revoked' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <RowActionDropdown>
                                        <button
                                            type="button"
                                            @click="toggleKey(key)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <NoSymbolIcon class="w-3.5 h-3.5 text-slate-500" />
                                            <span>{{ key.is_revoked ? 'Activate Key' : 'Revoke Token' }}</span>
                                        </button>

                                        <button
                                            type="button"
                                            @click="openDeleteModal(key)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-rose-600 hover:bg-rose-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <TrashIcon class="w-3.5 h-3.5" />
                                            <span>Delete Key</span>
                                        </button>
                                    </RowActionDropdown>
                                </td>
                            </tr>

                            <tr v-if="!apiKeys.data || apiKeys.data.length === 0">
                                <td colspan="8" class="py-12 text-center text-slate-400 font-sans">
                                    No REST API keys found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- GENERATE KEY MODAL -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <KeyIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Generate REST API Key Token
                        </h3>
                    </div>
                    <button @click="showModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitKey" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Key Name / Friendly Label <span class="text-rose-500">*</span></label>
                        <input v-model="keyForm.name" type="text" required placeholder="WHMCS Provisioning Module" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">IP Whitelist (Optional)</label>
                        <input v-model="keyForm.allowed_ips" type="text" placeholder="192.168.1.100, 10.0.0.0/24" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        <span class="text-[10px] text-slate-400">Leave blank to allow requests from any remote host.</span>
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Token Expiration</label>
                        <select v-model="keyForm.expires_in_days" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                            <option :value="30">30 Days</option>
                            <option :value="90">90 Days</option>
                            <option :value="365">1 Year</option>
                            <option :value="0">Never Expires</option>
                        </select>
                    </div>

                    <div class="space-y-1.5 pt-1">
                        <label class="block font-bold text-slate-700">API Scopes & Permissions</label>
                        <div class="space-y-1 bg-slate-50 p-2 rounded-[3px] border border-slate-200 max-h-32 overflow-y-auto">
                            <label v-for="ab in availableAbilities" :key="ab.key" class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" :value="ab.key" v-model="keyForm.abilities" class="rounded-[2px] text-blue-600 focus:ring-blue-500 cursor-pointer" />
                                <span class="text-xs text-slate-800">{{ ab.label }}</span>
                            </label>
                        </div>
                    </div>

                    <div class="px-4 py-2.5 -mx-4 -mb-4 mt-4 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                        <button
                            type="button"
                            @click="showModal = false"
                            class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="keyForm.processing"
                            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ keyForm.processing ? 'Generating...' : 'Generate Key' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- DELETE MODAL -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-rose-50 text-rose-600 flex items-center justify-center font-bold border border-rose-100">
                            <TrashIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Delete REST API Key
                        </h3>
                    </div>
                    <button @click="showDeleteModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-2.5 text-xs">
                    <p class="text-slate-600">
                        Are you sure you want to permanently delete API key <strong class="text-slate-900 font-mono">[{{ keyToDelete?.name }}]</strong>? Any external integrations using this token will immediately fail.
                    </p>
                </div>

                <div class="px-4 py-2.5 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                    <button
                        type="button"
                        @click="showDeleteModal = false"
                        class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="submitDelete"
                        :disabled="deleteForm.processing"
                        class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                    >
                        {{ deleteForm.processing ? 'Deleting...' : 'Delete Key' }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
