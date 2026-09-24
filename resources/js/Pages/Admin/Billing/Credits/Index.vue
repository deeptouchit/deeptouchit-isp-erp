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
    WalletIcon,
    ArrowTrendingUpIcon,
    ArrowTrendingDownIcon,
    UserIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    credits: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    stats: {
        type: Object,
        default: () => ({
            total_wallet_pool: 0,
            funded_clients_count: 0,
            total_clients_count: 0,
            total_added: 0,
            added_count: 0,
            total_deducted: 0,
            deducted_count: 0,
        }),
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
        default: () => ({ search: '', type: '', date_from: '', date_to: '' }),
    },
})

// Flash Feedback Toast
const feedbackMsg = ref('')

// Filter State
const search = ref(props.filters?.search || '')
const currentType = ref(props.filters?.type || 'all')
const dateFrom = ref(props.filters?.date_from || '')
const dateTo = ref(props.filters?.date_to || '')

const applyFilters = () => {
    router.get(route('admin.billing.credits'), {
        search: search.value || undefined,
        type: currentType.value !== 'all' ? currentType.value : undefined,
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
    dateFrom.value = ''
    dateTo.value = ''
    applyFilters()
}

const formatMoney = (amount) => {
    return '$' + Number(amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

// 1. ADJUST CREDIT MODAL
const showModal = ref(false)
const creditForm = useForm({
    user_id: '',
    type: 'add',
    amount: '',
    description: '',
})

const openAdjustModal = () => {
    creditForm.reset()
    creditForm.type = 'add'
    showModal.value = true
}

const submitCredit = () => {
    creditForm.post(route('admin.billing.credits.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showModal.value = false
            feedbackMsg.value = 'Client wallet credit updated.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}
</script>

<template>
    <Head title="Client Wallet Credits - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Billing', href: '#' },
                    { label: 'Billing & Finance', href: route('admin.billing.invoices') },
                    { label: 'Client Wallet Balances & Credits' }
                ]"
            >
                <template #actions>
                    <Link
                        :href="route('admin.billing.invoices')"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowLeftIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Invoices</span>
                    </Link>

                    <button 
                        type="button" 
                        @click="openAdjustModal"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <PlusIcon class="w-3.5 h-3.5" />
                        <span>Adjust Client Credit</span>
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
                    title="Total Wallet Pool"
                    :value="formatMoney(stats.total_wallet_pool)"
                    badge="Customer Funds"
                    badgeType="success"
                    color="emerald"
                    :icon="WalletIcon"
                />

                <InfoCard
                    title="Funded Clients"
                    :value="`${stats.funded_clients_count || 0} / ${stats.total_clients_count || clients.length || 0}`"
                    badge="Active Wallets"
                    badgeType="info"
                    color="blue"
                    :icon="UserIcon"
                />

                <InfoCard
                    title="Total Added Credits"
                    :value="formatMoney(stats.total_added)"
                    badge="Credits Deposited"
                    badgeType="info"
                    color="purple"
                    :icon="ArrowTrendingUpIcon"
                />

                <InfoCard
                    title="Total Deducted Credits"
                    :value="formatMoney(stats.total_deducted)"
                    badge="Applied to Invoices"
                    badgeType="warning"
                    color="rose"
                    :icon="ArrowTrendingDownIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Type Tabs -->
            <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        v-for="t in ['all', 'add', 'deduct', 'applied_to_invoice', 'refund']"
                        :key="t"
                        type="button"
                        @click="selectType(t)"
                        :class="currentType === t ? 'bg-blue-600 text-white' : 'bg-white text-slate-700 hover:bg-slate-50 border border-slate-200'"
                        class="px-3 py-1.5 rounded-[3px] font-bold text-xs uppercase font-mono transition cursor-pointer shadow-2xs"
                    >
                        {{ t.replace(/_/g, ' ') }}
                    </button>
                </div>
            </div>

            <!-- 4. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search customer name, email, or credit description..."
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
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Customer / Client</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Adjustment Type</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Description / Reason</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Amount Adjusted</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Balance After</th>
                                <th class="py-2.5 px-3">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(c, index) in credits.data" :key="c.id || index" class="hover:bg-blue-50/30 transition">
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-bold text-slate-900">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs border border-blue-100 shrink-0">
                                            <WalletIcon class="w-3.5 h-3.5" />
                                        </div>
                                        <div>
                                            <span class="block leading-tight">{{ c.user?.name || c.client_name || 'Customer' }}</span>
                                            <span class="text-[10px] text-slate-400 font-mono block">{{ c.user?.email || c.client_email }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="c.type === 'add' || c.type === 'refund' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ c.type }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-medium text-slate-800 max-w-sm truncate" :title="c.description">
                                    {{ c.description || 'Credit adjustment' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono font-bold text-[11px]" :class="c.type === 'add' || c.type === 'refund' ? 'text-emerald-700' : 'text-rose-700'">
                                    {{ c.type === 'add' || c.type === 'refund' ? '+' : '-' }}{{ formatMoney(c.amount) }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono font-bold text-slate-900 text-[11px]">
                                    {{ formatMoney(c.balance_after || c.new_balance || 0) }}
                                </td>
                                <td class="py-2.5 px-3 whitespace-nowrap text-center font-mono text-[10.5px] text-slate-500">
                                    {{ c.created_at ? new Date(c.created_at).toLocaleString() : 'Recent' }}
                                </td>
                            </tr>

                            <tr v-if="!credits.data || credits.data.length === 0">
                                <td colspan="7" class="py-12 text-center text-slate-400 font-sans">
                                    No wallet credit adjustment records found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ADJUST CREDIT MODAL -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <WalletIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Adjust Client Credit Balance
                        </h3>
                    </div>
                    <button @click="showModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitCredit" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Client / Customer <span class="text-rose-500">*</span></label>
                        <select v-model="creditForm.user_id" required class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                            <option value="" disabled>Select Customer...</option>
                            <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }} ({{ c.email }})</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Action Type</label>
                            <select v-model="creditForm.type" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                                <option value="add">Add Funds (+)</option>
                                <option value="deduct">Deduct Funds (-)</option>
                            </select>
                        </div>

                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Amount ($) <span class="text-rose-500">*</span></label>
                            <input v-model.number="creditForm.amount" type="number" step="0.01" min="0.01" required placeholder="50.00" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Reason / Description <span class="text-rose-500">*</span></label>
                        <input v-model="creditForm.description" type="text" required placeholder="Goodwill credit adjustment for service downtime" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
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
                            :disabled="creditForm.processing"
                            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ creditForm.processing ? 'Saving...' : 'Apply Credit' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
