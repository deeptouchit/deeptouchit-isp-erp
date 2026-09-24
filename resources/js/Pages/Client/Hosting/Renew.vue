<script setup>
import { ref, computed } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import { 
    ClockIcon, 
    ServerIcon, 
    ShieldCheckIcon, 
    ArrowRightIcon, 
    CheckCircleIcon,
    SparklesIcon,
    ArrowUpCircleIcon,
    CreditCardIcon,
    BanknotesIcon,
    DocumentTextIcon,
    GlobeAltIcon,
    LockClosedIcon,
    LifebuoyIcon,
    CheckIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    subscription: {
        type: Object,
        default: null
    },
    subscriptions: {
        type: Array,
        default: () => []
    },
    plan: {
        type: Object,
        default: null
    },
    cycles: {
        type: Array,
        default: () => []
    },
    daysRemaining: {
        type: Number,
        default: null
    },
    recentInvoices: {
        type: Array,
        default: () => []
    },
    availableGateways: {
        type: Array,
        default: () => []
    }
})

// Form State
const selectedSubscriptionId = ref(props.subscription?.id || (props.subscriptions[0]?.id ?? ''))
const selectedCycleId = ref('yearly')
const selectedGateway = ref('bkash')

const form = useForm({
    subscription_id: selectedSubscriptionId.value,
    cycle: selectedCycleId.value,
    gateway: selectedGateway.value,
})

const onSubscriptionChange = () => {
    router.get(route('hosting.renew'), { subscription_id: selectedSubscriptionId.value }, { preserveState: true })
}

const selectedCycle = computed(() => {
    return props.cycles.find(c => c.id === selectedCycleId.value) || props.cycles[1] || props.cycles[0]
})

const discountAmount = computed(() => {
    if (!selectedCycle.value) return 0
    return Math.max(0, (selectedCycle.value.regular_price || selectedCycle.value.price) - selectedCycle.value.price)
})

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

// Projected new expiry date
const projectedExpiry = computed(() => {
    if (!props.subscription?.expires_at || !selectedCycle.value) return 'Extended from expiration'
    try {
        const d = new Date(props.subscription.expires_at)
        if (isNaN(d.getTime())) return 'Extended from expiration'
        const months = selectedCycle.value.months || 12
        d.setMonth(d.getMonth() + months)
        return d.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' })
    } catch {
        return 'Extended from expiration'
    }
})

const submitRenewal = () => {
    form.subscription_id = selectedSubscriptionId.value
    form.cycle = selectedCycleId.value
    form.gateway = selectedGateway.value
    form.post(route('hosting.renew.process'), {
        preserveScroll: true
    })
}
</script>

<template>
    <Head title="Renew Hosting Subscription - DeepTouch Host" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto space-y-4">
            
            <!-- 1. Page Header with Breadcrumbs & Actions -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Hosting Plan', href: route('subscriptions.index') },
                    { label: 'Renew Subscription' }
                ]"
            >
                <template #actions>
                    <Link 
                        :href="route('subscriptions.index')" 
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-[3px] text-xs font-bold transition flex items-center gap-1.5 shadow-2xs"
                    >
                        <ServerIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>All Subscriptions</span>
                    </Link>
                    <Link 
                        :href="route('tickets.create')" 
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-[3px] text-xs font-bold transition flex items-center gap-1.5 shadow-2xs"
                    >
                        <LifebuoyIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Need Help?</span>
                    </Link>
                </template>
            </PageHeader>

            <!-- 2. Active Subscription Overview & Switcher Card -->
            <div v-if="subscription" class="bg-white rounded-lg border border-slate-200 p-5 shadow-2xs space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-slate-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-md bg-blue-50 border border-blue-200/80 text-blue-600 flex items-center justify-center shrink-0">
                            <ServerIcon class="w-5 h-5 stroke-[2.2]" />
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-slate-900 tracking-tight">{{ subscription.domain }}</h2>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Current Package: <strong class="text-slate-800">{{ plan?.name }}</strong> • User: <span class="font-mono text-slate-700">{{ subscription.username }}</span>
                            </p>
                        </div>
                    </div>

                    <!-- Subscription Switcher if multiple exist -->
                    <div v-if="subscriptions.length > 1" class="flex items-center gap-2">
                        <label class="text-xs font-bold text-slate-600">Switch Plan:</label>
                        <select 
                            v-model="selectedSubscriptionId" 
                            @change="onSubscriptionChange"
                            class="text-xs font-bold rounded-[3px] border-slate-300 bg-slate-50 text-slate-800 py-1.5 px-2.5 focus:ring-blue-500 focus:border-blue-500"
                        >
                            <option v-for="sub in subscriptions" :key="sub.id" :value="sub.id">
                                {{ sub.domain }} ({{ sub.plan?.name }})
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Expiry Date Alert Bar -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between text-xs bg-slate-50 p-3 rounded-md border border-slate-200/80 gap-3">
                    <div class="flex items-center gap-2">
                        <ClockIcon class="w-4 h-4 text-slate-600 shrink-0" />
                        <span class="text-slate-600">Current Expiry Date:</span>
                        <strong class="text-slate-900 font-mono">{{ formatDate(subscription.expires_at) }}</strong>
                    </div>

                    <div class="flex items-center gap-2">
                        <span 
                            class="px-2 py-0.5 rounded-[3px] text-[11px] font-bold border"
                            :class="daysRemaining !== null && daysRemaining <= 7 ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-slate-100 text-slate-700 border-slate-200'"
                        >
                            {{ daysRemaining !== null ? `${daysRemaining} Days Remaining` : 'Auto-Renew Active' }}
                        </span>
                        <span class="text-[11px] text-emerald-600 font-semibold flex items-center gap-1">
                            <ShieldCheckIcon class="w-3.5 h-3.5" />
                            <span>Zero Downtime Protection</span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- 3. Interactive Renewal Term Selection (4-Card Grid) -->
            <div class="space-y-2.5">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-1.5">
                        <SparklesIcon class="w-4 h-4 text-blue-600" />
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800">Select Renewal Term & Savings</h3>
                    </div>
                    <span class="text-[11px] text-slate-500 font-medium">Multi-year renewals include guaranteed price lock</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
                    <div 
                        v-for="cycle in cycles" 
                        :key="cycle.id"
                        @click="selectedCycleId = cycle.id"
                        class="p-4 rounded-lg border transition cursor-pointer relative flex flex-col justify-between space-y-3"
                        :class="selectedCycleId === cycle.id 
                            ? 'bg-blue-50/40 border-blue-600 ring-2 ring-blue-600/10 shadow-xs' 
                            : 'bg-white border-slate-200 hover:border-slate-300 shadow-2xs'"
                    >
                        <!-- Top Badge -->
                        <div class="flex items-center justify-between">
                            <span 
                                v-if="cycle.badge" 
                                class="px-2 py-0.5 rounded-[3px] text-[9.5px] font-bold uppercase tracking-wider bg-blue-600 text-white"
                            >
                                {{ cycle.badge }}
                            </span>
                            <span v-else class="text-[10px] text-slate-400 font-bold uppercase">Flexible</span>

                            <div 
                                class="w-4 h-4 rounded-full border flex items-center justify-center transition"
                                :class="selectedCycleId === cycle.id ? 'border-blue-600 bg-blue-600 text-white' : 'border-slate-300 bg-white'"
                            >
                                <CheckIcon v-if="selectedCycleId === cycle.id" class="w-2.5 h-2.5 stroke-[3]" />
                            </div>
                        </div>

                        <!-- Title & Price -->
                        <div>
                            <h4 class="text-xs font-black text-slate-900">{{ cycle.name }}</h4>
                            <p class="text-[11px] text-slate-500 mt-0.5 leading-snug">{{ cycle.description }}</p>
                            
                            <div class="mt-2.5 pt-2.5 border-t border-slate-100 flex items-baseline gap-1.5">
                                <span class="text-xl font-black text-slate-900">৳{{ formatCurrency(cycle.price) }}</span>
                                <span 
                                    v-if="cycle.regular_price && cycle.regular_price > cycle.price" 
                                    class="text-xs text-slate-400 line-through font-mono"
                                >
                                    ৳{{ formatCurrency(cycle.regular_price) }}
                                </span>
                            </div>
                        </div>

                        <!-- Savings Tag -->
                        <div v-if="cycle.savings_percent > 0" class="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-1 rounded border border-emerald-200 text-center">
                            Save {{ cycle.savings_percent }}% (৳{{ formatCurrency(cycle.regular_price - cycle.price) }} Off)
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. Two-Column Payment & Final Order Summary Card -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                
                <!-- Left 2-Cols: Payment Gateway Selector -->
                <div class="lg:col-span-2 bg-white rounded-lg border border-slate-200 p-5 shadow-2xs space-y-3.5">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <CreditCardIcon class="w-4 h-4 text-blue-600" />
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800">Select Instant Payment Gateway</h3>
                        </div>
                        <span class="text-[11px] text-emerald-600 font-bold flex items-center gap-1">
                            <LockClosedIcon class="w-3.5 h-3.5" />
                            <span>256-Bit SSL Encrypted</span>
                        </span>
                    </div>

                    <div class="space-y-2">
                        <div 
                            v-for="gw in availableGateways" 
                            :key="gw.id"
                            @click="selectedGateway = gw.id"
                            class="p-3 rounded-md border transition cursor-pointer flex items-center justify-between"
                            :class="selectedGateway === gw.id 
                                ? 'bg-blue-50/40 border-blue-600 ring-1 ring-blue-600/20' 
                                : 'bg-slate-50/60 border-slate-200 hover:bg-slate-50 hover:border-slate-300'"
                        >
                            <div class="flex items-center gap-3">
                                <div 
                                    class="w-4 h-4 rounded-full border flex items-center justify-center shrink-0"
                                    :class="selectedGateway === gw.id ? 'border-blue-600 bg-blue-600 text-white' : 'border-slate-300 bg-white'"
                                >
                                    <CheckIcon v-if="selectedGateway === gw.id" class="w-2.5 h-2.5 stroke-[3]" />
                                </div>
                                <div>
                                    <span class="text-xs font-bold text-slate-900 block">{{ gw.name }}</span>
                                    <span class="text-[11px] text-slate-500 block">{{ gw.desc }}</span>
                                </div>
                            </div>

                            <span class="text-[10px] font-bold uppercase text-slate-400">Instant</span>
                        </div>
                    </div>
                </div>

                <!-- Right 1-Col: Order Review & Instant Submit -->
                <div class="bg-white rounded-lg border border-slate-200 p-5 shadow-2xs space-y-4 flex flex-col justify-between">
                    <div class="space-y-3">
                        <div class="pb-2.5 border-b border-slate-100">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800">Renewal Order Summary</h3>
                        </div>

                        <div class="space-y-2 text-xs">
                            <div class="flex items-center justify-between text-slate-600">
                                <span>Domain:</span>
                                <strong class="text-slate-900 font-mono">{{ subscription?.domain }}</strong>
                            </div>

                            <div class="flex items-center justify-between text-slate-600">
                                <span>Selected Term:</span>
                                <strong class="text-slate-900">{{ selectedCycle?.name }}</strong>
                            </div>

                            <div class="flex items-center justify-between text-slate-600">
                                <span>Regular Price:</span>
                                <span class="font-mono text-slate-600">৳{{ formatCurrency(selectedCycle?.regular_price || selectedCycle?.price) }}</span>
                            </div>

                            <div v-if="discountAmount > 0" class="flex items-center justify-between text-emerald-700 font-semibold">
                                <span>Term Savings Discount:</span>
                                <span class="font-mono">-৳{{ formatCurrency(discountAmount) }}</span>
                            </div>

                            <div class="flex items-center justify-between text-slate-600">
                                <span>VAT & System Fees:</span>
                                <span class="font-mono text-slate-600">৳0.00</span>
                            </div>

                            <div class="pt-2.5 border-t border-slate-200 flex items-center justify-between text-slate-900">
                                <span class="text-xs font-black">Total Payable:</span>
                                <span class="text-xl font-black text-blue-700 font-mono">৳{{ formatCurrency(selectedCycle?.price) }}</span>
                            </div>

                            <div class="p-2.5 bg-slate-50 rounded border border-slate-200 text-[11px] text-slate-600 space-y-1">
                                <span class="font-bold text-slate-800 block">New Expiration Date Preview:</span>
                                <span class="font-mono text-emerald-700 font-bold block">{{ projectedExpiry }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-3">
                        <button 
                            @click="submitRenewal"
                            :disabled="form.processing"
                            class="w-full py-2.5 px-4 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white rounded-[3px] text-xs font-bold shadow-2xs transition flex items-center justify-center gap-2 cursor-pointer"
                        >
                            <span v-if="form.processing">Generating Invoice...</span>
                            <span v-else>Proceed to Payment (৳{{ formatCurrency(selectedCycle?.price) }})</span>
                            <ArrowRightIcon class="w-3.5 h-3.5 stroke-[2.5]" />
                        </button>
                        <p class="text-[10px] text-slate-400 text-center mt-2">
                            Automated invoice will be generated instantly.
                        </p>
                    </div>
                </div>

            </div>

            <!-- 5. Renewal History & Invoices Table -->
            <div v-if="recentInvoices.length > 0" class="bg-white rounded-lg border border-slate-200 shadow-2xs overflow-hidden">
                <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between bg-slate-50/60">
                    <div class="flex items-center gap-2">
                        <DocumentTextIcon class="w-4 h-4 text-slate-700" />
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800">Renewal Transactions & Invoices</h3>
                    </div>
                    <Link :href="route('billing.invoices')" class="text-xs font-bold text-blue-600 hover:underline">
                        View All Invoices →
                    </Link>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs whitespace-nowrap">
                        <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200">
                            <tr>
                                <th class="py-2.5 px-4">Invoice #</th>
                                <th class="py-2.5 px-4">Issue Date</th>
                                <th class="py-2.5 px-4">Due Date</th>
                                <th class="py-2.5 px-4">Total Amount</th>
                                <th class="py-2.5 px-4">Status</th>
                                <th class="py-2.5 px-4 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <tr v-for="inv in recentInvoices" :key="inv.id" class="hover:bg-slate-50/70 transition">
                                <td class="py-3 px-4 font-mono font-bold text-slate-900">
                                    #{{ inv.invoice_no }}
                                </td>
                                <td class="py-3 px-4 text-slate-600">
                                    {{ formatDate(inv.issue_date) }}
                                </td>
                                <td class="py-3 px-4 text-slate-600">
                                    {{ formatDate(inv.due_date) }}
                                </td>
                                <td class="py-3 px-4 font-mono font-bold text-slate-900">
                                    ৳{{ formatCurrency(inv.total_amount) }}
                                </td>
                                <td class="py-3 px-4">
                                    <span 
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border"
                                        :class="inv.status === 'paid' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
                                    >
                                        {{ inv.status }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <Link 
                                        :href="route('billing.invoice.show', inv.id)" 
                                        class="px-2.5 py-1 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 font-bold rounded-[3px] text-xs transition shadow-2xs"
                                    >
                                        {{ inv.status === 'unpaid' ? 'Pay Now' : 'View Invoice' }}
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
