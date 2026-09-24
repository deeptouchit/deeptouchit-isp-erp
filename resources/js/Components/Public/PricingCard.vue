<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { CheckIcon, SparklesIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    plan: {
        type: Object,
        required: true
    },
    isYearly: {
        type: Boolean,
        default: false
    },
    currentCurrency: {
        type: String,
        default: 'BDT'
    }
})

const isPopular = computed(() => {
    return props.plan.popular || props.plan.slug === 'bdix-standard-business' || props.plan.slug === 'professional' || props.plan.slug === 'vps-2'
})

const planDescription = computed(() => {
    return props.plan.description || props.plan.desc || 'Optimized for high-speed NVMe storage and maximum reliability.'
})

const monthlyBdt = computed(() => {
    if (props.plan.priceMonthlyBdt !== undefined) return Number(props.plan.priceMonthlyBdt)
    return Number(props.plan.price_monthly || 0)
})

const yearlyBdt = computed(() => {
    if (props.plan.priceYearlyBdt !== undefined) return Number(props.plan.priceYearlyBdt)
    if (props.plan.price_yearly !== undefined && Number(props.plan.price_yearly) > 0) return Number(props.plan.price_yearly)
    return monthlyBdt.value * 10
})

const price = computed(() => {
    if (props.currentCurrency === 'BDT') {
        return props.isYearly 
            ? Math.round(yearlyBdt.value).toLocaleString() 
            : Math.round(monthlyBdt.value).toLocaleString()
    } else {
        const usdMonthly = (monthlyBdt.value / 120).toFixed(2)
        const usdYearly = (yearlyBdt.value / 120).toFixed(2)
        return props.isYearly ? usdYearly : usdMonthly
    }
})

const priceUnit = computed(() => {
    return props.isYearly ? '/yr' : '/mo'
})

const totalPriceText = computed(() => {
    if (props.isYearly) {
        const monthlyEquivalent = props.currentCurrency === 'BDT'
            ? `৳${Math.round(yearlyBdt.value / 12)}/mo equivalent`
            : `$${(yearlyBdt.value / (120 * 12)).toFixed(2)}/mo equivalent`
        return `${monthlyEquivalent} (Billed annually)`
    }
    return 'Billed monthly, cancel anytime'
})

const planStorage = computed(() => {
    if (props.plan.disk_space !== undefined) {
        return props.plan.disk_space >= 1024 ? `${(props.plan.disk_space / 1024).toFixed(0)} GB NVMe` : `${props.plan.disk_space} MB NVMe`
    }
    return 'NVMe PCIe 4.0'
})

const planBandwidth = computed(() => {
    if (props.plan.bandwidth !== undefined) {
        if (props.plan.bandwidth === 0 || props.plan.bandwidth >= 999999) return 'Unlimited Bandwidth'
        return props.plan.bandwidth >= 1024 ? `${(props.plan.bandwidth / 1024).toFixed(0)} GB Bandwidth` : `${props.plan.bandwidth} MB Bandwidth`
    }
    return '10Gbps Network'
})

const planDomains = computed(() => {
    if (props.plan.max_domains !== undefined) {
        if (props.plan.max_domains === 0 || props.plan.max_domains >= 999) return 'Unlimited Websites'
        return `${props.plan.max_domains} Website${props.plan.max_domains > 1 ? 's' : ''}`
    }
    return 'Direct SSH Root'
})

const planFeatures = computed(() => {
    if (Array.isArray(props.plan.features) && props.plan.features.length > 0) {
        return props.plan.features
    }

    const list = []

    if (props.plan.max_domains !== undefined) {
        if (props.plan.max_domains === 0 || props.plan.max_domains >= 999) {
            list.push('Unlimited Hosted Websites')
        } else {
            list.push(`${props.plan.max_domains} Hosted Website${props.plan.max_domains > 1 ? 's' : ''}`)
        }
    }

    if (props.plan.disk_space !== undefined) {
        const gb = props.plan.disk_space >= 1024 ? `${(props.plan.disk_space / 1024).toFixed(0)} GB` : `${props.plan.disk_space} MB`
        list.push(`${gb} Enterprise NVMe SSD`)
    }

    if (props.plan.bandwidth !== undefined) {
        if (props.plan.bandwidth === 0 || props.plan.bandwidth >= 999999) {
            list.push('Unlimited BDIX Bandwidth')
        } else {
            const bwGb = props.plan.bandwidth >= 1024 ? `${(props.plan.bandwidth / 1024).toFixed(0)} GB` : `${props.plan.bandwidth} MB`
            list.push(`${bwGb} BDIX 10Gbps Bandwidth`)
        }
    }

    if (props.plan.auto_ssl) {
        list.push('Free Automated SSL (Let\'s Encrypt)')
    } else {
        list.push('Free SSL Certificate')
    }

    if (props.plan.max_email_accounts !== undefined) {
        if (props.plan.max_email_accounts === 0 || props.plan.max_email_accounts >= 999) {
            list.push('Unlimited Business Email Accounts')
        } else {
            list.push(`${props.plan.max_email_accounts} Professional Email Accounts`)
        }
    }

    if (props.plan.max_databases !== undefined) {
        if (props.plan.max_databases === 0 || props.plan.max_databases >= 999) {
            list.push('Unlimited MySQL Databases')
        } else {
            list.push(`${props.plan.max_databases} MySQL 8 Database${props.plan.max_databases > 1 ? 's' : ''}`)
        }
    }

    if (props.plan.allow_ssh_access) {
        list.push('SSH / SFTP & Git Deploy Included')
    }

    list.push('Daily Automated Backups')
    list.push('99.99% Uptime Guarantee')

    return list
})
</script>

<template>
    <div
        :class="[
            'relative flex flex-col rounded-xl p-4 sm:p-5 transition-all duration-200 bg-white h-full',
            isPopular
                ? 'border-2 border-blue-600 shadow-lg shadow-blue-500/10'
                : 'border border-slate-200 hover:border-slate-300 hover:shadow-md shadow-sm'
        ]"
    >
        <!-- Popular Badge -->
        <div
            v-if="isPopular"
            class="absolute -top-2.5 left-4 px-2.5 py-0.5 bg-blue-600 text-white text-[9px] font-black uppercase tracking-wider rounded shadow-sm flex items-center gap-1"
        >
            <SparklesIcon class="w-3 h-3" />
            <span>POPULAR CHOICE</span>
        </div>

        <!-- Plan Header -->
        <div class="mb-3">
            <h3 class="text-sm sm:text-base font-bold text-slate-900 tracking-tight">{{ plan.name }}</h3>
            <p class="text-[11px] text-slate-500 mt-0.5 line-clamp-1 leading-snug">{{ planDescription }}</p>
        </div>

        <!-- Price Showcase -->
        <div class="mb-3 pb-3 border-b border-slate-100">
            <div class="flex items-baseline gap-1">
                <span class="text-base font-black text-blue-600">
                    {{ currentCurrency === 'BDT' ? '৳' : '$' }}
                </span>
                <span class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight font-mono">
                    {{ price }}
                </span>
                <span class="text-[11px] font-semibold text-slate-500">{{ priceUnit }}</span>
            </div>
            <p class="text-[10px] font-semibold text-emerald-600 mt-0.5">{{ totalPriceText }}</p>
        </div>

        <!-- High-Density Quick Specs Bar -->
        <div class="grid grid-cols-2 gap-1.5 mb-3.5 bg-slate-50 p-2 rounded-lg border border-slate-200/80 text-[10px]">
            <div class="text-slate-700 flex items-center gap-1.5 truncate">
                <span class="w-1.5 h-1.5 rounded-full bg-blue-600 shrink-0"></span>
                <span class="truncate font-medium">{{ planStorage }}</span>
            </div>
            <div class="text-slate-700 flex items-center gap-1.5 truncate">
                <span class="w-1.5 h-1.5 rounded-full bg-indigo-600 shrink-0"></span>
                <span class="truncate font-medium">{{ planBandwidth }}</span>
            </div>
            <div class="text-slate-700 flex items-center gap-1.5 truncate">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 shrink-0"></span>
                <span class="truncate font-medium">{{ planDomains }}</span>
            </div>
            <div class="text-slate-700 flex items-center gap-1.5 truncate">
                <span class="w-1.5 h-1.5 rounded-full bg-purple-600 shrink-0"></span>
                <span class="truncate font-medium">Free SSL Included</span>
            </div>
        </div>

        <!-- Feature List (Middle Section) -->
        <div class="flex-1 space-y-1.5 mb-4">
            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block">
                Features & Quotas:
            </span>
            <ul class="space-y-1">
                <li
                    v-for="(feature, idx) in planFeatures"
                    :key="idx"
                    class="flex items-start gap-1.5 text-[11px] text-slate-600 leading-tight"
                >
                    <CheckIcon class="w-3.5 h-3.5 text-emerald-600 shrink-0 mt-0.5" />
                    <span>{{ feature }}</span>
                </li>
            </ul>
        </div>

        <!-- CTA Order Button (At the Very Bottom) -->
        <div class="mt-auto pt-3 border-t border-slate-100">
            <Link
                :href="route('order.checkout', { plan: plan.slug || plan.id, cycle: isYearly ? 'yearly' : 'monthly' })"
                :class="[
                    'w-full py-2 px-3 rounded-lg font-bold text-xs flex items-center justify-center gap-1.5 transition cursor-pointer',
                    isPopular
                        ? 'bg-blue-600 hover:bg-blue-700 text-white shadow-sm'
                        : 'bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-200'
                ]"
            >
                <span>Get Started</span>
            </Link>
        </div>
    </div>
</template>
