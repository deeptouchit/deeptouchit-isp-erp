<script setup>
import { ref } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import RefreshButton from '@/Components/UI/RefreshButton.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'

import {
    AdjustmentsHorizontalIcon,
    ServerIcon,
    GlobeAltIcon,
    ShieldCheckIcon,
    CpuChipIcon,
    CircleStackIcon,
    CheckIcon,
    ArrowUturnLeftIcon,
    XMarkIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    settings: {
        type: Object,
        required: true,
    },
    stats: {
        type: Object,
        required: true,
    },
})

// Form instance
const form = useForm({
    default_plan_id: Number(props.settings.default_plan_id) || 1,
    default_php_version: props.settings.default_php_version || '8.3',
    default_web_server: props.settings.default_web_server || 'nginx',
    default_document_root_format: props.settings.default_document_root_format || '/var/www/vhosts/{username}/{domain}/public_html',
    auto_ssl_on_provision: Boolean(props.settings.auto_ssl_on_provision),
    force_https_default: Boolean(props.settings.force_https_default),
    default_disk_quota_mb: Number(props.settings.default_disk_quota_mb) || 5120,
    default_bandwidth_monthly_gb: Number(props.settings.default_bandwidth_monthly_gb) || 50,
    default_inode_limit: Number(props.settings.default_inode_limit) || 150000,
    default_max_databases: Number(props.settings.default_max_databases) || 5,
    default_max_email_accounts: Number(props.settings.default_max_email_accounts) || 10,
    default_shell_access: props.settings.default_shell_access || 'jailed',
    default_ssh_port: Number(props.settings.default_ssh_port) || 22,
    default_auto_backup: Boolean(props.settings.default_auto_backup),
    primary_nameserver: props.settings.primary_nameserver || 'ns1.deeptouchit.com',
    secondary_nameserver: props.settings.secondary_nameserver || 'ns2.deeptouchit.com',
    php_memory_limit: props.settings.php_memory_limit || '256M',
    php_max_execution_time: Number(props.settings.php_max_execution_time) || 60,
    php_upload_max_filesize: props.settings.php_upload_max_filesize || '64M',
    php_post_max_size: props.settings.php_post_max_size || '64M',
})

const showResetModal = ref(false)
const isResetting = ref(false)
const feedbackMsg = ref('')

const submit = () => {
    form.post(route('admin.settings.defaults.update'), {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = 'Provisioning defaults and quotas updated.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

const confirmReset = () => {
    isResetting.value = true
    router.post(route('admin.settings.defaults.reset'), {}, {
        preserveScroll: true,
        onFinish: () => {
            isResetting.value = false
            showResetModal.value = false
            feedbackMsg.value = 'Defaults restored to factory baseline.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}
</script>

<template>
    <Head title="Provisioning Defaults - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Settings', href: route('admin.settings.general') },
                    { label: 'Defaults' }
                ]"
            >
                <template #actions>
                    <button
                        type="button"
                        @click="showResetModal = true"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowUturnLeftIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Reset Defaults</span>
                    </button>

                    <button
                        type="button"
                        @click="submit"
                        :disabled="form.processing"
                        class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-bold rounded-[3px] text-xs flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <CheckIcon class="w-3.5 h-3.5" />
                        <span>{{ form.processing ? 'Saving...' : 'Save Settings' }}</span>
                    </button>

                    <RefreshButton />
                </template>
            </PageHeader>

            <!-- Feedback Banner -->
            <div v-if="feedbackMsg" class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-3 py-2 rounded-[3px] text-xs font-bold flex items-center justify-between shadow-2xs">
                <div class="flex items-center gap-2">
                    <CheckIcon class="w-4 h-4 text-emerald-600" />
                    <span>{{ feedbackMsg }}</span>
                </div>
                <button @click="feedbackMsg = ''" class="text-emerald-600 hover:text-emerald-800 text-xs cursor-pointer">✕</button>
            </div>

            <!-- 2. Ultra-Compact KPI Info Cards (56px) -->
            <InfoCardsGrid :cols="4">
                <InfoCard
                    title="Default PHP Engine"
                    :value="`PHP ${form.default_php_version}`"
                    badge="Runtime"
                    badgeType="info"
                    color="blue"
                    :icon="CpuChipIcon"
                />

                <InfoCard
                    title="Web Server Engine"
                    :value="form.default_web_server === 'nginx' ? 'Nginx (FPM)' : 'Apache (event)'"
                    badge="HTTP"
                    badgeType="success"
                    color="emerald"
                    :icon="ServerIcon"
                />

                <InfoCard
                    title="Nameservers"
                    :value="form.primary_nameserver || 'ns1.deeptouchit.com'"
                    badge="DNS"
                    badgeType="info"
                    color="purple"
                    :icon="GlobeAltIcon"
                />

                <InfoCard
                    title="Auto SSL Issuance"
                    :value="form.auto_ssl_on_provision ? 'Auto Let\'s Encrypt' : 'Manual SSL'"
                    :badge="form.auto_ssl_on_provision ? 'Active' : 'Disabled'"
                    badgeType="success"
                    color="sky"
                    :icon="ShieldCheckIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Provisioning Defaults Form Panels -->
            <form @submit.prevent="submit" class="space-y-3.5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                    <!-- Provisioning Templates & Engines -->
                    <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs p-4 space-y-3">
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide flex items-center gap-2 border-b border-slate-100 pb-2">
                            <ServerIcon class="w-4 h-4 text-blue-600" />
                            Default Provisioning Engine & Runtimes
                        </h3>

                        <div class="space-y-2.5 text-xs">
                            <div class="grid grid-cols-2 gap-2">
                                <div class="space-y-1">
                                    <label class="block font-bold text-slate-700">Default PHP Version</label>
                                    <select v-model="form.default_php_version" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                                        <option value="8.4">PHP 8.4 (Latest)</option>
                                        <option value="8.3">PHP 8.3 (Recommended)</option>
                                        <option value="8.2">PHP 8.2</option>
                                        <option value="8.1">PHP 8.1</option>
                                        <option value="7.4">PHP 7.4 (Legacy)</option>
                                    </select>
                                </div>
                                <div class="space-y-1">
                                    <label class="block font-bold text-slate-700">Web Server Engine</label>
                                    <select v-model="form.default_web_server" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                                        <option value="nginx">Nginx High-Performance</option>
                                        <option value="apache">Apache 2.4</option>
                                    </select>
                                </div>
                            </div>

                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700">Document Root Directory Format</label>
                                <input v-model="form.default_document_root_format" type="text" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                            </div>

                            <div class="flex items-center gap-2 pt-2">
                                <input v-model="form.auto_ssl_on_provision" id="auto_ssl" type="checkbox" class="rounded-[2px] text-blue-600 focus:ring-blue-500 cursor-pointer" />
                                <label for="auto_ssl" class="font-medium text-slate-700 cursor-pointer">Automatically issue Let's Encrypt SSL on site creation</label>
                            </div>
                        </div>
                    </div>

                    <!-- Quotas & Limits Baseline -->
                    <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs p-4 space-y-3">
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide flex items-center gap-2 border-b border-slate-100 pb-2">
                            <CircleStackIcon class="w-4 h-4 text-blue-600" />
                            Default Account Quotas & Resource Baselines
                        </h3>

                        <div class="space-y-2.5 text-xs">
                            <div class="grid grid-cols-2 gap-2">
                                <div class="space-y-1">
                                    <label class="block font-bold text-slate-700">Disk Quota (MB)</label>
                                    <input v-model.number="form.default_disk_quota_mb" type="number" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                                </div>
                                <div class="space-y-1">
                                    <label class="block font-bold text-slate-700">Bandwidth (GB/mo)</label>
                                    <input v-model.number="form.default_bandwidth_monthly_gb" type="number" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <div class="space-y-1">
                                    <label class="block font-bold text-slate-700">Max MySQL Databases</label>
                                    <input v-model.number="form.default_max_databases" type="number" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                                </div>
                                <div class="space-y-1">
                                    <label class="block font-bold text-slate-700">Max Email Accounts</label>
                                    <input v-model.number="form.default_max_email_accounts" type="number" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Authoritative DNS Nameservers -->
                    <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs p-4 space-y-3">
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide flex items-center gap-2 border-b border-slate-100 pb-2">
                            <GlobeAltIcon class="w-4 h-4 text-blue-600" />
                            Authoritative Nameservers
                        </h3>

                        <div class="space-y-2.5 text-xs">
                            <div class="grid grid-cols-2 gap-2">
                                <div class="space-y-1">
                                    <label class="block font-bold text-slate-700">Primary NS1 FQDN</label>
                                    <input v-model="form.primary_nameserver" type="text" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                                </div>
                                <div class="space-y-1">
                                    <label class="block font-bold text-slate-700">Secondary NS2 FQDN</label>
                                    <input v-model="form.secondary_nameserver" type="text" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Default PHP.ini Directives -->
                    <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs p-4 space-y-3">
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide flex items-center gap-2 border-b border-slate-100 pb-2">
                            <CpuChipIcon class="w-4 h-4 text-blue-600" />
                            Default PHP.ini Directives
                        </h3>

                        <div class="space-y-2.5 text-xs">
                            <div class="grid grid-cols-2 gap-2">
                                <div class="space-y-1">
                                    <label class="block font-bold text-slate-700">Memory Limit</label>
                                    <input v-model="form.php_memory_limit" type="text" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                                </div>
                                <div class="space-y-1">
                                    <label class="block font-bold text-slate-700">Max Execution (Sec)</label>
                                    <input v-model.number="form.php_max_execution_time" type="number" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <div class="space-y-1">
                                    <label class="block font-bold text-slate-700">Upload Max Filesize</label>
                                    <input v-model="form.php_upload_max_filesize" type="text" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                                </div>
                                <div class="space-y-1">
                                    <label class="block font-bold text-slate-700">Post Max Size</label>
                                    <input v-model="form.php_post_max_size" type="text" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button
                        type="button"
                        @click="showResetModal = true"
                        class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        Reset Defaults
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-bold rounded-[3px] text-xs shadow-2xs transition cursor-pointer"
                    >
                        {{ form.processing ? 'Saving...' : 'Save Provisioning Baseline' }}
                    </button>
                </div>
            </form>
        </div>

        <!-- RESET CONFIRMATION MODAL -->
        <div v-if="showResetModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-amber-50 text-amber-600 flex items-center justify-center font-bold border border-amber-100">
                            <ArrowUturnLeftIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Reset Provisioning Defaults
                        </h3>
                    </div>
                    <button @click="showResetModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-2.5 text-xs">
                    <p class="text-slate-600">
                        Are you sure you want to reset all provisioning quotas, nameservers, and PHP runtime defaults back to factory settings?
                    </p>
                </div>

                <div class="px-4 py-2.5 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                    <button
                        type="button"
                        @click="showResetModal = false"
                        class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="confirmReset"
                        :disabled="isResetting"
                        class="px-3.5 py-1.5 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                    >
                        {{ isResetting ? 'Resetting...' : 'Confirm Reset' }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
