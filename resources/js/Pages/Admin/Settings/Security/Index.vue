<script setup>
import { ref } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import RefreshButton from '@/Components/UI/RefreshButton.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'

import {
    ShieldCheckIcon,
    LockClosedIcon,
    ServerIcon,
    ExclamationTriangleIcon,
    ArrowPathIcon,
    KeyIcon,
    GlobeAltIcon,
    CheckIcon,
    ArrowUturnLeftIcon,
    XMarkIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    settings: {
        type: Object,
        required: true
    },
    stats: {
        type: Object,
        required: true
    }
})

// Initialize Form
const form = useForm({
    password_min_length: Number(props.settings.password_min_length) || 10,
    password_require_uppercase: Boolean(props.settings.password_require_uppercase),
    password_require_number: Boolean(props.settings.password_require_number),
    password_require_special: Boolean(props.settings.password_require_special),
    password_expiry_days: Number(props.settings.password_expiry_days) || 90,
    session_lifetime_minutes: Number(props.settings.session_lifetime_minutes) || 120,
    session_idle_timeout_minutes: Number(props.settings.session_idle_timeout_minutes) || 30,
    max_login_attempts: Number(props.settings.max_login_attempts) || 5,
    lockout_duration_minutes: Number(props.settings.lockout_duration_minutes) || 15,
    auto_block_ip_after_lockouts: Boolean(props.settings.auto_block_ip_after_lockouts),
    auto_block_threshold: Number(props.settings.auto_block_threshold) || 3,
    force_ssl_admin: Boolean(props.settings.force_ssl_admin),
    hsts_enabled: Boolean(props.settings.hsts_enabled),
    hsts_max_age: Number(props.settings.hsts_max_age) || 31536000,
    x_frame_options: props.settings.x_frame_options || 'SAMEORIGIN',
    ip_allowlist_enforced: Boolean(props.settings.ip_allowlist_enforced),
    admin_ip_allowlist: props.settings.admin_ip_allowlist || '103.59.177.138, 127.0.0.1',
})

const showResetModal = ref(false)
const isResetting = ref(false)
const feedbackMsg = ref('')

const submit = () => {
    form.post(route('admin.settings.security.update'), {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = 'Security parameters and firewall policies saved.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

const confirmReset = () => {
    isResetting.value = true
    router.post(route('admin.settings.security.reset'), {}, {
        preserveScroll: true,
        onFinish: () => {
            isResetting.value = false
            showResetModal.value = false
            feedbackMsg.value = 'Security settings restored to baseline defaults.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}
</script>

<template>
    <Head title="Security Settings - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Settings', href: route('admin.settings.general') },
                    { label: 'Security' }
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
                    title="Security Grade"
                    :value="stats.security_score || 'A+ (Hardened)'"
                    badge="Policy"
                    badgeType="success"
                    color="blue"
                    :icon="ShieldCheckIcon"
                />

                <InfoCard
                    title="Brute-Force Guard"
                    :value="`${form.max_login_attempts} attempts / ${form.lockout_duration_minutes}m`"
                    badge="Active"
                    badgeType="info"
                    color="emerald"
                    :icon="LockClosedIcon"
                />

                <InfoCard
                    title="HSTS Transport"
                    :value="form.hsts_enabled ? 'Strict HSTS Enforced' : 'Optional HSTS'"
                    :badge="form.hsts_enabled ? 'Hardened' : 'Standard'"
                    badgeType="success"
                    color="purple"
                    :icon="GlobeAltIcon"
                />

                <InfoCard
                    title="IP Perimeter"
                    :value="form.ip_allowlist_enforced ? 'Restricted Access' : 'Open Access'"
                    :badge="form.ip_allowlist_enforced ? 'Locked' : 'Public'"
                    badgeType="info"
                    color="sky"
                    :icon="KeyIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Security Settings Form Panels -->
            <form @submit.prevent="submit" class="space-y-3.5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                    <!-- Password Hardening -->
                    <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs p-4 space-y-3">
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide flex items-center gap-2 border-b border-slate-100 pb-2">
                            <KeyIcon class="w-4 h-4 text-blue-600" />
                            Operator Password Complexity & Expiry
                        </h3>

                        <div class="space-y-2.5 text-xs">
                            <div class="grid grid-cols-2 gap-2">
                                <div class="space-y-1">
                                    <label class="block font-bold text-slate-700">Minimum Length</label>
                                    <input v-model.number="form.password_min_length" type="number" min="8" max="64" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                                </div>
                                <div class="space-y-1">
                                    <label class="block font-bold text-slate-700">Password Expiry (Days)</label>
                                    <input v-model.number="form.password_expiry_days" type="number" min="0" max="365" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                                </div>
                            </div>

                            <div class="space-y-1.5 pt-1">
                                <div class="flex items-center gap-2">
                                    <input v-model="form.password_require_uppercase" id="req_upper" type="checkbox" class="rounded-[2px] text-blue-600 focus:ring-blue-500 cursor-pointer" />
                                    <label for="req_upper" class="font-medium text-slate-700 cursor-pointer">Require at least one uppercase letter (A-Z)</label>
                                </div>
                                <div class="flex items-center gap-2">
                                    <input v-model="form.password_require_number" id="req_num" type="checkbox" class="rounded-[2px] text-blue-600 focus:ring-blue-500 cursor-pointer" />
                                    <label for="req_num" class="font-medium text-slate-700 cursor-pointer">Require at least one numeric digit (0-9)</label>
                                </div>
                                <div class="flex items-center gap-2">
                                    <input v-model="form.password_require_special" id="req_spec" type="checkbox" class="rounded-[2px] text-blue-600 focus:ring-blue-500 cursor-pointer" />
                                    <label for="req_spec" class="font-medium text-slate-700 cursor-pointer">Require at least one special character (!@#$%^&*)</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Brute Force & Rate Limiting -->
                    <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs p-4 space-y-3">
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide flex items-center gap-2 border-b border-slate-100 pb-2">
                            <LockClosedIcon class="w-4 h-4 text-blue-600" />
                            Brute-Force Protection & Lockout
                        </h3>

                        <div class="space-y-2.5 text-xs">
                            <div class="grid grid-cols-2 gap-2">
                                <div class="space-y-1">
                                    <label class="block font-bold text-slate-700">Max Failed Attempts</label>
                                    <input v-model.number="form.max_login_attempts" type="number" min="3" max="20" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                                </div>
                                <div class="space-y-1">
                                    <label class="block font-bold text-slate-700">Lockout Time (Minutes)</label>
                                    <input v-model.number="form.lockout_duration_minutes" type="number" min="1" max="1440" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                                </div>
                            </div>

                            <div class="flex items-center gap-2 pt-2">
                                <input v-model="form.auto_block_ip_after_lockouts" id="auto_ban" type="checkbox" class="rounded-[2px] text-blue-600 focus:ring-blue-500 cursor-pointer" />
                                <label for="auto_ban" class="font-bold text-slate-900 cursor-pointer">Automatically ban IP in firewall after repeated lockouts</label>
                            </div>
                        </div>
                    </div>

                    <!-- HTTP Headers & HSTS -->
                    <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs p-4 space-y-3">
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide flex items-center gap-2 border-b border-slate-100 pb-2">
                            <GlobeAltIcon class="w-4 h-4 text-blue-600" />
                            HTTP Security Headers & HSTS
                        </h3>

                        <div class="space-y-2.5 text-xs">
                            <div class="flex items-center gap-2">
                                <input v-model="form.hsts_enabled" id="hsts_on" type="checkbox" class="rounded-[2px] text-blue-600 focus:ring-blue-500 cursor-pointer" />
                                <label for="hsts_on" class="font-bold text-slate-900 cursor-pointer">Enable HTTP Strict Transport Security (HSTS)</label>
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <div class="space-y-1">
                                    <label class="block font-bold text-slate-700">HSTS Max Age (Seconds)</label>
                                    <input v-model.number="form.hsts_max_age" type="number" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                                </div>
                                <div class="space-y-1">
                                    <label class="block font-bold text-slate-700">X-Frame-Options</label>
                                    <select v-model="form.x_frame_options" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                                        <option value="SAMEORIGIN">SAMEORIGIN (Protected)</option>
                                        <option value="DENY">DENY (Strict)</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- IP Access Perimeter -->
                    <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs p-4 space-y-3">
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide flex items-center gap-2 border-b border-slate-100 pb-2">
                            <ShieldCheckIcon class="w-4 h-4 text-blue-600" />
                            Admin IP Access Perimeter
                        </h3>

                        <div class="space-y-2.5 text-xs">
                            <div class="flex items-center gap-2">
                                <input v-model="form.ip_allowlist_enforced" id="ip_enf" type="checkbox" class="rounded-[2px] text-rose-600 focus:ring-rose-500 cursor-pointer" />
                                <label for="ip_enf" class="font-bold text-rose-700 cursor-pointer">Restrict Admin Panel login exclusively to Allowed IPs</label>
                            </div>

                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700">Allowed IP Addresses / Subnets (CIDR)</label>
                                <input v-model="form.admin_ip_allowlist" type="text" placeholder="103.59.177.138, 127.0.0.1, 192.168.1.0/24" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
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
                        {{ form.processing ? 'Saving...' : 'Save Security Policies' }}
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
                            Reset Security Settings
                        </h3>
                    </div>
                    <button @click="showResetModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-2.5 text-xs">
                    <p class="text-slate-600">
                        Are you sure you want to reset all security policies and brute force thresholds back to default parameters?
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
