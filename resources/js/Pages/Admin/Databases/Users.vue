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
    CircleStackIcon,
    TrashIcon,
    PlusIcon,
    ServerIcon,
    KeyIcon,
    BoltIcon,
    XMarkIcon,
    UserGroupIcon,
    CpuChipIcon,
    GlobeAltIcon,
    LockClosedIcon,
    ShieldCheckIcon,
    CheckIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    users: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            total_users: 0,
            remote_users: 0,
            localhost_users: 0,
            admin_users: 0,
        }),
    },
    databases: {
        type: Array,
        default: () => [],
    },
    subscriptions: {
        type: Array,
        default: () => [],
    },
})

// Toast Feedback
const feedbackMsg = ref('')

// Filter & Search
const search = ref('')
const selectedEngineFilter = ref('all')
const selectedHostFilter = ref('all')

const filteredUsers = computed(() => {
    let list = props.users || []

    if (search.value.trim()) {
        const q = search.value.toLowerCase()
        list = list.filter(u =>
            u.username.toLowerCase().includes(q) ||
            (u.host || '').toLowerCase().includes(q) ||
            (u.privileges_label || '').toLowerCase().includes(q)
        )
    }

    if (selectedEngineFilter.value !== 'all') {
        list = list.filter(u => (u.engine || 'mysql') === selectedEngineFilter.value)
    }

    if (selectedHostFilter.value === 'local') {
        list = list.filter(u => !u.is_remote && (u.host === 'localhost' || u.host === '127.0.0.1'))
    } else if (selectedHostFilter.value === 'remote') {
        list = list.filter(u => u.is_remote || u.host === '%' || (u.host !== 'localhost' && u.host !== '127.0.0.1'))
    }

    return list
})

const resetFilters = () => {
    search.value = ''
    selectedEngineFilter.value = 'all'
    selectedHostFilter.value = 'all'
}

// 1. CREATE USER MODAL
const showCreateModal = ref(false)
const createForm = useForm({
    engine: 'mysql',
    username: '',
    password: '',
    host: 'localhost',
    database_name: '',
    privileges: 'all',
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
    generatePassword()
    showCreateModal.value = true
}

const submitCreate = () => {
    createForm.post(route('admin.databases.users.create'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false
            feedbackMsg.value = `Database user '${createForm.username}' created successfully.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 2. PASSWORD MODAL
const showPasswordModal = ref(false)
const selectedUser = ref(null)
const passwordForm = useForm({
    engine: 'mysql',
    username: '',
    host: 'localhost',
    new_password: '',
})

const openPasswordModal = (user) => {
    selectedUser.value = user
    passwordForm.engine = user.engine || 'mysql'
    passwordForm.username = user.username
    passwordForm.host = user.host || 'localhost'
    passwordForm.new_password = ''
    showPasswordModal.value = true
}

const submitPassword = () => {
    if (!selectedUser.value) return
    passwordForm.post(route('admin.databases.users.password'), {
        preserveScroll: true,
        onSuccess: () => {
            showPasswordModal.value = false
            feedbackMsg.value = `Password for user '${selectedUser.value.username}' updated.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 3. TOGGLE REMOTE ACCESS
const toggleRemote = (user) => {
    const newHost = user.is_remote ? 'localhost' : '%'
    const action = user.is_remote ? 'Revoke Remote Access' : 'Allow Remote Access (%)'
    if (confirm(`${action} for database user '${user.username}'?`)) {
        useForm({
            username: user.username,
            current_host: user.host,
            new_host: newHost
        }).post(route('admin.databases.users.host'), {
            preserveScroll: true,
            onSuccess: () => {
                feedbackMsg.value = `Remote host access for '${user.username}' updated to '${newHost}'.`
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    }
}
</script>

<template>
    <Head title="Database Users & Privileges - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Services', href: '#' },
                    { label: 'Databases', href: route('admin.databases.index') },
                    { label: 'Database Users & Privileges' }
                ]"
            >
                <template #actions>
                    <Link
                        :href="route('admin.databases.index')"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowLeftIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Databases Overview</span>
                    </Link>

                    <button 
                        type="button" 
                        @click="openCreateModal"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <PlusIcon class="w-3.5 h-3.5" />
                        <span>Create DB User</span>
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
                    title="Database Users"
                    :value="String(stats.total_users || users.length || 0)"
                    badge="Accounts"
                    badgeType="info"
                    color="blue"
                    :icon="UserGroupIcon"
                />

                <InfoCard
                    title="Localhost Accounts"
                    :value="String(stats.localhost_users || 0)"
                    badge="Local"
                    badgeType="success"
                    color="emerald"
                    :icon="LockClosedIcon"
                />

                <InfoCard
                    title="Remote Allowed Hosts"
                    :value="String(stats.remote_users || 0)"
                    badge="Remote"
                    badgeType="warning"
                    color="amber"
                    :icon="GlobeAltIcon"
                />

                <InfoCard
                    title="Admin Privileges"
                    :value="String(stats.admin_users || 0)"
                    badge="Root Access"
                    badgeType="info"
                    color="purple"
                    :icon="ShieldCheckIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search database user, host, or privilege level..."
                @search="() => {}"
                @filter="() => {}"
                @reset="resetFilters"
            >
                <FilterSelect
                    v-model="selectedEngineFilter"
                    label="Database Engine"
                    :options="[
                        { label: 'All Database Engines', value: 'all' },
                        { label: 'MySQL / MariaDB', value: 'mysql' },
                        { label: 'PostgreSQL', value: 'postgres' }
                    ]"
                    placeholder="All Engines"
                />

                <FilterSelect
                    v-model="selectedHostFilter"
                    label="Host Access Scope"
                    :options="[
                        { label: 'All Host Scopes', value: 'all' },
                        { label: 'Localhost Only', value: 'local' },
                        { label: 'Remote Allowed (%)', value: 'remote' }
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
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Database Username</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Engine</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Allowed Host Access</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Assigned Databases</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Privileges</th>
                                <th class="py-2.5 px-3 w-28">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(user, index) in filteredUsers" :key="user.id || index" class="hover:bg-blue-50/30 transition">
                                <!-- # -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>

                                <!-- Username -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs border border-blue-100 shrink-0">
                                            <UserGroupIcon class="w-3.5 h-3.5" />
                                        </div>
                                        <span class="font-bold text-slate-900 font-mono leading-tight">{{ user.username }}</span>
                                    </div>
                                </td>

                                <!-- Engine -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="user.engine === 'postgres' ? 'bg-purple-50 text-purple-700 border-purple-200' : 'bg-blue-50 text-blue-700 border-blue-200'"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ user.engine || 'mysql' }}
                                    </span>
                                </td>

                                <!-- Host -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap">
                                    <span 
                                        :class="user.host === '%' ? 'text-amber-700 font-bold bg-amber-50 border border-amber-200' : 'text-slate-700 font-mono bg-slate-50 border border-slate-200'"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px]"
                                    >
                                        {{ user.host || 'localhost' }}
                                    </span>
                                </td>

                                <!-- Databases -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left font-mono text-[10.5px] text-slate-600 max-w-xs truncate" :title="user.databases?.join(', ') || 'All'">
                                    {{ user.databases?.join(', ') || 'All Catalogs' }}
                                </td>

                                <!-- Privileges -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase bg-slate-100 text-slate-700 border border-slate-200 font-mono">
                                        {{ user.privileges_label || 'ALL PRIVILEGES' }}
                                    </span>
                                </td>

                                <!-- Action -->
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1">
                                        <button 
                                            type="button" 
                                            @click="openPasswordModal(user)"
                                            class="px-2 py-1 bg-white hover:bg-slate-50 text-blue-700 font-bold rounded-[3px] text-xs border border-slate-200 shadow-2xs transition cursor-pointer"
                                            title="Change Password"
                                        >
                                            Password 🔑
                                        </button>

                                        <button 
                                            v-if="user.engine !== 'postgres'"
                                            type="button" 
                                            @click="toggleRemote(user)"
                                            class="px-2 py-1 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs border border-slate-200 shadow-2xs transition cursor-pointer"
                                            :title="user.is_remote ? 'Revoke Remote Host' : 'Allow Remote Host (%)'"
                                        >
                                            {{ user.is_remote ? 'Revoke %' : 'Remote %' }}
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!filteredUsers || filteredUsers.length === 0">
                                <td colspan="7" class="py-12 text-center text-slate-400">
                                    No database users found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 1. CREATE USER MODAL -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <PlusIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Create Database User
                        </h3>
                    </div>
                    <button @click="showCreateModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitCreate" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Username <span class="text-rose-500">*</span></label>
                        <input v-model="createForm.username" type="text" required placeholder="e.g. db_user" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="space-y-1">
                        <div class="flex items-center justify-between">
                            <label class="font-bold text-slate-700">Password <span class="text-rose-500">*</span></label>
                            <button type="button" @click="generatePassword" class="text-[10px] text-blue-600 font-bold hover:underline cursor-pointer">Generate</button>
                        </div>
                        <input v-model="createForm.password" type="text" required class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Allowed Host</label>
                        <select v-model="createForm.host" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                            <option value="localhost">localhost (Secure / Default)</option>
                            <option value="%">% (Any Remote IP)</option>
                        </select>
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
                            {{ createForm.processing ? 'Creating...' : 'Create User' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 2. PASSWORD MODAL -->
        <div v-if="showPasswordModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <KeyIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Change Password: {{ selectedUser?.username }}
                        </h3>
                    </div>
                    <button @click="showPasswordModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitPassword" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">New Password <span class="text-rose-500">*</span></label>
                        <input v-model="passwordForm.new_password" type="password" required placeholder="••••••••" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
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
    </AdminLayout>
</template>
