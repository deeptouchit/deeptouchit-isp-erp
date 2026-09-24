<script setup>
defineProps({
    variant: {
        type: String,
        default: 'neutral' // 'purple', 'success', 'warning', 'danger', 'info', 'neutral'
    },
    dot: {
        type: Boolean,
        default: false
    },
    pulse: {
        type: Boolean,
        default: false
    },
    size: {
        type: String,
        default: 'sm' // 'xs', 'sm', 'md'
    }
})

const variantClasses = {
    purple: 'bg-brand-50 text-brand-700 border-brand-200',
    success: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    warning: 'bg-amber-50 text-amber-700 border-amber-200',
    danger: 'bg-rose-50 text-rose-700 border-rose-200',
    info: 'bg-sky-50 text-sky-700 border-sky-200',
    neutral: 'bg-hslate-100 text-hslate-700 border-hslate-200',
}

const dotColors = {
    purple: 'bg-brand-600',
    success: 'bg-emerald-500',
    warning: 'bg-amber-500',
    danger: 'bg-rose-500',
    info: 'bg-sky-500',
    neutral: 'bg-hslate-500',
}

const sizeClasses = {
    xs: 'px-2 py-0.5 text-[10px]',
    sm: 'px-2.5 py-0.5 text-[11px]',
    md: 'px-3 py-1 text-xs',
}
</script>

<template>
    <span 
        :class="[
            'inline-flex items-center gap-1.5 font-bold uppercase tracking-wider rounded-full border',
            variantClasses[variant] || variantClasses.neutral,
            sizeClasses[size] || sizeClasses.sm
        ]"
    >
        <span v-if="dot" class="relative flex h-2 w-2">
            <span 
                v-if="pulse" 
                :class="['animate-ping absolute inline-flex h-full w-full rounded-full opacity-75', dotColors[variant] || dotColors.neutral]"
            ></span>
            <span :class="['relative inline-flex rounded-full h-2 w-2', dotColors[variant] || dotColors.neutral]"></span>
        </span>
        <slot />
    </span>
</template>
