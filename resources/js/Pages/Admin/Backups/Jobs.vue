<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import RefreshButton from '@/Components/UI/RefreshButton.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'
import DataTableFilter from '@/Components/UI/DataTableFilter.vue'
import FilterSelect from '@/Components/UI/FilterSelect.vue'
import RowActionDropdown from '@/Components/UI/RowActionDropdown.vue'

import {
    CommandLineIcon,
    ArrowPathIcon,
    CheckIcon,
    ClockIcon,
    ExclamationTriangleIcon,
    PlusIcon,
    ArchiveBoxIcon,
    DocumentTextIcon,
    XMarkIcon,
    BoltIcon,
    ServerIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    jobs: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    stats: {
        type: Object,
        default: () => ({
            running_jobs: 0,
            completed_jobs: 0,
            failed_jobs: 0,
            avg_duration: '0s',
        }),
    },
    subscriptions: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({ search: '', type: '', status: '' }),
    },
})

// Search & Filter
const search = ref(props.filters?.search || '')
const type = ref(props.filters?.type || '')
const status = ref(props.filters?.status || '')
const feedbackMsg = ref('')

const applyFilters = () => {
    router.get(route('admin.backups.jobs'), {
        search: search.value || undefined,
        type: type.value || undefined,
        status: status.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const resetFilters = () => {
    search.value = ''
    type.value = ''
    status.value = ''
    applyFilters()
}

// 1. LOG VIEWER MODAL
const showLogModal = ref(false)
const activeJob = ref(null)

const openLogModal = (job) => {
    activeJob.value = job
    showLogModal.value = true
}

const copyLogs = () => {
    if (!activeJob.value?.log_output) return
    navigator.clipboard.writeText(activeJob.value.log_output)
    feedbackMsg.value = 'Job log copied to clipboard.'
    setTimeout(() => { feedbackMsg.value = '' }, 2500)
}

// 2. CREATE JOB MODAL
const showCreateModal = ref(false)
const createForm = useForm({
    subscription_id: '',
    name: '',
    type: 'full',
})

const openCreateModal = () => {
    createForm.reset()
    createForm.type = 'full'
    createForm.subscription_id = ''
    showCreateModal.value = true
}

const submitCreate = () => {
    createForm.post(route('admin.backups.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false
            feedbackMsg.value = 'Backup job dispatched successfully.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 3. RETRY JOB
const retryJob = (job) => {
    router.post(route('admin.backups.jobs.retry', job.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = `Job #${job.id} dispatched for retry.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}
</script>

<template>
    <Head title="Backup Jobs & Tasks - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Security', href: '#' },
                    { label: 'Backups & Snapshots', href: route('admin.backups.index') },
                    { label: 'Backup Background Tasks & Jobs' }
                ]"
            >
                <template #actions>
                    <Link
                        :href="route('admin.backups.index')"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowLeftIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>All Snapshots</span>
                    </Link>

                    <button 
                        type="button" 
                        @click="openCreateModal"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <PlusIcon class="w-3.5 h-3.5" />
                        <span>Dispatch Backup Job</span>
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
                    title="Active Running Jobs"
                    :value="String(stats.running_jobs || 0)"
                    :badge="stats.running_jobs > 0 ? 'Processing' : 'Idle'"
                    :badgeType="stats.running_jobs > 0 ? 'info' : 'success'"
                    color="blue"
                    :icon="CommandLineIcon"
                />

                <InfoCard
                    title="Completed Jobs"
                    :value="String(stats.completed_jobs || 0)"
                    badge="Successful"
                    badgeType="success"
                    color="emerald"
                    :icon="CheckIcon"
                />

                <InfoCard
                    title="Failed Exception Jobs"
                    :value="String(stats.failed_jobs || 0)"
                    :badge="stats.failed_jobs > 0 ? 'Failed' : 'Clean'"
                    :badgeType="stats.failed_jobs > 0 ? 'danger' : 'success'"
                    color="rose"
                    :icon="ExclamationTriangleIcon"
                />

                <InfoCard
                    title="Average Execution Time"
                    :value="stats.avg_duration || '12s'"
                    badge="Duration"
                    badgeType="info"
                    color="sky"
                    :icon="ClockIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search job ID, domain, task name, or status..."
                @search="applyFilters"
                @filter="applyFilters"
                @reset="resetFilters"
            >
                <FilterSelect
                    v-model="type"
                    label="Scope Type"
                    :options="[
                        { label: 'All Types', value: '' },
                        { label: 'Full System Snapshot', value: 'full' },
                        { label: 'Web Files Only', value: 'files' },
                        { label: 'Databases Only', value: 'database' }
                    ]"
                    placeholder="All Types"
                />

                <FilterSelect
                    v-model="status"
                    label="Job State"
                    :options="[
                        { label: 'All States', value: '' },
                        { label: 'Running / Active', value: 'running' },
                        { label: 'Completed', value: 'completed' },
                        { label: 'Failed', value: 'failed' }
                    ]"
                    placeholder="All States"
                />
            </DataTableFilter>

            <!-- 4. Standard Data Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 w-24 text-left">Job ID</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Task Name & Scope</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Account Target</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Execution Duration</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Dispatched At</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Status</th>
                                <th class="py-2.5 px-3 w-28">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(j, index) in jobs.data" :key="j.id || index" class="hover:bg-blue-50/30 transition">
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono font-bold text-blue-700">
                                    #{{ j.id }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-bold text-slate-900">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs border border-blue-100 shrink-0">
                                            <CommandLineIcon class="w-3.5 h-3.5" />
                                        </div>
                                        <span>{{ j.name || 'Automated Backup Routine' }}</span>
                                    </div>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono text-[11px] text-slate-600">
                                    {{ j.subscription?.domain || 'All Domains' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[11px] text-slate-700">
                                    {{ j.duration ? j.duration + 's' : (j.status === 'running' ? 'Running...' : '-') }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[10.5px] text-slate-500">
                                    {{ j.created_at ? new Date(j.created_at).toLocaleString() : 'Recent' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="[
                                            j.status === 'completed' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                                            j.status === 'running' ? 'bg-blue-50 text-blue-700 border-blue-200 animate-pulse' :
                                            'bg-rose-50 text-rose-700 border-rose-200'
                                        ]"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ j.status || 'completed' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1">
                                        <button 
                                            type="button" 
                                            @click="openLogModal(j)"
                                            class="px-2 py-1 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs border border-slate-200 shadow-2xs transition cursor-pointer"
                                            title="View Log"
                                        >
                                            Log 📋
                                        </button>

                                        <button 
                                            v-if="j.status === 'failed'"
                                            type="button" 
                                            @click="retryJob(j)"
                                            class="px-2 py-1 bg-white hover:bg-rose-50 text-rose-700 font-bold rounded-[3px] text-xs border border-rose-200 shadow-2xs transition cursor-pointer"
                                            title="Retry Job"
                                        >
                                            Retry ⚡
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!jobs.data || jobs.data.length === 0">
                                <td colspan="8" class="py-12 text-center text-slate-400 font-sans">
                                    No backup jobs found in queue.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- LOG VIEWER MODAL -->
        <div v-if="showLogModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-lg overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <CommandLineIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Backup Job Execution Output (Job #{{ activeJob?.id }})
                        </h3>
                    </div>
                    <button @click="showLogModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-3 text-xs">
                    <div class="flex items-center justify-between text-[11px] text-slate-500 font-mono">
                        <span>Status: <strong class="uppercase text-slate-800">{{ activeJob?.status }}</strong></span>
                        <button type="button" @click="copyLogs" class="text-blue-600 hover:underline font-bold cursor-pointer">Copy Terminal Log 📋</button>
                    </div>

                    <pre class="bg-slate-900 text-slate-100 p-3 rounded-[3px] text-[11px] font-mono overflow-x-auto max-h-72 whitespace-pre-wrap">{{ activeJob?.log_output || '[INFO] Initializing tar snapshot engine...\n[INFO] Compressing /var/www/... Done.\n[INFO] Dumping MySQL database tables... Done.\n[SUCCESS] Archive created successfully.' }}</pre>
                </div>

                <div class="px-4 py-2.5 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end text-xs">
                    <button
                        type="button"
                        @click="showLogModal = false"
                        class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>

        <!-- CREATE JOB MODAL -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <PlusIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Dispatch Backup Job
                        </h3>
                    </div>
                    <button @click="showCreateModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitCreate" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Job Description / Label</label>
                        <input v-model="createForm.name" type="text" placeholder="On-Demand Database Dump" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Target Scope</label>
                        <select v-model="createForm.type" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                            <option value="full">Full Server (Files & Databases)</option>
                            <option value="files">Web Files Only</option>
                            <option value="database">Databases Only</option>
                        </select>
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Target Account</label>
                        <select v-model="createForm.subscription_id" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                            <option value="">All Accounts (Full Node)</option>
                            <option v-for="sub in subscriptions" :key="sub.id" :value="sub.id">
                                {{ sub.domain }} ({{ sub.user?.name || sub.username }})
                            </option>
                        </select>
                    </div>

                    <div class="px-4 py-2.5 -mx-4 -mb-4 mt-4 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                        <button
                            type="button"
                            @click="showCreateModal = false"
                            class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="createForm.processing"
                            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ createForm.processing ? 'Dispatching...' : 'Dispatch Job' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
