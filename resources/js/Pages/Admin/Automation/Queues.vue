<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import RefreshButton from '@/Components/UI/RefreshButton.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'

import {
    QueueListIcon,
    CpuChipIcon,
    ExclamationTriangleIcon,
    CheckIcon,
    ArrowPathIcon,
    BoltIcon,
    ServerIcon,
    TrashIcon,
    ClockIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({}),
    },
    supervisor: {
        type: Object,
        default: () => null,
    },
    workers: {
        type: Array,
        default: () => [],
    },
    pools: {
        type: Array,
        default: () => [],
    },
    pending_jobs: {
        type: Array,
        default: () => [],
    },
    failed_jobs: {
        type: Array,
        default: () => [],
    },
    batches: {
        type: Array,
        default: () => [],
    },
})

// Reactive Live State
const liveStats = ref(props.stats || {})
const liveSupervisor = ref(props.supervisor || null)
const liveWorkers = ref(props.workers || [])
const livePools = ref(props.pools || [])
const livePendingJobs = ref(props.pending_jobs || [])
const liveFailedJobs = ref(props.failed_jobs || [])
const liveBatches = ref(props.batches || [])
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
        const response = await fetch(route('admin.automation.api.queues'), {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        if (response.ok) {
            const data = await response.json()
            liveStats.value = data.stats || liveStats.value
            liveSupervisor.value = data.supervisor || liveSupervisor.value
            liveWorkers.value = data.workers || liveWorkers.value
            livePools.value = data.pools || livePools.value
            livePendingJobs.value = data.pending_jobs || livePendingJobs.value
            liveFailedJobs.value = data.failed_jobs || liveFailedJobs.value
            liveBatches.value = data.batches || liveBatches.value
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

const restartSupervisor = () => {
    router.post(route('admin.automation.queues.restart'), {}, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = 'Supervisor worker pools restarted.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

const retryAllFailed = () => {
    router.post(route('admin.automation.queues.retry-all'), {}, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = 'Retrying all failed jobs...'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

const retrySingleFailed = (id) => {
    router.post(route('admin.automation.queues.retry-job', { id }), {}, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = `Job #${id} queued for retry.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

const deleteSingleFailed = (id) => {
    router.delete(route('admin.automation.queues.forget-job', { id }), {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = `Failed job #${id} deleted.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}
</script>

<template>
    <Head title="Queue Workers & Jobs - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Automation', href: '#' },
                    { label: 'Automation & Schedulers', href: route('admin.automation.cron') },
                    { label: 'Queue Workers & Background Jobs' }
                ]"
            >
                <template #actions>
                    <button
                        type="button"
                        @click="restartSupervisor"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowPathIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Restart Workers</span>
                    </button>

                    <button
                        v-if="liveFailedJobs.length > 0"
                        type="button"
                        @click="retryAllFailed"
                        class="px-2.5 py-1.5 bg-white hover:bg-rose-50 text-rose-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-rose-200 shadow-2xs transition cursor-pointer"
                    >
                        <BoltIcon class="w-3.5 h-3.5" />
                        <span>Retry Failed ({{ liveFailedJobs.length }})</span>
                    </button>

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
                    title="Active Queue Workers"
                    :value="String(liveWorkers.length || liveStats.total_workers || 4)"
                    badge="Supervisor"
                    badgeType="success"
                    color="blue"
                    :icon="CpuChipIcon"
                />

                <InfoCard
                    title="Pending Queue Jobs"
                    :value="String(livePendingJobs.length || liveStats.pending_jobs || 0)"
                    badge="Pending"
                    badgeType="info"
                    color="purple"
                    :icon="QueueListIcon"
                />

                <InfoCard
                    title="Processed Past 24h"
                    :value="String(liveStats.processed_today || '4.2k')"
                    badge="Completed"
                    badgeType="success"
                    color="emerald"
                    :icon="CheckIcon"
                />

                <InfoCard
                    title="Failed Exceptions"
                    :value="String(liveFailedJobs.length || liveStats.failed_jobs || 0)"
                    :badge="liveFailedJobs.length > 0 ? 'Failed' : 'Clean'"
                    :badgeType="liveFailedJobs.length > 0 ? 'danger' : 'success'"
                    color="rose"
                    :icon="ExclamationTriangleIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Worker Pools Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div 
                    v-for="(p, pi) in livePools" 
                    :key="pi" 
                    class="bg-white rounded-[4px] border border-[#E2E8F0] p-3 shadow-2xs space-y-1.5 text-xs"
                >
                    <div class="flex items-center justify-between pb-1 border-b border-slate-100">
                        <span class="font-bold text-slate-900 font-mono text-xs">{{ p.name }}</span>
                        <span class="px-1.5 py-0.2 rounded text-[10px] font-bold uppercase bg-emerald-50 text-emerald-700 border border-emerald-200 font-mono">
                            {{ p.processes || 1 }} Workers
                        </span>
                    </div>

                    <div class="flex items-center justify-between text-[11px] font-mono text-slate-500">
                        <span>Queue: {{ p.queue || 'default' }}</span>
                        <span class="text-slate-400">Balancing: {{ p.balance || 'auto' }}</span>
                    </div>
                </div>
            </div>

            <!-- 4. Failed Jobs Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="px-3.5 py-2.5 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <span class="text-xs font-bold text-slate-900 uppercase tracking-wide">Failed Job Queue & Dead Letters</span>
                    <span class="text-[11px] text-slate-400 font-mono">failed_jobs table</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left w-24">Job ID</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Queue Channel</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Payload Job Class</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Exception Reason</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Failed At</th>
                                <th class="py-2.5 px-3 w-28">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(job, index) in liveFailedJobs" :key="job.id || index" class="hover:bg-rose-50/20 transition">
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono font-bold text-rose-700">
                                    #{{ job.id }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono text-slate-900">
                                    {{ job.queue || 'default' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono font-bold text-slate-800">
                                    {{ job.payload_name || job.name || 'App\\Jobs\\ProcessTask' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap text-rose-700 max-w-sm truncate" :title="job.exception">
                                    {{ job.exception || 'Connection timeout' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[10.5px] text-slate-500">
                                    {{ job.failed_at ? new Date(job.failed_at).toLocaleTimeString() : 'Recent' }}
                                </td>
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button 
                                            type="button" 
                                            @click="retrySingleFailed(job.id)"
                                            class="px-2 py-1 bg-white hover:bg-slate-50 text-blue-700 font-bold rounded-[3px] text-xs border border-slate-200 shadow-2xs transition cursor-pointer"
                                            title="Retry Job"
                                        >
                                            Retry ⚡
                                        </button>
                                        <button 
                                            type="button" 
                                            @click="deleteSingleFailed(job.id)"
                                            class="px-2 py-1 bg-white hover:bg-rose-50 text-rose-700 font-bold rounded-[3px] text-xs border border-rose-200 shadow-2xs transition cursor-pointer"
                                            title="Delete Failed Record"
                                        >
                                            <TrashIcon class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!liveFailedJobs || liveFailedJobs.length === 0">
                                <td colspan="7" class="py-8 text-center text-slate-400 font-sans">
                                    No failed jobs in queue. Everything running cleanly!
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
