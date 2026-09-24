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
    DocumentTextIcon,
    BanknotesIcon,
    CreditCardIcon,
    ExclamationTriangleIcon,
    PaperAirplaneIcon,
    TrashIcon,
    ClockIcon,
    ArrowDownTrayIcon,
    PencilSquareIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    invoices: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    stats: {
        type: Object,
        default: () => ({
            total_invoiced: 0,
            total_paid: 0,
            total_due: 0,
            overdue_count: 0,
            total_invoices_count: 0,
            paid_invoices_count: 0,
        }),
    },
    clients: {
        type: Array,
        default: () => [],
    },
    subscriptions: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({ search: '', status: '', date_from: '', date_to: '' }),
    },
})

// Flash Feedback Toast
const feedbackMsg = ref('')

// Filter State
const search = ref(props.filters?.search || '')
const currentStatus = ref(props.filters?.status || 'all')
const dateFrom = ref(props.filters?.date_from || '')
const dateTo = ref(props.filters?.date_to || '')

const applyFilters = () => {
    router.get(route('admin.billing.invoices'), {
        search: search.value || undefined,
        status: currentStatus.value !== 'all' ? currentStatus.value : undefined,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
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
    dateFrom.value = ''
    dateTo.value = ''
    applyFilters()
}

const formatMoney = (amount) => {
    return '$' + Number(amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

// 1. CREATE / EDIT INVOICE MODAL
const showModal = ref(false)
const editingInvoice = ref(null)
const invoiceForm = useForm({
    user_id: '',
    subscription_id: '',
    due_date: new Date(Date.now() + 7 * 86400000).toISOString().split('T')[0],
    currency: 'USD',
    notes: '',
    items: [
        { description: 'Cloud VPS Hosting - Monthly Subscription', quantity: 1, unit_price: 15.00 }
    ]
})

const openCreateModal = () => {
    editingInvoice.value = null
    invoiceForm.reset()
    invoiceForm.due_date = new Date(Date.now() + 7 * 86400000).toISOString().split('T')[0]
    invoiceForm.currency = 'USD'
    invoiceForm.items = [
        { description: 'Cloud VPS Hosting - Monthly Subscription', quantity: 1, unit_price: 15.00 }
    ]
    showModal.value = true
}

const openEditModal = (inv) => {
    editingInvoice.value = inv
    invoiceForm.user_id = inv.user_id || inv.client_id || ''
    invoiceForm.subscription_id = inv.subscription_id || ''
    invoiceForm.due_date = inv.due_date ? inv.due_date.split('T')[0] : ''
    invoiceForm.currency = inv.currency || 'USD'
    invoiceForm.notes = inv.notes || ''
    invoiceForm.items = inv.items?.length ? inv.items : [
        { description: inv.description || 'Web Hosting Services', quantity: 1, unit_price: inv.total || 0 }
    ]
    showModal.value = true
}

const addItem = () => {
    invoiceForm.items.push({ description: '', quantity: 1, unit_price: 0.00 })
}

const removeItem = (index) => {
    if (invoiceForm.items.length > 1) {
        invoiceForm.items.splice(index, 1)
    }
}

const computedSubtotal = computed(() => {
    return invoiceForm.items.reduce((sum, item) => sum + (Number(item.quantity || 0) * Number(item.unit_price || 0)), 0)
})

const submitInvoice = () => {
    if (editingInvoice.value) {
        invoiceForm.put(route('admin.billing.invoices.update', editingInvoice.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false
                feedbackMsg.value = 'Invoice updated successfully.'
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    } else {
        invoiceForm.post(route('admin.billing.invoices.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false
                feedbackMsg.value = 'New client invoice created.'
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    }
}

// 2. MARK AS PAID
const markPaid = (invoice) => {
    router.post(route('admin.billing.invoices.mark-paid', invoice.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = `Invoice #${invoice.invoice_number || invoice.id} marked as Paid.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 3. SEND REMINDER
const sendReminder = (invoice) => {
    router.post(route('admin.billing.invoices.send-reminder', invoice.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = `Payment reminder emailed to customer.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 4. DELETE MODAL
const showDeleteModal = ref(false)
const invoiceToDelete = ref(null)
const deleteForm = useForm({})

const openDeleteModal = (inv) => {
    invoiceToDelete.value = inv
    showDeleteModal.value = true
}

const submitDelete = () => {
    if (!invoiceToDelete.value) return
    deleteForm.delete(route('admin.billing.invoices.destroy', invoiceToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false
            feedbackMsg.value = 'Invoice deleted.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}
</script>

<template>
    <Head title="Client Invoices - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Billing', href: '#' },
                    { label: 'Billing & Finance', href: route('admin.billing.invoices') },
                    { label: 'Client Invoices & Statements' }
                ]"
            >
                <template #actions>
                    <Link
                        :href="route('admin.billing.payments')"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <CreditCardIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Payments</span>
                    </Link>

                    <button 
                        type="button" 
                        @click="openCreateModal"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <PlusIcon class="w-3.5 h-3.5" />
                        <span>Create Invoice</span>
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
                    title="Total Invoiced Volume"
                    :value="formatMoney(stats.total_invoiced)"
                    badge="Gross Billed"
                    badgeType="info"
                    color="blue"
                    :icon="DocumentTextIcon"
                />

                <InfoCard
                    title="Revenue Collected"
                    :value="formatMoney(stats.total_paid)"
                    badge="Paid"
                    badgeType="success"
                    color="emerald"
                    :icon="BanknotesIcon"
                />

                <InfoCard
                    title="Outstanding Due"
                    :value="formatMoney(stats.total_due)"
                    badge="Receivable"
                    badgeType="warning"
                    color="purple"
                    :icon="CreditCardIcon"
                />

                <InfoCard
                    title="Overdue Invoices"
                    :value="String(stats.overdue_count || 0)"
                    :badge="stats.overdue_count > 0 ? 'Overdue' : 'Clean'"
                    :badgeType="stats.overdue_count > 0 ? 'danger' : 'success'"
                    color="rose"
                    :icon="ExclamationTriangleIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Status Tabs -->
            <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        v-for="st in ['all', 'paid', 'unpaid', 'overdue', 'cancelled']"
                        :key="st"
                        type="button"
                        @click="selectStatus(st)"
                        :class="currentStatus === st ? 'bg-blue-600 text-white' : 'bg-white text-slate-700 hover:bg-slate-50 border border-slate-200'"
                        class="px-3 py-1.5 rounded-[3px] font-bold text-xs uppercase font-mono transition cursor-pointer shadow-2xs"
                    >
                        {{ st }}
                    </button>
                </div>
            </div>

            <!-- 4. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search invoice #, customer name, email, or domain..."
                @search="applyFilters"
                @filter="applyFilters"
                @reset="resetFilters"
            />

            <!-- 5. Standard Data Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left w-32">Invoice Number</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Customer / Client</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Service Domain</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Total Amount</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Invoice Date</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Due Date</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Status</th>
                                <th class="py-2.5 px-3 w-28">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(inv, index) in invoices.data" :key="inv.id || index" class="hover:bg-blue-50/30 transition">
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono font-bold text-blue-700">
                                    <div class="flex items-center gap-1.5">
                                        <DocumentTextIcon class="w-3.5 h-3.5 text-blue-600" />
                                        <span>{{ inv.invoice_number || 'INV-' + inv.id }}</span>
                                    </div>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-bold text-slate-900">
                                    <div>
                                        <span class="block leading-tight">{{ inv.user?.name || inv.client_name || 'Customer' }}</span>
                                        <span class="text-[10px] text-slate-400 font-mono block">{{ inv.user?.email || inv.client_email }}</span>
                                    </div>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono text-[11px] text-slate-600">
                                    {{ inv.subscription?.domain || '-' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono font-bold text-slate-900 text-[11px]">
                                    {{ formatMoney(inv.total || inv.amount) }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[10.5px] text-slate-500">
                                    {{ inv.created_at ? new Date(inv.created_at).toLocaleDateString() : 'Recent' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[10.5px] text-slate-600 font-bold">
                                    {{ inv.due_date ? new Date(inv.due_date).toLocaleDateString() : '-' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="[
                                            inv.status === 'paid' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                                            inv.status === 'unpaid' ? 'bg-amber-50 text-amber-700 border-amber-200' :
                                            inv.status === 'overdue' ? 'bg-rose-50 text-rose-700 border-rose-200' :
                                            'bg-slate-100 text-slate-500 border-slate-200'
                                        ]"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ inv.status || 'unpaid' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1">
                                        <button 
                                            v-if="inv.status !== 'paid'"
                                            type="button" 
                                            @click="markPaid(inv)"
                                            class="px-2 py-1 bg-white hover:bg-emerald-50 text-emerald-700 font-bold rounded-[3px] text-xs border border-emerald-200 shadow-2xs transition cursor-pointer"
                                            title="Mark Paid"
                                        >
                                            Paid 💳
                                        </button>

                                        <RowActionDropdown>
                                            <a
                                                :href="route('admin.billing.invoices.pdf', inv.id)"
                                                target="_blank"
                                                class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <ArrowDownTrayIcon class="w-3.5 h-3.5 text-slate-500" />
                                                <span>Download PDF</span>
                                            </a>

                                            <button
                                                v-if="inv.status !== 'paid'"
                                                type="button"
                                                @click="sendReminder(inv)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <PaperAirplaneIcon class="w-3.5 h-3.5 text-slate-500" />
                                                <span>Send Reminder</span>
                                            </button>

                                            <button
                                                type="button"
                                                @click="openEditModal(inv)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <PencilSquareIcon class="w-3.5 h-3.5 text-slate-500" />
                                                <span>Edit Invoice</span>
                                            </button>

                                            <button
                                                type="button"
                                                @click="openDeleteModal(inv)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-rose-600 hover:bg-rose-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <TrashIcon class="w-3.5 h-3.5" />
                                                <span>Delete Invoice</span>
                                            </button>
                                        </RowActionDropdown>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!invoices.data || invoices.data.length === 0">
                                <td colspan="9" class="py-12 text-center text-slate-400 font-sans">
                                    No invoices found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- CREATE / EDIT INVOICE MODAL -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-xl overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <DocumentTextIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            {{ editingInvoice ? 'Edit Invoice' : 'Create Client Invoice' }}
                        </h3>
                    </div>
                    <button @click="showModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitInvoice" class="p-4 space-y-3.5 text-xs max-h-[80vh] overflow-y-auto">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Client / Customer <span class="text-rose-500">*</span></label>
                            <select v-model="invoiceForm.user_id" required class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                                <option value="" disabled>Select Client...</option>
                                <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }} ({{ c.email }})</option>
                            </select>
                        </div>

                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Due Date</label>
                            <input v-model="invoiceForm.due_date" type="date" required class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        </div>
                    </div>

                    <!-- Line Items -->
                    <div class="space-y-2 border-t border-slate-100 pt-3">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-slate-900 uppercase text-[11px] tracking-wide">Invoice Line Items</span>
                            <button type="button" @click="addItem" class="text-blue-600 hover:underline font-bold text-xs cursor-pointer">+ Add Line Item</button>
                        </div>

                        <div v-for="(item, idx) in invoiceForm.items" :key="idx" class="flex items-center gap-2 bg-slate-50 p-2 rounded-[3px] border border-slate-200">
                            <input v-model="item.description" type="text" required placeholder="Item description" class="flex-1 px-2.5 py-1 text-xs rounded-[2px] border border-slate-200 bg-white text-slate-900 focus:outline-none focus:ring-1 focus:ring-blue-500" />
                            <input v-model.number="item.quantity" type="number" min="1" placeholder="Qty" class="w-14 px-2 py-1 text-xs rounded-[2px] border border-slate-200 bg-white text-slate-900 text-center font-mono focus:outline-none focus:ring-1 focus:ring-blue-500" />
                            <input v-model.number="item.unit_price" type="number" step="0.01" min="0" placeholder="Price" class="w-24 px-2 py-1 text-xs rounded-[2px] border border-slate-200 bg-white text-slate-900 text-right font-mono focus:outline-none focus:ring-1 focus:ring-blue-500" />
                            <button type="button" @click="removeItem(idx)" class="text-slate-400 hover:text-rose-600 p-1 cursor-pointer">✕</button>
                        </div>

                        <div class="flex justify-end pt-1 font-bold text-xs text-slate-900">
                            <span>Subtotal: {{ formatMoney(computedSubtotal) }}</span>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Invoice Notes (Optional)</label>
                        <textarea v-model="invoiceForm.notes" rows="2" placeholder="Payment terms or instructions..." class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"></textarea>
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
                            :disabled="invoiceForm.processing"
                            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ invoiceForm.processing ? 'Saving...' : (editingInvoice ? 'Update Invoice' : 'Create Invoice') }}
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
                            Delete Invoice
                        </h3>
                    </div>
                    <button @click="showDeleteModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-2.5 text-xs">
                    <p class="text-slate-600">
                        Are you sure you want to permanently delete invoice <strong class="text-slate-900 font-mono">[{{ invoiceToDelete?.invoice_number || 'INV-' + invoiceToDelete?.id }}]</strong>?
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
                        {{ deleteForm.processing ? 'Deleting...' : 'Delete Invoice' }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
