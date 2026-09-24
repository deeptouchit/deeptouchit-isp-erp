<script setup>
import { ref } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'
import ServerStatusBadge from '@/Components/Admin/Servers/ServerStatusBadge.vue'
import RowActionDropdown from '@/Components/UI/RowActionDropdown.vue'

import {
    WrenchScrewdriverIcon,
    ServerIcon,
    ArrowPathIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    ClockIcon,
    ShieldCheckIcon,
    PlayIcon,
    XMarkIcon,
    SparklesIcon,
    BoltIcon,
    ArrowTrendingUpIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    servers: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            total_servers: 0,
            active_maintenance: 0,
            operational_nodes: 0,
            available_routines: 5,
            last_sync_at: null,
        }),
    },
    routines: {
        type: Array,
        default: () => [],
    },
    auditLogs: {
        type: Array,
        default: () => [],
    },
})

// Flash Feedback
const feedbackMsg = ref('')

// Toggle Maintenance Modal State
const showToggleModal = ref(false)
const selectedServer = ref(null)
const toggleForm = useForm({
    enabled: false,
    reason: '',
})

const openToggleModal = (server) => {
    selectedServer.value = server
    const willEnable = server.status !== 'maintenance'
    toggleForm.enabled = willEnable
    toggleForm.reason = server.maintenance_reason || (willEnable ? 'Scheduled infrastructure maintenance window' : '')
    showToggleModal.value = true
}

const submitToggleMaintenance = () => {
    if (!selectedServer.value) return
    toggleForm.post(route('admin.infrastructure.maintenance.node.toggle', selectedServer.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showToggleModal.value = false
            feedbackMsg.value = `Maintenance mode ${toggleForm.enabled ? 'enabled' : 'disabled'} for '${selectedServer.value.name}'.`
            setTimeout(() => {
                feedbackMsg.value = ''
            }, 4000)
        },
    })
}

// Routine Execution State
const showRoutineModal = ref(false)
const selectedRoutine = ref(null)
const routineForm = useForm({
    routine: '',
    server_id: '',
})

const openRoutineModal = (routine) => {
    selectedRoutine.value = routine
    routineForm.routine = routine.id
    routineForm.server_id = ''
    showRoutineModal.value = true
}

const submitRunRoutine = () => {
    if (!selectedRoutine.value) return
    routineForm.post(route('admin.infrastructure.maintenance.routines.run'), {
        preserveScroll: true,
        onSuccess: () => {
            showRoutineModal.value = false
            feedbackMsg.value = `Routine '${selectedRoutine.value.name}' executed successfully.`
            setTimeout(() => {
                feedbackMsg.value = ''
            }, 4000)
        },
    })
}

// Helpers
const formatDate = (dateStr) => {
    if (!dateStr) return 'Just now'
    try {
        const d = new Date(dateStr)
        if (isNaN(d.getTime())) return dateStr
        return new Intl.DateTimeFormat('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
            second: '2-digit',
            hour12: true,
        }).format(d)
    } catch {
        return dateStr
    }
}
</script>

<template>
    <Head title="Infrastructure Maintenance & Workflows - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Infrastructure', href: route('admin.servers.index') },
                    { label: 'Maintenance' }
                ]"
            >
                <template #actions>
                    <button 
                        type="button" 
                        @click="router.reload({ preserveScroll: true })"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowPathIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Refresh</span>
                    </button>
                </template>
            </PageHeader>

            <!-- Feedback Banner -->
            <div v-if="feedbackMsg" class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-3 py-2 rounded-[3px] text-xs font-bold flex items-center justify-between shadow-2xs">
                <div class="flex items-center gap-2">
                    <CheckCircleIcon class="w-4 h-4 text-emerald-600" />
                    <span>{{ feedbackMsg }}</span>
                </div>
                <button @click="feedbackMsg = ''" class="text-emerald-600 hover:text-emerald-800 text-xs cursor-pointer">✕</button>
            </div>

            <!-- 2. Ultra-Compact KPI Info Cards (56px) -->
            <InfoCardsGrid :cols="4">
                <InfoCard
                    title="Live Production"
                    :value="String(stats.operational_nodes || 0)"
                    :badge="`${stats.total_servers || 0} Online`"
                    badgeType="success"
                    color="emerald"
                    :icon="CheckCircleIcon"
                />

                <InfoCard
                    title="Maintenance State"
                    :value="String(stats.active_maintenance || 0)"
                    badge="Drained"
                    badgeType="warning"
                    color="amber"
                    :icon="WrenchScrewdriverIcon"
                />

                <InfoCard
                    title="Active Routines"
                    :value="String(stats.available_routines || 5)"
                    badge="Automated"
                    badgeType="purple"
                    color="purple"
                    :icon="SparklesIcon"
                />

                <InfoCard
                    title="Operational Guard"
                    value="Protected"
                    badge="Active"
                    badgeType="success"
                    color="sky"
                    :icon="ShieldCheckIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Cluster Node Draining Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="p-3.5 border-b border-slate-100 flex items-center justify-between bg-slate-50/40">
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wide text-slate-900">Cluster Node Draining & Maintenance Mode</h3>
                        <p class="text-[10.5px] text-slate-400">Put individual server nodes into maintenance mode to drain traffic before patching or reboots</p>
                    </div>
                    <span class="text-xs font-mono font-bold text-slate-600">{{ servers.length }} Node{{ servers.length === 1 ? '' : 's' }}</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Server Node</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Cluster Group</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Hosted Workloads</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Operational Mode</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Maintenance Reason / Window</th>
                                <th class="py-2.5 px-3 w-36">Draining Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                            <tr v-for="(server, index) in servers" :key="server.id" class="hover:bg-blue-50/30 transition">
                                <!-- # -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>

                                <!-- Node -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs shrink-0 border border-blue-100">
                                            {{ server.id }}
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-1.5">
                                                <Link :href="route('admin.servers.show', server.id)" class="font-bold text-slate-900 hover:text-blue-600">
                                                    {{ server.name }}
                                                </Link>
                                                <span v-if="server.is_master" class="px-1.5 py-0.2 rounded-[2px] text-[9px] font-bold uppercase bg-blue-50 text-blue-700 border border-blue-200">
                                                    Master
                                                </span>
                                            </div>
                                            <span class="text-[10px] font-mono text-slate-400 block">{{ server.ip_address }}</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Group -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded-[3px] text-[10.5px] font-mono bg-slate-100 text-slate-700 border border-slate-200">
                                        {{ server.group?.name || 'Default Cluster' }}
                                    </span>
                                </td>

                                <!-- Workloads -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap font-mono text-slate-700">
                                    {{ server.subscriptions_count || 0 }} Accounts
                                </td>

                                <!-- Status -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap">
                                    <span 
                                        v-if="server.status === 'maintenance'"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase bg-amber-50 text-amber-700 border border-amber-200"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                        In Maintenance
                                    </span>
                                    <span 
                                        v-else
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase bg-emerald-50 text-emerald-700 border border-emerald-200"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Production
                                    </span>
                                </td>

                                <!-- Reason -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left text-xs">
                                    <span v-if="server.maintenance_reason" class="font-medium text-slate-900 truncate block max-w-xs">
                                        {{ server.maintenance_reason }}
                                    </span>
                                    <span v-else class="text-slate-400 italic">No scheduled maintenance</span>
                                </td>

                                <!-- Action -->
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <button
                                        v-if="server.status === 'maintenance'"
                                        type="button"
                                        @click="openToggleModal(server)"
                                        class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-[3px] text-xs shadow-2xs transition inline-flex items-center gap-1 cursor-pointer"
                                    >
                                        <PlayIcon class="w-3.5 h-3.5" />
                                        <span>Exit Mode</span>
                                    </button>

                                    <button
                                        v-else
                                        type="button"
                                        @click="openToggleModal(server)"
                                        class="px-2.5 py-1 bg-white hover:bg-amber-50 text-amber-700 font-bold rounded-[3px] text-xs border border-amber-200 shadow-2xs transition inline-flex items-center gap-1 cursor-pointer"
                                    >
                                        <WrenchScrewdriverIcon class="w-3.5 h-3.5" />
                                        <span>Enter Mode</span>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 4. Automated Maintenance Workflows Grid -->
            <div class="bg-white p-4 rounded-[4px] border border-[#E2E8F0] shadow-2xs space-y-3">
                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wide text-slate-900">Automated Maintenance Workflows</h3>
                        <p class="text-[10.5px] text-slate-400">Execute zero-downtime hot reloads, socket flushes, and memory defragmentation routines</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    <div 
                        v-for="routine in routines" 
                        :key="routine.id"
                        class="bg-slate-50/70 p-3.5 rounded-[3px] border border-slate-200/80 hover:bg-white hover:border-blue-300 transition-all flex flex-col justify-between space-y-2.5 shadow-2xs"
                    >
                        <div class="space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="px-1.5 py-0.2 rounded-[2px] text-[9.5px] font-bold uppercase bg-white text-slate-700 border border-slate-200">
                                    {{ routine.category }}
                                </span>
                                <span class="text-[10px] font-mono text-emerald-600 font-bold">
                                    {{ routine.duration }}
                                </span>
                            </div>
                            <h4 class="text-xs font-bold text-slate-900">{{ routine.name }}</h4>
                            <p class="text-[10.5px] text-slate-500 line-clamp-2">{{ routine.description }}</p>
                        </div>

                        <div class="pt-2 border-t border-slate-200/80 flex items-center justify-between">
                            <span class="text-[10px] text-slate-400 font-mono">Impact: {{ routine.impact }}</span>
                            <button
                                type="button"
                                @click="openRoutineModal(routine)"
                                class="px-2.5 py-1 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] text-xs shadow-2xs transition inline-flex items-center gap-1 cursor-pointer"
                            >
                                <PlayIcon class="w-3.5 h-3.5" />
                                <span>Run</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 5. Maintenance Operations Audit Trail -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="p-3.5 border-b border-slate-100 flex items-center justify-between bg-slate-50/40">
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wide text-slate-900">Recent Maintenance Operations & Audit Trail</h3>
                        <p class="text-[10.5px] text-slate-400">Cryptographically logged lifecycle transitions, routine executions, and service reloads</p>
                    </div>
                    <span class="text-xs font-mono font-bold text-slate-600">Audit History</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Timestamp</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Target Server</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Event Type</th>
                                <th class="py-2.5 px-3 text-left">Execution Summary</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                            <tr v-for="(log, index) in auditLogs" :key="log.id" class="hover:bg-blue-50/30 transition">
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap font-mono text-slate-500 text-[10.5px]">
                                    {{ formatDate(log.occurred_at || log.created_at) }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap font-bold text-slate-900">
                                    {{ log.server?.name || 'Cluster Host' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap font-mono text-[10px] uppercase text-slate-600">
                                    <span class="px-1.5 py-0.5 rounded-[2px] bg-slate-100 border border-slate-200">
                                        {{ log.event_type }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-left text-slate-600">
                                    {{ log.message }}
                                </td>
                            </tr>
                            <tr v-if="!auditLogs || auditLogs.length === 0">
                                <td colspan="5" class="py-8 text-center text-slate-400">
                                    No maintenance audit records found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TOGGLE MAINTENANCE MODAL -->
        <div v-if="showToggleModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <WrenchScrewdriverIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            {{ toggleForm.enabled ? 'Enter Node Maintenance Mode' : 'Exit Maintenance Mode' }}
                        </h3>
                    </div>
                    <button @click="showToggleModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitToggleMaintenance" class="p-4 space-y-3 text-xs">
                    <div class="p-3 bg-amber-50 border border-amber-200 rounded-[3px] text-amber-800 space-y-1">
                        <strong class="block font-bold">Node Target: {{ selectedServer?.name }}</strong>
                        <p class="text-[11px]">
                            {{ toggleForm.enabled ? 'Placing this node in maintenance mode will mark it as unavailable for new tenant provisioning.' : 'Exiting maintenance mode will restore this node to live production status.' }}
                        </p>
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Maintenance Reason / Notice</label>
                        <input
                            v-model="toggleForm.reason"
                            type="text"
                            placeholder="e.g. Scheduled security patch & kernel update"
                            class="w-full px-3 py-1.5 rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition-all shadow-2xs"
                            required
                        />
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2.5 border-t border-slate-100">
                        <button
                            type="button"
                            @click="showToggleModal = false"
                            class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="toggleForm.processing"
                            :class="[
                                'px-3.5 py-1.5 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50',
                                toggleForm.enabled ? 'bg-amber-600 hover:bg-amber-700' : 'bg-emerald-600 hover:bg-emerald-700'
                            ]"
                        >
                            {{ toggleForm.processing ? 'Updating...' : (toggleForm.enabled ? 'Enter Maintenance' : 'Exit Maintenance') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- RUN ROUTINE MODAL -->
        <div v-if="showRoutineModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <SparklesIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Run Routine: {{ selectedRoutine?.name }}
                        </h3>
                    </div>
                    <button @click="showRoutineModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitRunRoutine" class="p-4 space-y-3 text-xs">
                    <p class="text-slate-600">
                        {{ selectedRoutine?.description }}
                    </p>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Target Node Scope</label>
                        <select
                            v-model="routineForm.server_id"
                            class="w-full px-3 py-1.5 rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition-all shadow-2xs cursor-pointer"
                        >
                            <option value="">All Cluster Nodes (Broadcast)</option>
                            <option v-for="srv in servers" :key="srv.id" :value="srv.id">
                                {{ srv.name }} ({{ srv.ip_address }})
                            </option>
                        </select>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2.5 border-t border-slate-100">
                        <button
                            type="button"
                            @click="showRoutineModal = false"
                            class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="routineForm.processing"
                            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ routineForm.processing ? 'Executing...' : 'Run Workflow' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
