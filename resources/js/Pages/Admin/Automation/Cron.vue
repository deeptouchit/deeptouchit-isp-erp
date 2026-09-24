<script setup>
import { ref, computed } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import RefreshButton from '@/Components/UI/RefreshButton.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'
import DataTableFilter from '@/Components/UI/DataTableFilter.vue'
import RowActionDropdown from '@/Components/UI/RowActionDropdown.vue'

import {
    ClockIcon,
    CommandLineIcon,
    PlayIcon,
    PlusIcon,
    PencilSquareIcon,
    TrashIcon,
    DocumentTextIcon,
    ArrowPathIcon,
    XMarkIcon,
    CheckIcon,
    BoltIcon,
    ShieldCheckIcon,
    ServerIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({}),
    },
    jobs: {
        type: Array,
        default: () => [],
    },
    system_crons: {
        type: Array,
        default: () => [],
    },
})

// Search & Feedback State
const search = ref('')
const feedbackMsg = ref('')

const filteredJobs = computed(() => {
    let list = props.jobs || []
    if (search.value.trim()) {
        const q = search.value.toLowerCase()
        list = list.filter(j =>
            (j.command || '').toLowerCase().includes(q) ||
            (j.description || '').toLowerCase().includes(q) ||
            (j.user || '').toLowerCase().includes(q)
        )
    }
    return list
})

// 1. CREATE / EDIT MODAL
const showModal = ref(false)
const editingJob = ref(null)
const jobForm = useForm({
    command: '',
    expression: '0 0 * * *',
    user: 'root',
    description: '',
})

const openCreateModal = () => {
    editingJob.value = null
    jobForm.reset()
    jobForm.expression = '0 0 * * *'
    jobForm.user = 'root'
    showModal.value = true
}

const openEditModal = (job) => {
    editingJob.value = job
    jobForm.command = job.command
    jobForm.expression = job.expression
    jobForm.user = job.user || 'root'
    jobForm.description = job.description || ''
    showModal.value = true
}

const applyPreset = (expr) => {
    jobForm.expression = expr
}

const submitJob = () => {
    if (editingJob.value) {
        jobForm.put(route('admin.automation.cron.update', editingJob.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false
                feedbackMsg.value = 'Cron job updated.'
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    } else {
        jobForm.post(route('admin.automation.cron.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false
                feedbackMsg.value = 'New cron job scheduled.'
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    }
}

// 2. RUN CRON NOW
const runCronNow = (job) => {
    router.post(route('admin.automation.cron.run', job.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = `Cron job executed successfully.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 3. TOGGLE CRON
const toggleJob = (job) => {
    router.post(route('admin.automation.cron.toggle', job.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = `Cron status updated.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 4. DELETE CRON
const showDeleteModal = ref(false)
const jobToDelete = ref(null)
const deleteForm = useForm({})

const openDeleteModal = (job) => {
    jobToDelete.value = job
    showDeleteModal.value = true
}

const submitDelete = () => {
    if (!jobToDelete.value) return
    deleteForm.delete(route('admin.automation.cron.destroy', jobToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false
            feedbackMsg.value = 'Cron job removed.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}
</script>

<template>
    <Head title="Cron Jobs & Automation - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Automation', href: '#' },
                    { label: 'Automation & Schedulers', href: route('admin.automation.cron') },
                    { label: 'Cron Jobs & Background Schedulers' }
                ]"
            >
                <template #actions>
                    <Link
                        :href="route('admin.automation.scheduled-tasks')"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowLeftIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Scheduled Tasks</span>
                    </Link>

                    <button 
                        type="button" 
                        @click="openCreateModal"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <PlusIcon class="w-3.5 h-3.5" />
                        <span>Create Cron Job</span>
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
                    title="Configured Cron Jobs"
                    :value="String(jobs.length || stats.total_jobs || 0)"
                    badge="Crons"
                    badgeType="info"
                    color="blue"
                    :icon="ClockIcon"
                />

                <InfoCard
                    title="Active & Scheduled"
                    :value="String(jobs.filter(j => j.status === 'active' || j.is_active).length || stats.active_jobs || 0)"
                    badge="Active"
                    badgeType="success"
                    color="emerald"
                    :icon="BoltIcon"
                />

                <InfoCard
                    title="System Core Crons"
                    :value="String(system_crons.length || 6)"
                    badge="Systemd"
                    badgeType="info"
                    color="purple"
                    :icon="ServerIcon"
                />

                <InfoCard
                    title="Executed in 24h"
                    :value="String(stats.executed_today || '1.4k')"
                    badge="Completed"
                    badgeType="success"
                    color="sky"
                    :icon="ShieldCheckIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search cron command, description, or system user..."
                @search="() => {}"
                @filter="() => {}"
                @reset="search = ''"
            />

            <!-- 4. Standard Data Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Cronjob Command Payload</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 w-32">Schedule Expression</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">User</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Last Execution</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Status</th>
                                <th class="py-2.5 px-3 w-28">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(job, index) in filteredJobs" :key="job.id || index" class="hover:bg-blue-50/30 transition">
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono font-bold text-slate-900 max-w-md truncate" :title="job.command">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs border border-blue-100 shrink-0">
                                            <CommandLineIcon class="w-3.5 h-3.5" />
                                        </div>
                                        <span>{{ job.command }}</span>
                                    </div>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono font-bold text-blue-700 text-[11px]">
                                    {{ job.expression }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono text-[11px] text-slate-600">
                                    @{{ job.user || 'root' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[10.5px] text-slate-500">
                                    {{ job.last_run ? new Date(job.last_run).toLocaleTimeString() : 'Pending' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="job.status === 'active' || job.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-500 border-slate-200'"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ job.status || (job.is_active ? 'active' : 'paused') }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1">
                                        <button 
                                            type="button" 
                                            @click="runCronNow(job)"
                                            class="px-2 py-1 bg-white hover:bg-slate-50 text-blue-700 font-bold rounded-[3px] text-xs border border-slate-200 shadow-2xs transition cursor-pointer"
                                            title="Run Now"
                                        >
                                            Run ⚡
                                        </button>

                                        <RowActionDropdown>
                                            <button
                                                type="button"
                                                @click="openEditModal(job)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <PencilSquareIcon class="w-3.5 h-3.5 text-slate-500" />
                                                <span>Edit Cron Job</span>
                                            </button>

                                            <button
                                                type="button"
                                                @click="toggleJob(job)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <BoltIcon class="w-3.5 h-3.5 text-slate-500" />
                                                <span>{{ job.status === 'active' || job.is_active ? 'Pause Cron' : 'Activate Cron' }}</span>
                                            </button>

                                            <button
                                                type="button"
                                                @click="openDeleteModal(job)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-rose-600 hover:bg-rose-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <TrashIcon class="w-3.5 h-3.5" />
                                                <span>Delete Cron Job</span>
                                            </button>
                                        </RowActionDropdown>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!filteredJobs || filteredJobs.length === 0">
                                <td colspan="7" class="py-12 text-center text-slate-400 font-sans">
                                    No custom cron jobs configured.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- CREATE / EDIT MODAL -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <ClockIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            {{ editingJob ? 'Edit Cron Job' : 'Create Cron Job' }}
                        </h3>
                    </div>
                    <button @click="showModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitJob" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Command to Execute <span class="text-rose-500">*</span></label>
                        <input v-model="jobForm.command" type="text" required placeholder="php /var/www/site/artisan schedule:run" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Schedule Expression <span class="text-rose-500">*</span></label>
                        <input v-model="jobForm.expression" type="text" required placeholder="0 0 * * *" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        
                        <!-- Expression Presets -->
                        <div class="flex items-center gap-1.5 pt-1">
                            <span class="text-[10.5px] text-slate-400">Presets:</span>
                            <button type="button" @click="applyPreset('* * * * *')" class="px-2 py-0.5 rounded-[2px] bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10.5px] font-bold cursor-pointer">Every Min</button>
                            <button type="button" @click="applyPreset('0 * * * *')" class="px-2 py-0.5 rounded-[2px] bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10.5px] font-bold cursor-pointer">Hourly</button>
                            <button type="button" @click="applyPreset('0 0 * * *')" class="px-2 py-0.5 rounded-[2px] bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10.5px] font-bold cursor-pointer">Daily</button>
                            <button type="button" @click="applyPreset('0 0 * * 0')" class="px-2 py-0.5 rounded-[2px] bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10.5px] font-bold cursor-pointer">Weekly</button>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">System User</label>
                        <input v-model="jobForm.user" type="text" placeholder="root" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="px-4 py-2.5 -mx-4 -mb-4 mt-4 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                        <button
                            type="button"
                            @click="showModal = false"
                            class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="jobForm.processing"
                            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ jobForm.processing ? 'Saving...' : (editingJob ? 'Save Changes' : 'Schedule Cron') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- DELETE MODAL -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-rose-50 text-rose-600 flex items-center justify-center font-bold border border-rose-100">
                            <TrashIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Delete Cron Job
                        </h3>
                    </div>
                    <button @click="showDeleteModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-2.5 text-xs">
                    <p class="text-slate-600">
                        Are you sure you want to remove cron job <strong class="text-slate-900 font-mono">[{{ jobToDelete?.command }}]</strong>?
                    </p>
                </div>

                <div class="px-4 py-2.5 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                    <button
                        type="button"
                        @click="showDeleteModal = false"
                        class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="submitDelete"
                        :disabled="deleteForm.processing"
                        class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                    >
                        {{ deleteForm.processing ? 'Deleting...' : 'Delete Cron' }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
