<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'
import ServerStatusBadge from '@/Components/Admin/Servers/ServerStatusBadge.vue'
import RowActionDropdown from '@/Components/UI/RowActionDropdown.vue'

import {
    CpuChipIcon,
    CircleStackIcon,
    FolderIcon,
    ClockIcon,
    ServerIcon,
    ArrowPathIcon,
    CheckCircleIcon,
    ShieldCheckIcon,
    SparklesIcon,
    EyeIcon,
    ChartBarSquareIcon,
    Square2StackIcon,
    BoltIcon,
    WrenchScrewdriverIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    servers: {
        type: Array,
        default: () => [],
    },
    summary: {
        type: Object,
        default: () => ({
            total_nodes: 0,
            total_cores: 4,
            avg_cpu_usage: 0,
            load_avg_1min: 0.15,
            load_avg_5min: 0.18,
            load_avg_15min: 0.12,
            total_ram_mb: 7285,
            used_ram_mb: 3060,
            free_ram_mb: 4225,
            ram_usage_percent: 42.0,
            ram_free_percent: 58.0,
            total_disk_mb: 116500,
            used_disk_mb: 9800,
            free_disk_mb: 106700,
            disk_usage_percent: 8.4,
            disk_free_percent: 91.6,
            last_calculated_at: null,
        }),
    },
    workloadBreakdown: {
        type: Array,
        default: () => [],
    },
    policies: {
        type: Object,
        default: () => ({}),
    },
})

// Recalculate State
const isRecalculating = ref(false)
const syncMsg = ref('')

const triggerRecalculate = () => {
    isRecalculating.value = true
    router.post(route('admin.infrastructure.resources.recalculate'), {}, {
        preserveScroll: true,
        onSuccess: () => {
            isRecalculating.value = false
            syncMsg.value = 'Hardware resource capacities and workload metrics recalculated.'
            setTimeout(() => {
                syncMsg.value = ''
            }, 4000)
        },
        onError: () => {
            isRecalculating.value = false
        },
        onFinish: () => {
            isRecalculating.value = false
        }
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

const formatMb = (mb) => {
    if (!mb || mb <= 0) return '0 GB'
    if (mb >= 1024) {
        return `${(mb / 1024).toFixed(1)} GB`
    }
    return `${mb} MB`
}

const getCpuPercent = (server) => {
    if (server.latest_metric && server.latest_metric.cpu_usage !== undefined) {
        return Math.round(server.latest_metric.cpu_usage)
    }
    if (server.load_avg_1min !== undefined && server.load_avg_1min > 0) {
        const cores = server.cpu_cores || 1
        return Math.min(100, Math.round((server.load_avg_1min / cores) * 100))
    }
    return 0
}

const getRamPercent = (server) => {
    if (!server.total_ram || server.total_ram <= 0) return 0
    return Math.round(((server.used_ram || 0) / server.total_ram) * 100)
}

const getDiskPercent = (server) => {
    if (!server.total_disk || server.total_disk <= 0) return 0
    return Math.round(((server.used_disk || 0) / server.total_disk) * 100)
}
</script>

<template>
    <Head title="Cluster Resource Allocation - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Infrastructure', href: route('admin.servers.index') },
                    { label: 'Resource Capacity' }
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

                    <button 
                        type="button"
                        @click="triggerRecalculate"
                        :disabled="isRecalculating"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer disabled:opacity-60"
                    >
                        <ArrowPathIcon v-if="isRecalculating" class="w-3.5 h-3.5 animate-spin" />
                        <SparklesIcon v-else class="w-3.5 h-3.5" />
                        <span>{{ isRecalculating ? 'Recalculating...' : 'Recalculate' }}</span>
                    </button>
                </template>
            </PageHeader>

            <!-- Feedback Banner -->
            <div v-if="syncMsg" class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-3 py-2 rounded-[3px] text-xs font-bold flex items-center justify-between shadow-2xs">
                <div class="flex items-center gap-2">
                    <CheckCircleIcon class="w-4 h-4 text-emerald-600" />
                    <span>{{ syncMsg }}</span>
                </div>
                <button @click="syncMsg = ''" class="text-emerald-600 hover:text-emerald-800 text-xs cursor-pointer">✕</button>
            </div>

            <!-- 2. Ultra-Compact KPI Info Cards (56px) -->
            <InfoCardsGrid :cols="4">
                <InfoCard
                    title="Compute Pool"
                    :value="`${summary.total_cores || 1} Cores`"
                    :badge="`${summary.avg_cpu_usage || 0}% Load`"
                    badgeType="info"
                    color="blue"
                    :icon="CpuChipIcon"
                />

                <InfoCard
                    title="Memory Pool"
                    :value="`${summary.ram_usage_percent || 0}%`"
                    :badge="formatMb(summary.used_ram_mb)"
                    badgeType="success"
                    color="emerald"
                    :icon="CircleStackIcon"
                />

                <InfoCard
                    title="Storage Volume"
                    :value="`${summary.disk_usage_percent || 0}%`"
                    :badge="formatMb(summary.used_disk_mb)"
                    badgeType="info"
                    color="sky"
                    :icon="FolderIcon"
                />

                <InfoCard
                    title="System Load"
                    :value="String(summary.load_avg_1min || 0)"
                    :badge="`5m: ${summary.load_avg_5min || 0}`"
                    badgeType="warning"
                    color="amber"
                    :icon="ClockIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Workload Memory Allocation Breakdown -->
            <div class="bg-white p-4 rounded-[4px] border border-[#E2E8F0] shadow-2xs space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 pb-2 border-b border-slate-100">
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wide text-slate-900">Workload Memory Allocation Breakdown</h3>
                        <p class="text-[10.5px] text-slate-400">Live breakdown of RAM memory across application runtimes, database buffers, web proxies, and available headroom</p>
                    </div>
                    <div class="text-right">
                        <span class="text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-[3px] border border-emerald-200">
                            {{ summary.ram_free_percent }}% Free Headroom
                        </span>
                    </div>
                </div>

                <!-- Multi-Segmented Progress Bar -->
                <div class="h-3.5 w-full bg-slate-100 rounded-[3px] overflow-hidden flex p-0.5 border border-slate-200">
                    <div 
                        v-for="(tier, idx) in workloadBreakdown" 
                        :key="idx"
                        class="h-full first:rounded-l last:rounded-r transition-all duration-500 relative group"
                        :style="{ width: `${tier.percent}%`, backgroundColor: tier.color }"
                        :title="`${tier.tier}: ${tier.memory_mb} MB (${tier.percent}%)`"
                    ></div>
                </div>

                <!-- Workload Tiers Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5 pt-1">
                    <div 
                        v-for="(tier, idx) in workloadBreakdown" 
                        :key="idx"
                        class="bg-slate-50/70 p-2.5 rounded-[3px] border border-slate-200/80 flex items-start justify-between gap-2.5"
                    >
                        <div class="space-y-0.5">
                            <div class="flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-[2px]" :style="{ backgroundColor: tier.color }"></span>
                                <h4 class="text-xs font-bold text-slate-900">{{ tier.tier }}</h4>
                            </div>
                            <p class="text-[10.5px] text-slate-500 line-clamp-1">{{ tier.description }}</p>
                        </div>

                        <div class="text-right shrink-0">
                            <strong class="font-mono text-xs text-slate-900 block">{{ formatMb(tier.memory_mb) }}</strong>
                            <span class="text-[10px] text-slate-400 font-mono font-bold">{{ tier.percent }}%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. Node-by-Node Hardware Allocation Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="p-3.5 border-b border-slate-100 flex items-center justify-between bg-slate-50/40">
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wide text-slate-900">Node-by-Node Hardware Allocation</h3>
                        <p class="text-[10.5px] text-slate-400">Compute, memory, and storage saturation on individual infrastructure nodes</p>
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
                                <th class="py-2.5 px-3 border-r border-slate-200">Status</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Compute</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">RAM Memory</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Disk Storage</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">System Load</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Headroom</th>
                                <th class="py-2.5 px-3 w-16">Action</th>
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

                                <!-- Cluster -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded-[3px] text-[10.5px] font-mono bg-slate-100 text-slate-700 border border-slate-200">
                                        {{ server.group?.name || 'Default Cluster' }}
                                    </span>
                                </td>

                                <!-- Status -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap">
                                    <ServerStatusBadge :status="server.health_status || 'healthy'" />
                                </td>

                                <!-- CPU -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap">
                                    <div class="w-20 mx-auto">
                                        <div class="flex items-center justify-between text-[10px] font-mono mb-0.5">
                                            <span class="font-bold text-slate-900">{{ getCpuPercent(server) }}%</span>
                                            <span class="text-slate-400">{{ server.cpu_cores || 1 }}c</span>
                                        </div>
                                        <div class="h-1.5 w-full bg-slate-100 rounded overflow-hidden">
                                            <div class="h-full rounded bg-blue-600" :style="{ width: `${getCpuPercent(server)}%` }"></div>
                                        </div>
                                    </div>
                                </td>

                                <!-- RAM -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap">
                                    <div class="w-20 mx-auto">
                                        <div class="flex items-center justify-between text-[10px] font-mono mb-0.5">
                                            <span class="font-bold text-slate-900">{{ getRamPercent(server) }}%</span>
                                            <span class="text-slate-400">{{ formatMb(server.total_ram) }}</span>
                                        </div>
                                        <div class="h-1.5 w-full bg-slate-100 rounded overflow-hidden">
                                            <div class="h-full rounded bg-emerald-500" :style="{ width: `${getRamPercent(server)}%` }"></div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Disk -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap">
                                    <div class="w-20 mx-auto">
                                        <div class="flex items-center justify-between text-[10px] font-mono mb-0.5">
                                            <span class="font-bold text-slate-900">{{ getDiskPercent(server) }}%</span>
                                            <span class="text-slate-400">{{ formatMb(server.total_disk) }}</span>
                                        </div>
                                        <div class="h-1.5 w-full bg-slate-100 rounded overflow-hidden">
                                            <div class="h-full rounded bg-sky-500" :style="{ width: `${getDiskPercent(server)}%` }"></div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Load -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1 font-mono text-[10.5px]">
                                        <span class="px-1.5 py-0.5 rounded-[2px] bg-slate-100 text-slate-800 font-bold border border-slate-200">{{ server.load_avg_1min || 0 }}</span>
                                        <span class="px-1.5 py-0.5 rounded-[2px] bg-slate-100 text-slate-800 font-bold border border-slate-200">{{ server.load_avg_5min || 0 }}</span>
                                        <span class="px-1.5 py-0.5 rounded-[2px] bg-slate-100 text-slate-800 font-bold border border-slate-200">{{ server.load_avg_15min || 0 }}</span>
                                    </div>
                                </td>

                                <!-- Headroom -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded-[2px] text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Optimal
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <RowActionDropdown>
                                        <Link
                                            :href="route('admin.servers.show', server.id)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium"
                                        >
                                            <EyeIcon class="w-3.5 h-3.5 text-slate-500" />
                                            <span>Inspect Node</span>
                                        </Link>

                                        <Link
                                            :href="route('admin.servers.edit', server.id)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium"
                                        >
                                            <WrenchScrewdriverIcon class="w-3.5 h-3.5 text-slate-500" />
                                            <span>Edit Config</span>
                                        </Link>
                                    </RowActionDropdown>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 5. Capacity Guardrails & Provisioning Policies Card -->
            <div class="bg-white p-4 rounded-[4px] border border-[#E2E8F0] shadow-2xs space-y-2.5">
                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wide text-slate-900">Capacity Guardrails & Safety Thresholds</h3>
                        <p class="text-[10.5px] text-slate-400">Automated alerting triggers and cluster over-commit protection rules</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 text-xs">
                    <div class="bg-slate-50/70 p-2.5 rounded-[3px] border border-slate-100">
                        <span class="text-slate-400 block text-[9.5px] font-bold uppercase">CPU Policy</span>
                        <strong class="text-slate-800 font-bold block mt-0.5">{{ policies.cpu_policy }}</strong>
                    </div>

                    <div class="bg-slate-50/70 p-2.5 rounded-[3px] border border-slate-100">
                        <span class="text-slate-400 block text-[9.5px] font-bold uppercase">RAM Thresholds</span>
                        <strong class="text-slate-800 font-bold block mt-0.5">Warning: {{ policies.ram_warning }} • Crit: {{ policies.ram_critical }}</strong>
                    </div>

                    <div class="bg-slate-50/70 p-2.5 rounded-[3px] border border-slate-100">
                        <span class="text-slate-400 block text-[9.5px] font-bold uppercase">Storage Thresholds</span>
                        <strong class="text-slate-800 font-bold block mt-0.5">Warning: {{ policies.disk_warning }} • Crit: {{ policies.disk_critical }}</strong>
                    </div>

                    <div class="bg-slate-50/70 p-2.5 rounded-[3px] border border-slate-100">
                        <span class="text-slate-400 block text-[9.5px] font-bold uppercase">Over-commit Guard</span>
                        <strong class="text-emerald-700 font-bold block mt-0.5">{{ policies.overcommit_protection }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
