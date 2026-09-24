<template>
    <div class="h-64 flex flex-col justify-between">
        <div class="space-y-4">
            <div>
                <div class="flex justify-between text-sm mb-1">
                    <span class="font-medium text-slate-600">CPU Usage</span>
                    <span class="font-semibold text-slate-800">{{ cpuUsage }}%</span>
                </div>
                <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
                    <div class="bg-blue-600 h-2.5 rounded-full transition-all duration-500" :style="{ width: `${cpuUsage}%` }"></div>
                </div>
            </div>

            <div>
                <div class="flex justify-between text-sm mb-1">
                    <span class="font-medium text-slate-600">RAM Usage</span>
                    <span class="font-semibold text-slate-800">{{ ramUsage }}% ({{ ramUsed }} / {{ ramTotal }} MB)</span>
                </div>
                <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
                    <div class="bg-emerald-500 h-2.5 rounded-full transition-all duration-500" :style="{ width: `${ramUsage}%` }"></div>
                </div>
            </div>

            <div>
                <div class="flex justify-between text-sm mb-1">
                    <span class="font-medium text-slate-600">Disk Space</span>
                    <span class="font-semibold text-slate-800">{{ diskUsage }}% ({{ diskUsed }} / {{ diskTotal }} GB)</span>
                </div>
                <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
                    <div class="bg-purple-600 h-2.5 rounded-full transition-all duration-500" :style="{ width: `${diskUsage}%` }"></div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-2 text-center pt-4 border-t border-slate-100 text-xs text-slate-500">
            <div>
                <p class="font-semibold text-slate-700">1m Load</p>
                <p>{{ load1m }}</p>
            </div>
            <div>
                <p class="font-semibold text-slate-700">5m Load</p>
                <p>{{ load5m }}</p>
            </div>
            <div>
                <p class="font-semibold text-slate-700">15m Load</p>
                <p>{{ load15m }}</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    data: {
        type: [Array, Object],
        default: () => ({})
    }
})

const cpuUsage = computed(() => props.data?.cpu_usage ?? 12)
const ramUsage = computed(() => {
    if (props.data?.total_ram && props.data?.used_ram) {
        return Math.round((props.data.used_ram / props.data.total_ram) * 100)
    }
    return props.data?.mem_usage ?? 45
})
const ramUsed = computed(() => props.data?.used_ram ?? 3680)
const ramTotal = computed(() => props.data?.total_ram ?? 8192)

const diskUsage = computed(() => {
    if (props.data?.total_disk && props.data?.used_disk) {
        return Math.round((props.data.used_disk / props.data.total_disk) * 100)
    }
    return props.data?.disk_usage ?? 28
})
const diskUsed = computed(() => props.data?.used_disk ?? 45)
const diskTotal = computed(() => props.data?.total_disk ?? 160)

const load1m = computed(() => props.data?.load_avg_1min ?? '0.45')
const load5m = computed(() => props.data?.load_avg_5min ?? '0.38')
const load15m = computed(() => props.data?.load_avg_15min ?? '0.29')
</script>
