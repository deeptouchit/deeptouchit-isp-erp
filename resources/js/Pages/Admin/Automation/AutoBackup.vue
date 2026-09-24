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
    CloudArrowUpIcon,
    ClockIcon,
    ServerIcon,
    PlayIcon,
    PlusIcon,
    PencilSquareIcon,
    TrashIcon,
    ArrowPathIcon,
    XMarkIcon,
    CheckIcon,
    ShieldCheckIcon,
    BoltIcon,
    FolderIcon,
    CircleStackIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    schedules: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({}),
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
        default: () => ({}),
    },
})

// Search & Feedback State
const search = ref('')
const feedbackMsg = ref('')

const filteredSchedules = computed(() => {
    let list = props.schedules || []
    if (search.value.trim()) {
        const q = search.value.toLowerCase()
        list = list.filter(s =>
            (s.name || '').toLowerCase().includes(q) ||
            (s.frequency || '').toLowerCase().includes(q) ||
            (s.destination || '').toLowerCase().includes(q)
        )
    }
    return list
})

// 1. CREATE / EDIT MODAL
const showModal = ref(false)
const editingSchedule = ref(null)
const scheduleForm = useForm({
    name: '',
    frequency: 'daily',
    time: '02:00',
    type: 'full',
    retention_days: 7,
    storage_type: 'local',
})

const openCreateModal = () => {
    editingSchedule.value = null
    scheduleForm.reset()
    scheduleForm.frequency = 'daily'
    scheduleForm.time = '02:00'
    scheduleForm.type = 'full'
    scheduleForm.retention_days = 7
    scheduleForm.storage_type = 'local'
    showModal.value = true
}

const openEditModal = (s) => {
    editingSchedule.value = s
    scheduleForm.name = s.name
    scheduleForm.frequency = s.frequency || 'daily'
    scheduleForm.time = s.time || '02:00'
    scheduleForm.type = s.type || 'full'
    scheduleForm.retention_days = s.retention_days || 7
    scheduleForm.storage_type = s.storage_type || 'local'
    showModal.value = true
}

const submitSchedule = () => {
    if (editingSchedule.value) {
        scheduleForm.put(route('admin.automation.auto-backup.update', editingSchedule.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false
                feedbackMsg.value = 'Backup schedule updated.'
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    } else {
        scheduleForm.post(route('admin.automation.auto-backup.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false
                feedbackMsg.value = 'New automated backup plan created.'
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    }
}

// 2. RUN BACKUP NOW
const runBackupNow = (s) => {
    router.post(route('admin.automation.auto-backup.run', s.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = `Backup task '${s.name}' started in background.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 3. TOGGLE SCHEDULE
const toggleSchedule = (s) => {
    router.post(route('admin.automation.auto-backup.toggle', s.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = 'Schedule status updated.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 4. DELETE SCHEDULE
const showDeleteModal = ref(false)
const scheduleToDelete = ref(null)
const deleteForm = useForm({})

const openDeleteModal = (s) => {
    scheduleToDelete.value = s
    showDeleteModal.value = true
}

const submitDelete = () => {
    if (!scheduleToDelete.value) return
    deleteForm.delete(route('admin.automation.auto-backup.destroy', scheduleToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false
            feedbackMsg.value = 'Backup plan removed.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}
</script>

<template>
    <Head title="Auto Backup Plans - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Automation', href: '#' },
                    { label: 'Automation & Schedulers', href: route('admin.automation.cron') },
                    { label: 'Automated Backup Schedules' }
                ]"
            >
                <template #actions>
                    <button 
                        type="button" 
                        @click="openCreateModal"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <PlusIcon class="w-3.5 h-3.5" />
                        <span>Create Backup Plan</span>
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
                    title="Active Backup Plans"
                    :value="String(schedules.length || stats.total_plans || 0)"
                    badge="Schedules"
                    badgeType="info"
                    color="blue"
                    :icon="CloudArrowUpIcon"
                />

                <InfoCard
                    title="Retention Rotation"
                    :value="`${stats.retention_copies || 7} Days`"
                    badge="Auto Purge"
                    badgeType="success"
                    color="emerald"
                    :icon="ShieldCheckIcon"
                />

                <InfoCard
                    title="Backed-up Storage"
                    :value="stats.total_storage || '0 B'"
                    badge="Compressed"
                    badgeType="info"
                    color="purple"
                    :icon="CircleStackIcon"
                />

                <InfoCard
                    title="Next Backup Window"
                    :value="stats.next_window || 'Tonight (02:00)'"
                    badge="Scheduled"
                    badgeType="info"
                    color="sky"
                    :icon="ClockIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search backup plan name, destination, or frequency..."
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
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Backup Plan Name</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Scope Type</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Frequency & Time</th>
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
                                            <CloudArrowUpIcon class="w-3.5 h-3.5" />
                                        </div>
                                        <span>{{ s.name }}</span>
                                    </div>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase bg-slate-100 text-slate-700 border border-slate-200 font-mono">
                                        {{ s.type || 'Full Server' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[11px] text-blue-700 font-bold">
                                    {{ s.frequency }} ({{ s.time || '02:00' }})
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[11px] text-slate-700">
                                    {{ s.storage_type || 'Local Disk' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[11px] text-slate-600">
                                    {{ s.retention_days || 7 }} Days
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="s.status === 'active' || s.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-500 border-slate-200'"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ s.status || (s.is_active ? 'active' : 'paused') }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1">
                                        <button 
                                            type="button" 
                                            @click="runBackupNow(s)"
                                            class="px-2 py-1 bg-white hover:bg-slate-50 text-blue-700 font-bold rounded-[3px] text-xs border border-slate-200 shadow-2xs transition cursor-pointer"
                                            title="Run Now"
                                        >
                                            Backup ⚡
                                        </button>

                                        <RowActionDropdown>
                                            <button
                                                type="button"
                                                @click="openEditModal(s)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <PencilSquareIcon class="w-3.5 h-3.5 text-slate-500" />
                                                <span>Edit Plan</span>
                                            </button>

                                            <button
                                                type="button"
                                                @click="toggleSchedule(s)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <BoltIcon class="w-3.5 h-3.5 text-slate-500" />
                                                <span>{{ s.status === 'active' || s.is_active ? 'Pause Plan' : 'Activate Plan' }}</span>
                                            </button>

                                            <button
                                                type="button"
                                                @click="openDeleteModal(s)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-rose-600 hover:bg-rose-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <TrashIcon class="w-3.5 h-3.5" />
                                                <span>Delete Plan</span>
                                            </button>
                                        </RowActionDropdown>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!filteredSchedules || filteredSchedules.length === 0">
                                <td colspan="8" class="py-12 text-center text-slate-400 font-sans">
                                    No automated backup plans configured.
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
                            <CloudArrowUpIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            {{ editingSchedule ? 'Edit Backup Plan' : 'Create Automated Backup Plan' }}
                        </h3>
                    </div>
                    <button @click="showModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitSchedule" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Plan Name <span class="text-rose-500">*</span></label>
                        <input v-model="scheduleForm.name" type="text" required placeholder="Daily Nightly Server Backup" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Frequency</label>
                            <select v-model="scheduleForm.frequency" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                                <option value="hourly">Hourly</option>
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                            </select>
                        </div>

                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Execution Time</label>
                            <input v-model="scheduleForm.time" type="time" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Backup Scope</label>
                            <select v-model="scheduleForm.type" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                                <option value="full">Full Server (Files & DB)</option>
                                <option value="databases">Databases Only</option>
                                <option value="files">Web Files Only</option>
                            </select>
                        </div>

                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Retention (Days)</label>
                            <input v-model.number="scheduleForm.retention_days" type="number" min="1" max="365" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        </div>
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
                            :disabled="scheduleForm.processing"
                            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ scheduleForm.processing ? 'Saving...' : (editingSchedule ? 'Save Changes' : 'Create Plan') }}
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
                            Delete Backup Plan
                        </h3>
                    </div>
                    <button @click="showDeleteModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-2.5 text-xs">
                    <p class="text-slate-600">
                        Are you sure you want to remove backup plan <strong class="text-slate-900 font-mono">[{{ scheduleToDelete?.name }}]</strong>?
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
                        {{ deleteForm.processing ? 'Deleting...' : 'Delete Plan' }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
