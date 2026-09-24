<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import HModal from '@/Components/UI/HModal.vue'
import HButton from '@/Components/UI/HButton.vue'
import HInput from '@/Components/UI/HInput.vue'
import { ShieldCheckIcon, ExclamationTriangleIcon, CheckCircleIcon } from '@heroicons/vue/24/outline'

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
const acknowledged = ref(false)
const isSubmitting = ref(false)

const approveHostKey = () => {
    if (!acknowledged.value || !props.server?.ssh_host_key_fingerprint) return

    isSubmitting.value = true
    router.post(route('admin.servers.host-key.approve', props.server.id), {
        fingerprint: props.server.ssh_host_key_fingerprint,
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
    <HModal :show="show" max-width="lg" @close="emit('close')">
        <div class="p-6">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-12 h-12 rounded-2xl bg-brand-50 dark:bg-brand-950/50 text-brand-600 dark:text-brand-400 flex items-center justify-center flex-shrink-0">
                    <ShieldCheckIcon class="w-6 h-6" />
                </div>
                <div>
                    <h3 class="text-lg font-bold text-hslate-900 dark:text-white">
                        Approve & Pin SSH Host Key
                    </h3>
                    <p class="text-xs text-hslate-500">
                        Node: <span class="font-semibold text-hslate-700 dark:text-hslate-300">{{ server?.name }}</span> ({{ server?.hostname }})
                    </p>
                </div>
            </div>

            <div class="space-y-4">
                <div class="p-3 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900/50 rounded-xl text-xs text-amber-800 dark:text-amber-300 flex items-start gap-2.5">
                    <ExclamationTriangleIcon class="w-4 h-4 flex-shrink-0 mt-0.5" />
                    <div>
                        <strong>Security Boundary:</strong> Approving this cryptographic fingerprint establishes strict host key pinning for all automated control-plane operations. Future connections will be rejected if the remote key changes unexpectedly.
                    </div>
                </div>

                <div class="space-y-3 bg-hslate-50 dark:bg-hslate-900/60 p-4 rounded-xl border border-hslate-200 dark:border-hslate-800">
                    <div>
                        <span class="text-[11px] uppercase font-bold tracking-wider text-hslate-400">Observed Fingerprint (Probe)</span>
                        <div class="mt-1 font-mono text-xs text-brand-600 dark:text-brand-400 bg-white dark:bg-hslate-950 p-2.5 rounded-lg border border-hslate-200 dark:border-hslate-800 select-all break-all">
                            {{ server?.ssh_host_key_fingerprint || 'No fingerprint observed yet' }}
                        </div>
                    </div>

                    <div v-if="server?.trusted_ssh_host_key_fingerprint">
                        <span class="text-[11px] uppercase font-bold tracking-wider text-hslate-400">Current Trusted Fingerprint</span>
                        <div class="mt-1 font-mono text-xs text-hslate-600 dark:text-hslate-400 bg-white dark:bg-hslate-950 p-2.5 rounded-lg border border-hslate-200 dark:border-hslate-800 select-all break-all">
                            {{ server?.trusted_ssh_host_key_fingerprint }}
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-hslate-700 dark:text-hslate-300 mb-1">
                        Audit Note / Change Reason
                    </label>
                    <HInput
                        v-model="reason"
                        placeholder="e.g. Verified server initial install on console"
                    />
                </div>

                <label class="flex items-start gap-2.5 cursor-pointer pt-2">
                    <input 
                        v-model="acknowledged" 
                        type="checkbox" 
                        class="mt-0.5 rounded border-hslate-300 text-brand-600 focus:ring-brand-500"
                    />
                    <span class="text-xs text-hslate-600 dark:text-hslate-300">
                        I confirm that I have verified this cryptographic fingerprint matches the target server's OpenSSH host certificate.
                    </span>
                </label>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3">
                <HButton variant="secondary" :disabled="isSubmitting" @click="emit('close')">
                    Cancel
                </HButton>
                <HButton 
                    variant="primary" 
                    :loading="isSubmitting" 
                    :disabled="!acknowledged || !server?.ssh_host_key_fingerprint"
                    @click="approveHostKey"
                >
                    <CheckCircleIcon class="w-4 h-4 mr-1.5 inline" />
                    Approve Fingerprint
                </HButton>
            </div>
        </div>
    </HModal>
</template>
