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
    ArrowsRightLeftIcon,
    TrashIcon,
    PlusIcon,
    PencilSquareIcon,
    BoltIcon,
    XMarkIcon,
    CheckIcon,
    GlobeAltIcon,
    EnvelopeIcon,
    InboxArrowDownIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    forwarders: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            total_forwarders: 0,
            multi_recipient_count: 0,
            local_copy_count: 0,
        }),
    },
    domains: {
        type: Array,
        default: () => [],
    },
    subscriptions: {
        type: Array,
        default: () => [],
    },
    currentDomain: {
        type: String,
        default: 'all',
    },
    currentStatus: {
        type: String,
        default: 'all',
    },
})

// Toast Feedback
const feedbackMsg = ref('')

// Filters
const search = ref('')
const selectedDomainFilter = ref(props.currentDomain || 'all')
const selectedStatusFilter = ref(props.currentStatus || 'all')

const domainOptions = computed(() => {
    const list = [{ label: 'All Mail Domains', value: 'all' }]
    props.domains.forEach(d => {
        list.push({ label: d.domain, value: d.domain })
    })
    return list
})

const filteredForwarders = computed(() => {
    let list = props.forwarders || []

    if (search.value.trim()) {
        const q = search.value.toLowerCase()
        list = list.filter(f =>
            (f.source || '').toLowerCase().includes(q) ||
            (f.destination || '').toLowerCase().includes(q) ||
            (f.domain || '').toLowerCase().includes(q) ||
            (f.subscription?.domain || '').toLowerCase().includes(q)
        )
    }

    if (selectedDomainFilter.value !== 'all') {
        list = list.filter(f => f.domain === selectedDomainFilter.value)
    }

    if (selectedStatusFilter.value !== 'all') {
        list = list.filter(f => (f.status || 'active') === selectedStatusFilter.value)
    }

    return list
})

const resetFilters = () => {
    search.value = ''
    selectedDomainFilter.value = 'all'
    selectedStatusFilter.value = 'all'
}

// 1. CREATE FORWARDER MODAL
const showCreateModal = ref(false)
const createForm = useForm({
    email_domain_id: props.domains[0]?.id || '',
    source: '',
    destination: '',
    keep_local_copy: false,
})

const openCreateModal = () => {
    createForm.reset()
    createForm.email_domain_id = props.domains[0]?.id || ''
    showCreateModal.value = true
}

const submitCreate = () => {
    createForm.post(route('admin.email.forwarders.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false
            feedbackMsg.value = `Forwarding rule created successfully.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 2. EDIT FORWARDER MODAL
const showEditModal = ref(false)
const selectedForwarder = ref(null)
const editForm = useForm({
    destination: '',
    keep_local_copy: false,
})

const openEditModal = (fwd) => {
    selectedForwarder.value = fwd
    editForm.destination = fwd.destination
    editForm.keep_local_copy = fwd.keep_local_copy || false
    showEditModal.value = true
}

const submitEdit = () => {
    if (!selectedForwarder.value) return
    editForm.put(route('admin.email.forwarders.update', selectedForwarder.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false
            feedbackMsg.value = `Forwarding rule for '${selectedForwarder.value.source}' updated.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 3. TOGGLE STATUS
const toggleStatus = (fwd) => {
    const action = fwd.status === 'active' ? 'Suspend' : 'Activate'
    if (confirm(`${action} forwarding rule for ${fwd.source}?`)) {
        useForm({}).post(route('admin.email.forwarders.toggle', fwd.id), {
            preserveScroll: true,
            onSuccess: () => {
                feedbackMsg.value = `Forwarding rule status updated.`
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    }
}

// 4. DELETE FORWARDER
const showDeleteModal = ref(false)
const forwarderToDelete = ref(null)
const deleteForm = useForm({})

const openDeleteModal = (fwd) => {
    forwarderToDelete.value = fwd
    showDeleteModal.value = true
}

const submitDelete = () => {
    if (!forwarderToDelete.value) return
    deleteForm.delete(route('admin.email.forwarders.destroy', forwarderToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false
            feedbackMsg.value = `Forwarding rule deleted.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}
</script>

<template>
    <Head title="Mail Forwarders - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Services', href: '#' },
                    { label: 'Email Services', href: route('admin.email.domains') },
                    { label: 'Mail Forwarders' }
                ]"
            >
                <template #actions>
                    <Link
                        :href="route('admin.email.domains')"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowLeftIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Mail Domains</span>
                    </Link>

                    <button 
                        type="button" 
                        @click="openCreateModal"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <PlusIcon class="w-3.5 h-3.5" />
                        <span>Create Forwarder</span>
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
            <InfoCardsGrid :cols="3">
                <InfoCard
                    title="Forwarding Rules"
                    :value="String(stats.total_forwarders || forwarders.length || 0)"
                    badge="Active"
                    badgeType="info"
                    color="blue"
                    :icon="ArrowsRightLeftIcon"
                />

                <InfoCard
                    title="Multi-Target Relays"
                    :value="String(stats.multi_recipient_count || 0)"
                    badge="Multi"
                    badgeType="success"
                    color="emerald"
                    :icon="EnvelopeIcon"
                />

                <InfoCard
                    title="Retained Local Copies"
                    :value="String(stats.local_copy_count || 0)"
                    badge="Inbox Copy"
                    badgeType="info"
                    color="purple"
                    :icon="InboxArrowDownIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search source address, destination, or domain..."
                @search="() => {}"
                @filter="() => {}"
                @reset="resetFilters"
            >
                <FilterSelect
                    v-model="selectedDomainFilter"
                    label="Filter Domain"
                    :options="domainOptions"
                    placeholder="All Domains"
                />

                <FilterSelect
                    v-model="selectedStatusFilter"
                    label="Rule Status"
                    :options="[
                        { label: 'All Statuses', value: 'all' },
                        { label: 'Active Rules', value: 'active' },
                        { label: 'Suspended Rules', value: 'suspended' }
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
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Source Email Address</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Forwarding Target Address(es)</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Parent Domain / Subscription</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Local Copy</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Status</th>
                                <th class="py-2.5 px-3 w-28">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(fwd, index) in filteredForwarders" :key="fwd.id || index" class="hover:bg-blue-50/30 transition">
                                <!-- # -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>

                                <!-- Source -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs border border-blue-100 shrink-0">
                                            <ArrowsRightLeftIcon class="w-3.5 h-3.5" />
                                        </div>
                                        <span class="font-bold text-slate-900 font-mono leading-tight">{{ fwd.source }}</span>
                                    </div>
                                </td>

                                <!-- Destination -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono text-[11px] text-blue-700 font-bold max-w-sm truncate" :title="fwd.destination">
                                    ↳ {{ fwd.destination }}
                                </td>

                                <!-- Parent Domain -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap">
                                    <div class="space-y-0.5">
                                        <span class="font-bold text-slate-900 block leading-tight font-mono text-[11px]">{{ fwd.domain }}</span>
                                        <span v-if="fwd.subscription" class="text-[10px] text-slate-400 block">@{{ fwd.subscription.domain }}</span>
                                    </div>
                                </td>

                                <!-- Local Copy -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span :class="fwd.keep_local_copy ? 'text-emerald-700 font-bold bg-emerald-50 border border-emerald-200' : 'text-slate-400 bg-slate-50 border border-slate-200'" class="px-2 py-0.5 rounded-[3px] text-[10px]">
                                        {{ fwd.keep_local_copy ? 'Retained' : 'Discarded' }}
                                    </span>
                                </td>

                                <!-- Status -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="fwd.status === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ fwd.status || 'active' }}
                                    </span>
                                </td>

                                <!-- Action -->
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1">
                                        <button 
                                            type="button" 
                                            @click="openEditModal(fwd)"
                                            class="px-2 py-1 bg-white hover:bg-slate-50 text-blue-700 font-bold rounded-[3px] text-xs border border-slate-200 shadow-2xs transition cursor-pointer"
                                            title="Edit Forwarder"
                                        >
                                            Edit ✏️
                                        </button>

                                        <RowActionDropdown>
                                            <button
                                                type="button"
                                                @click="toggleStatus(fwd)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <BoltIcon class="w-3.5 h-3.5 text-slate-500" />
                                                <span>{{ fwd.status === 'active' ? 'Suspend Rule' : 'Activate Rule' }}</span>
                                            </button>

                                            <button
                                                type="button"
                                                @click="openDeleteModal(fwd)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-rose-600 hover:bg-rose-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <TrashIcon class="w-3.5 h-3.5" />
                                                <span>Delete Rule</span>
                                            </button>
                                        </RowActionDropdown>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!filteredForwarders || filteredForwarders.length === 0">
                                <td colspan="7" class="py-12 text-center text-slate-400">
                                    No email forwarders configured.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 1. CREATE FORWARDER MODAL -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <PlusIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Create Mail Forwarder
                        </h3>
                    </div>
                    <button @click="showCreateModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitCreate" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Domain <span class="text-rose-500">*</span></label>
                        <select v-model="createForm.email_domain_id" required class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                            <option v-for="d in domains" :key="d.id" :value="d.id">
                                @{{ d.domain }}
                            </option>
                        </select>
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Source Username <span class="text-rose-500">*</span></label>
                        <input v-model="createForm.source" type="text" required placeholder="e.g. sales, support" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Destination Email(s) <span class="text-rose-500">*</span></label>
                        <input v-model="createForm.destination" type="text" required placeholder="user@gmail.com, team@company.com" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        <span class="text-[10px] text-slate-400 block">Separate multiple destinations with commas.</span>
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <input v-model="createForm.keep_local_copy" id="keep_local_copy" type="checkbox" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer" />
                        <label for="keep_local_copy" class="text-slate-700 font-medium cursor-pointer">Retain copy in local mailbox if it exists</label>
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
                            {{ createForm.processing ? 'Creating...' : 'Create Forwarder' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 2. EDIT FORWARDER MODAL -->
        <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <PencilSquareIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Edit Forwarder: {{ selectedForwarder?.source }}
                        </h3>
                    </div>
                    <button @click="showEditModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitEdit" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Destination Email(s) <span class="text-rose-500">*</span></label>
                        <input v-model="editForm.destination" type="text" required class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <input v-model="editForm.keep_local_copy" id="edit_keep_local_copy" type="checkbox" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer" />
                        <label for="edit_keep_local_copy" class="text-slate-700 font-medium cursor-pointer">Retain copy in local mailbox</label>
                    </div>

                    <div class="px-4 py-2.5 -mx-4 -mb-4 mt-4 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                        <button
                            type="button"
                            @click="showEditModal = false"
                            class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="editForm.processing"
                            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ editForm.processing ? 'Saving...' : 'Update Forwarder' }}
                        </button>
                    </div>
                </form>
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
                            Delete Forwarder
                        </h3>
                    </div>
                    <button @click="showDeleteModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-2.5 text-xs">
                    <p class="text-slate-600">
                        Are you sure you want to delete forwarding rule <strong class="text-slate-900 font-mono">[{{ forwarderToDelete?.source }} ↳ {{ forwarderToDelete?.destination }}]</strong>?
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
                        {{ deleteForm.processing ? 'Deleting...' : 'Delete Forwarder' }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
