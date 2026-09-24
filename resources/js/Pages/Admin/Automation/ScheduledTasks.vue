<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import RefreshButton from '@/Components/UI/RefreshButton.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'
import DataTableFilter from '@/Components/UI/DataTableFilter.vue'
import RowActionDropdown from '@/Components/UI/RowActionDropdown.vue'

import {
    CalendarDaysIcon,
    CommandLineIcon,
    PlayIcon,
    ArrowPathIcon,
    CheckIcon,
    ClockIcon,
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
    tasks: {
        type: Array,
        default: () => [],
    },
    categories: {
        type: Array,
        default: () => [],
    },
})

// Search & Feedback State
const search = ref('')
const feedbackMsg = ref('')

const filteredTasks = computed(() => {
    let list = props.tasks || []
    if (search.value.trim()) {
        const q = search.value.toLowerCase()
        list = list.filter(t =>
            (t.name || '').toLowerCase().includes(q) ||
            (t.command || '').toLowerCase().includes(q) ||
            (t.description || '').toLowerCase().includes(q)
        )
    }
    return list
})

const runTaskNow = (task) => {
    router.post(route('admin.automation.scheduled-tasks.run', { command: task.command }), {}, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = `Artisan command '${task.command}' executed.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}
</script>

<template>
    <Head title="Scheduled Tasks - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Automation', href: '#' },
                    { label: 'Automation & Schedulers', href: route('admin.automation.cron') },
                    { label: 'Scheduled System Tasks' }
                ]"
            >
                <template #actions>
                    <Link
                        :href="route('admin.automation.cron')"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowLeftIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Cron Jobs</span>
                    </Link>

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
                    title="Scheduled Artisan Tasks"
                    :value="String(tasks.length || stats.total_tasks || 0)"
                    badge="Tasks"
                    badgeType="info"
                    color="blue"
                    :icon="CalendarDaysIcon"
                />

                <InfoCard
                    title="Active Tasks"
                    :value="String(tasks.filter(t => t.status === 'active' || t.is_active).length || stats.active_tasks || 0)"
                    badge="Running"
                    badgeType="success"
                    color="emerald"
                    :icon="BoltIcon"
                />

                <InfoCard
                    title="Task Categories"
                    :value="String(categories.length || stats.categories_count || 4)"
                    badge="Modules"
                    badgeType="info"
                    color="purple"
                    :icon="ServerIcon"
                />

                <InfoCard
                    title="Next Schedule Window"
                    :value="stats.next_run || '1 Minute'"
                    badge="Imminent"
                    badgeType="info"
                    color="sky"
                    :icon="ClockIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search task name, artisan command, or schedule..."
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
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Task Name & Scope</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Artisan Command</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 w-32">Interval</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Next Scheduled Run</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Status</th>
                                <th class="py-2.5 px-3 w-28">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(task, index) in filteredTasks" :key="index" class="hover:bg-blue-50/30 transition">
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-bold text-slate-900">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs border border-blue-100 shrink-0">
                                            <CalendarDaysIcon class="w-3.5 h-3.5" />
                                        </div>
                                        <div>
                                            <span class="block leading-tight">{{ task.name }}</span>
                                            <span class="text-[10px] text-slate-400 block font-normal">{{ task.description || 'System routine' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono font-bold text-blue-700">
                                    {{ task.command }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[11px] text-slate-600">
                                    {{ task.interval || task.expression || 'Hourly' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[10.5px] text-slate-500">
                                    {{ task.next_run || '1m' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="task.status === 'active' || task.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-500 border-slate-200'"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ task.status || (task.is_active ? 'active' : 'paused') }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <button 
                                        type="button" 
                                        @click="runTaskNow(task)"
                                        class="px-2 py-1 bg-white hover:bg-slate-50 text-blue-700 font-bold rounded-[3px] text-xs border border-slate-200 shadow-2xs transition cursor-pointer"
                                        title="Run Task"
                                    >
                                        Run ⚡
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="!filteredTasks || filteredTasks.length === 0">
                                <td colspan="7" class="py-12 text-center text-slate-400 font-sans">
                                    No scheduled tasks found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
