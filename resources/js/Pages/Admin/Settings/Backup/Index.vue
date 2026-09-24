<script setup>
import { ref } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import RefreshButton from '@/Components/UI/RefreshButton.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'

import {
    ArchiveBoxIcon,
    CloudArrowUpIcon,
    ServerIcon,
    ShieldCheckIcon,
    ArrowPathIcon,
    LockClosedIcon,
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
    auto_backup_enabled: Boolean(props.settings.auto_backup_enabled),
    default_storage_driver: props.settings.default_storage_driver || 'local',
    compression_algorithm: props.settings.compression_algorithm || 'gzip',
    compression_level: Number(props.settings.compression_level) || 6,
    execution_window_hour: Number(props.settings.execution_window_hour) || 2,
    daily_retention_days: Number(props.settings.daily_retention_days) || 7,
    weekly_retention_weeks: Number(props.settings.weekly_retention_weeks) || 4,
    monthly_retention_months: Number(props.settings.monthly_retention_months) || 3,
    db_single_transaction: Boolean(props.settings.db_single_transaction),
    encryption_enabled: Boolean(props.settings.encryption_enabled),
    encryption_cipher: props.settings.encryption_cipher || 'AES-256-CBC',
    disk_safety_threshold_percent: Number(props.settings.disk_safety_threshold_percent) || 85,
    offsite_replication_enabled: Boolean(props.settings.offsite_replication_enabled),
    alert_email: props.settings.alert_email || 'admin@deeptouchit.com',
})

const showResetModal = ref(false)
const isResetting = ref(false)
const feedbackMsg = ref('')

const submit = () => {
    form.post(route('admin.settings.backup.update'), {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = 'Backup engine parameters updated.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

const confirmReset = () => {
    isResetting.value = true
    router.post(route('admin.settings.backup.reset'), {}, {
        preserveScroll: true,
        onFinish: () => {
            isResetting.value = false
            showResetModal.value = false
            feedbackMsg.value = 'Backup settings restored to defaults.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}
</script>

<template>
    <Head title="Backup Settings - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Settings', href: route('admin.settings.general') },
                    { label: 'Backup & Recovery' }
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
                    title="Automated Engine"
                    :value="form.auto_backup_enabled ? 'Automated GFS Active' : 'Manual Trigger Only'"
                    :badge="form.auto_backup_enabled ? 'Active' : 'Disabled'"
                    :badgeType="form.auto_backup_enabled ? 'success' : 'info'"
                    :color="form.auto_backup_enabled ? 'emerald' : 'blue'"
                    :icon="ArchiveBoxIcon"
                />

                <InfoCard
                    title="Storage Target"
                    :value="form.default_storage_driver.toUpperCase()"
                    badge="Repository"
                    badgeType="info"
                    color="blue"
                    :icon="CloudArrowUpIcon"
                />

                <InfoCard
                    title="GFS Retention"
                    :value="`${form.daily_retention_days}d / ${form.weekly_retention_weeks}w / ${form.monthly_retention_months}m`"
                    badge="Retention"
                    badgeType="info"
                    color="purple"
                    :icon="CircleStackIcon"
                />

                <InfoCard
                    title="AES-256 Encryption"
                    :value="form.encryption_enabled ? 'Cipher Protected' : 'Plain Archive'"
                    :badge="form.encryption_enabled ? 'Encrypted' : 'Plain'"
                    badgeType="success"
                    color="sky"
                    :icon="LockClosedIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Backup Settings Form Panels -->
            <form @submit.prevent="submit" class="space-y-3.5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                    <!-- Engine & Scheduling -->
                    <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs p-4 space-y-3">
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide flex items-center gap-2 border-b border-slate-100 pb-2">
                            <ArchiveBoxIcon class="w-4 h-4 text-blue-600" />
                            Engine Automation & Compression
                        </h3>

                        <div class="space-y-2.5 text-xs">
                            <div class="flex items-center gap-2">
                                <input v-model="form.auto_backup_enabled" id="auto_bk" type="checkbox" class="rounded-[2px] text-blue-600 focus:ring-blue-500 cursor-pointer" />
                                <label for="auto_bk" class="font-bold text-slate-900 cursor-pointer">Enable Scheduled Automatic Server Backups</label>
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <div class="space-y-1">
                                    <label class="block font-bold text-slate-700">Storage Driver Target</label>
                                    <select v-model="form.default_storage_driver" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                                        <option value="local">Local Filesystem Storage</option>
                                        <option value="s3">Amazon S3 / Wasabi / MinIO</option>
                                        <option value="sftp">Remote SFTP Storage Server</option>
                                        <option value="google">Google Cloud Storage</option>
                                    </select>
                                </div>
                                <div class="space-y-1">
                                    <label class="block font-bold text-slate-700">Execution Window</label>
                                    <select v-model.number="form.execution_window_hour" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                                        <option :value="1">01:00 AM (Low Traffic)</option>
                                        <option :value="2">02:00 AM (Recommended)</option>
                                        <option :value="3">03:00 AM</option>
                                        <option :value="4">04:00 AM</option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <div class="space-y-1">
                                    <label class="block font-bold text-slate-700">Compression Algorithm</label>
                                    <select v-model="form.compression_algorithm" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                                        <option value="gzip">GZIP (.tar.gz)</option>
                                        <option value="zstd">Zstandard Fast (.tar.zst)</option>
                                        <option value="bzip2">BZIP2 High (.tar.bz2)</option>
                                    </select>
                                </div>
                                <div class="space-y-1">
                                    <label class="block font-bold text-slate-700">Compression Level (1-9)</label>
                                    <input v-model.number="form.compression_level" type="number" min="1" max="9" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- GFS Retention Policy -->
                    <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs p-4 space-y-3">
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide flex items-center gap-2 border-b border-slate-100 pb-2">
                            <CircleStackIcon class="w-4 h-4 text-blue-600" />
                            GFS Snapshot Retention Policy
                        </h3>

                        <div class="space-y-2.5 text-xs">
                            <div class="grid grid-cols-3 gap-2">
                                <div class="space-y-1">
                                    <label class="block font-bold text-slate-700">Daily (Days)</label>
                                    <input v-model.number="form.daily_retention_days" type="number" min="1" max="90" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                                </div>
                                <div class="space-y-1">
                                    <label class="block font-bold text-slate-700">Weekly (Weeks)</label>
                                    <input v-model.number="form.weekly_retention_weeks" type="number" min="1" max="52" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                                </div>
                                <div class="space-y-1">
                                    <label class="block font-bold text-slate-700">Monthly (Months)</label>
                                    <input v-model.number="form.monthly_retention_months" type="number" min="1" max="36" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                                </div>
                            </div>

                            <div class="flex items-center gap-2 pt-2">
                                <input v-model="form.encryption_enabled" id="enc_on" type="checkbox" class="rounded-[2px] text-blue-600 focus:ring-blue-500 cursor-pointer" />
                                <label for="enc_on" class="font-bold text-slate-900 cursor-pointer">Encrypt backup archives with AES-256-CBC</label>
                            </div>

                            <div class="space-y-1 pt-1">
                                <label class="block font-bold text-slate-700">Disk Safety Space Threshold (%)</label>
                                <input v-model.number="form.disk_safety_threshold_percent" type="number" min="50" max="95" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
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
                        {{ form.processing ? 'Saving...' : 'Save Backup Engine Settings' }}
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
                            Reset Backup Settings
                        </h3>
                    </div>
                    <button @click="showResetModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-2.5 text-xs">
                    <p class="text-slate-600">
                        Are you sure you want to reset all backup engine schedules, storage targets, and retention rules back to defaults?
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
