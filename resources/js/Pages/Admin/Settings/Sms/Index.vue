<script setup>
import { ref } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import RefreshButton from '@/Components/UI/RefreshButton.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'

import {
    DevicePhoneMobileIcon,
    ChatBubbleLeftRightIcon,
    ShieldCheckIcon,
    PaperAirplaneIcon,
    ArrowPathIcon,
    CheckIcon,
    ArrowUturnLeftIcon,
    KeyIcon,
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
    sms_enabled: Boolean(props.settings.sms_enabled),
    sms_provider: props.settings.sms_provider || 'twilio',
    api_key: props.settings.api_key || '',
    api_secret: props.settings.api_secret || '',
    api_url: props.settings.api_url || '',
    sms_sender_id: props.settings.sms_sender_id || 'DeepTouchHost',
    enable_2fa_sms: Boolean(props.settings.enable_2fa_sms),
    enable_server_alert_sms: Boolean(props.settings.enable_server_alert_sms),
    enable_invoice_sms: Boolean(props.settings.enable_invoice_sms),
    admin_alert_phone: props.settings.admin_alert_phone || '',
    default_country_code: props.settings.default_country_code || '+880',
})

const testForm = useForm({
    phone_number: '',
    test_message: 'DeepTouchHost SMS Probe: Gateway verified.',
})

const showTestModal = ref(false)
const showResetModal = ref(false)
const isResetting = ref(false)
const feedbackMsg = ref('')

const providers = [
    { id: 'twilio', name: 'Twilio (Global)' },
    { id: 'ssl_wireless', name: 'SSL Wireless (BD)' },
    { id: 'bulksmsbd', name: 'BulkSMSBD (BD)' },
    { id: 'infobip', name: 'Infobip (Global)' },
    { id: 'generic_http', name: 'Generic HTTP / REST' },
]

const selectProvider = (p) => {
    form.sms_provider = p.id
}

const submit = () => {
    form.post(route('admin.settings.sms.update'), {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = 'SMS gateway parameters updated.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

const sendTest = () => {
    testForm.post(route('admin.settings.sms.test'), {
        preserveScroll: true,
        onSuccess: () => {
            showTestModal.value = false
            testForm.reset('phone_number')
            feedbackMsg.value = 'Diagnostic SMS dispatched.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

const confirmReset = () => {
    isResetting.value = true
    router.post(route('admin.settings.sms.reset'), {}, {
        preserveScroll: true,
        onFinish: () => {
            isResetting.value = false
            showResetModal.value = false
            feedbackMsg.value = 'SMS settings restored to defaults.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}
</script>

<template>
    <Head title="SMS Gateway Settings - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Settings', href: route('admin.settings.general') },
                    { label: 'SMS Gateway' }
                ]"
            >
                <template #actions>
                    <button
                        type="button"
                        @click="showTestModal = true"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-blue-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ChatBubbleLeftRightIcon class="w-3.5 h-3.5" />
                        <span>Send Test SMS</span>
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
                    title="Active Gateway"
                    :value="form.sms_provider.toUpperCase()"
                    badge="Provider"
                    badgeType="info"
                    color="blue"
                    :icon="DevicePhoneMobileIcon"
                />

                <InfoCard
                    title="Gateway State"
                    :value="form.sms_enabled ? 'SMS Gateway Active' : 'Gateway Disabled'"
                    :badge="form.sms_enabled ? 'Online' : 'Off'"
                    :badgeType="form.sms_enabled ? 'success' : 'danger'"
                    :color="form.sms_enabled ? 'emerald' : 'rose'"
                    :icon="ChatBubbleLeftRightIcon"
                />

                <InfoCard
                    title="2FA OTP Dispatch"
                    :value="form.enable_2fa_sms ? 'Mandatory SMS OTP' : 'Authenticator App'"
                    :badge="form.enable_2fa_sms ? 'SMS' : 'TOTP'"
                    badgeType="info"
                    color="purple"
                    :icon="ShieldCheckIcon"
                />

                <InfoCard
                    title="Sender Caller ID"
                    :value="form.sms_sender_id || 'DeepTouchHost'"
                    badge="Masking"
                    badgeType="info"
                    color="sky"
                    :icon="KeyIcon"
                />
            </InfoCardsGrid>

            <!-- 3. SMS Gateway Settings Form Panels -->
            <form @submit.prevent="submit" class="space-y-3.5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                    <!-- Provider & Credentials -->
                    <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs p-4 space-y-3">
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide flex items-center gap-2 border-b border-slate-100 pb-2">
                            <DevicePhoneMobileIcon class="w-4 h-4 text-blue-600" />
                            SMS Gateway Provider & API Keys
                        </h3>

                        <div class="space-y-2.5 text-xs">
                            <div class="flex items-center gap-2">
                                <input v-model="form.sms_enabled" id="sms_on" type="checkbox" class="rounded-[2px] text-blue-600 focus:ring-blue-500 cursor-pointer" />
                                <label for="sms_on" class="font-bold text-slate-900 cursor-pointer">Enable Outbound SMS Notifications & OTP</label>
                            </div>

                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700">Supported SMS Gateway Providers</label>
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <button
                                        v-for="p in providers"
                                        :key="p.id"
                                        type="button"
                                        @click="selectProvider(p)"
                                        :class="form.sms_provider === p.id ? 'bg-blue-600 text-white' : 'bg-slate-50 text-slate-700 hover:bg-slate-100 border border-slate-200'"
                                        class="px-2 py-0.5 rounded-[3px] font-bold text-[11px] transition cursor-pointer shadow-2xs"
                                    >
                                        {{ p.name }}
                                    </button>
                                </div>
                            </div>

                            <div class="space-y-1 pt-1">
                                <label class="block font-bold text-slate-700">API Key / Auth SID</label>
                                <input v-model="form.api_key" type="text" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                            </div>

                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700">API Secret / Auth Token</label>
                                <input v-model="form.api_secret" type="password" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <div class="space-y-1">
                                    <label class="block font-bold text-slate-700">Sender ID (Alpha Masking)</label>
                                    <input v-model="form.sms_sender_id" type="text" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                                </div>
                                <div class="space-y-1">
                                    <label class="block font-bold text-slate-700">Default Country Code</label>
                                    <input v-model="form.default_country_code" type="text" placeholder="+880" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Trigger Rules & Dispatch Policies -->
                    <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs p-4 space-y-3">
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide flex items-center gap-2 border-b border-slate-100 pb-2">
                            <ShieldCheckIcon class="w-4 h-4 text-blue-600" />
                            Automated SMS Triggers & Alerts
                        </h3>

                        <div class="space-y-2.5 text-xs">
                            <div class="space-y-1.5">
                                <div class="flex items-center gap-2">
                                    <input v-model="form.enable_2fa_sms" id="sms_2fa" type="checkbox" class="rounded-[2px] text-blue-600 focus:ring-blue-500 cursor-pointer" />
                                    <label for="sms_2fa" class="font-medium text-slate-700 cursor-pointer">Dispatch Two-Factor Authentication (2FA) OTPs via SMS</label>
                                </div>

                                <div class="flex items-center gap-2">
                                    <input v-model="form.enable_server_alert_sms" id="sms_alert" type="checkbox" class="rounded-[2px] text-blue-600 focus:ring-blue-500 cursor-pointer" />
                                    <label for="sms_alert" class="font-medium text-slate-700 cursor-pointer">Dispatch Critical Server Hardware / Down Alerts to Admin</label>
                                </div>

                                <div class="flex items-center gap-2">
                                    <input v-model="form.enable_invoice_sms" id="sms_inv" type="checkbox" class="rounded-[2px] text-blue-600 focus:ring-blue-500 cursor-pointer" />
                                    <label for="sms_inv" class="font-medium text-slate-700 cursor-pointer">Send Invoice Due / Payment Receipt SMS to Customers</label>
                                </div>
                            </div>

                            <div class="space-y-1 pt-2">
                                <label class="block font-bold text-slate-700">Admin Alert Phone Number</label>
                                <input v-model="form.admin_alert_phone" type="text" placeholder="+8801700000000" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
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
                        {{ form.processing ? 'Saving...' : 'Save SMS Gateway Settings' }}
                    </button>
                </div>
            </form>
        </div>

        <!-- TEST SMS MODAL -->
        <div v-if="showTestModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <ChatBubbleLeftRightIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Dispatch Test SMS
                        </h3>
                    </div>
                    <button @click="showTestModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="sendTest" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Recipient Mobile Number</label>
                        <input v-model="testForm.phone_number" type="text" placeholder="+8801700000000" required class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Message Text</label>
                        <textarea v-model="testForm.test_message" rows="2" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"></textarea>
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
                            {{ testForm.processing ? 'Sending...' : 'Send SMS' }}
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
                            Reset SMS Settings
                        </h3>
                    </div>
                    <button @click="showResetModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-2.5 text-xs">
                    <p class="text-slate-600">
                        Are you sure you want to reset all SMS gateway API keys, endpoints, and alert triggers back to defaults?
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
