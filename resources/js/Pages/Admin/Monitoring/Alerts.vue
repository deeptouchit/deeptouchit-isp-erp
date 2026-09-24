<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import RefreshButton from '@/Components/UI/RefreshButton.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'

import {
    BellAlertIcon,
    ExclamationTriangleIcon,
    CheckIcon,
    ShieldCheckIcon,
    ArrowPathIcon,
    PaperAirplaneIcon,
    ClockIcon,
    EnvelopeIcon,
    ChatBubbleLeftRightIcon,
    BoltIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({}),
    },
    incidents: {
        type: Array,
        default: () => [],
    },
    history: {
        type: Array,
        default: () => [],
    },
    rules: {
        type: Array,
        default: () => [],
    },
    channels: {
        type: Array,
        default: () => [],
    },
})

// Reactive Live State
const liveStats = ref(props.stats || {})
const liveIncidents = ref(props.incidents || [])
const liveHistory = ref(props.history || [])
const liveRules = ref(props.rules || [])
const liveChannels = ref(props.channels || [])
const feedbackMsg = ref('')

// Live Telemetry Engine
const refreshInterval = ref(3000)
const isLiveActive = ref(true)
const isPolling = ref(false)
let timer = null

const fetchMetrics = async () => {
    if (isPolling.value) return
    isPolling.value = true

    try {
        const response = await fetch(route('admin.monitoring.api.alerts'), {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        if (response.ok) {
            const data = await response.json()
            liveStats.value = data.stats || liveStats.value
            liveIncidents.value = data.incidents || liveIncidents.value
            liveHistory.value = data.history || liveHistory.value
            liveRules.value = data.rules || liveRules.value
            liveChannels.value = data.channels || liveChannels.value
        }
    } catch (e) {
        // background error
    } finally {
        isPolling.value = false
    }
}

const toggleLive = () => {
    isLiveActive.value = !isLiveActive.value
    if (isLiveActive.value) {
        startTimer()
    } else {
        stopTimer()
    }
}

const startTimer = () => {
    stopTimer()
    if (isLiveActive.value) {
        timer = setInterval(fetchMetrics, refreshInterval.value)
    }
}

const stopTimer = () => {
    if (timer) {
        clearInterval(timer)
        timer = null
    }
}

onMounted(() => {
    startTimer()
})

onUnmounted(() => {
    stopTimer()
})

const sendTestAlert = (channelKey) => {
    router.post(route('admin.monitoring.alerts.test'), { channel: channelKey }, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = `Test notification dispatched successfully.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}
</script>

<template>
    <Head title="Threshold Alerts & Monitoring - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Monitoring', href: '#' },
                    { label: 'Server Monitoring', href: route('admin.monitoring.overview') },
                    { label: 'Automated Threshold Alerts' }
                ]"
            >
                <template #actions>
                    <Link
                        :href="route('admin.monitoring.overview')"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowLeftIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>All Metrics</span>
                    </Link>

                    <button
                        type="button"
                        @click="toggleLive"
                        :class="isLiveActive ? 'bg-emerald-50 text-emerald-700 border-emerald-300' : 'bg-slate-100 text-slate-600 border-slate-200'"
                        class="px-2.5 py-1.5 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border shadow-2xs transition cursor-pointer"
                    >
                        <span class="w-2 h-2 rounded-full" :class="isLiveActive ? 'bg-emerald-500 animate-pulse' : 'bg-slate-400'"></span>
                        <span>{{ isLiveActive ? 'Live: 3s' : 'Paused' }}</span>
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
                    title="Active Incidents"
                    :value="String(liveIncidents.length || liveStats.active_incidents || 0)"
                    :badge="liveIncidents.length > 0 ? 'Triggered' : 'All Clear'"
                    :badgeType="liveIncidents.length > 0 ? 'danger' : 'success'"
                    color="rose"
                    :icon="BellAlertIcon"
                />

                <InfoCard
                    title="Threshold Rules"
                    :value="String(liveRules.length || liveStats.total_rules || 0)"
                    badge="Guards"
                    badgeType="info"
                    color="blue"
                    :icon="ShieldCheckIcon"
                />

                <InfoCard
                    title="Dispatch Channels"
                    :value="String(liveChannels.length || 0)"
                    badge="Integrations"
                    badgeType="info"
                    color="purple"
                    :icon="PaperAirplaneIcon"
                />

                <InfoCard
                    title="Resolved in 24 Hours"
                    :value="String(liveStats.resolved_24h || liveHistory.length || 0)"
                    badge="Mitigated"
                    badgeType="success"
                    color="emerald"
                    :icon="CheckIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Threshold Alert Rules Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="px-3.5 py-2.5 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <span class="text-xs font-bold text-slate-900 uppercase tracking-wide">Configured Server Alert Policies</span>
                    <span class="text-[11px] text-slate-400 font-mono">Automated Watchdogs</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Alert Policy Name</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Monitored Metric</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Trigger Threshold</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Sustain Duration</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Severity</th>
                                <th class="py-2.5 px-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(r, index) in liveRules" :key="r.id || index" class="hover:bg-blue-50/30 transition">
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-bold text-slate-900">
                                    {{ r.name }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[11px] text-slate-600">
                                    {{ r.metric }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono font-bold text-rose-600 text-[11px]">
                                    {{ r.operator }} {{ r.threshold }}%
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[11px] text-slate-500">
                                    {{ r.duration ? r.duration + 's' : '5m' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="r.severity === 'critical' ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-amber-50 text-amber-700 border-amber-200'"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ r.severity }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 whitespace-nowrap text-center">
                                    <span class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase bg-emerald-50 text-emerald-700 border border-emerald-200 font-mono">
                                        {{ r.enabled ? 'Active' : 'Disabled' }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 4. Notification Channels Grid -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] p-3.5 shadow-2xs space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <span class="text-xs font-bold text-slate-900 uppercase tracking-wide">Notification Channels</span>
                    <span class="text-[11px] text-slate-400 font-mono">Webhook & Messaging</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div 
                        v-for="(c, ci) in liveChannels" 
                        :key="ci" 
                        class="bg-slate-50 p-2.5 rounded-[3px] border border-slate-200 flex items-center justify-between"
                    >
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs border border-blue-100 shrink-0">
                                <EnvelopeIcon v-if="c.type === 'email'" class="w-3.5 h-3.5" />
                                <ChatBubbleLeftRightIcon v-else class="w-3.5 h-3.5" />
                            </div>
                            <div>
                                <span class="font-bold text-slate-900 text-xs block leading-tight">{{ c.name }}</span>
                                <span class="text-[10.5px] text-slate-500 font-mono">{{ c.target }}</span>
                            </div>
                        </div>

                        <button 
                            type="button" 
                            @click="sendTestAlert(c.key)"
                            class="px-2 py-1 bg-white hover:bg-slate-100 text-slate-700 font-bold rounded-[3px] text-[10.5px] border border-slate-200 shadow-2xs transition cursor-pointer"
                        >
                            Test 📤
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
