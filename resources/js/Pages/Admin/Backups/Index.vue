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
    ArchiveBoxIcon,
    ArrowDownTrayIcon,
    ArrowPathIcon,
    CheckIcon,
    ClockIcon,
    CircleStackIcon,
    ShieldCheckIcon,
    PlusIcon,
    TrashIcon,
    XMarkIcon,
    ServerIcon,
    FolderIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    backups: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    stats: {
        type: Object,
        default: () => ({
            total_backups: 0,
            completed_count: 0,
            failed_count: 0,
            total_size_formatted: '0 B',
            success_rate: 100,
            last_backup_time: 'Never',
            backup_directory: '/var/backups/deeptouchhost',
            driver: 'Local Node Storage',
        }),
    },
    subscriptions: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({ search: '', type: '', status: '' }),
    },
})

// Search & Filter
const search = ref(props.filters?.search || '')
const type = ref(props.filters?.type || '')
const status = ref(props.filters?.status || '')
const feedbackMsg = ref('')

const applyFilters = () => {
    router.get(route('admin.backups.index'), {
        search: search.value || undefined,
        type: type.value || undefined,
        status: status.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const resetFilters = () => {
    search.value = ''
    type.value = ''
    status.value = ''
    applyFilters()
}

// 1. CREATE BACKUP MODAL
const showCreateModal = ref(false)
const createForm = useForm({
    subscription_id: '',
    name: '',
    type: 'full',
})

const openCreateModal = () => {
    createForm.reset()
    createForm.type = 'full'
    createForm.subscription_id = ''
    showCreateModal.value = true
}

const submitCreate = () => {
    createForm.post(route('admin.backups.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false
            feedbackMsg.value = `Backup task dispatched and generated successfully.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 2. RESTORE SNAPSHOT
const restoreBackup = (backup) => {
    if (confirm(`Restore snapshot '${backup.name || backup.filename}'? This will replace existing data.`)) {
        router.post(route('admin.backups.restore.run', backup.id), {}, {
            preserveScroll: true,
            onSuccess: () => {
                feedbackMsg.value = `Snapshot restore initiated in background.`
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    }
}

// 3. DELETE MODAL
const showDeleteModal = ref(false)
const backupToDelete = ref(null)
const deleteForm = useForm({})

const openDeleteModal = (backup) => {
    backupToDelete.value = backup
    showDeleteModal.value = true
}

const submitDelete = () => {
    if (!backupToDelete.value) return
    deleteForm.delete(route('admin.backups.destroy', backupToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false
            feedbackMsg.value = 'Backup archive removed from storage.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}
</script>

<template>
    <Head title="Backups Dashboard - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Security', href: '#' },
                    { label: 'Backups & Snapshots', href: route('admin.backups.index') },
                    { label: 'Backup Archive Catalog' }
                ]"
            >
                <template #actions>
                    <Link
                        :href="route('admin.backups.storage')"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <FolderIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Storage Targets</span>
                    </Link>

                    <button 
                        type="button" 
                        @click="openCreateModal"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <PlusIcon class="w-3.5 h-3.5" />
                        <span>Create Backup Snapshot</span>
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
                    title="Stored Backup Archives"
                    :value="String(stats.total_backups || backups.data?.length || 0)"
                    badge="Snapshots"
                    badgeType="info"
                    color="blue"
                    :icon="ArchiveBoxIcon"
                />

                <InfoCard
                    title="Total Storage Size"
                    :value="stats.total_size_formatted || '0 B'"
                    badge="Compressed"
                    badgeType="info"
                    color="purple"
                    :icon="CircleStackIcon"
                />

                <InfoCard
                    title="Backup Success Rate"
                    :value="`${stats.success_rate || 100}%`"
                    badge="Optimal"
                    badgeType="success"
                    color="emerald"
                    :icon="ShieldCheckIcon"
                />

                <InfoCard
                    title="Last Backup Snapshot"
                    :value="stats.last_backup_time || 'Recent'"
                    badge="Completed"
                    badgeType="info"
                    color="sky"
                    :icon="ClockIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search backup name, filename, domain, or target..."
                @search="applyFilters"
                @filter="applyFilters"
                @reset="resetFilters"
            >
                <FilterSelect
                    v-model="type"
                    label="Backup Scope"
                    :options="[
                        { label: 'All Scopes', value: '' },
                        { label: 'Full System Snapshot', value: 'full' },
                        { label: 'Web Files Only', value: 'files' },
                        { label: 'Databases Only', value: 'database' }
                    ]"
                    placeholder="All Scopes"
                />

                <FilterSelect
                    v-model="status"
                    label="Job Status"
                    :options="[
                        { label: 'All Statuses', value: '' },
                        { label: 'Completed', value: 'completed' },
                        { label: 'Pending / Running', value: 'pending' },
                        { label: 'Failed', value: 'failed' }
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
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Backup Snapshot Name</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Scope Type</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Tenant Account</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Archive Size</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Created Timestamp</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Status</th>
                                <th class="py-2.5 px-3 w-28">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(b, index) in backups.data" :key="b.id || index" class="hover:bg-blue-50/30 transition">
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-bold text-slate-900">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs border border-blue-100 shrink-0">
                                            <ArchiveBoxIcon class="w-3.5 h-3.5" />
                                        </div>
                                        <div>
                                            <span class="block leading-tight font-mono text-xs">{{ b.name || b.filename }}</span>
                                            <span class="text-[10px] text-slate-400 font-mono block">{{ b.filename }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase bg-slate-100 text-slate-700 border border-slate-200 font-mono">
                                        {{ b.type || 'Full' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono text-[11px] text-slate-600">
                                    {{ b.subscription?.domain || 'System Wide' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[11px] font-bold text-slate-800">
                                    {{ b.size_formatted || '0 B' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[10.5px] text-slate-500">
                                    {{ b.created_at ? new Date(b.created_at).toLocaleString() : 'Recent' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="b.status === 'completed' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200'"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ b.status || 'completed' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1">
                                        <a 
                                            :href="route('admin.backups.download', b.id)"
                                            class="px-2 py-1 bg-white hover:bg-slate-50 text-blue-700 font-bold rounded-[3px] text-xs border border-slate-200 shadow-2xs transition inline-flex items-center gap-1"
                                            title="Download Archive"
                                        >
                                            <ArrowDownTrayIcon class="w-3.5 h-3.5" />
                                            <span>Get</span>
                                        </a>

                                        <RowActionDropdown>
                                            <button
                                                type="button"
                                                @click="restoreBackup(b)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <ArrowPathIcon class="w-3.5 h-3.5 text-slate-500" />
                                                <span>Restore Snapshot</span>
                                            </button>

                                            <button
                                                type="button"
                                                @click="openDeleteModal(b)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-rose-600 hover:bg-rose-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <TrashIcon class="w-3.5 h-3.5" />
                                                <span>Delete Backup</span>
                                            </button>
                                        </RowActionDropdown>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!backups.data || backups.data.length === 0">
                                <td colspan="8" class="py-12 text-center text-slate-400 font-sans">
                                    No backup archives found in catalog.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- CREATE BACKUP MODAL -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <ArchiveBoxIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Create Backup Snapshot
                        </h3>
                    </div>
                    <button @click="showCreateModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitCreate" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Backup Label / Name</label>
                        <input v-model="createForm.name" type="text" placeholder="Manual Snapshot - Pre Upgrade" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Target Scope</label>
                        <select v-model="createForm.type" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                            <option value="full">Full Server (Files & Databases)</option>
                            <option value="files">Web Files Only</option>
                            <option value="database">Databases Only</option>
                        </select>
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Tenant Account (Optional)</label>
                        <select v-model="createForm.subscription_id" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                            <option value="">All Accounts (Global Server)</option>
                            <option v-for="sub in subscriptions" :key="sub.id" :value="sub.id">
                                {{ sub.domain }} ({{ sub.user?.name || sub.username }})
                            </option>
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
                            {{ createForm.processing ? 'Generating Snapshot...' : 'Generate Snapshot' }}
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
                            Delete Backup Archive
                        </h3>
                    </div>
                    <button @click="showDeleteModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-2.5 text-xs">
                    <p class="text-slate-600">
                        Are you sure you want to permanently delete archive <strong class="text-slate-900 font-mono">[{{ backupToDelete?.filename }}]</strong>? This will free disk space.
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
                        {{ deleteForm.processing ? 'Deleting...' : 'Delete Archive' }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
