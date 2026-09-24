<script setup>
import { ref } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import { 
    ArchiveBoxIcon, 
    ArrowPathIcon, 
    ArrowDownTrayIcon, 
    TrashIcon, 
    PlusIcon, 
    ShieldCheckIcon, 
    ClockIcon, 
    ServerStackIcon, 
    CheckCircleIcon,
    ExclamationTriangleIcon,
    XMarkIcon,
    CircleStackIcon,
    DocumentDuplicateIcon,
    GlobeAltIcon,
    SparklesIcon,
    ArrowDownCircleIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    subscription: {
        type: Object,
        default: null
    },
    subscriptions: {
        type: Array,
        default: () => []
    },
    websites: {
        type: Array,
        default: () => []
    },
    backups: {
        type: Array,
        default: () => []
    },
    total_size_formatted: {
        type: String,
        default: '0 B'
    },
    total_count: {
        type: Number,
        default: 0
    },
    auto_backup_enabled: {
        type: Boolean,
        default: true
    },
    schedule: {
        type: String,
        default: 'Daily at 03:00 AM (UTC)'
    },
    retention_days: {
        type: Number,
        default: 7
    }
})

// Create Modal State
const showCreateModal = ref(false)
const createForm = useForm({
    domain: props.websites[0]?.domain || props.subscription?.domain || '',
    backup_type: 'full', // 'full', 'files_only', 'db_only'
})

const submitCreateBackup = () => {
    createForm.post(route('files.backups.create'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false
        }
    })
}

// Restore Modal State
const showRestoreModal = ref(false)
const selectedBackupForRestore = ref(null)
const restoreForm = useForm({
    filename: '',
    domain: props.websites[0]?.domain || props.subscription?.domain || '',
})

const openRestoreModal = (backup) => {
    selectedBackupForRestore.value = backup
    restoreForm.filename = backup.filename
    restoreForm.domain = props.websites[0]?.domain || props.subscription?.domain || ''
    showRestoreModal.value = true
}

const submitRestore = () => {
    restoreForm.post(route('files.backups.restore'), {
        preserveScroll: true,
        onSuccess: () => {
            showRestoreModal.value = false
        }
    })
}

// Delete Backup
const deleteBackup = (filename) => {
    if (confirm(`Are you sure you want to permanently delete backup "${filename}"?`)) {
        router.delete(route('files.backups.destroy', filename), {
            preserveScroll: true
        })
    }
}

// Download URL
const getDownloadUrl = (filename) => {
    return route('files.backups.download', filename)
}
</script>

<template>
    <Head title="Automated Daily & On-Demand Backups - DeepTouch Cloud" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto space-y-4">
            
            <!-- 1. Page Header with Breadcrumbs & Actions -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Files', href: '#' },
                    { label: 'Automated & On-Demand Backups' }
                ]"
                :show-refresh="true"
                @refreshed="router.reload({ preserveScroll: true })"
            >
                <template #actions>
                    <button 
                        @click="showCreateModal = true"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <PlusIcon class="w-3.5 h-3.5 stroke-[2.5]" />
                        <span>Create Backup Snapshot</span>
                    </button>
                </template>
            </PageHeader>

            <!-- 2. Top 3 Backup & Health Metric Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                
                <!-- Card 1: Total Backup Volume -->
                <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-2xs space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <ArchiveBoxIcon class="w-4 h-4 text-blue-600" />
                            <span>Total Backup Storage</span>
                        </span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200 uppercase font-mono">
                            {{ total_count }} SNAPSHOTS
                        </span>
                    </div>

                    <div>
                        <div class="flex items-baseline justify-between">
                            <span class="text-2xl font-black text-slate-900 font-mono">{{ total_size_formatted }}</span>
                            <span class="text-xs text-slate-500 font-mono">NVMe Fast Storage</span>
                        </div>
                        <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden mt-2">
                            <div class="bg-blue-600 h-full rounded-full" style="width: 40%"></div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-100">
                        <span>Format:</span>
                        <strong class="text-slate-700">Encrypted GZIP (.tar.gz)</strong>
                    </div>
                </div>

                <!-- Card 2: Auto-Retention Schedule -->
                <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-2xs space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <ClockIcon class="w-4 h-4 text-purple-600" />
                            <span>Auto-Retention Schedule</span>
                        </span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 uppercase font-mono">
                            ACTIVE
                        </span>
                    </div>

                    <div>
                        <div class="flex items-baseline justify-between">
                            <span class="text-2xl font-black text-purple-600 font-mono">{{ retention_days }} Days</span>
                            <span class="text-xs text-slate-500 font-mono">Rolling Retention</span>
                        </div>
                        <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden mt-2">
                            <div class="bg-purple-500 h-full rounded-full w-full"></div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-100">
                        <span>Daily Execution:</span>
                        <strong class="text-purple-700 font-mono">{{ schedule }}</strong>
                    </div>
                </div>

                <!-- Card 3: 1-Click Restoration Engine -->
                <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-2xs space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <ShieldCheckIcon class="w-4 h-4 text-emerald-600" />
                            <span>Instant Point-in-Time Restore</span>
                        </span>
                        <span class="text-[10px] font-bold text-slate-500 uppercase font-mono">
                            1-CLICK
                        </span>
                    </div>

                    <div class="space-y-1 text-xs font-mono">
                        <div class="flex items-center justify-between p-1.5 bg-slate-50 rounded border border-slate-200/70">
                            <span class="text-slate-500 text-[11px]">Web Files:</span>
                            <span class="font-bold text-slate-800">public_html Rollback</span>
                        </div>

                        <div class="flex items-center justify-between p-1.5 bg-slate-50 rounded border border-slate-200/70">
                            <span class="text-slate-500 text-[11px]">Database:</span>
                            <span class="font-bold text-emerald-700">Atomic MySQL Import</span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- 3. Backups & Snapshots Table -->
            <div class="bg-white rounded-lg border border-slate-200 shadow-2xs overflow-hidden">
                <div class="px-4 py-3 bg-slate-50/70 border-b border-slate-200 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <ServerStackIcon class="w-4 h-4 text-blue-600" />
                        <span class="text-xs font-bold text-slate-800">Available Backup Snapshots</span>
                    </div>
                    <span class="text-[11px] text-slate-500 font-mono">{{ backups.length }} Archives Available</span>
                </div>

                <div v-if="backups.length === 0" class="p-12 text-center text-slate-500 text-xs">
                    <ArchiveBoxIcon class="w-12 h-12 text-slate-300 mx-auto mb-2" />
                    <p class="font-bold text-slate-700">No backup snapshots generated yet</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Click "Create Backup Snapshot" above or wait for the nightly automated backup.</p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-700">
                        <thead class="bg-slate-50/90 text-slate-500 font-bold uppercase tracking-wider text-[10.5px] border-b border-slate-200 select-none">
                            <tr>
                                <th class="py-2.5 px-4">Backup Snapshot Name</th>
                                <th class="py-2.5 px-4 w-36">Type</th>
                                <th class="py-2.5 px-4 w-28">Archive Size</th>
                                <th class="py-2.5 px-4 w-44">Created At</th>
                                <th class="py-2.5 px-4 w-36 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr 
                                v-for="b in backups" 
                                :key="b.filename"
                                class="hover:bg-slate-50/80 transition-colors"
                            >
                                <!-- Name -->
                                <td class="py-3 px-4 font-mono font-medium">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded bg-blue-50 border border-blue-200 flex items-center justify-center text-blue-600 shrink-0">
                                            <ArchiveBoxIcon class="w-4 h-4" />
                                        </div>
                                        <div>
                                            <span class="font-bold text-slate-900 block truncate max-w-sm">{{ b.filename }}</span>
                                            <span class="text-[10px] text-slate-400 font-mono">Location: local_nvme</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Type Badge -->
                                <td class="py-3 px-4">
                                    <span 
                                        class="text-[10px] font-bold px-1.5 py-0.5 rounded font-mono uppercase"
                                        :class="{
                                            'bg-purple-50 text-purple-700 border border-purple-200': b.type.includes('Full'),
                                            'bg-blue-50 text-blue-700 border border-blue-200': b.type.includes('Files'),
                                            'bg-emerald-50 text-emerald-700 border border-emerald-200': b.type.includes('MySQL'),
                                        }"
                                    >
                                        {{ b.type }}
                                    </span>
                                </td>

                                <!-- Size -->
                                <td class="py-3 px-4 font-mono font-bold text-slate-800 text-[11px]">
                                    {{ b.size_formatted }}
                                </td>

                                <!-- Created At -->
                                <td class="py-3 px-4 font-mono text-[11px] text-slate-500">
                                    <span class="block text-slate-700 font-bold">{{ b.created_at }}</span>
                                    <span class="text-[10px] text-slate-400">({{ b.age }})</span>
                                </td>

                                <!-- Actions -->
                                <td class="py-3 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <!-- Download -->
                                        <a 
                                            :href="getDownloadUrl(b.filename)"
                                            class="p-1.5 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-[3px] shadow-2xs transition cursor-pointer flex items-center gap-1 font-bold text-[11px]"
                                            title="Download Backup (.tar.gz)"
                                        >
                                            <ArrowDownTrayIcon class="w-3.5 h-3.5 text-blue-600" />
                                            <span class="hidden sm:inline">Download</span>
                                        </a>

                                        <!-- Restore -->
                                        <button 
                                            @click="openRestoreModal(b)"
                                            class="p-1.5 bg-white hover:bg-purple-50 text-purple-700 border border-purple-200 rounded-[3px] shadow-2xs transition cursor-pointer flex items-center gap-1 font-bold text-[11px]"
                                            title="1-Click Restore Snapshot"
                                        >
                                            <ArrowPathIcon class="w-3.5 h-3.5 text-purple-600" />
                                            <span class="hidden sm:inline">Restore</span>
                                        </button>

                                        <!-- Delete -->
                                        <button 
                                            @click="deleteBackup(b.filename)"
                                            class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded transition cursor-pointer"
                                            title="Delete Backup"
                                        >
                                            <TrashIcon class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 4. Automated Daily Backup Settings Info Box -->
            <div class="bg-slate-900 text-slate-100 rounded-lg p-5 shadow-2xs space-y-3.5 border border-slate-800">
                <div class="flex items-center justify-between border-b border-slate-800 pb-2.5">
                    <span class="text-xs font-bold text-slate-200 flex items-center gap-1.5">
                        <ClockIcon class="w-4 h-4 text-emerald-400" />
                        <span>Automated Disaster Recovery & Rotation Policy</span>
                    </span>
                    <span class="text-[10.5px] font-mono text-emerald-400 font-bold">CRON ACTIVE</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs font-mono">
                    <div class="p-3 bg-slate-800/80 rounded border border-slate-700 space-y-1">
                        <span class="text-slate-400 text-[11px] block font-bold uppercase">Nightly Backup Window</span>
                        <p class="text-slate-200 font-bold">Every Day @ 03:00 AM UTC</p>
                        <span class="text-[10px] text-slate-400 font-sans">Zero-impact background execution via cron.</span>
                    </div>

                    <div class="p-3 bg-slate-800/80 rounded border border-slate-700 space-y-1">
                        <span class="text-slate-400 text-[11px] block font-bold uppercase">Retention Policy</span>
                        <p class="text-emerald-400 font-bold">7 Daily Snapshots Retained</p>
                        <span class="text-[10px] text-slate-400 font-sans">Older snapshots rotated automatically to save quota.</span>
                    </div>

                    <div class="p-3 bg-slate-800/80 rounded border border-slate-700 space-y-1">
                        <span class="text-slate-400 text-[11px] block font-bold uppercase">Encrypted Storage</span>
                        <p class="text-blue-300 font-bold">NVMe High-Speed Array</p>
                        <span class="text-[10px] text-slate-400 font-sans">Protected with strict user jail permissions.</span>
                    </div>
                </div>
            </div>

        </div>

        <!-- CREATE BACKUP MODAL -->
        <div v-if="showCreateModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-lg max-w-md w-full p-5 shadow-2xl space-y-4 border border-slate-200">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2">
                        <ArchiveBoxIcon class="w-5 h-5 text-blue-600" />
                        <h3 class="font-bold text-slate-900 text-sm">Generate On-Demand Backup</h3>
                    </div>
                    <button @click="showCreateModal = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitCreateBackup" class="space-y-4 text-xs">
                    
                    <!-- Domain Selector -->
                    <div class="space-y-1.5">
                        <label class="block font-bold text-slate-700">Target Website Domain</label>
                        <select 
                            v-model="createForm.domain"
                            required
                            class="w-full text-xs font-bold rounded-[3px] border-slate-300 bg-white text-slate-900 py-2 px-2.5"
                        >
                            <option v-for="site in websites" :key="site.id" :value="site.domain">
                                {{ site.domain }}
                            </option>
                        </select>
                    </div>

                    <!-- Backup Type Radio Cards -->
                    <div class="space-y-2">
                        <label class="block font-bold text-slate-700">Snapshot Scope</label>
                        
                        <label class="p-3 rounded border flex items-start gap-3 cursor-pointer transition" :class="createForm.backup_type === 'full' ? 'border-blue-500 bg-blue-50/50 ring-1 ring-blue-500' : 'border-slate-200 hover:bg-slate-50'">
                            <input type="radio" v-model="createForm.backup_type" value="full" class="mt-0.5 text-blue-600 focus:ring-blue-500" />
                            <div>
                                <span class="font-bold text-slate-900 block">Full Account Snapshot (Recommended)</span>
                                <span class="text-[11px] text-slate-500">Includes all web files (public_html), MySQL databases, and mailboxes.</span>
                            </div>
                        </label>

                        <label class="p-3 rounded border flex items-start gap-3 cursor-pointer transition" :class="createForm.backup_type === 'files_only' ? 'border-blue-500 bg-blue-50/50 ring-1 ring-blue-500' : 'border-slate-200 hover:bg-slate-50'">
                            <input type="radio" v-model="createForm.backup_type" value="files_only" class="mt-0.5 text-blue-600 focus:ring-blue-500" />
                            <div>
                                <span class="font-bold text-slate-900 block">Web Files Only (public_html)</span>
                                <span class="text-[11px] text-slate-500">Fast snapshot of your code, plugins, uploads, and assets.</span>
                            </div>
                        </label>

                        <label class="p-3 rounded border flex items-start gap-3 cursor-pointer transition" :class="createForm.backup_type === 'db_only' ? 'border-blue-500 bg-blue-50/50 ring-1 ring-blue-500' : 'border-slate-200 hover:bg-slate-50'">
                            <input type="radio" v-model="createForm.backup_type" value="db_only" class="mt-0.5 text-blue-600 focus:ring-blue-500" />
                            <div>
                                <span class="font-bold text-slate-900 block">MySQL Databases Only (.sql.gz)</span>
                                <span class="text-[11px] text-slate-500">High-speed mysqldump of all relational database schemas.</span>
                            </div>
                        </label>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                        <button 
                            type="button" 
                            @click="showCreateModal = false" 
                            class="px-3 py-1.5 bg-slate-100 text-slate-700 rounded-[3px] text-xs font-bold cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            :disabled="createForm.processing"
                            class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold shadow-2xs transition cursor-pointer disabled:opacity-50 flex items-center gap-1.5"
                        >
                            <ArchiveBoxIcon class="w-4 h-4" :class="{ 'animate-spin': createForm.processing }" />
                            <span>{{ createForm.processing ? 'Generating Snapshot...' : 'Create Snapshot Now' }}</span>
                        </button>
                    </div>

                </form>
            </div>
        </div>

        <!-- RESTORE CONFIRMATION MODAL -->
        <div v-if="showRestoreModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-lg max-w-md w-full p-5 shadow-2xl space-y-4 border border-slate-200">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2">
                        <ArrowPathIcon class="w-5 h-5 text-purple-600" />
                        <h3 class="font-bold text-slate-900 text-sm">Restore Snapshot Confirmation</h3>
                    </div>
                    <button @click="showRestoreModal = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="space-y-3 text-xs text-slate-600">
                    <p>
                        Are you sure you want to restore snapshot <strong class="text-slate-900 font-mono">{{ selectedBackupForRestore?.filename }}</strong> into <strong class="text-purple-700 font-mono">{{ restoreForm.domain }}</strong>?
                    </p>

                    <div class="p-3 bg-amber-50 border border-amber-200 rounded text-amber-900 space-y-1">
                        <div class="flex items-center gap-1.5 font-bold">
                            <ExclamationTriangleIcon class="w-4 h-4 text-amber-600" />
                            <span>Warning: Existing Data Overwrite</span>
                        </div>
                        <p class="text-[11px] leading-relaxed">
                            Files in <code>public_html/</code> and MySQL database tables will be rolled back to the state saved inside this snapshot.
                        </p>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button 
                        type="button" 
                        @click="showRestoreModal = false" 
                        class="px-3 py-1.5 bg-slate-100 text-slate-700 rounded-[3px] text-xs font-bold cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button 
                        type="button"
                        @click="submitRestore"
                        :disabled="restoreForm.processing"
                        class="px-4 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded-[3px] text-xs font-bold shadow-2xs transition cursor-pointer disabled:opacity-50 flex items-center gap-1.5"
                    >
                        <ArrowPathIcon class="w-4 h-4" :class="{ 'animate-spin': restoreForm.processing }" />
                        <span>{{ restoreForm.processing ? 'Restoring Snapshot...' : 'Confirm Restore' }}</span>
                    </button>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>
