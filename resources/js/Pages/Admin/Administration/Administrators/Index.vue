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
    LockClosedIcon,
    KeyIcon,
    PencilSquareIcon,
    TrashIcon,
    NoSymbolIcon,
    FingerPrintIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    administrators: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    stats: {
        type: Object,
        default: () => ({
            total_admins: 0,
            active_admins: 0,
            two_factor_protected: 0,
            ip_restricted: 0,
        }),
    },
    filters: {
        type: Object,
        default: () => ({ search: '', role: 'all', status: 'all' }),
    },
})

// Flash Feedback Toast
const feedbackMsg = ref('')

// Filter State
const search = ref(props.filters?.search || '')
const currentRole = ref(props.filters?.role || 'all')
const currentStatus = ref(props.filters?.status || 'all')

const applyFilters = () => {
    router.get(route('admin.administration.administrators'), {
        search: search.value || undefined,
        role: currentRole.value !== 'all' ? currentRole.value : undefined,
        status: currentStatus.value !== 'all' ? currentStatus.value : undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const selectRole = (role) => {
    currentRole.value = role
    applyFilters()
}

const resetFilters = () => {
    search.value = ''
    currentRole.value = 'all'
    currentStatus.value = 'all'
    applyFilters()
}

// 1. CREATE / EDIT ADMIN MODAL
const showModal = ref(false)
const isEditing = ref(false)
const editingAdminId = ref(null)

const adminForm = useForm({
    name: '',
    email: '',
    username: '',
    password: '',
    role: 'Super Administrator',
    ip_allowlist: '',
    two_factor_enforced: true,
})

const openCreateModal = () => {
    isEditing.value = false
    editingAdminId.value = null
    adminForm.reset()
    adminForm.role = 'Super Administrator'
    adminForm.two_factor_enforced = true
    showModal.value = true
}

const openEditModal = (admin) => {
    isEditing.value = true
    editingAdminId.value = admin.id
    adminForm.name = admin.name
    adminForm.email = admin.email
    adminForm.username = admin.username || admin.name?.toLowerCase().replace(/\s+/g, '')
    adminForm.password = ''
    adminForm.role = admin.role || 'Super Administrator'
    adminForm.ip_allowlist = Array.isArray(admin.ip_allowlist) ? admin.ip_allowlist.join(', ') : (admin.ip_allowlist || '')
    adminForm.two_factor_enforced = Boolean(admin.two_factor_enforced || admin.has_2fa)
    showModal.value = true
}

const submitAdmin = () => {
    if (isEditing.value) {
        adminForm.put(route('admin.administration.administrators.update', editingAdminId.value), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false
                feedbackMsg.value = 'Administrator account updated.'
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    } else {
        adminForm.post(route('admin.administration.administrators.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false
                feedbackMsg.value = 'New administrator registered.'
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    }
}

// 2. TOGGLE STATUS
const toggleAdmin = (admin) => {
    router.post(route('admin.administration.administrators.toggle', admin.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = 'Administrator status updated.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 3. DELETE MODAL
const showDeleteModal = ref(false)
const adminToDelete = ref(null)
const deleteForm = useForm({})

const openDeleteModal = (admin) => {
    adminToDelete.value = admin
    showDeleteModal.value = true
}

const submitDelete = () => {
    if (!adminToDelete.value) return
    deleteForm.delete(route('admin.administration.administrators.destroy', adminToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false
            feedbackMsg.value = 'Administrator account removed.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}
</script>

<template>
    <Head title="Administrators - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Administration', href: route('admin.administration.administrators') },
                    { label: 'Administrators' }
                ]"
            >
                <template #actions>
                    <Link
                        :href="route('admin.administration.staff')"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <UserGroupIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Staff Members</span>
                    </Link>

                    <button 
                        type="button" 
                        @click="openCreateModal"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <PlusIcon class="w-3.5 h-3.5" />
                        <span>Create Administrator</span>
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
                    title="Total Administrators"
                    :value="String(stats.total_admins || administrators.data?.length || 0)"
                    badge="Super Admins"
                    badgeType="info"
                    color="blue"
                    :icon="ShieldCheckIcon"
                />

                <InfoCard
                    title="Active Accounts"
                    :value="String(stats.active_admins || administrators.data?.filter(a => a.status === 'active' || !a.is_suspended).length || 0)"
                    badge="Online"
                    badgeType="success"
                    color="emerald"
                    :icon="CheckIcon"
                />

                <InfoCard
                    title="2FA Protected"
                    :value="String(stats.two_factor_protected || 0)"
                    badge="MFA Secured"
                    badgeType="success"
                    color="purple"
                    :icon="FingerPrintIcon"
                />

                <InfoCard
                    title="IP Restricted"
                    :value="String(stats.ip_restricted || 0)"
                    badge="Allowlisted"
                    badgeType="info"
                    color="sky"
                    :icon="LockClosedIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Role Tabs -->
            <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        v-for="r in ['all', 'Super Administrator', 'Security Administrator', 'Billing Administrator']"
                        :key="r"
                        type="button"
                        @click="selectRole(r)"
                        :class="currentRole === r ? 'bg-blue-600 text-white' : 'bg-white text-slate-700 hover:bg-slate-50 border border-slate-200'"
                        class="px-3 py-1.5 rounded-[3px] font-bold text-xs uppercase font-mono transition cursor-pointer shadow-2xs"
                    >
                        {{ r }}
                    </button>
                </div>
            </div>

            <!-- 4. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search administrator name, email address, username..."
                @search="applyFilters"
                @filter="applyFilters"
                @reset="resetFilters"
            />

            <!-- 5. Standard Data Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Administrator</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Role Privilege</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Two-Factor Auth</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">IP Allowlist</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Status</th>
                                <th class="py-2.5 px-3 w-14 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(admin, index) in administrators.data" :key="admin.id || index" class="hover:bg-blue-50/30 transition">
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-bold text-slate-900">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs border border-blue-100 shrink-0">
                                            <ShieldCheckIcon class="w-3.5 h-3.5" />
                                        </div>
                                        <div>
                                            <span class="block leading-tight font-bold">{{ admin.name }}</span>
                                            <span class="text-[10px] text-slate-400 font-mono block">{{ admin.email }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase bg-blue-50 text-blue-700 border border-blue-200 font-mono">
                                        {{ admin.role || 'Super Administrator' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="admin.has_2fa || admin.two_factor_enforced ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-500 border-slate-200'"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ admin.has_2fa || admin.two_factor_enforced ? 'Enabled' : 'Disabled' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono text-[11px] text-slate-600">
                                    {{ Array.isArray(admin.ip_allowlist) ? (admin.ip_allowlist.join(', ') || 'Any IP') : (admin.ip_allowlist || 'Any IP') }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="admin.status === 'active' || !admin.is_suspended ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ admin.status || 'active' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <RowActionDropdown>
                                        <button
                                            type="button"
                                            @click="openEditModal(admin)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <PencilSquareIcon class="w-3.5 h-3.5 text-slate-500" />
                                            <span>Edit Administrator</span>
                                        </button>

                                        <button
                                            type="button"
                                            @click="toggleAdmin(admin)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <NoSymbolIcon class="w-3.5 h-3.5 text-slate-500" />
                                            <span>{{ admin.status === 'active' ? 'Suspend Access' : 'Activate Access' }}</span>
                                        </button>

                                        <button
                                            type="button"
                                            @click="openDeleteModal(admin)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-rose-600 hover:bg-rose-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <TrashIcon class="w-3.5 h-3.5" />
                                            <span>Delete Account</span>
                                        </button>
                                    </RowActionDropdown>
                                </td>
                            </tr>

                            <tr v-if="!administrators.data || administrators.data.length === 0">
                                <td colspan="7" class="py-12 text-center text-slate-400 font-sans">
                                    No administrator accounts found.
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
                            <ShieldCheckIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            {{ isEditing ? 'Edit Administrator Account' : 'Register Super Administrator' }}
                        </h3>
                    </div>
                    <button @click="showModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitAdmin" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Full Name <span class="text-rose-500">*</span></label>
                        <input v-model="adminForm.name" type="text" required placeholder="Administrator Name" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Email Address <span class="text-rose-500">*</span></label>
                            <input v-model="adminForm.email" type="email" required placeholder="admin@domain.local" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        </div>

                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Username</label>
                            <input v-model="adminForm.username" type="text" placeholder="admin_sys" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">{{ isEditing ? 'New Password (Leave blank to keep current)' : 'Password' }}</label>
                        <input v-model="adminForm.password" type="password" :required="!isEditing" placeholder="••••••••••••" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Role Privilege</label>
                        <select v-model="adminForm.role" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                            <option value="Super Administrator">Super Administrator (Full Root)</option>
                            <option value="Security Administrator">Security Administrator</option>
                            <option value="Billing Administrator">Billing Administrator</option>
                        </select>
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">IP Whitelist Allowlist</label>
                        <input v-model="adminForm.ip_allowlist" type="text" placeholder="192.168.1.100, 10.0.0.0/24" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <input v-model="adminForm.two_factor_enforced" id="admin_2fa" type="checkbox" class="rounded-[2px] text-blue-600 focus:ring-blue-500 cursor-pointer" />
                        <label for="admin_2fa" class="text-xs text-slate-700 font-medium cursor-pointer">Enforce 2FA two-factor authentication</label>
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
                            :disabled="adminForm.processing"
                            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ adminForm.processing ? 'Saving...' : (isEditing ? 'Save Changes' : 'Create Administrator') }}
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
                            Delete Administrator
                        </h3>
                    </div>
                    <button @click="showDeleteModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-2.5 text-xs">
                    <p class="text-slate-600">
                        Are you sure you want to delete administrator <strong class="text-slate-900 font-mono">[{{ adminToDelete?.name }}]</strong>?
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
                        {{ deleteForm.processing ? 'Deleting...' : 'Delete Administrator' }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
