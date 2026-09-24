<script setup>
import { ref, computed } from 'vue'
import { Head, useForm, Link } from '@inertiajs/vue3'
import PublicNavbar from '@/Components/Public/PublicNavbar.vue'
import PublicFooter from '@/Components/Public/PublicFooter.vue'
import { 
    CheckIcon, 
    ShieldCheckIcon, 
    BoltIcon, 
    ServerIcon, 
    CreditCardIcon, 
    LockClosedIcon,
    SparklesIcon,
    ArrowRightIcon,
    UserIcon,
    MapPinIcon,
    BanknotesIcon,
    GlobeAltIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    selectedPlan: {
        type: Object,
        required: true
    },
    allPlans: {
        type: Array,
        default: () => []
    },
    activeGateways: {
        type: Array,
        default: () => []
    },
    initialBillingCycle: {
        type: String,
        default: 'yearly'
    },
    prefilledData: {
        type: Object,
        default: null
    },
    user: {
        type: Object,
        default: null
    }
})

const currentPlanId = ref(props.selectedPlan?.id || (props.allPlans[0]?.id || 1))
const billingCycle = ref(props.initialBillingCycle || 'yearly')

const currentPlan = computed(() => {
    return props.allPlans.find(p => p.id === currentPlanId.value) || props.selectedPlan
})

const monthlyPrice = computed(() => Number(currentPlan.value?.price_monthly || 0))
const yearlyPrice = computed(() => {
    if (currentPlan.value?.price_yearly && Number(currentPlan.value.price_yearly) > 0) {
        return Number(currentPlan.value.price_yearly)
    }
    return monthlyPrice.value * 10
})

const activePrice = computed(() => {
    return billingCycle.value === 'yearly' ? yearlyPrice.value : monthlyPrice.value
})

const savingsAmount = computed(() => {
    return (monthlyPrice.value * 12) - yearlyPrice.value
})

// Gateway selection
const selectedGateway = ref('bkash')

const form = useForm({
    plan_id: currentPlanId.value,
    period: billingCycle.value,
    gateway: selectedGateway.value,
    domain_option: 'existing',
    domain_name: '',
    subdomain_prefix: '',
    currency: 'BDT',
    first_name: props.user?.first_name || (props.prefilledData?.first_name || ''),
    last_name: props.user?.last_name || (props.prefilledData?.last_name || ''),
    email: props.user?.email || (props.prefilledData?.email || ''),
    phone: props.user?.phone || (props.prefilledData?.phone || ''),
    company: props.user?.company || (props.prefilledData?.company || ''),
    country: props.user?.country || (props.prefilledData?.country || 'Bangladesh'),
    state: props.user?.state || (props.prefilledData?.state || ''),
    city: props.user?.city || (props.prefilledData?.city || ''),
    zip_code: props.user?.zip_code || (props.prefilledData?.zip_code || ''),
    address: props.user?.address || (props.prefilledData?.address || ''),
    password: '',
    password_confirmation: '',
})

const resolvedDomain = computed(() => {
    if (form.domain_option === 'existing') {
        return form.domain_name ? form.domain_name.trim().toLowerCase().replace(/^https?:\/\//, '').replace(/\/+$/, '') : ''
    }
    if (form.domain_option === 'subdomain') {
        return form.subdomain_prefix ? (form.subdomain_prefix.trim().toLowerCase().replace(/[^a-z0-9\-]/g, '') + '.deeptouchit.com') : ''
    }
    return form.domain_name || ''
})

const setCycle = (cycle) => {
    billingCycle.value = cycle
    form.period = cycle
}

const setGateway = (gwSlug) => {
    selectedGateway.value = gwSlug
    form.gateway = gwSlug
}

const countries = [
    'Bangladesh',
    'United States',
    'United Kingdom',
    'Canada',
    'Australia',
    'India',
    'Saudi Arabia',
    'United Arab Emirates',
    'Singapore',
    'Malaysia',
    'Germany',
    'Other'
]

const domainError = ref('')

const submitOrder = () => {
    domainError.value = ''
    if (!resolvedDomain.value) {
        domainError.value = 'Please specify your primary domain or select a free staging subdomain.'
        return
    }
    form.plan_id = currentPlanId.value
    form.period = billingCycle.value
    form.gateway = selectedGateway.value
    form.post(route('order.process'))
}
</script>

<template>
    <Head :title="`Order ${currentPlan?.name || 'Hosting'} - Checkout`" />

    <div class="min-h-screen bg-slate-50 flex flex-col font-sans text-slate-800 antialiased selection:bg-blue-100 selection:text-blue-900">
        <PublicNavbar />

        <!-- Header Hero -->
        <section class="bg-white border-b border-slate-200 py-8 px-4 sm:px-6">
            <div class="max-w-6xl mx-auto text-center">
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md bg-blue-50 border border-blue-200 text-blue-700 text-xs font-bold mb-2.5 shadow-2xs">
                    <SparklesIcon class="w-3.5 h-3.5 text-blue-600" />
                    <span>Instant Automated Cloud Provisioning</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                    Secure Hosting Checkout
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 max-w-xl mx-auto mt-1.5">
                    Configure your billing details and complete payment to activate your high-speed BDIX NVMe hosting.
                </p>
            </div>
        </section>

        <!-- Main Checkout Form Section -->
        <section class="py-8 px-4 sm:px-6 flex-1">
            <div class="max-w-6xl mx-auto">
                <form @submit.prevent="submitOrder" class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                    
                    <!-- Left Column: Configuration Steps -->
                    <div class="lg:col-span-7 space-y-6">
                        
                        <!-- Step 1: Plan & Billing Cycle Selection -->
                        <div class="bg-white rounded-xl border border-slate-200 p-5 sm:p-6 shadow-xs space-y-5">
                            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-7 h-7 rounded-lg bg-blue-600 text-white flex items-center justify-center font-black text-xs">
                                        1
                                    </div>
                                    <h2 class="text-sm sm:text-base font-bold text-slate-900">Hosting Package & Billing Term</h2>
                                </div>
                                <span class="text-xs font-semibold text-slate-400">Step 1 of 3</span>
                            </div>

                            <!-- Selected Plan Overview Card -->
                            <div class="p-4 rounded-xl bg-blue-50/60 border border-blue-200">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center font-bold text-xs shadow-xs">
                                            <ServerIcon class="w-4 h-4" />
                                        </div>
                                        <div>
                                            <h3 class="text-sm font-black text-slate-900 leading-tight">{{ currentPlan?.name }}</h3>
                                            <span class="text-[10px] font-bold text-blue-700 uppercase tracking-wider">High-Speed BDIX NVMe Cloud</span>
                                        </div>
                                    </div>
                                    <Link :href="route('public.shared')" class="text-xs font-bold text-blue-600 hover:text-blue-800 underline">
                                        Change Plan
                                    </Link>
                                </div>
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mt-3 pt-2.5 border-t border-blue-100 text-[11px] text-slate-600">
                                    <div class="flex items-center gap-1.5 truncate">
                                        <CheckIcon class="w-3.5 h-3.5 text-emerald-600 shrink-0" />
                                        <span>{{ currentPlan?.disk_space >= 1024 ? (currentPlan.disk_space/1024).toFixed(0) + ' GB NVMe' : currentPlan.disk_space + ' MB' }}</span>
                                    </div>
                                    <div class="flex items-center gap-1.5 truncate">
                                        <CheckIcon class="w-3.5 h-3.5 text-emerald-600 shrink-0" />
                                        <span>Unlimited Bandwidth</span>
                                    </div>
                                    <div class="flex items-center gap-1.5 truncate">
                                        <CheckIcon class="w-3.5 h-3.5 text-emerald-600 shrink-0" />
                                        <span>{{ currentPlan?.max_domains > 1 ? currentPlan.max_domains + ' Websites' : '1 Website' }}</span>
                                    </div>
                                    <div class="flex items-center gap-1.5 truncate">
                                        <CheckIcon class="w-3.5 h-3.5 text-emerald-600 shrink-0" />
                                        <span>Free AutoSSL</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Billing Cycle Options -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-2">Choose Billing Cycle:</label>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <button 
                                        type="button"
                                        @click="setCycle('monthly')"
                                        :class="[
                                            'p-3.5 rounded-xl border text-left transition cursor-pointer relative',
                                            billingCycle === 'monthly'
                                                ? 'bg-blue-50/70 border-blue-600 ring-2 ring-blue-600/20 shadow-xs'
                                                : 'bg-white border-slate-200 hover:border-slate-300 hover:bg-slate-50'
                                        ]"
                                    >
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs font-bold text-slate-900">Monthly Billing</span>
                                            <span class="text-xs font-black text-slate-900">৳{{ monthlyPrice.toLocaleString() }}/mo</span>
                                        </div>
                                        <p class="text-[10px] text-slate-500 mt-1">Billed monthly, cancel anytime.</p>
                                    </button>

                                    <button 
                                        type="button"
                                        @click="setCycle('yearly')"
                                        :class="[
                                            'p-3.5 rounded-xl border text-left transition cursor-pointer relative overflow-hidden',
                                            billingCycle === 'yearly'
                                                ? 'bg-blue-50/70 border-blue-600 ring-2 ring-blue-600/20 shadow-xs'
                                                : 'bg-white border-slate-200 hover:border-slate-300 hover:bg-slate-50'
                                        ]"
                                    >
                                        <div class="absolute top-0 right-0 bg-emerald-600 text-white text-[9px] font-black uppercase px-2 py-0.5 rounded-bl-lg">
                                            Save ৳{{ savingsAmount.toLocaleString() }}
                                        </div>
                                        <div class="flex items-center justify-between pr-8">
                                            <span class="text-xs font-bold text-slate-900">Annually (1 Year)</span>
                                            <span class="text-xs font-black text-blue-700">৳{{ yearlyPrice.toLocaleString() }}/yr</span>
                                        </div>
                                        <p class="text-[10px] text-emerald-700 font-medium mt-1">2 Months Free + Free AutoSSL</p>
                                    </button>
                                </div>
                            </div>

                            <!-- Primary Domain Configuration (Hostinger / Industry Standard) -->
                            <div class="pt-4 border-t border-slate-100 space-y-3">
                                <div class="flex items-center justify-between">
                                    <label class="block text-xs font-bold text-slate-800">
                                        Choose Primary Domain <span class="text-rose-500">*</span>
                                    </label>
                                    <span class="text-[11px] text-blue-600 font-bold">Required for Web & SSL Setup</span>
                                </div>

                                <!-- Domain Options Tabs -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                    <button 
                                        type="button" 
                                        @click="form.domain_option = 'existing'"
                                        :class="[
                                            'px-3.5 py-2.5 rounded-lg border text-left text-xs font-bold transition flex items-center justify-between cursor-pointer',
                                            form.domain_option === 'existing' 
                                                ? 'border-blue-600 bg-blue-50/70 text-blue-900 ring-2 ring-blue-600/20' 
                                                : 'border-slate-200 bg-white hover:bg-slate-50 text-slate-700'
                                        ]"
                                    >
                                        <div class="flex items-center gap-2">
                                            <GlobeAltIcon class="w-4 h-4 text-blue-600 shrink-0" />
                                            <span>I have an existing domain</span>
                                        </div>
                                        <CheckIcon v-if="form.domain_option === 'existing'" class="w-4 h-4 text-blue-600 shrink-0" />
                                    </button>

                                    <button 
                                        type="button" 
                                        @click="form.domain_option = 'subdomain'"
                                        :class="[
                                            'px-3.5 py-2.5 rounded-lg border text-left text-xs font-bold transition flex items-center justify-between cursor-pointer',
                                            form.domain_option === 'subdomain' 
                                                ? 'border-blue-600 bg-blue-50/70 text-blue-900 ring-2 ring-blue-600/20' 
                                                : 'border-slate-200 bg-white hover:bg-slate-50 text-slate-700'
                                        ]"
                                    >
                                        <div class="flex items-center gap-2">
                                            <SparklesIcon class="w-4 h-4 text-emerald-600 shrink-0" />
                                            <span>Use free staging subdomain</span>
                                        </div>
                                        <CheckIcon v-if="form.domain_option === 'subdomain'" class="w-4 h-4 text-blue-600 shrink-0" />
                                    </button>
                                </div>

                                <!-- Option 1: Existing Domain Input -->
                                <div v-if="form.domain_option === 'existing'" class="space-y-1.5">
                                    <div class="relative">
                                        <input 
                                            v-model="form.domain_name"
                                            type="text" 
                                            placeholder="e.g. yourcompany.com" 
                                            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-xs font-mono font-bold text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 transition"
                                            required
                                        />
                                    </div>
                                    <p class="text-[11px] text-slate-500">
                                        You will point your domain's nameservers to <code class="bg-slate-100 px-1.5 py-0.5 rounded text-blue-700 font-bold font-mono">ns1.deeptouchit.com</code> and <code class="bg-slate-100 px-1.5 py-0.5 rounded text-blue-700 font-bold font-mono">ns2.deeptouchit.com</code>.
                                    </p>
                                </div>

                                <!-- Option 2: Free Staging Subdomain -->
                                <div v-if="form.domain_option === 'subdomain'" class="space-y-1.5">
                                    <div class="flex items-center">
                                        <input 
                                            v-model="form.subdomain_prefix"
                                            type="text" 
                                            placeholder="mybrand" 
                                            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-l-lg text-xs font-mono font-bold text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 transition text-right"
                                            required
                                        />
                                        <span class="px-3.5 py-2.5 bg-slate-100 border border-l-0 border-slate-300 rounded-r-lg text-xs font-mono font-bold text-slate-600 shrink-0">
                                            .deeptouchit.com
                                        </span>
                                    </div>
                                    <p class="text-[11px] text-emerald-600 font-medium">
                                        ✓ 100% Free instant address with automatic DNS and SSL certificate.
                                    </p>
                                </div>

                                <!-- Domain Error Banner -->
                                <p v-if="domainError" class="text-xs font-bold text-rose-600 pt-1">
                                    ⚠ {{ domainError }}
                                </p>
                            </div>
                        </div>

                        <!-- Step 2: Personal Information & Billing Address -->
                        <div class="bg-white rounded-xl border border-slate-200 p-5 sm:p-6 shadow-xs space-y-5">
                            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-7 h-7 rounded-lg bg-blue-600 text-white flex items-center justify-center font-black text-xs">
                                        2
                                    </div>
                                    <h2 class="text-sm sm:text-base font-bold text-slate-900">Personal & Billing Address</h2>
                                </div>
                                <span class="text-xs font-semibold text-slate-400">Step 2 of 2</span>
                            </div>

                            <!-- Logged In User Banner -->
                            <div v-if="user" class="p-4 rounded-xl bg-blue-50/80 border border-blue-200 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-blue-600 text-white flex items-center justify-center font-black text-sm">
                                        {{ ((user.name || user.username || 'U')[0]).toUpperCase() }}
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold text-slate-900">Ordering as: {{ user.name || user.username }}</p>
                                        <p class="text-[11px] text-slate-500 font-mono">{{ user.email }}</p>
                                    </div>
                                </div>
                                <span class="text-[10px] font-bold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded">Active Client</span>
                            </div>

                            <!-- Guest Full Billing Address Form -->
                            <div v-else class="space-y-4">
                                <div class="flex items-center justify-between bg-slate-50 p-3 rounded-xl border border-slate-200 text-xs">
                                    <span class="text-slate-600">Already registered with us?</span>
                                    <Link :href="route('login', { plan: currentPlan.slug })" class="font-bold text-blue-600 hover:text-blue-700 underline">
                                        Sign In to Existing Account
                                    </Link>
                                </div>

                                <!-- Personal Info -->
                                <div>
                                    <h4 class="text-xs font-black uppercase tracking-wider text-slate-400 flex items-center gap-1.5 mb-2.5">
                                        <UserIcon class="w-3.5 h-3.5 text-blue-600" />
                                        <span>Personal Information</span>
                                    </h4>
                                    
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-xs font-bold text-slate-700 mb-1">First Name <span class="text-rose-500">*</span></label>
                                            <input 
                                                v-model="form.first_name"
                                                type="text" 
                                                placeholder="e.g. Salzar"
                                                class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-blue-600 focus:bg-white focus:ring-2 focus:ring-blue-600/20 transition"
                                                required
                                            />
                                            <p v-if="form.errors.first_name" class="text-xs font-bold text-rose-600 mt-1">{{ form.errors.first_name }}</p>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-bold text-slate-700 mb-1">Last Name <span class="text-rose-500">*</span></label>
                                            <input 
                                                v-model="form.last_name"
                                                type="text" 
                                                placeholder="e.g. Sabu"
                                                class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-blue-600 focus:bg-white focus:ring-2 focus:ring-blue-600/20 transition"
                                                required
                                            />
                                            <p v-if="form.errors.last_name" class="text-xs font-bold text-rose-600 mt-1">{{ form.errors.last_name }}</p>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-bold text-slate-700 mb-1">Email Address <span class="text-rose-500">*</span></label>
                                            <input 
                                                v-model="form.email"
                                                type="email" 
                                                placeholder="e.g. name@domain.com"
                                                class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-blue-600 focus:bg-white focus:ring-2 focus:ring-blue-600/20 transition"
                                                required
                                            />
                                            <p v-if="form.errors.email" class="text-xs font-bold text-rose-600 mt-1">{{ form.errors.email }}</p>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-bold text-slate-700 mb-1">Phone Number <span class="text-rose-500">*</span></label>
                                            <input 
                                                v-model="form.phone"
                                                type="tel" 
                                                placeholder="e.g. +880 1712 345678"
                                                class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-blue-600 focus:bg-white focus:ring-2 focus:ring-blue-600/20 transition"
                                                required
                                            />
                                            <p v-if="form.errors.phone" class="text-xs font-bold text-rose-600 mt-1">{{ form.errors.phone }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Billing Address -->
                                <div class="pt-2 border-t border-slate-100">
                                    <h4 class="text-xs font-black uppercase tracking-wider text-slate-400 flex items-center gap-1.5 mb-2.5">
                                        <MapPinIcon class="w-3.5 h-3.5 text-blue-600" />
                                        <span>Billing Address</span>
                                    </h4>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div class="sm:col-span-2">
                                            <label class="block text-xs font-bold text-slate-700 mb-1">Company Name <span class="text-slate-400 font-normal">(Optional)</span></label>
                                            <input 
                                                v-model="form.company"
                                                type="text" 
                                                placeholder="e.g. Deeptouch IT Ltd"
                                                class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-blue-600 focus:bg-white focus:ring-2 focus:ring-blue-600/20 transition"
                                            />
                                        </div>

                                        <div class="sm:col-span-2">
                                            <label class="block text-xs font-bold text-slate-700 mb-1">Street Address <span class="text-rose-500">*</span></label>
                                            <input 
                                                v-model="form.address"
                                                type="text" 
                                                placeholder="e.g. House 12, Road 4, Banani"
                                                class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-blue-600 focus:bg-white focus:ring-2 focus:ring-blue-600/20 transition"
                                                required
                                            />
                                            <p v-if="form.errors.address" class="text-xs font-bold text-rose-600 mt-1">{{ form.errors.address }}</p>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-bold text-slate-700 mb-1">City <span class="text-rose-500">*</span></label>
                                            <input 
                                                v-model="form.city"
                                                type="text" 
                                                placeholder="e.g. Dhaka"
                                                class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-blue-600 focus:bg-white focus:ring-2 focus:ring-blue-600/20 transition"
                                                required
                                            />
                                            <p v-if="form.errors.city" class="text-xs font-bold text-rose-600 mt-1">{{ form.errors.city }}</p>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-bold text-slate-700 mb-1">State / Region <span class="text-rose-500">*</span></label>
                                            <input 
                                                v-model="form.state"
                                                type="text" 
                                                placeholder="e.g. Dhaka Division"
                                                class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-blue-600 focus:bg-white focus:ring-2 focus:ring-blue-600/20 transition"
                                                required
                                            />
                                            <p v-if="form.errors.state" class="text-xs font-bold text-rose-600 mt-1">{{ form.errors.state }}</p>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-bold text-slate-700 mb-1">ZIP / Post Code <span class="text-rose-500">*</span></label>
                                            <input 
                                                v-model="form.zip_code"
                                                type="text" 
                                                placeholder="e.g. 1213"
                                                class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-blue-600 focus:bg-white focus:ring-2 focus:ring-blue-600/20 transition"
                                                required
                                            />
                                            <p v-if="form.errors.zip_code" class="text-xs font-bold text-rose-600 mt-1">{{ form.errors.zip_code }}</p>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-bold text-slate-700 mb-1">Country <span class="text-rose-500">*</span></label>
                                            <select 
                                                v-model="form.country"
                                                class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:border-blue-600 focus:bg-white focus:ring-2 focus:ring-blue-600/20 transition cursor-pointer"
                                                required
                                            >
                                                <option v-for="c in countries" :key="c" :value="c">{{ c }}</option>
                                            </select>
                                            <p v-if="form.errors.country" class="text-xs font-bold text-rose-600 mt-1">{{ form.errors.country }}</p>
                                        </div>

                                        <div class="sm:col-span-2">
                                            <label class="block text-xs font-bold text-slate-700 mb-1">Account Currency <span class="text-rose-500">*</span></label>
                                            <select 
                                                v-model="form.currency"
                                                class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-900 font-bold focus:outline-none focus:border-blue-600 focus:bg-white focus:ring-2 focus:ring-blue-600/20 transition cursor-pointer"
                                                required
                                            >
                                                <option value="BDT">BDT (৳ Bangladeshi Taka)</option>
                                                <option value="USD">USD ($ US Dollar)</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Account Password -->
                                <div class="pt-2 border-t border-slate-100">
                                    <h4 class="text-xs font-black uppercase tracking-wider text-slate-400 flex items-center gap-1.5 mb-2.5">
                                        <LockClosedIcon class="w-3.5 h-3.5 text-blue-600" />
                                        <span>Account Security</span>
                                    </h4>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-xs font-bold text-slate-700 mb-1">Password <span class="text-rose-500">*</span></label>
                                            <input 
                                                v-model="form.password"
                                                type="password" 
                                                placeholder="••••••••"
                                                class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-blue-600 focus:bg-white focus:ring-2 focus:ring-blue-600/20 transition"
                                                required
                                            />
                                            <p v-if="form.errors.password" class="text-xs font-bold text-rose-600 mt-1">{{ form.errors.password }}</p>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-bold text-slate-700 mb-1">Confirm Password <span class="text-rose-500">*</span></label>
                                            <input 
                                                v-model="form.password_confirmation"
                                                type="password" 
                                                placeholder="••••••••"
                                                class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-blue-600 focus:bg-white focus:ring-2 focus:ring-blue-600/20 transition"
                                                required
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Right Column: Order Summary & Checkout Action -->
                    <div class="lg:col-span-5 sticky top-20 space-y-6">
                        <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-lg space-y-4">
                            <h3 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">
                                Order Summary
                            </h3>

                            <!-- Package Card Inside Summary -->
                            <div class="p-3.5 rounded-lg bg-slate-50 border border-slate-200/80">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-black text-slate-900">{{ currentPlan?.name }}</span>
                                    <span class="text-xs font-bold text-blue-700">
                                        ৳{{ activePrice.toLocaleString() }}
                                    </span>
                                </div>
                                <p class="text-[11px] text-slate-500 capitalize">{{ billingCycle === 'yearly' ? '1 Year Term' : 'Monthly Term' }}</p>
                                
                                <div class="grid grid-cols-2 gap-2 mt-3 pt-2.5 border-t border-slate-200 text-[10px] text-slate-600">
                                    <div class="flex items-center gap-1.5 truncate">
                                        <CheckIcon class="w-3.5 h-3.5 text-emerald-600 shrink-0" />
                                        <span>{{ currentPlan?.disk_space >= 1024 ? (currentPlan.disk_space/1024).toFixed(0) + ' GB NVMe' : currentPlan.disk_space + ' MB' }}</span>
                                    </div>
                                    <div class="flex items-center gap-1.5 truncate">
                                        <CheckIcon class="w-3.5 h-3.5 text-emerald-600 shrink-0" />
                                        <span>Unlimited Bandwidth</span>
                                    </div>
                                    <div class="flex items-center gap-1.5 truncate">
                                        <CheckIcon class="w-3.5 h-3.5 text-emerald-600 shrink-0" />
                                        <span>Free Wildcard SSL</span>
                                    </div>
                                    <div class="flex items-center gap-1.5 truncate">
                                        <CheckIcon class="w-3.5 h-3.5 text-emerald-600 shrink-0" />
                                        <span>Daily Backups</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Cost Breakdown -->
                            <div class="space-y-2 text-xs border-b border-slate-100 pb-4">
                                <div class="flex items-center justify-between text-slate-600">
                                    <span>Hosting Plan Subtotal</span>
                                    <span class="font-bold text-slate-900">৳{{ activePrice.toLocaleString() }}.00</span>
                                </div>
                                <div class="flex items-center justify-between text-slate-600">
                                    <span>Primary Domain</span>
                                    <span v-if="resolvedDomain" class="font-mono font-bold text-blue-700 text-xs">{{ resolvedDomain }}</span>
                                    <span v-else class="text-rose-500 font-bold text-[11px]">Required</span>
                                </div>
                                <div class="flex items-center justify-between text-slate-600">
                                    <span>Payment Method</span>
                                    <span class="font-black uppercase text-xs" :class="selectedGateway === 'bkash' ? 'text-pink-600' : 'text-blue-600'">
                                        {{ selectedGateway }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between text-slate-600">
                                    <span>Instant Cloud Provisioning</span>
                                    <span class="font-bold text-emerald-600">FREE</span>
                                </div>
                                <div class="flex items-center justify-between text-slate-600">
                                    <span>AutoSSL Certificate</span>
                                    <span class="font-bold text-emerald-600">FREE</span>
                                </div>
                            </div>

                            <!-- Total Due Today -->
                            <div class="flex items-center justify-between">
                                <div>
                                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block">Total Due Today</span>
                                    <span class="text-[10px] text-slate-400">All taxes included</span>
                                </div>
                                <span class="text-2xl font-black text-slate-900">
                                    ৳{{ activePrice.toLocaleString() }}.00
                                </span>
                            </div>

                            <!-- Select Payment Method Section (Directly Above Pay Button) -->
                            <div class="pt-3 border-t border-slate-100 space-y-2.5">
                                <div class="flex items-center justify-between">
                                    <label class="block text-xs font-bold text-slate-900">
                                        Select Payment Method <span class="text-rose-500">*</span>
                                    </label>
                                    <span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200/60">Instant Clearance</span>
                                </div>

                                <div class="space-y-2">
                                    <!-- 1. bKash Official -->
                                    <div 
                                        @click="setGateway('bkash')"
                                        :class="[
                                            'p-3 rounded-lg border-2 text-left transition cursor-pointer flex items-center justify-between',
                                            selectedGateway === 'bkash' 
                                                ? 'bg-pink-50/70 border-[#E2136E] ring-2 ring-[#E2136E]/20 shadow-xs' 
                                                : 'bg-white border-slate-200 hover:border-slate-300 hover:bg-slate-50'
                                        ]"
                                    >
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-7 h-7 rounded-md bg-[#E2136E] flex items-center justify-center text-white shrink-0 p-1 shadow-2xs">
                                                <svg viewBox="0 0 100 100" fill="currentColor" class="w-full h-full">
                                                    <path d="M50 8 L90 40 L65 55 L95 85 L45 75 L30 92 L20 60 L5 55 L38 42 Z" fill="#ffffff"/>
                                                </svg>
                                            </div>
                                            <div>
                                                <div class="flex items-center gap-1.5">
                                                    <span class="font-black text-xs text-[#E2136E]">bKash</span>
                                                    <span class="text-[9px] font-bold text-pink-700 bg-pink-100/60 px-1.5 py-0.2 rounded">Direct PGW</span>
                                                </div>
                                                <span class="text-[10px] text-slate-500">Pay via bKash App, PIN or Wallet</span>
                                            </div>
                                        </div>
                                        <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center transition"
                                            :class="selectedGateway === 'bkash' ? 'border-[#E2136E] bg-[#E2136E]' : 'border-slate-300 bg-white'"
                                        >
                                            <div v-if="selectedGateway === 'bkash'" class="w-1.5 h-1.5 rounded-full bg-white"></div>
                                        </div>
                                    </div>

                                    <!-- 2. Nagad Official -->
                                    <div 
                                        @click="setGateway('nagad')"
                                        :class="[
                                            'p-3 rounded-lg border-2 text-left transition cursor-pointer flex items-center justify-between',
                                            selectedGateway === 'nagad' 
                                                ? 'bg-orange-50/70 border-orange-500 ring-2 ring-orange-500/20 shadow-xs' 
                                                : 'bg-white border-slate-200 hover:border-slate-300 hover:bg-slate-50'
                                        ]"
                                    >
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-7 h-7 rounded-md bg-gradient-to-tr from-[#ED1C24] to-[#F7941D] flex items-center justify-center text-white shrink-0 p-1 shadow-2xs">
                                                <svg viewBox="0 0 100 100" fill="currentColor" class="w-full h-full">
                                                    <path d="M50 5 C60 25, 85 40, 85 65 C85 85, 68 95, 50 95 C32 95, 15 85, 15 65 C15 45, 35 25, 50 5 Z M50 35 C42 48, 32 60, 32 70 C32 80, 40 85, 50 85 C60 85, 68 80, 68 70 C68 55, 58 45, 50 35 Z" fill="#ffffff"/>
                                                </svg>
                                            </div>
                                            <div>
                                                <div class="flex items-center gap-1.5">
                                                    <span class="font-black text-xs text-orange-600">Nagad</span>
                                                    <span class="text-[9px] font-bold text-orange-700 bg-orange-100/60 px-1.5 py-0.2 rounded">Merchant</span>
                                                </div>
                                                <span class="text-[10px] text-slate-500">Fast checkout with Nagad wallet</span>
                                            </div>
                                        </div>
                                        <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center transition"
                                            :class="selectedGateway === 'nagad' ? 'border-orange-500 bg-orange-500' : 'border-slate-300 bg-white'"
                                        >
                                            <div v-if="selectedGateway === 'nagad'" class="w-1.5 h-1.5 rounded-full bg-white"></div>
                                        </div>
                                    </div>

                                    <!-- 3. Cards & Banks (SSLCommerz) -->
                                    <div 
                                        @click="setGateway('sslcommerz')"
                                        :class="[
                                            'p-3 rounded-lg border-2 text-left transition cursor-pointer flex items-center justify-between',
                                            selectedGateway === 'sslcommerz' 
                                                ? 'bg-blue-50/70 border-blue-600 ring-2 ring-blue-600/20 shadow-xs' 
                                                : 'bg-white border-slate-200 hover:border-slate-300 hover:bg-slate-50'
                                        ]"
                                    >
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-7 h-7 rounded-md bg-blue-700 flex items-center justify-center text-white shrink-0 shadow-2xs">
                                                <CreditCardIcon class="w-4 h-4" />
                                            </div>
                                            <div>
                                                <div class="flex items-center gap-1.5">
                                                    <span class="font-black text-xs text-blue-900">Cards & 30+ Banks</span>
                                                    <span class="text-[9px] font-bold text-blue-700 bg-blue-100/60 px-1.5 py-0.2 rounded">SSL</span>
                                                </div>
                                                <div class="flex items-center gap-1 mt-0.5">
                                                    <span class="text-[9px] font-bold text-slate-500">VISA</span>
                                                    <span class="text-[9px] text-slate-300">•</span>
                                                    <span class="text-[9px] font-bold text-slate-500">MasterCard</span>
                                                    <span class="text-[9px] text-slate-300">•</span>
                                                    <span class="text-[9px] font-bold text-slate-500">Internet Banking</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center transition"
                                            :class="selectedGateway === 'sslcommerz' ? 'border-blue-600 bg-blue-600' : 'border-slate-300 bg-white'"
                                        >
                                            <div v-if="selectedGateway === 'sslcommerz'" class="w-1.5 h-1.5 rounded-full bg-white"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Dynamic Pay Now Button (Crisp Modern Rounded) -->
                            <button 
                                type="submit"
                                :disabled="form.processing"
                                :class="[
                                    'w-full py-3.5 px-4 rounded-lg text-white font-black text-sm flex items-center justify-center gap-2 shadow-md hover:shadow-lg transition cursor-pointer disabled:opacity-50',
                                    selectedGateway === 'bkash'
                                        ? 'bg-[#E2136E] hover:bg-[#c90f61]'
                                        : (selectedGateway === 'nagad' ? 'bg-orange-600 hover:bg-orange-700' : 'bg-blue-600 hover:bg-blue-700')
                                ]"
                            >
                                <span v-if="form.processing">Connecting to {{ selectedGateway }}...</span>
                                <span v-else class="flex items-center gap-2">
                                    Pay ৳{{ activePrice.toLocaleString() }} with {{ selectedGateway.toUpperCase() }}
                                    <ArrowRightIcon class="w-4 h-4" />
                                </span>
                            </button>

                            <!-- Trust Badges -->
                            <div class="pt-2 border-t border-slate-100 space-y-2.5 text-[11px] text-slate-500">
                                <div class="flex items-center gap-2">
                                    <ShieldCheckIcon class="w-4 h-4 text-emerald-600 shrink-0" />
                                    <span>30-Day Money Back Guarantee</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <BoltIcon class="w-4 h-4 text-blue-600 shrink-0" />
                                    <span>Instant Automated Server Setup</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <LockClosedIcon class="w-4 h-4 text-slate-400 shrink-0" />
                                    <span>256-Bit Encrypted Secure Checkout</span>
                                </div>
                            </div>
                        </div>
                    </div>

                </form>
            </div>
        </section>

        <PublicFooter />
    </div>
</template>
