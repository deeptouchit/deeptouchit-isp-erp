<script setup>
defineProps({
    cols: {
        type: [Number, String],
        default: 4,
    }
})
</script>

<template>
    <div :class="cols == 5 ? 'grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3' : 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3'">
        <slot />
    </div>
</template>
