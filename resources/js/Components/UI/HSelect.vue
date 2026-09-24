<script setup>
defineProps({
    modelValue: {
        type: [String, Number],
        default: ''
    },
    label: {
        type: String,
        default: ''
    },
    options: {
        type: Array,
        default: () => [] // Array of { value, label } or simple strings
    },
    error: {
        type: String,
        default: ''
    },
    hint: {
        type: String,
        default: ''
    },
    disabled: {
        type: Boolean,
        default: false
    },
    required: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['update:modelValue'])
</script>

<template>
    <div class="space-y-1.5">
        <label v-if="label" class="block text-xs font-bold text-hslate-700">
            {{ label }}
            <span v-if="required" class="text-rose-500">*</span>
        </label>

        <div class="relative rounded-xl shadow-2xs">
            <select
                :value="modelValue"
                @change="emit('update:modelValue', $event.target.value)"
                :disabled="disabled"
                :class="[
                    'w-full bg-white border rounded-xl py-2.5 pl-3.5 pr-10 text-xs font-semibold text-hslate-900 focus:outline-none transition duration-150 cursor-pointer appearance-none',
                    error 
                        ? 'border-rose-300 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 bg-rose-50/20' 
                        : 'border-hslate-200 focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20'
                ]"
            >
                <slot>
                    <option 
                        v-for="opt in options" 
                        :key="typeof opt === 'object' ? opt.value : opt" 
                        :value="typeof opt === 'object' ? opt.value : opt"
                    >
                        {{ typeof opt === 'object' ? opt.label : opt }}
                    </option>
                </slot>
            </select>

            <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-hslate-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </div>
        </div>

        <p v-if="error" class="text-[11px] font-semibold text-rose-600">
            {{ error }}
        </p>

        <p v-else-if="hint" class="text-[11px] text-hslate-400">
            {{ hint }}
        </p>
    </div>
</template>
