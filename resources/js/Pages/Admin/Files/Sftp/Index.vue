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
    ShieldCheckIcon,
    ServerIcon,
    KeyIcon,
    GlobeAltIcon,
    PlusIcon,
    LockClosedIcon,
    TrashIcon,
    XMarkIcon,
    CheckIcon,
    FolderIcon,
    CommandLineIcon,
    BoltIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    sftpUsers: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    stats: {
        type: Object,
        default: () => ({
            total_users: 0,
            active_users: 0,
            suspended_users: 0,
            key_auth_count: 0,
            server_ip: '103.59.177.138',
            ssh_port: 22,
            subsystem: '/usr/lib/openssh/sftp-server',
            daemon_status: 'online',
        }),
    },
    subscriptions: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({ search: '', status: 'all', auth_type: 'all' }),
    },
})

// Search & Filters
const search = ref(props.filters?.search || '')
const status = ref(props.filters?.status || 'all')
const authType = ref(props.filters?.auth_type || 'all')
const feedbackMsg = ref('')

const userList = computed(() => {
    if (Array.isArray(props.sftpUsers)) return props.sftpUsers
    if (props.sftpUsers?.data && Array.isArray(props.sftpUsers.data)) return props.sftpUsers.data
    return []
})

const filteredUsers = computed(() => {
    let list = userList.value

    if (search.value.trim()) {
        const q = search.value.toLowerCase()
        list = list.filter(u =>
            u.username.toLowerCase().includes(q) ||
            (u.path || '').toLowerCase().includes(q) ||
            (u.subscription?.domain || '').toLowerCase().includes(q)
        )
    }

    if (status.value !== 'all') {
        list = list.filter(u => (u.status || 'active') === status.value)
    }

    if (authType.value !== 'all') {
        list = list.filter(u => (u.auth_type || 'password') === authType.value)
    }

    return list
})

const resetFilters = () => {
    search.value = ''
    status.value = 'all'
    authType.value = 'all'
}

// 1. CREATE SFTP USER MODAL
const showCreateModal = ref(false)
const createForm = useForm({
    subscription_id: props.subscriptions[0]?.id || '',
    username: '',
    auth_type: 'password',
    password: '',
    public_key: '',
    path: '',
    shell: '/usr/lib/openssh/sftp-server',
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
    createForm.auth_type = 'password'
    createForm.shell = '/usr/lib/openssh/sftp-server'
    createForm.permissions = 'readwrite'
    generatePassword()
    showCreateModal.value = true
}

const submitCreate = () => {
    createForm.post(route('admin.files.sftp.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false
            feedbackMsg.value = `SFTP User '${createForm.username}' provisioned.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 2. QUICK CONNECT MODAL
const showConnectModal = ref(false)
const selectedUserForConnect = ref(null)

const openConnectModal = (u) => {
    selectedUserForConnect.value = u
    showConnectModal.value = true
}

// 3. KEY MANAGER MODAL
const showKeyModal = ref(false)
const selectedUserForKey = ref(null)
const keyForm = useForm({
    public_key: '',
})

const openKeyModal = (u) => {
    selectedUserForKey.value = u
    keyForm.public_key = u.public_key || ''
    showKeyModal.value = true
}

const submitKey = () => {
    if (!selectedUserForKey.value) return
    keyForm.post(route('admin.files.sftp.key', selectedUserForKey.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showKeyModal.value = false
            feedbackMsg.value = `Public key saved for '${selectedUserForKey.value.username}'.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 4. PASSWORD MODAL
const showPasswordModal = ref(false)
const selectedUserForPassword = ref(null)
const passwordForm = useForm({
    password: '',
})

const openPasswordModal = (u) => {
    selectedUserForPassword.value = u
    passwordForm.reset()
    showPasswordModal.value = true
}

const submitPassword = () => {
    if (!selectedUserForPassword.value) return
    passwordForm.post(route('admin.files.sftp.password', selectedUserForPassword.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showPasswordModal.value = false
            feedbackMsg.value = `Password updated for '${selectedUserForPassword.value.username}'.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 5. TOGGLE STATUS
const toggleStatus = (u) => {
    const action = u.status === 'active' ? 'Suspend' : 'Activate'
    if (confirm(`${action} SFTP user ${u.username}?`)) {
        useForm({}).post(route('admin.files.sftp.toggle', u.id), {
            preserveScroll: true,
            onSuccess: () => {
                feedbackMsg.value = `SFTP user status updated.`
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    }
}

// 6. DELETE USER
const showDeleteModal = ref(false)
const userToDelete = ref(null)
const deleteForm = useForm({})

const openDeleteModal = (u) => {
    userToDelete.value = u
    showDeleteModal.value = true
}

const submitDelete = () => {
    if (!userToDelete.value) return
    deleteForm.delete(route('admin.files.sftp.destroy', userToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false
            feedbackMsg.value = `SFTP User removed.`
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
    <Head title="SFTP / SSH Users - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Services', href: '#' },
                    { label: 'Files & Storage', href: route('admin.files.manager') },
                    { label: 'SFTP / SSH Users & Access' }
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
                        <span>Create SFTP User</span>
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
                    title="SFTP / SSH Users"
                    :value="String(stats.total_users || userList.length || 0)"
                    badge="Total"
                    badgeType="info"
                    color="blue"
                    :icon="CommandLineIcon"
                />

                <InfoCard
                    title="Active Resolving"
                    :value="String(stats.active_users || 0)"
                    badge="Active"
                    badgeType="success"
                    color="emerald"
                    :icon="ShieldCheckIcon"
                />

                <InfoCard
                    title="Key Authentication"
                    :value="String(stats.key_auth_count || 0)"
                    badge="Public Keys"
                    badgeType="info"
                    color="purple"
                    :icon="KeyIcon"
                />

                <InfoCard
                    title="OpenSSH Port 22"
                    :value="stats.daemon_status === 'online' ? 'SSH Subsystem Active' : 'Offline'"
                    badge="Port 22"
                    badgeType="success"
                    color="sky"
                    :icon="ServerIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search SFTP username, chroot path, or domain..."
                @search="() => {}"
                @filter="() => {}"
                @reset="resetFilters"
            >
                <FilterSelect
                    v-model="status"
                    label="Account Status"
                    :options="[
                        { label: 'All Statuses', value: 'all' },
                        { label: 'Active Users', value: 'active' },
                        { label: 'Suspended Users', value: 'suspended' }
                    ]"
                    placeholder="All Statuses"
                />

                <FilterSelect
                    v-model="authType"
                    label="Authentication Method"
                    :options="[
                        { label: 'All Methods', value: 'all' },
                        { label: 'Password Auth', value: 'password' },
                        { label: 'Public Key (RSA/Ed25519)', value: 'key' }
                    ]"
                    placeholder="All Methods"
                />
            </DataTableFilter>

            <!-- 4. Standard Data Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">SFTP Username</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Associated Domain / Tenant</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Auth Method</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Home Chroot Path</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Subsystem / Shell</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Status</th>
                                <th class="py-2.5 px-3 w-28">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(u, index) in filteredUsers" :key="u.id || index" class="hover:bg-blue-50/30 transition">
                                <!-- # -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>

                                <!-- Username -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono font-bold text-slate-900">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs border border-blue-100 shrink-0">
                                            <CommandLineIcon class="w-3.5 h-3.5" />
                                        </div>
                                        <span>{{ u.username }}</span>
                                    </div>
                                </td>

                                <!-- Domain -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap">
                                    <span v-if="u.subscription" class="font-bold text-slate-800 font-mono text-xs block leading-tight">{{ u.subscription.domain }}</span>
                                    <span v-else class="text-slate-400 italic">System Scope</span>
                                </td>

                                <!-- Auth Method -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="u.auth_type === 'key' ? 'bg-purple-50 text-purple-700 border-purple-200' : 'bg-blue-50 text-blue-700 border-blue-200'"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ u.auth_type === 'key' ? 'Public Key' : 'Password' }}
                                    </span>
                                </td>

                                <!-- Path -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono text-[11px] text-slate-700">
                                    {{ u.path || '/home/' + u.username }}
                                </td>

                                <!-- Shell -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[10.5px] text-slate-500">
                                    {{ u.shell ? u.shell.split('/').pop() : 'sftp-only' }}
                                </td>

                                <!-- Status -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="u.status === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ u.status || 'active' }}
                                    </span>
                                </td>

                                <!-- Action -->
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1">
                                        <button 
                                            type="button" 
                                            @click="openConnectModal(u)"
                                            class="px-2 py-1 bg-white hover:bg-slate-50 text-blue-700 font-bold rounded-[3px] text-xs border border-slate-200 shadow-2xs transition cursor-pointer"
                                            title="Quick Connect Info"
                                        >
                                            Connect ⚡
                                        </button>

                                        <RowActionDropdown>
                                            <button
                                                type="button"
                                                @click="openKeyModal(u)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-purple-700 hover:bg-purple-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <KeyIcon class="w-3.5 h-3.5 text-purple-500" />
                                                <span>Manage SSH Public Keys</span>
                                            </button>

                                            <button
                                                type="button"
                                                @click="openPasswordModal(u)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <LockClosedIcon class="w-3.5 h-3.5 text-slate-500" />
                                                <span>Change Password</span>
                                            </button>

                                            <button
                                                type="button"
                                                @click="toggleStatus(u)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <BoltIcon class="w-3.5 h-3.5 text-slate-500" />
                                                <span>{{ u.status === 'active' ? 'Suspend User' : 'Activate User' }}</span>
                                            </button>

                                            <button
                                                type="button"
                                                @click="openDeleteModal(u)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-rose-600 hover:bg-rose-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <TrashIcon class="w-3.5 h-3.5" />
                                                <span>Delete SFTP User</span>
                                            </button>
                                        </RowActionDropdown>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!filteredUsers || filteredUsers.length === 0">
                                <td colspan="8" class="py-12 text-center text-slate-400">
                                    No SFTP users configured.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 1. CREATE SFTP USER MODAL -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-lg overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <PlusIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Create SFTP / SSH User
                        </h3>
                    </div>
                    <button @click="showCreateModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitCreate" class="p-4 space-y-3 text-xs">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Account Subscription <span class="text-rose-500">*</span></label>
                            <select v-model="createForm.subscription_id" required class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                                <option v-for="s in subscriptions" :key="s.id" :value="s.id">
                                    {{ s.domain }} (@{{ s.username }})
                                </option>
                            </select>
                        </div>

                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">SFTP Username <span class="text-rose-500">*</span></label>
                            <input v-model="createForm.username" type="text" required placeholder="sftp_dev" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Authentication Method</label>
                        <div class="grid grid-cols-2 gap-2">
                            <label :class="createForm.auth_type === 'password' ? 'border-blue-600 bg-blue-50/30' : 'border-slate-200'" class="p-2 border rounded-[3px] cursor-pointer flex items-center gap-2">
                                <input type="radio" value="password" v-model="createForm.auth_type" class="text-blue-600" />
                                <span class="font-bold text-slate-800">Password Auth</span>
                            </label>
                            <label :class="createForm.auth_type === 'key' ? 'border-blue-600 bg-blue-50/30' : 'border-slate-200'" class="p-2 border rounded-[3px] cursor-pointer flex items-center gap-2">
                                <input type="radio" value="key" v-model="createForm.auth_type" class="text-blue-600" />
                                <span class="font-bold text-slate-800">SSH Public Key</span>
                            </label>
                        </div>
                    </div>

                    <div v-if="createForm.auth_type === 'password'" class="space-y-1">
                        <div class="flex items-center justify-between">
                            <label class="font-bold text-slate-700">SFTP Password <span class="text-rose-500">*</span></label>
                            <button type="button" @click="generatePassword" class="text-[10px] text-blue-600 font-bold hover:underline cursor-pointer">Generate</button>
                        </div>
                        <input v-model="createForm.password" type="text" required class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div v-if="createForm.auth_type === 'key'" class="space-y-1">
                        <label class="block font-bold text-slate-700">Authorized Public Key (id_rsa.pub / id_ed25519.pub) <span class="text-rose-500">*</span></label>
                        <textarea v-model="createForm.public_key" rows="3" required placeholder="ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABAQC..." class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"></textarea>
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Chroot Subdirectory (Optional)</label>
                        <input v-model="createForm.path" type="text" placeholder="e.g. public_html" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
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
                            {{ createForm.processing ? 'Provisioning...' : 'Create SFTP User' }}
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
                            <CommandLineIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            SFTP Connection Settings
                        </h3>
                    </div>
                    <button @click="showConnectModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-3 text-xs">
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-slate-700">SSH CLI Command</span>
                            <button @click="copyToClipboard(`sftp -P ${stats.ssh_port || 22} ${selectedUserForConnect?.username}@${stats.server_ip}`, 'Command')" class="text-[10px] text-blue-600 font-bold hover:underline cursor-pointer">Copy</button>
                        </div>
                        <div class="bg-slate-900 text-emerald-400 p-2.5 rounded-[3px] font-mono text-[11px] break-all">
                            sftp -P {{ stats.ssh_port || 22 }} {{ selectedUserForConnect?.username }}@{{ stats.server_ip }}
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <span class="font-bold text-slate-700">Host IP</span>
                            <div class="bg-slate-50 p-2 rounded-[3px] border border-slate-200 font-mono text-[11px] text-slate-900">
                                {{ stats.server_ip }}
                            </div>
                        </div>
                        <div class="space-y-1">
                            <span class="font-bold text-slate-700">Port</span>
                            <div class="bg-slate-50 p-2 rounded-[3px] border border-slate-200 font-mono text-[11px] text-slate-900">
                                {{ stats.ssh_port || 22 }}
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

        <!-- 3. KEY MANAGER MODAL -->
        <div v-if="showKeyModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-lg overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <KeyIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Authorized SSH Key: {{ selectedUserForKey?.username }}
                        </h3>
                    </div>
                    <button @click="showKeyModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitKey" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Public Key (OpenSSH Format) <span class="text-rose-500">*</span></label>
                        <textarea v-model="keyForm.public_key" rows="6" required placeholder="ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAI..." class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"></textarea>
                    </div>

                    <div class="px-4 py-2.5 -mx-4 -mb-4 mt-4 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                        <button
                            type="button"
                            @click="showKeyModal = false"
                            class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="keyForm.processing"
                            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ keyForm.processing ? 'Saving...' : 'Save Public Key' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 4. PASSWORD MODAL -->
        <div v-if="showPasswordModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <LockClosedIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Change Password: {{ selectedUserForPassword?.username }}
                        </h3>
                    </div>
                    <button @click="showPasswordModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitPassword" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">New SFTP Password <span class="text-rose-500">*</span></label>
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

        <!-- 5. DELETE MODAL -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-rose-50 text-rose-600 flex items-center justify-center font-bold border border-rose-100">
                            <TrashIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Delete SFTP User
                        </h3>
                    </div>
                    <button @click="showDeleteModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-2.5 text-xs">
                    <p class="text-slate-600">
                        Are you sure you want to delete SFTP user <strong class="text-slate-900 font-mono">[{{ userToDelete?.username }}]</strong>? SSH access and key authorizations will be revoked immediately.
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
                        {{ deleteForm.processing ? 'Deleting...' : 'Delete User' }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
