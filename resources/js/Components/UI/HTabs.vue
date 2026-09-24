<script setup>
defineProps({
    tabs: {
        type: Array,
        required: true // Array of { id, name, icon?, count?, badge? }
    },
    modelValue: {
        type: String,
        required: true
    },
    variant: {
        type: String,
        default: 'pill' // 'pill' or 'underline'
    }
})

const emit = defineEmits(['update:modelValue'])
</script>

<template>
    <div class="flex items-center gap-1.5 overflow-x-auto pb-1">
        <button
            v-for="tab in tabs"
            :key="tab.id"
            type="button"
            @click="emit('update:modelValue', tab.id)"
            :class="[
                'flex items-center gap-2 px-3.5 py-2 text-xs font-bold transition-all duration-150 cursor-pointer select-none rounded-xl flex-shrink-0',
                modelValue === tab.id
                    ? 'bg-brand-600 text-white shadow-hbtn-primary shadow-brand-600/20'
                    : 'text-hslate-600 hover:text-hslate-900 hover:bg-hslate-100 bg-transparent'
            ]"
        >
            <component :is="tab.icon" v-if="tab.icon" class="w-4 h-4" />
            <span>{{ tab.name }}</span>

            <span 
                v-if="tab.count !== undefined" 
                :class="[
                    'text-[10px] font-black px-1.5 py-0.2 rounded-full',
                    modelValue === tab.id ? 'bg-white/20 text-white' : 'bg-hslate-200/80 text-hslate-600'
                ]"
            >
                {{ tab.count }}
            </span>

            <span 
                v-if="tab.badge" 
                :class="[
                    'text-[9px] font-black uppercase px-1.5 py-0.5 rounded',
                    modelValue === tab.id ? 'bg-white/20 text-white' : 'bg-brand-50 text-brand-700 border border-brand-200'
                ]"
            >
                {{ tab.badge }}
            </span>
        </button>
    </div>
</template>
