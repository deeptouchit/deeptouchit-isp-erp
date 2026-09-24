<script setup>
import { ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import RefreshButton from '@/Components/UI/RefreshButton.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'

import {
    ShieldCheckIcon,
    LockClosedIcon,
    PlusIcon,
    NoSymbolIcon,
    CheckIcon,
    GlobeAltIcon,
    ServerIcon,
    DocumentTextIcon,
    BoltIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    activityLogs: {
        type: Object,
        default: () => ({ data: [] })
    },
    ipBlocks: {
        type: Array,
        default: () => []
    }
})

const feedbackMsg = ref('')

const blockForm = useForm({
    ip_address: '',
    reason: '',
})

const submitBlock = () => {
    blockForm.post(route('admin.security.blocklist.store'), {
        preserveScroll: true,
        onSuccess: () => {
            blockForm.reset()
            feedbackMsg.value = 'IP address blocked at firewall level.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}
</script>

<template>
    <Head title="Security & Hardening Overview - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Security', href: '#' },
                    { label: 'Security & Data Protection', href: route('admin.security.ssl') },
                    { label: 'Security Center Overview' }
                ]"
            >
                <template #actions>
                    <Link
                        :href="route('admin.security.firewall')"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ShieldCheckIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Firewall Rules</span>
                    </Link>

                    <Link
                        :href="route('admin.security.ssl')"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <LockClosedIcon class="w-3.5 h-3.5" />
                        <span>SSL Certificates</span>
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
                    title="Firewall Defense"
                    value="UFW Active"
                    badge="Active"
                    badgeType="success"
                    color="emerald"
                    :icon="ShieldCheckIcon"
                />

                <InfoCard
                    title="Active IP Bans"
                    :value="String(ipBlocks.length || 0)"
                    badge="Banned"
                    badgeType="danger"
                    color="rose"
                    :icon="NoSymbolIcon"
                />

                <InfoCard
                    title="Fail2ban Defense"
                    value="Monitored"
                    badge="Protected"
                    badgeType="info"
                    color="purple"
                    :icon="BoltIcon"
                />

                <InfoCard
                    title="Audit Trail Logs"
                    :value="String(activityLogs?.data?.length || 0)"
                    badge="Audited"
                    badgeType="info"
                    color="blue"
                    :icon="DocumentTextIcon"
                />
            </InfoCardsGrid>

            <!-- Quick Threat Block Action Card -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] p-3.5 shadow-2xs space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-rose-50 text-rose-600 flex items-center justify-center font-bold text-xs border border-rose-100 shrink-0">
                            <NoSymbolIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">Quick Threat Mitigation (1-Click IP Ban)</h3>
                    </div>
                    <span class="text-[11px] text-slate-400">Immediate iptables / UFW firewall drop</span>
                </div>

                <form @submit.prevent="submitBlock" class="flex flex-col sm:flex-row gap-2 text-xs">
                    <input v-model="blockForm.ip_address" type="text" required placeholder="IP Address (e.g. 192.168.1.100 or 10.0.0.0/24)" class="bg-slate-50 border border-slate-200 rounded-[3px] px-3 py-1.5 text-xs text-slate-900 font-mono focus:ring-1 focus:ring-rose-500 focus:bg-white flex-1" />
                    <input v-model="blockForm.reason" type="text" placeholder="Reason (e.g. Brute-force scanner attempt)" class="bg-slate-50 border border-slate-200 rounded-[3px] px-3 py-1.5 text-xs text-slate-900 focus:ring-1 focus:ring-rose-500 focus:bg-white flex-1" />
                    <button type="submit" :disabled="blockForm.processing" class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-[3px] text-xs font-bold transition flex items-center justify-center gap-1.5 cursor-pointer disabled:opacity-50">
                        <PlusIcon class="w-3.5 h-3.5" />
                        <span>{{ blockForm.processing ? 'Blocking...' : 'Block IP' }}</span>
                    </button>
                </form>
            </div>

            <!-- Audit Trail Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="px-3.5 py-2.5 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <span class="text-xs font-bold text-slate-900 uppercase tracking-wide">Recent Administrative Audit Logs</span>
                    <Link :href="route('admin.security.events')" class="text-xs font-bold text-blue-600 hover:underline">
                        View Full Events Trail →
                    </Link>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Action Trigger</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Description</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Source IP</th>
                                <th class="py-2.5 px-3">Timestamp</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(log, idx) in activityLogs.data" :key="log.id || idx" class="hover:bg-blue-50/30 transition">
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono font-bold text-blue-700">
                                    {{ log.action }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap text-slate-800">
                                    {{ log.description }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono text-[11px] text-slate-600">
                                    {{ log.ip_address }}
                                </td>
                                <td class="py-2.5 px-3 whitespace-nowrap font-mono text-[10.5px] text-slate-500">
                                    {{ new Date(log.created_at).toLocaleString() }}
                                </td>
                            </tr>
                            <tr v-if="!activityLogs.data || activityLogs.data.length === 0">
                                <td colspan="4" class="py-8 text-center text-slate-400">
                                    No administrative activity recorded.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
