<script setup>
import { ref } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import RefreshButton from '@/Components/UI/RefreshButton.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'

import {
    PaperAirplaneIcon,
    ShieldCheckIcon,
    BoltIcon,
    ArrowPathIcon,
    CheckIcon,
    ServerIcon,
    XMarkIcon,
    LockClosedIcon,
    EyeIcon,
    EyeSlashIcon,
    CommandLineIcon,
    GlobeAltIcon,
    SignalIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    setting: {
        type: Object,
        default: () => ({
            is_enabled: false,
            mode: 'direct',
            provider: 'brevo',
            host: 'smtp-relay.brevo.com',
            port: 587,
            encryption: 'tls',
            username: '',
            password: '',
            sender_domain: 'deeptouchit.com',
            last_test_status: null,
            last_test_at: null,
            last_test_log: null,
        }),
    },
    providers: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            is_enabled: false,
            mode: 'direct',
            provider_name: 'DIRECT',
            last_test_status: 'Never Tested',
            last_test_at: 'Never',
        }),
    },
})

// Toast Feedback
const feedbackMsg = ref('')
const showPassword = ref(false)

// Form
const form = useForm({
    is_enabled: props.setting.is_enabled ?? false,
    mode: props.setting.mode || 'direct',
    provider: props.setting.provider || 'brevo',
    host: props.setting.host || 'smtp-relay.brevo.com',
    port: props.setting.port || 587,
    encryption: props.setting.encryption || 'tls',
    username: props.setting.username || '',
    password: props.setting.password || '',
    sender_domain: props.setting.sender_domain || 'deeptouchit.com',
})

// Provider Preset Selector
const selectProvider = (prov) => {
    form.provider = prov.id
    if (prov.id !== 'custom') {
        form.host = prov.host
        form.port = prov.port
        form.encryption = prov.encryption
        if (prov.username) {
            form.username = prov.username
        }
    }
    form.mode = 'relay'
    form.is_enabled = true
}

const submitSettings = () => {
    form.post(route('admin.email.relay.update'), {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = `Outbound SMTP Relay parameters and Postfix SASL synced successfully.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        },
    })
}

// Live Test Diagnostic Modal
const showTestModal = ref(false)
const testForm = useForm({
    test_email: '',
})
const isTesting = ref(false)
const testResult = ref(null)

const openTestModal = () => {
    testResult.value = null
    showTestModal.value = true
}

const submitTest = () => {
    isTesting.value = true
    testResult.value = null
    testForm.post(route('admin.email.relay.test'), {
        preserveScroll: true,
        onSuccess: (page) => {
            isTesting.value = false
            testResult.value = page.props.flash?.test_result || { success: true, message: 'Test email successfully dispatched via SMTP Relay.' }
        },
        onError: (err) => {
            isTesting.value = false
            testResult.value = { success: false, message: err.test_email || 'SMTP handshake failed. Check credentials and port.' }
        }
    })
}
</script>

<template>
    <Head title="SMTP Smart Host Relay - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Services', href: '#' },
                    { label: 'Email Services', href: route('admin.email.domains') },
                    { label: 'SMTP Smart Host Relay' }
                ]"
            >
                <template #actions>
                    <Link
                        :href="route('admin.email.domains')"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowLeftIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Mail Domains</span>
                    </Link>

                    <button
                        type="button"
                        @click="openTestModal"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <PaperAirplaneIcon class="w-3.5 h-3.5 text-blue-600" />
                        <span>Test Relay Handshake</span>
                    </button>

                    <button
                        type="button"
                        @click="submitSettings"
                        :disabled="form.processing"
                        class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer disabled:opacity-50"
                    >
                        <CheckIcon class="w-3.5 h-3.5" />
                        <span>{{ form.processing ? 'Saving...' : 'Save Configuration' }}</span>
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
                    title="Relay Dispatch Mode"
                    :value="form.is_enabled ? 'Smart Host Relay' : 'Direct Server MX'"
                    :badge="form.is_enabled ? 'Relaying' : 'Local Postfix'"
                    :badgeType="form.is_enabled ? 'info' : 'success'"
                    color="blue"
                    :icon="PaperAirplaneIcon"
                />

                <InfoCard
                    title="Active Provider"
                    :value="form.provider ? form.provider.toUpperCase() : 'DIRECT'"
                    badge="Gateway"
                    badgeType="success"
                    color="emerald"
                    :icon="GlobeAltIcon"
                />

                <InfoCard
                    title="Relay Host & Port"
                    :value="form.is_enabled ? `${form.host}:${form.port}` : 'Local:25'"
                    badge="SASL"
                    badgeType="info"
                    color="purple"
                    :icon="ServerIcon"
                />

                <InfoCard
                    title="Handshake Status"
                    :value="setting.last_test_status || 'Never Tested'"
                    :badge="setting.last_test_status === 'passed' ? 'Verified' : 'Pending'"
                    :badgeType="setting.last_test_status === 'passed' ? 'success' : 'warning'"
                    color="sky"
                    :icon="SignalIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Main Full-Width Configuration Form -->
            <form @submit.prevent="submitSettings" class="space-y-3.5">
                <!-- Delivery Mode Switcher -->
                <div class="bg-white rounded-[4px] border border-[#E2E8F0] p-4 shadow-2xs space-y-3.5">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                        <div>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">Outbound Delivery Mode</h3>
                            <p class="text-[11px] text-slate-400">Choose between local Postfix direct delivery or authenticated 3rd-party Smart Host relay.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                        <label 
                            :class="!form.is_enabled ? 'border-blue-600 bg-blue-50/20 ring-1 ring-blue-600' : 'border-slate-200 hover:border-slate-300 bg-white'"
                            class="p-3 rounded-[3px] border transition cursor-pointer flex items-start gap-3"
                        >
                            <input type="radio" :value="false" v-model="form.is_enabled" class="mt-0.5 text-blue-600 focus:ring-blue-500" />
                            <div>
                                <span class="font-bold text-slate-900 block">Direct Outbound (Local Postfix)</span>
                                <span class="text-[11px] text-slate-500 block mt-0.5">Send directly from this server's IP address. Requires port 25 open on host provider.</span>
                            </div>
                        </label>

                        <label 
                            :class="form.is_enabled ? 'border-blue-600 bg-blue-50/20 ring-1 ring-blue-600' : 'border-slate-200 hover:border-slate-300 bg-white'"
                            class="p-3 rounded-[3px] border transition cursor-pointer flex items-start gap-3"
                        >
                            <input type="radio" :value="true" v-model="form.is_enabled" class="mt-0.5 text-blue-600 focus:ring-blue-500" />
                            <div>
                                <span class="font-bold text-slate-900 block">SMTP Smart Host Relay (Recommended)</span>
                                <span class="text-[11px] text-slate-500 block mt-0.5">Route outbound messages through Brevo, Amazon SES, SendGrid, Mailgun, or custom SMTP.</span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Provider Presets & Credentials (Shown when enabled) -->
                <div v-show="form.is_enabled" class="bg-white rounded-[4px] border border-[#E2E8F0] p-4 shadow-2xs space-y-3.5">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                        <div>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">1-Click Provider Quick-Select</h3>
                            <p class="text-[11px] text-slate-400">Pre-populates server endpoints and optimal TLS encryption ports.</p>
                        </div>
                    </div>

                    <!-- Preset Grid -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-7 gap-2 text-xs">
                        <button
                            v-for="prov in [
                                { id: 'resend', name: 'Resend', host: 'smtp.resend.com', port: 587, encryption: 'tls', username: 'resend' },
                                { id: 'brevo', name: 'Brevo', host: 'smtp-relay.brevo.com', port: 587, encryption: 'tls' },
                                { id: 'sendgrid', name: 'SendGrid', host: 'smtp.sendgrid.net', port: 587, encryption: 'tls' },
                                { id: 'ses', name: 'Amazon SES', host: 'email-smtp.us-east-1.amazonaws.com', port: 587, encryption: 'tls' },
                                { id: 'mailgun', name: 'Mailgun', host: 'smtp.mailgun.org', port: 587, encryption: 'tls' },
                                { id: 'postmark', name: 'Postmark', host: 'smtp.postmarkapp.com', port: 587, encryption: 'tls' },
                                { id: 'custom', name: 'Custom SMTP', host: '', port: 587, encryption: 'tls' },
                            ]"
                            :key="prov.id"
                            type="button"
                            @click="selectProvider(prov)"
                            :class="form.provider === prov.id ? 'bg-blue-600 text-white font-bold shadow-2xs' : 'bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 font-medium'"
                            class="p-2.5 rounded-[3px] text-center transition cursor-pointer text-xs"
                        >
                            {{ prov.name }}
                        </button>
                    </div>

                    <!-- Relay Parameters -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs pt-2 border-t border-slate-100">
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Relay Host Server <span class="text-rose-500">*</span></label>
                            <input v-model="form.host" type="text" required placeholder="smtp-relay.brevo.com" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        </div>

                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Port <span class="text-rose-500">*</span></label>
                            <input v-model.number="form.port" type="number" required class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        </div>

                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Encryption</label>
                            <select v-model="form.encryption" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                                <option value="tls">STARTTLS (Port 587)</option>
                                <option value="ssl">SSL / TLS (Port 465)</option>
                                <option value="none">Plain / None (Port 25)</option>
                            </select>
                        </div>
                    </div>

                    <!-- SASL Credentials -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">SASL Username / API Key</label>
                            <input v-model="form.username" type="text" placeholder="e.g. apikey or user@domain.com" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        </div>

                        <div class="space-y-1">
                            <div class="flex items-center justify-between">
                                <label class="font-bold text-slate-700">SASL Password / Secret</label>
                                <button type="button" @click="showPassword = !showPassword" class="text-[10px] text-blue-600 font-bold hover:underline cursor-pointer">
                                    {{ showPassword ? 'Hide' : 'Show' }}
                                </button>
                            </div>
                            <input v-model="form.password" :type="showPassword ? 'text' : 'password'" placeholder="••••••••" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        </div>
                    </div>
                </div>

                <!-- Bottom Save Button -->
                <div class="flex items-center justify-end">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                    >
                        {{ form.processing ? 'Saving...' : 'Save Relay Configuration' }}
                    </button>
                </div>
            </form>
        </div>

        <!-- LIVE TEST MODAL -->
        <div v-if="showTestModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <PaperAirplaneIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Test SMTP Relay Dispatch
                        </h3>
                    </div>
                    <button @click="showTestModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitTest" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Recipient Email Address <span class="text-rose-500">*</span></label>
                        <input v-model="testForm.test_email" type="email" required placeholder="your.name@example.com" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        <span class="text-[10px] text-slate-400 block">A synthetic test message will be sent through the configured smart host relay.</span>
                    </div>

                    <!-- Result Banner -->
                    <div v-if="testResult" :class="testResult.success ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-rose-50 border-rose-200 text-rose-800'" class="p-3 rounded-[3px] border text-xs">
                        <p class="font-bold">{{ testResult.success ? '✓ Relay Handshake Succeeded' : '✕ Relay Connection Failed' }}</p>
                        <p class="font-mono text-[11px] mt-1">{{ testResult.message }}</p>
                    </div>

                    <div class="px-4 py-2.5 -mx-4 -mb-4 mt-4 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                        <button
                            type="button"
                            @click="showTestModal = false"
                            class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                        >
                            Close
                        </button>
                        <button
                            type="submit"
                            :disabled="isTesting"
                            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ isTesting ? 'Testing Handshake...' : 'Send Test Message' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
