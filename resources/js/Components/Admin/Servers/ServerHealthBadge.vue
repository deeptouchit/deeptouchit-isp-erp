<script setup>
import { computed } from 'vue'

const props = defineProps({
    health: {
        type: String,
        default: 'unknown',
    },
    size: {
        type: String,
        default: 'sm',
    }
})

const config = computed(() => {
    switch (props.health?.toLowerCase()) {
        case 'healthy':
            return {
                label: 'Healthy',
                bg: 'bg-emerald-50 text-emerald-700 border-emerald-200',
                dot: 'bg-emerald-500',
                pulse: false
            }
        case 'warning':
            return {
                label: 'Degraded',
                bg: 'bg-amber-50 text-amber-700 border-amber-200',
                dot: 'bg-amber-500',
                pulse: true
            }
        case 'critical':
            return {
                label: 'Critical',
                bg: 'bg-rose-50 text-rose-700 border-rose-200',
                dot: 'bg-rose-500',
                pulse: true
            }
        case 'offline':
            return {
                label: 'Unreachable',
                bg: 'bg-rose-50 text-rose-700 border-rose-200',
                dot: 'bg-rose-500',
                pulse: false
            }
        default:
            return {
                label: props.health || 'Unknown',
                bg: 'bg-slate-50 text-slate-700 border-slate-200',
                dot: 'bg-slate-400',
                pulse: false
            }
    }
})
</script>

<template>
    <span 
        :class="[
            'inline-flex items-center gap-1.5 font-bold uppercase tracking-wider rounded border text-[10px] px-2 py-0.5 select-none',
            config.bg
        ]"
    >
        <span class="relative flex h-1.5 w-1.5">
            <span v-if="config.pulse" :class="['animate-ping absolute inline-flex h-full w-full rounded-full opacity-75', config.dot]"></span>
            <span :class="['relative inline-flex rounded-full h-1.5 w-1.5', config.dot]"></span>
        </span>
        <span>{{ config.label }}</span>
    </span>
</template>
