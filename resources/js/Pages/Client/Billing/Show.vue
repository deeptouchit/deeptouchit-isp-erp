<script setup>
import { ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import { 
    ArrowLeftIcon, 
    PrinterIcon, 
    CheckCircleIcon, 
    CreditCardIcon, 
    ShieldCheckIcon,
    CalendarIcon,
    BuildingOfficeIcon,
    CheckIcon,
    ClipboardDocumentIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    invoice: {
        type: Object,
        required: true
    },
    activeGateways: {
        type: Array,
        default: () => []
    }
})

const selectedGateway = ref(props.activeGateways[0]?.slug || 'bkash')

const payForm = useForm({
    gateway: selectedGateway.value
})

const submitPayment = () => {
    payForm.gateway = selectedGateway.value
    payForm.post(route('billing.pay', props.invoice.id))
}

const printInvoice = () => {
    window.print()
}

// Copy Feedback
const copied = ref(false)
const copyInvoiceNumber = () => {
    navigator.clipboard.writeText(props.invoice.invoice_no)
    copied.value = true
    setTimeout(() => {
        copied.value = false
    }, 2000)
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
    <Head :title="`Invoice #${invoice.invoice_no} - DeepTouch Host`" />

    <AuthenticatedLayout>
        <div class="max-w-4xl mx-auto space-y-4">
            <!-- 1. Smart Minimal Page Header -->
            <PageHeader
                class="no-print"
                :breadcrumbs="[
                    { label: 'Billing & Invoices', href: '#' },
                    { label: 'Invoices', href: route('billing.invoices') },
                    { label: 'Invoice #' + invoice.invoice_no }
                ]"
            >
                <template #actions>
                    <Link 
                        :href="route('billing.invoices')" 
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowLeftIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Invoices</span>
                    </Link>

                    <button 
                        @click="printInvoice" 
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-[3px] text-xs font-bold flex items-center gap-1.5 transition shadow-2xs cursor-pointer"
                    >
                        <PrinterIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Print</span>
                    </button>
                </template>
            </PageHeader>

            <!-- Printable Invoice Paper -->
            <div class="bg-white rounded-lg border border-slate-200 p-8 sm:p-12 shadow-xs space-y-8 printable-invoice">
                
                <!-- Invoice Header -->
                <div class="flex flex-col sm:flex-row justify-between sm:items-start pb-8 border-b border-slate-100 gap-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <div class="w-9 h-9 rounded bg-blue-600 text-white flex items-center justify-center font-black text-lg">
                                D
                            </div>
                            <span class="text-2xl font-black tracking-tight text-slate-900">
                                DeepTouch<span class="text-blue-600">Host</span>
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mt-2">Enterprise Cloud Hosting & Infrastructure</p>
                        <p class="text-xs text-slate-400 font-mono mt-0.5">Website: deeptouchit.com • Support: help@deeptouchit.com</p>
                    </div>

                    <div class="sm:text-right">
                        <div class="inline-block">
                            <span 
                                class="px-3 py-1 rounded text-xs font-black uppercase tracking-wider border inline-flex items-center gap-1.5"
                                :class="[
                                    invoice.status === 'paid' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                                    invoice.status === 'unpaid' ? 'bg-amber-50 text-amber-700 border-amber-200' :
                                    'bg-slate-50 text-slate-600 border-slate-200'
                                ]"
                            >
                                <span class="w-2 h-2 rounded-full" :class="invoice.status === 'paid' ? 'bg-emerald-600' : 'bg-amber-600'"></span>
                                <span>{{ invoice.status }}</span>
                            </span>
                        </div>
                        <p class="text-xs text-slate-400 font-mono mt-2">Invoice #: {{ invoice.invoice_no }}</p>
                        <p class="text-xs text-slate-500 mt-0.5 font-medium">Issue Date: {{ invoice.formatted_issue_date || formatDate(invoice.issue_date) }}</p>
                        <p class="text-xs font-bold mt-0.5" :class="invoice.status === 'unpaid' ? 'text-amber-700' : 'text-slate-500'">
                            Due Date: {{ invoice.formatted_due_date || formatDate(invoice.due_date) }}
                        </p>
                    </div>
                </div>

                <!-- Invoiced To / Invoiced From Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-8 text-xs text-slate-600">
                    <div>
                        <p class="font-black text-slate-400 uppercase tracking-wider text-[10px] mb-1.5">Invoiced To:</p>
                        <p class="font-black text-slate-900 text-sm">
                            {{ invoice.user?.first_name ? `${invoice.user.first_name} ${invoice.user.last_name || ''}` : invoice.user?.username || 'Client' }}
                        </p>
                        <p class="text-slate-600 font-mono mt-0.5">{{ invoice.user?.email }}</p>
                        <p v-if="invoice.user?.phone" class="text-slate-500 font-mono mt-0.5">{{ invoice.user.phone }}</p>
                        <p v-if="invoice.subscription?.domain" class="text-blue-600 font-bold mt-1">
                            Domain: {{ invoice.subscription.domain }}
                        </p>
                    </div>

                    <div class="sm:text-right">
                        <p class="font-black text-slate-400 uppercase tracking-wider text-[10px] mb-1.5">Payment Details:</p>
                        <p class="font-bold text-slate-900">
                            {{ invoice.status === 'paid' ? 'Paid via Online Payment Gateway' : 'Pending Client Payment' }}
                        </p>
                        <p class="text-slate-500 font-mono mt-0.5">Currency: {{ invoice.currency || 'BDT (৳)' }}</p>
                        <p v-if="invoice.paid_at" class="text-emerald-700 font-bold mt-0.5">
                            Settled on: {{ invoice.formatted_paid_at || formatDate(invoice.paid_at) }}
                        </p>
                    </div>
                </div>

                <!-- Line Items Table -->
                <div class="overflow-x-auto border border-slate-100 rounded">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 border-b border-slate-100 text-slate-500 uppercase font-black tracking-wider text-[10px]">
                            <tr>
                                <th class="py-3 px-4">Item Description</th>
                                <th class="py-3 px-4 text-center">Qty</th>
                                <th class="py-3 px-4 text-right">Unit Price</th>
                                <th class="py-3 px-4 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                            <tr v-for="item in invoice.items" :key="item.id">
                                <td class="py-3.5 px-4 font-bold text-slate-900">{{ item.description }}</td>
                                <td class="py-3.5 px-4 text-center font-mono">{{ item.quantity }}</td>
                                <td class="py-3.5 px-4 text-right font-mono">৳{{ formatCurrency(item.unit_price) }}</td>
                                <td class="py-3.5 px-4 text-right font-bold font-mono text-slate-900">৳{{ formatCurrency(item.total_price) }}</td>
                            </tr>
                            <tr v-if="!invoice.items || invoice.items.length === 0">
                                <td class="py-3.5 px-4 font-bold text-slate-900">
                                    {{ invoice.subscription?.plan?.name || 'Hosting Subscription' }} ({{ invoice.subscription?.domain || 'Web Service' }})
                                </td>
                                <td class="py-3.5 px-4 text-center font-mono">1</td>
                                <td class="py-3.5 px-4 text-right font-mono">৳{{ formatCurrency(invoice.total_amount) }}</td>
                                <td class="py-3.5 px-4 text-right font-bold font-mono text-slate-900">৳{{ formatCurrency(invoice.total_amount) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Financial Calculation Summary -->
                <div class="flex justify-end pt-2">
                    <div class="w-full sm:w-72 space-y-2 text-xs">
                        <div class="flex justify-between text-slate-500">
                            <span>Subtotal:</span>
                            <span class="font-mono text-slate-900 font-bold">৳{{ formatCurrency(invoice.total_amount) }}</span>
                        </div>
                        <div v-if="Number(invoice.discount_amount) > 0" class="flex justify-between text-emerald-600">
                            <span>Discount:</span>
                            <span class="font-mono font-bold">-৳{{ formatCurrency(invoice.discount_amount) }}</span>
                        </div>
                        <div class="flex justify-between text-slate-500 pb-2 border-b border-slate-100">
                            <span>Tax / VAT (0%):</span>
                            <span class="font-mono text-slate-900">৳0.00</span>
                        </div>
                        <div class="flex justify-between text-base font-black text-slate-900 pt-1">
                            <span>Total Payable:</span>
                            <span class="text-blue-600 font-mono">৳{{ formatCurrency(invoice.total_amount) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Settled Stamp if Paid -->
                <div v-if="invoice.status === 'paid'" class="bg-emerald-50 border border-emerald-200 rounded p-4 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                        <CheckCircleIcon class="w-5 h-5 stroke-[2.5]" />
                    </div>
                    <div>
                        <p class="text-xs font-black text-emerald-800">Invoice Fully Paid and Reconciled</p>
                        <p class="text-[11px] text-emerald-600 mt-0.5">Thank you for your business. Your services remain active without interruption.</p>
                    </div>
                </div>

            </div>

            <!-- Online Payment Box (If Unpaid) -->
            <div v-if="invoice.status !== 'paid'" class="bg-white rounded-lg border border-slate-200 p-6 shadow-xs space-y-5 no-print">
                <div>
                    <h3 class="text-base font-black text-slate-900">Select Payment Method & Pay Online</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Choose your preferred payment gateway for automated instant activation</p>
                </div>

                <!-- Gateway Selection Cards -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <label 
                        v-for="gw in activeGateways" 
                        :key="gw.slug"
                        :class="[selectedGateway === gw.slug ? 'border-blue-600 bg-blue-50/50 ring-1 ring-blue-600' : 'border-slate-200 hover:border-slate-300 bg-slate-50/50']"
                        class="p-4 rounded border flex flex-col items-center justify-center gap-1.5 cursor-pointer transition text-center"
                    >
                        <input type="radio" v-model="selectedGateway" :value="gw.slug" class="sr-only" />
                        <span class="font-black text-xs text-slate-900">{{ gw.name }}</span>
                        <span class="text-[10px] text-slate-500 capitalize">{{ gw.category || 'Automated' }}</span>
                    </label>
                </div>

                <div class="flex flex-col sm:flex-row justify-between items-center gap-4 pt-4 border-t border-slate-100">
                    <span class="text-xs text-slate-500 flex items-center gap-1.5">
                        <ShieldCheckIcon class="w-4 h-4 text-emerald-600 shrink-0" />
                        <span>256-Bit Encrypted Secure Gateway Checkout</span>
                    </span>
                    <button 
                        @click="submitPayment"
                        :disabled="payForm.processing"
                        class="w-full sm:w-auto px-8 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs font-bold shadow-xs transition cursor-pointer disabled:opacity-50"
                    >
                        {{ payForm.processing ? 'Redirecting to Gateway...' : `Pay ৳${formatCurrency(invoice.total_amount)} Now →` }}
                    </button>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>

<style>
@media print {
    .no-print {
        display: none !important;
    }
    body {
        background: white !important;
    }
    .printable-invoice {
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
    }
}
</style>

