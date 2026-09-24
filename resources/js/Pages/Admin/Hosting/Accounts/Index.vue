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
    GlobeAltIcon,
    ServerIcon,
    CheckCircleIcon,
    PauseIcon,
    PlayIcon,
    PlusIcon,
    ArrowPathIcon,
    EyeIcon,
    PencilSquareIcon,
    TrashIcon,
    ShieldCheckIcon,
    XMarkIcon,
    ArrowRightEndOnRectangleIcon,
    CpuChipIcon,
    BriefcaseIcon,
    UserCircleIcon,
    FolderIcon,
    ExclamationTriangleIcon,
    AdjustmentsHorizontalIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    accounts: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    stats: {
        type: Object,
        default: () => ({
            total_accounts: 0,
            active_accounts: 0,
            suspended_accounts: 0,
            allocated_disk_formatted: '0 MB',
            allocated_bw_formatted: '0 MB',
        }),
    },
    plans: {
        type: Array,
        default: () => [],
    },
    servers: {
        type: Array,
        default: () => [],
    },
    customers: {
        type: Array,
        default: () => [],
    },
    phpVersions: {
        type: Array,
        default: () => ['8.1', '8.2', '8.3', '8.4', '8.5'],
    },
    filters: {
        type: Object,
        default: () => ({ search: '', status: '', plan_id: '', server_id: '' }),
    },
})

// Flash Feedback
const feedbackMsg = ref('')

// Filters State
const search = ref(props.filters?.search || '')
const status = ref(props.filters?.status || '')
const planId = ref(props.filters?.plan_id || '')
const serverId = ref(props.filters?.server_id || '')

const applyFilters = () => {
    router.get(route('admin.hosting.accounts'), {
        search: search.value || undefined,
        status: status.value || undefined,
        plan_id: planId.value || undefined,
        server_id: serverId.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const resetFilters = () => {
    search.value = ''
    status.value = ''
    planId.value = ''
    serverId.value = ''
    applyFilters()
}

// 1. CREATE / PROVISION MODAL
const showCreateModal = ref(false)
const createForm = useForm({
    user_id: props.customers?.[0]?.id || '',
    domain: '',
    plan_id: props.plans?.[0]?.id || '',
    server_id: props.servers?.[0]?.id || '',
    php_version: '8.3',
    username: '',
})

const autoUsernamePreview = computed(() => {
    if (createForm.username) return createForm.username
    if (!createForm.domain) return 'user'
    return createForm.domain.split('.')[0].replace(/[^a-zA-Z0-9]/g, '').substring(0, 10).toLowerCase()
})

const submitCreateAccount = () => {
    if (!createForm.username) {
        createForm.username = autoUsernamePreview.value
    }
    createForm.post(route('admin.hosting.accounts.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false
            createForm.reset()
            feedbackMsg.value = 'Hosting account provisioned successfully.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 2. CHANGE PLAN MODAL
const showPlanModal = ref(false)
const selectedAccountForPlan = ref(null)
const planForm = useForm({
    plan_id: '',
})

const openPlanModal = (account) => {
    selectedAccountForPlan.value = account
    planForm.plan_id = account.plan_id || (props.plans?.[0]?.id || '')
    showPlanModal.value = true
}

const submitChangePlan = () => {
    if (!selectedAccountForPlan.value) return
    planForm.post(route('admin.hosting.accounts.change-plan', selectedAccountForPlan.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showPlanModal.value = false
            feedbackMsg.value = `Hosting plan updated for '${selectedAccountForPlan.value.domain}'.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 3. CHANGE PHP VERSION MODAL
const showPhpModal = ref(false)
const selectedAccountForPhp = ref(null)
const phpForm = useForm({
    php_version: '8.3',
})

const openPhpModal = (account) => {
    selectedAccountForPhp.value = account
    phpForm.php_version = account.php_version || '8.3'
    showPhpModal.value = true
}

const submitChangePhp = () => {
    if (!selectedAccountForPhp.value) return
    phpForm.post(route('admin.hosting.accounts.change-php', selectedAccountForPhp.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showPhpModal.value = false
            feedbackMsg.value = `PHP runtime for '${selectedAccountForPhp.value.domain}' switched to PHP ${phpForm.php_version}.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 4. SUSPEND / UNSUSPEND
const toggleAccountStatus = (account) => {
    const isSuspended = account.status === 'suspended'
    const action = isSuspended ? 'unsuspend' : 'suspend'
    const msg = isSuspended 
        ? `Are you sure you want to unsuspend account '${account.domain}'?`
        : `Are you sure you want to suspend account '${account.domain}'?`
    
    if (confirm(msg)) {
        router.post(route(`admin.hosting.accounts.${action}`, account.id), {}, {
            preserveScroll: true,
            onSuccess: () => {
                feedbackMsg.value = `Account '${account.domain}' ${action}ed.`
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    }
}

// 5. TERMINATE / DELETE
const showDeleteModal = ref(false)
const accountToDelete = ref(null)
const deleteForm = useForm({})

const openDeleteModal = (account) => {
    accountToDelete.value = account
    showDeleteModal.value = true
}

const submitDeleteAccount = () => {
    if (!accountToDelete.value) return
    deleteForm.delete(route('admin.hosting.accounts.destroy', accountToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false
            feedbackMsg.value = `Hosting account '${accountToDelete.value.domain}' terminated.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 6. IMPERSONATE (LOGIN AS CLIENT)
const impersonateClient = (user) => {
    if (!user) return
    if (confirm(`Login to client dashboard as '${user.name || user.first_name}' (${user.email})?`)) {
        router.post(route('admin.users.impersonate', user.id))
    }
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
    <Head title="Hosting Accounts & Workloads - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Hosting', href: route('admin.hosting.accounts') },
                    { label: 'Accounts' }
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
                        @click="showCreateModal = true"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <PlusIcon class="w-3.5 h-3.5" />
                        <span>Provision Account</span>
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
                    title="Total Workloads"
                    :value="String(stats.total_accounts || 0)"
                    badge="VHosts"
                    badgeType="info"
                    color="blue"
                    :icon="GlobeAltIcon"
                />

                <InfoCard
                    title="Active Workloads"
                    :value="String(stats.active_accounts || 0)"
                    badge="Serving"
                    badgeType="success"
                    color="emerald"
                    :icon="CheckCircleIcon"
                />

                <InfoCard
                    title="Disk Quota"
                    :value="String(stats.allocated_disk_formatted || '0 MB')"
                    badge="Allocated"
                    badgeType="info"
                    color="sky"
                    :icon="ServerIcon"
                />

                <InfoCard
                    title="Suspended Accounts"
                    :value="String(stats.suspended_accounts || 0)"
                    :badge="stats.suspended_accounts > 0 ? 'Locked' : 'Nominal'"
                    :badgeType="stats.suspended_accounts > 0 ? 'warning' : 'success'"
                    color="amber"
                    :icon="PauseIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Standard Unified Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search by domain, username, customer, or company..."
                @search="applyFilters"
                @filter="applyFilters"
                @reset="resetFilters"
            >
                <FilterSelect
                    v-model="status"
                    label="Status"
                    :options="[
                        { label: 'Active', value: 'active' },
                        { label: 'Suspended', value: 'suspended' },
                        { label: 'Cancelled', value: 'cancelled' }
                    ]"
                    placeholder="All Statuses"
                    @change="applyFilters"
                />

                <FilterSelect
                    v-if="plans && plans.length > 0"
                    v-model="planId"
                    label="Package"
                    :options="plans.map(p => ({ label: `${p.name} (${p.disk_space} MB)`, value: p.id }))"
                    placeholder="All Hosting Plans"
                    @change="applyFilters"
                />

                <FilterSelect
                    v-if="servers && servers.length > 0"
                    v-model="serverId"
                    label="Server Node"
                    :options="servers.map(s => ({ label: `${s.name} (${s.ip_address})`, value: s.id }))"
                    placeholder="All Server Nodes"
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
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Primary Domain & VHost</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Subscriber / Owner</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Hosting Plan</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Server Node</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">PHP Engine</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Status</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Created</th>
                                <th class="py-2.5 px-3 w-16">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(acc, index) in accounts.data" :key="acc.id" class="hover:bg-blue-50/30 transition">
                                <!-- # -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ ((accounts.current_page || 1) - 1) * (accounts.per_page || 15) + (index + 1) }}
                                </td>

                                <!-- Domain & VHost -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs border border-blue-100 shrink-0">
                                            <GlobeAltIcon class="w-3.5 h-3.5" />
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-1.5">
                                                <a :href="`http://${acc.domain}`" target="_blank" class="font-bold text-slate-900 hover:text-blue-600 flex items-center gap-1">
                                                    <span>{{ acc.domain }}</span>
                                                    <span class="text-slate-400 text-[10px]">↗</span>
                                                </a>
                                                <span class="px-1.5 py-0.2 rounded-[2px] bg-slate-100 font-mono text-[10px] text-slate-600 border border-slate-200">
                                                    @{{ acc.username }}
                                                </span>
                                            </div>
                                            <span class="text-[10px] font-mono text-slate-400 block max-w-xs truncate" :title="acc.document_root">
                                                {{ acc.document_root }}
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Subscriber / Owner -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap">
                                    <div v-if="acc.user" class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-[3px] bg-slate-100 text-slate-700 flex items-center justify-center font-bold text-[10px] border border-slate-200 shrink-0">
                                            {{ (acc.user.first_name || acc.user.username || acc.user.email).charAt(0).toUpperCase() }}
                                        </div>
                                        <div>
                                            <Link :href="route('admin.users.show', acc.user.id)" class="font-bold text-slate-900 hover:text-blue-600 block leading-tight">
                                                {{ acc.user.first_name }} {{ acc.user.last_name }}
                                            </Link>
                                            <span class="text-[10px] font-mono text-slate-400 block">{{ acc.user.email }}</span>
                                        </div>
                                    </div>
                                    <span v-else class="text-slate-400 italic">Unassigned</span>
                                </td>

                                <!-- Hosting Plan -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap">
                                    <div v-if="acc.plan">
                                        <span class="font-bold text-slate-900 block leading-tight">{{ acc.plan.name }}</span>
                                        <span class="text-[10px] font-mono text-slate-500">
                                            {{ acc.plan.disk_space }} MB Disk
                                        </span>
                                    </div>
                                    <span v-else class="text-slate-400 italic">Custom Plan</span>
                                </td>

                                <!-- Server Node -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap">
                                    <div v-if="acc.server" class="flex items-center justify-center gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="acc.server.status === 'online' ? 'bg-emerald-500' : 'bg-amber-500'"></span>
                                        <span class="font-medium text-slate-800">{{ acc.server.name }}</span>
                                    </div>
                                    <span v-else class="text-slate-400 italic">Master Node</span>
                                </td>

                                <!-- PHP Engine -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <button
                                        type="button"
                                        @click="openPhpModal(acc)"
                                        class="px-2 py-0.5 rounded-[3px] text-[10.5px] font-bold font-mono bg-purple-50 text-purple-700 border border-purple-200 hover:bg-purple-100 transition cursor-pointer"
                                        title="Click to switch PHP version"
                                    >
                                        PHP {{ acc.php_version || '8.3' }} ✎
                                    </button>
                                </td>

                                <!-- Status -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="[
                                            acc.status === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                                            acc.status === 'suspended' ? 'bg-amber-50 text-amber-700 border-amber-200' :
                                            'bg-rose-50 text-rose-700 border-rose-200'
                                        ]"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-[3px] text-[10px] font-bold capitalize border whitespace-nowrap"
                                    >
                                        <span 
                                            class="w-1.5 h-1.5 rounded-full"
                                            :class="acc.status === 'active' ? 'bg-emerald-500' : (acc.status === 'suspended' ? 'bg-amber-500' : 'bg-rose-500')"
                                        ></span>
                                        {{ acc.status }}
                                    </span>
                                </td>

                                <!-- Created -->
                                <td class="py-2.5 px-3 border-r border-slate-100 font-mono text-slate-400 text-[10.5px] whitespace-nowrap">
                                    {{ formatDate(acc.created_at) }}
                                </td>

                                <!-- Action Dropdown -->
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <RowActionDropdown>
                                        <!-- Login as Client SSO -->
                                        <button 
                                            v-if="acc.user"
                                            type="button" 
                                            @click="impersonateClient(acc.user)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-emerald-700 hover:bg-emerald-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <ArrowRightEndOnRectangleIcon class="w-3.5 h-3.5" />
                                            <span>Login Client SSO</span>
                                        </button>

                                        <!-- Change Plan -->
                                        <button 
                                            type="button" 
                                            @click="openPlanModal(acc)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <AdjustmentsHorizontalIcon class="w-3.5 h-3.5 text-slate-500" />
                                            <span>Change Plan</span>
                                        </button>

                                        <!-- Change PHP -->
                                        <button 
                                            type="button" 
                                            @click="openPhpModal(acc)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <CpuChipIcon class="w-3.5 h-3.5 text-slate-500" />
                                            <span>Switch PHP</span>
                                        </button>

                                        <!-- Suspend / Unsuspend -->
                                        <button 
                                            type="button" 
                                            @click="toggleAccountStatus(acc)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-amber-700 hover:bg-amber-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <PauseIcon v-if="acc.status === 'active'" class="w-3.5 h-3.5" />
                                            <PlayIcon v-else class="w-3.5 h-3.5" />
                                            <span>{{ acc.status === 'active' ? 'Suspend Account' : 'Unsuspend' }}</span>
                                        </button>

                                        <!-- Terminate / Delete -->
                                        <button 
                                            type="button" 
                                            @click="openDeleteModal(acc)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-rose-600 hover:bg-rose-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <TrashIcon class="w-3.5 h-3.5" />
                                            <span>Terminate Account</span>
                                        </button>
                                    </RowActionDropdown>
                                </td>
                            </tr>

                            <tr v-if="!accounts.data || accounts.data.length === 0">
                                <td colspan="9" class="py-12 text-center text-slate-400">
                                    No hosting accounts found matching the criteria.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <Pagination :links="accounts.links" :from="accounts.from" :to="accounts.to" :total="accounts.total" />
            </div>
        </div>

        <!-- 1. PROVISION NEW HOSTING ACCOUNT MODAL -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-lg overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <GlobeAltIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Provision New Hosting Account
                        </h3>
                    </div>
                    <button @click="showCreateModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitCreateAccount" class="p-4 space-y-3.5 text-xs">
                    <!-- Customer Owner -->
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">
                            Customer / Subscriber <span class="text-rose-500">*</span>
                        </label>
                        <select
                            v-model="createForm.user_id"
                            required
                            class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition-all shadow-2xs cursor-pointer"
                        >
                            <option v-for="c in customers" :key="c.id" :value="c.id">
                                {{ c.first_name }} {{ c.last_name }} ({{ c.email }})
                            </option>
                        </select>
                        <span v-if="createForm.errors.user_id" class="text-rose-600 text-[10.5px] mt-0.5 block font-semibold">{{ createForm.errors.user_id }}</span>
                    </div>

                    <!-- Domain Name -->
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">
                            Primary Domain Name <span class="text-rose-500">*</span>
                        </label>
                        <input
                            v-model="createForm.domain"
                            type="text"
                            required
                            placeholder="e.g. mysite.com (without http://)"
                            class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition-all shadow-2xs"
                        />
                        <span v-if="createForm.errors.domain" class="text-rose-600 text-[10.5px] mt-0.5 block font-semibold">{{ createForm.errors.domain }}</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <!-- Hosting Package -->
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">
                                Hosting Plan <span class="text-rose-500">*</span>
                            </label>
                            <select
                                v-model="createForm.plan_id"
                                required
                                class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition-all shadow-2xs cursor-pointer"
                            >
                                <option v-for="p in plans" :key="p.id" :value="p.id">
                                    {{ p.name }} ({{ p.disk_space }} MB)
                                </option>
                            </select>
                            <span v-if="createForm.errors.plan_id" class="text-rose-600 text-[10.5px] mt-0.5 block font-semibold">{{ createForm.errors.plan_id }}</span>
                        </div>

                        <!-- Server Node -->
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">
                                Target Server Node <span class="text-rose-500">*</span>
                            </label>
                            <select
                                v-model="createForm.server_id"
                                required
                                class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition-all shadow-2xs cursor-pointer"
                            >
                                <option v-for="s in servers" :key="s.id" :value="s.id">
                                    {{ s.name }} ({{ s.ip_address }})
                                </option>
                            </select>
                            <span v-if="createForm.errors.server_id" class="text-rose-600 text-[10.5px] mt-0.5 block font-semibold">{{ createForm.errors.server_id }}</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <!-- PHP Engine -->
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">PHP Engine</label>
                            <select
                                v-model="createForm.php_version"
                                class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition-all shadow-2xs cursor-pointer"
                            >
                                <option v-for="ver in phpVersions" :key="ver" :value="ver">
                                    PHP {{ ver }}
                                </option>
                            </select>
                        </div>

                        <!-- Username -->
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Unix Username</label>
                            <input
                                v-model="createForm.username"
                                type="text"
                                :placeholder="autoUsernamePreview"
                                class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition-all shadow-2xs"
                            />
                        </div>
                    </div>

                    <div class="p-2.5 bg-slate-50/70 border border-slate-200 text-slate-600 rounded-[3px] text-[10.5px] font-mono">
                        Doc Root: /var/www/vhosts/<strong>{{ autoUsernamePreview }}</strong>/{{ createForm.domain || 'domain.com' }}/public_html
                    </div>

                    <div class="pt-2 flex items-center justify-end gap-2 border-t border-slate-100">
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
                            {{ createForm.processing ? 'Provisioning...' : 'Deploy Account' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 2. CHANGE PLAN MODAL -->
        <div v-if="showPlanModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <AdjustmentsHorizontalIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Change Plan: {{ selectedAccountForPlan?.domain }}
                        </h3>
                    </div>
                    <button @click="showPlanModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-3.5 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Select New Package</label>
                        <select
                            v-model="planForm.plan_id"
                            class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition-all shadow-2xs cursor-pointer"
                        >
                            <option v-for="p in plans" :key="p.id" :value="p.id">
                                {{ p.name }} ({{ p.disk_space }} MB Storage • {{ p.bandwidth }} MB BW)
                            </option>
                        </select>
                    </div>
                </div>

                <div class="px-4 py-2.5 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                    <button
                        type="button"
                        @click="showPlanModal = false"
                        class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="submitChangePlan"
                        :disabled="planForm.processing"
                        class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                    >
                        {{ planForm.processing ? 'Updating...' : 'Save Plan Change' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- 3. CHANGE PHP MODAL -->
        <div v-if="showPhpModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-purple-50 text-purple-600 flex items-center justify-center font-bold border border-purple-100">
                            <CpuChipIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Switch PHP Runtime: {{ selectedAccountForPhp?.domain }}
                        </h3>
                    </div>
                    <button @click="showPhpModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Target PHP Version</label>
                        <select
                            v-model="phpForm.php_version"
                            class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition-all shadow-2xs cursor-pointer"
                        >
                            <option v-for="ver in phpVersions" :key="ver" :value="ver">
                                PHP {{ ver }} (PHP-FPM Socket)
                            </option>
                        </select>
                    </div>
                    <p class="text-slate-500 text-[10.5px]">
                        Nginx VirtualHost socket will automatically update to <code>/run/php/php{{ phpForm.php_version }}-fpm.sock</code> and reload cleanly.
                    </p>
                </div>

                <div class="px-4 py-2.5 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                    <button
                        type="button"
                        @click="showPhpModal = false"
                        class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="submitChangePhp"
                        :disabled="phpForm.processing"
                        class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                    >
                        {{ phpForm.processing ? 'Switching...' : 'Switch PHP' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- 4. TERMINATE / DELETE MODAL -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-rose-50 text-rose-600 flex items-center justify-center font-bold border border-rose-100">
                            <TrashIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Confirm Account Termination
                        </h3>
                    </div>
                    <button @click="showDeleteModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-2.5 text-xs">
                    <p class="text-slate-600">
                        Are you sure you want to permanently terminate hosting account <strong class="text-slate-900">{{ accountToDelete?.domain }}</strong> (@{{ accountToDelete?.username }})?
                    </p>
                    <div class="p-2.5 bg-rose-50 border border-rose-200 text-rose-800 rounded-[3px]">
                        <p class="font-bold text-[11px]">⚠️ Irreversible Workload Deletion</p>
                        <p class="text-[10.5px] mt-0.5">This will disable the Nginx VirtualHost, revoke databases and FTP associations, and remove the subscription record.</p>
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
                        @click="submitDeleteAccount"
                        :disabled="deleteForm.processing"
                        class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                    >
                        {{ deleteForm.processing ? 'Terminating...' : 'Terminate Account' }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
