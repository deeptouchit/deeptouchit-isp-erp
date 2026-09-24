<script setup>
import { ref, computed } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'
import DataTableFilter from '@/Components/UI/DataTableFilter.vue'
import FilterSelect from '@/Components/UI/FilterSelect.vue'
import RowActionDropdown from '@/Components/UI/RowActionDropdown.vue'
import RefreshButton from '@/Components/UI/RefreshButton.vue'

import {
    Squares2X2Icon,
    TableCellsIcon,
    PlusIcon,
    ArrowPathIcon,
    PencilSquareIcon,
    TrashIcon,
    CheckCircleIcon,
    XMarkIcon,
    CubeIcon,
    UserGroupIcon,
    BanknotesIcon,
    ShieldCheckIcon,
    CommandLineIcon,
    DocumentDuplicateIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    plans: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            total_plans: 0,
            active_plans: 0,
            total_subscribers: 0,
            avg_price: 0,
        }),
    },
    filters: {
        type: Object,
        default: () => ({ search: '', is_active: '' }),
    },
})

// Flash Feedback Toast
const feedbackMsg = ref('')

// Filter State
const search = ref(props.filters?.search || '')
const isActive = ref(props.filters?.is_active ?? '')
const viewMode = ref('table') // default to 'table' or 'grid'
const billingPeriod = ref('monthly') // 'monthly' or 'yearly'

const filteredPlans = computed(() => {
    return props.plans.filter(plan => {
        const matchesStatus = isActive.value === '' || 
            (isActive.value === '1' ? plan.is_active : !plan.is_active)

        const q = search.value.toLowerCase()
        const matchesSearch = !q ||
            plan.name.toLowerCase().includes(q) ||
            plan.slug.toLowerCase().includes(q) ||
            (plan.description && plan.description.toLowerCase().includes(q))

        return matchesStatus && matchesSearch
    })
})

const resetFilters = () => {
    search.value = ''
    isActive.value = ''
}

// Storage Formatter Helper
const formatStorage = (mb) => {
    if (!mb) return '0 MB'
    if (mb >= 1024) {
        return `${(mb / 1024).toFixed(mb % 1024 === 0 ? 0 : 1)} GB`
    }
    return `${mb} MB`
}

// 1. CREATE / EDIT MODAL STATE
const showModal = ref(false)
const isEditing = ref(false)
const editingPlanId = ref(null)
const activeTab = ref('general')

const planForm = useForm({
    name: '',
    slug: '',
    description: '',
    disk_space: 2048,
    bandwidth: 20480,
    max_domains: 1,
    max_subdomains: 5,
    max_databases: 2,
    max_email_accounts: 5,
    max_ftp_accounts: 2,
    cpu_limit: 100,
    ram_limit: 1024,
    php_version_default: '8.2',
    price_monthly: 299.00,
    price_yearly: 2990.00,
    setup_fee: 0.00,
    sort_order: 0,
    auto_ssl: true,
    allow_ssh_access: false,
    allow_custom_php_ini: true,
    allow_git_deploy: true,
    allow_redis: false,
    redis_memory_mb: 64,
    allow_memcached: false,
    allow_nodejs: false,
    allow_python: false,
    allow_cron_jobs: true,
    allow_backups: true,
    is_active: true,
})

const openCreateModal = () => {
    isEditing.value = false
    editingPlanId.value = null
    activeTab.value = 'general'
    planForm.reset()
    planForm.clearErrors()
    showModal.value = true
}

const openEditModal = (plan) => {
    isEditing.value = true
    editingPlanId.value = plan.id
    activeTab.value = 'general'
    planForm.clearErrors()
    planForm.name = plan.name
    planForm.slug = plan.slug
    planForm.description = plan.description || ''
    planForm.disk_space = plan.disk_space
    planForm.bandwidth = plan.bandwidth
    planForm.max_domains = plan.max_domains
    planForm.max_subdomains = plan.max_subdomains ?? 5
    planForm.max_databases = plan.max_databases ?? 2
    planForm.max_email_accounts = plan.max_email_accounts ?? 5
    planForm.max_ftp_accounts = plan.max_ftp_accounts ?? 2
    planForm.cpu_limit = plan.cpu_limit ?? 100
    planForm.ram_limit = plan.ram_limit ?? 1024
    planForm.php_version_default = plan.php_version_default || '8.2'
    planForm.price_monthly = plan.price_monthly
    planForm.price_yearly = plan.price_yearly
    planForm.setup_fee = plan.setup_fee ?? 0.00
    planForm.sort_order = plan.sort_order ?? 0
    planForm.auto_ssl = Boolean(plan.auto_ssl)
    planForm.allow_ssh_access = Boolean(plan.allow_ssh_access)
    planForm.allow_custom_php_ini = plan.allow_custom_php_ini !== undefined ? Boolean(plan.allow_custom_php_ini) : true
    planForm.allow_git_deploy = plan.allow_git_deploy !== undefined ? Boolean(plan.allow_git_deploy) : true
    planForm.allow_redis = Boolean(plan.allow_redis)
    planForm.redis_memory_mb = plan.redis_memory_mb ?? 64
    planForm.allow_memcached = Boolean(plan.allow_memcached)
    planForm.allow_nodejs = Boolean(plan.allow_nodejs)
    planForm.allow_python = Boolean(plan.allow_python)
    planForm.allow_cron_jobs = plan.allow_cron_jobs !== undefined ? Boolean(plan.allow_cron_jobs) : true
    planForm.allow_backups = plan.allow_backups !== undefined ? Boolean(plan.allow_backups) : true
    planForm.is_active = Boolean(plan.is_active)
    showModal.value = true
}

const submitPlanForm = () => {
    if (isEditing.value) {
        planForm.put(route('admin.plans.update', editingPlanId.value), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false
                feedbackMsg.value = `Package '${planForm.name}' updated successfully.`
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    } else {
        planForm.post(route('admin.plans.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false
                feedbackMsg.value = `Package '${planForm.name}' created successfully.`
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    }
}

// 2. TOGGLE ACTIVE STATUS
const toggleStatus = (plan) => {
    router.post(route('admin.plans.toggle-status', plan.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = `Package '${plan.name}' ${plan.is_active ? 'disabled' : 'activated'}.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 3. 1-CLICK CLONE
const cloningId = ref(null)
const clonePlan = (plan) => {
    cloningId.value = plan.id
    router.post(route('admin.plans.clone', plan.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            cloningId.value = null
            feedbackMsg.value = `Package '${plan.name}' cloned successfully.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        },
        onError: () => {
            cloningId.value = null
        }
    })
}

// 4. DELETE MODAL
const showDeleteModal = ref(false)
const planToDelete = ref(null)
const deleteForm = useForm({})

const openDeleteModal = (plan) => {
    planToDelete.value = plan
    showDeleteModal.value = true
}

const submitDelete = () => {
    if (!planToDelete.value) return
    deleteForm.delete(route('admin.plans.destroy', planToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false
            feedbackMsg.value = `Package '${planToDelete.value.name}' removed.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}
</script>

<template>
    <Head title="Hosting Packages & Plans - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Hosting', href: '#' },
                    { label: 'Packages / Plans' }
                ]"
            >
                <template #actions>
                    <RefreshButton />

                    <button 
                        type="button" 
                        @click="openCreateModal"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <PlusIcon class="w-3.5 h-3.5" />
                        <span>Create Package</span>
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
                    title="Total Packages"
                    :value="String(stats.total_plans || 0)"
                    badge="Tiers"
                    badgeType="info"
                    color="blue"
                    :icon="CubeIcon"
                />

                <InfoCard
                    title="Available on Store"
                    :value="String(stats.active_plans || 0)"
                    badge="Live"
                    badgeType="success"
                    color="emerald"
                    :icon="CheckCircleIcon"
                />

                <InfoCard
                    title="Active Subscribers"
                    :value="String(stats.total_subscribers || 0)"
                    badge="Accounts"
                    badgeType="info"
                    color="sky"
                    :icon="UserGroupIcon"
                />

                <InfoCard
                    title="Benchmark Price"
                    :value="`৳${stats.avg_price || 0}`"
                    badge="/mo avg"
                    badgeType="success"
                    color="purple"
                    :icon="BanknotesIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Standard Filter Toolbar with View Mode & Billing Switcher -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search packages by name, slug, or specs..."
                @search="() => {}"
                @filter="() => {}"
                @reset="resetFilters"
            >
                <FilterSelect
                    v-model="isActive"
                    label="Status"
                    :options="[
                        { label: 'Active Only', value: '1' },
                        { label: 'Disabled Only', value: '0' }
                    ]"
                    placeholder="All Statuses"
                />

                <!-- Billing Period Toggle -->
                <div class="inline-flex rounded-[3px] border border-slate-200 p-0.5 bg-slate-50 text-xs">
                    <button
                        type="button"
                        @click="billingPeriod = 'monthly'"
                        class="px-2.5 py-1 text-xs font-bold rounded-[2px] transition cursor-pointer"
                        :class="billingPeriod === 'monthly' ? 'bg-white text-blue-600 shadow-2xs' : 'text-slate-600 hover:text-slate-900'"
                    >
                        Monthly (৳/mo)
                    </button>
                    <button
                        type="button"
                        @click="billingPeriod = 'yearly'"
                        class="px-2.5 py-1 text-xs font-bold rounded-[2px] transition cursor-pointer"
                        :class="billingPeriod === 'yearly' ? 'bg-white text-blue-600 shadow-2xs' : 'text-slate-600 hover:text-slate-900'"
                    >
                        Yearly (৳/yr)
                    </button>
                </div>

                <!-- View Switcher -->
                <div class="flex items-center gap-1 border-l border-slate-200 pl-2">
                    <button
                        type="button"
                        @click="viewMode = 'table'"
                        class="p-1.5 rounded-[3px] border transition cursor-pointer"
                        :class="viewMode === 'table' ? 'bg-blue-50 text-blue-600 border-blue-200' : 'bg-white text-slate-400 border-slate-200 hover:text-slate-700'"
                        title="Data Table View"
                    >
                        <TableCellsIcon class="w-3.5 h-3.5" />
                    </button>
                    <button
                        type="button"
                        @click="viewMode = 'grid'"
                        class="p-1.5 rounded-[3px] border transition cursor-pointer"
                        :class="viewMode === 'grid' ? 'bg-blue-50 text-blue-600 border-blue-200' : 'bg-white text-slate-400 border-slate-200 hover:text-slate-700'"
                        title="Grid Cards View"
                    >
                        <Squares2X2Icon class="w-3.5 h-3.5" />
                    </button>
                </div>
            </DataTableFilter>

            <!-- 4.1 TABLE VIEW -->
            <div v-if="viewMode === 'table'" class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Package & Slug</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Disk Storage</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Bandwidth</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Domains & DBs</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">CPU & RAM</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Pricing</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Subscribers</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Status</th>
                                <th class="py-2.5 px-3 w-16">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(plan, index) in filteredPlans" :key="plan.id" class="hover:bg-blue-50/30 transition">
                                <!-- # -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>

                                <!-- Package Name & Slug -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs border border-blue-100 shrink-0">
                                            <CubeIcon class="w-3.5 h-3.5" />
                                        </div>
                                        <div>
                                            <span class="font-bold text-slate-900 block leading-tight">{{ plan.name }}</span>
                                            <span class="text-[10px] font-mono text-slate-400 block">{{ plan.slug }}</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Disk Storage -->
                                <td class="py-2.5 px-3 border-r border-slate-100 font-mono whitespace-nowrap">
                                    <span class="font-bold text-slate-900">{{ formatStorage(plan.disk_space) }}</span>
                                </td>

                                <!-- Bandwidth -->
                                <td class="py-2.5 px-3 border-r border-slate-100 font-mono whitespace-nowrap">
                                    <span class="font-bold text-slate-900">{{ formatStorage(plan.bandwidth) }}/mo</span>
                                </td>

                                <!-- Domains & DBs -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-slate-600">
                                    <span class="font-bold text-slate-900">{{ plan.max_domains }} Dom</span> • 
                                    <span>{{ plan.max_databases ?? '∞' }} DBs</span>
                                </td>

                                <!-- CPU & RAM -->
                                <td class="py-2.5 px-3 border-r border-slate-100 font-mono whitespace-nowrap text-slate-600">
                                    <span>{{ plan.cpu_limit ?? 100 }}% CPU</span> • 
                                    <span>{{ plan.ram_limit ?? 1024 }}MB RAM</span>
                                </td>

                                <!-- Price -->
                                <td class="py-2.5 px-3 border-r border-slate-100 font-mono font-bold text-slate-900 whitespace-nowrap">
                                    <span v-if="billingPeriod === 'monthly'">৳{{ plan.price_monthly }}/mo</span>
                                    <span v-else>৳{{ plan.price_yearly }}/yr</span>
                                </td>

                                <!-- Subscribers -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap">
                                    <span class="text-xs font-bold text-blue-700 bg-blue-50 px-2 py-0.5 rounded-[3px] border border-blue-100">
                                        {{ plan.subscriptions_count ?? 0 }} Subs
                                    </span>
                                </td>

                                <!-- Status -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <button
                                        type="button"
                                        @click="toggleStatus(plan)"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold border transition cursor-pointer"
                                        :class="plan.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-600 border-slate-200'"
                                    >
                                        {{ plan.is_active ? '● Active' : '○ Disabled' }}
                                    </button>
                                </td>

                                <!-- Action Dropdown -->
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <RowActionDropdown>
                                        <button
                                            type="button"
                                            @click="openEditModal(plan)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <PencilSquareIcon class="w-3.5 h-3.5 text-slate-500" />
                                            <span>Edit Package</span>
                                        </button>

                                        <button
                                            type="button"
                                            @click="clonePlan(plan)"
                                            :disabled="cloningId === plan.id"
                                            class="w-full text-left px-3 py-1.5 text-xs text-blue-700 hover:bg-blue-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <DocumentDuplicateIcon class="w-3.5 h-3.5" />
                                            <span>1-Click Clone</span>
                                        </button>

                                        <button
                                            type="button"
                                            @click="openDeleteModal(plan)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-rose-600 hover:bg-rose-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <TrashIcon class="w-3.5 h-3.5" />
                                            <span>Delete Package</span>
                                        </button>
                                    </RowActionDropdown>
                                </td>
                            </tr>

                            <tr v-if="filteredPlans.length === 0">
                                <td colspan="10" class="py-12 text-center text-slate-400">
                                    No hosting packages found matching your filters.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 4.2 GRID CARDS VIEW -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3.5">
                <div
                    v-for="plan in filteredPlans"
                    :key="plan.id"
                    class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs hover:border-blue-400 transition flex flex-col justify-between overflow-hidden group"
                >
                    <div>
                        <!-- Card Header -->
                        <div class="p-3.5 border-b border-slate-100 bg-slate-50/40">
                            <div class="flex items-center justify-between gap-2 mb-1.5">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs border border-blue-100">
                                        <CubeIcon class="w-3.5 h-3.5" />
                                    </div>
                                    <div>
                                        <h3 class="text-xs font-bold text-slate-900 group-hover:text-blue-600 transition">{{ plan.name }}</h3>
                                        <span class="text-[10px] font-mono text-slate-400 block">{{ plan.slug }}</span>
                                    </div>
                                </div>
                                <span
                                    :class="plan.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-600 border-slate-200'"
                                    class="px-1.5 py-0.5 rounded-[2px] text-[9.5px] font-bold uppercase border font-mono"
                                >
                                    {{ plan.is_active ? '● Active' : '○ Disabled' }}
                                </span>
                            </div>

                            <!-- Price Display -->
                            <div class="flex items-baseline gap-1 mt-2">
                                <span class="text-xl font-black text-slate-900 font-mono">
                                    ৳{{ billingPeriod === 'monthly' ? plan.price_monthly : plan.price_yearly }}
                                </span>
                                <span class="text-xs text-slate-400 font-medium">
                                    /{{ billingPeriod === 'monthly' ? 'mo' : 'yr' }}
                                </span>
                            </div>
                        </div>

                        <!-- Card Specs -->
                        <div class="p-3.5 space-y-2.5 text-xs">
                            <div class="grid grid-cols-2 gap-2 text-center">
                                <div class="bg-slate-50 p-2 rounded-[3px] border border-slate-200/80">
                                    <span class="text-[9.5px] uppercase font-bold text-slate-400 block">NVMe Storage</span>
                                    <span class="font-bold text-slate-900 font-mono text-xs">{{ formatStorage(plan.disk_space) }}</span>
                                </div>
                                <div class="bg-slate-50 p-2 rounded-[3px] border border-slate-200/80">
                                    <span class="text-[9.5px] uppercase font-bold text-slate-400 block">Bandwidth</span>
                                    <span class="font-bold text-slate-900 font-mono text-xs">{{ formatStorage(plan.bandwidth) }}/mo</span>
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-1.5 text-[10.5px] text-slate-700 text-center">
                                <div class="bg-slate-50/60 p-1.5 rounded-[2px] border border-slate-200/60">
                                    <span class="text-[9px] text-slate-400 block">Domains</span>
                                    <span class="font-bold font-mono text-slate-900">{{ plan.max_domains }}</span>
                                </div>
                                <div class="bg-slate-50/60 p-1.5 rounded-[2px] border border-slate-200/60">
                                    <span class="text-[9px] text-slate-400 block">Databases</span>
                                    <span class="font-bold font-mono text-slate-900">{{ plan.max_databases ?? '∞' }}</span>
                                </div>
                                <div class="bg-slate-50/60 p-1.5 rounded-[2px] border border-slate-200/60">
                                    <span class="text-[9px] text-slate-400 block">Mailboxes</span>
                                    <span class="font-bold font-mono text-slate-900">{{ plan.max_email_accounts ?? '∞' }}</span>
                                </div>
                            </div>

                            <!-- System Features -->
                            <div class="pt-1 flex flex-wrap items-center gap-1 text-[10px] font-bold">
                                <span class="px-1.5 py-0.5 rounded-[2px] bg-purple-50 text-purple-700 border border-purple-200">
                                    PHP {{ plan.php_version_default || '8.3' }}
                                </span>
                                <span class="px-1.5 py-0.5 rounded-[2px] bg-sky-50 text-sky-700 border border-sky-200">
                                    {{ plan.cpu_limit ?? 100 }}% CPU
                                </span>
                                <span class="px-1.5 py-0.5 rounded-[2px] bg-indigo-50 text-indigo-700 border border-indigo-200">
                                    {{ plan.ram_limit ?? 1024 }}MB RAM
                                </span>
                                <span v-if="plan.auto_ssl" class="px-1.5 py-0.5 rounded-[2px] bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    AutoSSL
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Card Footer Actions -->
                    <div class="p-2.5 bg-slate-50/80 border-t border-slate-100 flex items-center justify-between">
                        <button
                            type="button"
                            @click="clonePlan(plan)"
                            class="px-2 py-1 bg-white hover:bg-slate-100 text-slate-700 rounded-[3px] border border-slate-200 text-xs font-bold transition flex items-center gap-1 cursor-pointer"
                        >
                            <DocumentDuplicateIcon class="w-3.5 h-3.5 text-slate-500" />
                            <span>Clone</span>
                        </button>

                        <div class="flex items-center gap-1.5">
                            <button
                                type="button"
                                @click="openEditModal(plan)"
                                class="px-2.5 py-1 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-[3px] text-xs font-bold transition flex items-center gap-1 border border-blue-200 cursor-pointer"
                            >
                                <PencilSquareIcon class="w-3.5 h-3.5" />
                                <span>Edit</span>
                            </button>

                            <button
                                type="button"
                                @click="openDeleteModal(plan)"
                                class="p-1 bg-white hover:bg-rose-50 text-slate-400 hover:text-rose-600 rounded-[3px] border border-slate-200 transition cursor-pointer"
                            >
                                <TrashIcon class="w-3.5 h-3.5" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 5. CREATE / EDIT MODAL -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-2xl overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <CubeIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            {{ isEditing ? `Edit Hosting Package: ${planForm.name}` : 'Create Hosting Package' }}
                        </h3>
                    </div>
                    <button @click="showModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <!-- Tabs -->
                <div class="flex border-b border-slate-200 px-4 bg-slate-50/30 text-xs">
                    <button
                        type="button"
                        @click="activeTab = 'general'"
                        class="py-2.5 px-3 border-b-2 font-bold transition cursor-pointer"
                        :class="activeTab === 'general' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-900'"
                    >
                        General & Quotas
                    </button>
                    <button
                        type="button"
                        @click="activeTab = 'system'"
                        class="py-2.5 px-3 border-b-2 font-bold transition cursor-pointer"
                        :class="activeTab === 'system' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-900'"
                    >
                        Hardware & Runtime
                    </button>
                    <button
                        type="button"
                        @click="activeTab = 'services'"
                        class="py-2.5 px-3 border-b-2 font-bold transition cursor-pointer flex items-center gap-1.5"
                        :class="activeTab === 'services' ? 'border-[#673DE6] text-[#673DE6]' : 'border-transparent text-slate-500 hover:text-slate-900'"
                    >
                        <span>⚡ Services & Stack</span>
                        <span v-if="planForm.allow_redis || planForm.allow_nodejs" class="w-1.5 h-1.5 rounded-full bg-[#673DE6]"></span>
                    </button>
                    <button
                        type="button"
                        @click="activeTab = 'pricing'"
                        class="py-2.5 px-3 border-b-2 font-bold transition cursor-pointer"
                        :class="activeTab === 'pricing' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-900'"
                    >
                        Pricing & Visibility
                    </button>
                </div>

                <form @submit.prevent="submitPlanForm" class="p-4 space-y-3.5 text-xs max-h-[75vh] overflow-y-auto">
                    <!-- Tab 1: General & Quotas -->
                    <div v-if="activeTab === 'general'" class="space-y-3">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700">Package Name <span class="text-rose-500">*</span></label>
                                <input
                                    v-model="planForm.name"
                                    type="text"
                                    required
                                    placeholder="e.g. BDIX Starter NVMe"
                                    class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition-all shadow-2xs"
                                />
                                <span v-if="planForm.errors.name" class="text-rose-600 text-[10.5px] font-semibold block">{{ planForm.errors.name }}</span>
                            </div>
                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700">Slug Identifier <span class="text-rose-500">*</span></label>
                                <input
                                    v-model="planForm.slug"
                                    type="text"
                                    required
                                    placeholder="bdix-starter-nvme"
                                    class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition-all shadow-2xs"
                                />
                                <span v-if="planForm.errors.slug" class="text-rose-600 text-[10.5px] font-semibold block">{{ planForm.errors.slug }}</span>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Package Description</label>
                            <input
                                v-model="planForm.description"
                                type="text"
                                placeholder="e.g. High speed NVMe storage with 10Gbps BDIX connectivity"
                                class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition-all shadow-2xs"
                            />
                        </div>

                        <!-- Storage & Bandwidth -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 pt-1 border-t border-slate-100">
                            <div class="space-y-1">
                                <div class="flex items-center justify-between">
                                    <label class="font-bold text-slate-700">NVMe Storage (MB) <span class="text-rose-500">*</span></label>
                                    <span class="text-[10.5px] font-mono font-bold text-blue-600">
                                        = {{ formatStorage(planForm.disk_space) }}
                                    </span>
                                </div>
                                <input
                                    v-model.number="planForm.disk_space"
                                    type="number"
                                    required
                                    min="50"
                                    class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition-all shadow-2xs"
                                />
                            </div>

                            <div class="space-y-1">
                                <div class="flex items-center justify-between">
                                    <label class="font-bold text-slate-700">Monthly Bandwidth (MB) <span class="text-rose-500">*</span></label>
                                    <span class="text-[10.5px] font-mono font-bold text-blue-600">
                                        = {{ formatStorage(planForm.bandwidth) }}
                                    </span>
                                </div>
                                <input
                                    v-model.number="planForm.bandwidth"
                                    type="number"
                                    required
                                    min="100"
                                    class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition-all shadow-2xs"
                                />
                            </div>
                        </div>

                        <!-- Account & Domain Quotas -->
                        <div class="grid grid-cols-2 sm:grid-cols-5 gap-2.5 pt-1 border-t border-slate-100">
                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700">Hosted Domains</label>
                                <input
                                    v-model.number="planForm.max_domains"
                                    type="number"
                                    required
                                    min="1"
                                    class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition-all shadow-2xs"
                                />
                            </div>
                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700">Subdomains</label>
                                <input
                                    v-model.number="planForm.max_subdomains"
                                    type="number"
                                    min="0"
                                    class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition-all shadow-2xs"
                                />
                            </div>
                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700">Databases</label>
                                <input
                                    v-model.number="planForm.max_databases"
                                    type="number"
                                    min="0"
                                    class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition-all shadow-2xs"
                                />
                            </div>
                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700">Email Accounts</label>
                                <input
                                    v-model.number="planForm.max_email_accounts"
                                    type="number"
                                    min="0"
                                    class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition-all shadow-2xs"
                                />
                            </div>
                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700">FTP Accounts</label>
                                <input
                                    v-model.number="planForm.max_ftp_accounts"
                                    type="number"
                                    min="0"
                                    class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition-all shadow-2xs"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Tab 2: Hardware & Runtime -->
                    <div v-if="activeTab === 'system'" class="space-y-3">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700">CPU Core Limit (%)</label>
                                <input
                                    v-model.number="planForm.cpu_limit"
                                    type="number"
                                    min="10"
                                    max="400"
                                    class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition-all shadow-2xs"
                                />
                                <span class="text-[10px] text-slate-400">100% = 1 Full vCPU Core</span>
                            </div>
                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700">RAM Limit (MB)</label>
                                <input
                                    v-model.number="planForm.ram_limit"
                                    type="number"
                                    min="128"
                                    max="32768"
                                    class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition-all shadow-2xs"
                                />
                                <span class="text-[10px] text-slate-400 font-mono">{{ formatStorage(planForm.ram_limit) }} Dedicated Pool</span>
                            </div>
                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700">Default PHP Engine</label>
                                <select
                                    v-model="planForm.php_version_default"
                                    class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition-all shadow-2xs cursor-pointer font-bold"
                                >
                                    <option value="8.1">PHP 8.1 FPM</option>
                                    <option value="8.2">PHP 8.2 FPM (Default)</option>
                                    <option value="8.3">PHP 8.3 FPM</option>
                                    <option value="8.4">PHP 8.4 FPM</option>
                                    <option value="8.5">PHP 8.5 FPM</option>
                                </select>
                            </div>
                        </div>

                        <!-- Feature Toggles -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 pt-2 border-t border-slate-100">
                            <label class="p-2.5 bg-slate-50 rounded border border-slate-200/80 flex items-center gap-2 cursor-pointer font-bold text-slate-700">
                                <input v-model="planForm.auto_ssl" type="checkbox" class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300" />
                                <div>
                                    <span class="block">AutoSSL Protection</span>
                                    <span class="text-[10px] text-slate-400 font-normal">Automated Let's Encrypt certificate issuance</span>
                                </div>
                            </label>

                            <label class="p-2.5 bg-slate-50 rounded border border-slate-200/80 flex items-center gap-2 cursor-pointer font-bold text-slate-700">
                                <input v-model="planForm.allow_ssh_access" type="checkbox" class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300" />
                                <div>
                                    <span class="block">SSH Shell Access</span>
                                    <span class="text-[10px] text-slate-400 font-normal">Permit terminal SSH shell logins for vHost user</span>
                                </div>
                            </label>

                            <label class="p-2.5 bg-slate-50 rounded border border-slate-200/80 flex items-center gap-2 cursor-pointer font-bold text-slate-700">
                                <input v-model="planForm.allow_custom_php_ini" type="checkbox" class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300" />
                                <div>
                                    <span class="block">Custom php.ini Directives</span>
                                    <span class="text-[10px] text-slate-400 font-normal">Allow memory_limit and upload tweaks</span>
                                </div>
                            </label>

                            <label class="p-2.5 bg-slate-50 rounded border border-slate-200/80 flex items-center gap-2 cursor-pointer font-bold text-slate-700">
                                <input v-model="planForm.allow_git_deploy" type="checkbox" class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300" />
                                <div>
                                    <span class="block">Git Automated Deployment</span>
                                    <span class="text-[10px] text-slate-400 font-normal">Enable webhook and Git auto-deploy integration</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Tab 3: Services & Stack Entitlements (Redis, Node.js, Python, Cron, etc.) -->
                    <div v-if="activeTab === 'services'" class="space-y-3">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            <!-- Redis In-Memory Cache -->
                            <div class="p-3 rounded border transition-all" :class="planForm.allow_redis ? 'border-[#673DE6]/50 bg-[#EDE8FC]/20' : 'border-slate-200 bg-slate-50/50'">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <div class="flex items-center gap-1.5">
                                            <span class="font-bold text-slate-900">⚡ Redis In-Memory Cache</span>
                                            <span v-if="planForm.allow_redis" class="px-1.5 py-0.2 rounded text-[10px] font-black uppercase bg-[#EDE8FC] text-[#673DE6]">Active</span>
                                        </div>
                                        <p class="text-[11px] text-slate-500 mt-0.5">High-speed object cache & sessions on port 6379.</p>
                                    </div>
                                    <input v-model="planForm.allow_redis" type="checkbox" class="w-4 h-4 rounded text-[#673DE6] focus:ring-[#673DE6] border-slate-300 cursor-pointer mt-0.5" />
                                </div>
                                <div v-if="planForm.allow_redis" class="mt-2 pt-2 border-t border-slate-200/60 flex items-center justify-between">
                                    <label class="text-[11px] font-bold text-slate-700">Redis Memory Limit:</label>
                                    <div class="flex items-center gap-1">
                                        <input v-model.number="planForm.redis_memory_mb" type="number" min="16" max="4096" class="w-16 px-2 py-0.5 text-xs rounded border border-slate-200 bg-white font-mono text-right" />
                                        <span class="text-[11px] font-bold text-slate-500">MB</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Memcached Service -->
                            <div class="p-3 rounded border transition-all" :class="planForm.allow_memcached ? 'border-[#673DE6]/50 bg-[#EDE8FC]/20' : 'border-slate-200 bg-slate-50/50'">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <div class="flex items-center gap-1.5">
                                            <span class="font-bold text-slate-900">⚡ Memcached Service</span>
                                            <span v-if="planForm.allow_memcached" class="px-1.5 py-0.2 rounded text-[10px] font-black uppercase bg-[#EDE8FC] text-[#673DE6]">Active</span>
                                        </div>
                                        <p class="text-[11px] text-slate-500 mt-0.5">Distributed key-value memory cache on port 11211.</p>
                                    </div>
                                    <input v-model="planForm.allow_memcached" type="checkbox" class="w-4 h-4 rounded text-[#673DE6] focus:ring-[#673DE6] border-slate-300 cursor-pointer mt-0.5" />
                                </div>
                            </div>

                            <!-- Node.js Apps -->
                            <div class="p-3 rounded border transition-all" :class="planForm.allow_nodejs ? 'border-emerald-500/50 bg-emerald-50/20' : 'border-slate-200 bg-slate-50/50'">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <div class="flex items-center gap-1.5">
                                            <span class="font-bold text-slate-900">🟢 Node.js Applications</span>
                                            <span v-if="planForm.allow_nodejs" class="px-1.5 py-0.2 rounded text-[10px] font-black uppercase bg-emerald-100 text-emerald-700">Active</span>
                                        </div>
                                        <p class="text-[11px] text-slate-500 mt-0.5">PM2 daemon & reverse proxy for Express/Next.js/Nuxt.</p>
                                    </div>
                                    <input v-model="planForm.allow_nodejs" type="checkbox" class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500 border-slate-300 cursor-pointer mt-0.5" />
                                </div>
                            </div>

                            <!-- Python Apps -->
                            <div class="p-3 rounded border transition-all" :class="planForm.allow_python ? 'border-amber-500/50 bg-amber-50/20' : 'border-slate-200 bg-slate-50/50'">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <div class="flex items-center gap-1.5">
                                            <span class="font-bold text-slate-900">🐍 Python Runtime & WSGI</span>
                                            <span v-if="planForm.allow_python" class="px-1.5 py-0.2 rounded text-[10px] font-black uppercase bg-amber-100 text-amber-700">Active</span>
                                        </div>
                                        <p class="text-[11px] text-slate-500 mt-0.5">Django/Flask apps with dedicated virtual environments.</p>
                                    </div>
                                    <input v-model="planForm.allow_python" type="checkbox" class="w-4 h-4 rounded text-amber-600 focus:ring-amber-500 border-slate-300 cursor-pointer mt-0.5" />
                                </div>
                            </div>

                            <!-- Cron Jobs Automation -->
                            <div class="p-3 rounded border transition-all" :class="planForm.allow_cron_jobs ? 'border-blue-500/50 bg-blue-50/20' : 'border-slate-200 bg-slate-50/50'">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <div class="flex items-center gap-1.5">
                                            <span class="font-bold text-slate-900">⏰ Cron Jobs Automation</span>
                                            <span v-if="planForm.allow_cron_jobs" class="px-1.5 py-0.2 rounded text-[10px] font-black uppercase bg-blue-100 text-blue-700">Active</span>
                                        </div>
                                        <p class="text-[11px] text-slate-500 mt-0.5">Automate recurring scripts & background tasks.</p>
                                    </div>
                                    <input v-model="planForm.allow_cron_jobs" type="checkbox" class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300 cursor-pointer mt-0.5" />
                                </div>
                            </div>

                            <!-- Self-Service Backups -->
                            <div class="p-3 rounded border transition-all" :class="planForm.allow_backups ? 'border-blue-500/50 bg-blue-50/20' : 'border-slate-200 bg-slate-50/50'">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <div class="flex items-center gap-1.5">
                                            <span class="font-bold text-slate-900">💾 Backups & Restore</span>
                                            <span v-if="planForm.allow_backups" class="px-1.5 py-0.2 rounded text-[10px] font-black uppercase bg-blue-100 text-blue-700">Active</span>
                                        </div>
                                        <p class="text-[11px] text-slate-500 mt-0.5">Instant ZIP backups and file/database restoration.</p>
                                    </div>
                                    <input v-model="planForm.allow_backups" type="checkbox" class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300 cursor-pointer mt-0.5" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab 3: Pricing & Visibility -->
                    <div v-if="activeTab === 'pricing'" class="space-y-3">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700">Monthly Price (৳) <span class="text-rose-500">*</span></label>
                                <input
                                    v-model.number="planForm.price_monthly"
                                    type="number"
                                    step="0.01"
                                    required
                                    min="0"
                                    class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition-all shadow-2xs font-bold"
                                />
                            </div>
                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700">Yearly Price (৳) <span class="text-rose-500">*</span></label>
                                <input
                                    v-model.number="planForm.price_yearly"
                                    type="number"
                                    step="0.01"
                                    required
                                    min="0"
                                    class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition-all shadow-2xs font-bold"
                                />
                            </div>
                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700">Setup Fee (৳)</label>
                                <input
                                    v-model.number="planForm.setup_fee"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition-all shadow-2xs font-bold"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 pt-2 border-t border-slate-100">
                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700">Store Sort Order</label>
                                <input
                                    v-model.number="planForm.sort_order"
                                    type="number"
                                    min="0"
                                    placeholder="0"
                                    class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition-all shadow-2xs"
                                />
                                <span class="text-[10px] text-slate-400">Lower numbers appear first</span>
                            </div>

                            <div class="flex items-center pt-4">
                                <label class="p-2.5 bg-slate-50 rounded border border-slate-200/80 flex items-center gap-2 cursor-pointer font-bold text-slate-700 w-full">
                                    <input v-model="planForm.is_active" type="checkbox" class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300" />
                                    <div>
                                        <span class="block">Publish on Store</span>
                                        <span class="text-[10px] text-slate-400 font-normal">Active for client portal orders & upgrades</span>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="pt-2 flex items-center justify-end gap-2 border-t border-slate-100">
                        <button
                            type="button"
                            @click="showModal = false"
                            class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="planForm.processing"
                            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ planForm.processing ? 'Saving...' : (isEditing ? 'Update Package' : 'Create Package') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 6. DELETE CONFIRMATION MODAL -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-rose-50 text-rose-600 flex items-center justify-center font-bold border border-rose-100">
                            <TrashIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Confirm Package Deletion
                        </h3>
                    </div>
                    <button @click="showDeleteModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-2.5 text-xs">
                    <p class="text-slate-600">
                        Are you sure you want to delete hosting package <strong class="text-slate-900">{{ planToDelete?.name }}</strong>?
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
                        {{ deleteForm.processing ? 'Deleting...' : 'Delete Package' }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
