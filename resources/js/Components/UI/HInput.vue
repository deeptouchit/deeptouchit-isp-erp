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
    type: {
        type: String,
        default: 'text'
    },
    placeholder: {
        type: String,
        default: ''
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
            <div v-if="$slots.prefix" class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-hslate-400">
                <slot name="prefix" />
            </div>

            <input
                :type="type"
                :value="modelValue"
                @input="emit('update:modelValue', $event.target.value)"
                :placeholder="placeholder"
                :disabled="disabled"
                :class="[
                    'w-full bg-white border rounded-xl py-2.5 text-xs font-semibold text-hslate-900 placeholder:text-hslate-400 focus:outline-none transition duration-150',
                    $slots.prefix ? 'pl-10' : 'pl-3.5',
                    $slots.suffix ? 'pr-10' : 'pr-3.5',
                    error 
                        ? 'border-rose-300 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 bg-rose-50/20' 
                        : 'border-hslate-200 focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20'
                ]"
            />

            <div v-if="$slots.suffix" class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-hslate-400">
                <slot name="suffix" />
            </div>
        </div>

        <p v-if="error" class="text-[11px] font-semibold text-rose-600 flex items-center gap-1">
            <span>•</span>
            <span>{{ error }}</span>
        </p>

        <p v-else-if="hint" class="text-[11px] text-hslate-400">
            {{ hint }}
        </p>
    </div>
</template>
