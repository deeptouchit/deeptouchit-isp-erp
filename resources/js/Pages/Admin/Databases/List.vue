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
    ArrowTopRightOnSquareIcon,
    PlusIcon,
    ServerIcon,
    ArrowDownTrayIcon,
    WrenchIcon,
    KeyIcon,
    BoltIcon,
    CheckCircleIcon,
    XMarkIcon,
    CpuChipIcon,
    CheckIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    databases: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            total_databases: 0,
            mysql_count: 0,
            mysql_size_mb: 0,
            postgres_count: 0,
            postgres_size_mb: 0,
            total_size_mb: 0,
        }),
    },
    subscriptions: {
        type: Array,
        default: () => [],
    },
})

// Toast Feedback
const feedbackMsg = ref('')

// Search & Filter
const search = ref('')
const selectedEngineFilter = ref('all')

const filteredDatabases = computed(() => {
    let list = props.databases || []

    if (search.value.trim()) {
        const q = search.value.toLowerCase()
        list = list.filter(db =>
            db.name.toLowerCase().includes(q) ||
            (db.db_user || '').toLowerCase().includes(q) ||
            (db.subscription?.domain || '').toLowerCase().includes(q) ||
            (db.subscription?.user?.name || '').toLowerCase().includes(q)
        )
    }

    if (selectedEngineFilter.value !== 'all') {
        list = list.filter(db => (db.engine || 'mysql') === selectedEngineFilter.value)
    }

    return list
})

const resetFilters = () => {
    search.value = ''
    selectedEngineFilter.value = 'all'
}

// 1. CREATE DATABASE MODAL
const showCreateModal = ref(false)
const targetEngine = ref('mysql')

const createForm = useForm({
    subscription_id: props.subscriptions[0]?.id || '',
    name: '',
    db_user: '',
    db_password: '',
    charset: 'utf8mb4',
    collation: 'utf8mb4_unicode_ci',
    encoding: 'UTF8',
})

const generatePassword = () => {
    const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*'
    let pass = ''
    for (let i = 0; i < 16; i++) {
        pass += chars.charAt(Math.floor(Math.random() * chars.length))
    }
    createForm.db_password = pass
}

const openCreateModal = () => {
    createForm.reset()
    createForm.subscription_id = props.subscriptions[0]?.id || ''
    generatePassword()
    showCreateModal.value = true
}

const submitCreate = () => {
    const endpoint = targetEngine.value === 'postgres' ? route('admin.databases.postgres.store') : route('admin.databases.store')
    createForm.post(endpoint, {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false
            feedbackMsg.value = `Database '${createForm.name}' (${targetEngine.value.toUpperCase()}) created.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 2. DELETE DATABASE MODAL
const showDeleteModal = ref(false)
const dbToDelete = ref(null)
const deleteForm = useForm({})

const openDeleteModal = (db) => {
    dbToDelete.value = db
    showDeleteModal.value = true
}

const submitDelete = () => {
    if (!dbToDelete.value) return
    const endpoint = dbToDelete.value.engine === 'postgres' 
        ? route('admin.databases.postgres.destroy') 
        : route('admin.databases.destroy', dbToDelete.value.id)

    if (dbToDelete.value.engine === 'postgres') {
        useForm({ name: dbToDelete.value.name }).post(endpoint, {
            preserveScroll: true,
            onSuccess: () => {
                showDeleteModal.value = false
                feedbackMsg.value = `Database '${dbToDelete.value.name}' deleted.`
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    } else {
        deleteForm.delete(endpoint, {
            preserveScroll: true,
            onSuccess: () => {
                showDeleteModal.value = false
                feedbackMsg.value = `Database '${dbToDelete.value.name}' deleted.`
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    }
}

// 3. OPTIMIZE TABLES
const optimizeDb = (db) => {
    useForm({}).post(route('admin.databases.repair', db.id), {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = `Database '${db.name}' tables checked & optimized.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 4. EXPORT DUMP
const exportDb = (db) => {
    if (db.engine === 'postgres') {
        window.open(route('admin.databases.postgres.export', db.name), '_blank')
    } else {
        window.open(route('admin.databases.export', db.id), '_blank')
    }
}
</script>

<template>
    <Head title="MySQL & MariaDB Databases - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Services', href: '#' },
                    { label: 'Databases', href: route('admin.databases.index') },
                    { label: 'MySQL & Databases List' }
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
                        @click="openCreateModal"
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
                    title="Total Databases"
                    :value="String(stats.total_databases || databases.length || 0)"
                    badge="Active"
                    badgeType="info"
                    color="blue"
                    :icon="CircleStackIcon"
                />

                <InfoCard
                    title="MySQL / MariaDB"
                    :value="String(stats.mysql_count || 0)"
                    badge="Instances"
                    badgeType="success"
                    color="emerald"
                    :icon="ServerIcon"
                />

                <InfoCard
                    title="PostgreSQL Databases"
                    :value="String(stats.postgres_count || 0)"
                    badge="Relational"
                    badgeType="info"
                    color="purple"
                    :icon="CpuChipIcon"
                />

                <InfoCard
                    title="Total Disk Footprint"
                    :value="`${stats.total_size_mb || 0} MB`"
                    badge="Storage"
                    badgeType="success"
                    color="sky"
                    :icon="BoltIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search database name, privileged user, or domain..."
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
            </DataTableFilter>

            <!-- 4. Standard Data Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Database Name</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Engine Type</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Associated Domain / Owner</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Database Size</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Privileged User</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Collation / Encoding</th>
                                <th class="py-2.5 px-3 w-28">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(db, index) in filteredDatabases" :key="db.id || index" class="hover:bg-blue-50/30 transition">
                                <!-- # -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>

                                <!-- Name -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs border border-blue-100 shrink-0">
                                            <CircleStackIcon class="w-3.5 h-3.5" />
                                        </div>
                                        <span class="font-bold text-slate-900 font-mono leading-tight">{{ db.name }}</span>
                                    </div>
                                </td>

                                <!-- Engine -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="db.engine === 'postgres' ? 'bg-purple-50 text-purple-700 border-purple-200' : 'bg-blue-50 text-blue-700 border-blue-200'"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ db.engine || 'mysql' }}
                                    </span>
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
                                    {{ db.size_formatted || `${db.size_mb || 0} MB` }}
                                </td>

                                <!-- User -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left font-mono text-[11px] text-slate-600 whitespace-nowrap">
                                    {{ db.db_user || db.username || 'root' }}
                                </td>

                                <!-- Collation -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[10.5px] text-slate-500">
                                    {{ db.collation || db.encoding || 'utf8mb4_unicode_ci' }}
                                </td>

                                <!-- Action -->
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1">
                                        <a
                                            v-if="db.engine !== 'postgres'"
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
                                                @click="exportDb(db)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <ArrowDownTrayIcon class="w-3.5 h-3.5 text-slate-500" />
                                                <span>Download Dump (SQL)</span>
                                            </button>

                                            <button
                                                v-if="db.engine !== 'postgres'"
                                                type="button"
                                                @click="optimizeDb(db)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <WrenchIcon class="w-3.5 h-3.5 text-slate-500" />
                                                <span>Check & Optimize Tables</span>
                                            </button>

                                            <button
                                                type="button"
                                                @click="openDeleteModal(db)"
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
                                <td colspan="8" class="py-12 text-center text-slate-400">
                                    No databases found matching search.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 1. CREATE MODAL -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
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
                    <button @click="showCreateModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitCreate" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Database Engine</label>
                        <div class="grid grid-cols-2 gap-2">
                            <button
                                type="button"
                                @click="targetEngine = 'mysql'"
                                :class="targetEngine === 'mysql' ? 'bg-blue-600 text-white font-bold' : 'bg-slate-50 text-slate-700 border border-slate-200'"
                                class="py-1.5 px-3 text-xs rounded-[3px] transition cursor-pointer"
                            >
                                MySQL / MariaDB
                            </button>
                            <button
                                type="button"
                                @click="targetEngine = 'postgres'"
                                :class="targetEngine === 'postgres' ? 'bg-purple-600 text-white font-bold' : 'bg-slate-50 text-slate-700 border border-slate-200'"
                                class="py-1.5 px-3 text-xs rounded-[3px] transition cursor-pointer"
                            >
                                PostgreSQL
                            </button>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Hosting Subscription <span class="text-rose-500">*</span></label>
                        <select v-model="createForm.subscription_id" required class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                            <option v-for="s in subscriptions" :key="s.id" :value="s.id">
                                {{ s.domain }} (@{{ s.username }})
                            </option>
                        </select>
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Database Name <span class="text-rose-500">*</span></label>
                        <input v-model="createForm.name" type="text" required placeholder="e.g. app_db" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Privileged Username</label>
                        <input v-model="createForm.db_user" type="text" placeholder="e.g. db_user" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="space-y-1">
                        <div class="flex items-center justify-between">
                            <label class="font-bold text-slate-700">Password</label>
                            <button type="button" @click="generatePassword" class="text-[10px] text-blue-600 font-bold hover:underline cursor-pointer">Generate</button>
                        </div>
                        <input v-model="createForm.db_password" type="text" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
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
                            {{ createForm.processing ? 'Creating...' : 'Create Database' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 2. DELETE MODAL -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
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
                    <button @click="showDeleteModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-2.5 text-xs">
                    <p class="text-slate-600">
                        Are you sure you want to permanently drop database <strong class="text-slate-900 font-mono">[{{ dbToDelete?.name }}]</strong>?
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
                        {{ deleteForm.processing ? 'Dropping...' : 'Drop Database' }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
