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

import {
    PlusIcon,
    ArrowPathIcon,
    CheckIcon,
    XMarkIcon,
    BanknotesIcon,
    CreditCardIcon,
    DocumentDuplicateIcon,
    ScaleIcon,
    ArrowTrendingUpIcon,
    ArrowTrendingDownIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    transactions: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    stats: {
        type: Object,
        default: () => ({
            net_inflow: 0,
            total_credits: 0,
            credit_count: 0,
            total_debits: 0,
            debit_count: 0,
            adjustment_volume: 0,
            adjustment_count: 0,
            total_transactions_count: 0,
        }),
    },
    clients: {
        type: Array,
        default: () => [],
    },
    invoices: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({ search: '', type: '', category: '', status: '', date_from: '', date_to: '' }),
    },
})

// Flash Feedback Toast
const feedbackMsg = ref('')

// Filter State
const search = ref(props.filters?.search || '')
const currentType = ref(props.filters?.type || 'all')
const currentCategory = ref(props.filters?.category || 'all')
const dateFrom = ref(props.filters?.date_from || '')
const dateTo = ref(props.filters?.date_to || '')

const applyFilters = () => {
    router.get(route('admin.billing.transactions'), {
        search: search.value || undefined,
        type: currentType.value !== 'all' ? currentType.value : undefined,
        category: currentCategory.value !== 'all' ? currentCategory.value : undefined,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const selectType = (type) => {
    currentType.value = type
    applyFilters()
}

const resetFilters = () => {
    search.value = ''
    currentType.value = 'all'
    currentCategory.value = 'all'
    dateFrom.value = ''
    dateTo.value = ''
    applyFilters()
}

const formatMoney = (amount) => {
    return '$' + Number(amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

// 1. POST ENTRY MODAL
const showModal = ref(false)
const trxForm = useForm({
    user_id: '',
    type: 'credit',
    category: 'hosting',
    amount: '',
    description: '',
})

const openPostModal = () => {
    trxForm.reset()
    trxForm.type = 'credit'
    trxForm.category = 'hosting'
    showModal.value = true
}

const submitTrx = () => {
    trxForm.post(route('admin.billing.transactions.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showModal.value = false
            feedbackMsg.value = 'Ledger transaction recorded.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

const copyTrx = (trxId) => {
    navigator.clipboard.writeText(trxId)
    feedbackMsg.value = `Transaction ID '${trxId}' copied.`
    setTimeout(() => { feedbackMsg.value = '' }, 2500)
}
</script>

<template>
    <Head title="Financial Transactions - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Billing', href: '#' },
                    { label: 'Billing & Finance', href: route('admin.billing.invoices') },
                    { label: 'Financial Ledger & Cashflow' }
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
                        @click="openPostModal"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <PlusIcon class="w-3.5 h-3.5" />
                        <span>Post Ledger Entry</span>
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
                    title="Net Cashflow Inflow"
                    :value="formatMoney(stats.net_inflow)"
                    badge="Net Margin"
                    badgeType="success"
                    color="emerald"
                    :icon="ScaleIcon"
                />

                <InfoCard
                    title="Credit Inflows"
                    :value="formatMoney(stats.total_credits)"
                    badge="Gross Inflows"
                    badgeType="info"
                    color="blue"
                    :icon="ArrowTrendingUpIcon"
                />

                <InfoCard
                    title="Debit Outflows"
                    :value="formatMoney(stats.total_debits)"
                    badge="Refunds/Charges"
                    badgeType="warning"
                    color="purple"
                    :icon="ArrowTrendingDownIcon"
                />

                <InfoCard
                    title="Adjustments / Credits"
                    :value="formatMoney(stats.adjustment_volume || 0)"
                    badge="Discounts"
                    badgeType="info"
                    color="sky"
                    :icon="BanknotesIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Type Tabs -->
            <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        v-for="t in ['all', 'credit', 'debit', 'adjustment']"
                        :key="t"
                        type="button"
                        @click="selectType(t)"
                        :class="currentType === t ? 'bg-blue-600 text-white' : 'bg-white text-slate-700 hover:bg-slate-50 border border-slate-200'"
                        class="px-3 py-1.5 rounded-[3px] font-bold text-xs uppercase font-mono transition cursor-pointer shadow-2xs"
                    >
                        {{ t }}
                    </button>
                </div>
            </div>

            <!-- 4. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search transaction ID, customer name, description, or reference..."
                @search="applyFilters"
                @filter="applyFilters"
                @reset="resetFilters"
            >
                <FilterSelect
                    v-model="currentCategory"
                    label="Transaction Category"
                    :options="[
                        { label: 'All Categories', value: 'all' },
                        { label: 'Hosting Subscription', value: 'hosting' },
                        { label: 'Domain Registration', value: 'domain' },
                        { label: 'Server Upgrade / Addon', value: 'upgrade' },
                        { label: 'Refund / Reversal', value: 'refund' },
                        { label: 'Account Credit', value: 'credit' }
                    ]"
                    placeholder="All Categories"
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
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Customer / Entity</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Ledger Type</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Category / Description</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Amount</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Post Date</th>
                                <th class="py-2.5 px-3 w-16">Copy</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(t, index) in transactions.data" :key="t.id || index" class="hover:bg-blue-50/30 transition">
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono font-bold text-blue-700">
                                    {{ t.transaction_id || 'TRX-' + t.id }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-bold text-slate-900">
                                    <div>
                                        <span class="block leading-tight">{{ t.user?.name || t.client_name || 'Customer' }}</span>
                                        <span class="text-[10px] text-slate-400 font-mono block">{{ t.user?.email || t.client_email }}</span>
                                    </div>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="t.type === 'credit' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ t.type }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-medium text-slate-800 max-w-xs truncate" :title="t.description">
                                    {{ t.description || 'Service transaction' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono font-bold text-[11px]" :class="t.type === 'credit' ? 'text-emerald-700' : 'text-rose-700'">
                                    {{ t.type === 'credit' ? '+' : '-' }}{{ formatMoney(t.amount) }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[10.5px] text-slate-500">
                                    {{ t.created_at ? new Date(t.created_at).toLocaleString() : 'Recent' }}
                                </td>
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <button 
                                        type="button" 
                                        @click="copyTrx(t.transaction_id || t.id)"
                                        class="p-1 hover:bg-slate-100 text-slate-500 hover:text-slate-900 rounded-[2px] cursor-pointer"
                                        title="Copy Transaction ID"
                                    >
                                        <DocumentDuplicateIcon class="w-3.5 h-3.5" />
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="!transactions.data || transactions.data.length === 0">
                                <td colspan="8" class="py-12 text-center text-slate-400 font-sans">
                                    No financial ledger transactions found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- POST ENTRY MODAL -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <ScaleIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Post Ledger Transaction Entry
                        </h3>
                    </div>
                    <button @click="showModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitTrx" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Client / Customer <span class="text-rose-500">*</span></label>
                        <select v-model="trxForm.user_id" required class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                            <option value="" disabled>Select Customer...</option>
                            <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }} ({{ c.email }})</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Transaction Type</label>
                            <select v-model="trxForm.type" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                                <option value="credit">Credit (Inflow +)</option>
                                <option value="debit">Debit (Outflow -)</option>
                            </select>
                        </div>

                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Amount ($) <span class="text-rose-500">*</span></label>
                            <input v-model.number="trxForm.amount" type="number" step="0.01" min="0.01" required placeholder="25.00" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Category</label>
                        <select v-model="trxForm.category" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                            <option value="hosting">Hosting Subscription</option>
                            <option value="domain">Domain Registration</option>
                            <option value="upgrade">Server Upgrade / Addon</option>
                            <option value="refund">Refund / Reversal</option>
                            <option value="credit">Account Credit</option>
                        </select>
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Description / Memo <span class="text-rose-500">*</span></label>
                        <input v-model="trxForm.description" type="text" required placeholder="Monthly Dedicated IP Allocation" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
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
                            :disabled="trxForm.processing"
                            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ trxForm.processing ? 'Posting...' : 'Post Entry' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
