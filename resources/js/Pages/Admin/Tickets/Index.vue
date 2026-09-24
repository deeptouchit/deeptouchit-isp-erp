<script setup>
import { ref, computed, watch } from 'vue'
import { Head, router, Link, useForm } from '@inertiajs/vue3'
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
    LifebuoyIcon,
    ChatBubbleLeftRightIcon,
    ClockIcon,
    ExclamationTriangleIcon,
    UserCircleIcon,
    TrashIcon,
    TagIcon,
    ShieldExclamationIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    tickets: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    stats: {
        type: Object,
        default: () => ({
            total_tickets: 0,
            open_count: 0,
            critical_count: 0,
            in_progress_count: 0,
            answered_count: 0,
            closed_count: 0,
        }),
    },
    staff: {
        type: Array,
        default: () => [],
    },
    clients: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({ search: '', status: '', priority: '', department: '', assigned_to: '' }),
    },
})

// Flash Feedback Toast
const feedbackMsg = ref('')

// Filter State
const search = ref(props.filters?.search || '')
const currentStatus = ref(props.filters?.status || 'all')
const currentPriority = ref(props.filters?.priority || 'all')
const currentDepartment = ref(props.filters?.department || 'all')

const statusTabs = computed(() => [
    { key: 'all', label: 'ALL', count: props.stats.total_tickets || 0 },
    { key: 'open', label: 'OPEN', count: props.stats.open_count || 0 },
    { key: 'in_progress', label: 'IN PROGRESS', count: props.stats.in_progress_count || 0 },
    { key: 'answered', label: 'ANSWERED', count: props.stats.answered_count || 0 },
    { key: 'closed', label: 'CLOSED', count: props.stats.closed_count || 0 },
])

watch(() => props.filters, (newVal) => {
    currentStatus.value = newVal?.status || 'all'
    search.value = newVal?.search || ''
    currentPriority.value = newVal?.priority || 'all'
    currentDepartment.value = newVal?.department || 'all'
}, { deep: true })

const applyFilters = () => {
    router.get(route('admin.tickets.index'), {
        search: search.value || undefined,
        status: currentStatus.value !== 'all' ? currentStatus.value : undefined,
        priority: currentPriority.value !== 'all' ? currentPriority.value : undefined,
        department: currentDepartment.value !== 'all' ? currentDepartment.value : undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const selectStatus = (status) => {
    currentStatus.value = status
    applyFilters()
}

const resetFilters = () => {
    search.value = ''
    currentStatus.value = 'all'
    currentPriority.value = 'all'
    currentDepartment.value = 'all'
    applyFilters()
}

// 1. CREATE TICKET MODAL
const showModal = ref(false)
const ticketForm = useForm({
    user_id: '',
    subject: '',
    department: 'technical',
    priority: 'medium',
    message: '',
})

const openCreateModal = () => {
    ticketForm.reset()
    ticketForm.department = 'technical'
    ticketForm.priority = 'medium'
    showModal.value = true
}

const submitTicket = () => {
    ticketForm.post(route('admin.tickets.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showModal.value = false
            feedbackMsg.value = 'New support ticket opened.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 2. CLOSE TICKET
const closeTicket = (ticket) => {
    router.post(route('admin.tickets.close', ticket.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = `Ticket #${ticket.ticket_number || ticket.id} closed.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 3. DELETE TICKET
const showDeleteModal = ref(false)
const ticketToDelete = ref(null)
const deleteForm = useForm({})

const openDeleteModal = (t) => {
    ticketToDelete.value = t
    showDeleteModal.value = true
}

const submitDelete = () => {
    if (!ticketToDelete.value) return
    deleteForm.delete(route('admin.tickets.destroy', ticketToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false
            feedbackMsg.value = 'Support ticket deleted.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}
</script>

<template>
    <Head title="Support Tickets - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Support', href: '#' },
                    { label: 'Customer Support', href: route('admin.tickets.index') },
                    { label: 'Support Tickets & Helpdesk' }
                ]"
            >
                <template #actions>
                    <Link
                        :href="route('admin.support.canned-responses')"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <TagIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Canned Macros</span>
                    </Link>

                    <button 
                        type="button" 
                        @click="openCreateModal"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <PlusIcon class="w-3.5 h-3.5" />
                        <span>Open New Ticket</span>
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
                    title="Total Support Tickets"
                    :value="String(stats.total_tickets || tickets.data?.length || 0)"
                    badge="Tickets"
                    badgeType="info"
                    color="blue"
                    :icon="LifebuoyIcon"
                />

                <InfoCard
                    title="Open Unresolved"
                    :value="String(stats.open_count || tickets.data?.filter(t => t.status === 'open').length || 0)"
                    :badge="stats.open_count > 0 ? 'Pending Staff' : 'All Clear'"
                    :badgeType="stats.open_count > 0 ? 'warning' : 'success'"
                    color="emerald"
                    :icon="ChatBubbleLeftRightIcon"
                />

                <InfoCard
                    title="Critical Priority"
                    :value="String(stats.critical_count || 0)"
                    :badge="stats.critical_count > 0 ? 'Urgent' : 'Zero'"
                    :badgeType="stats.critical_count > 0 ? 'danger' : 'success'"
                    color="rose"
                    :icon="ExclamationTriangleIcon"
                />

                <InfoCard
                    title="In Progress"
                    :value="String(stats.in_progress_count || 0)"
                    badge="Active"
                    badgeType="info"
                    color="purple"
                    :icon="ClockIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Status Tabs -->
            <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        v-for="tab in statusTabs"
                        :key="tab.key"
                        type="button"
                        @click="selectStatus(tab.key)"
                        :class="currentStatus === tab.key ? 'bg-blue-600 text-white shadow-2xs' : 'bg-white text-slate-700 hover:bg-slate-50 border border-slate-200'"
                        class="px-3 py-1.5 rounded-[3px] font-bold text-xs font-mono transition cursor-pointer flex items-center gap-1.5"
                    >
                        <span>{{ tab.label }}</span>
                        <span 
                            class="px-1.5 py-0.2 rounded text-[10px] font-bold"
                            :class="currentStatus === tab.key ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600'"
                        >
                            {{ tab.count }}
                        </span>
                    </button>
                </div>
            </div>

            <!-- 4. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search ticket #, subject title, customer name, or email..."
                @search="applyFilters"
                @filter="applyFilters"
                @reset="resetFilters"
            >
                <FilterSelect
                    v-model="currentPriority"
                    label="Ticket Priority"
                    :options="[
                        { label: 'All Priorities', value: 'all' },
                        { label: 'Critical Urgency', value: 'critical' },
                        { label: 'High Priority', value: 'high' },
                        { label: 'Medium Priority', value: 'medium' },
                        { label: 'Low Priority', value: 'low' }
                    ]"
                    placeholder="All Priorities"
                />

                <FilterSelect
                    v-model="currentDepartment"
                    label="Department"
                    :options="[
                        { label: 'All Departments', value: 'all' },
                        { label: 'Technical Support', value: 'technical' },
                        { label: 'Billing & Accounts', value: 'billing' },
                        { label: 'Sales & Inquiries', value: 'sales' }
                    ]"
                    placeholder="All Departments"
                />
            </DataTableFilter>

            <!-- 5. Standard Data Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 w-28 text-left">Ticket Ref</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Subject Inquiry</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Customer / Client</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Department</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Priority</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Status</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Last Activity</th>
                                <th class="py-2.5 px-3 w-28">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(t, index) in tickets.data" :key="t.id || index" class="hover:bg-blue-50/30 transition">
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono font-bold text-blue-700">
                                    #{{ t.ticket_number || t.id }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-bold text-slate-900 max-w-sm truncate" :title="t.subject">
                                    <Link :href="route('admin.tickets.show', t.id)" class="hover:text-blue-600 hover:underline">
                                        {{ t.subject }}
                                    </Link>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-medium text-slate-800">
                                    <div>
                                        <span class="block leading-tight font-bold">{{ t.user?.name || t.client_name || 'Customer' }}</span>
                                        <span class="text-[10px] text-slate-400 font-mono block">{{ t.user?.email || t.client_email }}</span>
                                    </div>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase bg-slate-100 text-slate-700 border border-slate-200 font-mono">
                                        {{ t.department || 'Technical' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="[
                                            t.priority === 'critical' ? 'bg-rose-50 text-rose-700 border-rose-200' :
                                            t.priority === 'high' ? 'bg-amber-50 text-amber-700 border-amber-200' :
                                            t.priority === 'medium' ? 'bg-blue-50 text-blue-700 border-blue-200' :
                                            'bg-slate-100 text-slate-600 border-slate-200'
                                        ]"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ t.priority || 'medium' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="[
                                            t.status === 'open' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                                            t.status === 'in_progress' ? 'bg-purple-50 text-purple-700 border-purple-200' :
                                            t.status === 'answered' ? 'bg-blue-50 text-blue-700 border-blue-200' :
                                            'bg-slate-100 text-slate-500 border-slate-200'
                                        ]"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ t.status || 'open' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[10.5px] text-slate-500">
                                    {{ t.updated_at ? new Date(t.updated_at).toLocaleString() : 'Recent' }}
                                </td>
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1">
                                        <Link 
                                            :href="route('admin.tickets.show', t.id)"
                                            class="px-2 py-1 bg-white hover:bg-slate-50 text-blue-700 font-bold rounded-[3px] text-xs border border-slate-200 shadow-2xs transition cursor-pointer"
                                            title="View Conversation"
                                        >
                                            View 💬
                                        </Link>

                                        <RowActionDropdown>
                                            <button
                                                v-if="t.status !== 'closed'"
                                                type="button"
                                                @click="closeTicket(t)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <CheckIcon class="w-3.5 h-3.5 text-slate-500" />
                                                <span>Close Ticket</span>
                                            </button>

                                            <button
                                                type="button"
                                                @click="openDeleteModal(t)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-rose-600 hover:bg-rose-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <TrashIcon class="w-3.5 h-3.5" />
                                                <span>Delete Ticket</span>
                                            </button>
                                        </RowActionDropdown>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!tickets.data || tickets.data.length === 0">
                                <td colspan="9" class="py-12 text-center text-slate-400 font-sans">
                                    No support tickets found in helpdesk.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- CREATE TICKET MODAL -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <LifebuoyIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Open New Support Ticket
                        </h3>
                    </div>
                    <button @click="showModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitTicket" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Client / Customer <span class="text-rose-500">*</span></label>
                        <select v-model="ticketForm.user_id" required class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                            <option value="" disabled>Select Customer...</option>
                            <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }} ({{ c.email }})</option>
                        </select>
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Subject Title <span class="text-rose-500">*</span></label>
                        <input v-model="ticketForm.subject" type="text" required placeholder="Issue configuring SSL certificate" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Department</label>
                            <select v-model="ticketForm.department" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                                <option value="technical">Technical Support</option>
                                <option value="billing">Billing & Invoices</option>
                                <option value="sales">Sales & Upgrades</option>
                            </select>
                        </div>

                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Priority Urgency</label>
                            <select v-model="ticketForm.priority" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Initial Message Body <span class="text-rose-500">*</span></label>
                        <textarea v-model="ticketForm.message" rows="3" required placeholder="Describe the inquiry or support request in detail..." class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"></textarea>
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
                            :disabled="ticketForm.processing"
                            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ ticketForm.processing ? 'Opening...' : 'Open Ticket' }}
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
                            Delete Support Ticket
                        </h3>
                    </div>
                    <button @click="showDeleteModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-2.5 text-xs">
                    <p class="text-slate-600">
                        Are you sure you want to permanently remove ticket <strong class="text-slate-900 font-mono">[#{{ ticketToDelete?.ticket_number || ticketToDelete?.id }}]</strong>?
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
                        {{ deleteForm.processing ? 'Deleting...' : 'Delete Ticket' }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
