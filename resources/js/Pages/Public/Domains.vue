<script setup>
import { ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import PublicNavbar from '@/Components/Public/PublicNavbar.vue'
import PublicFooter from '@/Components/Public/PublicFooter.vue'
import DomainSearchBox from '@/Components/Public/DomainSearchBox.vue'
import {
    GlobeAltIcon,
    ShieldCheckIcon,
    LockClosedIcon,
    ArrowsRightLeftIcon,
    CheckCircleIcon,
    SparklesIcon
} from '@heroicons/vue/24/outline'

const currentCurrency = ref('BDT')

const tldPricing = [
    { tld: '.com', regBdt: 1250, renewBdt: 1450, transBdt: 1250, regUsd: 11.99, popular: true },
    { tld: '.net', regBdt: 1450, renewBdt: 1650, transBdt: 1450, regUsd: 13.99, popular: false },
    { tld: '.org', regBdt: 1550, renewBdt: 1750, transBdt: 1550, regUsd: 14.50, popular: false },
    { tld: '.info', regBdt: 650, renewBdt: 1850, transBdt: 1850, regUsd: 5.99, popular: false },
    { tld: '.xyz', regBdt: 350, renewBdt: 1450, transBdt: 1450, regUsd: 2.99, popular: true },
    { tld: '.com.bd', regBdt: 2000, renewBdt: 2000, transBdt: 2000, regUsd: 19.99, note: '2 Years BTCL Required', popular: true },
    { tld: '.org.bd', regBdt: 2000, renewBdt: 2000, transBdt: 2000, regUsd: 19.99, note: '2 Years BTCL', popular: false },
    { tld: '.edu.bd', regBdt: 2000, renewBdt: 2000, transBdt: 2000, regUsd: 19.99, note: 'Academic Approval', popular: false },
]
</script>

<template>
    <Head title="Domain Registration & Transfer - DeepTouch Host" />

    <div class="min-h-screen bg-slate-50/60 text-slate-800 font-sans antialiased">
        <PublicNavbar :current-currency="currentCurrency" @currency-change="c => currentCurrency = c" />

        <!-- Header Hero (Light) -->
        <section class="py-10 lg:py-16 bg-white border-b border-slate-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-50 border border-blue-200 text-blue-700 text-xs font-bold uppercase tracking-wider mb-3">
                    <GlobeAltIcon class="w-3.5 h-3.5" />
                    <span>Domain Registration Engine</span>
                </div>
                <h1 class="text-2xl sm:text-4xl font-black text-slate-900 tracking-tight">
                    Find and Claim Your <span class="text-blue-600">Perfect Domain Name</span>
                </h1>
                <p class="mt-2.5 text-xs sm:text-sm text-slate-600 max-w-2xl mx-auto">
                    Instant automated domain registration, free DNS management, theft protection, and official BTCL .com.bd domain services.
                </p>

                <!-- Search Box -->
                <div class="mt-6">
                    <DomainSearchBox :current-currency="currentCurrency" />
                </div>
            </div>
        </section>

        <!-- TLD Price Table (Light) -->
        <section class="py-12">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-8">
                    <h2 class="text-xl sm:text-3xl font-black text-slate-900">Full Domain Pricing Matrix</h2>
                    <p class="text-xs text-slate-600 mt-1">Transparent pricing with no hidden renewal markups</p>
                </div>

                <div class="bg-white border border-slate-200 rounded-xl overflow-x-auto shadow-xs">
                    <table class="w-full text-left text-xs whitespace-nowrap">
                        <thead class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200">
                            <tr>
                                <th class="py-3.5 px-5">TLD Extension</th>
                                <th class="py-3.5 px-5">New Registration</th>
                                <th class="py-3.5 px-5">Renewal</th>
                                <th class="py-3.5 px-5">Transfer</th>
                                <th class="py-3.5 px-5 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <tr v-for="item in tldPricing" :key="item.tld" class="hover:bg-slate-50/60 transition">
                                <td class="py-3 px-5 font-bold text-slate-900 flex items-center gap-2">
                                    <span class="text-blue-600 text-sm font-black">{{ item.tld }}</span>
                                    <span v-if="item.popular" class="text-[9px] px-1.5 py-0.2 bg-blue-50 text-blue-700 font-bold rounded border border-blue-200">HOT</span>
                                    <span v-if="item.note" class="text-[9px] px-1.5 py-0.2 bg-purple-50 text-purple-700 rounded font-semibold border border-purple-200">{{ item.note }}</span>
                                </td>
                                <td class="py-3 px-5 font-bold text-emerald-600">
                                    {{ currentCurrency === 'BDT' ? `৳${item.regBdt}/yr` : `$${item.regUsd}/yr` }}
                                </td>
                                <td class="py-3 px-5 text-slate-600">
                                    {{ currentCurrency === 'BDT' ? `৳${item.renewBdt}/yr` : `$${item.regUsd}/yr` }}
                                </td>
                                <td class="py-3 px-5 text-slate-600">
                                    {{ currentCurrency === 'BDT' ? `৳${item.transBdt}/yr` : `$${item.regUsd}/yr` }}
                                </td>
                                <td class="py-3 px-5 text-right">
                                    <Link :href="route('register')" class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg text-xs transition shadow-xs">
                                        Register
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <PublicFooter />
    </div>
</template>
