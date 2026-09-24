<script setup>
import { ref, onMounted, watch } from 'vue'
import { ArrowPathIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    serverId: {
        type: Number,
        required: true,
    },
    metricType: {
        type: String,
        default: 'cpu', // 'cpu', 'ram', 'disk', 'load_1m'
    },
    title: {
        type: String,
        default: 'Resource Utilization History',
    },
    unit: {
        type: String,
        default: '%',
    }
})

const currentRange = ref('1h')
const isLoading = ref(false)
const historyData = ref([])

const fetchHistory = async () => {
    isLoading.value = true
    try {
        const response = await fetch(`/api/v1/servers/${props.serverId}/monitoring/history?range=${currentRange.value}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        if (response.ok) {
            const data = await response.json()
            historyData.value = data.data || []
        }
    } catch (e) {
        historyData.value = []
    } finally {
        isLoading.value = false
    }
}

const getBarHeight = (point) => {
    const val = point[props.metricType] || 0
    if (props.metricType === 'ram') {
        // RAM in MB, assuming ~8000 MB max or proportional
        return Math.max(8, Math.min(100, Math.round((val / 7293) * 100)))
    }
    if (props.metricType === 'load_1m') {
        // Load average (0.0 to 4.0 cores)
        return Math.max(8, Math.min(100, Math.round((val / 4) * 100)))
    }
    return Math.max(8, Math.min(100, Math.round(val)))
}

const formatTooltip = (point) => {
    const val = point[props.metricType] || 0
    if (props.metricType === 'ram') {
        return val >= 1024 ? `${(val / 1024).toFixed(1)} GB` : `${val} MB`
    }
    if (props.metricType === 'load_1m') {
        return `${val} Load`
    }
    return `${val}${props.unit}`
}

onMounted(() => {
    fetchHistory()
})

watch(currentRange, () => {
    fetchHistory()
})
</script>

<template>
    <div class="space-y-3 bg-white p-4 rounded-lg border border-slate-200 shadow-xs">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                    {{ title }}
                </h4>
                <p class="text-[11px] text-slate-400">
                    Continuous timeseries metrics sampled from node.
                </p>
            </div>

            <!-- Time range selector -->
            <div class="flex items-center gap-1 bg-slate-100 p-0.5 rounded-md">
                <button
                    v-for="range in ['1h', '6h', '24h', '7d', '30d']"
                    :key="range"
                    type="button"
                    :class="[
                        'px-2 py-1 text-[10px] font-bold uppercase rounded transition-all cursor-pointer',
                        currentRange === range
                            ? 'bg-[#673DE6] text-white shadow-xs'
                            : 'text-slate-600 hover:text-slate-900'
                    ]"
                    @click="currentRange = range"
                >
                    {{ range }}
                </button>
            </div>
        </div>

        <!-- Timeseries Visualizer -->
        <div class="h-44 w-full bg-slate-50 rounded-md border border-slate-100 flex items-center justify-center p-3 relative overflow-hidden">
            <div v-if="isLoading" class="flex items-center gap-2 text-xs text-slate-400">
                <ArrowPathIcon class="w-4 h-4 animate-spin text-[#673DE6]" />
                Loading timeseries data...
            </div>

            <div v-else-if="historyData.length === 0" class="text-center text-xs text-slate-400">
                No telemetry samples recorded for the selected {{ currentRange }} range yet.
            </div>

            <!-- Sparkline Chart -->
            <div v-else class="w-full h-full flex flex-col justify-between">
                <div class="flex-1 flex items-end gap-1 pt-4">
                    <div
                        v-for="(point, idx) in historyData.slice(-30)"
                        :key="idx"
                        class="flex-1 flex flex-col items-center gap-1 group relative h-full justify-end"
                    >
                        <!-- Tooltip -->
                        <div class="opacity-0 group-hover:opacity-100 transition-opacity absolute -top-7 bg-slate-900 text-white text-[10px] py-0.5 px-1.5 rounded pointer-events-none whitespace-nowrap z-10 font-mono shadow-sm">
                            {{ formatTooltip(point) }} ({{ point.timestamp ? point.timestamp.substring(11, 16) : '' }})
                        </div>
                        <div
                            :class="[
                                'w-full rounded-t transition-all',
                                getBarHeight(point) >= 85 ? 'bg-rose-500' : getBarHeight(point) >= 70 ? 'bg-amber-500' : 'bg-[#673DE6]'
                            ]"
                            :style="{ height: `${getBarHeight(point)}%` }"
                        ></div>
                    </div>
                </div>
                <div class="flex items-center justify-between text-[10px] text-slate-400 pt-2 border-t border-slate-200/60 font-mono">
                    <span>{{ historyData[0]?.timestamp ? historyData[0].timestamp.substring(11, 16) : 'Start' }}</span>
                    <span class="font-sans font-semibold text-slate-500">Range: {{ currentRange.toUpperCase() }}</span>
                    <span>{{ historyData[historyData.length - 1]?.timestamp ? historyData[historyData.length - 1].timestamp.substring(11, 16) : 'Latest' }}</span>
                </div>
            </div>
        </div>
    </div>
</template>
