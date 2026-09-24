<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import { 
    CpuChipIcon, 
    CircleStackIcon, 
    ServerIcon, 
    FolderIcon,
    GlobeAltIcon,
    ShieldCheckIcon,
    ArrowUpCircleIcon,
    ChartBarIcon,
    CommandLineIcon,
    ClockIcon,
    BoltIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    subscription: {
        type: Object,
        default: null
    },
    plan: {
        type: Object,
        default: null
    },
    metrics: {
        type: Object,
        default: () => ({})
    },
    hourlyTimeline: {
        type: Array,
        default: () => []
    },
    activeProcesses: {
        type: Array,
        default: () => []
    },
    serverInfo: {
        type: Object,
        default: () => ({})
    }
})

// Active chart tab (CPU vs RAM vs IO)
const activeChartMetric = ref('cpu')
const activeTimeframe = ref('24h')

// Compute SVG chart coordinates for 24-hour timeline
const maxChartValue = computed(() => {
    if (activeChartMetric.value === 'cpu') return 100
    if (activeChartMetric.value === 'ram') return props.metrics?.ram?.limit || 768
    if (activeChartMetric.value === 'io') return props.metrics?.io?.limit || 10
    return 100
})

const chartPoints = computed(() => {
    const list = props.hourlyTimeline || []
    if (list.length === 0) return []
    const width = 800
    const height = 180
    const padding = 20
    const stepX = (width - padding * 2) / Math.max(1, list.length - 1)

    return list.map((item, index) => {
        let val = item.cpu
        if (activeChartMetric.value === 'ram') val = item.ram
        if (activeChartMetric.value === 'io') val = item.io
        
        const x = padding + index * stepX
        const normalized = Math.min(1, Math.max(0, val / maxChartValue.value))
        const y = height - padding - (normalized * (height - padding * 2))
        return { x, y, val, hour: item.hour }
    })
})

const svgPolylinePoints = computed(() => {
    return chartPoints.value.map(p => `${p.x},${p.y}`).join(' ')
})

const svgAreaPoints = computed(() => {
    if (chartPoints.value.length === 0) return ''
    const first = chartPoints.value[0]
    const last = chartPoints.value[chartPoints.value.length - 1]
    const bottom = 180 - 20
    return `${first.x},${bottom} ${svgPolylinePoints.value} ${last.x},${bottom}`
})

const hoveredPoint = ref(null)

// Structured Resource Limit Table Items
const resourceTableItems = computed(() => [
    {
        name: 'CPU Core Usage',
        desc: 'Isolated vCPU Limit',
        limit: `${props.metrics?.cpu?.limit || 100}% (1 Core)`,
        usage: `${props.metrics?.cpu?.used || 0}%`,
        percent: props.metrics?.cpu?.percent || 0,
        faults: '0',
        status: 'Optimal'
    },
    {
        name: 'Physical Memory (RAM)',
        desc: 'DDR4 Dedicated Pool',
        limit: `${props.metrics?.ram?.limit || 768} MB`,
        usage: `${props.metrics?.ram?.used || 0} MB`,
        percent: props.metrics?.ram?.percent || 0,
        faults: '0',
        status: 'Optimal'
    },
    {
        name: 'NVMe Disk Storage',
        desc: 'High-Speed SSD Array',
        limit: `${props.metrics?.disk?.limit || 2048} MB`,
        usage: `${props.metrics?.disk?.used || 0} MB`,
        percent: props.metrics?.disk?.percent || 0,
        faults: '0',
        status: 'Optimal'
    },
    {
        name: 'Inodes (File Count)',
        desc: 'Total Files & Folders',
        limit: `${Number(props.metrics?.inodes?.limit || 150000).toLocaleString()}`,
        usage: `${Number(props.metrics?.inodes?.used || 0).toLocaleString()}`,
        percent: props.metrics?.inodes?.percent || 0,
        faults: '0',
        status: 'Optimal'
    },
    {
        name: 'Disk I/O Speed',
        desc: 'Read / Write Throughput',
        limit: `${props.metrics?.io?.limit || 10} MB/s`,
        usage: `${props.metrics?.io?.used || 0} MB/s`,
        percent: props.metrics?.io?.percent || 0,
        faults: '0',
        status: 'Optimal'
    },
    {
        name: 'IOPS Limit',
        desc: 'I/O Operations / Sec',
        limit: `${props.metrics?.io?.iops_limit || 1024} IOPS`,
        usage: `${props.metrics?.io?.iops_used || 42} IOPS`,
        percent: Math.round(((props.metrics?.io?.iops_used || 42) / (props.metrics?.io?.iops_limit || 1024)) * 100),
        faults: '0',
        status: 'Optimal'
    },
    {
        name: 'Concurrent Tasks (NPROC)',
        desc: 'Active Process Threads',
        limit: `${props.metrics?.nproc?.limit || 25} Tasks`,
        usage: `${props.metrics?.nproc?.used || 0} Tasks`,
        percent: props.metrics?.nproc?.percent || 0,
        faults: '0',
        status: 'Optimal'
    },
    {
        name: 'Monthly Bandwidth',
        desc: '10Gbps BDIX Transfer',
        limit: `${props.metrics?.bandwidth?.limit || 50} GB`,
        usage: `${props.metrics?.bandwidth?.used || 0} GB`,
        percent: props.metrics?.bandwidth?.percent || 0,
        faults: '0',
        status: 'Optimal'
    },
])
</script>

<template>
    <Head title="Resource Usage - DeepTouch Host" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto space-y-4">
            
            <!-- 1. Page Header with Clean Breadcrumbs & Action Controls -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Hosting Plan', href: route('subscriptions.index') },
                    { label: 'Resource Usage' }
                ]"
                :show-refresh="true"
                @refreshed="router.reload({ preserveScroll: true })"
            >
                <template #actions>
                    <div class="flex items-center gap-1 bg-slate-100 p-0.5 rounded-[3px] border border-slate-200 text-xs font-bold">
                        <button 
                            @click="activeTimeframe = '1h'" 
                            class="px-2 py-1 rounded-[2px] transition cursor-pointer"
                            :class="activeTimeframe === '1h' ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-500 hover:text-slate-800'"
                        >
                            1h
                        </button>
                        <button 
                            @click="activeTimeframe = '24h'" 
                            class="px-2 py-1 rounded-[2px] transition cursor-pointer"
                            :class="activeTimeframe === '24h' ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-500 hover:text-slate-800'"
                        >
                            24h
                        </button>
                        <button 
                            @click="activeTimeframe = '7d'" 
                            class="px-2 py-1 rounded-[2px] transition cursor-pointer"
                            :class="activeTimeframe === '7d' ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-500 hover:text-slate-800'"
                        >
                            7d
                        </button>
                    </div>

                    <Link 
                        :href="route('hosting.upgrade')" 
                        class="px-2.5 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowUpCircleIcon class="w-3.5 h-3.5 text-amber-600" />
                        <span>Upgrade Plan</span>
                    </Link>
                </template>
            </PageHeader>

            <!-- 2. Primary Telemetry Metric Cards (4-Column Grid) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                
                <!-- 1. CPU Usage -->
                <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-2xs space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <CpuChipIcon class="w-4 h-4 text-blue-600" />
                            <span>CPU Usage</span>
                        </span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 uppercase">
                            Normal
                        </span>
                    </div>

                    <div>
                        <div class="flex items-baseline justify-between">
                            <span class="text-xl font-black text-slate-900 font-mono">{{ metrics.cpu?.used }}%</span>
                            <span class="text-xs text-slate-500 font-mono">Limit: {{ metrics.cpu?.limit }}%</span>
                        </div>
                        <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden mt-1.5">
                            <div 
                                class="bg-blue-600 h-full rounded-full transition-all duration-500" 
                                :style="{ width: `${Math.max(4, metrics.cpu?.percent)}%` }"
                            ></div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-100">
                        <span>Allocated Cores:</span>
                        <strong class="text-slate-700 font-mono">1 vCPU</strong>
                    </div>
                </div>

                <!-- 2. Memory (RAM) -->
                <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-2xs space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <BoltIcon class="w-4 h-4 text-purple-600" />
                            <span>Memory (RAM)</span>
                        </span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 uppercase">
                            Normal
                        </span>
                    </div>

                    <div>
                        <div class="flex items-baseline justify-between">
                            <span class="text-xl font-black text-slate-900 font-mono">{{ metrics.ram?.used }} MB</span>
                            <span class="text-xs text-slate-500 font-mono">Limit: {{ metrics.ram?.limit }} MB</span>
                        </div>
                        <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden mt-1.5">
                            <div 
                                class="bg-purple-600 h-full rounded-full transition-all duration-500" 
                                :style="{ width: `${Math.max(4, metrics.ram?.percent)}%` }"
                            ></div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-100">
                        <span>Free RAM:</span>
                        <strong class="text-slate-700 font-mono">{{ (metrics.ram?.limit - metrics.ram?.used) }} MB</strong>
                    </div>
                </div>

                <!-- 3. Disk Space -->
                <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-2xs space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <FolderIcon class="w-4 h-4 text-amber-600" />
                            <span>Disk Storage</span>
                        </span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 border border-slate-200 uppercase">
                            {{ metrics.disk?.percent }}% Used
                        </span>
                    </div>

                    <div>
                        <div class="flex items-baseline justify-between">
                            <span class="text-xl font-black text-slate-900 font-mono">{{ metrics.disk?.used }} MB</span>
                            <span class="text-xs text-slate-500 font-mono">Limit: {{ metrics.disk?.limit }} MB</span>
                        </div>
                        <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden mt-1.5">
                            <div 
                                class="bg-amber-500 h-full rounded-full transition-all duration-500" 
                                :style="{ width: `${Math.max(4, metrics.disk?.percent)}%` }"
                            ></div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-100">
                        <span>Array Type:</span>
                        <strong class="text-slate-700 font-mono">NVMe SSD</strong>
                    </div>
                </div>

                <!-- 4. Bandwidth -->
                <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-2xs space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <GlobeAltIcon class="w-4 h-4 text-emerald-600" />
                            <span>Bandwidth</span>
                        </span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 uppercase">
                            10Gbps
                        </span>
                    </div>

                    <div>
                        <div class="flex items-baseline justify-between">
                            <span class="text-xl font-black text-slate-900 font-mono">{{ metrics.bandwidth?.used }} GB</span>
                            <span class="text-xs text-slate-500 font-mono">Limit: {{ metrics.bandwidth?.limit }} GB</span>
                        </div>
                        <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden mt-1.5">
                            <div 
                                class="bg-emerald-500 h-full rounded-full transition-all duration-500" 
                                :style="{ width: `${Math.max(4, metrics.bandwidth?.percent)}%` }"
                            ></div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-100">
                        <span>Connection:</span>
                        <strong class="text-slate-700 font-mono">BDIX Port</strong>
                    </div>
                </div>

            </div>

            <!-- 3. Interactive Graphical Usage Chart (Line Chart) -->
            <div class="bg-white rounded-lg border border-slate-200 p-5 shadow-2xs space-y-3.5">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800 flex items-center gap-2">
                            <ChartBarIcon class="w-4 h-4 text-blue-600" />
                            <span>Resource Usage History</span>
                        </h3>
                        <p class="text-[11px] text-slate-400 mt-0.5">Real-time hourly utilization trendline</p>
                    </div>

                    <!-- Metric Toggle Buttons -->
                    <div class="flex items-center gap-1 bg-slate-100 p-1 rounded-md text-xs font-bold">
                        <button 
                            @click="activeChartMetric = 'cpu'" 
                            class="px-3 py-1 rounded transition cursor-pointer"
                            :class="activeChartMetric === 'cpu' ? 'bg-blue-600 text-white shadow-2xs' : 'text-slate-600 hover:text-slate-900'"
                        >
                            CPU (%)
                        </button>
                        <button 
                            @click="activeChartMetric = 'ram'" 
                            class="px-3 py-1 rounded transition cursor-pointer"
                            :class="activeChartMetric === 'ram' ? 'bg-purple-600 text-white shadow-2xs' : 'text-slate-600 hover:text-slate-900'"
                        >
                            RAM (MB)
                        </button>
                        <button 
                            @click="activeChartMetric = 'io'" 
                            class="px-3 py-1 rounded transition cursor-pointer"
                            :class="activeChartMetric === 'io' ? 'bg-amber-600 text-white shadow-2xs' : 'text-slate-600 hover:text-slate-900'"
                        >
                            I/O (MB/s)
                        </button>
                    </div>
                </div>

                <!-- SVG Chart Container -->
                <div class="relative bg-slate-50/70 border border-slate-200/80 rounded-lg p-3 overflow-hidden">
                    
                    <!-- Hover readout tooltip -->
                    <div v-if="hoveredPoint" class="absolute top-4 right-4 bg-slate-900 text-white text-xs px-3 py-1.5 rounded shadow-md font-mono flex items-center gap-2 z-10">
                        <span class="text-slate-300">Time: {{ hoveredPoint.hour }}</span>
                        <span class="text-amber-400 font-bold">Value: {{ hoveredPoint.val }} {{ activeChartMetric === 'cpu' ? '%' : (activeChartMetric === 'ram' ? 'MB' : 'MB/s') }}</span>
                    </div>

                    <svg viewBox="0 0 800 180" class="w-full h-40 overflow-visible">
                        <defs>
                            <linearGradient id="chartBlueGradient" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#3B82F6" stop-opacity="0.25" />
                                <stop offset="100%" stop-color="#3B82F6" stop-opacity="0.0" />
                            </linearGradient>
                            <linearGradient id="chartPurpleGradient" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#8B5CF6" stop-opacity="0.25" />
                                <stop offset="100%" stop-color="#8B5CF6" stop-opacity="0.0" />
                            </linearGradient>
                            <linearGradient id="chartAmberGradient" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#F59E0B" stop-opacity="0.25" />
                                <stop offset="100%" stop-color="#F59E0B" stop-opacity="0.0" />
                            </linearGradient>
                        </defs>

                        <!-- Grid Background Lines -->
                        <line x1="20" y1="20" x2="780" y2="20" stroke="#E2E8F0" stroke-dasharray="3 3" />
                        <line x1="20" y1="60" x2="780" y2="60" stroke="#E2E8F0" stroke-dasharray="3 3" />
                        <line x1="20" y1="100" x2="780" y2="100" stroke="#E2E8F0" stroke-dasharray="3 3" />
                        <line x1="20" y1="140" x2="780" y2="140" stroke="#E2E8F0" stroke-dasharray="3 3" />
                        <line x1="20" y1="160" x2="780" y2="160" stroke="#CBD5E1" stroke-width="1.5" />

                        <!-- Area Fill -->
                        <polygon 
                            :points="svgAreaPoints" 
                            :fill="activeChartMetric === 'cpu' ? 'url(#chartBlueGradient)' : (activeChartMetric === 'ram' ? 'url(#chartPurpleGradient)' : 'url(#chartAmberGradient)')" 
                        />

                        <!-- Stroke Line -->
                        <polyline 
                            :points="svgPolylinePoints" 
                            fill="none" 
                            :stroke="activeChartMetric === 'cpu' ? '#2563EB' : (activeChartMetric === 'ram' ? '#7C3AED' : '#D97706')" 
                            stroke-width="2" 
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />

                        <!-- Data Point Interactive Dots -->
                        <circle 
                            v-for="(point, idx) in chartPoints" 
                            :key="idx"
                            :cx="point.x" 
                            :cy="point.y" 
                            r="3.5" 
                            class="transition-all cursor-pointer hover:r-5 fill-white"
                            :stroke="activeChartMetric === 'cpu' ? '#2563EB' : (activeChartMetric === 'ram' ? '#7C3AED' : '#D97706')" 
                            stroke-width="2"
                            @mouseenter="hoveredPoint = point"
                            @mouseleave="hoveredPoint = null"
                        />
                    </svg>

                    <!-- Time Axis Labels -->
                    <div class="flex justify-between items-center text-[10px] text-slate-400 font-mono px-2 pt-1 border-t border-slate-200">
                        <span>24h Ago</span>
                        <span>18h Ago</span>
                        <span>12h Ago</span>
                        <span>6h Ago</span>
                        <span class="font-bold text-slate-700">Now (Live)</span>
                    </div>
                </div>
            </div>

            <!-- 4. Detailed Resource Usage & Limits Table -->
            <div class="bg-white rounded-lg border border-slate-200 shadow-2xs overflow-hidden">
                <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between bg-slate-50/60">
                    <div class="flex items-center gap-2">
                        <ServerIcon class="w-4 h-4 text-blue-600" />
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800">Resource Quota & Faults Breakdown</h3>
                    </div>
                    <span class="text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                        ● 0 Fault Incidents
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs whitespace-nowrap">
                        <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200">
                            <tr>
                                <th class="py-2.5 px-4">Resource</th>
                                <th class="py-2.5 px-4">Allocated Limit</th>
                                <th class="py-2.5 px-4">Current Usage</th>
                                <th class="py-2.5 px-4">Utilization</th>
                                <th class="py-2.5 px-4">Faults</th>
                                <th class="py-2.5 px-4 text-right">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <tr v-for="item in resourceTableItems" :key="item.name" class="hover:bg-slate-50/70 transition">
                                <td class="py-3 px-4 font-bold text-slate-900">
                                    <div>
                                        <p class="text-xs font-bold text-slate-900">{{ item.name }}</p>
                                        <p class="text-[10px] text-slate-400 font-normal">{{ item.desc }}</p>
                                    </div>
                                </td>
                                <td class="py-3 px-4 font-mono font-bold text-slate-800">
                                    {{ item.limit }}
                                </td>
                                <td class="py-3 px-4 font-mono font-bold text-blue-700">
                                    {{ item.usage }}
                                </td>
                                <td class="py-3 px-4 w-44">
                                    <div class="flex items-center gap-2">
                                        <div class="w-24 bg-slate-100 h-1.5 rounded-full overflow-hidden">
                                            <div 
                                                class="bg-blue-600 h-full rounded-full" 
                                                :style="{ width: `${Math.max(4, item.percent)}%` }"
                                            ></div>
                                        </div>
                                        <span class="text-[11px] font-mono text-slate-500">{{ item.percent }}%</span>
                                    </div>
                                </td>
                                <td class="py-3 px-4 font-mono text-slate-600">
                                    {{ item.faults }}
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200 uppercase">
                                        {{ item.status }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 5. Active Process Monitor Table (NPROC) -->
            <div class="bg-white rounded-lg border border-slate-200 shadow-2xs overflow-hidden">
                <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between bg-slate-50/60">
                    <div class="flex items-center gap-2">
                        <CommandLineIcon class="w-4 h-4 text-slate-700" />
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800">Active Container Processes</h3>
                    </div>
                    <span class="text-[11px] font-mono font-semibold text-slate-500">
                        Running: {{ activeProcesses.length }} of 25 Max
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs whitespace-nowrap">
                        <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200">
                            <tr>
                                <th class="py-2.5 px-4">PID</th>
                                <th class="py-2.5 px-4">User</th>
                                <th class="py-2.5 px-4">CPU %</th>
                                <th class="py-2.5 px-4">Memory</th>
                                <th class="py-2.5 px-4">Execution Time</th>
                                <th class="py-2.5 px-4">Process Command</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-mono text-[11px]">
                            <tr v-for="proc in activeProcesses" :key="proc.pid" class="hover:bg-slate-50/70 transition">
                                <td class="py-2.5 px-4 font-bold text-slate-900">{{ proc.pid }}</td>
                                <td class="py-2.5 px-4 text-slate-600">{{ proc.user }}</td>
                                <td class="py-2.5 px-4 font-bold text-blue-700">{{ proc.cpu }}</td>
                                <td class="py-2.5 px-4 text-slate-800 font-semibold">{{ proc.mem }}</td>
                                <td class="py-2.5 px-4 text-slate-500">{{ proc.time }}</td>
                                <td class="py-2.5 px-4 text-slate-800 font-mono truncate max-w-sm">{{ proc.command }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
