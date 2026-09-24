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
    ServerIcon,
    BoltIcon,
    ArrowPathIcon,
    ShieldCheckIcon,
    CommandLineIcon,
    ArrowsRightLeftIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({}),
    },
    engines: {
        type: Array,
        default: () => [],
    },
    processes: {
        type: Array,
        default: () => [],
    },
    databases: {
        type: Array,
        default: () => [],
    },
})

// Reactive Live State
const liveStats = ref(props.stats || {})
const liveEngines = ref(props.engines || [])
const liveProcesses = ref(props.processes || [])
const liveDatabases = ref(props.databases || [])

// Live Telemetry Engine
const refreshInterval = ref(3000)
const isLiveActive = ref(true)
const isPolling = ref(false)
let timer = null

const fetchMetrics = async () => {
    if (isPolling.value) return
    isPolling.value = true

    try {
        const response = await fetch(route('admin.monitoring.api.database'), {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        if (response.ok) {
            const data = await response.json()
            liveStats.value = data.stats || liveStats.value
            liveEngines.value = data.engines || liveEngines.value
            liveProcesses.value = data.processes || liveProcesses.value
            liveDatabases.value = data.databases || liveDatabases.value
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

const killQuery = (id) => {
    if (confirm(`Kill active database query thread ID ${id}?`)) {
        router.post(route('admin.databases.kill-query', { id }), {}, {
            preserveScroll: true,
            onSuccess: () => {
                fetchMetrics()
            }
        })
    }
}
</script>

<template>
    <Head title="Database Telemetry - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Monitoring', href: '#' },
                    { label: 'Server Monitoring', href: route('admin.monitoring.overview') },
                    { label: 'Database Engines & Throughput' }
                ]"
            >
                <template #actions>
                    <Link
                        :href="route('admin.databases')"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowLeftIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Database Catalog</span>
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
                    title="MySQL / MariaDB Status"
                    value="Port 3306 Online"
                    badge="Active"
                    badgeType="success"
                    color="emerald"
                    :icon="ShieldCheckIcon"
                />

                <InfoCard
                    title="PostgreSQL Engine"
                    value="Port 5432 Online"
                    badge="Active"
                    badgeType="success"
                    color="purple"
                    :icon="CircleStackIcon"
                />

                <InfoCard
                    title="Active Connections"
                    :value="String(liveStats.active_connections || liveProcesses.length || 0)"
                    badge="Threads"
                    badgeType="info"
                    color="blue"
                    :icon="BoltIcon"
                />

                <InfoCard
                    title="Total DB Space"
                    :value="liveStats.storage_formatted || '0 B'"
                    badge="Catalog Size"
                    badgeType="info"
                    color="sky"
                    :icon="ServerIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Active Running Queries Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="px-3.5 py-2.5 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <span class="text-xs font-bold text-slate-900 uppercase tracking-wide">Live Database Processlist & Active Queries</span>
                    <span class="text-[11px] text-slate-400 font-mono">SHOW FULL PROCESSLIST</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left w-20">Thread ID</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Database</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">DB User</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Command</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 w-20">Time (s)</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Query Execution Text</th>
                                <th class="py-2.5 px-3 w-20">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(p, index) in liveProcesses" :key="p.id || index" class="hover:bg-blue-50/30 transition font-mono text-[11px]">
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 whitespace-nowrap font-sans">
                                    {{ index + 1 }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-bold text-blue-700">
                                    #{{ p.id }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-bold text-slate-900">
                                    {{ p.db || 'information_schema' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap text-slate-600">
                                    @{{ p.user || 'root' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center text-slate-700">
                                    {{ p.command || 'Query' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-bold" :class="p.time > 10 ? 'text-rose-600' : 'text-slate-700'">
                                    {{ p.time || 0 }}s
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap text-slate-800 max-w-md truncate" :title="p.info">
                                    {{ p.info || 'SELECT 1;' }}
                                </td>
                                <td class="py-2.5 px-3 text-center whitespace-nowrap font-sans">
                                    <button
                                        type="button"
                                        @click="killQuery(p.id)"
                                        class="px-2 py-1 bg-white hover:bg-rose-50 text-rose-700 font-bold rounded-[3px] text-xs border border-rose-200 shadow-2xs transition cursor-pointer"
                                        title="Kill Query"
                                    >
                                        Kill
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="!liveProcesses || liveProcesses.length === 0">
                                <td colspan="8" class="py-8 text-center text-slate-400 font-sans">
                                    No active queries running in database engine.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
