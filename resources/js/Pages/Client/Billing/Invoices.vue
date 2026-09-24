<script setup>
import { ref, watch } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import { 
    CreditCardIcon, 
    BanknotesIcon, 
    ArrowRightIcon, 
    ShieldCheckIcon,
    MagnifyingGlassIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    ClipboardDocumentIcon,
    CheckIcon,
    XMarkIcon,
    ArrowTopRightOnSquareIcon,
    CalendarIcon,
    DocumentTextIcon,
    ClockIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    invoices: {
        type: Object,
        default: () => ({ data: [], links: [] })
    },
    stats: {
        type: Object,
        default: () => ({
            total_due: 0,
            total_paid: 0,
            unpaid_count: 0,
            paid_count: 0,
            total_count: 0
        })
    },
    filters: {
        type: Object,
        default: () => ({
            status: 'all',
            search: ''
        })
    },
    activeGateways: {
        type: Array,
        default: () => []
    }
})

// Filter state
const currentStatus = ref(props.filters.status || 'all')
const searchQuery = ref(props.filters.search || '')

// Apply Filter
const applyFilter = (status) => {
    currentStatus.value = status
    router.get(route('billing.invoices'), {
        status: status === 'all' ? null : status,
        search: searchQuery.value || null
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    })
}

let searchTimer = null
const onSearchInput = () => {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(() => {
        router.get(route('billing.invoices'), {
            status: currentStatus.value === 'all' ? null : currentStatus.value,
            search: searchQuery.value || null
        }, {
            preserveState: true,
            preserveScroll: true,
            replace: true
        })
    }, 350)
}

// Copy Feedback
const copiedField = ref(null)
const copyToClipboard = (text, fieldName) => {
    navigator.clipboard.writeText(text)
    copiedField.value = fieldName
    setTimeout(() => {
        copiedField.value = null
    }, 2000)
}

// Quick Pay Modal State
const showPayModal = ref(false)
const selectedInvoice = ref(null)
const selectedGateway = ref('bkash')

const openPayModal = (inv) => {
    selectedInvoice.value = inv
    if (props.activeGateways.length > 0) {
        selectedGateway.value = props.activeGateways[0].slug
    }
    showPayModal.value = true
}

const payForm = useForm({
    gateway: 'bkash'
})

const submitQuickPayment = () => {
    if (!selectedInvoice.value) return
    payForm.gateway = selectedGateway.value
    payForm.post(route('billing.pay', selectedInvoice.value.id))
}

const formatCurrency = (amount) => {
    return Number(amount || 0).toLocaleString('en-BD', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    })
}

const formatDate = (dateStr) => {
    if (!dateStr) return 'N/A'
    if (typeof dateStr === 'string' && dateStr.length <= 12 && dateStr.includes(',')) {
        return dateStr
    }
    try {
        const d = new Date(dateStr)
        if (isNaN(d.getTime())) return dateStr
        return d.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' })
    } catch {
        return String(dateStr).substring(0, 10)
    }
}
</script>

<template>
    <Head title="Billing & Invoices - DeepTouch Host" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto space-y-4">
            <!-- 1. Smart Minimal Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Billing & Invoices', href: '#' },
                    { label: 'Invoices & Payments' }
                ]"
            />

            <!-- ========================================================= -->
            <!-- 1. TOP FINANCIAL METRICS CARDS (3-COLUMN GRID)            -->
            <!-- ========================================================= -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                
                <!-- Card 1: Total Outstanding Balance -->
                <div class="bg-white border border-slate-200 rounded-lg p-5 shadow-xs flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Outstanding Balance</span>
                            <span 
                                class="text-[11px] font-bold px-2 py-0.5 rounded border"
                                :class="stats.total_due > 0 ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200'"
                            >
                                {{ stats.unpaid_count }} Pending Due
                            </span>
                        </div>
                        <div class="mt-2.5 flex items-baseline gap-2">
                            <span class="text-2xl font-black text-slate-900">৳{{ formatCurrency(stats.total_due) }}</span>
                            <span class="text-xs font-bold text-slate-400">Total Unpaid</span>
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                        <span v-if="stats.total_due > 0" class="text-amber-600 font-bold flex items-center gap-1">
                            <ExclamationTriangleIcon class="w-3.5 h-3.5" />
                            <span>Action Required: Please clear dues</span>
                        </span>
                        <span v-else class="text-emerald-600 font-bold flex items-center gap-1">
                            <CheckCircleIcon class="w-3.5 h-3.5" />
                            <span>All invoices fully settled</span>
                        </span>
                    </div>
                </div>

                <!-- Card 2: Total Settled / Paid -->
                <div class="bg-white border border-slate-200 rounded-lg p-5 shadow-xs flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Paid (Lifetime)</span>
                            <span class="text-[11px] font-bold px-2 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded">
                                {{ stats.paid_count }} Paid
                            </span>
                        </div>
                        <div class="mt-2.5 flex items-baseline gap-2">
                            <span class="text-2xl font-black text-emerald-600">৳{{ formatCurrency(stats.total_paid) }}</span>
                            <span class="text-xs font-bold text-slate-400">Successfully Settled</span>
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                        <span>Verified Transactions</span>
                        <span class="font-bold text-slate-700">100% Secured</span>
                    </div>
                </div>

                <!-- Card 3: Accepted Payment Channels -->
                <div class="bg-gradient-to-br from-blue-50/70 to-indigo-50/50 border border-blue-100 rounded-lg p-5 shadow-xs flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-blue-700">Online Checkout</span>
                            <span class="text-[10px] font-bold px-2 py-0.5 bg-blue-100 text-blue-800 rounded">
                                Instant Verification
                            </span>
                        </div>
                        <div class="flex flex-wrap items-center gap-1.5 mt-2.5">
                            <span class="px-2 py-0.5 bg-white text-pink-600 font-black text-xs border border-pink-100 rounded shadow-2xs">bKash</span>
                            <span class="px-2 py-0.5 bg-white text-orange-600 font-black text-xs border border-orange-100 rounded shadow-2xs">Nagad</span>
                            <span class="px-2 py-0.5 bg-white text-blue-600 font-black text-xs border border-blue-100 rounded shadow-2xs">SSLCommerz</span>
                            <span class="px-2 py-0.5 bg-white text-indigo-600 font-black text-xs border border-indigo-100 rounded shadow-2xs">Stripe</span>
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-t border-blue-100 flex items-center justify-between text-xs text-slate-500">
                        <span class="flex items-center gap-1 text-[11px]">
                            <ShieldCheckIcon class="w-3.5 h-3.5 text-emerald-600" />
                            <span>256-Bit SSL Encrypted</span>
                        </span>
                        <span class="font-bold text-slate-700">Automated</span>
                    </div>
                </div>

            </div>

            <!-- ========================================================= -->
            <!-- 2. INVOICE TABLE & FILTERS                                -->
            <!-- ========================================================= -->
            <div class="bg-white border border-slate-200 rounded-lg shadow-xs overflow-hidden">
                <div class="p-4 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/50">
                    
                    <!-- Status Tabs -->
                    <div class="flex items-center gap-1.5 overflow-x-auto">
                        <button 
                            @click="applyFilter('all')"
                            class="px-3 py-1.5 text-xs font-bold rounded transition cursor-pointer"
                            :class="currentStatus === 'all' ? 'bg-blue-600 text-white shadow-xs' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50'"
                        >
                            All ({{ stats.total_count }})
                        </button>
                        <button 
                            @click="applyFilter('unpaid')"
                            class="px-3 py-1.5 text-xs font-bold rounded transition cursor-pointer"
                            :class="currentStatus === 'unpaid' ? 'bg-blue-600 text-white shadow-xs' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50'"
                        >
                            Unpaid ({{ stats.unpaid_count }})
                        </button>
                        <button 
                            @click="applyFilter('paid')"
                            class="px-3 py-1.5 text-xs font-bold rounded transition cursor-pointer"
                            :class="currentStatus === 'paid' ? 'bg-blue-600 text-white shadow-xs' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50'"
                        >
                            Paid ({{ stats.paid_count }})
                        </button>
                    </div>

                    <!-- Search Input -->
                    <div class="relative w-full sm:w-64">
                        <MagnifyingGlassIcon class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2" />
                        <input 
                            v-model="searchQuery"
                            @input="onSearchInput"
                            type="text"
                            placeholder="Search Invoice # or domain..."
                            class="w-full bg-white border border-slate-200 rounded text-xs pl-8 pr-2.5 py-1.5 focus:ring-1 focus:ring-blue-600 focus:outline-hidden"
                        />
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="invoices.data.length === 0" class="p-12 text-center">
                    <div class="w-12 h-12 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center mx-auto mb-3 border border-blue-100">
                        <DocumentTextIcon class="w-6 h-6" />
                    </div>
                    <h3 class="text-sm font-bold text-slate-900 mb-1">
                        {{ searchQuery ? 'No invoices match your search' : 'No Invoices Found' }}
                    </h3>
                    <p class="text-xs text-slate-500 max-w-sm mx-auto">
                        {{ searchQuery ? 'Try adjusting your search criteria.' : 'All your subscription renewals and statements are currently settled with zero pending balance.' }}
                    </p>
                </div>

                <!-- Invoices Table -->
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase font-bold tracking-wider text-[10px]">
                            <tr>
                                <th class="py-3 px-4">Invoice #</th>
                                <th class="py-3 px-4">Description / Plan</th>
                                <th class="py-3 px-4">Issue Date</th>
                                <th class="py-3 px-4">Due Date</th>
                                <th class="py-3 px-4">Amount</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
                            <tr v-for="inv in invoices.data" :key="inv.id" class="hover:bg-slate-50/70 transition">
                                <!-- Invoice # -->
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-1.5">
                                        <Link 
                                            :href="route('billing.invoice.show', inv.id)"
                                            class="font-mono font-bold text-blue-600 hover:text-blue-800 hover:underline"
                                        >
                                            {{ inv.invoice_no }}
                                        </Link>
                                        <button 
                                            @click="copyToClipboard(inv.invoice_no, 'inv_' + inv.id)"
                                            class="text-slate-400 hover:text-slate-600 cursor-pointer"
                                            title="Copy Invoice #"
                                        >
                                            <CheckIcon v-if="copiedField === 'inv_' + inv.id" class="w-3 h-3 text-emerald-600" />
                                            <ClipboardDocumentIcon v-else class="w-3 h-3" />
                                        </button>
                                    </div>
                                </td>

                                <!-- Description / Service -->
                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-900">
                                        {{ inv.subscription?.plan?.name || 'Hosting Subscription' }}
                                    </div>
                                    <div v-if="inv.subscription?.domain" class="text-[11px] text-slate-500 font-mono mt-0.5">
                                        {{ inv.subscription.domain }}
                                    </div>
                                </td>

                                <!-- Issue Date -->
                                <td class="py-3 px-4 text-slate-600 font-mono text-[11px] whitespace-nowrap">
                                    {{ inv.formatted_issue_date || formatDate(inv.issue_date) }}
                                </td>

                                <!-- Due Date -->
                                <td class="py-3 px-4 font-mono text-[11px] whitespace-nowrap">
                                    <span 
                                        :class="inv.status === 'unpaid' ? 'text-amber-700 font-bold' : 'text-slate-600'"
                                    >
                                        {{ inv.formatted_due_date || formatDate(inv.due_date) }}
                                    </span>
                                </td>

                                <!-- Amount -->
                                <td class="py-3 px-4">
                                    <span class="font-mono font-black text-slate-900 text-sm">
                                        ৳{{ formatCurrency(inv.total_amount) }}
                                    </span>
                                </td>

                                <!-- Status -->
                                <td class="py-3 px-4">
                                    <span 
                                        class="px-2 py-0.5 rounded text-[10px] font-bold border capitalize inline-flex items-center gap-1"
                                        :class="[
                                            inv.status === 'paid' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                                            inv.status === 'unpaid' ? 'bg-amber-50 text-amber-700 border-amber-200' :
                                            inv.status === 'overdue' ? 'bg-rose-50 text-rose-700 border-rose-200' :
                                            'bg-slate-50 text-slate-600 border-slate-200'
                                        ]"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full" :class="inv.status === 'paid' ? 'bg-emerald-600' : 'bg-amber-600'"></span>
                                        <span>{{ inv.status }}</span>
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="py-3 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <!-- Quick Pay Button (if unpaid) -->
                                        <button 
                                            v-if="inv.status !== 'paid'"
                                            @click="openPayModal(inv)"
                                            class="px-2.5 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded text-[11px] font-bold transition cursor-pointer shadow-2xs flex items-center gap-1"
                                        >
                                            <span>Pay Now</span>
                                            <ArrowRightIcon class="w-3 h-3 stroke-[2.5]" />
                                        </button>

                                        <!-- View / Print Link -->
                                        <Link 
                                            :href="route('billing.invoice.show', inv.id)"
                                            class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-[11px] font-bold transition flex items-center gap-1"
                                        >
                                            <span>View</span>
                                            <ArrowTopRightOnSquareIcon class="w-3 h-3 text-slate-500" />
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="invoices.links && invoices.links.length > 3" class="p-4 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-xs text-slate-500">
                        Showing {{ invoices.from }} to {{ invoices.to }} of {{ invoices.total }} invoices
                    </span>
                    <div class="flex items-center gap-1">
                        <Link 
                            v-for="(link, i) in invoices.links" 
                            :key="i"
                            :href="link.url || '#'"
                            class="px-2.5 py-1 text-xs rounded font-bold transition"
                            :class="[
                                link.active ? 'bg-blue-600 text-white' : 'bg-slate-50 text-slate-600 hover:bg-slate-100',
                                !link.url ? 'opacity-40 cursor-not-allowed pointer-events-none' : ''
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>

        </div>

        <!-- ============================================================= -->
        <!-- QUICK PAY MODAL (WRAPPED IN TELEPORT)                         -->
        <!-- ============================================================= -->
        <Teleport to="body">
            <div v-if="showPayModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
                <div class="bg-white rounded-lg border border-slate-200 p-6 max-w-md w-full shadow-xl space-y-5">
                    
                    <div class="flex justify-between items-center pb-3 border-b border-slate-100">
                        <div>
                            <h3 class="text-base font-black text-slate-900">Pay Invoice Online</h3>
                            <p class="text-xs text-slate-500 font-mono mt-0.5">{{ selectedInvoice?.invoice_no }}</p>
                        </div>
                        <button @click="showPayModal = false" class="text-slate-400 hover:text-slate-700 p-1 rounded">
                            <XMarkIcon class="w-5 h-5" />
                        </button>
                    </div>

                    <!-- Amount Due Summary Box -->
                    <div class="bg-blue-50 border border-blue-100 rounded p-3.5 flex items-center justify-between">
                        <div>
                            <span class="text-xs text-blue-700 font-bold uppercase tracking-wider block">Total Payable</span>
                            <span class="text-[11px] text-slate-500">Due: {{ selectedInvoice?.formatted_due_date || formatDate(selectedInvoice?.due_date) }}</span>
                        </div>
                        <span class="text-xl font-black text-blue-700 font-mono">
                            ৳{{ formatCurrency(selectedInvoice?.total_amount) }}
                        </span>
                    </div>

                    <!-- Gateway Selector -->
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-700 mb-2">Select Payment Method</label>
                        <div class="grid grid-cols-2 gap-2">
                            <label 
                                v-for="gw in activeGateways" 
                                :key="gw.slug"
                                :class="[selectedGateway === gw.slug ? 'border-blue-600 bg-blue-50/50 ring-1 ring-blue-600' : 'border-slate-200 hover:border-slate-300 bg-slate-50/50']"
                                class="p-3 rounded border flex flex-col items-center justify-center gap-1 cursor-pointer transition text-center"
                            >
                                <input type="radio" v-model="selectedGateway" :value="gw.slug" class="sr-only" />
                                <span class="font-black text-xs text-slate-900">{{ gw.name }}</span>
                                <span class="text-[10px] text-slate-500 capitalize">{{ gw.category || 'Online' }}</span>
                            </label>
                        </div>
                    </div>

                    <!-- Secure Notice -->
                    <div class="text-[11px] text-slate-500 flex items-center gap-1.5 pt-1">
                        <ShieldCheckIcon class="w-4 h-4 text-emerald-600 shrink-0" />
                        <span>Instant automated activation upon successful payment</span>
                    </div>

                    <div class="flex justify-end gap-2.5 pt-3 border-t border-slate-100">
                        <button 
                            type="button" 
                            @click="showPayModal = false" 
                            class="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-xs font-bold transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button 
                            type="button" 
                            @click="submitQuickPayment" 
                            :disabled="payForm.processing" 
                            class="px-5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs font-bold shadow-xs transition cursor-pointer disabled:opacity-50 flex items-center gap-1.5"
                        >
                            <span>{{ payForm.processing ? 'Redirecting...' : 'Proceed to Checkout →' }}</span>
                        </button>
                    </div>

                </div>
            </div>
        </Teleport>

    </AuthenticatedLayout>
</template>

