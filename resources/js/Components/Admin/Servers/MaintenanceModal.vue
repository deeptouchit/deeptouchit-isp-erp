<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import HModal from '@/Components/UI/HModal.vue'
import HButton from '@/Components/UI/HButton.vue'
import HInput from '@/Components/UI/HInput.vue'
import { ExclamationTriangleIcon, WrenchScrewdriverIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    server: {
        type: Object,
        required: true,
    },
})

const emit = defineEmits(['close'])

const reason = ref('')
const isSubmitting = ref(false)

const isCurrentlyInMaintenance = () => {
    return props.server?.status === 'maintenance'
}

const toggleMaintenance = () => {
    isSubmitting.value = true
    const enable = !isCurrentlyInMaintenance()
    
    router.post(route('admin.servers.maintenance', props.server.id), {
        enabled: enable,
        reason: reason.value,
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
                        isCurrentlyInMaintenance() ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50' : 'bg-amber-50 text-amber-600 dark:bg-amber-950/50'
                    ]"
                >
                    <WrenchScrewdriverIcon class="w-6 h-6" />
                </div>
                <div>
                    <h3 class="text-lg font-bold text-hslate-900 dark:text-white">
                        {{ isCurrentlyInMaintenance() ? 'Disable Maintenance Mode' : 'Enable Maintenance Mode' }}
                    </h3>
                    <p class="text-xs text-hslate-500">
                        Node: <span class="font-semibold text-hslate-700 dark:text-hslate-300">{{ server?.name }}</span> ({{ server?.ip_address }})
                    </p>
                </div>
            </div>

            <div v-if="!isCurrentlyInMaintenance()" class="space-y-4">
                <div class="p-3 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900/50 rounded-xl text-xs text-amber-800 dark:text-amber-300 flex items-start gap-2.5">
                    <ExclamationTriangleIcon class="w-4 h-4 flex-shrink-0 mt-0.5" />
                    <div>
                        Enabling maintenance mode will notify administrators that node operations are suspended. Customer accounts hosted on this server will display a scheduled maintenance banner.
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-hslate-700 dark:text-hslate-300 mb-1">
                        Reason / Notes (Optional)
                    </label>
                    <HInput
                        v-model="reason"
                        placeholder="e.g. Upgrading kernel to Linux 6.8 & reboot"
                    />
                </div>
            </div>

            <div v-else class="space-y-3">
                <p class="text-sm text-hslate-600 dark:text-hslate-300">
                    Are you sure you want to disable maintenance mode and return <strong class="text-hslate-900 dark:text-white">{{ server?.name }}</strong> to active operational status?
                </p>
                <div v-if="server?.maintenance_reason" class="p-3 bg-hslate-50 dark:bg-hslate-900 border border-hslate-200 dark:border-hslate-800 rounded-xl text-xs text-hslate-600 dark:text-hslate-400">
                    <span class="font-bold">Active reason:</span> {{ server?.maintenance_reason }}
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3">
                <HButton variant="secondary" :disabled="isSubmitting" @click="emit('close')">
                    Cancel
                </HButton>
                <HButton 
                    :variant="isCurrentlyInMaintenance() ? 'primary' : 'warning'"
                    :loading="isSubmitting"
                    @click="toggleMaintenance"
                >
                    {{ isCurrentlyInMaintenance() ? 'Disable Maintenance' : 'Enable Maintenance' }}
                </HButton>
            </div>
        </div>
    </HModal>
</template>
