<script setup>
import { ref, computed } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import RefreshButton from '@/Components/UI/RefreshButton.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'

import {
    GlobeAltIcon,
    CurrencyDollarIcon,
    ClockIcon,
    CalendarIcon,
    ArrowPathIcon,
    CheckIcon,
    ArrowUturnLeftIcon,
    PlusIcon,
    TrashIcon,
    LanguageIcon,
    XMarkIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    settings: {
        type: Object,
        required: true,
    },
    stats: {
        type: Object,
        required: true,
    },
    timezones: {
        type: Object,
        required: true,
    },
    locales: {
        type: Object,
        required: true,
    },
})

// Form instance
const form = useForm({
    currency_code: props.settings.currency_code || 'BDT',
    currency_symbol: props.settings.currency_symbol || '৳',
    currency_position: props.settings.currency_position || 'before',
    decimal_separator: props.settings.decimal_separator || '.',
    thousand_separator: props.settings.thousand_separator || ',',
    decimal_precision: Number(props.settings.decimal_precision) ?? 2,
    timezone: props.settings.timezone || 'Asia/Dhaka',
    date_format: props.settings.date_format || 'd/m/Y',
    time_format: props.settings.time_format || '12h',
    first_day_of_week: props.settings.first_day_of_week || 'sunday',
    default_locale: props.settings.default_locale || 'en',
    allow_client_language: Boolean(props.settings.allow_client_language),
    rtl_support: Boolean(props.settings.rtl_support),
    exchange_rates: Array.isArray(props.settings.exchange_rates) ? [...props.settings.exchange_rates] : [
        { code: 'USD', symbol: '$', rate: 0.0084, enabled: true },
        { code: 'EUR', symbol: '€', rate: 0.0078, enabled: true },
        { code: 'INR', symbol: '₹', rate: 0.73, enabled: true },
    ],
})

const commonCurrencies = [
    { code: 'BDT', symbol: '৳' },
    { code: 'USD', symbol: '$' },
    { code: 'EUR', symbol: '€' },
    { code: 'GBP', symbol: '£' },
    { code: 'INR', symbol: '₹' },
    { code: 'AED', symbol: 'د.إ' },
    { code: 'SAR', symbol: '﷼' },
    { code: 'SGD', symbol: 'S$' },
]

const selectCurrency = (c) => {
    form.currency_code = c.code
    form.currency_symbol = c.symbol
}

const sampleFormattedAmount = computed(() => {
    const rawNum = 1250.75
    const prec = Number(form.decimal_precision) || 0
    let fixed = rawNum.toFixed(prec)
    let parts = fixed.split('.')
    let intPart = parts[0]
    let decPart = parts[1] || ''

    let tSep = form.thousand_separator
    if (tSep === 'none') tSep = ''
    else if (tSep === 'space') tSep = ' '

    intPart = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, tSep)
    let finalNum = prec > 0 ? `${intPart}${form.decimal_separator}${decPart}` : intPart

    return form.currency_position === 'after' 
        ? `${finalNum} ${form.currency_symbol}` 
        : `${form.currency_symbol} ${finalNum}`
})

const showResetModal = ref(false)
const isResetting = ref(false)
const feedbackMsg = ref('')

const submit = () => {
    form.post(route('admin.settings.localization.update'), {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = 'Localization and currency parameters updated.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

const confirmReset = () => {
    isResetting.value = true
    router.post(route('admin.settings.localization.reset'), {}, {
        preserveScroll: true,
        onFinish: () => {
            isResetting.value = false
            showResetModal.value = false
            feedbackMsg.value = 'Localization settings restored to defaults.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}
</script>

<template>
    <Head title="Localization Settings - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Settings', href: route('admin.settings.general') },
                    { label: 'Localization' }
                ]"
            >
                <template #actions>
                    <button
                        type="button"
                        @click="showResetModal = true"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowUturnLeftIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Reset Defaults</span>
                    </button>

                    <button
                        type="button"
                        @click="submit"
                        :disabled="form.processing"
                        class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-bold rounded-[3px] text-xs flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <CheckIcon class="w-3.5 h-3.5" />
                        <span>{{ form.processing ? 'Saving...' : 'Save Settings' }}</span>
                    </button>

                    <RefreshButton />
                </template>
            </PageHeader>

            <!-- Feedback Banner -->
            <div v-if="feedbackMsg" class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-3 py-2 rounded-[3px] text-xs font-bold flex items-center justify-between shadow-2xs">
                <div class="flex items-center gap-2">
                    <CheckIcon class="w-4 h-4 text-emerald-600" />
                    <span>{{ feedbackMsg }}</span>
                </div>
                <button @click="feedbackMsg = ''" class="text-emerald-600 hover:text-emerald-800 text-xs cursor-pointer">✕</button>
            </div>

            <!-- 2. Ultra-Compact KPI Info Cards (56px) -->
            <InfoCardsGrid :cols="4">
                <InfoCard
                    title="Default Currency"
                    :value="`${form.currency_code} (${form.currency_symbol})`"
                    badge="Base"
                    badgeType="info"
                    color="blue"
                    :icon="CurrencyDollarIcon"
                />

                <InfoCard
                    title="Timezone Node"
                    :value="form.timezone || 'Asia/Dhaka'"
                    badge="Time"
                    badgeType="success"
                    color="emerald"
                    :icon="ClockIcon"
                />

                <InfoCard
                    title="Locale Language"
                    :value="form.default_locale === 'en' ? 'English (EN)' : 'Bengali (BN)'"
                    badge="Language"
                    badgeType="info"
                    color="purple"
                    :icon="LanguageIcon"
                />

                <InfoCard
                    title="Sample Format"
                    :value="sampleFormattedAmount"
                    badge="Preview"
                    badgeType="info"
                    color="sky"
                    :icon="GlobeAltIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Localization Form Panels -->
            <form @submit.prevent="submit" class="space-y-3.5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                    <!-- Base Currency & Numeric Formatting -->
                    <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs p-4 space-y-3">
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide flex items-center gap-2 border-b border-slate-100 pb-2">
                            <CurrencyDollarIcon class="w-4 h-4 text-blue-600" />
                            Authoritative Currency & Financial Formatting
                        </h3>

                        <div class="space-y-2.5 text-xs">
                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700">Quick Common Currencies</label>
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <button
                                        v-for="c in commonCurrencies"
                                        :key="c.code"
                                        type="button"
                                        @click="selectCurrency(c)"
                                        :class="form.currency_code === c.code ? 'bg-blue-600 text-white' : 'bg-slate-50 text-slate-700 hover:bg-slate-100 border border-slate-200'"
                                        class="px-2 py-0.5 rounded-[3px] font-bold text-[11px] font-mono transition cursor-pointer shadow-2xs"
                                    >
                                        {{ c.code }} ({{ c.symbol }})
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-2 pt-1">
                                <div class="space-y-1">
                                    <label class="block font-bold text-slate-700">Currency Code</label>
                                    <input v-model="form.currency_code" type="text" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                                </div>
                                <div class="space-y-1">
                                    <label class="block font-bold text-slate-700">Currency Symbol</label>
                                    <input v-model="form.currency_symbol" type="text" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-2">
                                <div class="space-y-1">
                                    <label class="block font-bold text-slate-700">Symbol Position</label>
                                    <select v-model="form.currency_position" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                                        <option value="before">Before ($100)</option>
                                        <option value="after">After (100$)</option>
                                    </select>
                                </div>
                                <div class="space-y-1">
                                    <label class="block font-bold text-slate-700">Decimal Sep</label>
                                    <input v-model="form.decimal_separator" type="text" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 text-center" />
                                </div>
                                <div class="space-y-1">
                                    <label class="block font-bold text-slate-700">Thousand Sep</label>
                                    <input v-model="form.thousand_separator" type="text" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 text-center" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Timezone & Date Formatting -->
                    <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs p-4 space-y-3">
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide flex items-center gap-2 border-b border-slate-100 pb-2">
                            <ClockIcon class="w-4 h-4 text-blue-600" />
                            Timezone & Timestamp Formatting
                        </h3>

                        <div class="space-y-2.5 text-xs">
                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700">Server & UI Timezone</label>
                                <input v-model="form.timezone" type="text" placeholder="Asia/Dhaka" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <div class="space-y-1">
                                    <label class="block font-bold text-slate-700">Date Format</label>
                                    <select v-model="form.date_format" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                                        <option value="d/m/Y">DD/MM/YYYY (31/12/2026)</option>
                                        <option value="Y-m-d">YYYY-MM-DD (2026-12-31)</option>
                                        <option value="m/d/Y">MM/DD/YYYY (12/31/2026)</option>
                                        <option value="d M Y">DD MMM YYYY (31 Dec 2026)</option>
                                    </select>
                                </div>
                                <div class="space-y-1">
                                    <label class="block font-bold text-slate-700">Time Format</label>
                                    <select v-model="form.time_format" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                                        <option value="12h">12-Hour (02:30 PM)</option>
                                        <option value="24h">24-Hour (14:30)</option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <div class="space-y-1">
                                    <label class="block font-bold text-slate-700">Primary Language Locale</label>
                                    <select v-model="form.default_locale" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                                        <option value="en">English (United States)</option>
                                        <option value="bn">Bengali (বাংলা)</option>
                                    </select>
                                </div>
                                <div class="space-y-1">
                                    <label class="block font-bold text-slate-700">First Day of Week</label>
                                    <select v-model="form.first_day_of_week" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                                        <option value="sunday">Sunday</option>
                                        <option value="monday">Monday</option>
                                        <option value="saturday">Saturday</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button
                        type="button"
                        @click="showResetModal = true"
                        class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        Reset Defaults
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-bold rounded-[3px] text-xs shadow-2xs transition cursor-pointer"
                    >
                        {{ form.processing ? 'Saving...' : 'Save Localization Settings' }}
                    </button>
                </div>
            </form>
        </div>

        <!-- RESET CONFIRMATION MODAL -->
        <div v-if="showResetModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-amber-50 text-amber-600 flex items-center justify-center font-bold border border-amber-100">
                            <ArrowUturnLeftIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Reset Localization Settings
                        </h3>
                    </div>
                    <button @click="showResetModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-2.5 text-xs">
                    <p class="text-slate-600">
                        Are you sure you want to reset all localization, timezone, and currency format parameters back to factory defaults?
                    </p>
                </div>

                <div class="px-4 py-2.5 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                    <button
                        type="button"
                        @click="showResetModal = false"
                        class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="confirmReset"
                        :disabled="isResetting"
                        class="px-3.5 py-1.5 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                    >
                        {{ isResetting ? 'Resetting...' : 'Confirm Reset' }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
