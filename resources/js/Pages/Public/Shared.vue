<script setup>
import { ref, computed } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import PublicNavbar from '@/Components/Public/PublicNavbar.vue'
import PublicFooter from '@/Components/Public/PublicFooter.vue'
import PricingCard from '@/Components/Public/PricingCard.vue'
import DatacenterMap from '@/Components/Public/DatacenterMap.vue'
import {
    ServerIcon,
    BoltIcon,
    ShieldCheckIcon,
    CpuChipIcon,
    SparklesIcon,
    CheckCircleIcon,
    ArrowRightIcon,
    GlobeAltIcon,
    CircleStackIcon,
    LockClosedIcon,
    ClockIcon,
    QuestionMarkCircleIcon,
    ChevronDownIcon,
    ArrowsRightLeftIcon,
    CheckIcon,
    MinusIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    plans: {
        type: Array,
        default: () => []
    }
})

const page = usePage()
const currentCurrency = ref('BDT')
const isYearly = ref(false)
const selectedCategory = ref('all')
const openFaq = ref(0)

// Dynamic Plans from Controller or Inertia Shared Props
const allPlans = computed(() => {
    if (props.plans && props.plans.length > 0) {
        return props.plans
    }
    if (page.props.hosting_plans && page.props.hosting_plans.length > 0) {
        return page.props.hosting_plans
    }
    return []
})

// Filtered Plans based on selected category tab
const filteredPlans = computed(() => {
    if (selectedCategory.value === 'all') {
        return allPlans.value
    }
    if (selectedCategory.value === 'starter') {
        return allPlans.value.filter(p => Number(p.price_monthly || p.priceMonthlyBdt || 0) <= 199)
    }
    if (selectedCategory.value === 'business') {
        return allPlans.value.filter(p => {
            const price = Number(p.price_monthly || p.priceMonthlyBdt || 0)
            return price > 199 && price <= 599
        })
    }
    if (selectedCategory.value === 'enterprise') {
        return allPlans.value.filter(p => Number(p.price_monthly || p.priceMonthlyBdt || 0) > 599)
    }
    return allPlans.value
})

const faqs = [
    {
        q: 'What is BDIX Shared Hosting and why is it faster in Bangladesh?',
        a: 'BDIX (Bangladesh Internet Exchange) routes domestic website traffic directly through local ISPs in Bangladesh, delivering near-zero latency (<5ms) and blazing fast page loading speeds compared to overseas routing.'
    },
    {
        q: 'Can I upgrade or downgrade my hosting plan at any time?',
        a: 'Yes! You can instantly upgrade your hosting package or scale your disk space and bandwidth directly from your client control panel with zero downtime.'
    },
    {
        q: 'Do you offer free website migration from cPanel or another host?',
        a: 'Yes, our technical team provides 100% free website and database migration with zero downtime. Simply open a support ticket or contact our WhatsApp helpline.'
    },
    {
        q: 'Are SSL certificates and automated daily backups included?',
        a: 'Yes! Every shared hosting plan comes with automated Let\'s Encrypt SSL certificates for all your hosted domains and automated daily off-site cloud backups.'
    },
    {
        q: 'Which PHP versions and web technologies are supported?',
        a: 'Our servers support Multi-PHP switching from PHP 8.1, 8.2, 8.3 to 8.5 with custom php.ini extensions, OPcache, Redis caching, Node.js, and MySQL 8.'
    }
]

const technicalFeatures = [
    {
        icon: BoltIcon,
        title: 'PCIe 4.0 NVMe SSD Storage',
        desc: 'Enterprise high-IOPS NVMe solid-state drives delivering up to 10x faster read/write speeds over traditional SSDs.'
    },
    {
        icon: GlobeAltIcon,
        title: '10 Gbps BDIX Connectivity',
        desc: 'Direct peering with all Bangladeshi broadband and mobile networks for instantaneous local browsing.'
    },
    {
        icon: ShieldCheckIcon,
        title: 'CloudLinux & CageFS Security',
        desc: 'Complete tenant isolation preventing resource hogging, unauthorized cross-account access, and malicious scripts.'
    },
    {
        icon: CircleStackIcon,
        title: 'Automated Daily Backups',
        desc: 'Daily snapshots stored on isolated off-site S3 storage, ready for 1-click restoration at any moment.'
    },
    {
        icon: CpuChipIcon,
        title: 'Multi-PHP 8.1 - 8.5 Support',
        desc: 'Switch between modern PHP versions per-site with customizable extensions, memory limits, and OPcache.'
    },
    {
        icon: LockClosedIcon,
        title: 'Free Automated SSL Certificates',
        desc: 'Automated issuance and renewal of Let\'s Encrypt Wildcard SSL certificates for all your active domains.'
    }
]
</script>

<template>
    <Head title="Shared NVMe Cloud Hosting - DeepTouch Host" />

    <div class="min-h-screen bg-slate-50/60 text-slate-800 font-sans antialiased">
        <PublicNavbar :current-currency="currentCurrency" @currency-change="c => currentCurrency = c" />

        <!-- Header Hero Section (Light) -->
        <section class="py-10 lg:py-14 border-b border-slate-200 bg-white relative overflow-hidden">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-blue-50 border border-blue-200 text-blue-700 text-[11px] font-bold uppercase tracking-wider mb-3">
                    <ServerIcon class="w-3.5 h-3.5" />
                    <span>BDIX NVMe Cloud Hosting</span>
                </div>
                
                <h1 class="text-2xl sm:text-4xl font-black text-slate-900 tracking-tight leading-tight">
                    Ultra-Fast <span class="text-blue-600">BDIX Shared Hosting</span>
                </h1>
                
                <p class="mt-2.5 text-xs sm:text-sm text-slate-600 max-w-2xl mx-auto leading-relaxed">
                    Powered by enterprise PCIe 4.0 NVMe storage, direct 10Gbps BDIX connectivity, isolated CloudLinux containers, and 24/7 support.
                </p>

                <!-- Monthly / Yearly Billing Toggle -->
                <div class="mt-6 inline-flex items-center bg-slate-100 p-1 rounded-xl border border-slate-200 shadow-xs">
                    <button
                        type="button"
                        @click="isYearly = false"
                        :class="[!isYearly ? 'bg-white text-blue-600 font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900', 'px-3.5 py-1.5 rounded-lg text-xs transition cursor-pointer']"
                    >
                        Monthly
                    </button>
                    <button
                        type="button"
                        @click="isYearly = true"
                        :class="[isYearly ? 'bg-white text-blue-600 font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900', 'px-3.5 py-1.5 rounded-lg text-xs transition flex items-center gap-1.5 cursor-pointer']"
                    >
                        <span>Yearly</span>
                        <span class="px-1.5 py-0.2 bg-emerald-100 text-emerald-800 text-[9px] font-bold rounded-full">Save ~20%</span>
                    </button>
                </div>

                <!-- Category Filtering Tabs -->
                <div v-if="allPlans.length > 3" class="mt-6 flex flex-wrap items-center justify-center gap-1.5">
                    <button
                        type="button"
                        @click="selectedCategory = 'all'"
                        :class="[selectedCategory === 'all' ? 'bg-blue-600 text-white font-bold' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200', 'px-3 py-1 rounded-md text-xs transition cursor-pointer shadow-xs']"
                    >
                        All ({{ allPlans.length }})
                    </button>
                    <button
                        type="button"
                        @click="selectedCategory = 'starter'"
                        :class="[selectedCategory === 'starter' ? 'bg-blue-600 text-white font-bold' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200', 'px-3 py-1 rounded-md text-xs transition cursor-pointer shadow-xs']"
                    >
                        Starter & Personal
                    </button>
                    <button
                        type="button"
                        @click="selectedCategory = 'business'"
                        :class="[selectedCategory === 'business' ? 'bg-blue-600 text-white font-bold' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200', 'px-3 py-1 rounded-md text-xs transition cursor-pointer shadow-xs']"
                    >
                        Business & eCommerce
                    </button>
                    <button
                        type="button"
                        @click="selectedCategory = 'enterprise'"
                        :class="[selectedCategory === 'enterprise' ? 'bg-blue-600 text-white font-bold' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200', 'px-3 py-1 rounded-md text-xs transition cursor-pointer shadow-xs']"
                    >
                        Agency & Enterprise
                    </button>
                </div>
            </div>
        </section>

        <!-- Dynamic Plans Showcase Grid (4 Columns) -->
        <section class="py-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div v-if="filteredPlans.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
                    <PricingCard
                        v-for="plan in filteredPlans"
                        :key="plan.id || plan.slug"
                        :plan="plan"
                        :is-yearly="isYearly"
                        :current-currency="currentCurrency"
                    />
                </div>

                <div v-else class="text-center py-12 bg-white rounded-2xl border border-slate-200 shadow-sm">
                    <ServerIcon class="w-10 h-10 text-slate-400 mx-auto mb-2" />
                    <h3 class="text-base font-bold text-slate-800">No plans currently available in this category</h3>
                    <p class="text-xs text-slate-500 mt-1">Please select "All" or contact our sales team.</p>
                </div>
            </div>
        </section>

        <!-- Technical Infrastructure Features Section (Light) -->
        <section class="py-12 bg-white border-t border-b border-slate-200">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto mb-10">
                    <h2 class="text-xl sm:text-3xl font-black text-slate-900 tracking-tight">
                        Built on Enterprise-Grade Cloud Architecture
                    </h2>
                    <p class="mt-2 text-xs sm:text-sm text-slate-600">
                        Every hosting account is provisioned with high-availability server hardware and dedicated resource quotas.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div
                        v-for="(feat, idx) in technicalFeatures"
                        :key="idx"
                        class="p-5 rounded-xl bg-slate-50 border border-slate-200/80 hover:border-blue-400/50 hover:bg-white transition group shadow-xs"
                    >
                        <div class="w-10 h-10 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center mb-3 group-hover:bg-blue-600 group-hover:text-white transition">
                            <component :is="feat.icon" class="w-5 h-5" />
                        </div>
                        <h3 class="text-sm font-bold text-slate-900 mb-1">{{ feat.title }}</h3>
                        <p class="text-xs text-slate-600 leading-relaxed">{{ feat.desc }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Datacenter Section -->
        <section class="py-12 bg-slate-50/50">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <DatacenterMap />
            </div>
        </section>

        <!-- FAQ Section (Light) -->
        <section class="py-12 bg-white border-t border-slate-200">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-10">
                    <h2 class="text-xl sm:text-3xl font-black text-slate-900 tracking-tight">
                        Frequently Asked Questions
                    </h2>
                    <p class="mt-2 text-xs sm:text-sm text-slate-600">
                        Everything you need to know about our high-speed BDIX shared hosting services.
                    </p>
                </div>

                <div class="space-y-3">
                    <div
                        v-for="(faq, idx) in faqs"
                        :key="idx"
                        class="border border-slate-200 rounded-xl bg-slate-50 overflow-hidden transition"
                    >
                        <button
                            type="button"
                            @click="openFaq = openFaq === idx ? null : idx"
                            class="w-full p-4 text-left flex items-center justify-between gap-4 font-bold text-xs sm:text-sm text-slate-800 hover:text-blue-600 transition cursor-pointer"
                        >
                            <span>{{ faq.q }}</span>
                            <ChevronDownIcon
                                :class="[
                                    'w-4 h-4 text-slate-500 shrink-0 transition-transform duration-200',
                                    openFaq === idx ? 'rotate-180 text-blue-600' : ''
                                ]"
                            />
                        </button>
                        <div
                            v-show="openFaq === idx"
                            class="px-4 pb-4 text-xs text-slate-600 leading-relaxed border-t border-slate-200 pt-2.5 bg-white"
                        >
                            {{ faq.a }}
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Bottom Call To Action (Light Gradient) -->
        <section class="py-12 bg-gradient-to-r from-blue-600 via-indigo-600 to-blue-700 text-white text-center">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-xl sm:text-3xl font-black text-white tracking-tight">
                    Ready to launch your website on ultra-fast BDIX NVMe?
                </h2>
                <p class="mt-2 text-xs sm:text-sm text-blue-100">
                    Get started in less than 60 seconds with instant automated provisioning and a 30-day money-back guarantee.
                </p>
                <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                    <Link
                        :href="route('register')"
                        class="px-5 py-2.5 bg-white hover:bg-slate-100 text-blue-600 text-xs font-black uppercase tracking-wider rounded-lg shadow-md flex items-center gap-1.5 transition cursor-pointer"
                    >
                        <span>Choose Your Plan Now</span>
                        <ArrowRightIcon class="w-3.5 h-3.5" />
                    </Link>
                    <Link
                        :href="route('public.contact')"
                        class="px-5 py-2.5 bg-blue-700/80 hover:bg-blue-700 text-white border border-blue-400/40 text-xs font-bold uppercase tracking-wider rounded-lg transition"
                    >
                        Contact Sales Team
                    </Link>
                </div>
            </div>
        </section>

        <PublicFooter />
    </div>
</template>
