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
    ShieldCheckIcon,
    BoltIcon,
    NoSymbolIcon,
    TrashIcon,
    PencilSquareIcon,
    KeyIcon,
    ClockIcon,
    ExclamationTriangleIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    policies: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    stats: {
        type: Object,
        default: () => ({
            total_policies: 0,
            active_policies: 0,
            total_breaches: 0,
            strict_rules: 0,
            dry_run_count: 0,
        }),
    },
    filters: {
        type: Object,
        default: () => ({ search: '', scope: 'all', status: 'all' }),
    },
})

// Flash Feedback Toast
const feedbackMsg = ref('')

// Filter State
const search = ref(props.filters?.search || '')
const currentScope = ref(props.filters?.scope || 'all')
const currentStatus = ref(props.filters?.status || 'all')

const applyFilters = () => {
    router.get(route('admin.api.rate-limits'), {
        search: search.value || undefined,
        scope: currentScope.value !== 'all' ? currentScope.value : undefined,
        status: currentStatus.value !== 'all' ? currentStatus.value : undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const selectScope = (scope) => {
    currentScope.value = scope
    applyFilters()
}

const resetFilters = () => {
    search.value = ''
    currentScope.value = 'all'
    currentStatus.value = 'all'
    applyFilters()
}

// 1. CREATE / EDIT POLICY MODAL
const showModal = ref(false)
const isEditing = ref(false)
const editingPolicyId = ref(null)

const policyForm = useForm({
    name: '',
    scope_type: 'global_ip',
    endpoint_pattern: '*',
    requests_per_minute: 60,
    burst_capacity: 10,
    action_on_breach: 'http_429',
    status: 'active',
})

const openCreateModal = () => {
    isEditing.value = false
    editingPolicyId.value = null
    policyForm.reset()
    policyForm.scope_type = 'global_ip'
    policyForm.endpoint_pattern = '*'
    policyForm.requests_per_minute = 60
    policyForm.burst_capacity = 10
    policyForm.action_on_breach = 'http_429'
    policyForm.status = 'active'
    showModal.value = true
}

const openEditModal = (p) => {
    isEditing.value = true
    editingPolicyId.value = p.id
    policyForm.name = p.name
    policyForm.scope_type = p.scope_type || 'global_ip'
    policyForm.endpoint_pattern = p.endpoint_pattern || '*'
    policyForm.requests_per_minute = p.requests_per_minute || 60
    policyForm.burst_capacity = p.burst_capacity || 10
    policyForm.action_on_breach = p.action_on_breach || 'http_429'
    policyForm.status = p.status || 'active'
    showModal.value = true
}

const submitPolicy = () => {
    if (isEditing.value) {
        policyForm.put(route('admin.api.rate-limits.update', editingPolicyId.value), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false
                feedbackMsg.value = 'Throttling policy updated.'
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    } else {
        policyForm.post(route('admin.api.rate-limits.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false
                feedbackMsg.value = 'New throttling policy created.'
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    }
}

// 2. TOGGLE POLICY
const togglePolicy = (p) => {
    router.post(route('admin.api.rate-limits.toggle', p.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = 'Policy status updated.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 3. DELETE MODAL
const showDeleteModal = ref(false)
const policyToDelete = ref(null)
const deleteForm = useForm({})

const openDeleteModal = (p) => {
    policyToDelete.value = p
    showDeleteModal.value = true
}

const submitDelete = () => {
    if (!policyToDelete.value) return
    deleteForm.delete(route('admin.api.rate-limits.destroy', policyToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false
            feedbackMsg.value = 'Throttling policy removed.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}
</script>

<template>
    <Head title="API Rate Limits - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'API', href: route('admin.api.rate-limits') },
                    { label: 'Rate Limits' }
                ]"
            >
                <template #actions>
                    <Link
                        :href="route('admin.api.keys')"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <KeyIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>API Keys</span>
                    </Link>

                    <button 
                        type="button" 
                        @click="openCreateModal"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <PlusIcon class="w-3.5 h-3.5" />
                        <span>Create Rate Policy</span>
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
                    title="Configured Policies"
                    :value="String(stats.total_policies || policies.data?.length || 0)"
                    badge="Rules"
                    badgeType="info"
                    color="blue"
                    :icon="ShieldCheckIcon"
                />

                <InfoCard
                    title="Active Rate Limits"
                    :value="String(stats.active_policies || policies.data?.filter(p => p.status === 'active').length || 0)"
                    badge="Enforcing"
                    badgeType="success"
                    color="emerald"
                    :icon="BoltIcon"
                />

                <InfoCard
                    title="Throttling Breaches"
                    :value="String(stats.total_breaches || 0)"
                    badge="429 Drops"
                    badgeType="warning"
                    color="rose"
                    :icon="ExclamationTriangleIcon"
                />

                <InfoCard
                    title="Strict IP Bans"
                    :value="String(stats.strict_rules || 0)"
                    badge="Strict"
                    badgeType="info"
                    color="purple"
                    :icon="NoSymbolIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Scope Tabs -->
            <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        v-for="s in ['all', 'global_ip', 'user_key', 'endpoint_regex']"
                        :key="s"
                        type="button"
                        @click="selectScope(s)"
                        :class="currentScope === s ? 'bg-blue-600 text-white' : 'bg-white text-slate-700 hover:bg-slate-50 border border-slate-200'"
                        class="px-3 py-1.5 rounded-[3px] font-bold text-xs uppercase font-mono transition cursor-pointer shadow-2xs"
                    >
                        {{ s.replace(/_/g, ' ') }}
                    </button>
                </div>
            </div>

            <!-- 4. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search policy name, endpoint path pattern..."
                @search="applyFilters"
                @filter="applyFilters"
                @reset="resetFilters"
            >
                <FilterSelect
                    v-model="currentStatus"
                    label="Status"
                    :options="[
                        { label: 'All Statuses', value: 'all' },
                        { label: 'Active Enforcement', value: 'active' },
                        { label: 'Disabled', value: 'disabled' }
                    ]"
                    placeholder="All Statuses"
                />
            </DataTableFilter>

            <!-- 5. Standard Data Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Policy Name</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Scope Type</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Endpoint Match Pattern</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Rate Limit</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Breach Action</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Status</th>
                                <th class="py-2.5 px-3 w-14 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(p, index) in policies.data" :key="p.id || index" class="hover:bg-blue-50/30 transition">
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-bold text-slate-900">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs border border-blue-100 shrink-0">
                                            <ShieldCheckIcon class="w-3.5 h-3.5" />
                                        </div>
                                        <span>{{ p.name }}</span>
                                    </div>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase bg-slate-100 text-slate-700 border border-slate-200 font-mono">
                                        {{ p.scope_type || 'Global IP' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono text-[11px] text-slate-700">
                                    <code>{{ p.endpoint_pattern || '*' }}</code>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono font-bold text-[11px] text-slate-900">
                                    {{ p.requests_per_minute || 60 }} / min (+{{ p.burst_capacity || 10 }} burst)
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="p.action_on_breach === 'http_429' ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ p.action_on_breach === 'http_429' ? 'HTTP 429' : 'Temp IP Ban' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="p.status === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-500 border-slate-200'"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ p.status === 'active' ? 'Active' : 'Disabled' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <RowActionDropdown>
                                        <button
                                            type="button"
                                            @click="openEditModal(p)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <PencilSquareIcon class="w-3.5 h-3.5 text-slate-500" />
                                            <span>Edit Policy</span>
                                        </button>

                                        <button
                                            type="button"
                                            @click="togglePolicy(p)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <NoSymbolIcon class="w-3.5 h-3.5 text-slate-500" />
                                            <span>{{ p.status === 'active' ? 'Disable Policy' : 'Activate Policy' }}</span>
                                        </button>

                                        <button
                                            type="button"
                                            @click="openDeleteModal(p)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-rose-600 hover:bg-rose-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <TrashIcon class="w-3.5 h-3.5" />
                                            <span>Delete Policy</span>
                                        </button>
                                    </RowActionDropdown>
                                </td>
                            </tr>

                            <tr v-if="!policies.data || policies.data.length === 0">
                                <td colspan="8" class="py-12 text-center text-slate-400 font-sans">
                                    No throttling policies configured.
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
                            {{ isEditing ? 'Edit Throttling Policy' : 'Create Rate Limit Policy' }}
                        </h3>
                    </div>
                    <button @click="showModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitPolicy" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Policy Name <span class="text-rose-500">*</span></label>
                        <input v-model="policyForm.name" type="text" required placeholder="Strict Auth Route Limiter" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Scope Type</label>
                            <select v-model="policyForm.scope_type" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                                <option value="global_ip">Global IP Address</option>
                                <option value="user_key">User / API Key Token</option>
                                <option value="endpoint_regex">Endpoint Route Pattern</option>
                            </select>
                        </div>

                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Endpoint Match Pattern</label>
                            <input v-model="policyForm.endpoint_pattern" type="text" placeholder="/api/v1/auth/*" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Requests Per Minute <span class="text-rose-500">*</span></label>
                            <input v-model.number="policyForm.requests_per_minute" type="number" min="1" max="10000" required class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        </div>

                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Burst Allowance</label>
                            <input v-model.number="policyForm.burst_capacity" type="number" min="0" max="100" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Action On Limit Breach</label>
                        <select v-model="policyForm.action_on_breach" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                            <option value="http_429">Throttle with HTTP 429 Too Many Requests</option>
                            <option value="temp_ban">Temporary IP Ban (15 mins via Firewall)</option>
                        </select>
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
                            :disabled="policyForm.processing"
                            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ policyForm.processing ? 'Saving...' : (isEditing ? 'Save Changes' : 'Create Policy') }}
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
                            Delete Rate Limit Policy
                        </h3>
                    </div>
                    <button @click="showDeleteModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-2.5 text-xs">
                    <p class="text-slate-600">
                        Are you sure you want to delete policy <strong class="text-slate-900 font-mono">[{{ policyToDelete?.name }}]</strong>?
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
                        {{ deleteForm.processing ? 'Deleting...' : 'Delete Policy' }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
