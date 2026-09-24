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
    EnvelopeIcon,
    TrashIcon,
    PlusIcon,
    KeyIcon,
    BoltIcon,
    XMarkIcon,
    CheckIcon,
    DocumentDuplicateIcon,
    GlobeAltIcon,
    ArrowsRightLeftIcon,
    DevicePhoneMobileIcon,
    CircleStackIcon,
    ServerIcon,
    ArrowTopRightOnSquareIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    accounts: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            total_accounts: 0,
            total_quota_gb: 0,
            used_storage_mb: 0,
            forwarders_count: 0,
        }),
    },
    domains: {
        type: Array,
        default: () => [],
    },
    subscriptions: {
        type: Array,
        default: () => [],
    },
    currentDomain: {
        type: String,
        default: 'all',
    },
    currentStatus: {
        type: String,
        default: 'all',
    },
})

// Toast Feedback
const feedbackMsg = ref('')

// Filters
const search = ref('')
const selectedDomainFilter = ref(props.currentDomain || 'all')
const selectedStatusFilter = ref(props.currentStatus || 'all')

const domainOptions = computed(() => {
    const list = [{ label: 'All Mail Domains', value: 'all' }]
    props.domains.forEach(d => {
        list.push({ label: d.domain, value: d.domain })
    })
    return list
})

const filteredAccounts = computed(() => {
    let list = props.accounts || []

    if (search.value.trim()) {
        const q = search.value.toLowerCase()
        list = list.filter(a =>
            a.email.toLowerCase().includes(q) ||
            (a.domain || '').toLowerCase().includes(q) ||
            (a.forward_to || '').toLowerCase().includes(q) ||
            (a.subscription?.domain || '').toLowerCase().includes(q)
        )
    }

    if (selectedDomainFilter.value !== 'all') {
        list = list.filter(a => a.domain === selectedDomainFilter.value)
    }

    if (selectedStatusFilter.value !== 'all') {
        list = list.filter(a => (a.status || 'active') === selectedStatusFilter.value)
    }

    return list
})

const resetFilters = () => {
    search.value = ''
    selectedDomainFilter.value = 'all'
    selectedStatusFilter.value = 'all'
}

// 1. CREATE MAILBOX MODAL
const showCreateModal = ref(false)
const createForm = useForm({
    email_domain_id: props.domains[0]?.id || '',
    username: '',
    password: '',
    quota_mb: 1024,
    forward_to: '',
})

const generatePassword = () => {
    const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*'
    let pass = ''
    for (let i = 0; i < 16; i++) {
        pass += chars.charAt(Math.floor(Math.random() * chars.length))
    }
    createForm.password = pass
}

const openCreateModal = () => {
    createForm.reset()
    createForm.email_domain_id = props.domains[0]?.id || ''
    generatePassword()
    showCreateModal.value = true
}

const submitCreate = () => {
    createForm.post(route('admin.email.accounts.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false
            feedbackMsg.value = `Mailbox '${createForm.username}@...' created successfully.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 2. PASSWORD MODAL
const showPasswordModal = ref(false)
const showPassField = ref(false)
const selectedAccount = ref(null)
const passwordForm = useForm({
    password: '',
})

const generateRandomPass = () => {
    const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*'
    let pass = ''
    for (let i = 0; i < 14; i++) {
        pass += chars.charAt(Math.floor(Math.random() * chars.length))
    }
    return pass
}

const openPasswordModal = (account) => {
    selectedAccount.value = account
    passwordForm.reset()
    showPassField.value = false
    showPasswordModal.value = true
}

const submitPassword = () => {
    if (!selectedAccount.value) return
    passwordForm.post(route('admin.email.accounts.password', selectedAccount.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showPasswordModal.value = false
            feedbackMsg.value = `Password for '${selectedAccount.value.email}' updated.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 3. QUOTA MODAL
const showQuotaModal = ref(false)
const quotaForm = useForm({
    quota_mb: 1024,
})

const openQuotaModal = (account) => {
    selectedAccount.value = account
    quotaForm.quota_mb = account.quota_mb || 1024
    showQuotaModal.value = true
}

const submitQuota = () => {
    if (!selectedAccount.value) return
    quotaForm.post(route('admin.email.accounts.quota', selectedAccount.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showQuotaModal.value = false
            feedbackMsg.value = `Quota for '${selectedAccount.value.email}' updated.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 4. FORWARDING MODAL
const showForwardingModal = ref(false)
const forwardForm = useForm({
    forward_to: '',
})

const openForwardingModal = (account) => {
    selectedAccount.value = account
    forwardForm.forward_to = account.forward_to || ''
    showForwardingModal.value = true
}

const submitForwarding = () => {
    if (!selectedAccount.value) return
    forwardForm.post(route('admin.email.accounts.forwarding', selectedAccount.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showForwardingModal.value = false
            feedbackMsg.value = `Forwarding for '${selectedAccount.value.email}' updated.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 5. CLIENT SETTINGS MODAL
const showClientModal = ref(false)
const clientAccount = ref(null)

const openClientModal = (account) => {
    clientAccount.value = account
    showClientModal.value = true
}

// 6. TOGGLE STATUS
const toggleStatus = (account) => {
    const action = account.status === 'active' ? 'Suspend' : 'Activate'
    if (confirm(`${action} mailbox ${account.email}?`)) {
        useForm({}).post(route('admin.email.accounts.toggle', account.id), {
            preserveScroll: true,
            onSuccess: () => {
                feedbackMsg.value = `Mailbox ${account.email} status updated.`
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    }
}

// 7. DELETE ACCOUNT
const showDeleteModal = ref(false)
const accountToDelete = ref(null)
const deleteForm = useForm({})

const openDeleteModal = (account) => {
    accountToDelete.value = account
    showDeleteModal.value = true
}

const submitDelete = () => {
    if (!accountToDelete.value) return
    deleteForm.delete(route('admin.email.accounts.destroy', accountToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false
            feedbackMsg.value = `Mailbox ${accountToDelete.value.email} deleted.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

const copyToClipboard = (text) => {
    navigator.clipboard.writeText(text)
    feedbackMsg.value = 'Copied to clipboard!'
    setTimeout(() => { feedbackMsg.value = '' }, 2500)
}
</script>

<template>
    <Head title="Mail Accounts - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Email', href: route('admin.email.domains') },
                    { label: 'Accounts' }
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
                        @click="openCreateModal"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <PlusIcon class="w-3.5 h-3.5" />
                        <span>Create Account</span>
                    </button>

                    <RefreshButton />
                </template>
            </PageHeader>

            <!-- Feedback Banner -->
            <div v-if="feedbackMsg" class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-3 py-2 rounded-[3px] text-xs font-bold flex items-center justify-between shadow-2xs">
                <div class="flex items-center gap-2">
                    <CheckCircleIcon class="w-4 h-4 text-emerald-600" />
                    <span>{{ feedbackMsg }}</span>
                </div>
                <button @click="feedbackMsg = ''" class="text-emerald-600 hover:text-emerald-800 text-xs cursor-pointer">✕</button>
            </div>

            <!-- 2. Ultra-Compact KPI Info Cards (56px) -->
            <InfoCardsGrid :cols="4">
                <InfoCard
                    title="Total Mailboxes"
                    :value="String(stats.total_accounts || accounts.length)"
                    badge="Active"
                    badgeType="success"
                    color="blue"
                    :icon="EnvelopeIcon"
                />

                <InfoCard
                    title="Allocated Storage"
                    :value="`${Math.round((stats.total_quota_mb || 0) / 1024)} GB`"
                    badge="Quota"
                    badgeType="neutral"
                    color="indigo"
                    :icon="CircleStackIcon"
                />

                <InfoCard
                    title="Active Forwarders"
                    :value="String(stats.forwarders_count || 0)"
                    badge="Routing"
                    badgeType="info"
                    color="purple"
                    :icon="ArrowPathIcon"
                />

                <InfoCard
                    title="Auto-Responders"
                    :value="String(stats.autoresponders_count || 0)"
                    badge="Automated"
                    badgeType="warning"
                    color="amber"
                    :icon="ArrowsRightLeftIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search email address, domain, or forwarding target..."
                @search="() => {}"
                @filter="() => {}"
                @reset="resetFilters"
            >
                <FilterSelect
                    v-model="selectedDomainFilter"
                    label="Filter Domain"
                    :options="domainOptions"
                    placeholder="All Domains"
                />

                <FilterSelect
                    v-model="selectedStatusFilter"
                    label="Account Status"
                    :options="[
                        { label: 'All Statuses', value: 'all' },
                        { label: 'Active Mailboxes', value: 'active' },
                        { label: 'Suspended Mailboxes', value: 'suspended' }
                    ]"
                    placeholder="All Statuses"
                />
            </DataTableFilter>

            <!-- 4. Standard Data Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Email Address</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Domain</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Owner / Subscription</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Disk Usage / Quota</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Auto Forwarding</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Status</th>
                                <th class="py-2.5 px-3 w-14 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(account, index) in filteredAccounts" :key="account.id || index" class="hover:bg-blue-50/30 transition">
                                <!-- # -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>

                                <!-- Email -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs border border-blue-100 shrink-0">
                                            <EnvelopeIcon class="w-3.5 h-3.5" />
                                        </div>
                                        <div>
                                            <span class="font-bold text-slate-900 font-mono leading-tight">{{ account.email }}</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Domain -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono text-slate-700">
                                    {{ account.domain }}
                                </td>

                                <!-- Owner / Sub -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap">
                                    <div v-if="account.subscription" class="space-y-0.5">
                                        <span class="font-bold text-slate-900 block leading-tight">{{ account.subscription.domain }}</span>
                                        <span class="text-[10px] font-mono text-slate-400 block">@{{ account.subscription.username }}</span>
                                    </div>
                                    <span v-else class="text-slate-400 italic">Standalone Mail</span>
                                </td>

                                <!-- Quota -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono">
                                    <div class="space-y-1">
                                        <span class="font-bold text-slate-900 text-[11px]">{{ account.used_mb || 0 }} MB</span>
                                        <span class="text-slate-400 text-[10px]"> / {{ account.quota_mb ? `${account.quota_mb} MB` : '∞' }}</span>
                                    </div>
                                </td>

                                <!-- Forwarding -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono text-[10.5px]">
                                    <span v-if="account.forward_to" class="text-blue-600 font-bold">
                                        ↳ {{ account.forward_to }}
                                    </span>
                                    <span v-else class="text-slate-400 italic">
                                        Direct Inbox Only
                                    </span>
                                </td>

                                <!-- Status -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="account.status === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ account.status || 'active' }}
                                    </span>
                                </td>

                                <!-- Action -->
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <RowActionDropdown>
                                        <button 
                                            type="button" 
                                            @click="openPasswordModal(account)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-blue-600 hover:bg-blue-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <KeyIcon class="w-3.5 h-3.5 text-blue-500" />
                                            <span>Change Password</span>
                                        </button>

                                        <button
                                            type="button"
                                            @click="openClientModal(account)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <DevicePhoneMobileIcon class="w-3.5 h-3.5 text-slate-500" />
                                            <span>Client Setup (IMAP/SMTP)</span>
                                        </button>

                                        <button
                                            type="button"
                                            @click="openQuotaModal(account)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <CircleStackIcon class="w-3.5 h-3.5 text-slate-500" />
                                            <span>Change Quota Limit</span>
                                        </button>

                                        <button
                                            type="button"
                                            @click="openForwardingModal(account)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <ArrowsRightLeftIcon class="w-3.5 h-3.5 text-slate-500" />
                                            <span>Configure Forwarding</span>
                                        </button>

                                        <button
                                            type="button"
                                            @click="toggleStatus(account)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <BoltIcon class="w-3.5 h-3.5 text-slate-500" />
                                            <span>{{ account.status === 'active' ? 'Suspend Mailbox' : 'Activate Mailbox' }}</span>
                                        </button>

                                        <button
                                            type="button"
                                            @click="openDeleteModal(account)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-rose-600 hover:bg-rose-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <TrashIcon class="w-3.5 h-3.5" />
                                            <span>Delete Mailbox</span>
                                        </button>
                                    </RowActionDropdown>
                                </td>
                            </tr>

                            <tr v-if="!filteredAccounts || filteredAccounts.length === 0">
                                <td colspan="8" class="py-12 text-center text-slate-400">
                                    No mail accounts found matching search.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 1. CREATE MAILBOX MODAL -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <PlusIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Create New Mailbox
                        </h3>
                    </div>
                    <button @click="showCreateModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitCreate" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Domain <span class="text-rose-500">*</span></label>
                        <select v-model="createForm.email_domain_id" required class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                            <option v-for="d in domains" :key="d.id" :value="d.id">
                                @{{ d.domain }}
                            </option>
                        </select>
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Username Prefix <span class="text-rose-500">*</span></label>
                        <input v-model="createForm.username" type="text" required placeholder="e.g. info, contact, john" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="space-y-1">
                        <div class="flex items-center justify-between">
                            <label class="font-bold text-slate-700">Password <span class="text-rose-500">*</span></label>
                            <button type="button" @click="generatePassword" class="text-[10px] text-blue-600 font-bold hover:underline cursor-pointer">Generate</button>
                        </div>
                        <input v-model="createForm.password" type="text" required class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Storage Quota (MB)</label>
                        <input v-model.number="createForm.quota_mb" type="number" min="100" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Optional Auto Forwarding (To Address)</label>
                        <input v-model="createForm.forward_to" type="email" placeholder="e.g. personal@gmail.com" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="px-4 py-2.5 -mx-4 -mb-4 mt-4 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                        <button
                            type="button"
                            @click="showCreateModal = false"
                            class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="createForm.processing"
                            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ createForm.processing ? 'Creating...' : 'Create Mailbox' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 2. PASSWORD RESET MODAL -->
        <div v-if="showPasswordModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <KeyIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Change Password: {{ selectedAccount?.email }}
                        </h3>
                    </div>
                    <button @click="showPasswordModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitPassword" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <div class="flex items-center justify-between">
                            <label class="block font-bold text-slate-700">New Password <span class="text-rose-500">*</span></label>
                            <button 
                                type="button" 
                                @click="passwordForm.password = generateRandomPass()" 
                                class="text-[10px] text-blue-600 font-bold hover:underline cursor-pointer"
                            >
                                Generate Password
                            </button>
                        </div>
                        <div class="relative">
                            <input 
                                v-model="passwordForm.password" 
                                :type="showPassField ? 'text' : 'password'" 
                                required 
                                placeholder="Enter at least 8 characters" 
                                class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 pr-16" 
                            />
                            <button
                                type="button"
                                @click="showPassField = !showPassField"
                                class="absolute right-2 top-1.5 text-[10px] font-bold text-slate-500 hover:text-slate-800"
                            >
                                {{ showPassField ? 'HIDE' : 'SHOW' }}
                            </button>
                        </div>
                        <span v-if="passwordForm.errors.password || passwordForm.errors.new_password" class="text-[11px] text-rose-600 block font-bold">
                            {{ passwordForm.errors.password || passwordForm.errors.new_password }}
                        </span>
                    </div>

                    <div class="px-4 py-2.5 -mx-4 -mb-4 mt-4 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                        <button
                            type="button"
                            @click="showPasswordModal = false"
                            class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="passwordForm.processing"
                            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ passwordForm.processing ? 'Updating...' : 'Update Password' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 3. QUOTA MODAL -->
        <div v-if="showQuotaModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <CircleStackIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Change Quota: {{ selectedAccount?.email }}
                        </h3>
                    </div>
                    <button @click="showQuotaModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitQuota" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Storage Quota Limit (MB)</label>
                        <input v-model.number="quotaForm.quota_mb" type="number" min="100" required class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="px-4 py-2.5 -mx-4 -mb-4 mt-4 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                        <button
                            type="button"
                            @click="showQuotaModal = false"
                            class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="quotaForm.processing"
                            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ quotaForm.processing ? 'Saving...' : 'Save Quota' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 4. FORWARDING MODAL -->
        <div v-if="showForwardingModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <ArrowsRightLeftIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Auto Forwarding: {{ selectedAccount?.email }}
                        </h3>
                    </div>
                    <button @click="showForwardingModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitForwarding" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Forward Destination Address</label>
                        <input v-model="forwardForm.forward_to" type="email" placeholder="Leave empty to disable forwarding" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        <span class="text-[10px] text-slate-400 block">A copy of incoming messages will still be retained in this mailbox.</span>
                    </div>

                    <div class="px-4 py-2.5 -mx-4 -mb-4 mt-4 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                        <button
                            type="button"
                            @click="showForwardingModal = false"
                            class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="forwardForm.processing"
                            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ forwardForm.processing ? 'Saving...' : 'Save Forwarding' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 5. CLIENT SETUP MODAL -->
        <div v-if="showClientModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-lg overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <DevicePhoneMobileIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Client Setup: {{ clientAccount?.email }}
                        </h3>
                    </div>
                    <button @click="showClientModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-3 text-xs">
                    <!-- Secure IMAP / POP3 -->
                    <div class="p-3 bg-slate-50 rounded-[3px] border border-slate-200 space-y-1.5">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-slate-800">Incoming Server (IMAP / SSL)</span>
                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">Port 993 (SSL/TLS)</span>
                        </div>
                        <p class="font-mono text-[11px] text-slate-700">Host: mail.{{ clientAccount?.domain }}</p>
                        <p class="font-mono text-[11px] text-slate-700">Username: {{ clientAccount?.email }}</p>
                    </div>

                    <!-- Secure SMTP -->
                    <div class="p-3 bg-slate-50 rounded-[3px] border border-slate-200 space-y-1.5">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-slate-800">Outgoing Server (SMTP / TLS)</span>
                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-800">Port 465 (SSL) / 587 (STARTTLS)</span>
                        </div>
                        <p class="font-mono text-[11px] text-slate-700">Host: mail.{{ clientAccount?.domain }}</p>
                        <p class="font-mono text-[11px] text-slate-700">Authentication: Password Required</p>
                    </div>
                </div>

                <div class="px-4 py-2.5 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end text-xs">
                    <button
                        type="button"
                        @click="showClientModal = false"
                        class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>

        <!-- 6. DELETE MODAL -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-rose-50 text-rose-600 flex items-center justify-center font-bold border border-rose-100">
                            <TrashIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Delete Mailbox
                        </h3>
                    </div>
                    <button @click="showDeleteModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-2.5 text-xs">
                    <p class="text-slate-600">
                        Are you sure you want to permanently delete mailbox <strong class="text-slate-900 font-mono">[{{ accountToDelete?.email }}]</strong>? All messages stored in this inbox will be permanently purged.
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
                        {{ deleteForm.processing ? 'Deleting...' : 'Delete Mailbox' }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
