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
    CircleStackIcon,
    ServerIcon,
    ArrowPathIcon,
    PlusIcon,
    PencilSquareIcon,
    TrashIcon,
    XMarkIcon,
    CheckIcon,
    CloudIcon,
    ShieldCheckIcon,
    StarIcon,
    FolderIcon,
    LockClosedIcon,
    BoltIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    storages: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            total_repositories: 0,
            active_repositories: 0,
            total_used_formatted: '0 B',
            default_driver: 'LOCAL',
            default_name: 'Local Storage',
        }),
    },
    filters: {
        type: Object,
        default: () => ({ search: '', driver: '', status: '' }),
    },
})

// Search & Filter
const search = ref(props.filters?.search || '')
const driver = ref(props.filters?.driver || '')
const status = ref(props.filters?.status || '')
const feedbackMsg = ref('')

const filteredStorages = computed(() => {
    let list = props.storages || []
    if (search.value.trim()) {
        const q = search.value.toLowerCase()
        list = list.filter(s =>
            (s.name || '').toLowerCase().includes(q) ||
            (s.driver || '').toLowerCase().includes(q) ||
            (s.path || '').toLowerCase().includes(q)
        )
    }
    if (driver.value) {
        list = list.filter(s => (s.driver || '').toLowerCase() === driver.value.toLowerCase())
    }
    return list
})

// 1. ADD / EDIT STORAGE MODAL
const showModal = ref(false)
const isEditing = ref(false)
const selectedStorage = ref(null)

const form = useForm({
    name: '',
    driver: 's3',
    path: '',
    capacity_gb: 100,
    retention_days: 30,
    is_default: false,
    encryption_enabled: true,
    credentials: {
        key: '',
        secret: '',
        region: 'us-east-1',
        endpoint: '',
        bucket: '',
    }
})

const openAddModal = () => {
    isEditing.value = false
    selectedStorage.value = null
    form.reset()
    form.driver = 's3'
    form.capacity_gb = 100
    form.retention_days = 30
    form.encryption_enabled = true
    showModal.value = true
}

const openEditModal = (storage) => {
    isEditing.value = true
    selectedStorage.value = storage
    form.name = storage.name
    form.driver = storage.driver
    form.path = storage.path || ''
    form.capacity_gb = storage.capacity_gb || 100
    form.retention_days = storage.retention_days || 30
    form.is_default = storage.is_default || false
    form.encryption_enabled = storage.encryption_enabled !== false
    form.credentials = storage.credentials || {
        key: '',
        secret: '',
        region: 'us-east-1',
        endpoint: '',
        bucket: '',
    }
    showModal.value = true
}

const submitForm = () => {
    if (isEditing.value && selectedStorage.value) {
        form.put(route('admin.backups.storage.update', selectedStorage.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false
                feedbackMsg.value = 'Storage destination updated.'
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    } else {
        form.post(route('admin.backups.storage.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false
                feedbackMsg.value = 'New backup storage destination added.'
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    }
}

// 2. TEST CONNECTION
const testConnection = (storage) => {
    router.post(route('admin.backups.storage.test', storage.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = `Connection test to '${storage.name}' succeeded.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 3. SET DEFAULT
const setDefault = (storage) => {
    router.post(route('admin.backups.storage.default', storage.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = `'${storage.name}' set as default storage.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 4. DELETE MODAL
const showDeleteModal = ref(false)
const storageToDelete = ref(null)
const deleteForm = useForm({})

const openDeleteModal = (storage) => {
    storageToDelete.value = storage
    showDeleteModal.value = true
}

const submitDelete = () => {
    if (!storageToDelete.value) return
    deleteForm.delete(route('admin.backups.storage.destroy', storageToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false
            feedbackMsg.value = 'Storage destination removed.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}
</script>

<template>
    <Head title="Backup Storage Repositories - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Security', href: '#' },
                    { label: 'Backups & Snapshots', href: route('admin.backups.index') },
                    { label: 'Storage Targets & Remote Repositories' }
                ]"
            >
                <template #actions>
                    <Link
                        :href="route('admin.backups.index')"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowLeftIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>All Snapshots</span>
                    </Link>

                    <button 
                        type="button" 
                        @click="openAddModal"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <PlusIcon class="w-3.5 h-3.5" />
                        <span>Add Storage Target</span>
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
                    title="Storage Repositories"
                    :value="String(stats.total_repositories || storages.length || 0)"
                    badge="Locations"
                    badgeType="info"
                    color="blue"
                    :icon="FolderIcon"
                />

                <InfoCard
                    title="Active Connected"
                    :value="String(stats.active_repositories || storages.length || 0)"
                    badge="Online"
                    badgeType="success"
                    color="emerald"
                    :icon="ShieldCheckIcon"
                />

                <InfoCard
                    title="Allocated Space"
                    :value="stats.total_used_formatted || '0 B'"
                    badge="Backups"
                    badgeType="info"
                    color="purple"
                    :icon="CircleStackIcon"
                />

                <InfoCard
                    title="Default Backup Target"
                    :value="stats.default_name || 'Local Storage'"
                    badge="Primary"
                    badgeType="success"
                    color="sky"
                    :icon="CloudIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search repository name, driver type, or remote path..."
                @search="() => {}"
                @filter="() => {}"
                @reset="() => { search = ''; driver = ''; }"
            >
                <FilterSelect
                    v-model="driver"
                    label="Driver Protocol"
                    :options="[
                        { label: 'All Protocols', value: '' },
                        { label: 'Local Disk Storage', value: 'local' },
                        { label: 'Amazon S3 / Wasabi / B2', value: 's3' },
                        { label: 'Remote FTP', value: 'ftp' },
                        { label: 'Secure SFTP / SSH', value: 'sftp' },
                        { label: 'Google Drive', value: 'gdrive' }
                    ]"
                    placeholder="All Protocols"
                />
            </DataTableFilter>

            <!-- 4. Standard Data Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Repository Name</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Storage Driver</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Target Path / Bucket</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Capacity & Retention</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Default</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Status</th>
                                <th class="py-2.5 px-3 w-28">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(s, index) in filteredStorages" :key="s.id || index" class="hover:bg-blue-50/30 transition">
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-bold text-slate-900">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs border border-blue-100 shrink-0">
                                            <CloudIcon class="w-3.5 h-3.5" />
                                        </div>
                                        <span>{{ s.name }}</span>
                                    </div>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase bg-slate-100 text-slate-700 border border-slate-200 font-mono">
                                        {{ s.driver || 'LOCAL' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono text-[11px] text-slate-600 max-w-xs truncate" :title="s.path || s.credentials?.bucket">
                                    {{ s.path || s.credentials?.bucket || '/var/backups' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[11px] text-slate-700">
                                    {{ s.capacity_gb ? s.capacity_gb + ' GB' : 'Unlimited' }} ({{ s.retention_days || 30 }}d retention)
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span v-if="s.is_default" class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase bg-emerald-50 text-emerald-700 border border-emerald-200 font-mono">
                                        Default ⭐
                                    </span>
                                    <button v-else type="button" @click="setDefault(s)" class="text-[10.5px] text-slate-400 hover:text-blue-600 font-bold cursor-pointer">
                                        Set Default
                                    </button>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase bg-emerald-50 text-emerald-700 border border-emerald-200 font-mono">
                                        Connected
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1">
                                        <button 
                                            type="button" 
                                            @click="testConnection(s)"
                                            class="px-2 py-1 bg-white hover:bg-slate-50 text-blue-700 font-bold rounded-[3px] text-xs border border-slate-200 shadow-2xs transition cursor-pointer"
                                            title="Test Connection"
                                        >
                                            Test 🔌
                                        </button>

                                        <RowActionDropdown>
                                            <button
                                                type="button"
                                                @click="openEditModal(s)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <PencilSquareIcon class="w-3.5 h-3.5 text-slate-500" />
                                                <span>Edit Storage</span>
                                            </button>

                                            <button
                                                v-if="!s.is_default"
                                                type="button"
                                                @click="openDeleteModal(s)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-rose-600 hover:bg-rose-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <TrashIcon class="w-3.5 h-3.5" />
                                                <span>Delete Destination</span>
                                            </button>
                                        </RowActionDropdown>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!filteredStorages || filteredStorages.length === 0">
                                <td colspan="8" class="py-12 text-center text-slate-400 font-sans">
                                    No backup storage repositories configured.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ADD / EDIT MODAL -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-lg overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <CloudIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            {{ isEditing ? 'Edit Backup Storage' : 'Add Backup Storage Destination' }}
                        </h3>
                    </div>
                    <button @click="showModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitForm" class="p-4 space-y-3 text-xs">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Storage Label Name <span class="text-rose-500">*</span></label>
                            <input v-model="form.name" type="text" required placeholder="AWS Offsite S3 Bucket" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        </div>

                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Storage Driver</label>
                            <select v-model="form.driver" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                                <option value="s3">Amazon S3 / Wasabi / Backblaze</option>
                                <option value="local">Local Filesystem Disk</option>
                                <option value="ftp">Remote FTP Server</option>
                                <option value="sftp">Secure SFTP / SSH</option>
                                <option value="gdrive">Google Drive</option>
                            </select>
                        </div>
                    </div>

                    <div v-if="form.driver === 'local'" class="space-y-1">
                        <label class="block font-bold text-slate-700">Directory Path</label>
                        <input v-model="form.path" type="text" placeholder="/var/backups/deeptouchhost" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div v-if="form.driver === 's3'" class="space-y-3 bg-slate-50/80 p-3 rounded-[3px] border border-slate-200">
                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700">S3 Bucket Name</label>
                                <input v-model="form.credentials.bucket" type="text" placeholder="my-backups-bucket" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-white text-slate-900 font-mono focus:outline-none focus:ring-1 focus:ring-blue-500" />
                            </div>
                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700">AWS Region</label>
                                <input v-model="form.credentials.region" type="text" placeholder="us-east-1" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-white text-slate-900 font-mono focus:outline-none focus:ring-1 focus:ring-blue-500" />
                            </div>
                        </div>

                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Access Key ID</label>
                            <input v-model="form.credentials.key" type="text" placeholder="AKIAIOSFODNN7EXAMPLE" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-white text-slate-900 font-mono focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        </div>

                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Secret Access Key</label>
                            <input v-model="form.credentials.secret" type="password" placeholder="wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-white text-slate-900 font-mono focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Quota Capacity (GB)</label>
                            <input v-model.number="form.capacity_gb" type="number" min="1" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        </div>

                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Retention Days</label>
                            <input v-model.number="form.retention_days" type="number" min="1" max="365" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        </div>
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <input v-model="form.is_default" id="is_default" type="checkbox" class="rounded-[2px] text-blue-600 focus:ring-blue-500 cursor-pointer" />
                        <label for="is_default" class="text-xs text-slate-700 font-medium cursor-pointer">Set as default storage for automated backups</label>
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
                            :disabled="form.processing"
                            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ form.processing ? 'Saving...' : (isEditing ? 'Save Changes' : 'Add Storage Target') }}
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
                            Delete Storage Destination
                        </h3>
                    </div>
                    <button @click="showDeleteModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-2.5 text-xs">
                    <p class="text-slate-600">
                        Are you sure you want to remove storage destination <strong class="text-slate-900 font-mono">[{{ storageToDelete?.name }}]</strong>?
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
                        {{ deleteForm.processing ? 'Deleting...' : 'Delete Destination' }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
