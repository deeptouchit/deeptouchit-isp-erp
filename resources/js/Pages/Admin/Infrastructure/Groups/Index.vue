<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'
import DataTableFilter from '@/Components/UI/DataTableFilter.vue'
import FilterSelect from '@/Components/UI/FilterSelect.vue'
import RowActionDropdown from '@/Components/UI/RowActionDropdown.vue'
import Pagination from '@/Components/UI/Pagination.vue'

import {
    FolderIcon,
    ServerIcon,
    PlusIcon,
    ArrowPathIcon,
    GlobeAltIcon,
    CheckCircleIcon,
    PencilSquareIcon,
    TrashIcon,
    EyeIcon,
    XMarkIcon,
    CpuChipIcon,
    CircleStackIcon,
    Square2StackIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    groups: {
        type: Object,
        default: () => ({ data: [] }),
    },
    stats: {
        type: Object,
        default: () => ({
            total_groups: 0,
            active_groups: 0,
            total_servers: 0,
            assigned_servers: 0,
            locations: [],
        }),
    },
    filters: {
        type: Object,
        default: () => ({ search: '', status: '', location: '' }),
    },
})

// Search & Filter State
const search = ref(props.filters?.search || '')
const status = ref(props.filters?.status || '')
const location = ref(props.filters?.location || '')

const applyFilters = () => {
    router.get(route('admin.infrastructure.groups'), {
        search: search.value || undefined,
        status: status.value || undefined,
        location: location.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const resetFilters = () => {
    search.value = ''
    status.value = ''
    location.value = ''
    applyFilters()
}

// Modal States
const showCreateModal = ref(false)
const showEditModal = ref(false)
const showDeleteModal = ref(false)
const selectedGroup = ref(null)

// Create Form
const createForm = useForm({
    name: '',
    location: '',
    description: '',
    status: 'active',
})

const openCreateModal = () => {
    createForm.reset()
    createForm.clearErrors()
    showCreateModal.value = true
}

const submitCreate = () => {
    createForm.post(route('admin.infrastructure.groups.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false
            createForm.reset()
        },
    })
}

// Edit Form
const editForm = useForm({
    name: '',
    location: '',
    description: '',
    status: 'active',
})

const openEditModal = (group) => {
    selectedGroup.value = group
    editForm.name = group.name
    editForm.location = group.location || ''
    editForm.description = group.description || ''
    editForm.status = group.status || 'active'
    editForm.clearErrors()
    showEditModal.value = true
}

const submitEdit = () => {
    if (!selectedGroup.value) return
    editForm.put(route('admin.infrastructure.groups.update', selectedGroup.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false
        },
    })
}

// Delete Form
const deleteForm = useForm({})
const openDeleteModal = (group) => {
    selectedGroup.value = group
    showDeleteModal.value = true
}

const submitDelete = () => {
    if (!selectedGroup.value) return
    deleteForm.delete(route('admin.infrastructure.groups.destroy', selectedGroup.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false
        },
    })
}

// Helper formatters
const getGroupCores = (servers) => {
    if (!servers || servers.length === 0) return 0
    return servers.reduce((acc, s) => acc + (s.cpu_cores || 1), 0)
}

const getGroupRam = (servers) => {
    if (!servers || servers.length === 0) return '0 GB'
    const totalMb = servers.reduce((acc, s) => acc + (s.total_ram || 0), 0)
    return totalMb >= 1024 ? `${(totalMb / 1024).toFixed(1)} GB` : `${totalMb} MB`
}

const getGroupDisk = (servers) => {
    if (!servers || servers.length === 0) return '0 GB'
    const totalMb = servers.reduce((acc, s) => acc + (s.total_disk || 0), 0)
    return totalMb >= 1024 ? `${(totalMb / 1024).toFixed(1)} GB` : `${totalMb} MB`
}
</script>

<template>
    <Head title="Server Groups & Clusters - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Infrastructure', href: route('admin.servers.index') },
                    { label: 'Server Groups' }
                ]"
            >
                <template #actions>
                    <button 
                        type="button" 
                        @click="router.reload({ preserveScroll: true })"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowPathIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Refresh</span>
                    </button>

                    <button 
                        type="button"
                        @click="openCreateModal"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <PlusIcon class="w-3.5 h-3.5" />
                        <span>Create Group</span>
                    </button>
                </template>
            </PageHeader>

            <!-- 2. Ultra-Compact KPI Info Cards (56px) -->
            <InfoCardsGrid :cols="4">
                <InfoCard
                    title="Total Groups"
                    :value="String(stats.total_groups || 0)"
                    badge="Cluster"
                    badgeType="info"
                    color="blue"
                    :icon="FolderIcon"
                />

                <InfoCard
                    title="Clustered Nodes"
                    :value="String(stats.assigned_servers || 0)"
                    :badge="`${stats.total_servers || 0} Total`"
                    badgeType="success"
                    color="sky"
                    :icon="ServerIcon"
                />

                <InfoCard
                    title="Active Clusters"
                    :value="String(stats.active_groups || 0)"
                    badge="Active"
                    badgeType="success"
                    color="emerald"
                    :icon="CheckCircleIcon"
                />

                <InfoCard
                    title="Datacenter Regions"
                    :value="String(stats.locations?.length || 1)"
                    badge="Global"
                    badgeType="purple"
                    color="purple"
                    :icon="GlobeAltIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Standard Unified Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search groups by name, slug, or location..."
                @search="applyFilters"
                @filter="applyFilters"
                @reset="resetFilters"
            >
                <FilterSelect
                    v-model="status"
                    label="Status"
                    :options="[
                        { label: 'Active', value: 'active' },
                        { label: 'Inactive', value: 'inactive' }
                    ]"
                    placeholder="All Statuses"
                    @change="applyFilters"
                />

                <FilterSelect
                    v-if="stats.locations && stats.locations.length > 0"
                    v-model="location"
                    label="Region"
                    :options="stats.locations.map(loc => ({ label: loc, value: loc }))"
                    placeholder="All Regions"
                    @change="applyFilters"
                />
            </DataTableFilter>

            <!-- 4. Standard Data Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="border-b border-slate-200 bg-slate-50/90 text-[10.5px] font-bold text-slate-600 uppercase tracking-wider whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Group Name & Slug</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Region / Location</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Assigned Nodes</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">CPU Pool</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">RAM Pool</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Status</th>
                                <th class="py-2.5 px-3 w-16">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
                            <tr 
                                v-for="(group, index) in (groups.data || [])" 
                                :key="group.id" 
                                class="hover:bg-blue-50/30 transition"
                            >
                                <!-- # Index -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ ((groups.current_page || 1) - 1) * (groups.per_page || 15) + (index + 1) }}
                                </td>

                                <!-- Group Name & Slug -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 border border-blue-100">
                                            <Square2StackIcon class="w-3.5 h-3.5" />
                                        </div>
                                        <div>
                                            <span class="font-bold text-slate-900 block">{{ group.name }}</span>
                                            <span class="text-[10px] font-mono text-slate-400">{{ group.slug }}</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Region / Location -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1 text-slate-700 font-medium">
                                        <GlobeAltIcon class="w-3.5 h-3.5 text-slate-400" />
                                        <span>{{ group.location || 'Global Datacenter' }}</span>
                                    </span>
                                </td>

                                <!-- Assigned Nodes -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap">
                                    <Link 
                                        :href="route('admin.servers.index', { server_group_id: group.id })" 
                                        class="inline-flex items-center gap-1 font-mono font-bold text-blue-600 hover:underline bg-blue-50 px-2 py-0.5 rounded-[3px] border border-blue-100"
                                    >
                                        <ServerIcon class="w-3 h-3" />
                                        <span>{{ group.servers_count || 0 }} Nodes</span>
                                    </Link>
                                </td>

                                <!-- CPU Pool -->
                                <td class="py-2.5 px-3 border-r border-slate-100 font-mono text-slate-700 whitespace-nowrap">
                                    {{ getGroupCores(group.servers) }} Cores
                                </td>

                                <!-- RAM Pool -->
                                <td class="py-2.5 px-3 border-r border-slate-100 font-mono text-slate-700 whitespace-nowrap">
                                    {{ getGroupRam(group.servers) }}
                                </td>

                                <!-- Status -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap">
                                    <span 
                                        :class="group.status === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-600 border-slate-200'"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border inline-flex items-center gap-1 whitespace-nowrap"
                                    >
                                        ● {{ group.status }}
                                    </span>
                                </td>

                                <!-- Action Dropdown -->
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <RowActionDropdown>
                                        <Link 
                                            :href="route('admin.servers.index', { server_group_id: group.id })"
                                            class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium"
                                        >
                                            <EyeIcon class="w-3.5 h-3.5 text-slate-500" />
                                            <span>View Group Nodes</span>
                                        </Link>

                                        <button 
                                            type="button" 
                                            @click="openEditModal(group)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <PencilSquareIcon class="w-3.5 h-3.5 text-slate-500" />
                                            <span>Edit Group</span>
                                        </button>

                                        <button 
                                            type="button" 
                                            @click="openDeleteModal(group)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-rose-600 hover:bg-rose-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <TrashIcon class="w-3.5 h-3.5" />
                                            <span>Delete Group</span>
                                        </button>
                                    </RowActionDropdown>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="!groups.data || groups.data.length === 0">
                                <td colspan="8" class="py-12 text-center text-slate-400">
                                    <FolderIcon class="w-8 h-8 mx-auto mb-2 text-slate-300" />
                                    <p class="font-bold text-slate-600">No Server Groups Found</p>
                                    <p class="text-[11px] text-slate-400 mt-0.5">Create your first server cluster group to logically organize your nodes.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <Pagination :links="groups.links" :from="groups.from" :to="groups.to" :total="groups.total" />
            </div>
        </div>

        <!-- CREATE GROUP MODAL -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-lg overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <PlusIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">Create New Cluster Group</h3>
                    </div>
                    <button @click="showCreateModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitCreate" class="p-4 space-y-3.5 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Cluster Group Name <span class="text-rose-500">*</span></label>
                        <input
                            v-model="createForm.name"
                            type="text"
                            placeholder="e.g. EU Premium SSD Cluster"
                            class="w-full px-3 py-1.5 rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition-all shadow-2xs"
                            required
                        />
                        <p v-if="createForm.errors.name" class="text-[10.5px] text-rose-600 font-semibold">{{ createForm.errors.name }}</p>
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Datacenter / Region Location</label>
                        <input
                            v-model="createForm.location"
                            type="text"
                            placeholder="e.g. Frankfurt, Germany (EU-Central)"
                            class="w-full px-3 py-1.5 rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition-all shadow-2xs"
                        />
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Description</label>
                        <textarea
                            v-model="createForm.description"
                            rows="3"
                            placeholder="Briefly describe the cluster purpose..."
                            class="w-full p-2.5 rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition-all shadow-2xs"
                        ></textarea>
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Status</label>
                        <select
                            v-model="createForm.status"
                            class="w-full px-3 py-1.5 rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition-all shadow-2xs cursor-pointer"
                        >
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2.5 border-t border-slate-100">
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
                            {{ createForm.processing ? 'Creating...' : 'Create Group' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- EDIT GROUP MODAL -->
        <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-lg overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <PencilSquareIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">Edit Cluster Group: {{ selectedGroup?.name }}</h3>
                    </div>
                    <button @click="showEditModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitEdit" class="p-4 space-y-3.5 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Cluster Group Name <span class="text-rose-500">*</span></label>
                        <input
                            v-model="editForm.name"
                            type="text"
                            class="w-full px-3 py-1.5 rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition-all shadow-2xs"
                            required
                        />
                        <p v-if="editForm.errors.name" class="text-[10.5px] text-rose-600 font-semibold">{{ editForm.errors.name }}</p>
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Datacenter / Region Location</label>
                        <input
                            v-model="editForm.location"
                            type="text"
                            class="w-full px-3 py-1.5 rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition-all shadow-2xs"
                        />
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Description</label>
                        <textarea
                            v-model="editForm.description"
                            rows="3"
                            class="w-full p-2.5 rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition-all shadow-2xs"
                        ></textarea>
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Status</label>
                        <select
                            v-model="editForm.status"
                            class="w-full px-3 py-1.5 rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition-all shadow-2xs cursor-pointer"
                        >
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2.5 border-t border-slate-100">
                        <button
                            type="button"
                            @click="showEditModal = false"
                            class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="editForm.processing"
                            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ editForm.processing ? 'Saving...' : 'Save Changes' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- DELETE CONFIRMATION MODAL -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="p-4 space-y-2.5">
                    <div class="w-8 h-8 rounded-[3px] bg-rose-50 text-rose-600 flex items-center justify-center font-bold border border-rose-100">
                        <TrashIcon class="w-4 h-4" />
                    </div>
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">Delete Cluster Group: {{ selectedGroup?.name }}</h3>
                    <p class="text-[11px] text-slate-500">
                        Are you sure you want to delete this group? All assigned servers will remain intact and be safely unassigned to the standalone node pool.
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
                        {{ deleteForm.processing ? 'Deleting...' : 'Delete Group' }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
