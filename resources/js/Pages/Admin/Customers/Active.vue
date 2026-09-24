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
    UserGroupIcon,
    GlobeAltIcon,
    CheckCircleIcon,
    BriefcaseIcon,
    PlusIcon,
    ArrowPathIcon,
    EyeIcon,
    PencilSquareIcon,
    PauseIcon,
    KeyIcon,
    XMarkIcon,
    ArrowRightEndOnRectangleIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    customers: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    stats: {
        type: Object,
        default: () => ({
            active_clients: 0,
            total_clients: 0,
            active_subscriptions: 0,
            active_resellers: 0,
            operational_rate: 100,
        }),
    },
    resellers: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({ search: '', reseller_id: '' }),
    },
})

// Filter State
const search = ref(props.filters?.search || '')
const resellerId = ref(props.filters?.reseller_id || '')

const applyFilters = () => {
    router.get(route('admin.customers.active'), {
        search: search.value || undefined,
        reseller_id: resellerId.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const resetFilters = () => {
    search.value = ''
    resellerId.value = ''
    applyFilters()
}

// Flash Message
const feedbackMsg = ref('')

// Suspend Modal State
const showSuspendModal = ref(false)
const userToSuspend = ref(null)

const openSuspendModal = (user) => {
    userToSuspend.value = user
    showSuspendModal.value = true
}

const submitSuspend = () => {
    if (!userToSuspend.value) return
    router.post(route('admin.users.toggle-status', userToSuspend.value.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            showSuspendModal.value = false
            feedbackMsg.value = `Client account '${userToSuspend.value.name}' suspended.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        },
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
const passwordForm = useForm({ password: '' })

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
    <Head title="Active Customers - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Customers', href: '#' },
                    { label: 'Active Customers' }
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
                    title="Active Customers"
                    :value="String(stats.active_clients || 0)"
                    badge="Active"
                    badgeType="success"
                    color="emerald"
                    :icon="UserGroupIcon"
                />

                <InfoCard
                    title="Operational Health"
                    :value="`${stats.operational_rate || 100}%`"
                    badge="Online"
                    badgeType="info"
                    color="blue"
                    :icon="CheckCircleIcon"
                />

                <InfoCard
                    title="Active Subscriptions"
                    :value="String(stats.active_subscriptions || 0)"
                    badge="VHosts"
                    badgeType="info"
                    color="sky"
                    :icon="GlobeAltIcon"
                />

                <InfoCard
                    title="Active Resellers"
                    :value="String(stats.active_resellers || 0)"
                    badge="Partners"
                    badgeType="success"
                    color="purple"
                    :icon="BriefcaseIcon"
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
                    v-if="resellers && resellers.length > 0"
                    v-model="resellerId"
                    label="Reseller Parent"
                    :options="resellers.map(r => ({ label: `${r.name} (${r.email})`, value: r.id }))"
                    placeholder="All Resellers & Direct"
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
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Active Customer Identity</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Role</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Account Status</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Active Workloads</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Parent Reseller</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Member Since</th>
                                <th class="py-2.5 px-3 w-16">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(c, index) in customers.data" :key="c.id" class="hover:bg-blue-50/30 transition">
                                <!-- # -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ ((customers.current_page || 1) - 1) * (customers.per_page || 15) + (index + 1) }}
                                </td>

                                <!-- Customer Identity -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-[3px] bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-xs border border-emerald-100 shrink-0">
                                            {{ (c.first_name || c.username || c.email || 'C').charAt(0).toUpperCase() }}
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-1.5">
                                                <Link :href="route('admin.users.show', c.id)" class="font-bold text-slate-900 hover:text-blue-600 block leading-tight">
                                                    {{ c.name || `${c.first_name || ''} ${c.last_name || ''}` }}
                                                </Link>
                                                <span v-if="c.company" class="text-[10px] text-slate-400 font-normal">({{ c.company }})</span>
                                            </div>
                                            <span class="text-[10px] font-mono text-slate-400 block">{{ c.email }} • @{{ c.username }}</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Role -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="[
                                            c.role === 'admin' ? 'bg-purple-50 text-purple-700 border-purple-200' :
                                            c.role === 'reseller' ? 'bg-sky-50 text-sky-700 border-sky-200' :
                                            'bg-slate-100 text-slate-700 border-slate-200'
                                        ]"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ c.role }}
                                    </span>
                                </td>

                                <!-- Status -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-[3px] text-[10px] font-bold capitalize border bg-emerald-50 text-emerald-700 border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Active
                                    </span>
                                </td>

                                <!-- Workloads -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap font-mono text-[10.5px]">
                                    <span class="font-bold text-slate-800 bg-slate-100 px-2 py-0.5 rounded-[3px] border border-slate-200">
                                        {{ c.subscriptions_count || 0 }} VHosts
                                    </span>
                                </td>

                                <!-- Reseller Parent -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap">
                                    <span v-if="c.reseller" class="text-xs text-slate-800 font-medium">
                                        {{ c.reseller.name }}
                                    </span>
                                    <span v-else class="text-slate-400 text-[10.5px] italic">Direct Account</span>
                                </td>

                                <!-- Created -->
                                <td class="py-2.5 px-3 border-r border-slate-100 font-mono text-slate-400 text-[10.5px] whitespace-nowrap">
                                    {{ formatDate(c.created_at) }}
                                </td>

                                <!-- Action Dropdown -->
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <RowActionDropdown>
                                        <!-- View 360 -->
                                        <Link
                                            :href="route('admin.users.show', c.id)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium"
                                        >
                                            <EyeIcon class="w-3.5 h-3.5 text-slate-500" />
                                            <span>Customer 360</span>
                                        </Link>

                                        <!-- Login as Client SSO -->
                                        <button 
                                            type="button" 
                                            @click="impersonateUser(c)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-emerald-700 hover:bg-emerald-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <ArrowRightEndOnRectangleIcon class="w-3.5 h-3.5" />
                                            <span>Login as Client</span>
                                        </button>

                                        <!-- Edit -->
                                        <Link
                                            :href="route('admin.users.edit', c.id)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium"
                                        >
                                            <PencilSquareIcon class="w-3.5 h-3.5 text-slate-500" />
                                            <span>Edit Profile</span>
                                        </Link>

                                        <!-- Reset Password -->
                                        <button 
                                            type="button" 
                                            @click="openPasswordModal(c)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <KeyIcon class="w-3.5 h-3.5 text-slate-500" />
                                            <span>Reset Password</span>
                                        </button>

                                        <!-- Suspend -->
                                        <button 
                                            type="button" 
                                            @click="openSuspendModal(c)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-amber-700 hover:bg-amber-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <PauseIcon class="w-3.5 h-3.5" />
                                            <span>Suspend Customer</span>
                                        </button>
                                    </RowActionDropdown>
                                </td>
                            </tr>

                            <tr v-if="!customers.data || customers.data.length === 0">
                                <td colspan="8" class="py-12 text-center text-slate-400">
                                    No active customers found matching your criteria.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <Pagination :links="customers.links" :from="customers.from" :to="customers.to" :total="customers.total" />
            </div>
        </div>

        <!-- 1. SUSPEND CONFIRMATION MODAL -->
        <div v-if="showSuspendModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-amber-50 text-amber-600 flex items-center justify-center font-bold border border-amber-100">
                            <PauseIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Suspend Customer Account
                        </h3>
                    </div>
                    <button @click="showSuspendModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-2.5 text-xs">
                    <p class="text-slate-600">
                        Are you sure you want to suspend access for <strong class="text-slate-900">{{ userToSuspend?.name }}</strong>?
                    </p>
                    <div class="p-2.5 bg-amber-50 border border-amber-200 text-amber-800 rounded-[3px]">
                        <p class="font-bold text-[11px]">⚠️ Access Restriction</p>
                        <p class="text-[10.5px] mt-0.5">Customer login sessions will be immediately terminated and dashboard access will be restricted.</p>
                    </div>
                </div>

                <div class="px-4 py-2.5 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                    <button
                        type="button"
                        @click="showSuspendModal = false"
                        class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="submitSuspend"
                        class="px-3.5 py-1.5 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer"
                    >
                        Suspend Account
                    </button>
                </div>
            </div>
        </div>

        <!-- 2. PASSWORD RESET MODAL -->
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
    </AdminLayout>
</template>
