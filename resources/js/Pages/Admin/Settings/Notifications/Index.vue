<script setup>
import { ref } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import RefreshButton from '@/Components/UI/RefreshButton.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'

import {
    BellIcon,
    EnvelopeIcon,
    DevicePhoneMobileIcon,
    ChatBubbleLeftRightIcon,
    ShieldCheckIcon,
    PaperAirplaneIcon,
    ArrowPathIcon,
    CheckIcon,
    ArrowUturnLeftIcon,
    GlobeAltIcon,
    XMarkIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    settings: {
        type: Object,
        required: true,
    },
    eventMatrix: {
        type: Object,
        required: true,
    },
    stats: {
        type: Object,
        required: true,
    },
})

// Main Form instance
const form = useForm({
    channel_mail_enabled: Boolean(props.settings.channel_mail_enabled),
    channel_sms_enabled: Boolean(props.settings.channel_sms_enabled),
    channel_in_app_enabled: Boolean(props.settings.channel_in_app_enabled),
    channel_slack_enabled: Boolean(props.settings.channel_slack_enabled),
    slack_webhook_url: props.settings.slack_webhook_url || '',
    channel_discord_enabled: Boolean(props.settings.channel_discord_enabled),
    discord_webhook_url: props.settings.discord_webhook_url || '',
    channel_telegram_enabled: Boolean(props.settings.channel_telegram_enabled),
    telegram_bot_token: props.settings.telegram_bot_token || '',
    telegram_chat_id: props.settings.telegram_chat_id || '',
    channel_webhook_enabled: Boolean(props.settings.channel_webhook_enabled),
    webhook_url: props.settings.webhook_url || '',
    admin_recipient_emails: props.settings.admin_recipient_emails || 'admin@deeptouchit.com',
    event_matrix: JSON.parse(JSON.stringify(props.eventMatrix)),
})

const probeForm = useForm({
    channel: 'in_app',
})

const showProbeModal = ref(false)
const showResetModal = ref(false)
const isResetting = ref(false)
const feedbackMsg = ref('')

const submit = () => {
    form.post(route('admin.settings.notifications.update'), {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = 'Notification matrix saved successfully.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

const sendProbe = () => {
    probeForm.post(route('admin.settings.notifications.probe'), {
        preserveScroll: true,
        onSuccess: () => {
            showProbeModal.value = false
            feedbackMsg.value = 'Diagnostic notification probe sent.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

const confirmReset = () => {
    isResetting.value = true
    router.post(route('admin.settings.notifications.reset'), {}, {
        preserveScroll: true,
        onFinish: () => {
            isResetting.value = false
            showResetModal.value = false
            feedbackMsg.value = 'Notification rules reset to defaults.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}
</script>

<template>
    <Head title="Notification Matrix - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Settings', href: route('admin.settings.general') },
                    { label: 'Notifications' }
                ]"
            >
                <template #actions>
                    <button
                        type="button"
                        @click="showProbeModal = true"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-blue-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <BellIcon class="w-3.5 h-3.5" />
                        <span>Send Test Probe</span>
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
                        <span>{{ form.processing ? 'Saving...' : 'Save Matrix' }}</span>
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
                    title="In-App Realtime"
                    :value="form.channel_in_app_enabled ? 'Realtime Websocket' : 'Disabled'"
                    badge="Panel"
                    badgeType="success"
                    color="blue"
                    :icon="BellIcon"
                />

                <InfoCard
                    title="Telegram Bot"
                    :value="form.channel_telegram_enabled ? 'Bot Dispatch Active' : 'Disconnected'"
                    :badge="form.channel_telegram_enabled ? 'Online' : 'Off'"
                    :badgeType="form.channel_telegram_enabled ? 'success' : 'info'"
                    color="emerald"
                    :icon="PaperAirplaneIcon"
                />

                <InfoCard
                    title="Slack & Discord"
                    :value="form.channel_slack_enabled || form.channel_discord_enabled ? 'ChatOps Webhooks' : 'Not Configured'"
                    badge="Webhooks"
                    badgeType="info"
                    color="purple"
                    :icon="ChatBubbleLeftRightIcon"
                />

                <InfoCard
                    title="Custom Webhook"
                    :value="form.channel_webhook_enabled ? 'Outbound POST REST' : 'Disabled'"
                    badge="API"
                    badgeType="info"
                    color="sky"
                    :icon="GlobeAltIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Notifications Form Panels -->
            <form @submit.prevent="submit" class="space-y-3.5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                    <!-- Standard Dispatch Channels -->
                    <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs p-4 space-y-3">
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide flex items-center gap-2 border-b border-slate-100 pb-2">
                            <BellIcon class="w-4 h-4 text-blue-600" />
                            Core Communication Channels
                        </h3>

                        <div class="space-y-2.5 text-xs">
                            <div class="flex items-center gap-2">
                                <input v-model="form.channel_in_app_enabled" id="in_app" type="checkbox" class="rounded-[2px] text-blue-600 focus:ring-blue-500 cursor-pointer" />
                                <label for="in_app" class="font-bold text-slate-900 cursor-pointer">In-App Live Bell Notification Drawer</label>
                            </div>

                            <div class="flex items-center gap-2">
                                <input v-model="form.channel_mail_enabled" id="mail_ch" type="checkbox" class="rounded-[2px] text-blue-600 focus:ring-blue-500 cursor-pointer" />
                                <label for="mail_ch" class="font-bold text-slate-900 cursor-pointer">Email Notifications to Admin Recipients</label>
                            </div>

                            <div class="space-y-1 pt-1">
                                <label class="block font-bold text-slate-700">Admin Email Recipients (comma-separated)</label>
                                <input v-model="form.admin_recipient_emails" type="text" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                            </div>

                            <div class="flex items-center gap-2 pt-1">
                                <input v-model="form.channel_sms_enabled" id="sms_ch" type="checkbox" class="rounded-[2px] text-blue-600 focus:ring-blue-500 cursor-pointer" />
                                <label for="sms_ch" class="font-bold text-slate-900 cursor-pointer">SMS Alerts for Critical Incidents</label>
                            </div>
                        </div>
                    </div>

                    <!-- ChatOps & Webhook Relays -->
                    <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs p-4 space-y-3">
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide flex items-center gap-2 border-b border-slate-100 pb-2">
                            <ChatBubbleLeftRightIcon class="w-4 h-4 text-blue-600" />
                            ChatOps Relays (Telegram / Slack / Discord)
                        </h3>

                        <div class="space-y-2.5 text-xs">
                            <div class="flex items-center gap-2">
                                <input v-model="form.channel_telegram_enabled" id="tg_on" type="checkbox" class="rounded-[2px] text-blue-600 focus:ring-blue-500 cursor-pointer" />
                                <label for="tg_on" class="font-bold text-slate-900 cursor-pointer">Telegram Bot Channel</label>
                            </div>

                            <div v-if="form.channel_telegram_enabled" class="grid grid-cols-2 gap-2 pl-4 border-l-2 border-blue-200">
                                <div class="space-y-1">
                                    <label class="block font-bold text-slate-700">Bot Token</label>
                                    <input v-model="form.telegram_bot_token" type="password" placeholder="123456:ABC-DEF..." class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                                </div>
                                <div class="space-y-1">
                                    <label class="block font-bold text-slate-700">Chat ID</label>
                                    <input v-model="form.telegram_chat_id" type="text" placeholder="-100123456789" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                                </div>
                            </div>

                            <div class="flex items-center gap-2 pt-1">
                                <input v-model="form.channel_slack_enabled" id="slack_on" type="checkbox" class="rounded-[2px] text-blue-600 focus:ring-blue-500 cursor-pointer" />
                                <label for="slack_on" class="font-bold text-slate-900 cursor-pointer">Slack Incoming Webhook</label>
                            </div>

                            <div v-if="form.channel_slack_enabled" class="pl-4 border-l-2 border-blue-200 space-y-1">
                                <label class="block font-bold text-slate-700">Webhook URL</label>
                                <input v-model="form.slack_webhook_url" type="url" placeholder="https://hooks.slack.com/services/..." class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                            </div>

                            <div class="flex items-center gap-2 pt-1">
                                <input v-model="form.channel_discord_enabled" id="discord_on" type="checkbox" class="rounded-[2px] text-blue-600 focus:ring-blue-500 cursor-pointer" />
                                <label for="discord_on" class="font-bold text-slate-900 cursor-pointer">Discord Channel Webhook</label>
                            </div>

                            <div v-if="form.channel_discord_enabled" class="pl-4 border-l-2 border-blue-200 space-y-1">
                                <label class="block font-bold text-slate-700">Webhook URL</label>
                                <input v-model="form.discord_webhook_url" type="url" placeholder="https://discord.com/api/webhooks/..." class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Event Routing Matrix Table -->
                <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                    <div class="px-4 py-2.5 bg-slate-50/90 border-b border-slate-200 flex items-center justify-between">
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Event Dispatch Routing Rules Matrix
                        </h3>
                        <span class="text-[11px] text-slate-500 font-mono">Toggle per-event channel delivery</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                    <th class="py-2.5 px-3 border-r border-slate-200 text-left">Event Category & Trigger</th>
                                    <th class="py-2.5 px-3 border-r border-slate-200">Severity</th>
                                    <th class="py-2.5 px-3 border-r border-slate-200">In-App</th>
                                    <th class="py-2.5 px-3 border-r border-slate-200">Email</th>
                                    <th class="py-2.5 px-3 border-r border-slate-200">SMS</th>
                                    <th class="py-2.5 px-3">ChatOps</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                                <tr v-for="(rule, key) in form.event_matrix" :key="key" class="hover:bg-blue-50/30 transition">
                                    <td class="py-2.5 px-3 border-r border-slate-100 text-left font-bold text-slate-900 whitespace-nowrap">
                                        {{ rule.label || key }}
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                        <span 
                                            :class="rule.severity === 'critical' ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-amber-50 text-amber-700 border-amber-200'"
                                            class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                        >
                                            {{ rule.severity || 'warning' }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-slate-100 text-center whitespace-nowrap">
                                        <input v-model="rule.in_app" type="checkbox" class="rounded-[2px] text-blue-600 focus:ring-blue-500 cursor-pointer" />
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-slate-100 text-center whitespace-nowrap">
                                        <input v-model="rule.mail" type="checkbox" class="rounded-[2px] text-blue-600 focus:ring-blue-500 cursor-pointer" />
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-slate-100 text-center whitespace-nowrap">
                                        <input v-model="rule.sms" type="checkbox" class="rounded-[2px] text-blue-600 focus:ring-blue-500 cursor-pointer" />
                                    </td>
                                    <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                        <input v-model="rule.chatops" type="checkbox" class="rounded-[2px] text-blue-600 focus:ring-blue-500 cursor-pointer" />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
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
                        {{ form.processing ? 'Saving...' : 'Save Notification Matrix' }}
                    </button>
                </div>
            </form>
        </div>

        <!-- TEST PROBE MODAL -->
        <div v-if="showProbeModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <BellIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Dispatch Test Notification
                        </h3>
                    </div>
                    <button @click="showProbeModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="sendProbe" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Target Notification Channel</label>
                        <select v-model="probeForm.channel" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                            <option value="in_app">In-App Bell Drawer</option>
                            <option value="mail">Email Alert</option>
                            <option value="telegram">Telegram Bot</option>
                            <option value="slack">Slack Webhook</option>
                            <option value="discord">Discord Webhook</option>
                        </select>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                        <button
                            type="button"
                            @click="showProbeModal = false"
                            class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="probeForm.processing"
                            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ probeForm.processing ? 'Dispatching...' : 'Dispatch Probe' }}
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
                            Reset Notification Rules
                        </h3>
                    </div>
                    <button @click="showResetModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-2.5 text-xs">
                    <p class="text-slate-600">
                        Are you sure you want to reset all notification channels and matrix routing rules back to defaults?
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
