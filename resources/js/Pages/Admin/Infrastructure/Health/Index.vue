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
    HeartIcon,
    ServerIcon,
    ArrowPathIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    XCircleIcon,
    CpuChipIcon,
    CircleStackIcon,
    FolderIcon,
    ClockIcon,
    ShieldCheckIcon,
    CommandLineIcon,
    WrenchScrewdriverIcon,
    GlobeAltIcon,
    SparklesIcon,
    EyeIcon,
    BoltIcon
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
            healthy_servers: 0,
            warning_servers: 0,
            critical_servers: 0,
            total_services: 0,
            running_services: 0,
            failed_services: 0,
            total_cpu_cores: 1,
            avg_cpu_percent: 0,
            total_ram_mb: 1,
            used_ram_mb: 0,
            avg_ram_percent: 0,
            total_disk_mb: 1,
            used_disk_mb: 0,
            avg_disk_percent: 0,
            system_health_score: 100,
            last_inspected_at: null,
        }),
    },
    subsystems: {
        type: Array,
        default: () => [],
    },
    healthEvents: {
        type: Array,
        default: () => [],
    },
})

// Probe Action State
const isProbing = ref(false)
const probeSuccessMsg = ref('')

const triggerProbeAll = () => {
    isProbing.value = true
    router.post(route('admin.infrastructure.health.probe'), {}, {
        preserveScroll: true,
        onSuccess: () => {
            isProbing.value = false
            probeSuccessMsg.value = 'Health probe completed across all cluster nodes.'
            setTimeout(() => {
                probeSuccessMsg.value = ''
            }, 4000)
        },
        onError: () => {
            isProbing.value = false
        },
        onFinish: () => {
            isProbing.value = false
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
    return Math.min(100, Math.round(((server.used_ram || 0) / server.total_ram) * 100))
}

const getDiskPercent = (server) => {
    if (!server.total_disk || server.total_disk <= 0) return 0
    return Math.min(100, Math.round(((server.used_disk || 0) / server.total_disk) * 100))
}

const getSubsystemIcon = (cat) => {
    switch (cat?.toLowerCase()) {
        case 'webserver':
            return GlobeAltIcon
        case 'database':
            return CircleStackIcon
        case 'cache':
            return BoltIcon
        case 'runtime':
            return CpuChipIcon
        case 'security':
            return ShieldCheckIcon
        default:
            return ServerIcon
    }
}

const getSubsystemIconBg = (cat) => {
    switch (cat?.toLowerCase()) {
        case 'webserver':
            return 'bg-blue-50 text-blue-600 border-blue-100'
        case 'database':
            return 'bg-emerald-50 text-emerald-600 border-emerald-100'
        case 'cache':
            return 'bg-amber-50 text-amber-600 border-amber-100'
        case 'runtime':
            return 'bg-purple-50 text-purple-600 border-purple-100'
        case 'security':
            return 'bg-rose-50 text-rose-600 border-rose-100'
        default:
            return 'bg-slate-50 text-slate-600 border-slate-100'
    }
}
</script>

<template>
    <Head title="Infrastructure Health & Node Telemetry - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Infrastructure', href: route('admin.servers.index') },
                    { label: 'Server Health' }
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
                        @click="triggerProbeAll"
                        :disabled="isProbing"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer disabled:opacity-60"
                    >
                        <ArrowPathIcon v-if="isProbing" class="w-3.5 h-3.5 animate-spin" />
                        <SparklesIcon v-else class="w-3.5 h-3.5" />
                        <span>{{ isProbing ? 'Probing Nodes...' : 'Run Full Health Probe' }}</span>
                    </button>
                </template>
            </PageHeader>

            <!-- Feedback Banner -->
            <div v-if="probeSuccessMsg" class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-3 py-2 rounded-[3px] text-xs font-bold flex items-center justify-between shadow-2xs">
                <div class="flex items-center gap-2">
                    <CheckCircleIcon class="w-4 h-4 text-emerald-600" />
                    <span>{{ probeSuccessMsg }}</span>
                </div>
                <button @click="probeSuccessMsg = ''" class="text-emerald-600 hover:text-emerald-800 text-xs cursor-pointer">✕</button>
            </div>

            <!-- 2. Ultra-Compact KPI Info Cards (56px) -->
            <InfoCardsGrid :cols="4">
                <InfoCard
                    title="Healthy Nodes"
                    :value="String(stats.healthy_servers || 0)"
                    :badge="`${stats.total_servers || 0} Online`"
                    badgeType="success"
                    color="emerald"
                    :icon="CheckCircleIcon"
                />

                <InfoCard
                    title="Active Services"
                    :value="String(stats.running_services || 0)"
                    :badge="`${stats.total_services || 0} Units`"
                    badgeType="info"
                    color="sky"
                    :icon="WrenchScrewdriverIcon"
                />

                <InfoCard
                    title="Cluster CPU"
                    :value="`${stats.avg_cpu_percent || 0}%`"
                    :badge="`${stats.total_cpu_cores || 1} Cores`"
                    badgeType="info"
                    color="blue"
                    :icon="CpuChipIcon"
                />

                <InfoCard
                    title="Cluster RAM"
                    :value="`${stats.avg_ram_percent || 0}%`"
                    :badge="formatMb(stats.used_ram_mb)"
                    badgeType="success"
                    color="emerald"
                    :icon="CircleStackIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Core Subsystems Matrix Grid (Smart & Premium Cards) -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] p-3.5 shadow-2xs space-y-3">
                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
                        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-900">Core Subsystem Telemetry & Probes</h2>
                        <span class="text-[10.5px] text-slate-400 font-medium">({{ subsystems.length }} Engines Monitored)</span>
                    </div>
                    <span class="text-[10px] font-mono text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-[2px] border border-emerald-200 font-bold">
                        ● 100% Subsystems Online
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5">
                    <div 
                        v-for="sub in subsystems" 
                        :key="sub.id"
                        class="bg-white p-3 rounded-[3px] border border-slate-200 hover:border-blue-400 hover:shadow-xs transition-all flex flex-col justify-between space-y-2.5 group"
                    >
                        <!-- Top: Icon + Name + Status -->
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-2 min-w-0">
                                <div 
                                    :class="getSubsystemIconBg(sub.category)"
                                    class="w-7 h-7 rounded-[3px] flex items-center justify-center shrink-0 border"
                                >
                                    <component :is="getSubsystemIcon(sub.category)" class="w-4 h-4" />
                                </div>
                                <div class="min-w-0">
                                    <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block font-mono">{{ sub.category }}</span>
                                    <h4 class="text-xs font-bold text-slate-900 truncate leading-tight group-hover:text-blue-600 transition-colors">{{ sub.name }}</h4>
                                </div>
                            </div>

                            <span 
                                :class="sub.status === 'healthy' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
                                class="px-1.5 py-0.5 rounded-[2px] text-[9.5px] font-bold uppercase border inline-flex items-center gap-1 shrink-0 font-mono"
                            >
                                <span class="w-1.5 h-1.5 rounded-full" :class="sub.status === 'healthy' ? 'bg-emerald-500' : 'bg-rose-500'"></span>
                                {{ sub.status }}
                            </span>
                        </div>

                        <!-- Mid: Port & Latency -->
                        <div class="flex items-center justify-between text-[10.5px] font-mono pt-1 border-t border-slate-100">
                            <span class="text-slate-600 bg-slate-50 px-1.5 py-0.5 rounded-[2px] border border-slate-200 text-[10px]">
                                Port: {{ sub.port }}
                            </span>
                            <span class="text-emerald-600 font-bold bg-emerald-50 px-1.5 py-0.5 rounded-[2px] border border-emerald-200 text-[10px]">
                                ⚡ {{ sub.latency }}
                            </span>
                        </div>

                        <!-- Bottom: Details -->
                        <div class="text-[10.5px] text-slate-500 bg-slate-50/70 p-1.5 rounded-[2px] border border-slate-100 truncate font-medium">
                            {{ sub.details }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. Node-by-Node Health Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="p-3.5 border-b border-slate-100 flex items-center justify-between bg-slate-50/40">
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wide text-slate-900">Cluster Node Health Breakdown</h3>
                        <p class="text-[10.5px] text-slate-400">Individual node telemetry, resource saturation, and active services</p>
                    </div>
                    <span class="text-xs font-mono font-bold text-slate-600">{{ servers.length }} Node{{ servers.length === 1 ? '' : 's' }}</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Server Node</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Cluster Pool</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Health Status</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">CPU</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">RAM</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Disk</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">System Load (1m/5m/15m)</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Services</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Last Inspected</th>
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
                                        {{ server.group?.name || 'Default Pool' }}
                                    </span>
                                </td>

                                <!-- Health Status -->
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

                                <!-- Services -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap">
                                    <span class="px-1.5 py-0.5 rounded-[3px] text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-100">
                                        {{ server.services?.length || 0 }} Active
                                    </span>
                                </td>

                                <!-- Last Checked -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[10.5px] whitespace-nowrap">
                                    {{ formatDate(server.last_health_check_at) }}
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

                            <tr v-if="!servers || servers.length === 0">
                                <td colspan="11" class="py-12 text-center text-slate-400">
                                    No servers detected for health telemetry.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
