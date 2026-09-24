<script setup>
import { ref, computed } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import RefreshButton from '@/Components/UI/RefreshButton.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'
import DataTableFilter from '@/Components/UI/DataTableFilter.vue'
import FilterSelect from '@/Components/UI/FilterSelect.vue'
import RowActionDropdown from '@/Components/UI/RowActionDropdown.vue'

import {
    EnvelopeIcon,
    TrashIcon,
    PlusIcon,
    ServerIcon,
    KeyIcon,
    BoltIcon,
    XMarkIcon,
    ShieldCheckIcon,
    CheckIcon,
    DocumentDuplicateIcon,
    GlobeAltIcon,
    ArrowTopRightOnSquareIcon,
    CheckCircleIcon,
    ArrowsRightLeftIcon,
    ExclamationTriangleIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    domains: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            total_domains: 0,
            total_accounts: 0,
            dkim_verified_percent: 100,
            total_quota_gb: 0,
        }),
    },
    subscriptions: {
        type: Array,
        default: () => [],
    },
})

// Toast Feedback
const feedbackMsg = ref('')

// Search & Filter
const search = ref('')
const selectedStatusFilter = ref('all')

const filteredDomains = computed(() => {
    let list = props.domains || []

    if (search.value.trim()) {
        const q = search.value.toLowerCase()
        list = list.filter(d =>
            d.domain.toLowerCase().includes(q) ||
            (d.subscription?.domain || '').toLowerCase().includes(q) ||
            (d.subscription?.user?.name || '').toLowerCase().includes(q)
        )
    }

    if (selectedStatusFilter.value !== 'all') {
        list = list.filter(d => (d.status || 'active') === selectedStatusFilter.value)
    }

    return list
})

const resetFilters = () => {
    search.value = ''
    selectedStatusFilter.value = 'all'
}

// 1. ADD MAIL DOMAIN MODAL
const showCreateModal = ref(false)
const createForm = useForm({
    subscription_id: props.subscriptions[0]?.id || '',
    domain: '',
    max_accounts: 50,
    max_quota_mb: 10240,
})

const onSubscriptionChange = () => {
    const selectedSub = props.subscriptions.find(s => s.id === createForm.subscription_id)
    if (selectedSub && selectedSub.domain && !createForm.domain) {
        createForm.domain = selectedSub.domain
    }
}

const openCreateModal = () => {
    createForm.reset()
    createForm.subscription_id = props.subscriptions[0]?.id || ''
    onSubscriptionChange()
    showCreateModal.value = true
}

const submitCreate = () => {
    createForm.post(route('admin.email.domains.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false
            createForm.reset()
            feedbackMsg.value = `Email domain provisioned with 2048-bit DKIM & SPF records.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        },
    })
}

// 2. CATCHALL MODAL
const showCatchallModal = ref(false)
const selectedDomainForCatchall = ref(null)
const catchallForm = useForm({
    catchall_action: 'reject',
    catchall_address: '',
})

const openCatchallModal = (domain) => {
    selectedDomainForCatchall.value = domain
    catchallForm.catchall_action = domain.catchall_action || 'reject'
    catchallForm.catchall_address = domain.catchall_address || ''
    showCatchallModal.value = true
}

const submitCatchall = () => {
    if (!selectedDomainForCatchall.value) return
    catchallForm.post(route('admin.email.domains.catchall', selectedDomainForCatchall.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showCatchallModal.value = false
            feedbackMsg.value = `Catch-all routing updated for ${selectedDomainForCatchall.value.domain}.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 3. DNS RECORDS MODAL
const showDnsModal = ref(false)
const selectedDomainForDns = ref(null)
const dnsDiagnostics = ref(null)
const isVerifyingDns = ref(false)

const openDnsModal = async (domain) => {
    selectedDomainForDns.value = domain
    showDnsModal.value = true
    isVerifyingDns.value = true
    dnsDiagnostics.value = null

    try {
        const res = await fetch(route('admin.email.domains.verify-dns', domain.id))
        const data = await res.json()
        dnsDiagnostics.value = data
    } catch (e) {
        dnsDiagnostics.value = {
            status: 'error',
            message: 'Unable to query live DNS records.'
        }
    } finally {
        isVerifyingDns.value = false
    }
}

// 4. TOGGLE STATUS
const toggleStatus = (domain) => {
    const action = domain.status === 'active' ? 'Suspend' : 'Activate'
    if (confirm(`${action} email service for domain ${domain.domain}?`)) {
        useForm({}).post(route('admin.email.domains.toggle', domain.id), {
            preserveScroll: true,
            onSuccess: () => {
                feedbackMsg.value = `Domain ${domain.domain} status updated.`
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    }
}

// 5. DELETE DOMAIN
const showDeleteModal = ref(false)
const domainToDelete = ref(null)
const deleteForm = useForm({})

const openDeleteModal = (domain) => {
    domainToDelete.value = domain
    showDeleteModal.value = true
}

const submitDelete = () => {
    if (!domainToDelete.value) return
    deleteForm.delete(route('admin.email.domains.destroy', domainToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false
            feedbackMsg.value = `Domain ${domainToDelete.value.domain} and mailboxes deleted.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

const copyToClipboard = (text) => {
    navigator.clipboard.writeText(text)
    feedbackMsg.value = 'Copied to clipboard!'
    setTimeout(() => { feedbackMsg.value = '' }, 2500)
}
</script>

<template>
    <Head title="Mail Domains - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Email', href: route('admin.email.domains') },
                    { label: 'Domains' }
                ]"
            >
                <template #actions>
                    <a
                        href="/webmail"
                        target="_blank"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowTopRightOnSquareIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Webmail SSO</span>
                    </a>

                    <button 
                        type="button" 
                        @click="openCreateModal"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <PlusIcon class="w-3.5 h-3.5" />
                        <span>Add Mail Domain</span>
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
                    title="Managed Mail Domains"
                    :value="String(stats.total_domains || domains.length || 0)"
                    badge="Domains"
                    badgeType="info"
                    color="blue"
                    :icon="EnvelopeIcon"
                />

                <InfoCard
                    title="Active Mailboxes"
                    :value="String(stats.total_accounts || 0)"
                    badge="Accounts"
                    badgeType="success"
                    color="emerald"
                    :icon="GlobeAltIcon"
                />

                <InfoCard
                    title="DKIM & SPF Health"
                    :value="`${stats.dkim_verified_percent || 100}%`"
                    badge="Verified"
                    badgeType="success"
                    color="purple"
                    :icon="ShieldCheckIcon"
                />

                <InfoCard
                    title="Total Quota Allocation"
                    :value="`${stats.total_quota_gb || 0} GB`"
                    badge="Storage"
                    badgeType="info"
                    color="sky"
                    :icon="ServerIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search mail domain, owner, or subscription..."
                @search="() => {}"
                @filter="() => {}"
                @reset="resetFilters"
            >
                <FilterSelect
                    v-model="selectedStatusFilter"
                    label="Service Status"
                    :options="[
                        { label: 'All Statuses', value: 'all' },
                        { label: 'Active Domains', value: 'active' },
                        { label: 'Suspended Domains', value: 'suspended' }
                    ]"
                    placeholder="All Statuses"
                />
            </DataTableFilter>

            <!-- 4. Standard Data Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Mail Domain</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Associated Account / Owner</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Mailboxes</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Storage Quota</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">DKIM / SPF</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Catch-All Target</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Status</th>
                                <th class="py-2.5 px-3 w-14 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(domain, index) in filteredDomains" :key="domain.id || index" class="hover:bg-blue-50/30 transition">
                                <!-- # -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>

                                <!-- Domain -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs border border-blue-100 shrink-0">
                                            <EnvelopeIcon class="w-3.5 h-3.5" />
                                        </div>
                                        <Link 
                                            :href="route('admin.email.accounts', { domain_id: domain.id })"
                                            class="font-bold text-blue-600 hover:text-blue-800 font-mono leading-tight hover:underline cursor-pointer"
                                        >
                                            {{ domain.domain }}
                                        </Link>
                                    </div>
                                </td>

                                <!-- Account / Owner -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap">
                                    <div v-if="domain.subscription" class="space-y-0.5">
                                        <span class="font-bold text-slate-900 block leading-tight">{{ domain.subscription.domain }}</span>
                                        <span class="text-[10px] font-mono text-slate-400 block">@{{ domain.subscription.username }}</span>
                                    </div>
                                    <span v-else class="text-slate-400 italic">Global Domain</span>
                                </td>

                                <!-- Mailboxes Count -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono">
                                    <span class="font-bold text-slate-900">{{ domain.accounts_count || domain.accounts?.length || 0 }}</span>
                                    <span class="text-slate-400 text-[10.5px]"> / {{ domain.max_accounts || '∞' }}</span>
                                </td>

                                <!-- Quota -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[11px] text-slate-700">
                                    {{ domain.max_quota_mb ? `${Math.round(domain.max_quota_mb / 1024)} GB` : 'Unlimited' }}
                                </td>

                                <!-- DKIM / SPF -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <button 
                                        type="button" 
                                        @click="openDnsModal(domain)"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-[3px] text-[10px] font-bold border transition cursor-pointer"
                                        :class="domain.is_dkim_verified !== false ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' : 'bg-amber-50 text-amber-700 border-amber-200 hover:bg-amber-100'"
                                    >
                                        <ShieldCheckIcon class="w-3 h-3" />
                                        <span>{{ domain.is_dkim_verified !== false ? 'DKIM Ready' : 'Check DNS' }}</span>
                                    </button>
                                </td>

                                <!-- Catch-All -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono text-[10.5px] text-slate-600">
                                    <span v-if="domain.catchall_action === 'forward' && domain.catchall_address" class="text-blue-600 font-bold">
                                        ↳ {{ domain.catchall_address }}
                                    </span>
                                    <span v-else-if="domain.catchall_action === 'pipe'" class="text-purple-600 font-bold">
                                        | Script Pipe
                                    </span>
                                    <span v-else class="text-slate-400 italic">
                                        Fail / Reject (Blackhole)
                                    </span>
                                </td>

                                <!-- Status -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="domain.status === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ domain.status || 'active' }}
                                    </span>
                                </td>

                                <!-- Action -->
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <RowActionDropdown>
                                        <Link 
                                            :href="route('admin.email.accounts', { domain_id: domain.id })"
                                            class="w-full text-left px-3 py-1.5 text-xs text-blue-600 hover:bg-blue-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <EnvelopeIcon class="w-3.5 h-3.5 text-blue-500" />
                                            <span>Manage Accounts</span>
                                        </Link>

                                        <button
                                            type="button"
                                            @click="openDnsModal(domain)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <ShieldCheckIcon class="w-3.5 h-3.5 text-slate-500" />
                                            <span>DNS & DKIM Keys</span>
                                        </button>

                                        <button
                                            type="button"
                                            @click="openCatchallModal(domain)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <ArrowsRightLeftIcon class="w-3.5 h-3.5 text-slate-500" />
                                            <span>Configure Catch-All</span>
                                        </button>

                                        <button
                                            type="button"
                                            @click="toggleStatus(domain)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <BoltIcon class="w-3.5 h-3.5 text-slate-500" />
                                            <span>{{ domain.status === 'active' ? 'Suspend Mail Service' : 'Activate Service' }}</span>
                                        </button>

                                        <button
                                            type="button"
                                            @click="openDeleteModal(domain)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-rose-600 hover:bg-rose-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <TrashIcon class="w-3.5 h-3.5" />
                                            <span>Delete Domain</span>
                                        </button>
                                    </RowActionDropdown>
                                </td>
                            </tr>

                            <tr v-if="!filteredDomains || filteredDomains.length === 0">
                                <td colspan="9" class="py-12 text-center text-slate-400">
                                    No email domains configured.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 1. ADD MAIL DOMAIN MODAL -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <PlusIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Add Mail Domain
                        </h3>
                    </div>
                    <button @click="showCreateModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitCreate" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Account Subscription <span class="text-rose-500">*</span></label>
                        <select v-model="createForm.subscription_id" @change="onSubscriptionChange" required class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                            <option v-for="s in subscriptions" :key="s.id" :value="s.id">
                                {{ s.domain }} (@{{ s.username }})
                            </option>
                        </select>
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Mail Domain FQDN <span class="text-rose-500">*</span></label>
                        <input v-model="createForm.domain" type="text" required placeholder="example.com" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Max Accounts</label>
                            <input v-model.number="createForm.max_accounts" type="number" min="1" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        </div>
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Max Quota (MB)</label>
                            <input v-model.number="createForm.max_quota_mb" type="number" min="100" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        </div>
                    </div>

                    <div class="px-4 py-2.5 -mx-4 -mb-4 mt-4 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
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
                            {{ createForm.processing ? 'Provisioning...' : 'Add Mail Domain' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 2. CATCH-ALL ROUTING MODAL -->
        <div v-if="showCatchallModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <ArrowsRightLeftIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Catch-All Routing: {{ selectedDomainForCatchall?.domain }}
                        </h3>
                    </div>
                    <button @click="showCatchallModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitCatchall" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Action for Undelivered Emails</label>
                        <select v-model="catchallForm.catchall_action" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                            <option value="reject">Reject with Bounce (550 No Such User)</option>
                            <option value="blackhole">Discard (Drop Silently)</option>
                            <option value="forward">Forward to Designated Email Address</option>
                        </select>
                    </div>

                    <div v-if="catchallForm.catchall_action === 'forward'" class="space-y-1">
                        <label class="block font-bold text-slate-700">Forwarding Destination <span class="text-rose-500">*</span></label>
                        <input v-model="catchallForm.catchall_address" type="email" required placeholder="admin@example.com" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="px-4 py-2.5 -mx-4 -mb-4 mt-4 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                        <button
                            type="button"
                            @click="showCatchallModal = false"
                            class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="catchallForm.processing"
                            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ catchallForm.processing ? 'Saving...' : 'Save Routing' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 3. DNS RECORDS & DKIM MODAL -->
        <div v-if="showDnsModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-lg overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-purple-50 text-purple-600 flex items-center justify-center font-bold border border-purple-100">
                            <ShieldCheckIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            DNS Records & DKIM: {{ selectedDomainForDns?.domain }}
                        </h3>
                    </div>
                    <button @click="showDnsModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-3 text-xs">
                    <!-- MX Record -->
                    <div class="space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-slate-700">1. MX Record (Mail Exchanger)</span>
                            <button @click="copyToClipboard(`mail.${selectedDomainForDns?.domain}`)" class="text-[10px] text-blue-600 font-bold hover:underline cursor-pointer">Copy</button>
                        </div>
                        <div class="bg-slate-50 p-2 rounded-[3px] border border-slate-200 font-mono text-[11px] text-slate-800">
                            Priority: 10 | Target: mail.{{ selectedDomainForDns?.domain }}
                        </div>
                    </div>

                    <!-- SPF Record -->
                    <div class="space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-slate-700">2. SPF Record (TXT)</span>
                            <button @click="copyToClipboard('v=spf1 a mx ip4:127.0.0.1 ~all')" class="text-[10px] text-blue-600 font-bold hover:underline cursor-pointer">Copy</button>
                        </div>
                        <div class="bg-slate-50 p-2 rounded-[3px] border border-slate-200 font-mono text-[11px] text-slate-800 break-all">
                            v=spf1 a mx ip4:127.0.0.1 ~all
                        </div>
                    </div>

                    <!-- DKIM Record -->
                    <div class="space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-slate-700">3. DKIM 2048-bit Key (default._domainkey)</span>
                            <button @click="copyToClipboard(selectedDomainForDns?.dkim_public_key || 'v=DKIM1; k=rsa; p=MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA...')" class="text-[10px] text-blue-600 font-bold hover:underline cursor-pointer">Copy</button>
                        </div>
                        <div class="bg-slate-50 p-2 rounded-[3px] border border-slate-200 font-mono text-[10.5px] text-slate-800 break-all max-h-24 overflow-y-auto">
                            {{ selectedDomainForDns?.dkim_public_key || 'v=DKIM1; k=rsa; p=MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAuX2ZpGv1xY... (2048-bit RSA)' }}
                        </div>
                    </div>
                </div>

                <div class="px-4 py-2.5 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end text-xs">
                    <button
                        type="button"
                        @click="showDnsModal = false"
                        class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>

        <!-- 4. DELETE DOMAIN MODAL -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-rose-50 text-rose-600 flex items-center justify-center font-bold border border-rose-100">
                            <TrashIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Delete Mail Domain
                        </h3>
                    </div>
                    <button @click="showDeleteModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-2.5 text-xs">
                    <p class="text-slate-600">
                        Are you sure you want to permanently delete mail domain <strong class="text-slate-900 font-mono">[{{ domainToDelete?.domain }}]</strong>? All associated mailboxes, messages, and forwarders will be deleted immediately.
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
                        {{ deleteForm.processing ? 'Deleting...' : 'Delete Domain' }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
