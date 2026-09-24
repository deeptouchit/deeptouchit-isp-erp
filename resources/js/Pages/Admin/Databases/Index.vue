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
    CircleStackIcon,
    TrashIcon,
    ArrowTopRightOnSquareIcon,
    PlusIcon,
    ServerIcon,
    ArrowDownTrayIcon,
    WrenchIcon,
    KeyIcon,
    BoltIcon,
    CommandLineIcon,
    CheckCircleIcon,
    XMarkIcon,
    UserGroupIcon,
    CpuChipIcon,
    CheckIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    databases: {
        type: [Object, Array],
        default: () => ({ data: [] }),
    },
    dbUsers: {
        type: Array,
        default: () => [],
    },
    telemetry: {
        type: Object,
        default: () => ({
            version: 'MySQL 8.0',
            uptime: '0d 0h 0m',
            threads_connected: 1,
            questions: 0,
            slow_queries: 0,
            max_connections: 151,
            innodb_buffer_pool_size: '128 MB',
        }),
    },
    processlist: {
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

// Active Tab
const activeTab = ref('instances') // 'instances' | 'users' | 'processes' | 'telemetry'

// Search & Filter
const search = ref('')

const dbList = computed(() => {
    if (Array.isArray(props.databases)) return props.databases
    if (props.databases?.data && Array.isArray(props.databases.data)) return props.databases.data
    return []
})

const filteredDatabases = computed(() => {
    let list = dbList.value
    if (search.value.trim()) {
        const q = search.value.toLowerCase()
        list = list.filter(db =>
            db.name.toLowerCase().includes(q) ||
            (db.db_user || '').toLowerCase().includes(q) ||
            (db.subscription?.domain || '').toLowerCase().includes(q) ||
            (db.subscription?.user?.name || '').toLowerCase().includes(q)
        )
    }
    return list
})

const filteredProcesses = computed(() => {
    let list = props.processlist || []
    if (search.value.trim()) {
        const q = search.value.toLowerCase()
        list = list.filter(p =>
            (p.user || '').toLowerCase().includes(q) ||
            (p.db || '').toLowerCase().includes(q) ||
            (p.command || '').toLowerCase().includes(q) ||
            (p.info || '').toLowerCase().includes(q)
        )
    }
    return list
})

const filteredDbUsers = computed(() => {
    let list = props.dbUsers || []
    if (search.value.trim()) {
        const q = search.value.toLowerCase()
        list = list.filter(u =>
            u.username.toLowerCase().includes(q) ||
            (u.host || '').toLowerCase().includes(q)
        )
    }
    return list
})

// 1. CREATE DATABASE MODAL
const showCreateDbModal = ref(false)
const createDbForm = useForm({
    subscription_id: '',
    name: '',
    db_user: '',
    db_password: '',
    charset: 'utf8mb4',
    collation: 'utf8mb4_unicode_ci',
})

const openCreateDbModal = () => {
    createDbForm.reset()
    createDbForm.subscription_id = props.subscriptions[0]?.id || ''
    showCreateDbModal.value = true
}

const submitCreateDb = () => {
    createDbForm.post(route('admin.databases.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateDbModal.value = false
            feedbackMsg.value = `Database '${createDbForm.name}' created successfully.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 2. CREATE USER MODAL
const showCreateUserModal = ref(false)
const createUserForm = useForm({
    engine: 'mysql',
    username: '',
    password: '',
    host: 'localhost',
    database_name: '',
    privileges: 'all',
})

const openCreateUserModal = () => {
    createUserForm.reset()
    showCreateUserModal.value = true
}

const submitCreateUser = () => {
    createUserForm.post(route('admin.databases.users.create'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateUserModal.value = false
            feedbackMsg.value = `Database user '${createUserForm.username}' created successfully.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 3. CHANGE PASSWORD MODAL
const showPasswordModal = ref(false)
const selectedUserForPassword = ref(null)
const passwordForm = useForm({
    engine: 'mysql',
    username: '',
    host: 'localhost',
    new_password: '',
})

const openPasswordModal = (user) => {
    selectedUserForPassword.value = user
    passwordForm.username = user.username
    passwordForm.host = user.host || 'localhost'
    passwordForm.new_password = ''
    showPasswordModal.value = true
}

const submitChangePassword = () => {
    if (!selectedUserForPassword.value) return
    passwordForm.post(route('admin.databases.users.password'), {
        preserveScroll: true,
        onSuccess: () => {
            showPasswordModal.value = false
            feedbackMsg.value = `Password for user '${selectedUserForPassword.value.username}' updated.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 4. DELETE DATABASE
const showDeleteDbModal = ref(false)
const dbToDelete = ref(null)
const deleteDbForm = useForm({})

const openDeleteDbModal = (db) => {
    dbToDelete.value = db
    showDeleteDbModal.value = true
}

const submitDeleteDb = () => {
    if (!dbToDelete.value) return
    deleteDbForm.delete(route('admin.databases.destroy', dbToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteDbModal.value = false
            feedbackMsg.value = `Database '${dbToDelete.value.name}' dropped.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 5. KILL THREAD
const killThread = (threadId) => {
    if (confirm(`Terminate SQL thread ID #${threadId}?`)) {
        useForm({}).post(route('admin.databases.kill', threadId), {
            preserveScroll: true,
            onSuccess: () => {
                feedbackMsg.value = `SQL Thread #${threadId} terminated.`
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    }
}

// 6. CHECK / OPTIMIZE
const optimizeDatabase = (db) => {
    useForm({}).post(route('admin.databases.repair', db.id), {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = `Database '${db.name}' tables checked & optimized.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 7. BACKUP SQL DUMP
const backupDatabase = (db) => {
    window.open(route('admin.databases.export', db.id), '_blank')
}
</script>

<template>
    <Head title="Databases & SQL Engines - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Services', href: '#' },
                    { label: 'Databases' }
                ]"
            >
                <template #actions>
                    <a
                        :href="route('admin.databases.sso')"
                        target="_blank"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowTopRightOnSquareIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>phpMyAdmin SSO</span>
                    </a>

                    <button 
                        type="button" 
                        @click="openCreateDbModal"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <PlusIcon class="w-3.5 h-3.5" />
                        <span>Create Database</span>
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
                    title="Managed Databases"
                    :value="String(dbList.length || 0)"
                    badge="Catalogs"
                    badgeType="info"
                    color="blue"
                    :icon="CircleStackIcon"
                />

                <InfoCard
                    title="Database Users"
                    :value="String(dbUsers.length || 0)"
                    badge="Accounts"
                    badgeType="success"
                    color="emerald"
                    :icon="UserGroupIcon"
                />

                <InfoCard
                    title="Active SQL Threads"
                    :value="`${telemetry.threads_connected || 1} / ${telemetry.max_connections || 151}`"
                    badge="Threads"
                    badgeType="info"
                    color="sky"
                    :icon="CpuChipIcon"
                />

                <InfoCard
                    title="Engine Health"
                    :value="telemetry.version || 'MySQL 8.0'"
                    badge="Healthy"
                    badgeType="success"
                    color="purple"
                    :icon="ServerIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Navigation Tabs Bar -->
            <div class="bg-white p-1 rounded-[4px] border border-[#E2E8F0] shadow-2xs flex flex-wrap items-center gap-1">
                <button
                    type="button"
                    @click="activeTab = 'instances'"
                    :class="activeTab === 'instances' ? 'bg-blue-600 text-white font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50 font-medium'"
                    class="px-3 py-1.5 rounded-[3px] text-xs flex items-center gap-1.5 transition cursor-pointer"
                >
                    <CircleStackIcon class="w-3.5 h-3.5" />
                    <span>Databases Catalog</span>
                    <span :class="activeTab === 'instances' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-500 font-mono'" class="px-1.5 py-0.2 rounded-full text-[9.5px] font-bold">
                        {{ dbList.length }}
                    </span>
                </button>

                <button
                    type="button"
                    @click="activeTab = 'users'"
                    :class="activeTab === 'users' ? 'bg-blue-600 text-white font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50 font-medium'"
                    class="px-3 py-1.5 rounded-[3px] text-xs flex items-center gap-1.5 transition cursor-pointer"
                >
                    <UserGroupIcon class="w-3.5 h-3.5" />
                    <span>Database Users</span>
                    <span :class="activeTab === 'users' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-500 font-mono'" class="px-1.5 py-0.2 rounded-full text-[9.5px] font-bold">
                        {{ dbUsers.length }}
                    </span>
                </button>

                <button
                    type="button"
                    @click="activeTab = 'processes'"
                    :class="activeTab === 'processes' ? 'bg-blue-600 text-white font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50 font-medium'"
                    class="px-3 py-1.5 rounded-[3px] text-xs flex items-center gap-1.5 transition cursor-pointer"
                >
                    <CommandLineIcon class="w-3.5 h-3.5" />
                    <span>Live SQL Threads</span>
                    <span :class="activeTab === 'processes' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-500 font-mono'" class="px-1.5 py-0.2 rounded-full text-[9.5px] font-bold">
                        {{ processlist.length }}
                    </span>
                </button>

                <button
                    type="button"
                    @click="activeTab = 'telemetry'"
                    :class="activeTab === 'telemetry' ? 'bg-blue-600 text-white font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50 font-medium'"
                    class="px-3 py-1.5 rounded-[3px] text-xs flex items-center gap-1.5 transition cursor-pointer"
                >
                    <CpuChipIcon class="w-3.5 h-3.5" />
                    <span>Engine Telemetry</span>
                </button>
            </div>

            <!-- 1. TAB: Databases Catalog -->
            <div v-show="activeTab === 'instances'" class="space-y-3.5">
                <DataTableFilter
                    v-model="search"
                    searchPlaceholder="Search database name, user, or domain..."
                    @search="() => {}"
                    @filter="() => {}"
                    @reset="search = ''"
                />

                <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                            <thead>
                                <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                    <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                    <th class="py-2.5 px-3 border-r border-slate-200 text-left">Database Name</th>
                                    <th class="py-2.5 px-3 border-r border-slate-200 text-left">Associated Domain / Owner</th>
                                    <th class="py-2.5 px-3 border-r border-slate-200">Database Size</th>
                                    <th class="py-2.5 px-3 border-r border-slate-200 text-left">Privileged User</th>
                                    <th class="py-2.5 px-3 border-r border-slate-200">Collation</th>
                                    <th class="py-2.5 px-3 w-28">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                                <tr v-for="(db, index) in filteredDatabases" :key="db.id || index" class="hover:bg-blue-50/30 transition">
                                    <!-- # -->
                                    <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                        {{ index + 1 }}
                                    </td>

                                    <!-- Database Name -->
                                    <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs border border-blue-100 shrink-0">
                                                <CircleStackIcon class="w-3.5 h-3.5" />
                                            </div>
                                            <span class="font-bold text-slate-900 font-mono leading-tight">{{ db.name }}</span>
                                        </div>
                                    </td>

                                    <!-- Domain / Owner -->
                                    <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap">
                                        <div v-if="db.subscription" class="space-y-0.5">
                                            <span class="font-bold text-slate-900 block leading-tight">{{ db.subscription.domain }}</span>
                                            <span class="text-[10px] font-mono text-slate-400 block">@{{ db.subscription.username }}</span>
                                        </div>
                                        <span v-else class="text-slate-400 italic">System Scope</span>
                                    </td>

                                    <!-- Size -->
                                    <td class="py-2.5 px-3 border-r border-slate-100 font-mono font-bold text-slate-900 whitespace-nowrap text-center">
                                        {{ db.size_formatted || db.size_mb ? `${db.size_mb} MB` : '0 MB' }}
                                    </td>

                                    <!-- User -->
                                    <td class="py-2.5 px-3 border-r border-slate-100 text-left font-mono text-[11px] text-slate-600 whitespace-nowrap">
                                        {{ db.db_user || db.username || 'root' }}
                                    </td>

                                    <!-- Collation -->
                                    <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[10.5px] text-slate-500">
                                        {{ db.collation || 'utf8mb4_unicode_ci' }}
                                    </td>

                                    <!-- Action -->
                                    <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                        <div class="inline-flex items-center gap-1">
                                            <a
                                                :href="route('admin.databases.sso', { database: db.id })"
                                                target="_blank"
                                                class="px-2 py-1 bg-white hover:bg-slate-50 text-blue-700 font-bold rounded-[3px] text-xs border border-slate-200 shadow-2xs transition cursor-pointer"
                                                title="Open in phpMyAdmin"
                                            >
                                                SQL ↗
                                            </a>

                                            <RowActionDropdown>
                                                <button
                                                    type="button"
                                                    @click="backupDatabase(db)"
                                                    class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                                >
                                                    <ArrowDownTrayIcon class="w-3.5 h-3.5 text-slate-500" />
                                                    <span>Export / Download Dump</span>
                                                </button>

                                                <button
                                                    type="button"
                                                    @click="optimizeDatabase(db)"
                                                    class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                                >
                                                    <WrenchIcon class="w-3.5 h-3.5 text-slate-500" />
                                                    <span>Optimize & Repair Tables</span>
                                                </button>

                                                <button
                                                    type="button"
                                                    @click="openDeleteDbModal(db)"
                                                    class="w-full text-left px-3 py-1.5 text-xs text-rose-600 hover:bg-rose-50 flex items-center gap-2 font-medium cursor-pointer"
                                                >
                                                    <TrashIcon class="w-3.5 h-3.5" />
                                                    <span>Drop Database</span>
                                                </button>
                                            </RowActionDropdown>
                                        </div>
                                    </td>
                                </tr>

                                <tr v-if="!filteredDatabases || filteredDatabases.length === 0">
                                    <td colspan="7" class="py-12 text-center text-slate-400">
                                        No databases found matching search.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 2. TAB: Database Users -->
            <div v-show="activeTab === 'users'" class="space-y-3.5">
                <div class="flex items-center justify-between gap-3">
                    <DataTableFilter
                        v-model="search"
                        searchPlaceholder="Search database user, host, or account..."
                        @search="() => {}"
                        @filter="() => {}"
                        @reset="search = ''"
                        class="flex-1"
                    />

                    <button 
                        type="button" 
                        @click="openCreateUserModal"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer shrink-0"
                    >
                        <PlusIcon class="w-3.5 h-3.5" />
                        <span>Create DB User</span>
                    </button>
                </div>

                <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                            <thead>
                                <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                    <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                    <th class="py-2.5 px-3 border-r border-slate-200 text-left">Database Username</th>
                                    <th class="py-2.5 px-3 border-r border-slate-200 text-left">Allowed Host</th>
                                    <th class="py-2.5 px-3 border-r border-slate-200 text-left">Status</th>
                                    <th class="py-2.5 px-3 w-28">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                                <tr v-for="(user, idx) in filteredDbUsers" :key="user.username + user.host || idx" class="hover:bg-blue-50/30 transition">
                                    <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">{{ idx + 1 }}</td>
                                    <td class="py-2.5 px-3 border-r border-slate-100 text-left font-mono font-bold text-slate-900 whitespace-nowrap">
                                        {{ user.username }}
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-slate-100 text-left font-mono text-slate-600 text-[11px] whitespace-nowrap">
                                        {{ user.host || 'localhost' }}
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap">
                                        <span :class="user.is_locked ? 'text-rose-600 font-bold' : 'text-emerald-600 font-bold'">
                                            {{ user.is_locked ? 'Locked' : 'Active' }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                        <button 
                                            type="button" 
                                            @click="openPasswordModal(user)"
                                            class="px-2 py-1 bg-white hover:bg-slate-50 text-blue-700 font-bold rounded-[3px] text-xs border border-slate-200 shadow-2xs transition cursor-pointer"
                                            title="Change Password"
                                        >
                                            Password 🔑
                                        </button>
                                    </td>
                                </tr>

                                <tr v-if="!filteredDbUsers || filteredDbUsers.length === 0">
                                    <td colspan="5" class="py-12 text-center text-slate-400">
                                        No database users found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 3. TAB: Live SQL Threads & Processlist -->
            <div v-show="activeTab === 'processes'" class="space-y-3.5">
                <DataTableFilter
                    v-model="search"
                    searchPlaceholder="Search SQL query info, user, or db..."
                    @search="() => {}"
                    @filter="() => {}"
                    @reset="search = ''"
                />

                <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                            <thead>
                                <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                    <th class="py-2.5 px-3 border-r border-slate-200 w-16">Thread ID</th>
                                    <th class="py-2.5 px-3 border-r border-slate-200 text-left">DB User</th>
                                    <th class="py-2.5 px-3 border-r border-slate-200 text-left">Client Host</th>
                                    <th class="py-2.5 px-3 border-r border-slate-200">Database</th>
                                    <th class="py-2.5 px-3 border-r border-slate-200">Command</th>
                                    <th class="py-2.5 px-3 border-r border-slate-200">Time (s)</th>
                                    <th class="py-2.5 px-3 border-r border-slate-200">State</th>
                                    <th class="py-2.5 px-3 text-left">SQL Query Query Execution</th>
                                    <th class="py-2.5 px-3 w-16">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                                <tr v-for="p in filteredProcesses" :key="p.id" class="hover:bg-blue-50/30 transition">
                                    <td class="py-2.5 px-3 border-r border-slate-100 font-mono font-bold text-slate-900 whitespace-nowrap text-center">
                                        #{{ p.id }}
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-slate-100 text-left font-mono font-bold text-slate-800 whitespace-nowrap">
                                        {{ p.user }}
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-slate-100 text-left font-mono text-[10.5px] text-slate-500 whitespace-nowrap">
                                        {{ p.host }}
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-slate-100 font-mono text-[11px] text-blue-700 whitespace-nowrap text-center">
                                        {{ p.db || 'NULL' }}
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-slate-100 font-mono text-[10px] uppercase font-bold text-slate-600 whitespace-nowrap text-center">
                                        {{ p.command }}
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-slate-100 font-mono text-[11px] whitespace-nowrap text-center" :class="p.time > 10 ? 'text-rose-600 font-bold' : 'text-slate-700'">
                                        {{ p.time }}s
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-slate-100 text-slate-500 text-[10.5px] whitespace-nowrap text-center">
                                        {{ p.state || '-' }}
                                    </td>
                                    <td class="py-2.5 px-3 text-left font-mono text-[10.5px] text-slate-800 max-w-md truncate" :title="p.info">
                                        {{ p.info || 'Sleep' }}
                                    </td>
                                    <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                        <button
                                            type="button"
                                            @click="killThread(p.id)"
                                            class="px-2 py-0.5 bg-white hover:bg-rose-50 text-rose-600 border border-rose-200 rounded-[2px] text-[10px] font-bold transition shadow-2xs cursor-pointer"
                                        >
                                            Kill
                                        </button>
                                    </td>
                                </tr>

                                <tr v-if="!filteredProcesses || filteredProcesses.length === 0">
                                    <td colspan="9" class="py-12 text-center text-slate-400">
                                        No active SQL process threads currently running.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 4. TAB: Engine Telemetry -->
            <div v-show="activeTab === 'telemetry'" class="bg-white rounded-[4px] border border-[#E2E8F0] p-4 shadow-2xs space-y-3.5">
                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">Database Engine Internal Telemetry</h3>
                        <p class="text-[11px] text-slate-400 font-mono">{{ telemetry.version || 'MySQL 8.0' }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                    <div class="p-3 rounded-[3px] bg-slate-50 border border-slate-200 space-y-1">
                        <span class="text-[10px] font-bold uppercase text-slate-400">Server Uptime</span>
                        <p class="font-mono font-bold text-slate-900 text-sm">{{ telemetry.uptime }}</p>
                    </div>
                    <div class="p-3 rounded-[3px] bg-slate-50 border border-slate-200 space-y-1">
                        <span class="text-[10px] font-bold uppercase text-slate-400">Queries Processed</span>
                        <p class="font-mono font-bold text-slate-900 text-sm">{{ Number(telemetry.questions || 0).toLocaleString() }}</p>
                    </div>
                    <div class="p-3 rounded-[3px] bg-slate-50 border border-slate-200 space-y-1">
                        <span class="text-[10px] font-bold uppercase text-slate-400">Slow Queries</span>
                        <p class="font-mono font-bold text-slate-900 text-sm" :class="telemetry.slow_queries > 0 ? 'text-amber-600' : 'text-slate-900'">{{ telemetry.slow_queries || 0 }}</p>
                    </div>
                    <div class="p-3 rounded-[3px] bg-slate-50 border border-slate-200 space-y-1">
                        <span class="text-[10px] font-bold uppercase text-slate-400">InnoDB Buffer Pool</span>
                        <p class="font-mono font-bold text-slate-900 text-sm">{{ telemetry.innodb_buffer_pool_size || '128 MB' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- 1. CREATE DATABASE MODAL -->
        <div v-if="showCreateDbModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <PlusIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Create New Database
                        </h3>
                    </div>
                    <button @click="showCreateDbModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitCreateDb" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Account Subscription <span class="text-rose-500">*</span></label>
                        <select v-model="createDbForm.subscription_id" required class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                            <option v-for="s in subscriptions" :key="s.id" :value="s.id">
                                {{ s.domain }} (@{{ s.username }})
                            </option>
                        </select>
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Database Name <span class="text-rose-500">*</span></label>
                        <input v-model="createDbForm.name" type="text" required placeholder="e.g. app_db" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Database User <span class="text-rose-500">*</span></label>
                        <input v-model="createDbForm.db_user" type="text" required placeholder="e.g. app_user" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Database Password <span class="text-rose-500">*</span></label>
                        <input v-model="createDbForm.db_password" type="password" required placeholder="••••••••" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="px-4 py-2.5 -mx-4 -mb-4 mt-4 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                        <button
                            type="button"
                            @click="showCreateDbModal = false"
                            class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="createDbForm.processing"
                            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ createDbForm.processing ? 'Creating...' : 'Create Database' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 2. CREATE USER MODAL -->
        <div v-if="showCreateUserModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
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
                    <button @click="showCreateUserModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitCreateUser" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Username <span class="text-rose-500">*</span></label>
                        <input v-model="createUserForm.username" type="text" required placeholder="e.g. db_user" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Password <span class="text-rose-500">*</span></label>
                        <input v-model="createUserForm.password" type="password" required placeholder="••••••••" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Allowed Host</label>
                        <select v-model="createUserForm.host" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                            <option value="localhost">localhost</option>
                            <option value="%">% (Remote Access)</option>
                        </select>
                    </div>

                    <div class="px-4 py-2.5 -mx-4 -mb-4 mt-4 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                        <button
                            type="button"
                            @click="showCreateUserModal = false"
                            class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="createUserForm.processing"
                            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ createUserForm.processing ? 'Creating...' : 'Create User' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 3. PASSWORD MODAL -->
        <div v-if="showPasswordModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <KeyIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Change Password: {{ selectedUserForPassword?.username }}
                        </h3>
                    </div>
                    <button @click="showPasswordModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitChangePassword" class="p-4 space-y-3 text-xs">
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

        <!-- 4. DELETE DATABASE MODAL -->
        <div v-if="showDeleteDbModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-rose-50 text-rose-600 flex items-center justify-center font-bold border border-rose-100">
                            <TrashIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Drop Database
                        </h3>
                    </div>
                    <button @click="showDeleteDbModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-2.5 text-xs">
                    <p class="text-slate-600">
                        Are you sure you want to permanently drop database <strong class="text-slate-900 font-mono">[{{ dbToDelete?.name }}]</strong>? All tables and data will be deleted immediately.
                    </p>
                </div>

                <div class="px-4 py-2.5 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                    <button
                        type="button"
                        @click="showDeleteDbModal = false"
                        class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="submitDeleteDb"
                        :disabled="deleteDbForm.processing"
                        class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                    >
                        {{ deleteDbForm.processing ? 'Dropping...' : 'Drop Database' }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
