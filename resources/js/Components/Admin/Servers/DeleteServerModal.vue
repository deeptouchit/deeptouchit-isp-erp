<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import HModal from '@/Components/UI/HModal.vue'
import HButton from '@/Components/UI/HButton.vue'
import { TrashIcon, ExclamationTriangleIcon, ShieldExclamationIcon } from '@heroicons/vue/24/outline'

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

const isSubmitting = ref(false)

const isMaster = () => {
    return Boolean(props.server?.is_master)
}

const hasActiveAccounts = () => {
    return Number(props.server?.subscriptions_count || 0) > 0
}

const canDelete = () => {
    return !isMaster() && !hasActiveAccounts()
}

const deleteServer = () => {
    if (!canDelete()) return

    isSubmitting.value = true
    router.delete(route('admin.servers.destroy', props.server.id), {
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
                <div class="w-12 h-12 rounded-2xl bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 flex items-center justify-center flex-shrink-0">
                    <TrashIcon class="w-6 h-6" />
                </div>
                <div>
                    <h3 class="text-lg font-bold text-hslate-900 dark:text-white">
                        Decommission Server
                    </h3>
                    <p class="text-xs text-hslate-500">
                        Node: <span class="font-semibold text-hslate-700 dark:text-hslate-300">{{ server?.name }}</span>
                    </p>
                </div>
            </div>

            <div class="space-y-4">
                <div v-if="isMaster()" class="p-4 bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-900/50 rounded-xl text-xs text-rose-800 dark:text-rose-300 flex items-start gap-3">
                    <ShieldExclamationIcon class="w-5 h-5 flex-shrink-0 text-rose-600" />
                    <div>
                        <strong class="block text-sm font-bold mb-1">Protected Master Control Node</strong>
                        This server is the primary cluster control plane node hosting the control panel and cannot be deleted or decommissioned.
                    </div>
                </div>

                <div v-else-if="hasActiveAccounts()" class="p-4 bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-900/50 rounded-xl text-xs text-rose-800 dark:text-rose-300 flex items-start gap-3">
                    <ExclamationTriangleIcon class="w-5 h-5 flex-shrink-0 text-rose-600" />
                    <div>
                        <strong class="block text-sm font-bold mb-1">Active Hosting Accounts Detected</strong>
                        There are <strong class="underline">{{ server?.subscriptions_count }} active hosting accounts</strong> allocated to this node. You must migrate or terminate these accounts before this node can be deleted.
                    </div>
                </div>

                <div v-else class="space-y-3">
                    <div class="p-3 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900/50 rounded-xl text-xs text-amber-800 dark:text-amber-300 flex items-start gap-2.5">
                        <ExclamationTriangleIcon class="w-4 h-4 flex-shrink-0 mt-0.5" />
                        <div>
                            This action will soft-delete the server node record, revoke all active agent push credentials, and remove it from cluster routing tables.
                        </div>
                    </div>
                    <p class="text-sm text-hslate-600 dark:text-hslate-300">
                        Are you sure you want to decommission <strong class="text-hslate-900 dark:text-white">{{ server?.name }}</strong> ({{ server?.ip_address }})?
                    </p>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3">
                <HButton variant="secondary" :disabled="isSubmitting" @click="emit('close')">
                    Cancel
                </HButton>
                <HButton 
                    variant="danger" 
                    :disabled="!canDelete()"
                    :loading="isSubmitting"
                    @click="deleteServer"
                >
                    Decommission Node
                </HButton>
            </div>
        </div>
    </HModal>
</template>
