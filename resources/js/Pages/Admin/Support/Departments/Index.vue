<script setup>
import { ref, computed } from 'vue'
import { Head, router, Link, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import RefreshButton from '@/Components/UI/RefreshButton.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'
import DataTableFilter from '@/Components/UI/DataTableFilter.vue'
import RowActionDropdown from '@/Components/UI/RowActionDropdown.vue'

import {
    PlusIcon,
    ArrowPathIcon,
    XMarkIcon,
    BuildingOfficeIcon,
    UsersIcon,
    ClockIcon,
    EnvelopeIcon,
    PencilSquareIcon,
    TrashIcon,
    ShieldCheckIcon,
    ChatBubbleLeftRightIcon,
    BoltIcon,
    CheckIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    departments: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            total_departments: 0,
            active_departments: 0,
            open_tickets_count: 0,
            total_staff_count: 0,
        }),
    },
    staff: {
        type: Array,
        default: () => [],
    },
})

// Flash Feedback Toast
const feedbackMsg = ref('')
const search = ref('')

const filteredDepartments = computed(() => {
    let list = props.departments || []
    if (search.value.trim()) {
        const q = search.value.toLowerCase()
        list = list.filter(d =>
            (d.name || '').toLowerCase().includes(q) ||
            (d.email || '').toLowerCase().includes(q) ||
            (d.description || '').toLowerCase().includes(q)
        )
    }
    return list
})

// 1. CREATE / EDIT MODAL
const showModal = ref(false)
const isEditing = ref(false)
const editingDeptId = ref(null)

const deptForm = useForm({
    name: '',
    slug: '',
    email: '',
    description: '',
    sla_response_hours: 2,
    is_client_selectable: true,
    is_active: true,
})

const onNameChange = () => {
    if (!isEditing.value) {
        deptForm.slug = deptForm.name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '')
    }
}

const openCreateModal = () => {
    isEditing.value = false
    editingDeptId.value = null
    deptForm.reset()
    deptForm.email = 'support@deeptouchhost.local'
    deptForm.sla_response_hours = 2
    deptForm.is_client_selectable = true
    deptForm.is_active = true
    showModal.value = true
}

const openEditModal = (dept) => {
    isEditing.value = true
    editingDeptId.value = dept.id
    deptForm.name = dept.name
    deptForm.slug = dept.slug
    deptForm.email = dept.email || ''
    deptForm.description = dept.description || ''
    deptForm.sla_response_hours = dept.sla_response_hours || 2
    deptForm.is_client_selectable = Boolean(dept.is_client_selectable)
    deptForm.is_active = Boolean(dept.is_active)
    showModal.value = true
}

const submitDeptForm = () => {
    if (isEditing.value) {
        deptForm.put(route('admin.support.departments.update', editingDeptId.value), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false
                feedbackMsg.value = 'Department updated.'
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    } else {
        deptForm.post(route('admin.support.departments.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false
                feedbackMsg.value = 'New support department created.'
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    }
}

// 2. TOGGLE DEPT
const toggleDept = (dept) => {
    router.post(route('admin.support.departments.toggle', dept.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = 'Department status updated.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 3. DELETE MODAL
const showDeleteModal = ref(false)
const deptToDelete = ref(null)
const deleteForm = useForm({})

const openDeleteModal = (dept) => {
    deptToDelete.value = dept
    showDeleteModal.value = true
}

const submitDelete = () => {
    if (!deptToDelete.value) return
    deleteForm.delete(route('admin.support.departments.destroy', deptToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false
            feedbackMsg.value = 'Department removed.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}
</script>

<template>
    <Head title="Support Departments - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Support', href: '#' },
                    { label: 'Customer Support', href: route('admin.tickets.index') },
                    { label: 'Helpdesk Support Departments' }
                ]"
            >
                <template #actions>
                    <Link
                        :href="route('admin.tickets.index')"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowLeftIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>All Tickets</span>
                    </Link>

                    <button 
                        type="button" 
                        @click="openCreateModal"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <PlusIcon class="w-3.5 h-3.5" />
                        <span>Create Department</span>
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
                    title="Total Departments"
                    :value="String(stats.total_departments || departments.length || 0)"
                    badge="Units"
                    badgeType="info"
                    color="blue"
                    :icon="BuildingOfficeIcon"
                />

                <InfoCard
                    title="Active Channels"
                    :value="String(stats.active_departments || departments.filter(d => d.is_active).length || 0)"
                    badge="Routing"
                    badgeType="success"
                    color="emerald"
                    :icon="ShieldCheckIcon"
                />

                <InfoCard
                    title="Open Tickets"
                    :value="String(stats.open_tickets_count || 0)"
                    badge="Pending"
                    badgeType="warning"
                    color="purple"
                    :icon="ChatBubbleLeftRightIcon"
                />

                <InfoCard
                    title="Assigned Agents"
                    :value="String(stats.total_staff_count || staff.length || 0)"
                    badge="Staff"
                    badgeType="info"
                    color="sky"
                    :icon="UsersIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search department name, email address, or description..."
                @search="() => {}"
                @filter="() => {}"
                @reset="() => { search = ''; }"
            />

            <!-- 4. Standard Data Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Department Name</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Inbound Email Address</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">SLA Response Window</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Client Selectable</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Status</th>
                                <th class="py-2.5 px-3 w-28">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(dept, index) in filteredDepartments" :key="dept.id || index" class="hover:bg-blue-50/30 transition">
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-bold text-slate-900">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs border border-blue-100 shrink-0">
                                            <BuildingOfficeIcon class="w-3.5 h-3.5" />
                                        </div>
                                        <div>
                                            <span class="block leading-tight font-bold">{{ dept.name }}</span>
                                            <span class="text-[10px] text-slate-400 font-mono block">{{ dept.slug }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono text-[11px] text-slate-600">
                                    {{ dept.email || 'support@domain.local' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[11px] text-slate-700">
                                    {{ dept.sla_response_hours || 2 }} Hours Target
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="dept.is_client_selectable ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-500 border-slate-200'"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ dept.is_client_selectable ? 'Public' : 'Internal' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="dept.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-500 border-slate-200'"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ dept.is_active ? 'Active' : 'Disabled' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1">
                                        <RowActionDropdown>
                                            <button
                                                type="button"
                                                @click="openEditModal(dept)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <PencilSquareIcon class="w-3.5 h-3.5 text-slate-500" />
                                                <span>Edit Department</span>
                                            </button>

                                            <button
                                                type="button"
                                                @click="toggleDept(dept)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <BoltIcon class="w-3.5 h-3.5 text-slate-500" />
                                                <span>{{ dept.is_active ? 'Disable Channel' : 'Activate Channel' }}</span>
                                            </button>

                                            <button
                                                type="button"
                                                @click="openDeleteModal(dept)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-rose-600 hover:bg-rose-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <TrashIcon class="w-3.5 h-3.5" />
                                                <span>Delete Department</span>
                                            </button>
                                        </RowActionDropdown>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!filteredDepartments || filteredDepartments.length === 0">
                                <td colspan="7" class="py-12 text-center text-slate-400 font-sans">
                                    No support departments configured.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- CREATE / EDIT MODAL -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <BuildingOfficeIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            {{ isEditing ? 'Edit Support Department' : 'Create Helpdesk Department' }}
                        </h3>
                    </div>
                    <button @click="showModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitDeptForm" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Department Name <span class="text-rose-500">*</span></label>
                        <input v-model="deptForm.name" @input="onNameChange" type="text" required placeholder="Technical Infrastructure" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Inbound Mail Address</label>
                        <input v-model="deptForm.email" type="email" placeholder="techsupport@deeptouchhost.local" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">SLA Response (Hours)</label>
                            <input v-model.number="deptForm.sla_response_hours" type="number" min="1" max="72" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        </div>

                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Client Visibility</label>
                            <select v-model="deptForm.is_client_selectable" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                                <option :value="true">Public (Customer Visible)</option>
                                <option :value="false">Private (Staff Internal Only)</option>
                            </select>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Description</label>
                        <textarea v-model="deptForm.description" rows="2" placeholder="Brief summary of department scope..." class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"></textarea>
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <input v-model="deptForm.is_active" id="dept_act" type="checkbox" class="rounded-[2px] text-blue-600 focus:ring-blue-500 cursor-pointer" />
                        <label for="dept_act" class="text-xs text-slate-700 font-medium cursor-pointer">Active and accepting tickets</label>
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
                            :disabled="deptForm.processing"
                            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ deptForm.processing ? 'Saving...' : (isEditing ? 'Save Changes' : 'Create Department') }}
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
                            Delete Support Department
                        </h3>
                    </div>
                    <button @click="showDeleteModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-2.5 text-xs">
                    <p class="text-slate-600">
                        Are you sure you want to remove department <strong class="text-slate-900 font-mono">[{{ deptToDelete?.name }}]</strong>?
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
                        {{ deleteForm.processing ? 'Deleting...' : 'Delete Department' }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
