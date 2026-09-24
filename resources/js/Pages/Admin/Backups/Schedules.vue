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
    CalendarDaysIcon,
    ClockIcon,
    ArrowPathIcon,
    PlusIcon,
    PencilSquareIcon,
    TrashIcon,
    XMarkIcon,
    CheckIcon,
    BoltIcon,
    ServerIcon,
    CircleStackIcon,
    FolderIcon,
    ArchiveBoxIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    schedules: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            total_schedules: 0,
            active_schedules: 0,
            next_run_time: 'Never',
            next_run_exact: null,
            default_cron: 'Cron Runner Online',
        }),
    },
    storages: {
        type: Array,
        default: () => [],
    },
    subscriptions: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({ search: '', frequency: '', status: '' }),
    },
})

// Search & Filter
const search = ref(props.filters?.search || '')
const frequency = ref(props.filters?.frequency || '')
const status = ref(props.filters?.status || '')
const feedbackMsg = ref('')

const filteredSchedules = computed(() => {
    let list = props.schedules || []
    if (search.value.trim()) {
        const q = search.value.toLowerCase()
        list = list.filter(s =>
            (s.name || '').toLowerCase().includes(q) ||
            (s.frequency || '').toLowerCase().includes(q) ||
            (s.cron_expression || '').toLowerCase().includes(q)
        )
    }
    if (frequency.value) {
        list = list.filter(s => (s.frequency || '').toLowerCase() === frequency.value.toLowerCase())
    }
    return list
})

// 1. ADD / EDIT SCHEDULE MODAL
const showModal = ref(false)
const isEditing = ref(false)
const selectedSchedule = ref(null)

const form = useForm({
    name: '',
    subscription_id: '',
    backup_storage_id: '',
    frequency: 'daily',
    cron_expression: '0 2 * * *',
    type: 'full',
    retention_count: 7,
    status: 'active',
    notify_on_failure: true,
})

const openAddModal = () => {
    isEditing.value = false
    selectedSchedule.value = null
    form.reset()
    form.frequency = 'daily'
    form.cron_expression = '0 2 * * *'
    form.type = 'full'
    form.retention_count = 7
    form.status = 'active'
    showModal.value = true
}

const openEditModal = (schedule) => {
    isEditing.value = true
    selectedSchedule.value = schedule
    form.name = schedule.name
    form.subscription_id = schedule.subscription_id || ''
    form.backup_storage_id = schedule.backup_storage_id || ''
    form.frequency = schedule.frequency || 'daily'
    form.cron_expression = schedule.cron_expression || '0 2 * * *'
    form.type = schedule.type || 'full'
    form.retention_count = schedule.retention_count || 7
    form.status = schedule.status || 'active'
    form.notify_on_failure = schedule.notify_on_failure !== false
    showModal.value = true
}

const applyPreset = (preset) => {
    if (preset === 'daily') {
        form.frequency = 'daily'
        form.cron_expression = '0 2 * * *'
    } else if (preset === 'weekly') {
        form.frequency = 'weekly'
        form.cron_expression = '0 3 * * 0'
    } else if (preset === 'hourly') {
        form.frequency = 'hourly'
        form.cron_expression = '0 * * * *'
    }
}

const submitForm = () => {
    if (isEditing.value && selectedSchedule.value) {
        form.put(route('admin.backups.schedules.update', selectedSchedule.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false
                feedbackMsg.value = 'Backup schedule policy updated.'
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    } else {
        form.post(route('admin.backups.schedules.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false
                feedbackMsg.value = 'New automated schedule policy registered.'
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    }
}

// 2. RUN SCHEDULE NOW
const runScheduleNow = (schedule) => {
    router.post(route('admin.backups.schedules.run', schedule.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = `Schedule '${schedule.name}' executed in background.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 3. TOGGLE SCHEDULE
const toggleSchedule = (schedule) => {
    router.post(route('admin.backups.schedules.toggle', schedule.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = 'Schedule status updated.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 4. DELETE MODAL
const showDeleteModal = ref(false)
const scheduleToDelete = ref(null)
const deleteForm = useForm({})

const openDeleteModal = (schedule) => {
    scheduleToDelete.value = schedule
    showDeleteModal.value = true
}

const submitDelete = () => {
    if (!scheduleToDelete.value) return
    deleteForm.delete(route('admin.backups.schedules.destroy', scheduleToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false
            feedbackMsg.value = 'Schedule policy deleted.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}
</script>

<template>
    <Head title="Backup Schedules - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Security', href: '#' },
                    { label: 'Backups & Snapshots', href: route('admin.backups.index') },
                    { label: 'Automated Backup Schedules & Policies' }
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
                        @click="openAddModal"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <PlusIcon class="w-3.5 h-3.5" />
                        <span>Create Schedule Policy</span>
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
                    title="Configured Schedules"
                    :value="String(stats.total_schedules || schedules.length || 0)"
                    badge="Policies"
                    badgeType="info"
                    color="blue"
                    :icon="CalendarDaysIcon"
                />

                <InfoCard
                    title="Active Automations"
                    :value="String(stats.active_schedules || schedules.filter(s => s.status === 'active').length || 0)"
                    badge="Scheduled"
                    badgeType="success"
                    color="emerald"
                    :icon="BoltIcon"
                />

                <InfoCard
                    title="Next Imminent Window"
                    :value="stats.next_run_time || 'Tonight (02:00)'"
                    badge="Queued"
                    badgeType="info"
                    color="purple"
                    :icon="ClockIcon"
                />

                <InfoCard
                    title="Cron Engine Daemon"
                    value="Active & Online"
                    badge="Systemd"
                    badgeType="success"
                    color="sky"
                    :icon="ServerIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search schedule name, cron expression, or scope..."
                @search="() => {}"
                @filter="() => {}"
                @reset="() => { search = ''; frequency = ''; }"
            >
                <FilterSelect
                    v-model="frequency"
                    label="Schedule Interval"
                    :options="[
                        { label: 'All Intervals', value: '' },
                        { label: 'Hourly (Every Hour)', value: 'hourly' },
                        { label: 'Daily (Every Night)', value: 'daily' },
                        { label: 'Weekly (Sundays)', value: 'weekly' },
                        { label: 'Monthly', value: 'monthly' }
                    ]"
                    placeholder="All Intervals"
                />
            </DataTableFilter>

            <!-- 4. Standard Data Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Schedule Policy Name</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Scope Type</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Cron Expression</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Storage Target</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Retention</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Status</th>
                                <th class="py-2.5 px-3 w-28">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(s, index) in filteredSchedules" :key="s.id || index" class="hover:bg-blue-50/30 transition">
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-bold text-slate-900">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs border border-blue-100 shrink-0">
                                            <CalendarDaysIcon class="w-3.5 h-3.5" />
                                        </div>
                                        <span>{{ s.name }}</span>
                                    </div>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase bg-slate-100 text-slate-700 border border-slate-200 font-mono">
                                        {{ s.type || 'Full' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono font-bold text-blue-700 text-[11px]">
                                    {{ s.cron_expression || '0 2 * * *' }} ({{ s.frequency }})
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[11px] text-slate-700">
                                    {{ s.storage?.name || 'Default Storage' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[11px] text-slate-600">
                                    Keep {{ s.retention_count || 7 }} Snapshots
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="s.status === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-500 border-slate-200'"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ s.status || 'active' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1">
                                        <button 
                                            type="button" 
                                            @click="runScheduleNow(s)"
                                            class="px-2 py-1 bg-white hover:bg-slate-50 text-blue-700 font-bold rounded-[3px] text-xs border border-slate-200 shadow-2xs transition cursor-pointer"
                                            title="Run Schedule Now"
                                        >
                                            Run ⚡
                                        </button>

                                        <RowActionDropdown>
                                            <button
                                                type="button"
                                                @click="openEditModal(s)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <PencilSquareIcon class="w-3.5 h-3.5 text-slate-500" />
                                                <span>Edit Schedule</span>
                                            </button>

                                            <button
                                                type="button"
                                                @click="toggleSchedule(s)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <BoltIcon class="w-3.5 h-3.5 text-slate-500" />
                                                <span>{{ s.status === 'active' ? 'Pause Policy' : 'Activate Policy' }}</span>
                                            </button>

                                            <button
                                                type="button"
                                                @click="openDeleteModal(s)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-rose-600 hover:bg-rose-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <TrashIcon class="w-3.5 h-3.5" />
                                                <span>Delete Policy</span>
                                            </button>
                                        </RowActionDropdown>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!filteredSchedules || filteredSchedules.length === 0">
                                <td colspan="8" class="py-12 text-center text-slate-400 font-sans">
                                    No automated backup schedule policies found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ADD / EDIT MODAL -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-lg overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <CalendarDaysIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            {{ isEditing ? 'Edit Backup Schedule Policy' : 'Create Automated Backup Schedule' }}
                        </h3>
                    </div>
                    <button @click="showModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitForm" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Policy Name / Description <span class="text-rose-500">*</span></label>
                        <input v-model="form.name" type="text" required placeholder="Nightly Full Server Snapshot" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Backup Scope</label>
                            <select v-model="form.type" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                                <option value="full">Full Server (Files & DB)</option>
                                <option value="files">Web Files Only</option>
                                <option value="database">Databases Only</option>
                            </select>
                        </div>

                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Storage Target</label>
                            <select v-model="form.backup_storage_id" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                                <option value="">Default Active Storage</option>
                                <option v-for="st in storages" :key="st.id" :value="st.id">{{ st.name }} ({{ st.driver }})</option>
                            </select>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Cron Timing Expression <span class="text-rose-500">*</span></label>
                        <input v-model="form.cron_expression" type="text" required placeholder="0 2 * * *" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        
                        <div class="flex items-center gap-1.5 pt-1">
                            <span class="text-[10.5px] text-slate-400">Quick Presets:</span>
                            <button type="button" @click="applyPreset('hourly')" class="px-2 py-0.5 rounded-[2px] bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10.5px] font-bold cursor-pointer">Hourly</button>
                            <button type="button" @click="applyPreset('daily')" class="px-2 py-0.5 rounded-[2px] bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10.5px] font-bold cursor-pointer">Daily 02:00</button>
                            <button type="button" @click="applyPreset('weekly')" class="px-2 py-0.5 rounded-[2px] bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10.5px] font-bold cursor-pointer">Weekly Sunday</button>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Retention Count (Snapshots to Keep)</label>
                            <input v-model.number="form.retention_count" type="number" min="1" max="100" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        </div>

                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Account Target</label>
                            <select v-model="form.subscription_id" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                                <option value="">All Accounts (Global Server)</option>
                                <option v-for="sub in subscriptions" :key="sub.id" :value="sub.id">{{ sub.domain }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <input v-model="form.notify_on_failure" id="notify" type="checkbox" class="rounded-[2px] text-blue-600 focus:ring-blue-500 cursor-pointer" />
                        <label for="notify" class="text-xs text-slate-700 font-medium cursor-pointer">Send administrative alert notification if backup fails</label>
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
                            :disabled="form.processing"
                            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ form.processing ? 'Saving...' : (isEditing ? 'Save Policy' : 'Create Policy') }}
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
                            Delete Schedule Policy
                        </h3>
                    </div>
                    <button @click="showDeleteModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-2.5 text-xs">
                    <p class="text-slate-600">
                        Are you sure you want to remove backup schedule policy <strong class="text-slate-900 font-mono">[{{ scheduleToDelete?.name }}]</strong>?
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
                        {{ deleteForm.processing ? 'Deleting...' : 'Delete Policy' }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
