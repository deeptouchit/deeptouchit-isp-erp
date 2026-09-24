<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import RefreshButton from '@/Components/UI/RefreshButton.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'

import {
    GlobeAltIcon,
    SignalIcon,
    ServerIcon,
    BoltIcon,
    ArrowsRightLeftIcon,
    LockClosedIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({}),
    },
    interfaces: {
        type: Array,
        default: () => [],
    },
    sockets: {
        type: Array,
        default: () => [],
    },
    connections: {
        type: Object,
        default: () => ({}),
    },
})

// Reactive Live State
const liveStats = ref(props.stats || {})
const liveInterfaces = ref(props.interfaces || [])
const liveSockets = ref(props.sockets || [])
const liveConnections = ref(props.connections || {})

// Live Telemetry Engine
const refreshInterval = ref(3000)
const isLiveActive = ref(true)
const isPolling = ref(false)
let timer = null

const fetchMetrics = async () => {
    if (isPolling.value) return
    isPolling.value = true

    try {
        const response = await fetch(route('admin.monitoring.api.network'), {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        if (response.ok) {
            const data = await response.json()
            liveStats.value = data.stats || liveStats.value
            liveInterfaces.value = data.interfaces || liveInterfaces.value
            liveSockets.value = data.sockets || liveSockets.value
            liveConnections.value = data.connections || liveConnections.value
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
</script>

<template>
    <Head title="Network Traffic & Sockets - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Monitoring', href: '#' },
                    { label: 'Server Monitoring', href: route('admin.monitoring.overview') },
                    { label: 'Network Traffic & Sockets' }
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
                    title="Inbound Traffic Rate"
                    :value="`↓ ${liveStats.rx_formatted || '0 B/s'}`"
                    badge="Download"
                    badgeType="info"
                    color="blue"
                    :icon="GlobeAltIcon"
                />

                <InfoCard
                    title="Outbound Traffic Rate"
                    :value="`↑ ${liveStats.tx_formatted || '0 B/s'}`"
                    badge="Upload"
                    badgeType="info"
                    color="purple"
                    :icon="SignalIcon"
                />

                <InfoCard
                    title="Active Socket Descriptors"
                    :value="String(liveSockets.length || liveConnections.total || 0)"
                    badge="Sockets"
                    badgeType="info"
                    color="sky"
                    :icon="ServerIcon"
                />

                <InfoCard
                    title="Established Connections"
                    :value="String(liveConnections.established || 0)"
                    badge="TCP ESTAB"
                    badgeType="success"
                    color="emerald"
                    :icon="BoltIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Network Interfaces Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="px-3.5 py-2.5 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <span class="text-xs font-bold text-slate-900 uppercase tracking-wide">Physical & Virtual Network Adapters</span>
                    <span class="text-[11px] text-slate-400 font-mono">NIC Telemetry</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Interface Name</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Assigned IPv4 Address</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">MAC Hardware Address</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">MTU</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Total RX Inbound</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Total TX Outbound</th>
                                <th class="py-2.5 px-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(iface, index) in liveInterfaces" :key="index" class="hover:bg-blue-50/30 transition">
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono font-bold text-blue-700">
                                    {{ iface.name }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono text-slate-900 font-bold">
                                    {{ iface.ip || '127.0.0.1' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[11px] text-slate-500">
                                    {{ iface.mac || '00:00:00:00:00:00' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[11px] text-slate-600">
                                    {{ iface.mtu || '1500' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[11px] text-blue-700 font-bold">
                                    {{ iface.rx_formatted || '0 B' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[11px] text-purple-700 font-bold">
                                    {{ iface.tx_formatted || '0 B' }}
                                </td>
                                <td class="py-2.5 px-3 whitespace-nowrap text-center">
                                    <span 
                                        :class="iface.status === 'UP' || iface.status === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-500 border-slate-200'"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ iface.status || 'UP' }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 4. Active Listening Ports & Sockets Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="px-3.5 py-2.5 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <span class="text-xs font-bold text-slate-900 uppercase tracking-wide">Listening Sockets & Ports</span>
                    <span class="text-[11px] text-slate-400 font-mono">netstat / ss table</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 w-20">Protocol</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Local Address : Port</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Foreign Address</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">State</th>
                                <th class="py-2.5 px-3 text-left">Process Name / PID</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap font-mono text-[11px]">
                            <tr v-for="(sock, index) in liveSockets" :key="index" class="hover:bg-blue-50/30 transition">
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 whitespace-nowrap font-sans">
                                    {{ index + 1 }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap uppercase font-bold text-slate-700">
                                    {{ sock.protocol || 'tcp' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-bold text-blue-700">
                                    {{ sock.local_address }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap text-slate-600">
                                    {{ sock.foreign_address || '*:*' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase bg-emerald-50 text-emerald-700 border border-emerald-200 font-sans">
                                        {{ sock.state || 'LISTEN' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-left whitespace-nowrap text-slate-800 font-bold">
                                    {{ sock.process || '-' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
