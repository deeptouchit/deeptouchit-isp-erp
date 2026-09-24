<script setup>
defineProps({
    title: {
        type: String,
        required: true
    },
    value: {
        type: [String, Number],
        required: true
    },
    subtitle: {
        type: String,
        default: ''
    },
    badgeText: {
        type: String,
        default: ''
    },
    badgeVariant: {
        type: String,
        default: 'purple'
    },
    iconBg: {
        type: String,
        default: 'bg-brand-50 text-brand-600 border-brand-100'
    }
})
</script>

<template>
    <div class="bg-white p-5 rounded-2xl border border-hslate-200 shadow-hcard hover:border-brand-300 hover:shadow-hcard-hover transition-all duration-200">
        <div class="flex items-start justify-between">
            <div class="space-y-1">
                <p class="text-[11px] font-bold uppercase tracking-wider text-hslate-400 font-sans">{{ title }}</p>
                <h4 class="text-2xl font-black text-hslate-900 tracking-tight font-sans">{{ value }}</h4>
            </div>

            <div v-if="$slots.icon" :class="['w-11 h-11 rounded-xl flex items-center justify-center border shadow-xs flex-shrink-0', iconBg]">
                <slot name="icon" />
            </div>
        </div>

        <div v-if="subtitle || badgeText" class="mt-3.5 flex items-center justify-between pt-3 border-t border-hslate-100/80 text-xs">
            <span v-if="subtitle" class="text-hslate-500 font-medium text-[11px] truncate">{{ subtitle }}</span>
            <span 
                v-if="badgeText" 
                class="text-[10px] font-black uppercase px-2 py-0.5 rounded-md bg-brand-50 text-brand-700 border border-brand-100"
            >
                {{ badgeText }}
            </span>
        </div>
    </div>
</template>
