<script setup>
import { computed } from 'vue'

const props = defineProps({
    label: {
        type: String,
        default: '',
    },
    percentage: {
        type: Number,
        default: 0,
    },
    detail: {
        type: String,
        default: '',
    },
    showPercentage: {
        type: Boolean,
        default: true,
    },
})

const clamped = computed(() => Math.min(100, Math.max(0, Math.round(props.percentage || 0))))

const barColor = computed(() => {
    if (clamped.value >= 85) return 'bg-rose-500'
    if (clamped.value >= 70) return 'bg-amber-500'
    return 'bg-emerald-500'
})

const textColor = computed(() => {
    if (clamped.value >= 85) return 'text-rose-600 font-bold'
    if (clamped.value >= 70) return 'text-amber-600 font-bold'
    return 'text-slate-700'
})
</script>

<template>
    <div class="space-y-1 w-full min-w-[110px] max-w-[160px]">
        <div class="flex items-center justify-between text-[11px] leading-tight">
            <span class="font-medium text-slate-500 truncate" :title="detail || label">
                {{ detail || label }}
            </span>
            <span v-if="showPercentage" :class="['font-mono font-bold text-[10px] flex-shrink-0 ml-1.5', textColor]">
                {{ clamped }}%
            </span>
        </div>
        <div class="h-1.5 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
            <div 
                :class="['h-full rounded-full transition-all duration-300 ease-out', barColor]"
                :style="{ width: `${clamped}%` }"
                role="progressbar"
                :aria-valuenow="clamped"
                aria-valuemin="0"
                aria-valuemax="100"
            ></div>
        </div>
    </div>
</template>
