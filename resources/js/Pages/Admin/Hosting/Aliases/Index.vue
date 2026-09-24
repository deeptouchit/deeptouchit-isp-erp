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
    GlobeAltIcon,
    ShieldCheckIcon,
    CheckCircleIcon,
    ArrowsRightLeftIcon,
    PlusIcon,
    ArrowPathIcon,
    TrashIcon,
    PauseIcon,
    PlayIcon,
    LockClosedIcon,
    XMarkIcon,
    ArrowTopRightOnSquareIcon,
    ArrowRightIcon,
    LinkIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    aliases: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    stats: {
        type: Object,
        default: () => ({
            total_aliases: 0,
            active_ssl_count: 0,
            parked_mirrors_count: 0,
            active_aliases: 0,
        }),
    },
    subscriptions: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({ search: '', target_type: '', ssl_status: '', status: '' }),
    },
})

// Flash Feedback
const feedbackMsg = ref('')

// Filter State
const search = ref(props.filters?.search || '')
const targetType = ref(props.filters?.target_type || '')
const sslStatus = ref(props.filters?.ssl_status || '')
const status = ref(props.filters?.status || '')

const applyFilters = () => {
    router.get(route('admin.hosting.aliases'), {
        search: search.value || undefined,
        target_type: targetType.value || undefined,
        ssl_status: sslStatus.value || undefined,
        status: status.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const resetFilters = () => {
    search.value = ''
    targetType.value = ''
    sslStatus.value = ''
    status.value = ''
    applyFilters()
}

// 1. ADD ALIAS MODAL
const showAddModal = ref(false)
const addForm = useForm({
    subscription_id: props.subscriptions?.[0]?.id || '',
    domain: '',
    target_type: 'parked',
    redirect_url: '',
    redirect_status_code: 301,
    auto_ssl: true,
})

const openAddModal = (presetSubId = null) => {
    addForm.reset()
    addForm.subscription_id = presetSubId || props.subscriptions?.[0]?.id || ''
    addForm.target_type = 'parked'
    addForm.redirect_status_code = 301
    showAddModal.value = true
}

const submitAddAlias = () => {
    addForm.post(route('admin.hosting.aliases.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showAddModal.value = false
            addForm.reset()
            feedbackMsg.value = 'Alias domain mapped and virtual host alias activated.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 2. TOGGLE STATUS
const toggleStatus = (alias) => {
    const isSuspended = alias.status === 'suspended'
    const action = isSuspended ? 'unsuspend' : 'suspend'
    const msg = isSuspended 
        ? `Reactivate domain alias '${alias.domain}'?`
        : `Suspend domain alias '${alias.domain}'? Routing will be halted.`
    
    if (confirm(msg)) {
        router.post(route(`admin.websites.${action}`, alias.id), {}, {
            preserveScroll: true,
            onSuccess: () => {
                feedbackMsg.value = `Domain alias '${alias.domain}' ${action}ed.`
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    }
}

// 3. ISSUE SSL
const issuingSslId = ref(null)
const issueSsl = (alias) => {
    issuingSslId.value = alias.id
    router.post(route('admin.websites.issue-ssl', alias.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            issuingSslId.value = null
            feedbackMsg.value = `Let's Encrypt SSL certificate provisioned for '${alias.domain}'.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        },
        onError: () => {
            issuingSslId.value = null
        }
    })
}

// 4. DELETE MODAL
const showDeleteModal = ref(false)
const aliasToDelete = ref(null)
const deleteForm = useForm({})

const openDeleteModal = (alias) => {
    aliasToDelete.value = alias
    showDeleteModal.value = true
}

const submitDeleteAlias = () => {
    if (!aliasToDelete.value) return
    deleteForm.delete(route('admin.hosting.aliases.destroy', aliasToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false
            feedbackMsg.value = `Domain alias '${aliasToDelete.value.domain}' deleted.`
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
    <Head title="Domain Aliases & Parked Mirrors - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Hosting', href: '#' },
                    { label: 'Domain Aliases' }
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
                        @click="openAddModal()"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <PlusIcon class="w-3.5 h-3.5" />
                        <span>Add Domain Alias</span>
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
                    title="Total Aliases"
                    :value="String(stats.total_aliases || 0)"
                    badge="Pointers"
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
                    title="Parked Mirrors"
                    :value="String(stats.parked_mirrors_count || 0)"
                    badge="Mirrors"
                    badgeType="info"
                    color="sky"
                    :icon="ArrowsRightLeftIcon"
                />

                <InfoCard
                    title="Active Aliases"
                    :value="String(stats.active_aliases || 0)"
                    badge="Routing"
                    badgeType="success"
                    color="purple"
                    :icon="CheckCircleIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search by alias domain, target, or user..."
                @search="applyFilters"
                @filter="applyFilters"
                @reset="resetFilters"
            >
                <FilterSelect
                    v-model="targetType"
                    label="Routing Type"
                    :options="[
                        { label: 'Parked Domain (VHost Mirror)', value: 'parked' },
                        { label: 'HTTP Redirect (301/302)', value: 'redirect' }
                    ]"
                    placeholder="All Routing Types"
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
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Alias Domain</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Target / Parent Account</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Routing Behavior</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">SSL Security</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Status</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Created</th>
                                <th class="py-2.5 px-3 w-16">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(alias, index) in aliases.data" :key="alias.id" class="hover:bg-blue-50/30 transition">
                                <!-- # -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ ((aliases.current_page || 1) - 1) * (aliases.per_page || 15) + (index + 1) }}
                                </td>

                                <!-- Alias Domain -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs border border-blue-100 shrink-0">
                                            <GlobeAltIcon class="w-3.5 h-3.5" />
                                        </div>
                                        <div>
                                            <a 
                                                :href="`http://${alias.domain}`" 
                                                target="_blank" 
                                                class="font-bold text-slate-900 hover:text-blue-600 flex items-center gap-1 leading-tight"
                                            >
                                                <span>{{ alias.domain }}</span>
                                                <ArrowTopRightOnSquareIcon class="w-3 h-3 text-slate-400" />
                                            </a>
                                            <span class="text-[10px] font-mono text-slate-400 block">Domain Pointer</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Target Account -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap">
                                    <div v-if="alias.subscription" class="space-y-0.5">
                                        <span class="font-bold text-slate-900 block leading-tight">{{ alias.subscription.domain }}</span>
                                        <span class="text-[10px] font-mono text-slate-400 block">
                                            @{{ alias.subscription.username }}
                                        </span>
                                    </div>
                                    <span v-else class="text-slate-400 italic">Standalone</span>
                                </td>

                                <!-- Routing Behavior -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        v-if="alias.target_type === 'redirect'"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-[3px] text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200"
                                    >
                                        <ArrowRightIcon class="w-3 h-3 text-amber-600" />
                                        <span>Redirect ({{ alias.redirect_status_code || 301 }})</span>
                                    </span>
                                    <span 
                                        v-else
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-[3px] text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200"
                                    >
                                        <ArrowsRightLeftIcon class="w-3 h-3 text-blue-600" />
                                        <span>Parked Mirror</span>
                                    </span>
                                </td>

                                <!-- SSL Security -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        v-if="alias.ssl_status === 'active'"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-[3px] text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200"
                                    >
                                        <ShieldCheckIcon class="w-3.5 h-3.5 text-emerald-600" />
                                        <span>Auto-SSL</span>
                                    </span>
                                    <button
                                        v-else
                                        type="button"
                                        @click="issueSsl(alias)"
                                        :disabled="issuingSslId === alias.id"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-[3px] text-[10px] font-bold bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 transition cursor-pointer disabled:opacity-50"
                                        title="Click to issue SSL"
                                    >
                                        <LockClosedIcon class="w-3 h-3 text-amber-600" />
                                        <span>{{ issuingSslId === alias.id ? 'Issuing...' : 'Issue SSL' }}</span>
                                    </button>
                                </td>

                                <!-- Status -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="alias.status === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200'"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-[3px] text-[10px] font-bold capitalize border"
                                    >
                                        <span 
                                            class="w-1.5 h-1.5 rounded-full"
                                            :class="alias.status === 'active' ? 'bg-emerald-500' : 'bg-amber-500'"
                                        ></span>
                                        {{ alias.status }}
                                    </span>
                                </td>

                                <!-- Created -->
                                <td class="py-2.5 px-3 border-r border-slate-100 font-mono text-slate-400 text-[10.5px] whitespace-nowrap">
                                    {{ formatDate(alias.created_at) }}
                                </td>

                                <!-- Action Dropdown -->
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <RowActionDropdown>
                                        <!-- Issue SSL -->
                                        <button 
                                            type="button" 
                                            @click="issueSsl(alias)"
                                            :disabled="issuingSslId === alias.id"
                                            class="w-full text-left px-3 py-1.5 text-xs text-emerald-700 hover:bg-emerald-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <ShieldCheckIcon class="w-3.5 h-3.5" />
                                            <span>Issue / Renew SSL</span>
                                        </button>

                                        <!-- Suspend / Unsuspend -->
                                        <button 
                                            type="button" 
                                            @click="toggleStatus(alias)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-amber-700 hover:bg-amber-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <PauseIcon v-if="alias.status === 'active'" class="w-3.5 h-3.5" />
                                            <PlayIcon v-else class="w-3.5 h-3.5" />
                                            <span>{{ alias.status === 'active' ? 'Suspend Alias' : 'Unsuspend' }}</span>
                                        </button>

                                        <!-- Delete -->
                                        <button 
                                            type="button" 
                                            @click="openDeleteModal(alias)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-rose-600 hover:bg-rose-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <TrashIcon class="w-3.5 h-3.5" />
                                            <span>Delete Alias</span>
                                        </button>
                                    </RowActionDropdown>
                                </td>
                            </tr>

                            <tr v-if="!aliases.data || aliases.data.length === 0">
                                <td colspan="8" class="py-12 text-center text-slate-400">
                                    No domain aliases found matching the criteria.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <Pagination :links="aliases.links" :from="aliases.from" :to="aliases.to" :total="aliases.total" />
            </div>
        </div>

        <!-- 1. ADD ALIAS MODAL -->
        <div v-if="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-lg overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <ArrowsRightLeftIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Add Domain Alias / Mirror
                        </h3>
                    </div>
                    <button @click="showAddModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitAddAlias" class="p-4 space-y-3.5 text-xs">
                    <!-- Parent Subscription -->
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">
                            Target Hosting Account <span class="text-rose-500">*</span>
                        </label>
                        <select
                            v-model="addForm.subscription_id"
                            required
                            class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition-all shadow-2xs cursor-pointer"
                        >
                            <option v-for="sub in subscriptions" :key="sub.id" :value="sub.id">
                                {{ sub.domain }} (@{{ sub.username }} - {{ sub.user?.first_name }} {{ sub.user?.last_name }})
                            </option>
                        </select>
                        <span v-if="addForm.errors.subscription_id" class="text-rose-600 text-[10.5px] font-semibold block">{{ addForm.errors.subscription_id }}</span>
                    </div>

                    <!-- Alias Domain Name -->
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">
                            Alias Domain FQDN <span class="text-rose-500">*</span>
                        </label>
                        <input
                            v-model="addForm.domain"
                            type="text"
                            required
                            placeholder="e.g. brand-mirror.com"
                            class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition-all shadow-2xs"
                        />
                        <span v-if="addForm.errors.domain" class="text-rose-600 text-[10.5px] font-semibold block">{{ addForm.errors.domain }}</span>
                    </div>

                    <!-- Target Type -->
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Alias Routing Behavior</label>
                        <div class="grid grid-cols-2 gap-2">
                            <label 
                                class="p-2.5 rounded-[3px] border cursor-pointer flex items-center gap-2 transition"
                                :class="addForm.target_type === 'parked' ? 'bg-blue-50/50 border-blue-300 text-blue-900' : 'bg-white border-slate-200 text-slate-700'"
                            >
                                <input type="radio" v-model="addForm.target_type" value="parked" class="text-blue-600 focus:ring-blue-500" />
                                <div>
                                    <p class="font-bold">Parked Domain</p>
                                    <p class="text-[10px] text-slate-500">Mirror parent site content</p>
                                </div>
                            </label>

                            <label 
                                class="p-2.5 rounded-[3px] border cursor-pointer flex items-center gap-2 transition"
                                :class="addForm.target_type === 'redirect' ? 'bg-blue-50/50 border-blue-300 text-blue-900' : 'bg-white border-slate-200 text-slate-700'"
                            >
                                <input type="radio" v-model="addForm.target_type" value="redirect" class="text-blue-600 focus:ring-blue-500" />
                                <div>
                                    <p class="font-bold">HTTP Redirect</p>
                                    <p class="text-[10px] text-slate-500">Forward traffic to URL</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Redirect Specific Options -->
                    <div v-if="addForm.target_type === 'redirect'" class="p-3 bg-amber-50/50 border border-amber-200 rounded-[3px] space-y-2.5">
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Destination URL <span class="text-rose-500">*</span></label>
                            <input
                                v-model="addForm.redirect_url"
                                type="url"
                                required
                                placeholder="https://mainbrand.com/destination"
                                class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-white text-slate-900 focus:outline-none focus:ring-1 focus:ring-blue-500 font-mono"
                            />
                        </div>
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Redirect HTTP Code</label>
                            <select
                                v-model="addForm.redirect_status_code"
                                class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-white text-slate-900 focus:outline-none focus:ring-1 focus:ring-blue-500 font-mono"
                            >
                                <option :value="301">301 Permanent Redirect</option>
                                <option :value="302">302 Temporary Redirect</option>
                            </select>
                        </div>
                    </div>

                    <div class="pt-1">
                        <label class="inline-flex items-center gap-2 cursor-pointer font-bold text-slate-700">
                            <input v-model="addForm.auto_ssl" type="checkbox" class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300" />
                            <span>Auto-Issue Let's Encrypt SSL Certificate</span>
                        </label>
                    </div>

                    <div class="pt-2 flex items-center justify-end gap-2 border-t border-slate-100">
                        <button
                            type="button"
                            @click="showAddModal = false"
                            class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="addForm.processing"
                            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ addForm.processing ? 'Deploying...' : 'Deploy Alias' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 2. DELETE MODAL -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-rose-50 text-rose-600 flex items-center justify-center font-bold border border-rose-100">
                            <TrashIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Delete Domain Alias
                        </h3>
                    </div>
                    <button @click="showDeleteModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-2.5 text-xs">
                    <p class="text-slate-600">
                        Are you sure you want to delete alias domain <strong class="text-slate-900">{{ aliasToDelete?.domain }}</strong>?
                    </p>
                    <div class="p-2.5 bg-rose-50 border border-rose-200 text-rose-800 rounded-[3px]">
                        <p class="font-bold text-[11px]">⚠️ Alias Removal</p>
                        <p class="text-[10.5px] mt-0.5">This will remove the server_name entry from Nginx and stop routing requests for this domain.</p>
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
                        @click="submitDeleteAlias"
                        :disabled="deleteForm.processing"
                        class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                    >
                        {{ deleteForm.processing ? 'Deleting...' : 'Delete Alias' }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
