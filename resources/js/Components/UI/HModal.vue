<script setup>
import { onMounted, onUnmounted } from 'vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    title: {
        type: String,
        default: ''
    },
    subtitle: {
        type: String,
        default: ''
    },
    maxWidth: {
        type: String,
        default: 'md' // 'sm', 'md', 'lg', 'xl', '2xl'
    }
})

const emit = defineEmits(['close'])

const maxWidthClasses = {
    sm: 'max-w-sm',
    md: 'max-w-md',
    lg: 'max-w-lg',
    xl: 'max-w-xl',
    '2xl': 'max-w-2xl',
}

const handleKeyDown = (e) => {
    if (e.key === 'Escape' && props.show) {
        emit('close')
    }
}

onMounted(() => window.addEventListener('keydown', handleKeyDown))
onUnmounted(() => window.removeEventListener('keydown', handleKeyDown))
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div 
                v-if="show" 
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-hslate-950/60 backdrop-blur-xs"
                @click.self="emit('close')"
            >
                <Transition
                    enter-active-class="transition duration-200 ease-out transform"
                    enter-from-class="opacity-0 scale-95 translate-y-2"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                    leave-active-class="transition duration-150 ease-in transform"
                    leave-from-class="opacity-100 scale-100 translate-y-0"
                    leave-to-class="opacity-0 scale-95 translate-y-2"
                >
                    <div 
                        v-if="show"
                        :class="[
                            'bg-white rounded-3xl border border-hslate-200 shadow-hmodal w-full overflow-hidden flex flex-col max-h-[90vh]',
                            maxWidthClasses[maxWidth] || maxWidthClasses.md
                        ]"
                    >
                        <!-- Modal Header -->
                        <div class="px-6 py-5 border-b border-hslate-100 flex items-center justify-between">
                            <div>
                                <slot name="header">
                                    <h3 class="text-base font-bold text-hslate-900 tracking-tight">{{ title }}</h3>
                                    <p v-if="subtitle" class="text-xs text-hslate-500 font-medium mt-0.5">{{ subtitle }}</p>
                                </slot>
                            </div>
                            <button 
                                @click="emit('close')" 
                                class="w-8 h-8 rounded-xl bg-hslate-100 hover:bg-hslate-200 text-hslate-500 hover:text-hslate-800 flex items-center justify-center transition cursor-pointer"
                            >
                                <XMarkIcon class="w-4 h-4" />
                            </button>
                        </div>

                        <!-- Modal Body -->
                        <div class="p-6 overflow-y-auto space-y-4">
                            <slot />
                        </div>

                        <!-- Modal Footer -->
                        <div v-if="$slots.footer" class="px-6 py-4 bg-hslate-50 border-t border-hslate-100 flex items-center justify-end gap-3 rounded-b-3xl">
                            <slot name="footer" />
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
