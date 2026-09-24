<script setup>
import { ref } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import RefreshButton from '@/Components/UI/RefreshButton.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'

import {
    EnvelopeIcon,
    ServerIcon,
    ShieldCheckIcon,
    PaperAirplaneIcon,
    ArrowPathIcon,
    CheckIcon,
    ArrowUturnLeftIcon,
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
})

// Form instance
const form = useForm({
    mail_mailer: props.settings.mail_mailer || 'smtp',
    mail_host: props.settings.mail_host || '127.0.0.1',
    mail_port: Number(props.settings.mail_port) || 587,
    mail_username: props.settings.mail_username || '',
    mail_password: props.settings.mail_password || '',
    mail_encryption: props.settings.mail_encryption || 'tls',
    mail_from_address: props.settings.mail_from_address || 'noreply@deeptouchit.com',
    mail_from_name: props.settings.mail_from_name || 'DeepTouchHost Cloud Platform',
    mail_reply_to: props.settings.mail_reply_to || 'support@deeptouchit.com',
    mail_queue_enabled: Boolean(props.settings.mail_queue_enabled),
    rate_limit_per_minute: Number(props.settings.rate_limit_per_minute) || 60,
})

const testForm = useForm({
    recipient_email: '',
})

const showTestModal = ref(false)
const showResetModal = ref(false)
const isResetting = ref(false)
const feedbackMsg = ref('')

const presets = [
    { name: 'Custom SMTP', mailer: 'smtp', host: '127.0.0.1', port: 587, enc: 'tls' },
    { name: 'Google Workspace', mailer: 'smtp', host: 'smtp.gmail.com', port: 587, enc: 'tls' },
    { name: 'Amazon SES', mailer: 'smtp', host: 'email-smtp.us-east-1.amazonaws.com', port: 587, enc: 'tls' },
    { name: 'Mailgun', mailer: 'smtp', host: 'smtp.mailgun.org', port: 587, enc: 'tls' },
    { name: 'SendGrid', mailer: 'smtp', host: 'smtp.sendgrid.net', port: 587, enc: 'tls' },
    { name: 'Local Postfix', mailer: 'smtp', host: '127.0.0.1', port: 25, enc: 'none' },
]

const applyPreset = (p) => {
    form.mail_mailer = p.mailer
    form.mail_host = p.host
    form.mail_port = p.port
    form.mail_encryption = p.enc
}

const submit = () => {
    form.post(route('admin.settings.email.update'), {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = 'Mail delivery settings updated.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

const sendTest = () => {
    testForm.post(route('admin.settings.email.test'), {
        preserveScroll: true,
        onSuccess: () => {
            showTestModal.value = false
            testForm.reset()
            feedbackMsg.value = 'Diagnostic test email dispatched.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

const confirmReset = () => {
    isResetting.value = true
    router.post(route('admin.settings.email.reset'), {}, {
        preserveScroll: true,
        onFinish: () => {
            isResetting.value = false
            showResetModal.value = false
            feedbackMsg.value = 'Email settings reset to baseline.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}
</script>

<template>
    <Head title="Email Delivery Settings - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Settings', href: route('admin.settings.general') },
                    { label: 'Mail Server & SMTP' }
                ]"
            >
                <template #actions>
                    <button
                        type="button"
                        @click="showTestModal = true"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-blue-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <PaperAirplaneIcon class="w-3.5 h-3.5" />
                        <span>Send Test Email</span>
                    </button>

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
                    title="Active Driver"
                    :value="form.mail_mailer.toUpperCase()"
                    badge="Transport"
                    badgeType="info"
                    color="blue"
                    :icon="EnvelopeIcon"
                />

                <InfoCard
                    title="Outbound Relay"
                    :value="`${form.mail_host}:${form.mail_port}`"
                    badge="Host"
                    badgeType="success"
                    color="emerald"
                    :icon="ServerIcon"
                />

                <InfoCard
                    title="Sender Identity"
                    :value="form.mail_from_address"
                    badge="From"
                    badgeType="info"
                    color="purple"
                    :icon="PaperAirplaneIcon"
                />

                <InfoCard
                    title="Queue Engine"
                    :value="form.mail_queue_enabled ? 'Redis Background Queue' : 'Synchronous Delivery'"
                    :badge="form.mail_queue_enabled ? 'Queued' : 'Direct'"
                    badgeType="success"
                    color="sky"
                    :icon="ShieldCheckIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Mail Delivery Settings Form Panels -->
            <form @submit.prevent="submit" class="space-y-3.5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                    <!-- SMTP Relay Credentials -->
                    <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs p-4 space-y-3">
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide flex items-center gap-2 border-b border-slate-100 pb-2">
                            <ServerIcon class="w-4 h-4 text-blue-600" />
                            SMTP Outbound Relay Configuration
                        </h3>

                        <div class="space-y-2.5 text-xs">
                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700">Quick Relay Presets</label>
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <button
                                        v-for="p in presets"
                                        :key="p.name"
                                        type="button"
                                        @click="applyPreset(p)"
                                        class="px-2 py-0.5 rounded-[3px] font-bold text-[11px] bg-slate-50 text-slate-700 hover:bg-slate-100 border border-slate-200 transition cursor-pointer shadow-2xs"
                                    >
                                        {{ p.name }}
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-2 pt-1">
                                <div class="space-y-1 col-span-2">
                                    <label class="block font-bold text-slate-700">SMTP Host</label>
                                    <input v-model="form.mail_host" type="text" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                                </div>
                                <div class="space-y-1">
                                    <label class="block font-bold text-slate-700">Port</label>
                                    <input v-model.number="form.mail_port" type="number" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 text-center" />
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <div class="space-y-1">
                                    <label class="block font-bold text-slate-700">SMTP Username</label>
                                    <input v-model="form.mail_username" type="text" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                                </div>
                                <div class="space-y-1">
                                    <label class="block font-bold text-slate-700">SMTP Password</label>
                                    <input v-model="form.mail_password" type="password" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                                </div>
                            </div>

                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700">Encryption Scheme</label>
                                <select v-model="form.mail_encryption" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                                    <option value="tls">STARTTLS (Port 587)</option>
                                    <option value="ssl">SSL/TLS Implicit (Port 465)</option>
                                    <option value="none">Plaintext (Port 25)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Sender Identities & Delivery Throttling -->
                    <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs p-4 space-y-3">
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide flex items-center gap-2 border-b border-slate-100 pb-2">
                            <PaperAirplaneIcon class="w-4 h-4 text-blue-600" />
                            Sender Identities & Queue Engine
                        </h3>

                        <div class="space-y-2.5 text-xs">
                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700">From Name</label>
                                <input v-model="form.mail_from_name" type="text" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                            </div>

                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700">From Email Address</label>
                                <input v-model="form.mail_from_address" type="email" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                            </div>

                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700">Reply-To Address</label>
                                <input v-model="form.mail_reply_to" type="email" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                            </div>

                            <div class="flex items-center gap-2 pt-2">
                                <input v-model="form.mail_queue_enabled" id="mail_queue" type="checkbox" class="rounded-[2px] text-blue-600 focus:ring-blue-500 cursor-pointer" />
                                <label for="mail_queue" class="font-bold text-slate-900 cursor-pointer">Dispatch transactional emails via background Redis queue</label>
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
                        {{ form.processing ? 'Saving...' : 'Save Mail Delivery Settings' }}
                    </button>
                </div>
            </form>
        </div>

        <!-- TEST EMAIL MODAL -->
        <div v-if="showTestModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <PaperAirplaneIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Dispatch Test Email
                        </h3>
                    </div>
                    <button @click="showTestModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="sendTest" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Recipient Email Address</label>
                        <input v-model="testForm.recipient_email" type="email" placeholder="admin@example.com" required class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                        <button
                            type="button"
                            @click="showTestModal = false"
                            class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="testForm.processing"
                            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ testForm.processing ? 'Dispatching...' : 'Send Test' }}
                        </button>
                    </div>
                </form>
            </div>
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
                            Reset Email Settings
                        </h3>
                    </div>
                    <button @click="showResetModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-2.5 text-xs">
                    <p class="text-slate-600">
                        Are you sure you want to reset all outbound mail relay parameters and sender configurations back to defaults?
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
