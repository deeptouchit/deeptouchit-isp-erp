<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import RefreshButton from '@/Components/UI/RefreshButton.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'

import {
    CpuChipIcon,
    CommandLineIcon,
    ArrowPathIcon,
    BoltIcon,
    ServerIcon,
    ShieldCheckIcon,
    DocumentTextIcon,
    CircleStackIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({}),
    },
    versions: {
        type: Array,
        default: () => [],
    },
    workers: {
        type: Array,
        default: () => [],
    },
    logs: {
        type: Array,
        default: () => [],
    },
})

// Reactive Live State
const liveStats = ref(props.stats || {})
const liveVersions = ref(props.versions || [])
const liveWorkers = ref(props.workers || [])
const liveLogs = ref(props.logs || [])

// Live Telemetry Engine
const refreshInterval = ref(3000)
const isLiveActive = ref(true)
const isPolling = ref(false)
let timer = null

const fetchMetrics = async () => {
    if (isPolling.value) return
    isPolling.value = true

    try {
        const response = await fetch(route('admin.monitoring.api.php-fpm'), {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        if (response.ok) {
            const data = await response.json()
            liveStats.value = data.stats || liveStats.value
            liveVersions.value = data.versions || liveVersions.value
            liveWorkers.value = data.workers || liveWorkers.value
            liveLogs.value = data.logs || liveLogs.value
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

const restartFpm = (version) => {
    if (confirm(`Restart PHP-FPM service for PHP ${version}?`)) {
        router.post(route('admin.php.restart', { version }), {}, {
            preserveScroll: true,
            onSuccess: () => {
                fetchMetrics()
            }
        })
    }
}

const killWorker = (pid) => {
    if (confirm(`Terminate PHP-FPM worker PID ${pid}?`)) {
        router.post(route('admin.monitoring.kill-process'), { pid }, {
            preserveScroll: true,
            onSuccess: () => {
                fetchMetrics()
            }
        })
    }
}
</script>

<template>
    <Head title="PHP-FPM Telemetry - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Monitoring', href: '#' },
                    { label: 'Server Monitoring', href: route('admin.monitoring.overview') },
                    { label: 'PHP-FPM Engine Telemetry' }
                ]"
            >
                <template #actions>
                    <Link
                        :href="route('admin.php.pools')"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowLeftIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>FPM Pool Manager</span>
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

            <!-- 2. Ultra-Compact KPI Info Cards (56px) -->
            <InfoCardsGrid :cols="4">
                <InfoCard
                    title="PHP-FPM Daemon Engine"
                    value="Online & Running"
                    badge="Active"
                    badgeType="success"
                    color="emerald"
                    :icon="ShieldCheckIcon"
                />

                <InfoCard
                    title="Active PHP Engines"
                    :value="String(liveVersions.length || 0)"
                    badge="Multi-PHP"
                    badgeType="info"
                    color="blue"
                    :icon="CpuChipIcon"
                />

                <InfoCard
                    title="Active Workers"
                    :value="String(liveWorkers.length || liveStats.active_workers || 0)"
                    badge="FPM Threads"
                    badgeType="info"
                    color="purple"
                    :icon="CommandLineIcon"
                />

                <InfoCard
                    title="Aggregate PHP Memory"
                    :value="liveStats.memory_formatted || '0 B'"
                    badge="RAM Load"
                    badgeType="info"
                    color="sky"
                    :icon="ServerIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Installed PHP-FPM Version Daemons Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div 
                    v-for="(v, vi) in liveVersions" 
                    :key="vi" 
                    class="bg-white rounded-[4px] border border-[#E2E8F0] p-3 shadow-2xs space-y-2 text-xs"
                >
                    <div class="flex items-center justify-between pb-1.5 border-b border-slate-100">
                        <span class="font-bold text-slate-900 font-mono text-xs">PHP {{ v.version }}</span>
                        <span 
                            :class="v.status === 'active' || v.status === 'running' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-500 border-slate-200'"
                            class="px-1.5 py-0.2 rounded text-[10px] font-bold uppercase border font-mono"
                        >
                            {{ v.status }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between text-[11px] font-mono text-slate-500">
                        <span>Workers: {{ v.workers_count || 0 }}</span>
                        <button 
                            type="button" 
                            @click="restartFpm(v.version)"
                            class="text-blue-600 hover:underline font-bold cursor-pointer"
                        >
                            Restart 🔄
                        </button>
                    </div>
                </div>
            </div>

            <!-- 4. Active FPM Workers Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="px-3.5 py-2.5 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <span class="text-xs font-bold text-slate-900 uppercase tracking-wide">Active PHP-FPM Worker Threads</span>
                    <span class="text-[11px] text-slate-400 font-mono">Live request execution stream</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left w-20">PID</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">FPM Pool / Domain</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">PHP Version</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Worker State</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Executing URI / Script</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 w-20">CPU %</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 w-20">RAM %</th>
                                <th class="py-2.5 px-3 w-20">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(w, index) in liveWorkers" :key="w.pid || index" class="hover:bg-blue-50/30 transition">
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono font-bold text-blue-700">
                                    {{ w.pid }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono font-bold text-slate-900">
                                    {{ w.pool || 'www' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[11px] text-slate-600">
                                    PHP {{ w.version || '8.3' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="w.state === 'Running' || w.state === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-500 border-slate-200'"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ w.state || 'Idle' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono text-[11px] text-slate-700 max-w-md truncate" :title="w.request_uri">
                                    {{ w.request_uri || '-' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[11px] text-slate-700">
                                    {{ w.cpu || 0 }}%
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[11px] text-slate-700">
                                    {{ w.mem || 0 }}%
                                </td>
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <button
                                        type="button"
                                        @click="killWorker(w.pid)"
                                        class="px-2 py-1 bg-white hover:bg-rose-50 text-rose-700 font-bold rounded-[3px] text-xs border border-rose-200 shadow-2xs transition cursor-pointer"
                                        title="Kill Worker"
                                    >
                                        Kill
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="!liveWorkers || liveWorkers.length === 0">
                                <td colspan="9" class="py-8 text-center text-slate-400 font-sans">
                                    No active PHP-FPM worker threads found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
