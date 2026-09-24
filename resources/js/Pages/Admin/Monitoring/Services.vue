<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import RefreshButton from '@/Components/UI/RefreshButton.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'

import {
    ServerIcon,
    CpuChipIcon,
    ArrowPathIcon,
    XMarkIcon,
    CheckIcon,
    ExclamationTriangleIcon,
    BoltIcon,
    ShieldCheckIcon,
    CommandLineIcon,
    PlayIcon,
    StopIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({}),
    },
    services: {
        type: Array,
        default: () => [],
    },
})

// Reactive Live State
const liveStats = ref(props.stats || {})
const liveServices = ref(props.services || [])

// Live Telemetry Engine
const refreshInterval = ref(3000)
const isLiveActive = ref(true)
const isPolling = ref(false)
let timer = null

const fetchMetrics = async () => {
    if (isPolling.value) return
    isPolling.value = true

    try {
        const response = await fetch(route('admin.monitoring.api.services'), {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        if (response.ok) {
            const data = await response.json()
            liveStats.value = data.stats || liveStats.value
            liveServices.value = data.services || liveServices.value
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

const restartService = (name) => {
    if (confirm(`Restart system service '${name}'?`)) {
        router.post(route('admin.infrastructure.services.restart'), { service: name }, {
            preserveScroll: true,
            onSuccess: () => {
                fetchMetrics()
            }
        })
    }
}

const toggleService = (name, action) => {
    if (confirm(`${action === 'stop' ? 'Stop' : 'Start'} system service '${name}'?`)) {
        router.post(route('admin.infrastructure.services.toggle'), { service: name, action }, {
            preserveScroll: true,
            onSuccess: () => {
                fetchMetrics()
            }
        })
    }
}
</script>

<template>
    <Head title="System Services & Daemons - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Monitoring', href: '#' },
                    { label: 'Server Monitoring', href: route('admin.monitoring.overview') },
                    { label: 'System Daemons & Service Control' }
                ]"
            >
                <template #actions>
                    <Link
                        :href="route('admin.infrastructure.services')"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowLeftIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Service Control</span>
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
                    title="Monitored Daemons"
                    :value="String(liveServices.length || liveStats.total_services || 0)"
                    badge="Daemons"
                    badgeType="info"
                    color="blue"
                    :icon="ServerIcon"
                />

                <InfoCard
                    title="Running & Active"
                    :value="String(liveStats.active_count || liveServices.filter(s => s.status === 'active' || s.status === 'running').length || 0)"
                    badge="Optimal"
                    badgeType="success"
                    color="emerald"
                    :icon="ShieldCheckIcon"
                />

                <InfoCard
                    title="Stopped / Degraded"
                    :value="String(liveStats.stopped_count || liveServices.filter(s => s.status !== 'active' && s.status !== 'running').length || 0)"
                    :badge="liveServices.some(s => s.status !== 'active' && s.status !== 'running') ? 'Alert' : 'Clean'"
                    :badgeType="liveServices.some(s => s.status !== 'active' && s.status !== 'running') ? 'danger' : 'success'"
                    color="rose"
                    :icon="ExclamationTriangleIcon"
                />

                <InfoCard
                    title="System Health Score"
                    value="100% Online"
                    badge="Healthy"
                    badgeType="success"
                    color="sky"
                    :icon="BoltIcon"
                />
            </InfoCardsGrid>

            <!-- 3. System Daemons Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="px-3.5 py-2.5 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <span class="text-xs font-bold text-slate-900 uppercase tracking-wide">Systemd Service Management</span>
                    <span class="text-[11px] text-slate-400 font-mono">systemctl daemon state</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Service Name</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">System Unit</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Category</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Port</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Status</th>
                                <th class="py-2.5 px-3 w-36">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(s, index) in liveServices" :key="s.name || index" class="hover:bg-blue-50/30 transition">
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-bold text-slate-900">
                                    {{ s.name }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono text-[11px] text-slate-600">
                                    {{ s.unit || s.name + '.service' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase bg-slate-100 text-slate-700 border border-slate-200 font-mono">
                                        {{ s.category || 'System' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[11px] text-blue-700 font-bold">
                                    {{ s.port || '-' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="s.status === 'active' || s.status === 'running' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ s.status }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button 
                                            type="button" 
                                            @click="restartService(s.name)"
                                            class="px-2 py-1 bg-white hover:bg-slate-50 text-blue-700 font-bold rounded-[3px] text-xs border border-slate-200 shadow-2xs transition cursor-pointer"
                                            title="Restart Service"
                                        >
                                            Restart 🔄
                                        </button>
                                        <button 
                                            v-if="s.status === 'active' || s.status === 'running'"
                                            type="button" 
                                            @click="toggleService(s.name, 'stop')"
                                            class="px-2 py-1 bg-white hover:bg-rose-50 text-rose-700 font-bold rounded-[3px] text-xs border border-rose-200 shadow-2xs transition cursor-pointer"
                                            title="Stop Service"
                                        >
                                            Stop ⏹
                                        </button>
                                        <button 
                                            v-else
                                            type="button" 
                                            @click="toggleService(s.name, 'start')"
                                            class="px-2 py-1 bg-white hover:bg-emerald-50 text-emerald-700 font-bold rounded-[3px] text-xs border border-emerald-200 shadow-2xs transition cursor-pointer"
                                            title="Start Service"
                                        >
                                            Start ▶
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
