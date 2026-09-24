<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import RefreshButton from '@/Components/UI/RefreshButton.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'

import {
    CircleStackIcon,
    BoltIcon,
    ArrowPathIcon,
    XMarkIcon,
    CheckIcon,
    ExclamationTriangleIcon,
    ShieldCheckIcon,
    ClockIcon,
    CommandLineIcon,
    SignalIcon,
    ServerIcon,
    ArchiveBoxIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({}),
    },
    breakdown: {
        type: Object,
        default: () => ({}),
    },
    hardware: {
        type: Object,
        default: () => ({}),
    },
    processes: {
        type: Array,
        default: () => [],
    },
})

// Reactive Live State
const liveStats = ref(props.stats || {})
const liveBreakdown = ref(props.breakdown || {})
const liveHardware = ref(props.hardware || {})
const liveProcesses = ref(props.processes || [])

// Live Telemetry Engine
const refreshInterval = ref(3000)
const isLiveActive = ref(true)
const isPolling = ref(false)
let timer = null

const fetchMetrics = async () => {
    if (isPolling.value) return
    isPolling.value = true

    try {
        const response = await fetch(route('admin.monitoring.api.ram'), {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        if (response.ok) {
            const data = await response.json()
            liveStats.value = data.stats || liveStats.value
            liveBreakdown.value = data.breakdown || liveBreakdown.value
            liveHardware.value = data.hardware || liveHardware.value
            liveProcesses.value = data.processes || liveProcesses.value
        }
    } catch (e) {
        // background fetch error
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

const killProcess = (pid) => {
    if (confirm(`Terminate process PID ${pid}?`)) {
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
    <Head title="RAM & Memory Telemetry - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Monitoring', href: '#' },
                    { label: 'Server Monitoring', href: route('admin.monitoring.overview') },
                    { label: 'RAM & Memory Telemetry' }
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

            <!-- 2. Ultra-Compact KPI Info Cards (56px) -->
            <InfoCardsGrid :cols="4">
                <InfoCard
                    title="Physical Total RAM"
                    :value="liveStats.total_formatted || '16 GB'"
                    badge="DIMM Pool"
                    badgeType="info"
                    color="blue"
                    :icon="ServerIcon"
                />

                <InfoCard
                    title="Memory In-Use"
                    :value="`${liveStats.used_formatted || '0 B'} (${liveStats.usage_percent || 0}%)`"
                    :badge="`Free ${liveStats.free_formatted || '0 B'}`"
                    badgeType="info"
                    :color="(liveStats.usage_percent || 0) > 85 ? 'rose' : 'purple'"
                    :icon="CircleStackIcon"
                />

                <InfoCard
                    title="Cached & Buffers"
                    :value="liveBreakdown.cached_formatted || liveStats.cached_formatted || '0 B'"
                    badge="PageCache"
                    badgeType="success"
                    color="emerald"
                    :icon="ArchiveBoxIcon"
                />

                <InfoCard
                    title="Swap Allocation"
                    :value="`${liveStats.swap_used_formatted || '0 B'} / ${liveStats.swap_total_formatted || '0 B'}`"
                    badge="Paging"
                    badgeType="info"
                    color="sky"
                    :icon="BoltIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Memory Subsystem Breakdown Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                <!-- Physical RAM Gauge -->
                <div class="bg-white rounded-[4px] border border-[#E2E8F0] p-3.5 shadow-2xs space-y-2.5 text-xs">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                        <span class="font-bold text-slate-900 uppercase tracking-wide">RAM Allocation Breakdown</span>
                        <span class="font-mono text-xs font-bold text-blue-700">{{ liveStats.usage_percent || 0 }}% Utilized</span>
                    </div>

                    <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden flex">
                        <div class="bg-blue-600 h-2" :style="{ width: `${liveStats.usage_percent || 0}%` }"></div>
                        <div class="bg-emerald-500 h-2" :style="{ width: `${liveBreakdown.cached_percent || 15}%` }"></div>
                    </div>

                    <div class="grid grid-cols-3 gap-2 pt-1 font-mono text-[11px]">
                        <div class="bg-slate-50 p-2 rounded-[3px] border border-slate-200">
                            <span class="text-slate-400 block">Active Applications:</span>
                            <span class="font-bold text-blue-700">{{ liveStats.used_formatted || '0 B' }}</span>
                        </div>
                        <div class="bg-slate-50 p-2 rounded-[3px] border border-slate-200">
                            <span class="text-slate-400 block">Buffers / Cache:</span>
                            <span class="font-bold text-emerald-700">{{ liveBreakdown.cached_formatted || '0 B' }}</span>
                        </div>
                        <div class="bg-slate-50 p-2 rounded-[3px] border border-slate-200">
                            <span class="text-slate-400 block">Available Free:</span>
                            <span class="font-bold text-slate-700">{{ liveStats.free_formatted || '0 B' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Swap Memory Gauge -->
                <div class="bg-white rounded-[4px] border border-[#E2E8F0] p-3.5 shadow-2xs space-y-2.5 text-xs">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                        <span class="font-bold text-slate-900 uppercase tracking-wide">Swap Disk Memory</span>
                        <span class="font-mono text-xs font-bold text-slate-700">{{ liveStats.swap_percent || 0 }}% Paged</span>
                    </div>

                    <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                        <div class="bg-purple-600 h-2 rounded-full" :style="{ width: `${liveStats.swap_percent || 0}%` }"></div>
                    </div>

                    <div class="grid grid-cols-2 gap-2 pt-1 font-mono text-[11px]">
                        <div class="bg-slate-50 p-2 rounded-[3px] border border-slate-200">
                            <span class="text-slate-400 block">Swap Used:</span>
                            <span class="font-bold text-purple-700">{{ liveStats.swap_used_formatted || '0 B' }}</span>
                        </div>
                        <div class="bg-slate-50 p-2 rounded-[3px] border border-slate-200">
                            <span class="text-slate-400 block">Swap Free:</span>
                            <span class="font-bold text-slate-700">{{ liveStats.swap_free_formatted || '0 B' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. Top RAM Consuming Processes Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="px-3.5 py-2.5 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs border border-blue-100">
                            <CommandLineIcon class="w-3.5 h-3.5" />
                        </div>
                        <span class="text-xs font-bold text-slate-900 uppercase tracking-wide">Top Resident Memory Processes</span>
                    </div>
                    <span class="text-[11px] text-slate-400 font-mono">Resident set size (RSS)</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left w-20">PID</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left w-28">User</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 w-24">RAM %</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 w-24">CPU %</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Process Command Payload</th>
                                <th class="py-2.5 px-3 w-20">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(p, index) in liveProcesses" :key="p.pid || index" class="hover:bg-blue-50/30 transition">
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono font-bold text-blue-700">
                                    {{ p.pid }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono text-[11px] text-slate-800">
                                    {{ p.user }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono font-bold" :class="p.mem > 50 ? 'text-rose-600' : 'text-purple-700'">
                                    {{ p.mem }}%
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-slate-700">
                                    {{ p.cpu }}%
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono text-[11px] text-slate-700 max-w-md truncate" :title="p.command">
                                    {{ p.command }}
                                </td>
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <button
                                        type="button"
                                        @click="killProcess(p.pid)"
                                        class="px-2 py-1 bg-white hover:bg-rose-50 text-rose-700 font-bold rounded-[3px] text-xs border border-rose-200 shadow-2xs transition cursor-pointer"
                                        title="Kill Process"
                                    >
                                        Kill
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="!liveProcesses || liveProcesses.length === 0">
                                <td colspan="7" class="py-8 text-center text-slate-400 font-sans">
                                    No active processes.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
