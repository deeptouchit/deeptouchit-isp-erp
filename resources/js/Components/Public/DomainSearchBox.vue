<script setup>
import { ref } from 'vue'
import { MagnifyingGlassIcon, CheckCircleIcon, SparklesIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    currentCurrency: {
        type: String,
        default: 'BDT'
    }
})

const domainQuery = ref('')
const isSearching = ref(false)
const searchResult = ref(null)

const tlds = [
    { name: '.com', priceBdt: 1250, priceUsd: 11.99, popular: true },
    { name: '.net', priceBdt: 1450, priceUsd: 13.99, popular: false },
    { name: '.org', priceBdt: 1550, priceUsd: 14.50, popular: false },
    { name: '.xyz', priceBdt: 350, priceUsd: 2.99, popular: true },
    { name: '.com.bd', priceBdt: 2000, priceUsd: 19.99, popular: true, note: '2 Years BTCL' }
]

const handleDomainSearch = () => {
    if (!domainQuery.value.trim()) return
    isSearching.value = true
    searchResult.value = null

    setTimeout(() => {
        isSearching.value = false
        const cleanDomain = domainQuery.value.trim().toLowerCase().replace(/^(https?:\/\/)?(www\.)?/, '')
        const formattedDomain = cleanDomain.includes('.') ? cleanDomain : `${cleanDomain}.com`

        searchResult.value = {
            domain: formattedDomain,
            available: true,
            priceBdt: 1250,
            priceUsd: 11.99
        }
    }, 600)
}
</script>

<template>
    <div class="w-full max-w-4xl mx-auto">
        <!-- Main Search Bar (Light) -->
        <form @submit.prevent="handleDomainSearch" class="relative group">
            <div class="relative flex items-center bg-white border-2 border-blue-600 rounded-xl p-1.5 shadow-md focus-within:ring-4 focus-within:ring-blue-100 transition-all">
                <div class="pl-3 pr-2 text-slate-400">
                    <MagnifyingGlassIcon class="w-5 h-5 text-blue-600" />
                </div>

                <input
                    v-model="domainQuery"
                    type="text"
                    placeholder="Find your perfect domain name (e.g. yourbrand.com, mycompany.com.bd)..."
                    class="w-full bg-transparent border-0 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-0 text-xs sm:text-sm font-medium py-2"
                    required
                />

                <button
                    type="submit"
                    :disabled="isSearching"
                    class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-lg shadow-sm shrink-0 flex items-center gap-1.5 transition cursor-pointer"
                >
                    <span v-if="!isSearching">Search Domain</span>
                    <span v-else class="flex items-center gap-2">
                        <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        Checking...
                    </span>
                </button>
            </div>
        </form>

        <!-- Search Result Alert (Light) -->
        <div v-if="searchResult" class="mt-3 p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 flex flex-col sm:flex-row items-center justify-between gap-3 animate-fadeIn">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
                    <CheckCircleIcon class="w-5 h-5" />
                </div>
                <div>
                    <span class="text-sm font-bold text-slate-900 tracking-wide">{{ searchResult.domain }}</span>
                    <span class="text-xs text-emerald-700 ml-2 font-medium">is available for registration!</span>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <span class="text-sm font-black text-slate-900">
                    {{ currentCurrency === 'BDT' ? `৳${searchResult.priceBdt}/yr` : `$${searchResult.priceUsd}/yr` }}
                </span>
                <a
                    :href="route('register')"
                    class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-lg transition shadow-xs cursor-pointer"
                >
                    Register Now
                </a>
            </div>
        </div>

        <!-- TLD Price Badges (Light) -->
        <div class="mt-3.5 flex flex-wrap items-center justify-center gap-2">
            <div
                v-for="tld in tlds"
                :key="tld.name"
                class="px-3 py-1 rounded-lg bg-white border border-slate-200 text-xs flex items-center gap-1.5 shadow-xs"
            >
                <span class="font-extrabold text-blue-600">{{ tld.name }}</span>
                <span class="text-slate-700 font-semibold">
                    {{ currentCurrency === 'BDT' ? `৳${tld.priceBdt}` : `$${tld.priceUsd}` }}
                    <span class="text-[10px] text-slate-400">/yr</span>
                </span>
                <span v-if="tld.popular" class="text-[9px] px-1 py-0.2 bg-blue-50 text-blue-700 font-bold rounded border border-blue-200">HOT</span>
                <span v-if="tld.note" class="text-[9px] px-1 py-0.2 bg-purple-50 text-purple-700 font-bold rounded border border-purple-200">{{ tld.note }}</span>
            </div>
        </div>
    </div>
</template>
