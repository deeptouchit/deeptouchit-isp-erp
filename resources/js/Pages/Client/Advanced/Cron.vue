<script setup>
import { ref, computed } from 'vue'
import { Head, useForm, router, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import { 
    ClockIcon, 
    PlusIcon, 
    PencilSquareIcon, 
    TrashIcon, 
    PlayIcon,
    ClipboardDocumentIcon,
    CheckIcon,
    ServerIcon,
    ShieldCheckIcon,
    ArrowPathIcon,
    XMarkIcon,
    ExclamationTriangleIcon,
    InformationCircleIcon,
    MagnifyingGlassIcon,
    DocumentTextIcon,
    CommandLineIcon,
    CheckCircleIcon,
    XCircleIcon,
    SparklesIcon,
    ArrowUturnLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    jobs: {
        type: Array,
        default: () => []
    },
    recentLogs: {
        type: Array,
        default: () => []
    },
    systemPaths: {
        type: Object,
        default: () => ({
            php_binary: '/usr/bin/php',
            curl_binary: '/usr/bin/curl',
            home_dir: '/var/www/vhosts/user',
            public_html: '/var/www/vhosts/user/domain.com/public_html',
            username: 'user',
            domain: 'domain.com'
        })
    },
    sessionFlash: {
        type: Object,
        default: () => ({})
    }
})

// Active Tab
const activeTab = ref('jobs') // 'jobs' | 'logs' | 'templates'

// Search
const searchQuery = ref('')
const filteredJobs = computed(() => {
    if (!searchQuery.value.trim()) return props.jobs
    const q = searchQuery.value.toLowerCase().trim()
    return props.jobs.filter(j => 
        j.title.toLowerCase().includes(q) ||
        j.command.toLowerCase().includes(q) ||
        j.cron_expression.toLowerCase().includes(q) ||
        j.schedule_human.toLowerCase().includes(q)
    )
})

// Modals
const showCreateModal = ref(false)
const showEditModal = ref(false)
const showDeleteModal = ref(false)
const showLogModal = ref(false)

const editingJob = ref(null)
const deletingJob = ref(null)
const viewingLog = ref(null)
const runningJobId = ref(null)

// Copy helper
const copiedField = ref(null)
const copyToClipboard = (text, fieldName) => {
    navigator.clipboard.writeText(text)
    copiedField.value = fieldName
    setTimeout(() => {
        copiedField.value = null
    }, 2000)
}

// Schedule Presets
const schedulePresets = [
    { label: 'Every minute (* * * * *)', value: '* * * * *' },
    { label: 'Every 5 minutes (*/5 * * * *)', value: '*/5 * * * *' },
    { label: 'Every 15 minutes (*/15 * * * *)', value: '*/15 * * * *' },
    { label: 'Every 30 minutes (*/30 * * * *)', value: '*/30 * * * *' },
    { label: 'Hourly at minute 0 (0 * * * *)', value: '0 * * * *' },
    { label: 'Twice daily at 00:00 & 12:00 (0 0,12 * * *)', value: '0 0,12 * * *' },
    { label: 'Once daily at Midnight (0 0 * * *)', value: '0 0 * * *' },
    { label: 'Weekly on Sunday (0 0 * * 0)', value: '0 0 * * 0' },
    { label: 'Monthly on the 1st (0 0 1 * *)', value: '0 0 1 * *' },
    { label: 'Custom Expression...', value: 'custom' },
]

// Form
const jobForm = useForm({
    title: '',
    preset: '* * * * *',
    cron_expression: '* * * * *',
    command: '',
    output_handling: 'discard',
    is_enabled: true,
})

const onPresetChange = () => {
    if (jobForm.preset !== 'custom') {
        jobForm.cron_expression = jobForm.preset
    }
}

const openCreateModal = () => {
    jobForm.reset({
        title: '',
        preset: '*/5 * * * *',
        cron_expression: '*/5 * * * *',
        command: `${props.systemPaths.php_binary} ${props.systemPaths.public_html}/artisan schedule:run`,
        output_handling: 'discard',
        is_enabled: true,
    })
    showCreateModal.value = true
}

const openEditModal = (job) => {
    editingJob.value = job
    jobForm.title = job.title
    jobForm.cron_expression = job.cron_expression
    const matchedPreset = schedulePresets.find(p => p.value === job.cron_expression)
    jobForm.preset = matchedPreset ? matchedPreset.value : 'custom'
    jobForm.command = job.command
    jobForm.output_handling = job.output_handling
    jobForm.is_enabled = job.is_enabled
    showEditModal.value = true
}

const submitCreateJob = () => {
    jobForm.post(route('advanced.cron.add-job'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false
            jobForm.reset()
        }
    })
}

const submitEditJob = () => {
    if (!editingJob.value) return
    jobForm.put(route('advanced.cron.update-job', editingJob.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false
            editingJob.value = null
        }
    })
}

const confirmDelete = (job) => {
    deletingJob.value = job
    showDeleteModal.value = true
}

const executeDelete = () => {
    if (!deletingJob.value) return
    router.delete(route('advanced.cron.delete-job', deletingJob.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false
            deletingJob.value = null
        }
    })
}

const toggleJob = (job) => {
    router.post(route('advanced.cron.toggle-job', job.id), {}, {
        preserveScroll: true,
    })
}

const runJobNow = (job) => {
    runningJobId.value = job.id
    router.post(route('advanced.cron.run-now', job.id), {}, {
        preserveScroll: true,
        onFinish: () => {
            runningJobId.value = null
        }
    })
}

const clearLogs = () => {
    if (!confirm('Clear all cron execution history logs?')) return
    router.post(route('advanced.cron.clear-logs'), {}, {
        preserveScroll: true,
    })
}

// Template Presets
const commandTemplates = [
    {
        title: 'Laravel Task Scheduler',
        description: 'Runs scheduled Artisan commands, queues, and background maintenance every minute.',
        cron_expression: '* * * * *',
        command: `${props.systemPaths.php_binary} ${props.systemPaths.public_html}/artisan schedule:run`,
    },
    {
        title: 'WordPress Background Cron',
        description: 'Triggers WordPress scheduled posts, plugin updates, and WooCommerce background tasks.',
        cron_expression: '*/15 * * * *',
        command: `${props.systemPaths.php_binary} ${props.systemPaths.public_html}/wp-cron.php`,
    },
    {
        title: 'cURL Webhook / URL Ping',
        description: 'Hits an external HTTP endpoint or webhook URL via cURL silently in the background.',
        cron_expression: '0 * * * *',
        command: `${props.systemPaths.curl_binary} -s "https://${props.systemPaths.domain}/api/cron/process" >/dev/null 2>&1`,
    },
    {
        title: 'Clear Cache & Temp Files',
        description: 'Removes expired cache entries and temporary files daily at 02:00 AM.',
        cron_expression: '0 2 * * *',
        command: `find ${props.systemPaths.home_dir}/tmp -type f -mtime +7 -delete`,
    }
]

const useTemplate = (tpl) => {
    jobForm.title = tpl.title
    jobForm.cron_expression = tpl.cron_expression
    const matchedPreset = schedulePresets.find(p => p.value === tpl.cron_expression)
    jobForm.preset = matchedPreset ? matchedPreset.value : 'custom'
    jobForm.command = tpl.command
    jobForm.output_handling = 'discard'
    jobForm.is_enabled = true
    activeTab.value = 'jobs'
    showCreateModal.value = true
}
</script>

<template>
    <Head title="Scheduled Cron Jobs - DeepTouch Host" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto space-y-4">
            
            <!-- 1. Page Header with Breadcrumbs & Actions -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Advanced Tools', href: '#' },
                    { label: 'Scheduled Cron Jobs' }
                ]"
                :show-refresh="true"
                @refreshed="router.reload({ preserveScroll: true })"
            >
                <template #actions>
                    <button 
                        @click="activeTab = 'templates'"
                        class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-[3px] text-xs font-bold flex items-center gap-1.5 transition cursor-pointer shadow-2xs"
                    >
                        <SparklesIcon class="w-3.5 h-3.5 text-indigo-600" />
                        <span>Command Templates</span>
                    </button>

                    <button 
                        @click="openCreateModal"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <PlusIcon class="w-3.5 h-3.5 stroke-[2.5]" />
                        <span>Create Cron Job</span>
                    </button>
                </template>
            </PageHeader>

            <!-- Manual Run Output Terminal Box (If executed in session) -->
            <div 
                v-if="sessionFlash?.cron_run_output" 
                class="bg-slate-900 border border-slate-800 rounded-lg p-4 shadow-xl space-y-2.5 text-slate-100 animate-in fade-in duration-200"
            >
                <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                    <div class="flex items-center gap-2">
                        <CommandLineIcon class="w-4 h-4 text-emerald-400" />
                        <span class="text-xs font-bold font-mono">Manual Execution Console Output</span>
                        <span 
                            class="text-[10px] font-mono font-bold px-1.5 py-0.2 rounded uppercase"
                            :class="sessionFlash.cron_run_exit_code === 0 ? 'bg-emerald-950 text-emerald-400 border border-emerald-800' : 'bg-rose-950 text-rose-400 border border-rose-800'"
                        >
                            Exit Code: {{ sessionFlash.cron_run_exit_code }} ({{ sessionFlash.cron_run_duration_ms }}ms)
                        </span>
                    </div>

                    <button 
                        @click="sessionFlash.cron_run_output = null" 
                        class="text-slate-400 hover:text-white cursor-pointer"
                    >
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <pre class="font-mono text-xs text-slate-300 bg-slate-950/80 p-3 rounded overflow-x-auto leading-relaxed max-h-48 whitespace-pre-wrap">{{ sessionFlash.cron_run_output }}</pre>
            </div>

            <!-- 2. Top 3 KPI / System Parameter Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                
                <!-- Card 1: Active Cron Jobs & Status -->
                <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-2xs space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <ClockIcon class="w-4 h-4 text-blue-600" />
                            <span>Cron Scheduler Daemon</span>
                        </span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 font-mono">
                            RUNNING
                        </span>
                    </div>

                    <div>
                        <div class="flex items-baseline gap-2">
                            <span class="text-2xl font-black text-slate-900 font-mono">{{ jobs.filter(j => j.is_enabled).length }}</span>
                            <span class="text-xs text-slate-400 font-bold font-mono">/ {{ jobs.length }} Active Jobs</span>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-1 font-mono">
                            User crontab: <strong class="text-slate-800">{{ systemPaths.username }}</strong>
                        </p>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-100 font-mono">
                        <span>Executed Today:</span>
                        <strong class="text-slate-700">{{ recentLogs.length }} Runs Logged</strong>
                    </div>
                </div>

                <!-- Card 2: System Binary Paths -->
                <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-2xs space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <ServerIcon class="w-4 h-4 text-emerald-600" />
                            <span>System Binary Paths</span>
                        </span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 border border-slate-200 font-mono">
                            CLI
                        </span>
                    </div>

                    <div class="space-y-1 text-xs font-mono">
                        <div class="bg-slate-50 p-1.5 rounded border border-slate-200/70 flex items-center justify-between">
                            <span class="text-slate-500">PHP:</span>
                            <div class="flex items-center gap-1">
                                <span class="font-bold text-slate-900">{{ systemPaths.php_binary }}</span>
                                <button 
                                    @click="copyToClipboard(systemPaths.php_binary, 'php_bin')"
                                    class="text-slate-400 hover:text-blue-600 cursor-pointer ml-1"
                                    title="Copy PHP path"
                                >
                                    <CheckIcon v-if="copiedField === 'php_bin'" class="w-3.5 h-3.5 text-emerald-600" />
                                    <ClipboardDocumentIcon v-else class="w-3.5 h-3.5" />
                                </button>
                            </div>
                        </div>

                        <div class="bg-slate-50 p-1.5 rounded border border-slate-200/70 flex items-center justify-between">
                            <span class="text-slate-500">cURL:</span>
                            <div class="flex items-center gap-1">
                                <span class="font-bold text-slate-900">{{ systemPaths.curl_binary }}</span>
                                <button 
                                    @click="copyToClipboard(systemPaths.curl_binary, 'curl_bin')"
                                    class="text-slate-400 hover:text-blue-600 cursor-pointer ml-1"
                                    title="Copy cURL path"
                                >
                                    <CheckIcon v-if="copiedField === 'curl_bin'" class="w-3.5 h-3.5 text-emerald-600" />
                                    <ClipboardDocumentIcon v-else class="w-3.5 h-3.5" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="text-[10.5px] text-slate-500 pt-1 border-t border-slate-100 flex items-center gap-1">
                        <InformationCircleIcon class="w-3.5 h-3.5 text-blue-600 shrink-0" />
                        <span>Use absolute paths in cron commands</span>
                    </div>
                </div>

                <!-- Card 3: Execution Engine & Environment -->
                <div class="bg-gradient-to-br from-indigo-50/70 to-blue-50/50 border border-indigo-100 rounded-lg p-4 shadow-2xs flex flex-col justify-between space-y-2.5">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-indigo-900 flex items-center gap-1.5">
                                <CommandLineIcon class="w-4 h-4 text-indigo-600" />
                                <span>Cron Engine & Environment</span>
                            </span>
                            <span class="text-[10px] font-bold px-1.5 py-0.5 bg-indigo-100 text-indigo-800 rounded font-mono">
                                VIRTUAL CRON
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-600 mt-1 line-clamp-2">
                            Commands run in background subshells with automated exit code logging and error trapping.
                        </p>
                    </div>

                    <div class="pt-2 border-t border-indigo-100 flex items-center justify-between text-[11px] text-indigo-900 font-mono">
                        <span>Working Dir:</span>
                        <span class="font-bold truncate max-w-xs">~/public_html</span>
                    </div>
                </div>

            </div>

            <!-- 3. Navigation Tabs -->
            <div class="border-b border-slate-200 flex items-center gap-4 text-xs font-bold">
                <button 
                    @click="activeTab = 'jobs'"
                    class="pb-2.5 border-b-2 transition cursor-pointer flex items-center gap-1.5"
                    :class="activeTab === 'jobs' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-800'"
                >
                    <ClockIcon class="w-4 h-4" />
                    <span>Scheduled Jobs ({{ jobs.length }})</span>
                </button>

                <button 
                    @click="activeTab = 'logs'"
                    class="pb-2.5 border-b-2 transition cursor-pointer flex items-center gap-1.5"
                    :class="activeTab === 'logs' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-800'"
                >
                    <DocumentTextIcon class="w-4 h-4" />
                    <span>Execution Logs ({{ recentLogs.length }})</span>
                </button>

                <button 
                    @click="activeTab = 'templates'"
                    class="pb-2.5 border-b-2 transition cursor-pointer flex items-center gap-1.5"
                    :class="activeTab === 'templates' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-800'"
                >
                    <SparklesIcon class="w-4 h-4" />
                    <span>Common Templates & Presets</span>
                </button>
            </div>

            <!-- 4. TAB 1: Scheduled Jobs Table -->
            <div v-if="activeTab === 'jobs'" class="bg-white border border-slate-200 rounded-lg shadow-2xs overflow-hidden">
                
                <div class="px-4 py-3 bg-slate-50/80 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <ClockIcon class="w-4 h-4 text-indigo-600" />
                        <span class="text-xs font-bold text-slate-900">Active Scheduled Commands</span>
                        <span class="text-[11px] text-slate-400 font-mono">({{ filteredJobs.length }})</span>
                    </div>

                    <div class="relative w-full sm:w-64">
                        <MagnifyingGlassIcon class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2" />
                        <input 
                            v-model="searchQuery"
                            type="text" 
                            placeholder="Search cron jobs..." 
                            class="w-full pl-8 pr-2.5 py-1.5 bg-white border border-slate-300 rounded-[3px] text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-blue-500 font-medium shadow-2xs"
                        />
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="filteredJobs.length === 0" class="p-12 text-center text-slate-500 text-xs">
                    <ClockIcon class="w-12 h-12 text-slate-300 mx-auto mb-2" />
                    <h4 class="text-sm font-bold text-slate-900 mb-1">
                        {{ searchQuery ? 'No cron jobs match your search' : 'No Scheduled Cron Jobs' }}
                    </h4>
                    <p class="text-[11px] text-slate-400 max-w-sm mx-auto mb-4">
                        {{ searchQuery ? 'Try clearing your search query.' : 'Automate recurrent tasks, queues, or WordPress/Laravel schedulers.' }}
                    </p>
                    <div class="flex items-center justify-center gap-2">
                        <button 
                            @click="activeTab = 'templates'"
                            class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-[3px] text-xs font-bold shadow-2xs transition cursor-pointer"
                        >
                            Browse Templates
                        </button>
                        <button 
                            @click="openCreateModal"
                            class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold shadow-2xs transition cursor-pointer"
                        >
                            Create Cron Job
                        </button>
                    </div>
                </div>

                <!-- Table Content -->
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-700">
                        <thead class="bg-slate-50/90 border-b border-slate-200 text-slate-500 uppercase font-bold tracking-wider text-[10.5px] select-none">
                            <tr>
                                <th class="py-2.5 px-4">Job Title</th>
                                <th class="py-2.5 px-4 w-48">Schedule Interval</th>
                                <th class="py-2.5 px-4">Command</th>
                                <th class="py-2.5 px-4 w-28">Last Run</th>
                                <th class="py-2.5 px-4 w-24">Status</th>
                                <th class="py-2.5 px-4 w-36 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
                            <tr v-for="job in filteredJobs" :key="job.id" class="hover:bg-slate-50/80 transition-colors">
                                
                                <!-- Title -->
                                <td class="py-3 px-4 font-bold text-slate-900">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded bg-indigo-50 border border-indigo-200 text-indigo-600 flex items-center justify-center shrink-0">
                                            <ClockIcon class="w-3.5 h-3.5" />
                                        </div>
                                        <div>
                                            <span>{{ job.title }}</span>
                                            <span v-if="job.logs_count > 0" class="block text-[10px] text-slate-400 font-normal">
                                                {{ job.logs_count }} execution logs
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Schedule -->
                                <td class="py-3 px-4">
                                    <span class="font-bold text-slate-800 text-[11.5px] block">{{ job.schedule_human }}</span>
                                    <span class="px-1.5 py-0.2 bg-slate-100 rounded text-[10px] font-mono text-slate-600 border border-slate-200 mt-0.5 inline-block">
                                        {{ job.cron_expression }}
                                    </span>
                                </td>

                                <!-- Command -->
                                <td class="py-3 px-4 font-mono text-[11px] text-slate-700">
                                    <div class="flex items-center gap-1.5 max-w-md truncate">
                                        <span class="truncate">{{ job.command }}</span>
                                        <button 
                                            @click="copyToClipboard(job.command, 'cmd_' + job.id)"
                                            class="text-slate-400 hover:text-blue-600 cursor-pointer shrink-0"
                                            title="Copy Command"
                                        >
                                            <CheckIcon v-if="copiedField === 'cmd_' + job.id" class="w-3.5 h-3.5 text-emerald-600" />
                                            <ClipboardDocumentIcon v-else class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </td>

                                <!-- Last Run -->
                                <td class="py-3 px-4 text-[11px]">
                                    <div class="flex items-center gap-1.5">
                                        <span 
                                            class="w-2 h-2 rounded-full shrink-0"
                                            :class="job.last_run_status === 'success' ? 'bg-emerald-500' : (job.last_run_status === 'failed' ? 'bg-rose-500' : 'bg-slate-300')"
                                        ></span>
                                        <span class="text-slate-600">{{ job.last_run_at }}</span>
                                    </div>
                                    <span v-if="job.last_run_duration_ms" class="text-[10px] font-mono text-slate-400 block ml-3.5">
                                        {{ job.last_run_duration_ms }}ms
                                    </span>
                                </td>

                                <!-- Status -->
                                <td class="py-3 px-4">
                                    <button 
                                        @click="toggleJob(job)"
                                        class="px-2 py-0.5 rounded text-[10px] font-bold border uppercase font-mono cursor-pointer transition"
                                        :class="job.is_enabled ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' : 'bg-slate-100 text-slate-600 border-slate-200 hover:bg-slate-200'"
                                    >
                                        ● {{ job.is_enabled ? 'ACTIVE' : 'PAUSED' }}
                                    </button>
                                </td>

                                <!-- Actions -->
                                <td class="py-3 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        
                                        <!-- Run Now Button -->
                                        <button 
                                            @click="runJobNow(job)"
                                            :disabled="runningJobId === job.id"
                                            title="Run Now Manually"
                                            class="p-1.5 bg-white hover:bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-[3px] shadow-2xs transition cursor-pointer disabled:opacity-50"
                                        >
                                            <PlayIcon class="w-3.5 h-3.5" :class="{ 'animate-spin': runningJobId === job.id }" />
                                        </button>

                                        <!-- Edit Job -->
                                        <button 
                                            @click="openEditModal(job)"
                                            title="Edit Cron Job"
                                            class="p-1.5 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-[3px] shadow-2xs transition cursor-pointer"
                                        >
                                            <PencilSquareIcon class="w-3.5 h-3.5 text-blue-600" />
                                        </button>

                                        <!-- Delete Job -->
                                        <button 
                                            @click="confirmDelete(job)"
                                            title="Delete Cron Job"
                                            class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded transition cursor-pointer"
                                        >
                                            <TrashIcon class="w-4 h-4" />
                                        </button>

                                    </div>
                                </td>

                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="px-4 py-2.5 bg-slate-50 border-t border-slate-200 text-[11px] text-slate-500 flex items-center justify-between">
                    <span>Linux Vixie-cron / systemd-cron Scheduler.</span>
                    <span class="font-mono">Timezone: UTC • Next execution in queue</span>
                </div>

            </div>

            <!-- 5. TAB 2: Execution Logs History -->
            <div v-else-if="activeTab === 'logs'" class="bg-white border border-slate-200 rounded-lg shadow-2xs overflow-hidden">
                
                <div class="px-4 py-3 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <DocumentTextIcon class="w-4 h-4 text-indigo-600" />
                        <span class="text-xs font-bold text-slate-900">Execution History Logs</span>
                        <span class="text-[11px] text-slate-400 font-mono">({{ recentLogs.length }} recorded)</span>
                    </div>

                    <button 
                        v-if="recentLogs.length > 0"
                        @click="clearLogs"
                        class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-[3px] text-xs font-bold transition cursor-pointer"
                    >
                        Clear History
                    </button>
                </div>

                <!-- Empty State -->
                <div v-if="recentLogs.length === 0" class="p-12 text-center text-slate-500 text-xs">
                    <DocumentTextIcon class="w-12 h-12 text-slate-300 mx-auto mb-2" />
                    <h4 class="text-sm font-bold text-slate-900 mb-1">No Execution Logs Recorded</h4>
                    <p class="text-[11px] text-slate-400 max-w-sm mx-auto">
                        When scheduled jobs execute or when you click "Run Now", execution logs will appear here.
                    </p>
                </div>

                <!-- Table Content -->
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-700">
                        <thead class="bg-slate-50/90 border-b border-slate-200 text-slate-500 uppercase font-bold tracking-wider text-[10.5px]">
                            <tr>
                                <th class="py-2.5 px-4 w-40">Timestamp</th>
                                <th class="py-2.5 px-4 w-48">Job Title</th>
                                <th class="py-2.5 px-4 w-28">Status</th>
                                <th class="py-2.5 px-4 w-28">Duration</th>
                                <th class="py-2.5 px-4">Output Preview</th>
                                <th class="py-2.5 px-4 w-20 text-right">View</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
                            <tr v-for="log in recentLogs" :key="log.id" class="hover:bg-slate-50/80 transition-colors">
                                
                                <td class="py-2.5 px-4 font-mono text-[11px] text-slate-600">
                                    {{ log.executed_at }}
                                </td>

                                <td class="py-2.5 px-4 font-bold text-slate-900">
                                    {{ log.job_title }}
                                </td>

                                <td class="py-2.5 px-4">
                                    <span 
                                        class="px-2 py-0.5 rounded text-[10px] font-bold border uppercase font-mono"
                                        :class="log.status === 'success' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
                                    >
                                        {{ log.status }} (Exit: {{ log.exit_code }})
                                    </span>
                                </td>

                                <td class="py-2.5 px-4 font-mono text-slate-600">
                                    {{ log.duration_ms }} ms
                                </td>

                                <td class="py-2.5 px-4 font-mono text-[11px] text-slate-500 max-w-md truncate">
                                    {{ log.output || '(No output)' }}
                                </td>

                                <td class="py-2.5 px-4 text-right">
                                    <button 
                                        @click="viewingLog = log; showLogModal = true"
                                        class="px-2 py-1 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-[3px] text-[11px] font-bold transition cursor-pointer"
                                    >
                                        Details
                                    </button>
                                </td>

                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>

            <!-- 6. TAB 3: Common Templates & Presets -->
            <div v-else-if="activeTab === 'templates'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                
                <div 
                    v-for="tpl in commandTemplates" 
                    :key="tpl.title"
                    class="bg-white border border-slate-200 rounded-lg p-5 shadow-2xs space-y-3.5 flex flex-col justify-between"
                >
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
                                <SparklesIcon class="w-4 h-4 text-indigo-600" />
                                <span>{{ tpl.title }}</span>
                            </h3>
                            <span class="px-2 py-0.5 bg-slate-100 rounded text-[10.5px] font-mono text-slate-700 border border-slate-200 font-bold">
                                {{ tpl.cron_expression }}
                            </span>
                        </div>

                        <p class="text-xs text-slate-500 leading-relaxed">
                            {{ tpl.description }}
                        </p>

                        <div class="bg-slate-900 text-slate-100 p-2.5 rounded-[3px] font-mono text-[11px] overflow-x-auto">
                            <code>{{ tpl.command }}</code>
                        </div>
                    </div>

                    <div class="flex items-center justify-end pt-2 border-t border-slate-100">
                        <button 
                            @click="useTemplate(tpl)"
                            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold shadow-2xs transition cursor-pointer flex items-center gap-1.5"
                        >
                            <PlusIcon class="w-3.5 h-3.5 stroke-[2.5]" />
                            <span>Use This Template</span>
                        </button>
                    </div>
                </div>

            </div>

        </div>

        <!-- ========================================================= -->
        <!-- MODALS                                                    -->
        <!-- ========================================================= -->
        <Teleport to="body">
            
            <!-- 1. Create / Edit Cron Job Modal -->
            <div v-if="showCreateModal || showEditModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
                <div class="bg-white rounded-lg max-w-xl w-full p-5 shadow-2xl space-y-4 border border-slate-200">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2">
                            <ClockIcon class="w-5 h-5 text-blue-600" />
                            <h3 class="font-bold text-slate-900 text-sm">
                                {{ showEditModal ? 'Edit Scheduled Cron Job' : 'Create New Scheduled Cron Job' }}
                            </h3>
                        </div>
                        <button @click="showCreateModal = false; showEditModal = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                            <XMarkIcon class="w-4 h-4" />
                        </button>
                    </div>

                    <form @submit.prevent="showEditModal ? submitEditJob() : submitCreateJob()" class="space-y-3.5 text-xs">
                        
                        <!-- Job Title -->
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Job Title / Description</label>
                            <input 
                                v-model="jobForm.title" 
                                type="text" 
                                required 
                                placeholder="e.g. Laravel Schedule Worker, Database Backup, Cache Purge" 
                                class="w-full bg-white border border-slate-300 rounded-[3px] px-2.5 py-2 text-xs font-bold text-slate-900 focus:ring-1 focus:ring-blue-600" 
                            />
                        </div>

                        <!-- Schedule Interval Presets & Custom -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700">Schedule Preset</label>
                                <select 
                                    v-model="jobForm.preset"
                                    @change="onPresetChange"
                                    class="w-full bg-white border border-slate-300 rounded-[3px] px-2.5 py-2 text-xs font-bold text-slate-900 focus:ring-1 focus:ring-blue-600 cursor-pointer"
                                >
                                    <option v-for="p in schedulePresets" :key="p.value" :value="p.value">
                                        {{ p.label }}
                                    </option>
                                </select>
                            </div>

                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700">Cron Expression (m h d m w)</label>
                                <input 
                                    v-model="jobForm.cron_expression" 
                                    type="text" 
                                    required 
                                    placeholder="* * * * *" 
                                    class="w-full bg-white border border-slate-300 rounded-[3px] px-2.5 py-2 text-xs font-mono font-bold text-slate-900 focus:ring-1 focus:ring-blue-600" 
                                />
                            </div>
                        </div>

                        <!-- Command Input -->
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Command to Execute</label>
                            <textarea 
                                v-model="jobForm.command"
                                rows="3"
                                required
                                placeholder="/usr/bin/php /var/www/vhosts/.../artisan schedule:run"
                                class="w-full bg-slate-50 border border-slate-300 rounded-[3px] p-2.5 text-xs font-mono text-slate-900 focus:ring-1 focus:ring-blue-600 outline-none leading-relaxed"
                            ></textarea>
                            <p class="text-[10.5px] text-slate-400">Use absolute binary paths such as <code>/usr/bin/php</code> or <code>/usr/bin/curl</code>.</p>
                        </div>

                        <!-- Output Handling -->
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Output Handling</label>
                            <div class="grid grid-cols-3 gap-2 text-xs">
                                <label 
                                    class="p-2 rounded border flex flex-col gap-0.5 cursor-pointer"
                                    :class="jobForm.output_handling === 'discard' ? 'bg-blue-50 border-blue-300 text-blue-900 font-bold' : 'bg-white border-slate-200 text-slate-600'"
                                >
                                    <input type="radio" v-model="jobForm.output_handling" value="discard" class="sr-only" />
                                    <span>Discard Output</span>
                                    <span class="text-[10px] font-normal text-slate-400">>/dev/null 2>&1</span>
                                </label>

                                <label 
                                    class="p-2 rounded border flex flex-col gap-0.5 cursor-pointer"
                                    :class="jobForm.output_handling === 'log' ? 'bg-blue-50 border-blue-300 text-blue-900 font-bold' : 'bg-white border-slate-200 text-slate-600'"
                                >
                                    <input type="radio" v-model="jobForm.output_handling" value="log" class="sr-only" />
                                    <span>Save to Log File</span>
                                    <span class="text-[10px] font-normal text-slate-400">>> cron.log 2>&1</span>
                                </label>

                                <label 
                                    class="p-2 rounded border flex flex-col gap-0.5 cursor-pointer"
                                    :class="jobForm.output_handling === 'email' ? 'bg-blue-50 border-blue-300 text-blue-900 font-bold' : 'bg-white border-slate-200 text-slate-600'"
                                >
                                    <input type="radio" v-model="jobForm.output_handling" value="email" class="sr-only" />
                                    <span>Email on Error</span>
                                    <span class="text-[10px] font-normal text-slate-400">Exit code != 0</span>
                                </label>
                            </div>
                        </div>

                        <!-- Enable / Disable Switch -->
                        <div class="pt-1">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input 
                                    v-model="jobForm.is_enabled" 
                                    type="checkbox" 
                                    class="w-4 h-4 text-blue-600 rounded-[2px] border-slate-300"
                                />
                                <span class="font-bold text-slate-700">Enable this cron job immediately upon saving</span>
                            </label>
                        </div>

                        <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                            <button 
                                type="button" 
                                @click="showCreateModal = false; showEditModal = false" 
                                class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-[3px] text-xs font-bold cursor-pointer"
                            >
                                Cancel
                            </button>
                            <button 
                                type="submit" 
                                :disabled="jobForm.processing"
                                class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold shadow-2xs transition cursor-pointer disabled:opacity-50 flex items-center gap-1"
                            >
                                <span>{{ jobForm.processing ? 'Saving...' : (showEditModal ? 'Update Cron Job' : 'Create Cron Job') }}</span>
                            </button>
                        </div>

                    </form>
                </div>
            </div>

            <!-- 2. Delete Confirmation Modal -->
            <div v-if="showDeleteModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
                <div class="bg-white rounded-lg max-w-md w-full p-5 shadow-2xl border border-slate-200 space-y-4">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-lg bg-rose-50 border border-rose-100 text-rose-600 flex items-center justify-center shrink-0">
                            <TrashIcon class="w-5 h-5 stroke-[1.75]" />
                        </div>
                        <div class="flex-1">
                            <h3 class="text-sm font-black text-slate-900">Delete Cron Job?</h3>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Are you sure you want to delete <strong class="text-slate-900">{{ deletingJob?.title }}</strong>? Scheduled background execution of this command will be terminated immediately.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button 
                            @click="showDeleteModal = false; deletingJob = null" 
                            type="button" 
                            class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-[3px] text-xs font-bold transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button 
                            @click="executeDelete" 
                            type="button"
                            class="px-4 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-[3px] text-xs font-bold shadow-2xs transition cursor-pointer flex items-center gap-1.5"
                        >
                            <TrashIcon class="w-3.5 h-3.5" />
                            <span>Delete Job</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- 3. View Log Output Modal -->
            <div v-if="showLogModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
                <div class="bg-white rounded-lg max-w-xl w-full p-5 shadow-2xl space-y-4 border border-slate-200">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2">
                            <DocumentTextIcon class="w-5 h-5 text-indigo-600" />
                            <h3 class="font-bold text-slate-900 text-sm">Log Details: {{ viewingLog?.job_title }}</h3>
                        </div>
                        <button @click="showLogModal = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                            <XMarkIcon class="w-4 h-4" />
                        </button>
                    </div>

                    <div class="space-y-3 text-xs">
                        <div class="flex items-center justify-between bg-slate-50 p-2.5 rounded border border-slate-200/70 font-mono">
                            <span>Executed: {{ viewingLog?.executed_at }}</span>
                            <span 
                                class="font-bold px-2 py-0.5 rounded"
                                :class="viewingLog?.status === 'success' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
                            >
                                {{ viewingLog?.status }} (Exit: {{ viewingLog?.exit_code }}) • {{ viewingLog?.duration_ms }}ms
                            </span>
                        </div>

                        <div class="bg-slate-900 text-slate-100 p-3.5 rounded-[3px] font-mono text-[11px] overflow-x-auto leading-relaxed max-h-64 whitespace-pre-wrap">
                            {{ viewingLog?.output || '(No console output generated)' }}
                        </div>
                    </div>

                    <div class="flex justify-end pt-2 border-t border-slate-100">
                        <button 
                            type="button" 
                            @click="showLogModal = false" 
                            class="px-4 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-[3px] text-xs font-bold cursor-pointer"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>

        </Teleport>

    </AuthenticatedLayout>
</template>
