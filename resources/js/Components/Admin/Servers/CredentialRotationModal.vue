<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import HModal from '@/Components/UI/HModal.vue'
import HButton from '@/Components/UI/HButton.vue'
import HInput from '@/Components/UI/HInput.vue'
import { KeyIcon, LockClosedIcon, ShieldCheckIcon } from '@heroicons/vue/24/outline'

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

const credentialType = ref('ssh_password') // 'ssh_password' or 'ssh_private_key'
const secret = ref('')
const username = ref(props.server?.ssh_user || 'root')
const isSubmitting = ref(false)

const rotateCredential = () => {
    if (!secret.value) return

    isSubmitting.value = true
    router.post(route('admin.servers.credentials.rotate', props.server.id), {
        credential_type: credentialType.value,
        secret: secret.value,
        username: username.value,
        name: `Rotated on ${new Date().toLocaleDateString()}`,
    }, {
        preserveScroll: true,
        onFinish: () => {
            isSubmitting.value = false
            secret.value = ''
            emit('close')
        }
    })
}
</script>

<template>
    <HModal :show="show" max-width="md" @close="emit('close')">
        <div class="p-6">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-12 h-12 rounded-2xl bg-brand-50 dark:bg-brand-950/50 text-brand-600 dark:text-brand-400 flex items-center justify-center flex-shrink-0">
                    <KeyIcon class="w-6 h-6" />
                </div>
                <div>
                    <h3 class="text-lg font-bold text-hslate-900 dark:text-white">
                        Rotate SSH Credentials
                    </h3>
                    <p class="text-xs text-hslate-500">
                        Node: <span class="font-semibold text-hslate-700 dark:text-hslate-300">{{ server?.name }}</span>
                    </p>
                </div>
            </div>

            <div class="space-y-4">
                <div class="p-3 bg-hslate-50 dark:bg-hslate-900 border border-hslate-200 dark:border-hslate-800 rounded-xl text-xs text-hslate-600 dark:text-hslate-300 flex items-start gap-2.5">
                    <ShieldCheckIcon class="w-4 h-4 flex-shrink-0 mt-0.5 text-brand-600" />
                    <div>
                        The new secret will be encrypted using <strong>AES-256-GCM</strong> envelope encryption. The previous credential will be securely replaced.
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2 p-1 bg-hslate-100 dark:bg-hslate-900 rounded-xl">
                    <button
                        type="button"
                        :class="[
                            'py-2 text-xs font-bold rounded-lg transition-all',
                            credentialType === 'ssh_password' 
                                ? 'bg-white dark:bg-hslate-800 text-brand-600 dark:text-brand-400 shadow-sm' 
                                : 'text-hslate-500 hover:text-hslate-900'
                        ]"
                        @click="credentialType = 'ssh_password'"
                    >
                        SSH Password
                    </button>
                    <button
                        type="button"
                        :class="[
                            'py-2 text-xs font-bold rounded-lg transition-all',
                            credentialType === 'ssh_private_key' 
                                ? 'bg-white dark:bg-hslate-800 text-brand-600 dark:text-brand-400 shadow-sm' 
                                : 'text-hslate-500 hover:text-hslate-900'
                        ]"
                        @click="credentialType = 'ssh_private_key'"
                    >
                        SSH Private Key
                    </button>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-hslate-700 dark:text-hslate-300 mb-1">
                        SSH Username
                    </label>
                    <HInput v-model="username" placeholder="root" />
                </div>

                <div v-if="credentialType === 'ssh_password'">
                    <label class="block text-xs font-semibold text-hslate-700 dark:text-hslate-300 mb-1">
                        New SSH Password
                    </label>
                    <HInput
                        v-model="secret"
                        type="password"
                        placeholder="••••••••••••"
                    />
                </div>

                <div v-else>
                    <label class="block text-xs font-semibold text-hslate-700 dark:text-hslate-300 mb-1">
                        New RSA / ED25519 Private Key
                    </label>
                    <textarea
                        v-model="secret"
                        rows="4"
                        placeholder="-----BEGIN OPENSSH PRIVATE KEY-----&#10;...&#10;-----END OPENSSH PRIVATE KEY-----"
                        class="w-full text-xs font-mono rounded-xl border border-hslate-200 dark:border-hslate-800 bg-white dark:bg-hslate-950 text-hslate-900 dark:text-white focus:ring-2 focus:ring-brand-500 p-3"
                    ></textarea>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3">
                <HButton variant="secondary" :disabled="isSubmitting" @click="emit('close')">
                    Cancel
                </HButton>
                <HButton 
                    variant="primary" 
                    :loading="isSubmitting"
                    :disabled="!secret"
                    @click="rotateCredential"
                >
                    <LockClosedIcon class="w-4 h-4 mr-1.5 inline" />
                    Save & Encrypt Secret
                </HButton>
            </div>
        </div>
    </HModal>
</template>
