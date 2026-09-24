<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { ArrowPathIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    label: {
        type: String,
        default: 'Refresh'
    },
    preserveScroll: {
        type: Boolean,
        default: true
    },
    preserveState: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['refreshed'])
const isRefreshing = ref(false)

const handleRefresh = () => {
    if (isRefreshing.value) return
    isRefreshing.value = true
    
    router.reload({
        preserveScroll: props.preserveScroll,
        preserveState: props.preserveState,
        onFinish: () => {
            setTimeout(() => {
                isRefreshing.value = false
                emit('refreshed')
            }, 500)
        },
        onError: () => {
            isRefreshing.value = false
        }
    })
}
</script>

<template>
    <button
        type="button"
        @click="handleRefresh"
        :disabled="isRefreshing"
        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition-all cursor-pointer disabled:opacity-75 select-none"
        :title="isRefreshing ? 'Refreshing data...' : 'Refresh current page data'"
    >
        <ArrowPathIcon 
            class="w-3.5 h-3.5 text-slate-500 transition-transform duration-500" 
            :class="{ 'animate-spin text-blue-600': isRefreshing }"
        />
        <span>{{ isRefreshing ? 'Refreshing...' : label }}</span>
    </button>
</template>
