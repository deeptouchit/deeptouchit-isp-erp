<script setup>
import { ChevronDownIcon } from '@heroicons/vue/20/solid'

const model = defineModel({ type: [String, Number], default: '' })

defineProps({
    options: {
        type: Array,
        default: () => [],
    },
    placeholder: {
        type: String,
        default: 'All',
    },
})
</script>

<template>
    <div class="relative inline-block min-w-[125px]">
        <select
            v-model="model"
            class="w-full pl-3 pr-8 py-1.5 text-xs font-semibold rounded-[3px] border border-slate-200 bg-slate-50/70 hover:bg-white text-slate-700 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-all cursor-pointer appearance-none shadow-2xs"
        >
            <option value="">{{ placeholder }}</option>
            <slot>
                <option v-for="opt in options" :key="typeof opt === 'object' ? opt.value : opt" :value="typeof opt === 'object' ? opt.value : opt">
                    {{ typeof opt === 'object' ? opt.label : opt }}
                </option>
            </slot>
        </select>
        <ChevronDownIcon class="w-3.5 h-3.5 text-slate-400 absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none" />
    </div>
</template>
