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
    PlusIcon,
    ArrowPathIcon,
    CheckIcon,
    XMarkIcon,
    UserGroupIcon,
    ShieldCheckIcon,
    ClockIcon,
    BuildingOffice2Icon,
    PencilSquareIcon,
    TrashIcon,
    NoSymbolIcon,
    FingerPrintIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    staff: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    stats: {
        type: Object,
        default: () => ({
            total_staff: 0,
            on_duty_count: 0,
            departments_covered: 0,
            two_factor_protected: 0,
        }),
    },
    departments: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({ search: '', department_id: 'all', shift_status: 'all', status: 'all' }),
    },
})

// Flash Feedback Toast
const feedbackMsg = ref('')

// Filter State
const search = ref(props.filters?.search || '')
const currentDept = ref(props.filters?.department_id || 'all')
const currentShiftStatus = ref(props.filters?.shift_status || 'all')

const applyFilters = () => {
    router.get(route('admin.administration.staff'), {
        search: search.value || undefined,
        department_id: currentDept.value !== 'all' ? currentDept.value : undefined,
        shift_status: currentShiftStatus.value !== 'all' ? currentShiftStatus.value : undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const selectDept = (deptId) => {
    currentDept.value = deptId
    applyFilters()
}

const resetFilters = () => {
    search.value = ''
    currentDept.value = 'all'
    currentShiftStatus.value = 'all'
    applyFilters()
}

// 1. CREATE / EDIT STAFF MODAL
const showModal = ref(false)
const isEditing = ref(false)
const editingStaffId = ref(null)

const staffForm = useForm({
    name: '',
    email: '',
    designation: 'Support Engineer',
    department_id: '',
    shift: 'Morning (08:00 - 16:00)',
    shift_status: 'on_duty',
    two_factor_enforced: true,
})

const openCreateModal = () => {
    isEditing.value = false
    editingStaffId.value = null
    staffForm.reset()
    staffForm.department_id = props.departments[0]?.id || ''
    staffForm.designation = 'Tier-2 Linux Support Engineer'
    staffForm.shift = 'Morning (08:00 - 16:00)'
    staffForm.shift_status = 'on_duty'
    staffForm.two_factor_enforced = true
    showModal.value = true
}

const openEditModal = (member) => {
    isEditing.value = true
    editingStaffId.value = member.id
    staffForm.name = member.name
    staffForm.email = member.email
    staffForm.designation = member.designation || member.job_title || 'Support Engineer'
    staffForm.department_id = member.department_id || ''
    staffForm.shift = member.shift || 'Morning (08:00 - 16:00)'
    staffForm.shift_status = member.shift_status || 'on_duty'
    staffForm.two_factor_enforced = Boolean(member.two_factor_enforced || member.has_2fa)
    showModal.value = true
}

const submitStaff = () => {
    if (isEditing.value) {
        staffForm.put(route('admin.administration.staff.update', editingStaffId.value), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false
                feedbackMsg.value = 'Staff member profile updated.'
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    } else {
        staffForm.post(route('admin.administration.staff.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false
                feedbackMsg.value = 'New staff member onboarded.'
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    }
}

// 2. TOGGLE DUTY
const toggleDuty = (member) => {
    router.post(route('admin.administration.staff.toggle', member.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = 'Staff duty status updated.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 3. DELETE MODAL
const showDeleteModal = ref(false)
const staffToDelete = ref(null)
const deleteForm = useForm({})

const openDeleteModal = (member) => {
    staffToDelete.value = member
    showDeleteModal.value = true
}

const submitDelete = () => {
    if (!staffToDelete.value) return
    deleteForm.delete(route('admin.administration.staff.destroy', staffToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false
            feedbackMsg.value = 'Staff member removed.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}
</script>

<template>
    <Head title="Staff Team - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Administration', href: route('admin.administration.staff') },
                    { label: 'Staff' }
                ]"
            >
                <template #actions>
                    <Link
                        :href="route('admin.administration.administrators')"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ShieldCheckIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Super Admins</span>
                    </Link>

                    <button 
                        type="button" 
                        @click="openCreateModal"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <PlusIcon class="w-3.5 h-3.5" />
                        <span>Onboard Staff Member</span>
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
                    title="Total Staff Team"
                    :value="String(stats.total_staff || staff.data?.length || 0)"
                    badge="Staff"
                    badgeType="info"
                    color="blue"
                    :icon="UserGroupIcon"
                />

                <InfoCard
                    title="On Duty Roster"
                    :value="String(stats.on_duty_count || staff.data?.filter(s => s.shift_status === 'on_duty').length || 0)"
                    badge="Active"
                    badgeType="success"
                    color="emerald"
                    :icon="ClockIcon"
                />

                <InfoCard
                    title="Covered Departments"
                    :value="String(stats.departments_covered || departments.length || 0)"
                    badge="Depts"
                    badgeType="info"
                    color="purple"
                    :icon="BuildingOffice2Icon"
                />

                <InfoCard
                    title="2FA Protected"
                    :value="String(stats.two_factor_protected || 0)"
                    badge="Secured"
                    badgeType="success"
                    color="sky"
                    :icon="FingerPrintIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Department Tabs -->
            <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        @click="selectDept('all')"
                        :class="currentDept === 'all' ? 'bg-blue-600 text-white' : 'bg-white text-slate-700 hover:bg-slate-50 border border-slate-200'"
                        class="px-3 py-1.5 rounded-[3px] font-bold text-xs uppercase font-mono transition cursor-pointer shadow-2xs"
                    >
                        All Departments
                    </button>
                    <button
                        v-for="d in departments"
                        :key="d.id"
                        type="button"
                        @click="selectDept(String(d.id))"
                        :class="String(currentDept) === String(d.id) ? 'bg-blue-600 text-white' : 'bg-white text-slate-700 hover:bg-slate-50 border border-slate-200'"
                        class="px-3 py-1.5 rounded-[3px] font-bold text-xs uppercase font-mono transition cursor-pointer shadow-2xs"
                    >
                        {{ d.name }}
                    </button>
                </div>
            </div>

            <!-- 4. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search staff member name, email address, designation..."
                @search="applyFilters"
                @filter="applyFilters"
                @reset="resetFilters"
            >
                <FilterSelect
                    v-model="currentShiftStatus"
                    label="Shift Status"
                    :options="[
                        { label: 'All Shifts', value: 'all' },
                        { label: 'On Duty (Active)', value: 'on_duty' },
                        { label: 'Off Duty (Away)', value: 'off_duty' }
                    ]"
                    placeholder="All Shifts"
                />
            </DataTableFilter>

            <!-- 5. Standard Data Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Staff Member</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Job Designation</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Department</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Duty Status</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">2FA Security</th>
                                <th class="py-2.5 px-3 w-14 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(member, index) in staff.data" :key="member.id || index" class="hover:bg-blue-50/30 transition">
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-bold text-slate-900">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs border border-blue-100 shrink-0">
                                            <UserGroupIcon class="w-3.5 h-3.5" />
                                        </div>
                                        <div>
                                            <span class="block leading-tight font-bold">{{ member.name }}</span>
                                            <span class="text-[10px] text-slate-400 font-mono block">{{ member.email }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-medium text-slate-800">
                                    {{ member.designation || member.job_title || 'Support Specialist' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase bg-slate-100 text-slate-700 border border-slate-200 font-mono">
                                        {{ member.department?.name || member.department_name || 'Technical' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="member.shift_status === 'on_duty' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-500 border-slate-200'"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ member.shift_status === 'on_duty' ? 'On Duty' : 'Off Duty' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="member.has_2fa || member.two_factor_enforced ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-500 border-slate-200'"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ member.has_2fa || member.two_factor_enforced ? 'Enabled' : 'Disabled' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <RowActionDropdown>
                                        <button
                                            type="button"
                                            @click="openEditModal(member)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <PencilSquareIcon class="w-3.5 h-3.5 text-slate-500" />
                                            <span>Edit Profile</span>
                                        </button>

                                        <button
                                            type="button"
                                            @click="toggleDuty(member)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <ClockIcon class="w-3.5 h-3.5 text-slate-500" />
                                            <span>{{ member.shift_status === 'on_duty' ? 'Set Off Duty' : 'Set On Duty' }}</span>
                                        </button>

                                        <button
                                            type="button"
                                            @click="openDeleteModal(member)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-rose-600 hover:bg-rose-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <TrashIcon class="w-3.5 h-3.5" />
                                            <span>Remove Staff</span>
                                        </button>
                                    </RowActionDropdown>
                                </td>
                            </tr>

                            <tr v-if="!staff.data || staff.data.length === 0">
                                <td colspan="7" class="py-12 text-center text-slate-400 font-sans">
                                    No staff members found.
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
                            <UserGroupIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            {{ isEditing ? 'Edit Staff Member' : 'Onboard Operations Staff' }}
                        </h3>
                    </div>
                    <button @click="showModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitStaff" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Full Name <span class="text-rose-500">*</span></label>
                        <input v-model="staffForm.name" type="text" required placeholder="Staff Member Name" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Email Address <span class="text-rose-500">*</span></label>
                        <input v-model="staffForm.email" type="email" required placeholder="staff@domain.local" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Department</label>
                            <select v-model="staffForm.department_id" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                                <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.name }}</option>
                            </select>
                        </div>

                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Job Designation</label>
                            <input v-model="staffForm.designation" type="text" placeholder="Tier-2 Linux Engineer" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Shift Schedule</label>
                            <select v-model="staffForm.shift" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                                <option value="Morning (08:00 - 16:00)">Morning (08:00 - 16:00)</option>
                                <option value="Evening (16:00 - 00:00)">Evening (16:00 - 00:00)</option>
                                <option value="Night (00:00 - 08:00)">Night (00:00 - 08:00)</option>
                            </select>
                        </div>

                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Duty Status</label>
                            <select v-model="staffForm.shift_status" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                                <option value="on_duty">On Duty</option>
                                <option value="off_duty">Off Duty</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <input v-model="staffForm.two_factor_enforced" id="staff_2fa" type="checkbox" class="rounded-[2px] text-blue-600 focus:ring-blue-500 cursor-pointer" />
                        <label for="staff_2fa" class="text-xs text-slate-700 font-medium cursor-pointer">Enforce 2FA two-factor authentication</label>
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
                            :disabled="staffForm.processing"
                            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ staffForm.processing ? 'Saving...' : (isEditing ? 'Save Changes' : 'Onboard Staff') }}
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
                            Remove Staff Member
                        </h3>
                    </div>
                    <button @click="showDeleteModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-2.5 text-xs">
                    <p class="text-slate-600">
                        Are you sure you want to remove staff member <strong class="text-slate-900 font-mono">[{{ staffToDelete?.name }}]</strong>?
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
                        {{ deleteForm.processing ? 'Removing...' : 'Remove Staff' }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
