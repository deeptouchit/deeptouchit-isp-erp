<script setup>
import { ref } from 'vue'
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
    UsersIcon,
    UserGroupIcon,
    ShieldCheckIcon,
    BriefcaseIcon,
    PlusIcon,
    ArrowPathIcon,
    EyeIcon,
    PencilSquareIcon,
    TrashIcon,
    KeyIcon,
    PlayIcon,
    PauseIcon,
    XMarkIcon,
    CheckCircleIcon,
    ArrowRightEndOnRectangleIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    users: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    stats: {
        type: Object,
        default: () => ({
            total_users: 0,
            active_clients: 0,
            total_resellers: 0,
            total_admins: 0,
        }),
    },
    filters: {
        type: Object,
        default: () => ({ search: '', role: '', status: '' }),
    },
})

// Search & Filter State
const search = ref(props.filters?.search || '')
const role = ref(props.filters?.role || '')
const status = ref(props.filters?.status || '')

const applyFilters = () => {
    router.get(route('admin.users.index'), {
        search: search.value || undefined,
        role: role.value || undefined,
        status: status.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const resetFilters = () => {
    search.value = ''
    role.value = ''
    status.value = ''
    applyFilters()
}

// Flash message
const feedbackMsg = ref('')

// Toggle Status
const toggleStatus = (user) => {
    router.post(route('admin.users.toggle-status', user.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = `User '${user.name}' status updated.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// Impersonate User
const impersonateUser = (user) => {
    if (confirm(`Login to client dashboard as '${user.name}' (${user.email})?`)) {
        router.post(route('admin.users.impersonate', user.id))
    }
}

// Password Reset Modal State
const showPasswordModal = ref(false)
const selectedUser = ref(null)
const passwordForm = useForm({
    password: '',
})

const openPasswordModal = (user) => {
    selectedUser.value = user
    passwordForm.password = ''
    showPasswordModal.value = true
}

const submitPasswordReset = () => {
    if (!selectedUser.value) return
    passwordForm.post(route('admin.users.reset-password', selectedUser.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showPasswordModal.value = false
            feedbackMsg.value = `Password reset successfully for '${selectedUser.value.name}'.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// Delete Modal State
const showDeleteModal = ref(false)
const userToDelete = ref(null)
const deleteForm = useForm({})

const openDeleteModal = (user) => {
    userToDelete.value = user
    showDeleteModal.value = true
}

const submitDeleteUser = () => {
    if (!userToDelete.value) return
    deleteForm.delete(route('admin.users.destroy', userToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false
            feedbackMsg.value = `User account '${userToDelete.value.name}' removed.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// Helpers
const formatDate = (dateStr) => {
    if (!dateStr) return 'Never'
    try {
        const d = new Date(dateStr)
        if (isNaN(d.getTime())) return dateStr
        return new Intl.DateTimeFormat('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
        }).format(d)
    } catch {
        return dateStr
    }
}
</script>

<template>
    <Head title="All Customers & Directory - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Customers', href: '#' },
                    { label: 'All Customers' }
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

                    <Link 
                        :href="route('admin.users.create')"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <PlusIcon class="w-3.5 h-3.5" />
                        <span>Add Customer</span>
                    </Link>
                </template>
            </PageHeader>

            <!-- Feedback Banner -->
            <div v-if="feedbackMsg" class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-3 py-2 rounded-[3px] text-xs font-bold flex items-center justify-between shadow-2xs">
                <div class="flex items-center gap-2">
                    <CheckCircleIcon class="w-4 h-4 text-emerald-600" />
                    <span>{{ feedbackMsg }}</span>
                </div>
                <button @click="feedbackMsg = ''" class="text-emerald-600 hover:text-emerald-800 text-xs cursor-pointer">✕</button>
            </div>

            <!-- 2. Ultra-Compact KPI Info Cards (56px) -->
            <InfoCardsGrid :cols="4">
                <InfoCard
                    title="Total Directory"
                    :value="String(stats.total_users || 0)"
                    badge="Accounts"
                    badgeType="info"
                    color="blue"
                    :icon="UsersIcon"
                />

                <InfoCard
                    title="Active Clients"
                    :value="String(stats.active_clients || 0)"
                    badge="Subscribers"
                    badgeType="success"
                    color="emerald"
                    :icon="UserGroupIcon"
                />

                <InfoCard
                    title="Resellers"
                    :value="String(stats.total_resellers || 0)"
                    badge="Partners"
                    badgeType="info"
                    color="sky"
                    :icon="BriefcaseIcon"
                />

                <InfoCard
                    title="Administrators"
                    :value="String(stats.total_admins || 0)"
                    badge="Superusers"
                    badgeType="success"
                    color="purple"
                    :icon="ShieldCheckIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search by name, email, username, or company..."
                @search="applyFilters"
                @filter="applyFilters"
                @reset="resetFilters"
            >
                <FilterSelect
                    v-model="role"
                    label="Role"
                    :options="[
                        { label: 'Client', value: 'client' },
                        { label: 'Reseller', value: 'reseller' },
                        { label: 'Administrator', value: 'admin' }
                    ]"
                    placeholder="All Roles"
                    @change="applyFilters"
                />

                <FilterSelect
                    v-model="status"
                    label="Status"
                    :options="[
                        { label: 'Active', value: 'active' },
                        { label: 'Suspended', value: 'suspended' },
                        { label: 'Terminated', value: 'terminated' }
                    ]"
                    placeholder="All Statuses"
                    @change="applyFilters"
                />
            </DataTableFilter>

            <!-- 4. Standard Data Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">User Identity</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Access Role</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Status</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Hosted Workloads</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Reseller Parent</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Created</th>
                                <th class="py-2.5 px-3 w-16">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(u, index) in users.data" :key="u.id" class="hover:bg-blue-50/30 transition">
                                <!-- # -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ ((users.current_page || 1) - 1) * (users.per_page || 15) + (index + 1) }}
                                </td>

                                <!-- User Identity -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs border border-blue-100 shrink-0">
                                            {{ (u.first_name || u.username || u.email || 'U').charAt(0).toUpperCase() }}
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-1.5">
                                                <Link :href="route('admin.users.show', u.id)" class="font-bold text-slate-900 hover:text-blue-600 block leading-tight">
                                                    {{ u.name || `${u.first_name || ''} ${u.last_name || ''}` }}
                                                </Link>
                                                <span v-if="u.company" class="text-[10px] text-slate-400 font-normal">({{ u.company }})</span>
                                            </div>
                                            <span class="text-[10px] font-mono text-slate-400 block">{{ u.email }} • @{{ u.username }}</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Role -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="[
                                            u.role === 'admin' ? 'bg-purple-50 text-purple-700 border-purple-200' :
                                            u.role === 'reseller' ? 'bg-sky-50 text-sky-700 border-sky-200' :
                                            'bg-slate-100 text-slate-700 border-slate-200'
                                        ]"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ u.role }}
                                    </span>
                                </td>

                                <!-- Status -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="[
                                            u.status === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                                            u.status === 'suspended' ? 'bg-amber-50 text-amber-700 border-amber-200' :
                                            'bg-rose-50 text-rose-700 border-rose-200'
                                        ]"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-[3px] text-[10px] font-bold capitalize border"
                                    >
                                        <span 
                                            class="w-1.5 h-1.5 rounded-full"
                                            :class="u.status === 'active' ? 'bg-emerald-500' : (u.status === 'suspended' ? 'bg-amber-500' : 'bg-rose-500')"
                                        ></span>
                                        {{ u.status }}
                                    </span>
                                </td>

                                <!-- Workloads -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap font-mono text-[10.5px]">
                                    <span v-if="u.role === 'reseller'" class="font-bold text-sky-700 bg-sky-50 px-2 py-0.5 rounded-[3px] border border-sky-100">
                                        {{ u.clients_count || 0 }} Clients
                                    </span>
                                    <span v-else class="font-bold text-slate-800 bg-slate-100 px-2 py-0.5 rounded-[3px] border border-slate-200">
                                        {{ u.subscriptions_count || 0 }} VHosts
                                    </span>
                                </td>

                                <!-- Reseller Parent -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap">
                                    <span v-if="u.reseller" class="text-xs text-slate-800 font-medium">
                                        {{ u.reseller.name }}
                                    </span>
                                    <span v-else class="text-slate-400 text-[10.5px] italic">Direct Account</span>
                                </td>

                                <!-- Created -->
                                <td class="py-2.5 px-3 border-r border-slate-100 font-mono text-slate-400 text-[10.5px] whitespace-nowrap">
                                    {{ formatDate(u.created_at) }}
                                </td>

                                <!-- Action Dropdown -->
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <RowActionDropdown>
                                        <!-- View 360 -->
                                        <Link
                                            :href="route('admin.users.show', u.id)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium"
                                        >
                                            <EyeIcon class="w-3.5 h-3.5 text-slate-500" />
                                            <span>Customer 360</span>
                                        </Link>

                                        <!-- Login as Client SSO -->
                                        <button 
                                            type="button" 
                                            @click="impersonateUser(u)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-emerald-700 hover:bg-emerald-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <ArrowRightEndOnRectangleIcon class="w-3.5 h-3.5" />
                                            <span>Login as Client</span>
                                        </button>

                                        <!-- Edit -->
                                        <Link
                                            :href="route('admin.users.edit', u.id)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium"
                                        >
                                            <PencilSquareIcon class="w-3.5 h-3.5 text-slate-500" />
                                            <span>Edit Profile</span>
                                        </Link>

                                        <!-- Reset Password -->
                                        <button 
                                            type="button" 
                                            @click="openPasswordModal(u)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <KeyIcon class="w-3.5 h-3.5 text-slate-500" />
                                            <span>Reset Password</span>
                                        </button>

                                        <!-- Suspend / Unsuspend -->
                                        <button 
                                            type="button" 
                                            @click="toggleStatus(u)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-amber-700 hover:bg-amber-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <PauseIcon v-if="u.status === 'active'" class="w-3.5 h-3.5" />
                                            <PlayIcon v-else class="w-3.5 h-3.5" />
                                            <span>{{ u.status === 'active' ? 'Suspend Account' : 'Unsuspend' }}</span>
                                        </button>

                                        <!-- Delete -->
                                        <button 
                                            type="button" 
                                            @click="openDeleteModal(u)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-rose-600 hover:bg-rose-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <TrashIcon class="w-3.5 h-3.5" />
                                            <span>Delete User</span>
                                        </button>
                                    </RowActionDropdown>
                                </td>
                            </tr>

                            <tr v-if="!users.data || users.data.length === 0">
                                <td colspan="8" class="py-12 text-center text-slate-400">
                                    No customers found matching the search criteria.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <Pagination :links="users.links" :from="users.from" :to="users.to" :total="users.total" />
            </div>
        </div>

        <!-- 1. PASSWORD RESET MODAL -->
        <div v-if="showPasswordModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <KeyIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Reset Password: {{ selectedUser?.name }}
                        </h3>
                    </div>
                    <button @click="showPasswordModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitPasswordReset" class="p-4 space-y-3.5 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">New Password <span class="text-rose-500">*</span></label>
                        <input
                            v-model="passwordForm.password"
                            type="password"
                            required
                            minlength="8"
                            placeholder="Enter secure new password"
                            class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition-all shadow-2xs"
                        />
                        <span v-if="passwordForm.errors.password" class="text-rose-600 text-[10.5px] font-semibold block">{{ passwordForm.errors.password }}</span>
                    </div>

                    <div class="pt-2 flex items-center justify-end gap-2 border-t border-slate-100">
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
                            {{ passwordForm.processing ? 'Resetting...' : 'Save New Password' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 2. DELETE USER MODAL -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-rose-50 text-rose-600 flex items-center justify-center font-bold border border-rose-100">
                            <TrashIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Confirm Customer Deletion
                        </h3>
                    </div>
                    <button @click="showDeleteModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-2.5 text-xs">
                    <p class="text-slate-600">
                        Are you sure you want to permanently delete user account <strong class="text-slate-900">{{ userToDelete?.name }}</strong> ({{ userToDelete?.email }})?
                    </p>
                    <div class="p-2.5 bg-rose-50 border border-rose-200 text-rose-800 rounded-[3px]">
                        <p class="font-bold text-[11px]">⚠️ Account Removal</p>
                        <p class="text-[10.5px] mt-0.5">This will remove all account metadata, access permissions, and revoke active sessions.</p>
                    </div>
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
                        @click="submitDeleteUser"
                        :disabled="deleteForm.processing"
                        class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                    >
                        {{ deleteForm.processing ? 'Deleting...' : 'Delete Customer' }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
