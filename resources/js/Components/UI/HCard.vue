<script setup>
defineProps({
    title: {
        type: String,
        default: ''
    },
    subtitle: {
        type: String,
        default: ''
    },
    noPadding: {
        type: Boolean,
        default: false
    },
    hoverable: {
        type: Boolean,
        default: false
    }
})
</script>

<template>
    <div 
        :class="[
            'bg-white rounded-2xl border border-hslate-200 shadow-hcard transition-all duration-200',
            hoverable ? 'hover:border-brand-300 hover:shadow-hcard-hover' : ''
        ]"
    >
        <!-- Card Header -->
        <div v-if="title || $slots.header || $slots.actions" class="px-6 py-5 border-b border-hslate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <slot name="header">
                    <h3 class="text-sm font-bold text-hslate-900 tracking-tight">{{ title }}</h3>
                    <p v-if="subtitle" class="text-xs text-hslate-500 font-medium mt-0.5">{{ subtitle }}</p>
                </slot>
            </div>
            <div v-if="$slots.actions" class="flex items-center gap-2">
                <slot name="actions" />
            </div>
        </div>

        <!-- Card Body -->
        <div :class="[noPadding ? '' : 'p-6']">
            <slot />
        </div>

        <!-- Card Footer -->
        <div v-if="$slots.footer" class="px-6 py-4 bg-hslate-50/60 rounded-b-2xl border-t border-hslate-100">
            <slot name="footer" />
        </div>
    </div>
</template>
