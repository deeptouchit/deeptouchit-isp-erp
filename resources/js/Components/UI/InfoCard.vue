<script setup>
import { computed } from 'vue'

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    value: {
        type: [String, Number],
        required: true,
    },
    icon: {
        type: [Object, Function],
        default: null,
    },
    badge: {
        type: String,
        default: '',
    },
    badgeType: {
        type: String,
        default: 'neutral', // 'success' | 'danger' | 'warning' | 'info' | 'neutral'
    },
    subtext: {
        type: String,
        default: '',
    },
    color: {
        type: String,
        default: 'indigo',
    }
})

const iconColorClasses = computed(() => {
    switch (props.color) {
        case 'emerald':
            return 'bg-emerald-50 text-emerald-600 border-emerald-200'
        case 'amber':
            return 'bg-amber-50 text-amber-600 border-amber-200'
        case 'rose':
            return 'bg-rose-50 text-rose-600 border-rose-200'
        case 'sky':
            return 'bg-sky-50 text-sky-600 border-sky-200'
        case 'purple':
            return 'bg-purple-50 text-purple-600 border-purple-200'
        default:
            return 'bg-blue-50 text-blue-600 border-blue-200'
    }
})

const badgeClasses = computed(() => {
    switch (props.badgeType) {
        case 'success':
            return 'bg-emerald-50 text-emerald-700 border-emerald-200'
        case 'danger':
            return 'bg-rose-50 text-rose-700 border-rose-200'
        case 'warning':
            return 'bg-amber-50 text-amber-700 border-amber-200'
        case 'info':
            return 'bg-sky-50 text-sky-700 border-sky-200'
        default:
            return 'bg-slate-50 text-slate-700 border-slate-200'
    }
})

const badgeDotColor = computed(() => {
    switch (props.badgeType) {
        case 'success':
            return 'bg-emerald-500'
        case 'danger':
            return 'bg-rose-500'
        case 'warning':
            return 'bg-amber-500'
        case 'info':
            return 'bg-sky-500'
        default:
            return 'bg-slate-400'
    }
})
</script>

<template>
    <div class="bg-white border border-[#E2E8F0] rounded-[4px] px-3.5 py-2.5 shadow-2xs hover:border-slate-300 transition-all flex items-center justify-between gap-3 min-h-[58px]">
        <!-- Left: Icon & Title/Value combo -->
        <div class="flex items-center gap-2.5 min-w-0 flex-1">
            <div 
                v-if="icon" 
                :class="iconColorClasses" 
                class="w-8 h-8 rounded-[4px] border flex items-center justify-center shrink-0"
            >
                <component :is="icon" class="w-4 h-4" />
            </div>

            <div class="min-w-0 flex-1">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider truncate leading-none mb-1">
                    {{ title }}
                </p>
                <div class="flex items-center gap-1.5">
                    <span class="text-[13.5px] font-bold text-slate-800 tracking-tight truncate leading-tight tabular-nums" :title="String(value)">
                        {{ value }}
                    </span>
                    <span v-if="subtext" class="text-[10px] text-slate-400 font-medium shrink-0">
                        {{ subtext }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Right: Small Compact Pill Badge -->
        <div v-if="badge" :class="badgeClasses" class="inline-flex items-center gap-1.5 text-[10px] font-semibold px-2 py-0.5 rounded-[3px] border shrink-0">
            <span :class="badgeDotColor" class="w-1.5 h-1.5 rounded-full shrink-0"></span>
            <span>{{ badge }}</span>
        </div>
    </div>
</template>
