<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import HModal from '@/Components/UI/HModal.vue'
import HButton from '@/Components/UI/HButton.vue'
import { ArrowPathIcon, ExclamationTriangleIcon, StopIcon, PlayIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    server: {
        type: Object,
        required: true,
    },
    service: {
        type: String,
        required: true,
    },
    action: {
        type: String,
        default: 'restart', // 'restart', 'reload', 'start', 'stop'
    }
})

const emit = defineEmits(['close'])

const isSubmitting = ref(false)

const executeAction = () => {
    isSubmitting.value = true
    router.post(route('admin.servers.services', props.server.id), {
        service: props.service,
        action: props.action,
    }, {
        preserveScroll: true,
        onFinish: () => {
            isSubmitting.value = false
            emit('close')
        }
    })
}
</script>

<template>
    <HModal :show="show" max-width="md" @close="emit('close')">
        <div class="p-6">
            <div class="flex items-center gap-4 mb-4">
                <div 
                    :class="[
                        'w-12 h-12 rounded-2xl flex items-center justify-center flex-shrink-0',
                        action === 'stop' ? 'bg-rose-50 text-rose-600 dark:bg-rose-950/50' : 'bg-brand-50 text-brand-600 dark:bg-brand-950/50'
                    ]"
                >
                    <StopIcon v-if="action === 'stop'" class="w-6 h-6" />
                    <PlayIcon v-else-if="action === 'start'" class="w-6 h-6" />
                    <ArrowPathIcon v-else class="w-6 h-6" />
                </div>
                <div>
                    <h3 class="text-lg font-bold text-hslate-900 dark:text-white capitalize">
                        {{ action }} {{ service }} Service
                    </h3>
                    <p class="text-xs text-hslate-500">
                        Node: <span class="font-semibold text-hslate-700 dark:text-hslate-300">{{ server?.name }}</span>
                    </p>
                </div>
            </div>

            <div class="space-y-3">
                <p class="text-sm text-hslate-600 dark:text-hslate-300">
                    Are you sure you want to trigger a systemd <strong class="text-hslate-900 dark:text-white">{{ action }}</strong> command for the <strong class="font-mono text-brand-600 dark:text-brand-400">{{ service }}</strong> daemon?
                </p>

                <div v-if="action === 'stop' || action === 'restart'" class="p-3 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900/50 rounded-xl text-xs text-amber-800 dark:text-amber-300 flex items-start gap-2.5">
                    <ExclamationTriangleIcon class="w-4 h-4 flex-shrink-0 mt-0.5" />
                    <div>
                        {{ action === 'stop' ? 'Stopping this service will immediately drop incoming traffic handled by this daemon.' : 'Restarting may temporarily interrupt in-flight requests for a few milliseconds.' }}
                    </div>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3">
                <HButton variant="secondary" :disabled="isSubmitting" @click="emit('close')">
                    Cancel
                </HButton>
                <HButton 
                    :variant="action === 'stop' ? 'danger' : 'primary'"
                    :loading="isSubmitting"
                    @click="executeAction"
                >
                    Confirm {{ action }}
                </HButton>
            </div>
        </div>
    </HModal>
</template>
