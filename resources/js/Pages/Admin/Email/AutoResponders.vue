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
    ChatBubbleLeftRightIcon,
    TrashIcon,
    PlusIcon,
    PencilSquareIcon,
    BoltIcon,
    XMarkIcon,
    CheckIcon,
    ClockIcon,
    EnvelopeIcon,
    EyeIcon,
    CalendarIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    autoResponders: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            total_auto_responders: 0,
            active_count: 0,
            scheduled_count: 0,
        }),
    },
    domains: {
        type: Array,
        default: () => [],
    },
    accounts: {
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

const filteredResponders = computed(() => {
    let list = props.autoResponders || []

    if (search.value.trim()) {
        const q = search.value.toLowerCase()
        list = list.filter(ar =>
            (ar.email || '').toLowerCase().includes(q) ||
            (ar.subject || '').toLowerCase().includes(q) ||
            (ar.from_name || '').toLowerCase().includes(q) ||
            (ar.domain || '').toLowerCase().includes(q)
        )
    }

    if (selectedDomainFilter.value !== 'all') {
        list = list.filter(ar => ar.domain === selectedDomainFilter.value)
    }

    if (selectedStatusFilter.value !== 'all') {
        list = list.filter(ar => (ar.status || 'active') === selectedStatusFilter.value)
    }

    return list
})

const resetFilters = () => {
    search.value = ''
    selectedDomainFilter.value = 'all'
    selectedStatusFilter.value = 'all'
}

// 1. CREATE AUTO-RESPONDER MODAL
const showCreateModal = ref(false)
const createForm = useForm({
    email_domain_id: props.domains[0]?.id || '',
    email_prefix: '',
    from_name: '',
    subject: 'Out of Office: Received Your Message',
    body: 'Hello,\n\nThank you for reaching out. We have received your email and will respond as soon as possible.\n\nBest regards,',
    is_html: false,
    interval_hours: 24,
    starts_at: '',
    stops_at: '',
})

const openCreateModal = () => {
    createForm.reset()
    createForm.email_domain_id = props.domains[0]?.id || ''
    showCreateModal.value = true
}

const submitCreate = () => {
    createForm.post(route('admin.email.auto-responders.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false
            feedbackMsg.value = `Auto responder created successfully.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 2. EDIT MODAL
const showEditModal = ref(false)
const selectedResponder = ref(null)
const editForm = useForm({
    from_name: '',
    subject: '',
    body: '',
    is_html: false,
    interval_hours: 24,
    starts_at: '',
    stops_at: '',
})

const openEditModal = (ar) => {
    selectedResponder.value = ar
    editForm.from_name = ar.from_name || ''
    editForm.subject = ar.subject || ''
    editForm.body = ar.body || ''
    editForm.is_html = ar.is_html || false
    editForm.interval_hours = ar.interval_hours || 24
    editForm.starts_at = ar.starts_at || ''
    editForm.stops_at = ar.stops_at || ''
    showEditModal.value = true
}

const submitEdit = () => {
    if (!selectedResponder.value) return
    editForm.put(route('admin.email.auto-responders.update', selectedResponder.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false
            feedbackMsg.value = `Auto responder for '${selectedResponder.value.email}' updated.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 3. PREVIEW MODAL
const showPreviewModal = ref(false)
const previewResponder = ref(null)

const openPreviewModal = (ar) => {
    previewResponder.value = ar
    showPreviewModal.value = true
}

// 4. TOGGLE STATUS
const toggleStatus = (ar) => {
    const action = ar.status === 'active' ? 'Pause' : 'Activate'
    if (confirm(`${action} auto responder for ${ar.email}?`)) {
        useForm({}).post(route('admin.email.auto-responders.toggle', ar.id), {
            preserveScroll: true,
            onSuccess: () => {
                feedbackMsg.value = `Auto responder status updated.`
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    }
}

// 5. DELETE MODAL
const showDeleteModal = ref(false)
const responderToDelete = ref(null)
const deleteForm = useForm({})

const openDeleteModal = (ar) => {
    responderToDelete.value = ar
    showDeleteModal.value = true
}

const submitDelete = () => {
    if (!responderToDelete.value) return
    deleteForm.delete(route('admin.email.auto-responders.destroy', responderToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false
            feedbackMsg.value = `Auto responder removed.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}
</script>

<template>
    <Head title="Auto Responders - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Services', href: '#' },
                    { label: 'Email Services', href: route('admin.email.domains') },
                    { label: 'Auto Responders' }
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
                        <span>Create Auto Responder</span>
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
                    title="Auto-Responders"
                    :value="String(stats.total_auto_responders || autoResponders.length || 0)"
                    badge="Configured"
                    badgeType="info"
                    color="blue"
                    :icon="ChatBubbleLeftRightIcon"
                />

                <InfoCard
                    title="Active Vacation Responders"
                    :value="String(stats.active_count || 0)"
                    badge="Active"
                    badgeType="success"
                    color="emerald"
                    :icon="ClockIcon"
                />

                <InfoCard
                    title="Scheduled Schedules"
                    :value="String(stats.scheduled_count || 0)"
                    badge="Timed"
                    badgeType="warning"
                    color="purple"
                    :icon="CalendarIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search mailbox email, subject line, or sender..."
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
                    label="Responder Status"
                    :options="[
                        { label: 'All Statuses', value: 'all' },
                        { label: 'Active Responders', value: 'active' },
                        { label: 'Paused Responders', value: 'paused' }
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
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Mailbox Email</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Response Subject Line</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Sender Name</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Rate Limit Interval</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Active Schedule</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Status</th>
                                <th class="py-2.5 px-3 w-28">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(ar, index) in filteredResponders" :key="ar.id || index" class="hover:bg-blue-50/30 transition">
                                <!-- # -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>

                                <!-- Email -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs border border-blue-100 shrink-0">
                                            <ChatBubbleLeftRightIcon class="w-3.5 h-3.5" />
                                        </div>
                                        <span class="font-bold text-slate-900 font-mono leading-tight">{{ ar.email }}</span>
                                    </div>
                                </td>

                                <!-- Subject -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-medium text-slate-800 max-w-sm truncate" :title="ar.subject">
                                    {{ ar.subject }}
                                </td>

                                <!-- From Name -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap text-slate-600">
                                    {{ ar.from_name || 'System Auto-Responder' }}
                                </td>

                                <!-- Interval -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[11px]">
                                    {{ ar.interval_hours || 24 }}h per sender
                                </td>

                                <!-- Schedule -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center text-[10.5px]">
                                    <span v-if="ar.starts_at || ar.stops_at" class="text-purple-700 font-mono font-bold">
                                        {{ ar.starts_at ? new Date(ar.starts_at).toLocaleDateString() : 'Immediate' }} → {{ ar.stops_at ? new Date(ar.stops_at).toLocaleDateString() : 'Indefinite' }}
                                    </span>
                                    <span v-else class="text-slate-400 italic">Always Active</span>
                                </td>

                                <!-- Status -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="ar.status === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-600 border-slate-200'"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ ar.status || 'active' }}
                                    </span>
                                </td>

                                <!-- Action -->
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1">
                                        <button 
                                            type="button" 
                                            @click="openPreviewModal(ar)"
                                            class="px-2 py-1 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs border border-slate-200 shadow-2xs transition cursor-pointer"
                                            title="Preview Template"
                                        >
                                            Preview 👁
                                        </button>

                                        <RowActionDropdown>
                                            <button
                                                type="button"
                                                @click="openEditModal(ar)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <PencilSquareIcon class="w-3.5 h-3.5 text-slate-500" />
                                                <span>Edit Message & Timer</span>
                                            </button>

                                            <button
                                                type="button"
                                                @click="toggleStatus(ar)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <BoltIcon class="w-3.5 h-3.5 text-slate-500" />
                                                <span>{{ ar.status === 'active' ? 'Pause Auto Responder' : 'Activate' }}</span>
                                            </button>

                                            <button
                                                type="button"
                                                @click="openDeleteModal(ar)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-rose-600 hover:bg-rose-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <TrashIcon class="w-3.5 h-3.5" />
                                                <span>Delete Responder</span>
                                            </button>
                                        </RowActionDropdown>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!filteredResponders || filteredResponders.length === 0">
                                <td colspan="8" class="py-12 text-center text-slate-400">
                                    No auto responders configured.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 1. CREATE AUTO-RESPONDER MODAL -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-lg overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <PlusIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Create Auto-Responder
                        </h3>
                    </div>
                    <button @click="showCreateModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitCreate" class="p-4 space-y-3 text-xs">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Domain <span class="text-rose-500">*</span></label>
                            <select v-model="createForm.email_domain_id" required class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                                <option v-for="d in domains" :key="d.id" :value="d.id">
                                    @{{ d.domain }}
                                </option>
                            </select>
                        </div>
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Mailbox Prefix <span class="text-rose-500">*</span></label>
                            <input v-model="createForm.email_prefix" type="text" required placeholder="e.g. support, info" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Sender / From Name</label>
                            <input v-model="createForm.from_name" type="text" placeholder="e.g. DeepTouch Support" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        </div>
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Interval (Hours)</label>
                            <input v-model.number="createForm.interval_hours" type="number" min="1" max="168" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Subject Line <span class="text-rose-500">*</span></label>
                        <input v-model="createForm.subject" type="text" required class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Message Body <span class="text-rose-500">*</span></label>
                        <textarea v-model="createForm.body" rows="4" required class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"></textarea>
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
                            {{ createForm.processing ? 'Creating...' : 'Create Auto-Responder' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 2. EDIT MODAL -->
        <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-lg overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <PencilSquareIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Edit Auto-Responder: {{ selectedResponder?.email }}
                        </h3>
                    </div>
                    <button @click="showEditModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitEdit" class="p-4 space-y-3 text-xs">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Sender / From Name</label>
                            <input v-model="editForm.from_name" type="text" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        </div>
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Interval (Hours)</label>
                            <input v-model.number="editForm.interval_hours" type="number" min="1" max="168" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Subject Line <span class="text-rose-500">*</span></label>
                        <input v-model="editForm.subject" type="text" required class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Message Body <span class="text-rose-500">*</span></label>
                        <textarea v-model="editForm.body" rows="5" required class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"></textarea>
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
                            {{ editForm.processing ? 'Saving...' : 'Save Changes' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 3. PREVIEW MODAL -->
        <div v-if="showPreviewModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-lg overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <EyeIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Auto-Responder Preview: {{ previewResponder?.email }}
                        </h3>
                    </div>
                    <button @click="showPreviewModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-3 text-xs">
                    <div class="p-3 bg-slate-50 rounded-[3px] border border-slate-200 space-y-1">
                        <p class="font-bold text-slate-900">From: {{ previewResponder?.from_name || 'System' }} &lt;{{ previewResponder?.email }}&gt;</p>
                        <p class="font-bold text-blue-700">Subject: {{ previewResponder?.subject }}</p>
                    </div>

                    <div class="p-3 bg-white rounded-[3px] border border-slate-200 text-slate-800 whitespace-pre-wrap font-sans text-xs leading-relaxed">
                        {{ previewResponder?.body }}
                    </div>
                </div>

                <div class="px-4 py-2.5 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end text-xs">
                    <button
                        type="button"
                        @click="showPreviewModal = false"
                        class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>

        <!-- 4. DELETE MODAL -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-rose-50 text-rose-600 flex items-center justify-center font-bold border border-rose-100">
                            <TrashIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Delete Auto-Responder
                        </h3>
                    </div>
                    <button @click="showDeleteModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-2.5 text-xs">
                    <p class="text-slate-600">
                        Are you sure you want to delete auto-responder for <strong class="text-slate-900 font-mono">[{{ responderToDelete?.email }}]</strong>?
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
                        {{ deleteForm.processing ? 'Deleting...' : 'Delete' }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
