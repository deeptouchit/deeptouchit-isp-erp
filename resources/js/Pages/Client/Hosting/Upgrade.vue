<script setup>
import { ref, computed } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import { 
    ArrowUpCircleIcon, 
    ServerIcon, 
    ShieldCheckIcon, 
    ArrowRightIcon, 
    CheckCircleIcon,
    SparklesIcon,
    CreditCardIcon,
    GlobeAltIcon,
    LockClosedIcon,
    LifebuoyIcon,
    CpuChipIcon,
    BoltIcon,
    FolderIcon,
    CircleStackIcon,
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
    currentPlan: {
        type: Object,
        default: null
    },
    allPlans: {
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
const billingCycle = ref('monthly') // 'monthly' | 'yearly'

// Find first higher plan as default target
const higherPlans = computed(() => props.allPlans.filter(p => !p.is_current && p.is_higher))
const selectedPlanId = ref(higherPlans.value[0]?.id || props.allPlans[1]?.id || props.allPlans[0]?.id)
const selectedGateway = ref('bkash')

const form = useForm({
    subscription_id: selectedSubscriptionId.value,
    target_plan_id: selectedPlanId.value,
    billing_cycle: billingCycle.value,
    gateway: selectedGateway.value,
})

const onSubscriptionChange = () => {
    router.get(route('hosting.upgrade'), { subscription_id: selectedSubscriptionId.value }, { preserveState: true })
}

const targetPlan = computed(() => {
    return props.allPlans.find(p => p.id === selectedPlanId.value) || higherPlans.value[0] || props.allPlans[0]
})

const targetPrice = computed(() => {
    if (!targetPlan.value) return 0
    return billingCycle.value === 'yearly' ? targetPlan.value.price_yearly : targetPlan.value.price_monthly
})

const currentPrice = computed(() => {
    if (!props.currentPlan) return 0
    return billingCycle.value === 'yearly' 
        ? Number(props.currentPlan.price_yearly || 0) 
        : Number(props.currentPlan.price_monthly || 0)
})

const upgradeDifference = computed(() => {
    const diff = targetPrice.value - currentPrice.value
    return Math.max(10, diff > 0 ? diff : targetPrice.value)
})

const formatCurrency = (amount) => {
    return Number(amount || 0).toLocaleString('en-BD', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    })
}

const submitUpgrade = () => {
    form.subscription_id = selectedSubscriptionId.value
    form.target_plan_id = selectedPlanId.value
    form.billing_cycle = billingCycle.value
    form.gateway = selectedGateway.value
    form.post(route('hosting.upgrade.process'), {
        preserveScroll: true
    })
}
</script>

<template>
    <Head title="Upgrade Hosting Plan - DeepTouch Host" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto space-y-4">
            
            <!-- 1. Page Header with Breadcrumbs & Actions -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Hosting Plan', href: route('subscriptions.index') },
                    { label: 'Upgrade Package' }
                ]"
            >
                <template #actions>
                    <Link 
                        :href="route('subscriptions.index')" 
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-[3px] text-xs font-bold transition flex items-center gap-1.5 shadow-2xs"
                    >
                        <ServerIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Subscriptions</span>
                    </Link>
                    <Link 
                        :href="route('hosting.renew')" 
                        class="px-2.5 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 rounded-[3px] text-xs font-bold transition flex items-center gap-1.5 shadow-2xs"
                    >
                        <SparklesIcon class="w-3.5 h-3.5 text-emerald-600" />
                        <span>Renew Current</span>
                    </Link>
                </template>
            </PageHeader>

            <!-- 2. Current Plan Status & Subscription Switcher Card -->
            <div v-if="subscription" class="bg-white rounded-lg border border-slate-200 p-5 shadow-2xs space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-slate-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-md bg-blue-50 border border-blue-200/80 text-blue-600 flex items-center justify-center shrink-0">
                            <ServerIcon class="w-5 h-5 stroke-[2.2]" />
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-slate-900 tracking-tight">{{ subscription.domain }}</h2>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Current Package: <strong class="text-slate-800">{{ currentPlan?.name }}</strong> • User: <span class="font-mono text-slate-700">{{ subscription.username }}</span>
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

                <!-- Zero Downtime Notice Bar -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between text-xs bg-slate-50 p-3 rounded-md border border-slate-200/80 gap-3">
                    <div class="flex items-center gap-2">
                        <ShieldCheckIcon class="w-4 h-4 text-emerald-600 shrink-0" />
                        <span class="text-slate-700 font-semibold">100% Zero Downtime Upgrade Guarantee:</span>
                        <span class="text-slate-500">All files, MySQL databases & emails stay untouched.</span>
                    </div>

                    <div class="flex items-center gap-2">
                        <!-- Billing Cycle Toggle (Monthly vs Yearly) -->
                        <div class="flex items-center gap-1 bg-white p-0.5 rounded-[3px] border border-slate-200 text-xs font-bold shadow-2xs">
                            <button 
                                @click="billingCycle = 'monthly'" 
                                class="px-2.5 py-1 rounded-[2px] transition cursor-pointer"
                                :class="billingCycle === 'monthly' ? 'bg-blue-600 text-white shadow-2xs' : 'text-slate-600 hover:text-slate-900'"
                            >
                                Monthly
                            </button>
                            <button 
                                @click="billingCycle = 'yearly'" 
                                class="px-2.5 py-1 rounded-[2px] transition cursor-pointer flex items-center gap-1"
                                :class="billingCycle === 'yearly' ? 'bg-blue-600 text-white shadow-2xs' : 'text-slate-600 hover:text-slate-900'"
                            >
                                <span>Yearly</span>
                                <span class="text-[9px] px-1 py-0.2 rounded bg-amber-400 text-slate-900 font-bold">Save 15%</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Available Hosting Plans Selection Grid -->
            <div class="space-y-2.5">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-1.5">
                        <ArrowUpCircleIcon class="w-4 h-4 text-blue-600" />
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800">Choose Upgrade Package</h3>
                    </div>
                    <span class="text-[11px] text-slate-500 font-medium">Select a tier to scale your allocated hardware resources</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5">
                    <div 
                        v-for="plan in allPlans" 
                        :key="plan.id"
                        @click="!plan.is_current && (selectedPlanId = plan.id)"
                        class="p-4 rounded-lg border transition relative flex flex-col justify-between space-y-3"
                        :class="[
                            plan.is_current 
                                ? 'bg-slate-100/70 border-slate-200 opacity-60 cursor-not-allowed' 
                                : (selectedPlanId === plan.id 
                                    ? 'bg-blue-50/40 border-blue-600 ring-2 ring-blue-600/10 shadow-xs cursor-pointer' 
                                    : 'bg-white border-slate-200 hover:border-slate-300 shadow-2xs cursor-pointer')
                        ]"
                    >
                        <!-- Card Header -->
                        <div>
                            <div class="flex items-center justify-between">
                                <span 
                                    v-if="plan.is_current"
                                    class="px-2 py-0.5 rounded-[3px] text-[9.5px] font-bold uppercase tracking-wider bg-slate-200 text-slate-700"
                                >
                                    Current Plan
                                </span>
                                <span 
                                    v-else-if="plan.badge" 
                                    class="px-2 py-0.5 rounded-[3px] text-[9.5px] font-bold uppercase tracking-wider bg-blue-600 text-white"
                                >
                                    {{ plan.badge }}
                                </span>
                                <span v-else class="text-[10px] text-slate-400 font-bold uppercase">Upgrade Option</span>

                                <div 
                                    v-if="!plan.is_current"
                                    class="w-4 h-4 rounded-full border flex items-center justify-center transition"
                                    :class="selectedPlanId === plan.id ? 'border-blue-600 bg-blue-600 text-white' : 'border-slate-300 bg-white'"
                                >
                                    <CheckIcon v-if="selectedPlanId === plan.id" class="w-2.5 h-2.5 stroke-[3]" />
                                </div>
                            </div>

                            <h4 class="text-sm font-black text-slate-900 mt-2">{{ plan.name }}</h4>
                            <p class="text-[11px] text-slate-500 mt-0.5 leading-snug">{{ plan.description || 'Optimized for high traffic and fast load speeds' }}</p>
                        </div>

                        <!-- Price Tag -->
                        <div class="pt-2 border-t border-slate-100">
                            <span class="text-2xl font-black text-slate-900">
                                ৳{{ formatCurrency(billingCycle === 'yearly' ? plan.price_yearly : plan.price_monthly) }}
                            </span>
                            <span class="text-xs text-slate-500 font-medium">/ {{ billingCycle === 'yearly' ? 'year' : 'month' }}</span>
                        </div>

                        <!-- Specs Comparison List -->
                        <div class="space-y-1.5 text-xs pt-2 border-t border-slate-100 font-medium text-slate-700">
                            <div class="flex items-center gap-2">
                                <FolderIcon class="w-3.5 h-3.5 text-blue-600 shrink-0" />
                                <span><strong>{{ plan.disk_space_formatted }}</strong> NVMe SSD Storage</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <BoltIcon class="w-3.5 h-3.5 text-purple-600 shrink-0" />
                                <span><strong>{{ plan.ram_formatted }}</strong> Dedicated RAM</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <CpuChipIcon class="w-3.5 h-3.5 text-emerald-600 shrink-0" />
                                <span><strong>{{ plan.cpu_cores }}</strong> Dedicated Share</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <GlobeAltIcon class="w-3.5 h-3.5 text-indigo-600 shrink-0" />
                                <span><strong>{{ plan.max_domains }}</strong> Hosted Domain(s)</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <CircleStackIcon class="w-3.5 h-3.5 text-amber-600 shrink-0" />
                                <span><strong>{{ plan.max_databases }}</strong> MySQL Database(s)</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. Payment Gateway Selector & Order Review (Two Columns) -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                
                <!-- Left 2-Cols: Payment Gateway Selector -->
                <div class="lg:col-span-2 bg-white rounded-lg border border-slate-200 p-5 shadow-2xs space-y-3.5">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <CreditCardIcon class="w-4 h-4 text-blue-600" />
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800">Select Payment Method</h3>
                        </div>
                        <span class="text-[11px] text-emerald-600 font-bold flex items-center gap-1">
                            <LockClosedIcon class="w-3.5 h-3.5" />
                            <span>SSL Secured</span>
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

                <!-- Right 1-Col: Upgrade Order Summary & Confirmation -->
                <div class="bg-white rounded-lg border border-slate-200 p-5 shadow-2xs space-y-4 flex flex-col justify-between">
                    <div class="space-y-3">
                        <div class="pb-2.5 border-b border-slate-100">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800">Upgrade Order Summary</h3>
                        </div>

                        <div class="space-y-2 text-xs">
                            <div class="flex items-center justify-between text-slate-600">
                                <span>Target Domain:</span>
                                <strong class="text-slate-900 font-mono">{{ subscription?.domain }}</strong>
                            </div>

                            <div class="flex items-center justify-between text-slate-600">
                                <span>Current Plan:</span>
                                <span class="font-medium text-slate-700">{{ currentPlan?.name }}</span>
                            </div>

                            <div class="flex items-center justify-between text-slate-600">
                                <span>New Target Plan:</span>
                                <strong class="text-blue-700 font-bold">{{ targetPlan?.name }}</strong>
                            </div>

                            <div class="flex items-center justify-between text-slate-600">
                                <span>Billing Cycle:</span>
                                <span class="capitalize font-bold text-slate-800">{{ billingCycle }}</span>
                            </div>

                            <div class="flex items-center justify-between text-slate-600">
                                <span>Target Plan Rate:</span>
                                <span class="font-mono text-slate-700">৳{{ formatCurrency(targetPrice) }}</span>
                            </div>

                            <div class="pt-2.5 border-t border-slate-200 flex items-center justify-between text-slate-900">
                                <div>
                                    <span class="text-xs font-black block">Prorated Upgrade Fee:</span>
                                    <span class="text-[10px] text-slate-400">Instant activation on payment</span>
                                </div>
                                <span class="text-xl font-black text-blue-700 font-mono">৳{{ formatCurrency(upgradeDifference) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-3">
                        <button 
                            @click="submitUpgrade"
                            :disabled="form.processing || !targetPlan || targetPlan.is_current"
                            class="w-full py-2.5 px-4 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white rounded-[3px] text-xs font-bold shadow-2xs transition flex items-center justify-center gap-2 cursor-pointer"
                        >
                            <span v-if="form.processing">Generating Upgrade Invoice...</span>
                            <span v-else>Confirm & Upgrade (৳{{ formatCurrency(upgradeDifference) }})</span>
                            <ArrowRightIcon class="w-3.5 h-3.5 stroke-[2.5]" />
                        </button>
                        <p class="text-[10px] text-slate-400 text-center mt-2">
                            Instant zero-downtime hardware expansion.
                        </p>
                    </div>
                </div>

            </div>

        </div>
    </AuthenticatedLayout>
</template>
