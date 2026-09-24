<script setup>
import { ref } from 'vue'
import { Head, useForm, router, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import RefreshButton from '@/Components/UI/RefreshButton.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'

import {
    Cog6ToothIcon,
    ServerIcon,
    ShieldCheckIcon,
    GlobeAltIcon,
    ArrowPathIcon,
    EnvelopeIcon,
    LockClosedIcon,
    ClockIcon,
    BuildingOfficeIcon,
    ArrowUturnLeftIcon,
    CheckIcon,
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
    app_name: props.settings.app_name || 'DeepTouch Host Cloud Platform',
    company_name: props.settings.company_name || 'DeepTouch IT Ltd.',
    app_url: props.settings.app_url || 'https://deeptouchit.com',
    admin_email: props.settings.admin_email || 'admin@deeptouchit.com',
    support_email: props.settings.support_email || 'support@deeptouchit.com',
    server_hostname: props.settings.server_hostname || 'deeptouchit.com',
    server_ip: props.settings.server_ip || '103.59.177.138',
    admin_port: Number(props.settings.admin_port) || 443,
    force_https: Boolean(props.settings.force_https),
    maintenance_mode: Boolean(props.settings.maintenance_mode),
    maintenance_message: props.settings.maintenance_message || '',
    maintenance_ip_allowlist: props.settings.maintenance_ip_allowlist || '103.59.177.138, 127.0.0.1',
    allow_registration: Boolean(props.settings.allow_registration),
    require_email_verification: Boolean(props.settings.require_email_verification),
    session_timeout_minutes: Number(props.settings.session_timeout_minutes) || 60,
    max_concurrent_sessions: Number(props.settings.max_concurrent_sessions) || 3,
})

const showResetModal = ref(false)
const isResetting = ref(false)
const feedbackMsg = ref('')

const submit = () => {
    form.post(route('admin.settings.general.update'), {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = 'General system settings saved successfully.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

const confirmReset = () => {
    isResetting.value = true
    router.post(route('admin.settings.general.reset'), {}, {
        preserveScroll: true,
        onFinish: () => {
            isResetting.value = false
            showResetModal.value = false
            feedbackMsg.value = 'Settings restored to factory defaults.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}
</script>

<template>
    <Head title="General Settings - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Settings', href: route('admin.settings.general') },
                    { label: 'General' }
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
                    title="Control Panel"
                    :value="stats.app_version || 'v2.4.0 (Enterprise)'"
                    badge="Core"
                    badgeType="info"
                    color="blue"
                    :icon="Cog6ToothIcon"
                />

                <InfoCard
                    title="Operational Mode"
                    :value="form.maintenance_mode ? 'Maintenance Active' : 'Production Live'"
                    :badge="form.maintenance_mode ? 'Locked' : 'Online'"
                    :badgeType="form.maintenance_mode ? 'danger' : 'success'"
                    :color="form.maintenance_mode ? 'rose' : 'emerald'"
                    :icon="ServerIcon"
                />

                <InfoCard
                    title="Server Authority"
                    :value="form.server_hostname || 'deeptouchit.com'"
                    badge="Node"
                    badgeType="info"
                    color="purple"
                    :icon="GlobeAltIcon"
                />

                <InfoCard
                    title="Security Enforce"
                    :value="form.force_https ? 'SSL HTTPS Enforced' : 'HTTP/HTTPS Mixed'"
                    :badge="form.force_https ? 'Secured' : 'Relaxed'"
                    badgeType="success"
                    color="sky"
                    :icon="ShieldCheckIcon"
                />
            </InfoCardsGrid>

            <!-- 3. General Settings Form Panels -->
            <form @submit.prevent="submit" class="space-y-3.5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                    <!-- Brand & App Identity -->
                    <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs p-4 space-y-3">
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide flex items-center gap-2 border-b border-slate-100 pb-2">
                            <BuildingOfficeIcon class="w-4 h-4 text-blue-600" />
                            Platform Identity & Authority
                        </h3>

                        <div class="space-y-2.5 text-xs">
                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700">Application Name</label>
                                <input v-model="form.app_name" type="text" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                            </div>

                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700">Company Legal Name</label>
                                <input v-model="form.company_name" type="text" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                            </div>

                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700">Authoritative Public URL</label>
                                <input v-model="form.app_url" type="url" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <div class="space-y-1">
                                    <label class="block font-bold text-slate-700">Admin Email</label>
                                    <input v-model="form.admin_email" type="email" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                                </div>
                                <div class="space-y-1">
                                    <label class="block font-bold text-slate-700">Support Email</label>
                                    <input v-model="form.support_email" type="email" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Authoritative Node Networking -->
                    <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs p-4 space-y-3">
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide flex items-center gap-2 border-b border-slate-100 pb-2">
                            <ServerIcon class="w-4 h-4 text-blue-600" />
                            Server Authority & Networking
                        </h3>

                        <div class="space-y-2.5 text-xs">
                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700">Server Hostname FQDN</label>
                                <input v-model="form.server_hostname" type="text" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <div class="space-y-1">
                                    <label class="block font-bold text-slate-700">Primary Public IP</label>
                                    <input v-model="form.server_ip" type="text" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                                </div>
                                <div class="space-y-1">
                                    <label class="block font-bold text-slate-700">Admin Control Port</label>
                                    <input v-model.number="form.admin_port" type="number" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                                </div>
                            </div>

                            <div class="flex items-center gap-2 pt-2">
                                <input v-model="form.force_https" id="force_https" type="checkbox" class="rounded-[2px] text-blue-600 focus:ring-blue-500 cursor-pointer" />
                                <label for="force_https" class="font-medium text-slate-700 cursor-pointer">Strictly Enforce HTTPS SSL Redirection</label>
                            </div>

                            <div class="flex items-center gap-2">
                                <input v-model="form.require_email_verification" id="req_verify" type="checkbox" class="rounded-[2px] text-blue-600 focus:ring-blue-500 cursor-pointer" />
                                <label for="req_verify" class="font-medium text-slate-700 cursor-pointer">Mandatory Customer Email Verification</label>
                            </div>
                        </div>
                    </div>

                    <!-- Operational Mode & Maintenance -->
                    <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs p-4 space-y-3">
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide flex items-center gap-2 border-b border-slate-100 pb-2">
                            <ShieldCheckIcon class="w-4 h-4 text-blue-600" />
                            Maintenance Mode & Emergency Gate
                        </h3>

                        <div class="space-y-2.5 text-xs">
                            <div class="flex items-center gap-2">
                                <input v-model="form.maintenance_mode" id="maint_mode" type="checkbox" class="rounded-[2px] text-rose-600 focus:ring-rose-500 cursor-pointer" />
                                <label for="maint_mode" class="font-bold text-rose-700 cursor-pointer">Enable Global Platform Maintenance Mode</label>
                            </div>

                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700">Maintenance Notice Message</label>
                                <textarea v-model="form.maintenance_message" rows="2" placeholder="System upgrade in progress. Estimated duration: 30 minutes." class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"></textarea>
                            </div>

                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700">Maintenance Bypass IP Allowlist</label>
                                <input v-model="form.maintenance_ip_allowlist" type="text" placeholder="103.59.177.138, 127.0.0.1" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                            </div>
                        </div>
                    </div>

                    <!-- Session Security & Access Limits -->
                    <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs p-4 space-y-3">
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide flex items-center gap-2 border-b border-slate-100 pb-2">
                            <ClockIcon class="w-4 h-4 text-blue-600" />
                            Session Lifespan & Concurrency
                        </h3>

                        <div class="space-y-2.5 text-xs">
                            <div class="grid grid-cols-2 gap-2">
                                <div class="space-y-1">
                                    <label class="block font-bold text-slate-700">Session Timeout (Minutes)</label>
                                    <input v-model.number="form.session_timeout_minutes" type="number" min="5" max="1440" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                                </div>
                                <div class="space-y-1">
                                    <label class="block font-bold text-slate-700">Max Concurrent Logins</label>
                                    <input v-model.number="form.max_concurrent_sessions" type="number" min="1" max="10" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                                </div>
                            </div>

                            <div class="flex items-center gap-2 pt-2">
                                <input v-model="form.allow_registration" id="allow_reg" type="checkbox" class="rounded-[2px] text-blue-600 focus:ring-blue-500 cursor-pointer" />
                                <label for="allow_reg" class="font-medium text-slate-700 cursor-pointer">Allow Public Customer Account Self-Registration</label>
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
                        {{ form.processing ? 'Saving...' : 'Save All General Settings' }}
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
                            Reset General Settings
                        </h3>
                    </div>
                    <button @click="showResetModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-2.5 text-xs">
                    <p class="text-slate-600">
                        Are you sure you want to reset all general system settings back to default factory parameters?
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
