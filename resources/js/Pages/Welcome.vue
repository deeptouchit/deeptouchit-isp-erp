<script setup>
import { ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import PublicNavbar from '@/Components/Public/PublicNavbar.vue'
import PublicFooter from '@/Components/Public/PublicFooter.vue'
import DomainSearchBox from '@/Components/Public/DomainSearchBox.vue'
import PricingCard from '@/Components/Public/PricingCard.vue'
import DatacenterMap from '@/Components/Public/DatacenterMap.vue'
import {
    ServerIcon,
    ShieldCheckIcon,
    BoltIcon,
    CircleStackIcon,
    GlobeAltIcon,
    CpuChipIcon,
    ArrowRightIcon,
    CheckCircleIcon,
    SparklesIcon,
    LockClosedIcon,
    ClockIcon,
    QuestionMarkCircleIcon,
    ChevronDownIcon,
    ArrowsRightLeftIcon,
    UserGroupIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    laravelVersion: String,
    phpVersion: String,
})

const currentCurrency = ref('BDT')
const isYearly = ref(false)
const openFaq = ref(0)

const plans = [
    {
        name: 'Single Starter',
        slug: 'starter',
        desc: 'Ideal for small personal websites, blogs, and startup portfolios.',
        priceMonthlyBdt: 199,
        priceYearlyBdt: 1990,
        priceMonthlyUsd: 2.99,
        priceYearlyUsd: 24.99,
        popular: false,
        features: [
            '1 Hosted Domain / Website',
            '2,048 MB Pure NVMe SSD Storage',
            '25,600 MB BDIX High-Speed Bandwidth',
            'Free Unlimited AutoSSL Certificate',
            '5 Business Email Accounts & 5 Subdomains',
            '1 MySQL 8 Database with phpMyAdmin',
            'Multi-PHP 8.1, 8.2, 8.3 & 8.5 Support',
            'Daily Automated Server Backups',
            '99.99% Uptime SLA Commitment'
        ]
    },
    {
        name: 'Premium Cloud',
        slug: 'professional',
        desc: 'Best solution for growing businesses, corporate sites, and active shops.',
        priceMonthlyBdt: 499,
        priceYearlyBdt: 4990,
        priceMonthlyUsd: 5.99,
        priceYearlyUsd: 49.99,
        popular: true,
        features: [
            '5 Hosted Domains / Websites',
            '10,240 MB High-IOPS NVMe SSD',
            '102,400 MB BDIX 10Gbps Bandwidth',
            'Free Wildcard SSL for all Domains',
            'Unlimited Business Emails & Subdomains',
            '10 MySQL Databases with phpMyAdmin',
            'Isolated Per-User PHP-FPM Resource Pool',
            'Cloud File Manager (Zip/Unzip/Direct Edit)',
            '24/7 Priority Hotline & WhatsApp Support'
        ]
    },
    {
        name: 'Business Enterprise',
        slug: 'enterprise',
        desc: 'Maximum power and isolated compute for busy eCommerce and agencies.',
        priceMonthlyBdt: 999,
        priceYearlyBdt: 9990,
        priceMonthlyUsd: 11.99,
        priceYearlyUsd: 99.99,
        popular: false,
        features: [
            'Unlimited Hosted Domains',
            '30,720 MB Ultra-Fast NVMe SSD Storage',
            'Unlimited High-Speed BDIX Bandwidth',
            'Free Automated Wildcard SSL & DDoS Shield',
            'Unlimited Business Emails & Mailboxes',
            'Unlimited MySQL Databases',
            'Custom PHP.INI & Git Deploy Hook',
            'Automated Cloud S3 Offsite Backups',
            'Dedicated Priority SLA & Account Manager'
        ]
    }
]

const featuresList = [
    {
        icon: BoltIcon,
        title: 'PCIe 4.0 NVMe SSD Storage',
        desc: 'All our servers utilize ultra-high IOPS enterprise NVMe drives, providing up to 10x faster read/write speeds over standard SSDs.'
    },
    {
        icon: GlobeAltIcon,
        title: '10Gbps BDIX Low-Latency Peering',
        desc: 'Direct peering with Bangladesh Internet Exchange (BDIX) ensures lightning-fast 1-5ms response times for all Bangladeshi users.'
    },
    {
        icon: ShieldCheckIcon,
        title: 'Enterprise Multi-Layer Firewall & DDoS Shield',
        desc: 'Real-time AI-powered WAF, fail2ban brute-force protection, and IP blacklist monitoring to keep your web applications secure.'
    },
    {
        icon: CpuChipIcon,
        title: 'Multi-PHP Manager (7.4 to 8.5)',
        desc: 'Easily switch PHP versions per domain in 1 click and customize PHP INI limits, OPcache, and extensions with ease.'
    },
    {
        icon: LockClosedIcon,
        title: 'Free Automated SSL Certificates',
        desc: 'Unlimited Let\'s Encrypt SSL certificates automatically issued and renewed for all your primary domains and subdomains.'
    },
    {
        icon: CircleStackIcon,
        title: 'Daily Automated Cloud Backups',
        desc: 'Rest easy with scheduled automated backups stored in secure offsite repositories, allowing 1-click restore at any time.'
    }
]

const faqs = [
    {
        q: 'What is BDIX and why does it make my website faster?',
        a: 'BDIX (Bangladesh Internet Exchange) allows local traffic within Bangladesh to be routed through high-speed domestic internet exchange points rather than traveling through international submarine cables. This reduces latency to just 1-5 milliseconds, resulting in instantaneous page loading.'
    },
    {
        q: 'Can I pay using bKash, Nagad, Rocket or Bangladeshi cards?',
        a: 'Yes! We support all major local payment methods including bKash, Nagad, Rocket, Upay, Visa, Mastercard, AMEX, and direct Bank Transfers with instant automated service activation.'
    },
    {
        q: 'Do you offer free website migration from cPanel or other hosts?',
        a: 'Absolutely. Our technical support team will migrate your existing websites, databases, and emails from your current hosting provider completely free of charge with zero downtime.'
    },
    {
        q: 'Can I upgrade my hosting plan later as my business grows?',
        a: 'Yes, you can upgrade your hosting plan at any time through our client portal with just one click. Your files, databases, and settings will remain completely intact.'
    },
    {
        q: 'Is there a money-back guarantee?',
        a: 'We offer an unconditional 30-day money-back guarantee on all our shared cloud hosting packages. If you are not 100% satisfied, we will issue a full refund.'
    }
]

const setCurrency = (curr) => {
    currentCurrency.value = curr
}
</script>

<template>
    <Head title="DeepTouch Host - Ultra-Fast BDIX NVMe Cloud Hosting & Domain Services" />

    <div class="min-h-screen bg-slate-50/60 text-slate-800 selection:bg-blue-600 selection:text-white font-sans antialiased">
        <!-- Global Public Header Navbar -->
        <PublicNavbar
            :current-currency="currentCurrency"
            :can-login="canLogin"
            :can-register="canRegister"
            @currency-change="setCurrency"
        />

        <!-- Hero Section (Light) -->
        <section class="relative overflow-hidden pt-10 pb-16 lg:pt-16 lg:pb-20 bg-white border-b border-slate-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                <!-- Promo Ribbon -->
                <div class="flex justify-center mb-4">
                    <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-blue-50 border border-blue-200 text-blue-700 text-xs font-bold uppercase tracking-wider">
                        <SparklesIcon class="w-4 h-4 text-blue-600" />
                        <span>Save up to 70% on High-Speed NVMe Cloud Hosting</span>
                    </div>
                </div>

                <!-- Main Hero Headline & Subtitle -->
                <div class="text-center max-w-4xl mx-auto">
                    <h1 class="text-3xl sm:text-5xl font-black text-slate-900 tracking-tight leading-tight">
                        Ultra-Fast <span class="text-blue-600">BDIX NVMe Cloud Hosting</span> for High-Performance Websites
                    </h1>
                    <p class="mt-3.5 text-xs sm:text-sm lg:text-base text-slate-600 max-w-2xl mx-auto leading-relaxed">
                        Supercharge your online business with PCIe 4.0 NVMe storage, 1-5ms BDIX connectivity, Multi-PHP engines, and 24/7 dedicated engineering support.
                    </p>
                </div>

                <!-- Live Domain Search Engine Component -->
                <div class="mt-8">
                    <DomainSearchBox :current-currency="currentCurrency" />
                </div>

                <!-- Trust Badges Row -->
                <div class="mt-8 flex flex-wrap items-center justify-center gap-6 sm:gap-8 text-xs text-slate-600 font-medium">
                    <div class="flex items-center gap-1.5">
                        <CheckCircleIcon class="w-4 h-4 text-emerald-600" />
                        <span>99.99% Uptime Guarantee</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <CheckCircleIcon class="w-4 h-4 text-emerald-600" />
                        <span>30-Day Money-Back Guarantee</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <CheckCircleIcon class="w-4 h-4 text-emerald-600" />
                        <span>Free Instant Website Migration</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <CheckCircleIcon class="w-4 h-4 text-emerald-600" />
                        <span>24/7 Local Hotline & WhatsApp</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Pricing & Plans Section (Light) -->
        <section class="py-14 lg:py-20 relative bg-slate-50/60">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Section Header -->
                <div class="text-center max-w-3xl mx-auto mb-10">
                    <span class="text-xs font-bold uppercase tracking-wider text-blue-600">Tailored Hosting Plans</span>
                    <h2 class="text-2xl sm:text-4xl font-black text-slate-900 tracking-tight mt-1">
                        Choose the Perfect Plan for Your Growth
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-600 mt-1.5">
                        All plans include high-speed NVMe storage, free SSL certificates, automated daily backups, and isolated user resource environments.
                    </p>

                    <!-- Monthly / Annually Billing Switcher -->
                    <div class="mt-6 inline-flex items-center bg-slate-200/80 p-1 rounded-xl border border-slate-300">
                        <button
                            type="button"
                            @click="isYearly = false"
                            :class="[
                                'px-4 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer',
                                !isYearly ? 'bg-white text-blue-600 shadow-xs' : 'text-slate-600 hover:text-slate-900'
                            ]"
                        >
                            Monthly Billing
                        </button>
                        <button
                            type="button"
                            @click="isYearly = true"
                            :class="[
                                'px-4 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5 cursor-pointer',
                                isYearly ? 'bg-white text-blue-600 shadow-xs' : 'text-slate-600 hover:text-slate-900'
                            ]"
                        >
                            <span>Annual Billing</span>
                            <span class="px-1.5 py-0.2 rounded-full bg-emerald-100 text-emerald-800 text-[9px] font-bold uppercase">Save 20%</span>
                        </button>
                    </div>
                </div>

                <!-- 3-Column Plan Cards Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <PricingCard
                        v-for="plan in plans"
                        :key="plan.slug"
                        :plan="plan"
                        :is-yearly="isYearly"
                        :current-currency="currentCurrency"
                    />
                </div>
            </div>
        </section>

        <!-- Datacenter & BDIX Network Section -->
        <section class="py-12 bg-white border-y border-slate-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <DatacenterMap />
            </div>
        </section>

        <!-- Server Infrastructure & Tech Stack Highlights (Light) -->
        <section class="py-14 lg:py-20 bg-slate-50/60">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto mb-12">
                    <span class="text-xs font-bold uppercase tracking-wider text-blue-600">Next-Gen Architecture</span>
                    <h2 class="text-2xl sm:text-4xl font-black text-slate-900 tracking-tight mt-1">
                        Built for Speed, Security & Reliability
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-600 mt-1.5">
                        Our cloud platform is engineered using modern server engines and isolation layers to guarantee continuous performance.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div
                        v-for="(feat, idx) in featuresList"
                        :key="idx"
                        class="p-5 rounded-xl bg-white border border-slate-200 hover:border-blue-400/50 hover:shadow-sm transition space-y-2.5"
                    >
                        <div class="w-10 h-10 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center">
                            <component :is="feat.icon" class="w-5 h-5" />
                        </div>
                        <h3 class="text-sm sm:text-base font-bold text-slate-900">{{ feat.title }}</h3>
                        <p class="text-xs text-slate-600 leading-relaxed">{{ feat.desc }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Comprehensive Specification Comparison Table (Light) -->
        <section class="py-14 bg-white border-t border-slate-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto mb-10">
                    <h2 class="text-xl sm:text-3xl font-black text-slate-900 tracking-tight">
                        Detailed Feature Comparison
                    </h2>
                    <p class="text-xs text-slate-600 mt-1">Full transparent technical breakdown of all hosting parameters</p>
                </div>

                <div class="bg-white border border-slate-200 rounded-xl overflow-x-auto shadow-xs">
                    <table class="w-full text-left text-xs whitespace-nowrap">
                        <thead class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200">
                            <tr>
                                <th class="py-3.5 px-5">Specification</th>
                                <th class="py-3.5 px-5 text-center">Single Starter</th>
                                <th class="py-3.5 px-5 text-center text-blue-600">Premium Cloud</th>
                                <th class="py-3.5 px-5 text-center">Business Enterprise</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <tr>
                                <td class="py-3 px-5 font-semibold text-slate-900">Hosted Websites</td>
                                <td class="py-3 px-5 text-center">1 Website</td>
                                <td class="py-3 px-5 text-center font-bold text-blue-600">5 Websites</td>
                                <td class="py-3 px-5 text-center">Unlimited</td>
                            </tr>
                            <tr>
                                <td class="py-3 px-5 font-semibold text-slate-900">NVMe SSD Storage</td>
                                <td class="py-3 px-5 text-center">2 GB NVMe</td>
                                <td class="py-3 px-5 text-center font-bold text-blue-600">10 GB NVMe</td>
                                <td class="py-3 px-5 text-center">30 GB NVMe</td>
                            </tr>
                            <tr>
                                <td class="py-3 px-5 font-semibold text-slate-900">BDIX 10Gbps Bandwidth</td>
                                <td class="py-3 px-5 text-center">25 GB</td>
                                <td class="py-3 px-5 text-center font-bold text-blue-600">100 GB</td>
                                <td class="py-3 px-5 text-center">Unlimited</td>
                            </tr>
                            <tr>
                                <td class="py-3 px-5 font-semibold text-slate-900">Free Wildcard SSL</td>
                                <td class="py-3 px-5 text-center text-emerald-600 font-bold">✓ Included</td>
                                <td class="py-3 px-5 text-center text-emerald-600 font-bold">✓ Included</td>
                                <td class="py-3 px-5 text-center text-emerald-600 font-bold">✓ Included</td>
                            </tr>
                            <tr>
                                <td class="py-3 px-5 font-semibold text-slate-900">Database Engine</td>
                                <td class="py-3 px-5 text-center">1 MySQL 8.0</td>
                                <td class="py-3 px-5 text-center font-bold text-blue-600">10 MySQL 8.0</td>
                                <td class="py-3 px-5 text-center">Unlimited MySQL</td>
                            </tr>
                            <tr>
                                <td class="py-3 px-5 font-semibold text-slate-900">PHP Version Selector</td>
                                <td class="py-3 px-5 text-center">7.4, 8.1, 8.2, 8.3, 8.5</td>
                                <td class="py-3 px-5 text-center font-bold text-blue-600">7.4, 8.1, 8.2, 8.3, 8.5</td>
                                <td class="py-3 px-5 text-center">7.4, 8.1, 8.2, 8.3, 8.5</td>
                            </tr>
                            <tr>
                                <td class="py-3 px-5 font-semibold text-slate-900">Automated Backup Frequency</td>
                                <td class="py-3 px-5 text-center">Daily</td>
                                <td class="py-3 px-5 text-center font-bold text-blue-600">Daily + On-Demand</td>
                                <td class="py-3 px-5 text-center">Real-time + S3 Offsite</td>
                            </tr>
                            <tr>
                                <td class="py-3 px-5 font-semibold text-slate-900">Technical Support Channel</td>
                                <td class="py-3 px-5 text-center">Ticket & Email</td>
                                <td class="py-3 px-5 text-center font-bold text-blue-600">24/7 Hotline & WhatsApp</td>
                                <td class="py-3 px-5 text-center">Dedicated Account Manager</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- Interactive FAQ Accordion (Light) -->
        <section class="py-14 bg-slate-50/60 border-t border-slate-200">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-10">
                    <span class="text-xs font-bold uppercase tracking-wider text-blue-600">Got Questions?</span>
                    <h2 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight mt-1">
                        Frequently Asked Questions
                    </h2>
                </div>

                <div class="space-y-3">
                    <div
                        v-for="(faq, idx) in faqs"
                        :key="idx"
                        class="border border-slate-200 rounded-xl overflow-hidden bg-white shadow-xs"
                    >
                        <button
                            type="button"
                            @click="openFaq = openFaq === idx ? null : idx"
                            class="w-full p-4 text-left flex items-center justify-between gap-4 font-bold text-xs sm:text-sm text-slate-800 hover:text-blue-600 transition cursor-pointer"
                        >
                            <span>{{ faq.q }}</span>
                            <ChevronDownIcon
                                :class="openFaq === idx ? 'rotate-180 text-blue-600' : 'text-slate-400'"
                                class="w-4 h-4 shrink-0 transition-transform duration-200"
                            />
                        </button>
                        <div
                            v-show="openFaq === idx"
                            class="p-4 pt-0 text-xs text-slate-600 leading-relaxed border-t border-slate-100"
                        >
                            {{ faq.a }}
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Final Call-To-Action Banner (Light Gradient) -->
        <section class="py-12 bg-gradient-to-r from-blue-600 via-indigo-600 to-blue-700 text-white border-t border-slate-200">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-4">
                <h2 class="text-2xl sm:text-3xl font-black text-white tracking-tight">
                    Ready to Experience High-Speed BDIX Cloud Hosting?
                </h2>
                <p class="text-xs sm:text-sm text-blue-100 max-w-xl mx-auto">
                    Get your domain and hosting online in under 60 seconds with instant automated provisioning and zero setup fees.
                </p>

                <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-2">
                    <Link
                        :href="route('register')"
                        class="w-full sm:w-auto px-6 py-2.5 bg-white hover:bg-slate-100 text-blue-600 font-bold text-xs uppercase tracking-wider rounded-lg shadow-md flex items-center justify-center gap-1.5 cursor-pointer transition"
                    >
                        <span>Launch Your Website Now</span>
                        <ArrowRightIcon class="w-3.5 h-3.5" />
                    </Link>

                    <a
                        :href="'https://wa.me/' + (supportWhatsapp || '8801977799300')"
                        target="_blank"
                        class="w-full sm:w-auto px-6 py-2.5 bg-blue-700/80 hover:bg-blue-700 text-white border border-blue-400/40 font-bold text-xs rounded-lg flex items-center justify-center gap-1.5 transition"
                    >
                        <span>Chat on WhatsApp</span>
                    </a>
                </div>
            </div>
        </section>

        <!-- Global Public Footer -->
        <PublicFooter />
    </div>
</template>
