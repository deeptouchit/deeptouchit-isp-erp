<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import RefreshButton from '@/Components/UI/RefreshButton.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'

import {
    CreditCardIcon,
    ArrowPathIcon,
    CheckIcon,
    XMarkIcon,
    BanknotesIcon,
    WrenchScrewdriverIcon,
    DocumentDuplicateIcon,
    ShieldCheckIcon,
    GlobeAltIcon,
    BoltIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    gateways: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            total_gateways: 0,
            active_gateways: 0,
            live_gateways: 0,
            sandbox_gateways: 0,
            total_settled_volume: 0,
        }),
    },
})

// Flash Feedback Toast
const feedbackMsg = ref('')

const formatMoney = (amount) => {
    return '$' + Number(amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

// 1. CONFIGURE GATEWAY MODAL
const showModal = ref(false)
const selectedGateway = ref(null)
const configForm = useForm({
    is_active: true,
    mode: 'live',
    app_key: '',
    app_secret: '',
    username: '',
    password: '',
    public_key: '',
    secret_key: '',
    webhook_secret: '',
    instructions: '',
    fee_value: 0,
})

const openConfigModal = (g) => {
    selectedGateway.value = g
    configForm.is_active = g.is_active !== false
    configForm.mode = g.mode || 'live'
    configForm.app_key = g.credentials?.app_key || g.credentials?.public_key || ''
    configForm.app_secret = g.credentials?.app_secret || g.credentials?.secret_key || ''
    configForm.username = g.credentials?.username || ''
    configForm.password = g.credentials?.password || ''
    configForm.public_key = g.credentials?.public_key || g.credentials?.app_key || g.credentials?.store_id || ''
    configForm.secret_key = g.credentials?.secret_key || g.credentials?.app_secret || g.credentials?.store_password || ''
    configForm.webhook_secret = g.credentials?.webhook_secret || ''
    configForm.instructions = g.instructions || ''
    configForm.fee_value = g.fee_value || 0
    showModal.value = true
}

const submitConfig = () => {
    if (!selectedGateway.value) return
    configForm.post(route('admin.billing.gateways.update', selectedGateway.value.id || selectedGateway.value.slug), {
        preserveScroll: true,
        onSuccess: () => {
            showModal.value = false
            feedbackMsg.value = `Gateway '${selectedGateway.value.name}' settings saved.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 2. TOGGLE GATEWAY
const toggleGateway = (g) => {
    router.post(route('admin.billing.gateways.toggle', g.id || g.slug), {}, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = `Gateway '${g.name}' status updated.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 3. TEST GATEWAY
const testingGatewayId = ref(null)
const testGateway = (g) => {
    testingGatewayId.value = g.id
    router.post(route('admin.billing.gateways.test', g.id || g.slug), {}, {
        preserveScroll: true,
        onFinish: () => {
            testingGatewayId.value = null
        },
        onSuccess: () => {
            feedbackMsg.value = `🔌 Connection to '${g.name}' API endpoint verified successfully.`
            setTimeout(() => { feedbackMsg.value = '' }, 5000)
        }
    })
}

const copyUrl = (url) => {
    navigator.clipboard.writeText(url)
    feedbackMsg.value = 'Webhook endpoint copied to clipboard.'
    setTimeout(() => { feedbackMsg.value = '' }, 2500)
}
</script>

<template>
    <Head title="Payment Gateways - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Billing', href: '#' },
                    { label: 'Billing & Finance', href: route('admin.billing.invoices') },
                    { label: 'Payment Gateway Integrations & Merchant APIs' }
                ]"
            >
                <template #actions>
                    <Link
                        :href="route('admin.billing.invoices')"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowLeftIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Invoices</span>
                    </Link>

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
                    title="Supported Gateways"
                    :value="String(stats.total_gateways || gateways.length || 0)"
                    badge="Integrations"
                    badgeType="info"
                    color="blue"
                    :icon="CreditCardIcon"
                />

                <InfoCard
                    title="Active In Production"
                    :value="String(stats.active_gateways || gateways.filter(g => g.is_active).length || 0)"
                    badge="Live"
                    badgeType="success"
                    color="emerald"
                    :icon="ShieldCheckIcon"
                />

                <InfoCard
                    title="Sandbox Environments"
                    :value="String(stats.sandbox_gateways || 0)"
                    badge="Testing"
                    badgeType="warning"
                    color="purple"
                    :icon="WrenchScrewdriverIcon"
                />

                <InfoCard
                    title="Processed Volume"
                    :value="formatMoney(stats.total_settled_volume || 0)"
                    badge="Settled"
                    badgeType="success"
                    color="sky"
                    :icon="BanknotesIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Payment Gateways Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="px-3.5 py-2.5 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <span class="text-xs font-bold text-slate-900 uppercase tracking-wide">Configured Merchant Payment Gateways</span>
                    <span class="text-[11px] text-slate-400 font-mono">Merchant API Connectors</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Gateway Merchant Provider</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Processing Mode</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">IPN / Webhook Callback URL</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Gateway Fee</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Status</th>
                                <th class="py-2.5 px-3 w-44">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(g, index) in gateways" :key="g.id || index" class="hover:bg-blue-50/30 transition">
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-bold text-slate-900">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs border border-blue-100 shrink-0">
                                            <CreditCardIcon class="w-3.5 h-3.5" />
                                        </div>
                                        <div>
                                            <span class="block leading-tight font-bold">{{ g.name }}</span>
                                            <span class="text-[10px] text-slate-400 font-mono block">{{ g.slug }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="g.mode === 'live' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200'"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ g.mode || 'live' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono text-[11px] text-slate-600 max-w-xs truncate" :title="g.webhook_url">
                                    <div class="flex items-center gap-1.5">
                                        <span>{{ g.webhook_url || `/api/webhooks/${g.slug}` }}</span>
                                        <button type="button" @click="copyUrl(g.webhook_url || `/api/webhooks/${g.slug}`)" class="text-slate-400 hover:text-slate-600 cursor-pointer" title="Copy URL">
                                            <DocumentDuplicateIcon class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[11px] text-slate-700">
                                    {{ g.fee_percentage ? g.fee_percentage + '%' : '0%' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="g.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-500 border-slate-200'"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ g.is_active ? 'Enabled' : 'Disabled' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button 
                                            type="button" 
                                            @click="testGateway(g)"
                                            :disabled="testingGatewayId === g.id"
                                            class="px-2 py-1 bg-white hover:bg-slate-50 text-blue-700 font-bold rounded-[3px] text-xs border border-slate-200 shadow-2xs transition cursor-pointer disabled:opacity-50"
                                            title="Test Gateway API"
                                        >
                                            {{ testingGatewayId === g.id ? 'Testing...' : 'Test 🔌' }}
                                        </button>

                                        <button 
                                            type="button" 
                                            @click="openConfigModal(g)"
                                            class="px-2 py-1 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs border border-slate-200 shadow-2xs transition cursor-pointer"
                                            title="Configure API Keys"
                                        >
                                            Configure ⚙️
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!gateways || gateways.length === 0">
                                <td colspan="7" class="py-12 text-center text-slate-400 font-sans">
                                    No payment gateways configured.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- CONFIGURE GATEWAY MODAL -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <CreditCardIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Configure {{ selectedGateway?.name }} API
                        </h3>
                    </div>
                    <button @click="showModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitConfig" class="p-4 space-y-3 text-xs">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Environment Mode</label>
                            <select v-model="configForm.mode" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                                <option value="live">Production (Live)</option>
                                <option value="sandbox">Sandbox (Testing)</option>
                            </select>
                        </div>

                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Gateway Fee (%)</label>
                            <input v-model.number="configForm.fee_percentage" type="number" step="0.01" min="0" placeholder="2.5" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        </div>
                    </div>

                    <!-- bKash Merchant PGW Specific Form -->
                    <template v-if="selectedGateway?.slug === 'bkash'">
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">bKash App Key <span class="text-rose-500">*</span></label>
                            <input v-model="configForm.app_key" type="text" placeholder="Enter bKash App Key" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" required />
                        </div>

                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">bKash App Secret <span class="text-rose-500">*</span></label>
                            <input v-model="configForm.app_secret" type="password" placeholder="Enter bKash App Secret" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" required />
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700">Merchant Username <span class="text-rose-500">*</span></label>
                                <input v-model="configForm.username" type="text" placeholder="bKash Merchant Username" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" required />
                            </div>

                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700">Merchant Password <span class="text-rose-500">*</span></label>
                                <input v-model="configForm.password" type="password" placeholder="bKash Merchant Password" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" required />
                            </div>
                        </div>
                    </template>

                    <!-- Other Generic / Stripe / SSLCommerz Form -->
                    <template v-else>
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">
                                {{ selectedGateway?.slug === 'sslcommerz' ? 'Store ID' : (selectedGateway?.slug === 'stripe' ? 'Publishable Key' : 'Public API Key / App ID') }}
                            </label>
                            <input v-model="configForm.public_key" type="text" placeholder="Enter Public Key or Store ID" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        </div>

                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">
                                {{ selectedGateway?.slug === 'sslcommerz' ? 'Store Password' : (selectedGateway?.slug === 'stripe' ? 'Secret Key' : 'Secret Key / API Secret') }}
                            </label>
                            <input v-model="configForm.secret_key" type="password" placeholder="Enter Secret Key or Store Password" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        </div>

                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Webhook Signing Secret (Optional)</label>
                            <input v-model="configForm.webhook_secret" type="password" placeholder="whsec_..." class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        </div>
                    </template>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Payment Instructions / Notes (Optional)</label>
                        <textarea v-model="configForm.instructions" rows="2" placeholder="e.g. For manual payment or specific note for customers..." class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"></textarea>
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <input v-model="configForm.is_active" id="active_gw" type="checkbox" class="rounded-[2px] text-blue-600 focus:ring-blue-500 cursor-pointer" />
                        <label for="active_gw" class="text-xs text-slate-700 font-medium cursor-pointer">Enable gateway on customer checkout</label>
                    </div>

                    <div class="px-4 py-2.5 -mx-4 -mb-4 mt-4 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                        <button
                            type="button"
                            @click="showModal = false"
                            class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="configForm.processing"
                            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ configForm.processing ? 'Saving...' : 'Save Configuration' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
