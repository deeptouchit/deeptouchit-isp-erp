<script setup>
import { ref } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import RefreshButton from '@/Components/UI/RefreshButton.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'

import {
    ShieldCheckIcon,
    ShieldExclamationIcon,
    BoltIcon,
    ArrowPathIcon,
    CheckIcon,
    ServerIcon,
    XMarkIcon,
    CpuChipIcon,
    ArrowLeftIcon,
    AdjustmentsHorizontalIcon,
    NoSymbolIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    setting: {
        type: Object,
        default: () => ({
            required_score: 5.0,
            rewrite_subject: true,
            subject_tag: '***SPAM***',
            auto_delete_score: 15.0,
            is_auto_delete_enabled: false,
            whitelist: '',
            blacklist: '',
            bayesian_filter_enabled: true,
            status: 'active',
        }),
    },
    stats: {
        type: Object,
        default: () => ({
            required_score: 5.0,
            is_active: true,
            whitelist_count: 0,
            blacklist_count: 0,
            bayesian_active: true,
        }),
    },
    daemonStatus: {
        type: Object,
        default: () => ({
            is_running: true,
            service_name: 'spamd.service',
            version: 'SpamAssassin 4.0.2',
            status: 'active',
        }),
    },
})

// Toast Feedback
const feedbackMsg = ref('')

const form = useForm({
    required_score: props.setting.required_score || 5.0,
    rewrite_subject: props.setting.rewrite_subject,
    subject_tag: props.setting.subject_tag || '***SPAM***',
    auto_delete_score: props.setting.auto_delete_score || 15.0,
    is_auto_delete_enabled: props.setting.is_auto_delete_enabled,
    whitelist: props.setting.whitelist || '',
    blacklist: props.setting.blacklist || '',
    bayesian_filter_enabled: props.setting.bayesian_filter_enabled,
    status: props.setting.status || 'active',
})

const applyScorePreset = (score) => {
    form.required_score = score
}

const submitSettings = () => {
    form.post(route('admin.email.spam.update'), {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = `SpamAssassin parameters and local.cf reloaded successfully.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        },
    })
}

const isRestarting = ref(false)
const restartDaemon = () => {
    isRestarting.value = true
    router.post(route('admin.email.spam.restart'), {}, {
        preserveScroll: true,
        onSuccess: () => {
            isRestarting.value = false
            feedbackMsg.value = `SpamAssassin daemon (spamd) restarted.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        },
        onError: () => {
            isRestarting.value = false
        }
    })
}
</script>

<template>
    <Head title="Spam Protection & SpamAssassin - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Services', href: '#' },
                    { label: 'Email Services', href: route('admin.email.domains') },
                    { label: 'Spam Protection & SpamAssassin' }
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
                        @click="restartDaemon"
                        :disabled="isRestarting"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer disabled:opacity-50"
                    >
                        <ArrowPathIcon class="w-3.5 h-3.5 text-slate-500" :class="{ 'animate-spin': isRestarting }" />
                        <span>Restart Daemon</span>
                    </button>

                    <button
                        type="button"
                        @click="submitSettings"
                        :disabled="form.processing"
                        class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer disabled:opacity-50"
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
                    title="Spam Engine"
                    :value="daemonStatus.version || 'SpamAssassin 4.0'"
                    badge="Active"
                    badgeType="info"
                    color="blue"
                    :icon="ShieldCheckIcon"
                />

                <InfoCard
                    title="Required Score Threshold"
                    :value="`${form.required_score} pts`"
                    :badge="form.required_score <= 3 ? 'Strict' : (form.required_score >= 8 ? 'Permissive' : 'Standard')"
                    badgeType="success"
                    color="emerald"
                    :icon="AdjustmentsHorizontalIcon"
                />

                <InfoCard
                    title="Bayesian Learning"
                    :value="form.bayesian_filter_enabled ? 'Enabled' : 'Disabled'"
                    :badge="form.bayesian_filter_enabled ? 'Active' : 'Off'"
                    :badgeType="form.bayesian_filter_enabled ? 'success' : 'warning'"
                    color="purple"
                    :icon="CpuChipIcon"
                />

                <InfoCard
                    title="Daemon Service"
                    :value="daemonStatus.is_running ? 'Running' : 'Stopped'"
                    :badge="daemonStatus.is_running ? 'Online' : 'Offline'"
                    :badgeType="daemonStatus.is_running ? 'success' : 'danger'"
                    color="sky"
                    :icon="ServerIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Main Full-Width Settings Panels -->
            <form @submit.prevent="submitSettings" class="space-y-3.5">
                <!-- Panel 1: Filter Scoring & Tags -->
                <div class="bg-white rounded-[4px] border border-[#E2E8F0] p-4 shadow-2xs space-y-3.5">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                        <div>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">Spam Scoring & Thresholds</h3>
                            <p class="text-[11px] text-slate-400">Configure sensitivity levels and message subject headers for detected spam.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                        <div class="space-y-2">
                            <label class="block font-bold text-slate-700">Required Score for Spam Tagging (1 - 10)</label>
                            <div class="flex items-center gap-3">
                                <input v-model.number="form.required_score" type="number" step="0.5" min="1" max="20" class="w-24 px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono font-bold focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                                <div class="flex items-center gap-1">
                                    <button type="button" @click="applyScorePreset(3.0)" class="px-2 py-1 text-[10.5px] rounded-[3px] border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-700 font-bold cursor-pointer">Strict (3.0)</button>
                                    <button type="button" @click="applyScorePreset(5.0)" class="px-2 py-1 text-[10.5px] rounded-[3px] border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-700 font-bold cursor-pointer">Standard (5.0)</button>
                                    <button type="button" @click="applyScorePreset(8.0)" class="px-2 py-1 text-[10.5px] rounded-[3px] border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-700 font-bold cursor-pointer">Lenient (8.0)</button>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="block font-bold text-slate-700">Subject Line Prefix Tag</label>
                            <input v-model="form.subject_tag" type="text" placeholder="***SPAM***" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-6 pt-2 border-t border-slate-100 text-xs">
                        <label class="flex items-center gap-2 cursor-pointer font-bold text-slate-700">
                            <input v-model="form.rewrite_subject" type="checkbox" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer" />
                            <span>Rewrite Subject Line with prefix tag</span>
                        </label>

                        <label class="flex items-center gap-2 cursor-pointer font-bold text-slate-700">
                            <input v-model="form.bayesian_filter_enabled" type="checkbox" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer" />
                            <span>Enable Bayesian Machine-Learning Adaptive Filter</span>
                        </label>

                        <label class="flex items-center gap-2 cursor-pointer font-bold text-slate-700">
                            <input v-model="form.is_auto_delete_enabled" type="checkbox" class="rounded border-slate-300 text-rose-600 focus:ring-rose-500 cursor-pointer" />
                            <span>Auto-Discard High-Score Spam (&gt; {{ form.auto_delete_score }} pts)</span>
                        </label>
                    </div>
                </div>

                <!-- Panel 2: Global Allowlist & Blocklist -->
                <div class="bg-white rounded-[4px] border border-[#E2E8F0] p-4 shadow-2xs space-y-3.5">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                        <div>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">Global Sender Allowlist & Blocklist</h3>
                            <p class="text-[11px] text-slate-400">Specify wildcards (e.g. *@partner.com) or individual IP addresses/emails (one per line).</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                        <div class="space-y-1.5">
                            <label class="block font-bold text-emerald-700">Trusted Senders Allowlist (whitelist_from)</label>
                            <textarea v-model="form.whitelist" rows="5" placeholder="*@trustedpartner.com&#10;newsletter@updates.com" class="w-full px-3 py-2 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-emerald-500"></textarea>
                        </div>

                        <div class="space-y-1.5">
                            <label class="block font-bold text-rose-700">Blocked Senders Blocklist (blacklist_from)</label>
                            <textarea v-model="form.blacklist" rows="5" placeholder="*@spammerdomain.com&#10;badactor@phishing.net" class="w-full px-3 py-2 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-rose-500"></textarea>
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
                        {{ form.processing ? 'Saving Parameters...' : 'Save SpamAssassin Settings' }}
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
