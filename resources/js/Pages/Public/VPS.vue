<script setup>
import { ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import PublicNavbar from '@/Components/Public/PublicNavbar.vue'
import PublicFooter from '@/Components/Public/PublicFooter.vue'
import PricingCard from '@/Components/Public/PricingCard.vue'
import DatacenterMap from '@/Components/Public/DatacenterMap.vue'
import {
    CpuChipIcon,
    ServerIcon,
    BoltIcon,
    ShieldCheckIcon,
    CommandLineIcon,
    CheckCircleIcon,
    ArrowRightIcon,
    CircleStackIcon,
    GlobeAltIcon,
    LockClosedIcon,
    ChevronDownIcon
} from '@heroicons/vue/24/outline'

const currentCurrency = ref('BDT')
const isYearly = ref(false)
const openFaq = ref(0)

const vpsPlans = [
    {
        name: 'Cloud VPS-1',
        slug: 'vps-1',
        desc: 'Entry-level KVM VPS for development, testing, and microservices.',
        priceMonthlyBdt: 990,
        priceYearlyBdt: 9900,
        priceMonthlyUsd: 8.25,
        priceYearlyUsd: 82.50,
        popular: false,
        features: [
            '1 Dedicated vCPU Core (3.4GHz+)',
            '2,048 MB DDR4 RAM',
            '30 GB NVMe PCIe 4.0 SSD',
            '1,000 GB Monthly Bandwidth',
            '1 Dedicated IPv4 Address',
            'Full Root / SSH Shell Access',
            'Ubuntu, Debian, AlmaLinux, Rocky',
            'Automated Weekly Snapshot Backups'
        ]
    },
    {
        name: 'Cloud VPS-2',
        slug: 'vps-2',
        desc: 'Balanced compute and memory for active production websites & APIs.',
        priceMonthlyBdt: 1890,
        priceYearlyBdt: 18900,
        priceMonthlyUsd: 15.75,
        priceYearlyUsd: 157.50,
        popular: true,
        features: [
            '2 Dedicated vCPU Cores',
            '4,096 MB DDR4 RAM',
            '60 GB NVMe Enterprise SSD',
            '2,000 GB Monthly Bandwidth',
            '1 Dedicated IPv4 + /64 IPv6',
            'Full Root / SSH Shell Access',
            '1-Click OS Reload & VNC Web Console',
            '24/7 Priority Node Monitoring'
        ]
    },
    {
        name: 'Cloud VPS-3',
        slug: 'vps-3',
        desc: 'High-performance cloud node for intensive databases & SaaS apps.',
        priceMonthlyBdt: 3690,
        priceYearlyBdt: 36900,
        priceMonthlyUsd: 30.75,
        priceYearlyUsd: 307.50,
        popular: false,
        features: [
            '4 Dedicated vCPU Cores',
            '8,192 MB DDR4 RAM',
            '120 GB NVMe Enterprise SSD',
            '4,000 GB Monthly Bandwidth',
            '1 Dedicated IPv4 + /64 IPv6',
            'Full Root / SSH Shell Access',
            '1-Click OS Reload & VNC Console',
            'Anti-DDoS Attack Mitigation'
        ]
    }
]

const vpsFeatures = [
    {
        icon: CpuChipIcon,
        title: '100% Dedicated KVM Virtualization',
        desc: 'Hardware-level kernel isolation guarantees your vCPU and RAM resources are always 100% available with zero overselling.'
    },
    {
        icon: BoltIcon,
        title: 'Pure Enterprise NVMe PCIe 4.0',
        desc: 'Ultra-low latency and up to 10x faster disk I/O performance compared to standard SATA SSDs for maximum throughput.'
    },
    {
        icon: CommandLineIcon,
        title: 'Full Root Access & VNC Console',
        desc: 'Complete administrative control with SSH keys, web-based emergency VNC console, and instant OS reinstallation.'
    },
    {
        icon: GlobeAltIcon,
        title: '10 Gbps BDIX & Global Peering',
        desc: 'High-speed local BDIX connectivity with low latency to domestic users alongside robust Tier-1 global transit.'
    },
    {
        icon: ShieldCheckIcon,
        title: 'Enterprise Anti-DDoS Protection',
        desc: 'Automated traffic filtering and scrubbing shields your cloud node from malicious Layer 3, 4, and 7 attacks.'
    },
    {
        icon: CircleStackIcon,
        title: 'Snapshot Backups & Storage',
        desc: 'Create on-demand image snapshots before making major updates and restore your entire system in seconds.'
    }
]

const faqs = [
    {
        q: 'What operating systems can I install on my Cloud VPS?',
        a: 'You can install Ubuntu (24.04 / 22.04 LTS), Debian (12 / 11), AlmaLinux (9 / 8), Rocky Linux 9, or use custom ISO images.'
    },
    {
        q: 'How fast is the VPS provisioning process?',
        a: 'Your VPS is automatically deployed and booted up in under 60 seconds once payment is verified.'
    },
    {
        q: 'Can I scale up my VPS CPU, RAM, or storage later?',
        a: 'Yes! You can instantly upgrade your VPS plan directly from your client control panel with zero data loss.'
    },
    {
        q: 'Do you provide dedicated IPv4 addresses?',
        a: 'Yes, every Cloud VPS comes with a dedicated clean IPv4 address and optional /64 IPv6 subnet.'
    }
]
</script>

<template>
    <Head title="Cloud KVM VPS Servers - DeepTouch Host" />

    <div class="min-h-screen bg-slate-50/60 text-slate-800 font-sans antialiased">
        <PublicNavbar :current-currency="currentCurrency" @currency-change="c => currentCurrency = c" />

        <!-- Header Hero Section (Light) -->
        <section class="py-10 lg:py-14 border-b border-slate-200 bg-white relative overflow-hidden">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-purple-50 border border-purple-200 text-purple-700 text-[11px] font-bold uppercase tracking-wider mb-3">
                    <CpuChipIcon class="w-3.5 h-3.5" />
                    <span>Pure KVM Cloud VPS Servers</span>
                </div>
                
                <h1 class="text-2xl sm:text-4xl font-black text-slate-900 tracking-tight leading-tight">
                    Dedicated Power with <span class="text-purple-600">Cloud KVM VPS</span>
                </h1>
                
                <p class="mt-2.5 text-xs sm:text-sm text-slate-600 max-w-2xl mx-auto leading-relaxed">
                    Guaranteed compute resources, PCIe 4.0 NVMe storage, 10Gbps BDIX networking, and full root access with instant provisioning.
                </p>

                <!-- Monthly / Yearly Billing Switch -->
                <div class="mt-6 inline-flex items-center bg-slate-100 p-1 rounded-xl border border-slate-200 shadow-xs">
                    <button
                        type="button"
                        @click="isYearly = false"
                        :class="[!isYearly ? 'bg-white text-purple-600 font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900', 'px-3.5 py-1.5 rounded-lg text-xs transition cursor-pointer']"
                    >
                        Monthly
                    </button>
                    <button
                        type="button"
                        @click="isYearly = true"
                        :class="[isYearly ? 'bg-white text-purple-600 font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900', 'px-3.5 py-1.5 rounded-lg text-xs transition flex items-center gap-1.5 cursor-pointer']"
                    >
                        <span>Yearly</span>
                        <span class="px-1.5 py-0.2 bg-emerald-100 text-emerald-800 text-[9px] font-bold rounded-full">Save ~20%</span>
                    </button>
                </div>
            </div>
        </section>

        <!-- Pricing Grid (Light) -->
        <section class="py-10">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <PricingCard
                        v-for="plan in vpsPlans"
                        :key="plan.slug"
                        :plan="plan"
                        :is-yearly="isYearly"
                        :current-currency="currentCurrency"
                    />
                </div>
            </div>
        </section>

        <!-- Technical Infrastructure Features Section (Light) -->
        <section class="py-12 bg-white border-t border-b border-slate-200">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto mb-10">
                    <h2 class="text-xl sm:text-3xl font-black text-slate-900 tracking-tight">
                        Engineered for High-Performance Workloads
                    </h2>
                    <p class="mt-2 text-xs sm:text-sm text-slate-600">
                        Enterprise-grade KVM hypervisors with dedicated compute resources and ultra-low latency.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div
                        v-for="(feat, idx) in vpsFeatures"
                        :key="idx"
                        class="p-5 rounded-xl bg-slate-50 border border-slate-200/80 hover:border-purple-400/50 hover:bg-white transition group shadow-xs"
                    >
                        <div class="w-10 h-10 rounded-lg bg-purple-100 text-purple-600 flex items-center justify-center mb-3 group-hover:bg-purple-600 group-hover:text-white transition">
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
                        Cloud VPS FAQs
                    </h2>
                    <p class="mt-2 text-xs sm:text-sm text-slate-600">
                        Common questions and answers regarding our Cloud KVM VPS nodes.
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
                            class="w-full p-4 text-left flex items-center justify-between gap-4 font-bold text-xs sm:text-sm text-slate-800 hover:text-purple-600 transition cursor-pointer"
                        >
                            <span>{{ faq.q }}</span>
                            <ChevronDownIcon
                                :class="[
                                    'w-4 h-4 text-slate-500 shrink-0 transition-transform duration-200',
                                    openFaq === idx ? 'rotate-180 text-purple-600' : ''
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

        <!-- Bottom Call To Action (Light) -->
        <section class="py-12 bg-gradient-to-r from-purple-700 via-indigo-700 to-blue-700 text-white text-center">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-xl sm:text-3xl font-black text-white tracking-tight">
                    Deploy your Cloud VPS in 60 seconds
                </h2>
                <p class="mt-2 text-xs sm:text-sm text-purple-100">
                    High-performance hardware, full root access, and 99.99% uptime SLA.
                </p>
                <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                    <Link
                        :href="route('register')"
                        class="px-5 py-2.5 bg-white hover:bg-slate-100 text-purple-700 text-xs font-black uppercase tracking-wider rounded-lg shadow-md flex items-center gap-1.5 transition cursor-pointer"
                    >
                        <span>Deploy Cloud VPS</span>
                        <ArrowRightIcon class="w-3.5 h-3.5" />
                    </Link>
                    <Link
                        :href="route('public.contact')"
                        class="px-5 py-2.5 bg-purple-800/80 hover:bg-purple-800 text-white border border-purple-400/40 text-xs font-bold uppercase tracking-wider rounded-lg transition"
                    >
                        Contact Technical Sales
                    </Link>
                </div>
            </div>
        </section>

        <PublicFooter />
    </div>
</template>
