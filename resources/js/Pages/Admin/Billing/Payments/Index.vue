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
    TrashIcon,
    ClockIcon,
    ArrowDownTrayIcon,
    DocumentDuplicateIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    payments: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    stats: {
        type: Object,
        default: () => ({
            total_settled_volume: 0,
            completed_count: 0,
            mfs_volume: 0,
            mfs_count: 0,
            card_volume: 0,
            card_count: 0,
            refunded_volume: 0,
            refunded_count: 0,
            total_transactions_count: 0,
        }),
    },
    gatewayStats: {
        type: Object,
        default: () => ({}),
    },
    clients: {
        type: Array,
        default: () => [],
    },
    unpaidInvoices: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({ search: '', status: '', gateway: '', date_from: '', date_to: '' }),
    },
})

// Flash Feedback Toast
const feedbackMsg = ref('')

// Filter State
const search = ref(props.filters?.search || '')
const currentStatus = ref(props.filters?.status || 'all')
const currentGateway = ref(props.filters?.gateway || 'all')
const dateFrom = ref(props.filters?.date_from || '')
const dateTo = ref(props.filters?.date_to || '')

const applyFilters = () => {
    router.get(route('admin.billing.payments'), {
        search: search.value || undefined,
        status: currentStatus.value !== 'all' ? currentStatus.value : undefined,
        gateway: currentGateway.value !== 'all' ? currentGateway.value : undefined,
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
    currentGateway.value = 'all'
    dateFrom.value = ''
    dateTo.value = ''
    applyFilters()
}

const formatMoney = (amount) => {
    return '$' + Number(amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

// 1. RECORD MANUAL PAYMENT MODAL
const showModal = ref(false)
const paymentForm = useForm({
    user_id: '',
    invoice_id: '',
    amount: '',
    gateway: 'bank_transfer',
    transaction_id: '',
    notes: '',
})

const openRecordModal = () => {
    paymentForm.reset()
    paymentForm.gateway = 'bank_transfer'
    showModal.value = true
}

const submitPayment = () => {
    paymentForm.post(route('admin.billing.payments.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showModal.value = false
            feedbackMsg.value = 'Payment transaction recorded.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 2. REFUND MODAL
const refundPayment = (payment) => {
    if (confirm(`Refund payment transaction #${payment.transaction_id || payment.id} of ${formatMoney(payment.amount)}?`)) {
        router.post(route('admin.billing.payments.refund', payment.id), {}, {
            preserveScroll: true,
            onSuccess: () => {
                feedbackMsg.value = 'Payment refund initiated.'
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    }
}

const copyTrx = (trxId) => {
    navigator.clipboard.writeText(trxId)
    feedbackMsg.value = `Transaction ID '${trxId}' copied.`
    setTimeout(() => { feedbackMsg.value = '' }, 2500)
}
</script>

<template>
    <Head title="Payment Transactions - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Billing', href: '#' },
                    { label: 'Billing & Finance', href: route('admin.billing.invoices') },
                    { label: 'Client Payments & Transactions' }
                ]"
            >
                <template #actions>
                    <Link
                        :href="route('admin.billing.invoices')"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <DocumentTextIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Invoices</span>
                    </Link>

                    <button 
                        type="button" 
                        @click="openRecordModal"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <PlusIcon class="w-3.5 h-3.5" />
                        <span>Record Payment</span>
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
                    title="Settled Payment Volume"
                    :value="formatMoney(stats.total_settled_volume)"
                    badge="Settled"
                    badgeType="success"
                    color="blue"
                    :icon="BanknotesIcon"
                />

                <InfoCard
                    title="Successful Payments"
                    :value="String(stats.completed_count || payments.data?.length || 0)"
                    badge="Transactions"
                    badgeType="info"
                    color="emerald"
                    :icon="CheckIcon"
                />

                <InfoCard
                    title="Card / Stripe Volume"
                    :value="formatMoney(stats.card_volume || 0)"
                    badge="Credit Card"
                    badgeType="info"
                    color="purple"
                    :icon="CreditCardIcon"
                />

                <InfoCard
                    title="Refunds & Chargebacks"
                    :value="formatMoney(stats.refunded_volume || 0)"
                    :badge="stats.refunded_volume > 0 ? 'Refunded' : 'Zero'"
                    :badgeType="stats.refunded_volume > 0 ? 'warning' : 'success'"
                    color="rose"
                    :icon="ExclamationTriangleIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Status Tabs -->
            <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        v-for="st in ['all', 'completed', 'pending', 'refunded', 'failed']"
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
                searchPlaceholder="Search transaction ID, customer, invoice #, or gateway..."
                @search="applyFilters"
                @filter="applyFilters"
                @reset="resetFilters"
            >
                <FilterSelect
                    v-model="currentGateway"
                    label="Payment Gateway"
                    :options="[
                        { label: 'All Gateways', value: 'all' },
                        { label: 'Stripe Card', value: 'stripe' },
                        { label: 'PayPal Checkout', value: 'paypal' },
                        { label: 'bKash MFS', value: 'bkash' },
                        { label: 'Nagad MFS', value: 'nagad' },
                        { label: 'Bank Wire Transfer', value: 'bank_transfer' },
                        { label: 'Manual Admin Record', value: 'manual' }
                    ]"
                    placeholder="All Gateways"
                />
            </DataTableFilter>

            <!-- 5. Standard Data Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left w-36">Transaction ID</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Customer / Client</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Invoice Ref</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Gateway</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Amount Paid</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Payment Date</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Status</th>
                                <th class="py-2.5 px-3 w-28">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(p, index) in payments.data" :key="p.id || index" class="hover:bg-blue-50/30 transition">
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono font-bold text-blue-700">
                                    <div class="flex items-center gap-1.5">
                                        <span>{{ p.transaction_id || 'TRX-' + p.id }}</span>
                                        <button type="button" @click="copyTrx(p.transaction_id || p.id)" class="text-slate-400 hover:text-slate-600 cursor-pointer" title="Copy ID">
                                            <DocumentDuplicateIcon class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-bold text-slate-900">
                                    <div>
                                        <span class="block leading-tight">{{ p.user?.name || p.client_name || 'Customer' }}</span>
                                        <span class="text-[10px] text-slate-400 font-mono block">{{ p.user?.email || p.client_email }}</span>
                                    </div>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono text-[11px] text-slate-600">
                                    {{ p.invoice?.invoice_number || (p.invoice_id ? 'INV-' + p.invoice_id : '-') }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase bg-slate-100 text-slate-700 border border-slate-200 font-mono">
                                        {{ p.gateway || 'Card' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono font-bold text-emerald-700 text-[11px]">
                                    {{ formatMoney(p.amount) }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[10.5px] text-slate-500">
                                    {{ p.created_at ? new Date(p.created_at).toLocaleString() : 'Recent' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="[
                                            p.status === 'completed' || p.status === 'paid' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                                            p.status === 'pending' ? 'bg-amber-50 text-amber-700 border-amber-200' :
                                            p.status === 'refunded' ? 'bg-purple-50 text-purple-700 border-purple-200' :
                                            'bg-rose-50 text-rose-700 border-rose-200'
                                        ]"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ p.status || 'completed' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1">
                                        <button 
                                            v-if="p.status === 'completed' || p.status === 'paid'"
                                            type="button" 
                                            @click="refundPayment(p)"
                                            class="px-2 py-1 bg-white hover:bg-rose-50 text-rose-700 font-bold rounded-[3px] text-xs border border-rose-200 shadow-2xs transition cursor-pointer"
                                            title="Refund"
                                        >
                                            Refund 🔄
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!payments.data || payments.data.length === 0">
                                <td colspan="9" class="py-12 text-center text-slate-400 font-sans">
                                    No payment transactions found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- RECORD PAYMENT MODAL -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <BanknotesIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Record Payment Transaction
                        </h3>
                    </div>
                    <button @click="showModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitPayment" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Client / Customer <span class="text-rose-500">*</span></label>
                        <select v-model="paymentForm.user_id" required class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                            <option value="" disabled>Select Customer...</option>
                            <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }} ({{ c.email }})</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Amount Paid ($) <span class="text-rose-500">*</span></label>
                            <input v-model.number="paymentForm.amount" type="number" step="0.01" min="0.01" required placeholder="50.00" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        </div>

                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Payment Gateway</label>
                            <select v-model="paymentForm.gateway" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                                <option value="bank_transfer">Bank Wire</option>
                                <option value="stripe">Stripe Card</option>
                                <option value="paypal">PayPal</option>
                                <option value="bkash">bKash</option>
                                <option value="nagad">Nagad</option>
                                <option value="manual">Cash / Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Transaction Ref ID</label>
                        <input v-model="paymentForm.transaction_id" type="text" placeholder="TXN-998273" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Notes (Optional)</label>
                        <textarea v-model="paymentForm.notes" rows="2" placeholder="Bank reference number..." class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"></textarea>
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
                            :disabled="paymentForm.processing"
                            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ paymentForm.processing ? 'Saving...' : 'Record Payment' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
