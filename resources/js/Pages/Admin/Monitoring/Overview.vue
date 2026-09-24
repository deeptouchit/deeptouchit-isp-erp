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
    CircleStackIcon,
    ServerIcon,
    GlobeAltIcon,
    ArrowPathIcon,
    BoltIcon,
    XMarkIcon,
    CheckIcon,
    ExclamationTriangleIcon,
    ShieldCheckIcon,
    ClockIcon,
    CommandLineIcon,
    SignalIcon,
    FolderIcon,
    LockClosedIcon,
    PlayIcon,
    PauseIcon,
    TrashIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({}),
    },
    cpu: {
        type: Object,
        default: () => ({}),
    },
    memory: {
        type: Object,
        default: () => ({}),
    },
    disk: {
        type: Object,
        default: () => ({}),
    },
    network: {
        type: Object,
        default: () => ({}),
    },
    services: {
        type: Array,
        default: () => [],
    },
    processes: {
        type: Array,
        default: () => [],
    },
    system: {
        type: Object,
        default: () => ({}),
    },
})

// Reactive Live State
const liveStats = ref(props.stats || {})
const liveCpu = ref(props.cpu || {})
const liveMemory = ref(props.memory || {})
const liveDisk = ref(props.disk || {})
const liveNetwork = ref(props.network || {})
const liveServices = ref(props.services || [])
const liveProcesses = ref(props.processes || [])
const liveSystem = ref(props.system || {})

// Live Telemetry Engine
const refreshInterval = ref(3000)
const isLiveActive = ref(true)
const isPolling = ref(false)
let timer = null

const fetchMetrics = async () => {
    if (isPolling.value) return
    isPolling.value = true

    try {
        const response = await fetch(route('admin.monitoring.api.metrics'), {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        if (response.ok) {
            const data = await response.json()
            liveStats.value = data.stats || liveStats.value
            liveCpu.value = data.cpu || liveCpu.value
            liveMemory.value = data.memory || liveMemory.value
            liveDisk.value = data.disk || liveDisk.value
            liveNetwork.value = data.network || liveNetwork.value
            liveServices.value = data.services || liveServices.value
            liveProcesses.value = data.processes || liveProcesses.value
            liveSystem.value = data.system || liveSystem.value
        }
    } catch (err) {
        console.error('Metrics fetch error:', err)
    } finally {
        isPolling.value = false
    }
}

const toggleLive = () => {
    isLiveActive.value = !isLiveActive.value
    if (isLiveActive.value) {
        startPolling()
    } else {
        stopPolling()
    }
}

const startPolling = () => {
    stopPolling()
    if (isLiveActive.value) {
        timer = setInterval(fetchMetrics, refreshInterval.value)
    }
}

const stopPolling = () => {
    if (timer) {
        clearInterval(timer)
        timer = null
    }
}

onMounted(() => {
    startPolling()
})

onUnmounted(() => {
    stopPolling()
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
    <Head title="Server Monitoring & Health - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Monitoring', href: '#' },
                    { label: 'Server Monitoring', href: route('admin.monitoring.overview') },
                    { label: 'Live System Health Overview' }
                ]"
            >
                <template #actions>
                    <button
                        type="button"
                        @click="toggleLive"
                        :class="isLiveActive ? 'bg-emerald-50 text-emerald-700 border-emerald-300' : 'bg-slate-100 text-slate-600 border-slate-200'"
                        class="px-2.5 py-1.5 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border shadow-2xs transition cursor-pointer"
                    >
                        <span class="w-2 h-2 rounded-full" :class="isLiveActive ? 'bg-emerald-500 animate-pulse' : 'bg-slate-400'"></span>
                        <span>{{ isLiveActive ? 'Live Stream: 3s' : 'Stream Paused' }}</span>
                    </button>

                    <Link
                        :href="route('admin.monitoring.alerts')"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ShieldCheckIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Threshold Alerts</span>
                    </Link>

                    <RefreshButton />
                </template>
            </PageHeader>

            <!-- 2. Ultra-Compact KPI Info Cards (56px) -->
            <InfoCardsGrid :cols="4">
                <InfoCard
                    title="Realtime CPU Load"
                    :value="`${liveCpu.usage_percent || liveStats.cpu_percent || 0}%`"
                    :badge="`${liveCpu.cores || 4} Cores`"
                    badgeType="info"
                    :color="(liveCpu.usage_percent || 0) > 85 ? 'rose' : 'blue'"
                    :icon="CpuChipIcon"
                />

                <InfoCard
                    title="RAM Memory Pool"
                    :value="`${liveMemory.used_formatted || liveStats.memory_used || '0 B'} (${liveMemory.usage_percent || liveStats.memory_percent || 0}%)`"
                    :badge="`Total ${liveMemory.total_formatted || '0 B'}`"
                    badgeType="info"
                    :color="(liveMemory.usage_percent || 0) > 85 ? 'rose' : 'purple'"
                    :icon="ServerIcon"
                />

                <InfoCard
                    title="Storage Block Allocation"
                    :value="`${liveDisk.used_formatted || liveStats.disk_used || '0 B'} (${liveDisk.usage_percent || liveStats.disk_percent || 0}%)`"
                    :badge="`Free ${liveDisk.free_formatted || '0 B'}`"
                    badgeType="success"
                    color="emerald"
                    :icon="CircleStackIcon"
                />

                <InfoCard
                    title="Network Bandwidth I/O"
                    :value="`↓ ${liveNetwork.rx_formatted || '0 B/s'} ↑ ${liveNetwork.tx_formatted || '0 B/s'}`"
                    badge="Active Interface"
                    badgeType="info"
                    color="sky"
                    :icon="GlobeAltIcon"
                />
            </InfoCardsGrid>

            <!-- 3. System Hardware & Daemon Services Overview -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5">
                <!-- System Hardware & Specs -->
                <div class="bg-white rounded-[4px] border border-[#E2E8F0] p-3.5 shadow-2xs space-y-2 text-xs">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                                <CommandLineIcon class="w-3.5 h-3.5" />
                            </div>
                            <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">Host Architecture</h3>
                        </div>
                        <span class="font-mono text-[11px] text-slate-500 font-bold">Linux x86_64</span>
                    </div>

                    <div class="space-y-1.5 font-mono text-[11px] text-slate-600 pt-1">
                        <div class="flex justify-between">
                            <span class="text-slate-400">Hostname:</span>
                            <span class="font-bold text-slate-900">{{ liveSystem.hostname || 'server1.smarthost' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">OS Distribution:</span>
                            <span class="font-bold text-slate-900">{{ liveSystem.os || 'Ubuntu 24.04 LTS' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Kernel Version:</span>
                            <span class="text-slate-700">{{ liveSystem.kernel || '6.8.0-31-generic' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">System Uptime:</span>
                            <span class="font-bold text-emerald-700">{{ liveSystem.uptime || '14 days, 6 hours' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Core Services Health Status -->
                <div class="bg-white rounded-[4px] border border-[#E2E8F0] p-3.5 shadow-2xs space-y-2 text-xs md:col-span-2">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-[3px] bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold border border-emerald-100">
                                <SignalIcon class="w-3.5 h-3.5" />
                            </div>
                            <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">Core Daemon Services Status</h3>
                        </div>
                        <Link :href="route('admin.monitoring.services')" class="text-blue-600 font-bold hover:underline text-[11px]">
                            Detailed View →
                        </Link>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 pt-1">
                        <div 
                            v-for="(srv, si) in liveServices" 
                            :key="si" 
                            class="bg-slate-50 p-2 rounded-[3px] border border-slate-200 flex items-center justify-between"
                        >
                            <span class="font-mono font-bold text-slate-800 text-[11px]">{{ srv.name }}</span>
                            <span 
                                :class="srv.status === 'active' || srv.status === 'running' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
                                class="px-1.5 py-0.2 rounded text-[9.5px] font-bold uppercase"
                            >
                                {{ srv.status }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. Top Active System Processes Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="px-3.5 py-2.5 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs border border-blue-100">
                            <CommandLineIcon class="w-3.5 h-3.5" />
                        </div>
                        <span class="text-xs font-bold text-slate-900 uppercase tracking-wide">Top High-Resource Processes</span>
                    </div>
                    <span class="text-[11px] text-slate-400 font-mono">Live sorted by CPU/RAM utilization</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left w-20">PID</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left w-28">User</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 w-24">CPU %</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 w-24">RAM %</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Process Command Payload</th>
                                <th class="py-2.5 px-3 w-20">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(p, index) in liveProcesses" :key="p.pid || index" class="hover:bg-blue-50/30 transition">
                                <!-- # -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>

                                <!-- PID -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono font-bold text-blue-700">
                                    {{ p.pid }}
                                </td>

                                <!-- User -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono text-[11px] text-slate-800">
                                    {{ p.user }}
                                </td>

                                <!-- CPU -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono font-bold" :class="p.cpu > 50 ? 'text-rose-600' : 'text-slate-800'">
                                    {{ p.cpu }}%
                                </td>

                                <!-- RAM -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono font-bold" :class="p.mem > 50 ? 'text-rose-600' : 'text-slate-800'">
                                    {{ p.mem }}%
                                </td>

                                <!-- Command -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono text-[11px] text-slate-700 max-w-md truncate" :title="p.command">
                                    {{ p.command }}
                                </td>

                                <!-- Action -->
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
                                    No active processes found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
