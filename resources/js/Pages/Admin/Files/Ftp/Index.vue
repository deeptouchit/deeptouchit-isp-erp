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
    FolderArrowDownIcon,
    ServerIcon,
    ArrowsRightLeftIcon,
    GlobeAltIcon,
    PlusIcon,
    KeyIcon,
    TrashIcon,
    XMarkIcon,
    CheckIcon,
    FolderIcon,
    ShieldCheckIcon,
    BoltIcon,
    Cog6ToothIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    ftpAccounts: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    stats: {
        type: Object,
        default: () => ({
            total_accounts: 0,
            active_accounts: 0,
            suspended_accounts: 0,
            total_subscriptions: 0,
            server_ip: '103.59.177.138',
            ftp_port: 21,
            passive_ports: '30000 - 31000',
            daemon_status: 'online',
        }),
    },
    subscriptions: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({ search: '', status: 'all' }),
    },
})

// Search & Filter
const search = ref(props.filters?.search || '')
const status = ref(props.filters?.status || 'all')
const feedbackMsg = ref('')

const accountList = computed(() => {
    if (Array.isArray(props.ftpAccounts)) return props.ftpAccounts
    if (props.ftpAccounts?.data && Array.isArray(props.ftpAccounts.data)) return props.ftpAccounts.data
    return []
})

const filteredAccounts = computed(() => {
    let list = accountList.value

    if (search.value.trim()) {
        const q = search.value.toLowerCase()
        list = list.filter(acc =>
            acc.username.toLowerCase().includes(q) ||
            (acc.path || '').toLowerCase().includes(q) ||
            (acc.subscription?.domain || '').toLowerCase().includes(q)
        )
    }

    if (status.value !== 'all') {
        list = list.filter(acc => (acc.status || 'active') === status.value)
    }

    return list
})

const resetFilters = () => {
    search.value = ''
    status.value = 'all'
}

// 1. CREATE FTP ACCOUNT MODAL
const showCreateModal = ref(false)
const showPassword = ref(false)
const createForm = useForm({
    subscription_id: props.subscriptions[0]?.id || '',
    username: '',
    password: '',
    path: 'public_html',
    permissions: 'readwrite',
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
    createForm.subscription_id = props.subscriptions[0]?.id || ''
    createForm.path = 'public_html'
    createForm.permissions = 'readwrite'
    generatePassword()
    showCreateModal.value = true
}

const submitCreate = () => {
    createForm.post(route('admin.files.ftp.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false
            feedbackMsg.value = `FTP Account '${createForm.username}' created.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 2. QUICK CONNECT MODAL
const showConnectModal = ref(false)
const selectedAccountForConnect = ref(null)

const openConnectModal = (acc) => {
    selectedAccountForConnect.value = acc
    showConnectModal.value = true
}

// 3. CHANGE PASSWORD MODAL
const showPasswordModal = ref(false)
const selectedAccountForPassword = ref(null)
const passwordForm = useForm({
    password: '',
})

const openPasswordModal = (acc) => {
    selectedAccountForPassword.value = acc
    passwordForm.reset()
    showPasswordModal.value = true
}

const submitPassword = () => {
    if (!selectedAccountForPassword.value) return
    passwordForm.post(route('admin.files.ftp.password', selectedAccountForPassword.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showPasswordModal.value = false
            feedbackMsg.value = `Password updated for '${selectedAccountForPassword.value.username}'.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 4. TOGGLE STATUS
const toggleStatus = (acc) => {
    const action = acc.status === 'active' ? 'Suspend' : 'Activate'
    if (confirm(`${action} FTP account ${acc.username}?`)) {
        useForm({}).post(route('admin.files.ftp.toggle', acc.id), {
            preserveScroll: true,
            onSuccess: () => {
                feedbackMsg.value = `FTP account status updated.`
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    }
}

// 5. DELETE ACCOUNT
const showDeleteModal = ref(false)
const accountToDelete = ref(null)
const deleteForm = useForm({})

const openDeleteModal = (acc) => {
    accountToDelete.value = acc
    showDeleteModal.value = true
}

const submitDelete = () => {
    if (!accountToDelete.value) return
    deleteForm.delete(route('admin.files.ftp.destroy', accountToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false
            feedbackMsg.value = `FTP Account removed.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

const copyToClipboard = (text, label) => {
    navigator.clipboard.writeText(text)
    feedbackMsg.value = `${label} copied to clipboard!`
    setTimeout(() => { feedbackMsg.value = '' }, 2500)
}
</script>

<template>
    <Head title="FTP Accounts - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Services', href: '#' },
                    { label: 'Files & Storage', href: route('admin.files.manager') },
                    { label: 'FTP Accounts & Daemon' }
                ]"
            >
                <template #actions>
                    <Link
                        :href="route('admin.files.manager')"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowLeftIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>File Manager</span>
                    </Link>

                    <button 
                        type="button" 
                        @click="openCreateModal"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <PlusIcon class="w-3.5 h-3.5" />
                        <span>Create FTP Account</span>
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
                    title="FTP Accounts"
                    :value="String(stats.total_accounts || accountList.length || 0)"
                    badge="Total"
                    badgeType="info"
                    color="blue"
                    :icon="FolderArrowDownIcon"
                />

                <InfoCard
                    title="Active Resolving"
                    :value="String(stats.active_accounts || 0)"
                    badge="Active"
                    badgeType="success"
                    color="emerald"
                    :icon="ShieldCheckIcon"
                />

                <InfoCard
                    title="Port & Passive Range"
                    :value="`Port ${stats.ftp_port || 21} (${stats.passive_ports || '30000-31000'})`"
                    badge="Pure-FTPd"
                    badgeType="info"
                    color="purple"
                    :icon="ServerIcon"
                />

                <InfoCard
                    title="Daemon Service"
                    :value="stats.daemon_status === 'online' ? 'Pure-FTPd Online' : 'Stopped'"
                    badge="Running"
                    badgeType="success"
                    color="sky"
                    :icon="ArrowsRightLeftIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search FTP username, path, or account domain..."
                @search="() => {}"
                @filter="() => {}"
                @reset="resetFilters"
            >
                <FilterSelect
                    v-model="status"
                    label="Account Status"
                    :options="[
                        { label: 'All Statuses', value: 'all' },
                        { label: 'Active Accounts', value: 'active' },
                        { label: 'Suspended Accounts', value: 'suspended' }
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
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">FTP Username</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Associated Domain / Tenant</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Chroot Directory Path</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Permissions</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Status</th>
                                <th class="py-2.5 px-3 w-28">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(acc, index) in filteredAccounts" :key="acc.id || index" class="hover:bg-blue-50/30 transition">
                                <!-- # -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>

                                <!-- Username -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono font-bold text-slate-900">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs border border-blue-100 shrink-0">
                                            <FolderArrowDownIcon class="w-3.5 h-3.5" />
                                        </div>
                                        <span>{{ acc.username }}</span>
                                    </div>
                                </td>

                                <!-- Domain -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap">
                                    <span v-if="acc.subscription" class="font-bold text-slate-800 font-mono text-xs block leading-tight">{{ acc.subscription.domain }}</span>
                                    <span v-else class="text-slate-400 italic">System Scope</span>
                                </td>

                                <!-- Path -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono text-[11px] text-slate-700">
                                    {{ acc.path || '/public_html' }}
                                </td>

                                <!-- Perms -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase bg-blue-50 text-blue-700 border border-blue-200 font-mono">
                                        {{ acc.permissions || 'Read / Write' }}
                                    </span>
                                </td>

                                <!-- Status -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="acc.status === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ acc.status || 'active' }}
                                    </span>
                                </td>

                                <!-- Action -->
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1">
                                        <button 
                                            type="button" 
                                            @click="openConnectModal(acc)"
                                            class="px-2 py-1 bg-white hover:bg-slate-50 text-blue-700 font-bold rounded-[3px] text-xs border border-slate-200 shadow-2xs transition cursor-pointer"
                                            title="Quick Connect Info"
                                        >
                                            Connect ⚡
                                        </button>

                                        <RowActionDropdown>
                                            <button
                                                type="button"
                                                @click="openPasswordModal(acc)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <KeyIcon class="w-3.5 h-3.5 text-slate-500" />
                                                <span>Change Password</span>
                                            </button>

                                            <button
                                                type="button"
                                                @click="toggleStatus(acc)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <BoltIcon class="w-3.5 h-3.5 text-slate-500" />
                                                <span>{{ acc.status === 'active' ? 'Suspend Account' : 'Activate Account' }}</span>
                                            </button>

                                            <button
                                                type="button"
                                                @click="openDeleteModal(acc)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-rose-600 hover:bg-rose-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <TrashIcon class="w-3.5 h-3.5" />
                                                <span>Delete FTP Account</span>
                                            </button>
                                        </RowActionDropdown>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!filteredAccounts || filteredAccounts.length === 0">
                                <td colspan="7" class="py-12 text-center text-slate-400">
                                    No FTP accounts found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 1. CREATE FTP ACCOUNT MODAL -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <PlusIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Create FTP Account
                        </h3>
                    </div>
                    <button @click="showCreateModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitCreate" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Account Subscription <span class="text-rose-500">*</span></label>
                        <select v-model="createForm.subscription_id" required class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                            <option v-for="s in subscriptions" :key="s.id" :value="s.id">
                                {{ s.domain }} (@{{ s.username }})
                            </option>
                        </select>
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">FTP Username <span class="text-rose-500">*</span></label>
                        <input v-model="createForm.username" type="text" required placeholder="ftp_user" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="space-y-1">
                        <div class="flex items-center justify-between">
                            <label class="font-bold text-slate-700">FTP Password <span class="text-rose-500">*</span></label>
                            <button type="button" @click="generatePassword" class="text-[10px] text-blue-600 font-bold hover:underline cursor-pointer">Generate</button>
                        </div>
                        <input v-model="createForm.password" type="text" required class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Chroot Directory Path</label>
                        <input v-model="createForm.path" type="text" placeholder="public_html" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        <span class="text-[10px] text-slate-400 block">Relative to subscription home directory.</span>
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
                            {{ createForm.processing ? 'Creating...' : 'Create Account' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 2. QUICK CONNECT MODAL -->
        <div v-if="showConnectModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <GlobeAltIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            FTP Client Settings (FileZilla, Cyberduck)
                        </h3>
                    </div>
                    <button @click="showConnectModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-3 text-xs">
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-slate-700">FTP Host / Server IP</span>
                            <button @click="copyToClipboard(stats.server_ip, 'Host IP')" class="text-[10px] text-blue-600 font-bold hover:underline cursor-pointer">Copy</button>
                        </div>
                        <div class="bg-slate-50 p-2 rounded-[3px] border border-slate-200 font-mono text-[11px] text-slate-900">
                            {{ stats.server_ip }}
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-slate-700">Username</span>
                            <button @click="copyToClipboard(selectedAccountForConnect?.username, 'Username')" class="text-[10px] text-blue-600 font-bold hover:underline cursor-pointer">Copy</button>
                        </div>
                        <div class="bg-slate-50 p-2 rounded-[3px] border border-slate-200 font-mono text-[11px] text-slate-900">
                            {{ selectedAccountForConnect?.username }}
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <span class="font-bold text-slate-700">Port</span>
                            <div class="bg-slate-50 p-2 rounded-[3px] border border-slate-200 font-mono text-[11px] text-slate-900">
                                {{ stats.ftp_port || 21 }}
                            </div>
                        </div>
                        <div class="space-y-1">
                            <span class="font-bold text-slate-700">Protocol</span>
                            <div class="bg-slate-50 p-2 rounded-[3px] border border-slate-200 font-mono text-[11px] text-slate-900">
                                FTP / Explicit TLS
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-4 py-2.5 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end text-xs">
                    <button
                        type="button"
                        @click="showConnectModal = false"
                        class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>

        <!-- 3. CHANGE PASSWORD MODAL -->
        <div v-if="showPasswordModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <KeyIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Change Password: {{ selectedAccountForPassword?.username }}
                        </h3>
                    </div>
                    <button @click="showPasswordModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitPassword" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">New Password <span class="text-rose-500">*</span></label>
                        <input v-model="passwordForm.password" type="text" required class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
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
                            {{ passwordForm.processing ? 'Saving...' : 'Update Password' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 4. DELETE MODAL -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-rose-50 text-rose-600 flex items-center justify-center font-bold border border-rose-100">
                            <TrashIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Delete FTP Account
                        </h3>
                    </div>
                    <button @click="showDeleteModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-2.5 text-xs">
                    <p class="text-slate-600">
                        Are you sure you want to delete FTP account <strong class="text-slate-900 font-mono">[{{ accountToDelete?.username }}]</strong>?
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
                        {{ deleteForm.processing ? 'Deleting...' : 'Delete Account' }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
