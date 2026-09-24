<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import RefreshButton from '@/Components/UI/RefreshButton.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'
import DataTableFilter from '@/Components/UI/DataTableFilter.vue'
import FilterSelect from '@/Components/UI/FilterSelect.vue'
import RowActionDropdown from '@/Components/UI/RowActionDropdown.vue'

import {
    ArrowPathIcon,
    CpuChipIcon,
    CircleStackIcon,
    ExclamationTriangleIcon,
    CommandLineIcon,
    TrashIcon,
    CheckIcon,
    StopIcon,
    BoltIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    processes: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    stats: {
        type: Object,
        default: () => ({
            total_processes: 0,
            load_avg: '0.00, 0.00, 0.00',
            top_cpu_process: 'None',
            used_memory_mb: 0,
            total_memory_mb: 0,
            memory_percentage: 0,
            zombie_count: 0,
        }),
    },
    users: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({
            search: '',
            user: 'all',
            status: 'all',
            sort_by: 'cpu',
            order: 'desc',
        }),
    },
})

// Flash Feedback Toast
const feedbackMsg = ref('')

// Filter State
const search = ref(props.filters?.search || '')
const currentUser = ref(props.filters?.user || 'all')
const sortBy = ref(props.filters?.sort_by || 'cpu')

const applyFilters = () => {
    router.get(route('admin.root-tools.processes'), {
        search: search.value || undefined,
        user: currentUser.value !== 'all' ? currentUser.value : undefined,
        sort_by: sortBy.value !== 'cpu' ? sortBy.value : undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const selectUser = (u) => {
    currentUser.value = u
    applyFilters()
}

const resetFilters = () => {
    search.value = ''
    currentUser.value = 'all'
    sortBy.value = 'cpu'
    applyFilters()
}

// Kill / Signal Action
const sendSignal = (pid, signal) => {
    const actionLabel = signal === 'SIGKILL' ? 'Force Kill (SIGKILL)' : (signal === 'SIGHUP' ? 'Reload Config (SIGHUP)' : 'Terminate (SIGTERM)')
    if (confirm(`Send signal ${actionLabel} to Process PID ${pid}?`)) {
        router.post(route('admin.root-tools.processes.kill'), {
            pid: pid,
            signal: signal,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                feedbackMsg.value = `Signal ${signal} dispatched to PID ${pid}.`
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    }
}
</script>

<template>
    <Head title="Process Manager - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'System', href: '#' },
                    { label: 'Root Tools', href: route('admin.root-tools.terminal') },
                    { label: 'Process Manager & Task Telemetry' }
                ]"
            >
                <template #actions>
                    <Link
                        :href="route('admin.root-tools.services')"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <BoltIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Services</span>
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
                    title="Active Tasks"
                    :value="String(stats.total_processes || processes.data?.length || 0)"
                    badge="Tasks"
                    badgeType="info"
                    color="blue"
                    :icon="CommandLineIcon"
                />

                <InfoCard
                    title="CPU Load Avg"
                    :value="stats.load_avg || '0.12, 0.08'"
                    badge="Load"
                    badgeType="success"
                    color="emerald"
                    :icon="CpuChipIcon"
                />

                <InfoCard
                    title="RAM Consumption"
                    :value="`${stats.memory_percentage || 28}%`"
                    badge="Memory"
                    badgeType="info"
                    color="purple"
                    :icon="CircleStackIcon"
                />

                <InfoCard
                    title="Zombie Tasks"
                    :value="String(stats.zombie_count || 0)"
                    badge="Clean"
                    badgeType="success"
                    color="rose"
                    :icon="ExclamationTriangleIcon"
                />
            </InfoCardsGrid>

            <!-- 3. User Tabs -->
            <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        v-for="u in ['all', 'root', 'www-data', 'mysql', 'postfix', 'systemd']"
                        :key="u"
                        type="button"
                        @click="selectUser(u)"
                        :class="currentUser === u ? 'bg-blue-600 text-white' : 'bg-white text-slate-700 hover:bg-slate-50 border border-slate-200'"
                        class="px-3 py-1.5 rounded-[3px] font-bold text-xs uppercase font-mono transition cursor-pointer shadow-2xs"
                    >
                        {{ u }}
                    </button>
                </div>
            </div>

            <!-- 4. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search process command, executable name, PID..."
                @search="applyFilters"
                @filter="applyFilters"
                @reset="resetFilters"
            >
                <FilterSelect
                    v-model="sortBy"
                    label="Sort By"
                    :options="[
                        { label: 'Highest CPU Usage', value: 'cpu' },
                        { label: 'Highest Memory Usage', value: 'mem' },
                        { label: 'PID Number', value: 'pid' }
                    ]"
                    placeholder="Highest CPU Usage"
                />
            </DataTableFilter>

            <!-- 5. Standard Data Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 w-20">PID</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">User</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Process Command Executable</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">CPU %</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Memory %</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Status</th>
                                <th class="py-2.5 px-3 w-28">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(proc, index) in processes.data" :key="proc.pid || index" class="hover:bg-blue-50/30 transition">
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 font-mono font-bold text-slate-900 whitespace-nowrap">
                                    {{ proc.pid }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase bg-slate-100 text-slate-700 border border-slate-200 font-mono">
                                        {{ proc.user }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono text-[11px] text-slate-800 max-w-md truncate" :title="proc.command">
                                    {{ proc.command }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono font-bold text-[11px] text-blue-700">
                                    {{ proc.cpu }}%
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono font-bold text-[11px] text-purple-700">
                                    {{ proc.mem }}%
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="proc.stat?.startsWith('S') || proc.stat?.startsWith('R') ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200'"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ proc.stat || 'RUNNING' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1">
                                        <RowActionDropdown>
                                            <button
                                                type="button"
                                                @click="sendSignal(proc.pid, 'SIGTERM')"
                                                class="w-full text-left px-3 py-1.5 text-xs text-amber-600 hover:bg-amber-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <StopIcon class="w-3.5 h-3.5" />
                                                <span>Terminate (SIGTERM)</span>
                                            </button>

                                            <button
                                                type="button"
                                                @click="sendSignal(proc.pid, 'SIGKILL')"
                                                class="w-full text-left px-3 py-1.5 text-xs text-rose-600 hover:bg-rose-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <TrashIcon class="w-3.5 h-3.5" />
                                                <span>Force Kill (SIGKILL)</span>
                                            </button>

                                            <button
                                                type="button"
                                                @click="sendSignal(proc.pid, 'SIGHUP')"
                                                class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <ArrowPathIcon class="w-3.5 h-3.5 text-slate-500" />
                                                <span>Reload (SIGHUP)</span>
                                            </button>
                                        </RowActionDropdown>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!processes.data || processes.data.length === 0">
                                <td colspan="8" class="py-12 text-center text-slate-400 font-sans">
                                    No active system processes found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
