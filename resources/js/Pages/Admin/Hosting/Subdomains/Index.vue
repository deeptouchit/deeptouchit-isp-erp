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
    ShieldCheckIcon,
    ShieldExclamationIcon,
    CheckCircleIcon,
    PlusIcon,
    ArrowPathIcon,
    TrashIcon,
    PauseIcon,
    PlayIcon,
    LockClosedIcon,
    XMarkIcon,
    ArrowTopRightOnSquareIcon,
    ClipboardDocumentIcon,
    CpuChipIcon,
    FolderIcon,
    ServerIcon,
    CheckIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    subdomains: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    stats: {
        type: Object,
        default: () => ({
            total_subdomains: 0,
            active_ssl_count: 0,
            active_subdomains: 0,
            parent_domains_count: 0,
        }),
    },
    subscriptions: {
        type: Array,
        default: () => [],
    },
    phpVersions: {
        type: Array,
        default: () => ['8.1', '8.2', '8.3', '8.4', '8.5'],
    },
    filters: {
        type: Object,
        default: () => ({ search: '', subscription_id: '', ssl_status: '', status: '' }),
    },
})

// Flash Feedback
const feedbackMsg = ref('')

// Filter State
const search = ref(props.filters?.search || '')
const subscriptionId = ref(props.filters?.subscription_id || '')
const sslStatus = ref(props.filters?.ssl_status || '')
const status = ref(props.filters?.status || '')

const applyFilters = () => {
    router.get(route('admin.hosting.subdomains'), {
        search: search.value || undefined,
        subscription_id: subscriptionId.value || undefined,
        ssl_status: sslStatus.value || undefined,
        status: status.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const resetFilters = () => {
    search.value = ''
    subscriptionId.value = ''
    sslStatus.value = ''
    status.value = ''
    applyFilters()
}

// 1. CREATE SUBDOMAIN MODAL
const showCreateModal = ref(false)
const createForm = useForm({
    subscription_id: props.subscriptions?.[0]?.id || '',
    subdomain_prefix: '',
    document_root: '',
    php_version: '8.3',
    auto_ssl: true,
})

const openCreateModal = (presetSubId = null) => {
    createForm.reset()
    createForm.subscription_id = presetSubId || props.subscriptions?.[0]?.id || ''
    showCreateModal.value = true
}

const selectedParentDomain = computed(() => {
    const sub = props.subscriptions.find(s => String(s.id) === String(createForm.subscription_id))
    return sub ? sub.domain : (props.subscriptions?.[0]?.domain || 'domain.com')
})

const fullComputedFqdn = computed(() => {
    const prefix = createForm.subdomain_prefix ? createForm.subdomain_prefix.trim().toLowerCase() : 'sub'
    return `${prefix}.${selectedParentDomain.value}`
})

const submitCreateSubdomain = () => {
    createForm.post(route('admin.hosting.subdomains.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false
            createForm.reset()
            feedbackMsg.value = `Subdomain endpoint '${fullComputedFqdn.value}' deployed.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 2. CHANGE PHP MODAL
const showPhpModal = ref(false)
const selectedSiteForPhp = ref(null)
const phpForm = useForm({
    php_version: '8.3',
})

const openPhpModal = (site) => {
    selectedSiteForPhp.value = site
    phpForm.php_version = site.php_version || '8.3'
    showPhpModal.value = true
}

const submitChangePhp = () => {
    if (!selectedSiteForPhp.value) return
    phpForm.post(route('admin.websites.change-php', selectedSiteForPhp.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showPhpModal.value = false
            feedbackMsg.value = `PHP engine for '${selectedSiteForPhp.value.domain}' switched to PHP ${phpForm.php_version}.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 3. TOGGLE SUSPENSION
const toggleStatus = (sub) => {
    const isSuspended = sub.status === 'suspended'
    const action = isSuspended ? 'unsuspend' : 'suspend'
    const msg = isSuspended 
        ? `Reactivate subdomain '${sub.domain}'?`
        : `Suspend subdomain '${sub.domain}'? Traffic will be stopped.`
    
    if (confirm(msg)) {
        router.post(route(`admin.websites.${action}`, sub.id), {}, {
            preserveScroll: true,
            onSuccess: () => {
                feedbackMsg.value = `Subdomain '${sub.domain}' ${action}ed.`
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    }
}

// 4. ISSUE SSL CERTIFICATE
const issuingSslId = ref(null)
const issueSsl = (sub) => {
    issuingSslId.value = sub.id
    router.post(route('admin.websites.issue-ssl', sub.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            issuingSslId.value = null
            feedbackMsg.value = `Let's Encrypt SSL certificate provisioned for '${sub.domain}'.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        },
        onError: () => {
            issuingSslId.value = null
        }
    })
}

// 5. DELETE MODAL
const showDeleteModal = ref(false)
const subToDelete = ref(null)
const deleteForm = useForm({})

const openDeleteModal = (sub) => {
    subToDelete.value = sub
    showDeleteModal.value = true
}

const submitDeleteSubdomain = () => {
    if (!subToDelete.value) return
    deleteForm.delete(route('admin.hosting.subdomains.destroy', subToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false
            feedbackMsg.value = `Subdomain '${subToDelete.value.domain}' deleted.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// Copy to Clipboard
const copiedPath = ref('')
const copyToClipboard = (text) => {
    if (!text) return
    navigator.clipboard.writeText(text)
    copiedPath.value = text
    setTimeout(() => { copiedPath.value = '' }, 2000)
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
    <Head title="Subdomains & Endpoints - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Hosting', href: '#' },
                    { label: 'Subdomains' }
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
                        @click="openCreateModal()"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <PlusIcon class="w-3.5 h-3.5" />
                        <span>Create Subdomain</span>
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
                    title="Total Subdomains"
                    :value="String(stats.total_subdomains || 0)"
                    badge="Endpoints"
                    badgeType="info"
                    color="blue"
                    :icon="GlobeAltIcon"
                />

                <InfoCard
                    title="SSL Encrypted"
                    :value="String(stats.active_ssl_count || 0)"
                    badge="Secured"
                    badgeType="success"
                    color="emerald"
                    :icon="ShieldCheckIcon"
                />

                <InfoCard
                    title="Active Status"
                    :value="String(stats.active_subdomains || 0)"
                    badge="Serving"
                    badgeType="info"
                    color="sky"
                    :icon="CheckCircleIcon"
                />

                <InfoCard
                    title="Parent Roots"
                    :value="String(stats.parent_domains_count || 0)"
                    badge="Domains"
                    badgeType="success"
                    color="purple"
                    :icon="ServerIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search by subdomain, parent domain, or user..."
                @search="applyFilters"
                @filter="applyFilters"
                @reset="resetFilters"
            >
                <FilterSelect
                    v-if="subscriptions && subscriptions.length > 0"
                    v-model="subscriptionId"
                    label="Parent Domain"
                    :options="subscriptions.map(s => ({ label: `${s.domain} (@${s.username})`, value: s.id }))"
                    placeholder="All Parent Domains"
                    @change="applyFilters"
                />

                <FilterSelect
                    v-model="sslStatus"
                    label="SSL State"
                    :options="[
                        { label: 'Active SSL Only', value: 'active' },
                        { label: 'No SSL / Pending', value: 'none' }
                    ]"
                    placeholder="All SSL States"
                    @change="applyFilters"
                />

                <FilterSelect
                    v-model="status"
                    label="Status"
                    :options="[
                        { label: 'Active Only', value: 'active' },
                        { label: 'Suspended Only', value: 'suspended' }
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
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Subdomain Endpoint</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Parent Domain & Account</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Document Root</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">PHP Engine</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">SSL Security</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Status</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Created</th>
                                <th class="py-2.5 px-3 w-16">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(sub, index) in subdomains.data" :key="sub.id" class="hover:bg-blue-50/30 transition">
                                <!-- # -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ ((subdomains.current_page || 1) - 1) * (subdomains.per_page || 15) + (index + 1) }}
                                </td>

                                <!-- Subdomain & Link -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs border border-blue-100 shrink-0">
                                            <GlobeAltIcon class="w-3.5 h-3.5" />
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-1.5">
                                                <a 
                                                    :href="`http://${sub.domain}`" 
                                                    target="_blank" 
                                                    class="font-bold text-slate-900 hover:text-blue-600 flex items-center gap-1"
                                                >
                                                    <span>{{ sub.domain }}</span>
                                                    <ArrowTopRightOnSquareIcon class="w-3 h-3 text-slate-400" />
                                                </a>
                                                <span v-if="sub.subdomain" class="px-1.5 py-0.2 rounded-[2px] text-[9px] font-bold uppercase bg-sky-50 text-sky-700 border border-sky-200">
                                                    {{ sub.subdomain }}
                                                </span>
                                            </div>
                                            <span class="text-[10px] font-mono text-slate-400 block">VirtualHost Endpoint</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Parent Domain & Account -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap">
                                    <div v-if="sub.subscription" class="space-y-0.5">
                                        <span class="font-bold text-slate-900 block leading-tight">{{ sub.subscription.domain }}</span>
                                        <span class="text-[10px] font-mono text-slate-400 block">
                                            @{{ sub.subscription.username }}
                                        </span>
                                    </div>
                                    <span v-else class="text-slate-400 italic">Standalone</span>
                                </td>

                                <!-- Document Root -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap">
                                    <div class="flex items-center gap-1.5 group">
                                        <FolderIcon class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                                        <span class="font-mono text-[10.5px] text-slate-600 max-w-xs truncate" :title="sub.document_root">
                                            {{ sub.document_root }}
                                        </span>
                                        <button 
                                            type="button" 
                                            @click="copyToClipboard(sub.document_root)"
                                            class="text-slate-400 hover:text-slate-700 transition cursor-pointer p-0.5"
                                            title="Copy Path"
                                        >
                                            <CheckIcon v-if="copiedPath === sub.document_root" class="w-3.5 h-3.5 text-emerald-600" />
                                            <ClipboardDocumentIcon v-else class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </td>

                                <!-- PHP Engine -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <button
                                        type="button"
                                        @click="openPhpModal(sub)"
                                        class="px-2 py-0.5 rounded-[3px] text-[10.5px] font-bold font-mono bg-purple-50 text-purple-700 border border-purple-200 hover:bg-purple-100 transition cursor-pointer"
                                        title="Click to switch PHP version"
                                    >
                                        PHP {{ sub.php_version || '8.3' }} ✎
                                    </button>
                                </td>

                                <!-- SSL Security -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        v-if="sub.ssl_status === 'active'"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-[3px] text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200"
                                    >
                                        <ShieldCheckIcon class="w-3.5 h-3.5 text-emerald-600" />
                                        <span>Auto-SSL</span>
                                    </span>
                                    <button
                                        v-else
                                        type="button"
                                        @click="issueSsl(sub)"
                                        :disabled="issuingSslId === sub.id"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-[3px] text-[10px] font-bold bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 transition cursor-pointer disabled:opacity-50"
                                        title="Click to issue SSL"
                                    >
                                        <LockClosedIcon class="w-3 h-3 text-amber-600" />
                                        <span>{{ issuingSslId === sub.id ? 'Issuing...' : 'Issue SSL' }}</span>
                                    </button>
                                </td>

                                <!-- Status -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="sub.status === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200'"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-[3px] text-[10px] font-bold capitalize border"
                                    >
                                        <span 
                                            class="w-1.5 h-1.5 rounded-full"
                                            :class="sub.status === 'active' ? 'bg-emerald-500' : 'bg-amber-500'"
                                        ></span>
                                        {{ sub.status }}
                                    </span>
                                </td>

                                <!-- Created -->
                                <td class="py-2.5 px-3 border-r border-slate-100 font-mono text-slate-400 text-[10.5px] whitespace-nowrap">
                                    {{ formatDate(sub.created_at) }}
                                </td>

                                <!-- Action Dropdown -->
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <RowActionDropdown>
                                        <!-- Issue SSL -->
                                        <button 
                                            type="button" 
                                            @click="issueSsl(sub)"
                                            :disabled="issuingSslId === sub.id"
                                            class="w-full text-left px-3 py-1.5 text-xs text-emerald-700 hover:bg-emerald-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <ShieldCheckIcon class="w-3.5 h-3.5" />
                                            <span>Issue / Renew SSL</span>
                                        </button>

                                        <!-- Switch PHP -->
                                        <button 
                                            type="button" 
                                            @click="openPhpModal(sub)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <CpuChipIcon class="w-3.5 h-3.5 text-slate-500" />
                                            <span>Switch PHP Engine</span>
                                        </button>

                                        <!-- Suspend / Unsuspend -->
                                        <button 
                                            type="button" 
                                            @click="toggleStatus(sub)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-amber-700 hover:bg-amber-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <PauseIcon v-if="sub.status === 'active'" class="w-3.5 h-3.5" />
                                            <PlayIcon v-else class="w-3.5 h-3.5" />
                                            <span>{{ sub.status === 'active' ? 'Suspend Subdomain' : 'Unsuspend' }}</span>
                                        </button>

                                        <!-- Delete -->
                                        <button 
                                            type="button" 
                                            @click="openDeleteModal(sub)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-rose-600 hover:bg-rose-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <TrashIcon class="w-3.5 h-3.5" />
                                            <span>Delete Subdomain</span>
                                        </button>
                                    </RowActionDropdown>
                                </td>
                            </tr>

                            <tr v-if="!subdomains.data || subdomains.data.length === 0">
                                <td colspan="9" class="py-12 text-center text-slate-400">
                                    No subdomains configured yet.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <Pagination :links="subdomains.links" :from="subdomains.from" :to="subdomains.to" :total="subdomains.total" />
            </div>
        </div>

        <!-- 1. CREATE SUBDOMAIN MODAL -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-lg overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <GlobeAltIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Create Subdomain VirtualHost
                        </h3>
                    </div>
                    <button @click="showCreateModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitCreateSubdomain" class="p-4 space-y-3.5 text-xs">
                    <!-- Parent Subscription -->
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">
                            Parent Domain Subscription <span class="text-rose-500">*</span>
                        </label>
                        <select
                            v-model="createForm.subscription_id"
                            required
                            class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition-all shadow-2xs cursor-pointer"
                        >
                            <option v-for="sub in subscriptions" :key="sub.id" :value="sub.id">
                                {{ sub.domain }} (@{{ sub.username }} - {{ sub.user?.first_name }} {{ sub.user?.last_name }})
                            </option>
                        </select>
                        <span v-if="createForm.errors.subscription_id" class="text-rose-600 text-[10.5px] font-semibold block">{{ createForm.errors.subscription_id }}</span>
                    </div>

                    <!-- Subdomain Prefix -->
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">
                            Subdomain Prefix <span class="text-rose-500">*</span>
                        </label>
                        <div class="flex items-center">
                            <input
                                v-model="createForm.subdomain_prefix"
                                type="text"
                                required
                                placeholder="e.g. app, api, blog, dev"
                                class="w-full px-3 py-1.5 text-xs rounded-l-[3px] border border-r-0 border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition-all shadow-2xs"
                            />
                            <span class="px-3 py-1.5 bg-slate-100 border border-slate-200 rounded-r-[3px] font-mono text-slate-600 font-bold text-xs whitespace-nowrap">
                                .{{ selectedParentDomain }}
                            </span>
                        </div>
                        <span v-if="createForm.errors.subdomain_prefix" class="text-rose-600 text-[10.5px] font-semibold block">{{ createForm.errors.subdomain_prefix }}</span>
                    </div>

                    <div class="p-2.5 bg-blue-50/60 border border-blue-100 rounded-[3px] text-[11px] text-blue-900 flex items-center justify-between font-mono">
                        <span>FQDN: <strong>{{ fullComputedFqdn }}</strong></span>
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

                        <!-- Custom Doc Root -->
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Custom Document Root</label>
                            <input
                                v-model="createForm.document_root"
                                type="text"
                                placeholder="Default public_html/..."
                                class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 font-mono transition-all shadow-2xs"
                            />
                        </div>
                    </div>

                    <div class="pt-1">
                        <label class="inline-flex items-center gap-2 cursor-pointer font-bold text-slate-700">
                            <input v-model="createForm.auto_ssl" type="checkbox" class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300" />
                            <span>Auto-Issue Let's Encrypt SSL Certificate</span>
                        </label>
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
                            {{ createForm.processing ? 'Deploying...' : 'Deploy Subdomain' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 2. CHANGE PHP MODAL -->
        <div v-if="showPhpModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-purple-50 text-purple-600 flex items-center justify-center font-bold border border-purple-100">
                            <CpuChipIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Switch PHP: {{ selectedSiteForPhp?.domain }}
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

        <!-- 3. DELETE MODAL -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-rose-50 text-rose-600 flex items-center justify-center font-bold border border-rose-100">
                            <TrashIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Delete Subdomain VirtualHost
                        </h3>
                    </div>
                    <button @click="showDeleteModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-2.5 text-xs">
                    <p class="text-slate-600">
                        Are you sure you want to delete subdomain endpoint <strong class="text-slate-900">{{ subToDelete?.domain }}</strong>?
                    </p>
                    <div class="p-2.5 bg-rose-50 border border-rose-200 text-rose-800 rounded-[3px]">
                        <p class="font-bold text-[11px]">⚠️ VirtualHost Removal</p>
                        <p class="text-[10.5px] mt-0.5">This will disable the Nginx subdomain configuration and delete the virtual host endpoint.</p>
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
                        @click="submitDeleteSubdomain"
                        :disabled="deleteForm.processing"
                        class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                    >
                        {{ deleteForm.processing ? 'Deleting...' : 'Delete Subdomain' }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
