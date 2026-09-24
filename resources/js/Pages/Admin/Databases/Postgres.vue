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
    PlusIcon,
    ServerIcon,
    ArrowDownTrayIcon,
    WrenchIcon,
    KeyIcon,
    BoltIcon,
    CommandLineIcon,
    ShieldCheckIcon,
    XMarkIcon,
    UserGroupIcon,
    CpuChipIcon,
    CheckIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    telemetry: {
        type: Object,
        default: () => ({
            is_running: true,
            version: 'PostgreSQL 16',
            uptime: 'Online',
            active_connections: 1,
            max_connections: 100,
            shared_buffers: '128MB',
            total_databases: 0,
        }),
    },
    databases: {
        type: Array,
        default: () => [],
    },
    roles: {
        type: Array,
        default: () => [],
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
const activeTab = ref('instances') // 'instances' | 'roles' | 'processes' | 'telemetry'

// Search & Filter
const search = ref('')

const filteredDatabases = computed(() => {
    let list = props.databases || []
    if (search.value.trim()) {
        const q = search.value.toLowerCase()
        list = list.filter(db =>
            db.name.toLowerCase().includes(q) ||
            (db.owner || '').toLowerCase().includes(q) ||
            (db.subscription?.domain || '').toLowerCase().includes(q) ||
            (db.subscription?.user?.name || '').toLowerCase().includes(q)
        )
    }
    return list
})

const filteredRoles = computed(() => {
    let list = props.roles || []
    if (search.value.trim()) {
        const q = search.value.toLowerCase()
        list = list.filter(r => r.username.toLowerCase().includes(q))
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
            (p.query || '').toLowerCase().includes(q)
        )
    }
    return list
})

// 1. CREATE DATABASE MODAL
const showCreateDbModal = ref(false)
const createDbForm = useForm({
    subscription_id: props.subscriptions[0]?.id || '',
    name: '',
    owner: '',
    encoding: 'UTF8',
    create_role: true,
    role_password: '',
})

const openCreateDbModal = () => {
    createDbForm.reset()
    createDbForm.subscription_id = props.subscriptions[0]?.id || ''
    showCreateDbModal.value = true
}

const submitCreateDb = () => {
    createDbForm.post(route('admin.databases.postgres.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateDbModal.value = false
            feedbackMsg.value = `PostgreSQL database '${createDbForm.name}' created.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 2. CREATE ROLE MODAL
const showCreateRoleModal = ref(false)
const createRoleForm = useForm({
    engine: 'postgres',
    username: '',
    password: '',
    host: 'localhost',
    database_name: '',
    privileges: 'all',
})

const submitCreateRole = () => {
    createRoleForm.post(route('admin.databases.users.create'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateRoleModal.value = false
            feedbackMsg.value = `Role '${createRoleForm.username}' created.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 3. CHANGE ROLE PASSWORD
const showPasswordModal = ref(false)
const selectedRole = ref(null)
const passwordForm = useForm({
    engine: 'postgres',
    username: '',
    new_password: '',
})

const openPasswordModal = (role) => {
    selectedRole.value = role
    passwordForm.username = role.username
    passwordForm.new_password = ''
    showPasswordModal.value = true
}

const submitChangePassword = () => {
    if (!selectedRole.value) return
    passwordForm.post(route('admin.databases.postgres.password'), {
        preserveScroll: true,
        onSuccess: () => {
            showPasswordModal.value = false
            feedbackMsg.value = `Password for role '${selectedRole.value.username}' updated.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 4. DELETE DATABASE
const showDeleteDbModal = ref(false)
const dbToDelete = ref(null)
const deleteDbForm = useForm({
    name: '',
})

const openDeleteDbModal = (db) => {
    dbToDelete.value = db
    deleteDbForm.name = db.name
    showDeleteDbModal.value = true
}

const submitDeleteDb = () => {
    if (!dbToDelete.value) return
    deleteDbForm.post(route('admin.databases.postgres.destroy'), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteDbModal.value = false
            feedbackMsg.value = `Database '${dbToDelete.value.name}' dropped.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 5. VACUUM / ANALYZE
const vacuumDb = (db) => {
    useForm({ database_id: db.id }).post(route('admin.databases.postgres.vacuum'), {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = `Database '${db.name}' vacuumed & analyzed.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 6. TERMINATE BACKEND
const terminateBackend = (pid) => {
    if (confirm(`Terminate backend connection PID #${pid}?`)) {
        useForm({}).post(route('admin.databases.postgres.kill', pid), {
            preserveScroll: true,
            onSuccess: () => {
                feedbackMsg.value = `Backend PID #${pid} terminated.`
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    }
}
</script>

<template>
    <Head title="PostgreSQL Management - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Services', href: '#' },
                    { label: 'Databases', href: route('admin.databases.index') },
                    { label: 'PostgreSQL Management' }
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
                        @click="openCreateDbModal"
                        class="px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <PlusIcon class="w-3.5 h-3.5" />
                        <span>Create PostgreSQL DB</span>
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
                    title="PostgreSQL Databases"
                    :value="String(databases.length || 0)"
                    badge="Catalogs"
                    badgeType="info"
                    color="purple"
                    :icon="CircleStackIcon"
                />

                <InfoCard
                    title="Database Roles"
                    :value="String(roles.length || 0)"
                    badge="Users"
                    badgeType="success"
                    color="emerald"
                    :icon="UserGroupIcon"
                />

                <InfoCard
                    title="Active Backends"
                    :value="`${telemetry.active_connections || 1} / ${telemetry.max_connections || 100}`"
                    badge="Connections"
                    badgeType="info"
                    color="sky"
                    :icon="CpuChipIcon"
                />

                <InfoCard
                    title="Engine Version"
                    :value="telemetry.version || 'PostgreSQL 16'"
                    badge="Online"
                    badgeType="success"
                    color="blue"
                    :icon="ServerIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Navigation Tabs Bar -->
            <div class="bg-white p-1 rounded-[4px] border border-[#E2E8F0] shadow-2xs flex flex-wrap items-center gap-1">
                <button
                    type="button"
                    @click="activeTab = 'instances'"
                    :class="activeTab === 'instances' ? 'bg-purple-600 text-white font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50 font-medium'"
                    class="px-3 py-1.5 rounded-[3px] text-xs flex items-center gap-1.5 transition cursor-pointer"
                >
                    <CircleStackIcon class="w-3.5 h-3.5" />
                    <span>PostgreSQL Databases</span>
                    <span :class="activeTab === 'instances' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-500 font-mono'" class="px-1.5 py-0.2 rounded-full text-[9.5px] font-bold">
                        {{ databases.length }}
                    </span>
                </button>

                <button
                    type="button"
                    @click="activeTab = 'roles'"
                    :class="activeTab === 'roles' ? 'bg-purple-600 text-white font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50 font-medium'"
                    class="px-3 py-1.5 rounded-[3px] text-xs flex items-center gap-1.5 transition cursor-pointer"
                >
                    <UserGroupIcon class="w-3.5 h-3.5" />
                    <span>Roles & Users</span>
                    <span :class="activeTab === 'roles' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-500 font-mono'" class="px-1.5 py-0.2 rounded-full text-[9.5px] font-bold">
                        {{ roles.length }}
                    </span>
                </button>

                <button
                    type="button"
                    @click="activeTab = 'processes'"
                    :class="activeTab === 'processes' ? 'bg-purple-600 text-white font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50 font-medium'"
                    class="px-3 py-1.5 rounded-[3px] text-xs flex items-center gap-1.5 transition cursor-pointer"
                >
                    <CommandLineIcon class="w-3.5 h-3.5" />
                    <span>Backend Connections</span>
                    <span :class="activeTab === 'processes' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-500 font-mono'" class="px-1.5 py-0.2 rounded-full text-[9.5px] font-bold">
                        {{ processlist.length }}
                    </span>
                </button>

                <button
                    type="button"
                    @click="activeTab = 'telemetry'"
                    :class="activeTab === 'telemetry' ? 'bg-purple-600 text-white font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50 font-medium'"
                    class="px-3 py-1.5 rounded-[3px] text-xs flex items-center gap-1.5 transition cursor-pointer"
                >
                    <CpuChipIcon class="w-3.5 h-3.5" />
                    <span>Engine Telemetry</span>
                </button>
            </div>

            <!-- 1. TAB: PostgreSQL Databases -->
            <div v-show="activeTab === 'instances'" class="space-y-3.5">
                <DataTableFilter
                    v-model="search"
                    searchPlaceholder="Search PostgreSQL database name, owner, or domain..."
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
                                    <th class="py-2.5 px-3 border-r border-slate-200 text-left">Associated Domain / Account</th>
                                    <th class="py-2.5 px-3 border-r border-slate-200">Database Size</th>
                                    <th class="py-2.5 px-3 border-r border-slate-200 text-left">Role / Owner</th>
                                    <th class="py-2.5 px-3 border-r border-slate-200">Encoding</th>
                                    <th class="py-2.5 px-3 w-28">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                                <tr v-for="(db, index) in filteredDatabases" :key="db.id || index" class="hover:bg-purple-50/30 transition">
                                    <!-- # -->
                                    <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                        {{ index + 1 }}
                                    </td>

                                    <!-- Name -->
                                    <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <div class="w-6 h-6 rounded-[3px] bg-purple-50 text-purple-600 flex items-center justify-center font-bold text-xs border border-purple-100 shrink-0">
                                                <CircleStackIcon class="w-3.5 h-3.5" />
                                            </div>
                                            <span class="font-bold text-slate-900 font-mono leading-tight">{{ db.name }}</span>
                                        </div>
                                    </td>

                                    <!-- Domain -->
                                    <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap">
                                        <div v-if="db.subscription" class="space-y-0.5">
                                            <span class="font-bold text-slate-900 block leading-tight">{{ db.subscription.domain }}</span>
                                            <span class="text-[10px] font-mono text-slate-400 block">@{{ db.subscription.username }}</span>
                                        </div>
                                        <span v-else class="text-slate-400 italic">Cluster Wide</span>
                                    </td>

                                    <!-- Size -->
                                    <td class="py-2.5 px-3 border-r border-slate-100 font-mono font-bold text-slate-900 whitespace-nowrap text-center">
                                        {{ db.size_formatted || `${db.size_mb || 0} MB` }}
                                    </td>

                                    <!-- Owner -->
                                    <td class="py-2.5 px-3 border-r border-slate-100 text-left font-mono text-[11px] text-slate-600 whitespace-nowrap">
                                        {{ db.owner || db.db_user || 'postgres' }}
                                    </td>

                                    <!-- Encoding -->
                                    <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[10.5px] text-slate-500">
                                        {{ db.encoding || 'UTF8' }}
                                    </td>

                                    <!-- Action -->
                                    <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                        <div class="inline-flex items-center gap-1">
                                            <button 
                                                type="button" 
                                                @click="vacuumDb(db)"
                                                class="px-2 py-1 bg-white hover:bg-slate-50 text-purple-700 font-bold rounded-[3px] text-xs border border-slate-200 shadow-2xs transition cursor-pointer"
                                                title="Vacuum & Analyze"
                                            >
                                                Vacuum ⚙
                                            </button>

                                            <RowActionDropdown>
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
                                        No PostgreSQL databases found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 2. TAB: Roles & Users -->
            <div v-show="activeTab === 'roles'" class="space-y-3.5">
                <div class="flex items-center justify-between gap-3">
                    <DataTableFilter
                        v-model="search"
                        searchPlaceholder="Search PostgreSQL role or username..."
                        @search="() => {}"
                        @filter="() => {}"
                        @reset="search = ''"
                        class="flex-1"
                    />

                    <button 
                        type="button" 
                        @click="showCreateRoleModal = true"
                        class="px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer shrink-0"
                    >
                        <PlusIcon class="w-3.5 h-3.5" />
                        <span>Create Role</span>
                    </button>
                </div>

                <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                            <thead>
                                <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                    <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                    <th class="py-2.5 px-3 border-r border-slate-200 text-left">Role Name</th>
                                    <th class="py-2.5 px-3 border-r border-slate-200">Can Login</th>
                                    <th class="py-2.5 px-3 border-r border-slate-200">Superuser</th>
                                    <th class="py-2.5 px-3 border-r border-slate-200">Connection Limit</th>
                                    <th class="py-2.5 px-3 w-28">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                                <tr v-for="(role, idx) in filteredRoles" :key="role.username || idx" class="hover:bg-purple-50/30 transition">
                                    <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">{{ idx + 1 }}</td>
                                    <td class="py-2.5 px-3 border-r border-slate-100 text-left font-mono font-bold text-slate-900 whitespace-nowrap">
                                        {{ role.username }}
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                        <span :class="role.can_login ? 'text-emerald-600 font-bold' : 'text-slate-400'">
                                            {{ role.can_login ? 'Yes' : 'No' }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                        <span :class="role.is_superuser ? 'text-purple-700 font-bold' : 'text-slate-400'">
                                            {{ role.is_superuser ? 'Superuser' : 'Standard' }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-slate-100 font-mono text-slate-600 text-center whitespace-nowrap">
                                        {{ role.conn_limit ?? '-1 (Unlimited)' }}
                                    </td>
                                    <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                        <button 
                                            type="button" 
                                            @click="openPasswordModal(role)"
                                            class="px-2 py-1 bg-white hover:bg-slate-50 text-purple-700 font-bold rounded-[3px] text-xs border border-slate-200 shadow-2xs transition cursor-pointer"
                                        >
                                            Password 🔑
                                        </button>
                                    </td>
                                </tr>

                                <tr v-if="!filteredRoles || filteredRoles.length === 0">
                                    <td colspan="6" class="py-12 text-center text-slate-400">
                                        No PostgreSQL roles found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 3. TAB: Backend Connections -->
            <div v-show="activeTab === 'processes'" class="space-y-3.5">
                <DataTableFilter
                    v-model="search"
                    searchPlaceholder="Search backend query, PID, user, or db..."
                    @search="() => {}"
                    @filter="() => {}"
                    @reset="search = ''"
                />

                <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                            <thead>
                                <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                    <th class="py-2.5 px-3 border-r border-slate-200 w-16">PID</th>
                                    <th class="py-2.5 px-3 border-r border-slate-200 text-left">Role User</th>
                                    <th class="py-2.5 px-3 border-r border-slate-200 text-left">Client Address</th>
                                    <th class="py-2.5 px-3 border-r border-slate-200">Database</th>
                                    <th class="py-2.5 px-3 border-r border-slate-200">State</th>
                                    <th class="py-2.5 px-3 text-left">Current SQL Execution</th>
                                    <th class="py-2.5 px-3 w-16">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                                <tr v-for="p in filteredProcesses" :key="p.pid" class="hover:bg-purple-50/30 transition">
                                    <td class="py-2.5 px-3 border-r border-slate-100 font-mono font-bold text-slate-900 whitespace-nowrap text-center">
                                        #{{ p.pid }}
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-slate-100 text-left font-mono font-bold text-slate-800 whitespace-nowrap">
                                        {{ p.user }}
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-slate-100 text-left font-mono text-[10.5px] text-slate-500 whitespace-nowrap">
                                        {{ p.client_addr || 'local' }}
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-slate-100 font-mono text-[11px] text-purple-700 whitespace-nowrap text-center">
                                        {{ p.db || 'postgres' }}
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-slate-100 text-slate-500 text-[10.5px] whitespace-nowrap text-center">
                                        {{ p.state || 'active' }}
                                    </td>
                                    <td class="py-2.5 px-3 text-left font-mono text-[10.5px] text-slate-800 max-w-md truncate" :title="p.query">
                                        {{ p.query || 'idle' }}
                                    </td>
                                    <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                        <button
                                            type="button"
                                            @click="terminateBackend(p.pid)"
                                            class="px-2 py-0.5 bg-white hover:bg-rose-50 text-rose-600 border border-rose-200 rounded-[2px] text-[10px] font-bold transition shadow-2xs cursor-pointer"
                                        >
                                            Terminate
                                        </button>
                                    </td>
                                </tr>

                                <tr v-if="!filteredProcesses || filteredProcesses.length === 0">
                                    <td colspan="7" class="py-12 text-center text-slate-400">
                                        No active PostgreSQL backend connections running.
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
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">PostgreSQL Engine Internal Telemetry</h3>
                        <p class="text-[11px] text-slate-400 font-mono">{{ telemetry.version || 'PostgreSQL 16' }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                    <div class="p-3 rounded-[3px] bg-slate-50 border border-slate-200 space-y-1">
                        <span class="text-[10px] font-bold uppercase text-slate-400">Server Status</span>
                        <p class="font-mono font-bold text-emerald-600 text-sm">Online</p>
                    </div>
                    <div class="p-3 rounded-[3px] bg-slate-50 border border-slate-200 space-y-1">
                        <span class="text-[10px] font-bold uppercase text-slate-400">Shared Buffers</span>
                        <p class="font-mono font-bold text-slate-900 text-sm">{{ telemetry.shared_buffers || '128MB' }}</p>
                    </div>
                    <div class="p-3 rounded-[3px] bg-slate-50 border border-slate-200 space-y-1">
                        <span class="text-[10px] font-bold uppercase text-slate-400">Max Connections</span>
                        <p class="font-mono font-bold text-slate-900 text-sm">{{ telemetry.max_connections || 100 }}</p>
                    </div>
                    <div class="p-3 rounded-[3px] bg-slate-50 border border-slate-200 space-y-1">
                        <span class="text-[10px] font-bold uppercase text-slate-400">Active Connections</span>
                        <p class="font-mono font-bold text-purple-700 text-sm">{{ telemetry.active_connections || 1 }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- 1. CREATE DATABASE MODAL -->
        <div v-if="showCreateDbModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-purple-50 text-purple-600 flex items-center justify-center font-bold border border-purple-100">
                            <PlusIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Create PostgreSQL Database
                        </h3>
                    </div>
                    <button @click="showCreateDbModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitCreateDb" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Account Subscription <span class="text-rose-500">*</span></label>
                        <select v-model="createDbForm.subscription_id" required class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-purple-500 cursor-pointer">
                            <option v-for="s in subscriptions" :key="s.id" :value="s.id">
                                {{ s.domain }} (@{{ s.username }})
                            </option>
                        </select>
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Database Name <span class="text-rose-500">*</span></label>
                        <input v-model="createDbForm.name" type="text" required placeholder="e.g. pg_app" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-purple-500" />
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Owner Role Username</label>
                        <input v-model="createDbForm.owner" type="text" placeholder="e.g. pg_user" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-purple-500" />
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Password</label>
                        <input v-model="createDbForm.role_password" type="password" placeholder="••••••••" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-purple-500" />
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
                            class="px-3.5 py-1.5 bg-purple-600 hover:bg-purple-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ createDbForm.processing ? 'Creating...' : 'Create Database' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 2. CREATE ROLE MODAL -->
        <div v-if="showCreateRoleModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-purple-50 text-purple-600 flex items-center justify-center font-bold border border-purple-100">
                            <PlusIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Create PostgreSQL Role
                        </h3>
                    </div>
                    <button @click="showCreateRoleModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitCreateRole" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Role Username <span class="text-rose-500">*</span></label>
                        <input v-model="createRoleForm.username" type="text" required placeholder="e.g. pg_role" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-purple-500" />
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Password <span class="text-rose-500">*</span></label>
                        <input v-model="createRoleForm.password" type="password" required placeholder="••••••••" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-purple-500" />
                    </div>

                    <div class="px-4 py-2.5 -mx-4 -mb-4 mt-4 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                        <button
                            type="button"
                            @click="showCreateRoleModal = false"
                            class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="createRoleForm.processing"
                            class="px-3.5 py-1.5 bg-purple-600 hover:bg-purple-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ createRoleForm.processing ? 'Creating...' : 'Create Role' }}
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
                        <div class="w-6 h-6 rounded-[3px] bg-purple-50 text-purple-600 flex items-center justify-center font-bold border border-purple-100">
                            <KeyIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Change Role Password: {{ selectedRole?.username }}
                        </h3>
                    </div>
                    <button @click="showPasswordModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitChangePassword" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">New Password <span class="text-rose-500">*</span></label>
                        <input v-model="passwordForm.new_password" type="password" required placeholder="••••••••" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-purple-500" />
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
                            class="px-3.5 py-1.5 bg-purple-600 hover:bg-purple-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
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
                            Drop PostgreSQL Database
                        </h3>
                    </div>
                    <button @click="showDeleteDbModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-2.5 text-xs">
                    <p class="text-slate-600">
                        Are you sure you want to permanently drop PostgreSQL database <strong class="text-slate-900 font-mono">[{{ dbToDelete?.name }}]</strong>?
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
