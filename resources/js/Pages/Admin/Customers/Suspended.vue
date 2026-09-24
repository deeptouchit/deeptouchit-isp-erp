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
    ExclamationTriangleIcon,
    PauseIcon,
    PlayIcon,
    GlobeAltIcon,
    CheckCircleIcon,
    BriefcaseIcon,
    ArrowPathIcon,
    EyeIcon,
    PencilSquareIcon,
    TrashIcon,
    XMarkIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    customers: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    stats: {
        type: Object,
        default: () => ({
            suspended_clients: 0,
            total_clients: 0,
            frozen_subscriptions: 0,
            active_clients: 0,
            quarantine_rate: 0,
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
    router.get(route('admin.customers.suspended'), {
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

// Unsuspend Modal State
const showUnsuspendModal = ref(false)
const userToUnsuspend = ref(null)

const openUnsuspendModal = (user) => {
    userToUnsuspend.value = user
    showUnsuspendModal.value = true
}

const submitUnsuspend = () => {
    if (!userToUnsuspend.value) return
    router.post(route('admin.users.toggle-status', userToUnsuspend.value.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            showUnsuspendModal.value = false
            feedbackMsg.value = `Client account '${userToUnsuspend.value.name}' reactivated successfully.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        },
    })
}

// Delete / Terminate Modal State
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
            feedbackMsg.value = `Customer account '${userToDelete.value.name}' terminated.`
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
    <Head title="Suspended Customers - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Customers', href: '#' },
                    { label: 'Suspended Customers' }
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
                    title="Suspended Clients"
                    :value="String(stats.suspended_clients || 0)"
                    badge="Locked"
                    badgeType="danger"
                    color="rose"
                    :icon="PauseIcon"
                />

                <InfoCard
                    title="Quarantine Rate"
                    :value="`${stats.quarantine_rate || 0}%`"
                    :badge="stats.quarantine_rate > 0 ? 'Review' : 'Optimal'"
                    :badgeType="stats.quarantine_rate > 0 ? 'warning' : 'success'"
                    color="amber"
                    :icon="ExclamationTriangleIcon"
                />

                <InfoCard
                    title="Frozen Subscriptions"
                    :value="String(stats.frozen_subscriptions || 0)"
                    badge="Disabled"
                    badgeType="warning"
                    color="amber"
                    :icon="GlobeAltIcon"
                />

                <InfoCard
                    title="Healthy Active Clients"
                    :value="String(stats.active_clients || 0)"
                    badge="Active"
                    badgeType="success"
                    color="emerald"
                    :icon="CheckCircleIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search suspended clients by name, email, username, or company..."
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
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Suspended Identity</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Role</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Account Status</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Impacted Workloads</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Parent Reseller</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Suspended Date</th>
                                <th class="py-2.5 px-3 w-16">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(c, index) in customers.data" :key="c.id" class="hover:bg-blue-50/30 transition">
                                <!-- # -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ ((customers.current_page || 1) - 1) * (customers.per_page || 15) + (index + 1) }}
                                </td>

                                <!-- Suspended Identity -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-[3px] bg-rose-50 text-rose-600 flex items-center justify-center font-bold text-xs border border-rose-100 shrink-0">
                                            {{ (c.first_name || c.username || c.email || 'S').charAt(0).toUpperCase() }}
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
                                    <span class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border bg-slate-100 text-slate-700 border-slate-200 font-mono">
                                        {{ c.role }}
                                    </span>
                                </td>

                                <!-- Status -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-[3px] text-[10px] font-bold capitalize border bg-rose-50 text-rose-700 border-rose-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        Suspended
                                    </span>
                                </td>

                                <!-- Impacted Workloads -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap font-mono text-[10.5px]">
                                    <span class="font-bold text-amber-800 bg-amber-50 px-2 py-0.5 rounded-[3px] border border-amber-200">
                                        {{ c.subscriptions_count || 0 }} Frozen VHosts
                                    </span>
                                </td>

                                <!-- Parent Reseller -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap">
                                    <span v-if="c.reseller" class="text-xs text-slate-800 font-medium">
                                        {{ c.reseller.name }}
                                    </span>
                                    <span v-else class="text-slate-400 text-[10.5px] italic">Direct Account</span>
                                </td>

                                <!-- Updated / Suspended Date -->
                                <td class="py-2.5 px-3 border-r border-slate-100 font-mono text-slate-400 text-[10.5px] whitespace-nowrap">
                                    {{ formatDate(c.updated_at) }}
                                </td>

                                <!-- Action Dropdown -->
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <RowActionDropdown>
                                        <!-- Reactivate / Unsuspend -->
                                        <button 
                                            type="button" 
                                            @click="openUnsuspendModal(c)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-emerald-700 hover:bg-emerald-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <PlayIcon class="w-3.5 h-3.5" />
                                            <span>Reactivate Customer</span>
                                        </button>

                                        <!-- View 360 -->
                                        <Link
                                            :href="route('admin.users.show', c.id)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium"
                                        >
                                            <EyeIcon class="w-3.5 h-3.5 text-slate-500" />
                                            <span>Customer 360</span>
                                        </Link>

                                        <!-- Edit -->
                                        <Link
                                            :href="route('admin.users.edit', c.id)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium"
                                        >
                                            <PencilSquareIcon class="w-3.5 h-3.5 text-slate-500" />
                                            <span>Edit Profile</span>
                                        </Link>

                                        <!-- Terminate / Delete -->
                                        <button 
                                            type="button" 
                                            @click="openDeleteModal(c)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-rose-600 hover:bg-rose-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <TrashIcon class="w-3.5 h-3.5" />
                                            <span>Terminate Customer</span>
                                        </button>
                                    </RowActionDropdown>
                                </td>
                            </tr>

                            <tr v-if="!customers.data || customers.data.length === 0">
                                <td colspan="8" class="py-12 text-center text-slate-400">
                                    No suspended customers currently on record.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <Pagination :links="customers.links" :from="customers.from" :to="customers.to" :total="customers.total" />
            </div>
        </div>

        <!-- 1. UNSUSPEND CONFIRMATION MODAL -->
        <div v-if="showUnsuspendModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold border border-emerald-100">
                            <PlayIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Reactivate Customer Access
                        </h3>
                    </div>
                    <button @click="showUnsuspendModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-2.5 text-xs">
                    <p class="text-slate-600">
                        Restore active account status and login privileges for <strong class="text-slate-900">{{ userToUnsuspend?.name }}</strong>?
                    </p>
                    <div class="p-2.5 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-[3px]">
                        <p class="font-bold text-[11px]">✓ Access Restoration</p>
                        <p class="text-[10.5px] mt-0.5">Customer dashboard credentials will be reactivated immediately.</p>
                    </div>
                </div>

                <div class="px-4 py-2.5 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                    <button
                        type="button"
                        @click="showUnsuspendModal = false"
                        class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="submitUnsuspend"
                        class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer"
                    >
                        Reactivate Customer
                    </button>
                </div>
            </div>
        </div>

        <!-- 2. DELETE CONFIRMATION MODAL -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-rose-50 text-rose-600 flex items-center justify-center font-bold border border-rose-100">
                            <TrashIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Permanent Account Termination
                        </h3>
                    </div>
                    <button @click="showDeleteModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-2.5 text-xs">
                    <p class="text-slate-600">
                        Permanently purge customer account <strong class="text-slate-900">{{ userToDelete?.name }}</strong>?
                    </p>
                    <div class="p-2.5 bg-rose-50 border border-rose-200 text-rose-800 rounded-[3px]">
                        <p class="font-bold text-[11px]">⚠️ Irreversible Deletion</p>
                        <p class="text-[10.5px] mt-0.5">This will delete all client records and access credentials permanently.</p>
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
                        {{ deleteForm.processing ? 'Purging...' : 'Terminate Customer' }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
