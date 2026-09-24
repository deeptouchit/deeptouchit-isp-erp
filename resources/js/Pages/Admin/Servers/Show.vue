<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'
import ServerStatusBadge from '@/Components/Admin/Servers/ServerStatusBadge.vue'
import ServerHealthBadge from '@/Components/Admin/Servers/ServerHealthBadge.vue'
import MaintenanceModal from '@/Components/Admin/Servers/MaintenanceModal.vue'
import HostKeyApprovalModal from '@/Components/Admin/Servers/HostKeyApprovalModal.vue'
import ServiceOperationModal from '@/Components/Admin/Servers/ServiceOperationModal.vue'
import CredentialRotationModal from '@/Components/Admin/Servers/CredentialRotationModal.vue'
import DeleteServerModal from '@/Components/Admin/Servers/DeleteServerModal.vue'
import ServerMetricChart from '@/Components/Admin/Servers/Monitoring/ServerMetricChart.vue'
import MonitoringAlertsList from '@/Components/Admin/Servers/Monitoring/MonitoringAlertsList.vue'
import {
    ServerIcon,
    ArrowLeftIcon,
    ArrowPathIcon,
    ShieldCheckIcon,
    MagnifyingGlassIcon,
    WrenchScrewdriverIcon,
    KeyIcon,
    PencilSquareIcon,
    CpuChipIcon,
    CircleStackIcon,
    FolderIcon,
    ClockIcon,
    ExclamationTriangleIcon,
    PlayIcon,
    PauseIcon,
    LockClosedIcon,
    CheckCircleIcon,
    ClipboardDocumentIcon,
    ClipboardDocumentCheckIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    server: {
        type: Object,
        required: true,
    },
    allowedServices: {
        type: Array,
        default: () => [],
    },
})

// Active tab
const currentTab = ref('overview') // 'overview', 'monitoring', 'services', 'php', 'events', 'logs', 'security'

// Formatters
const formatMb = (mb) => {
    if (!mb || mb <= 0) return '0 GB'
    if (mb >= 1024) {
        return `${(mb / 1024).toFixed(1)} GB`
    }
    return `${mb} MB`
}

const formatRamDetail = (used, total) => {
    if (!total || total <= 0) return '0 GB / 0 GB'
    return `${formatMb(used)} / ${formatMb(total)}`
}

const formatDiskDetail = (used, total) => {
    if (!total || total <= 0) return '0 GB / 0 GB'
    return `${formatMb(used)} / ${formatMb(total)}`
}

const getCpuPercentage = () => {
    if (props.server?.latest_metric && props.server.latest_metric.cpu_usage !== undefined && props.server.latest_metric.cpu_usage > 0) {
        return Math.round(props.server.latest_metric.cpu_usage)
    }
    if (props.server?.load_avg_1min !== undefined && props.server.load_avg_1min > 0) {
        const cores = props.server.cpu_cores || 1
        return Math.min(100, Math.round((props.server.load_avg_1min / cores) * 100))
    }
    return 0
}

const getRamPercentage = () => {
    if (!props.server?.total_ram || props.server.total_ram <= 0) return 0
    return Math.min(100, Math.round(((props.server.used_ram || 0) / props.server.total_ram) * 100))
}

const getDiskPercentage = () => {
    if (!props.server?.total_disk || props.server.total_disk <= 0) return 0
    return Math.min(100, Math.round(((props.server.used_disk || 0) / props.server.total_disk) * 100))
}

// DateTime formatters
const formatDate = (dateStr) => {
    if (!dateStr) return '—'
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

const formatShortDate = (dateStr) => {
    if (!dateStr) return '—'
    try {
        const d = new Date(dateStr)
        if (isNaN(d.getTime())) return dateStr
        return new Intl.DateTimeFormat('en-US', {
            month: 'short',
            day: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
            hour12: true,
        }).format(d)
    } catch {
        return dateStr
    }
}

const copiedFingerprint = ref(false)
const copyFingerprint = (text) => {
    if (!text) return
    navigator.clipboard?.writeText(text)
    copiedFingerprint.value = true
    setTimeout(() => {
        copiedFingerprint.value = false
    }, 2000)
}

// Modal states
const showMaintenanceModal = ref(false)
const showHostKeyModal = ref(false)
const showCredentialsModal = ref(false)
const showDeleteModal = ref(false)

// Service modal state
const showServiceModal = ref(false)
const selectedService = ref('')
const selectedServiceAction = ref('restart')

const openServiceAction = (serviceName, action) => {
    selectedService.value = serviceName
    selectedServiceAction.value = action
    showServiceModal.value = true
}

// Action triggers
const isVerifying = ref(false)
const isDiscovering = ref(false)
const isSyncing = ref(false)

const triggerVerify = () => {
    isVerifying.value = true
    router.post(route('admin.servers.verify', props.server.id), {}, {
        preserveScroll: true,
        onFinish: () => { isVerifying.value = false }
    })
}

const triggerDiscover = () => {
    isDiscovering.value = true
    router.post(route('admin.servers.discover', props.server.id), {}, {
        preserveScroll: true,
        onFinish: () => { isDiscovering.value = false }
    })
}

const triggerSync = () => {
    isSyncing.value = true
    router.post(route('admin.servers.sync', props.server.id), {}, {
        preserveScroll: true,
        onFinish: () => { isSyncing.value = false }
    })
}

// Security mismatch check
const hasHostKeyMismatch = computed(() => {
    if (!props.server?.trusted_ssh_host_key_fingerprint || !props.server?.ssh_host_key_fingerprint) return false
    return props.server.trusted_ssh_host_key_fingerprint !== props.server.ssh_host_key_fingerprint
})

// Filter PHP services
const phpServices = computed(() => {
    return props.server?.services?.filter(s => s.service_name.startsWith('php') || s.service_name.includes('fpm')) || []
})

// System services
const systemServices = computed(() => {
    return props.server?.services?.filter(s => !s.service_name.startsWith('php') && !s.service_name.includes('fpm')) || []
})
</script>

<template>
    <Head :title="`${server.name} - DeepTouchHost`" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Infrastructure', href: '#' },
                    { label: 'Servers & Nodes', href: route('admin.servers.index') },
                    { label: server.name }
                ]"
            >
                <template #actions>
                    <Link 
                        :href="route('admin.servers.index')" 
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowLeftIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Back</span>
                    </Link>

                    <button 
                        type="button" 
                        :disabled="isSyncing"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer disabled:opacity-50"
                        @click="triggerSync"
                    >
                        <ArrowPathIcon class="w-3.5 h-3.5 text-sky-600" :class="{ 'animate-spin': isSyncing }" />
                        <span>{{ isSyncing ? 'Syncing...' : 'Sync' }}</span>
                    </button>

                    <button 
                        type="button" 
                        :disabled="isVerifying"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer disabled:opacity-50"
                        @click="triggerVerify"
                    >
                        <ShieldCheckIcon class="w-3.5 h-3.5 text-emerald-600" />
                        <span>{{ isVerifying ? 'Verifying...' : 'Verify' }}</span>
                    </button>

                    <button 
                        type="button" 
                        class="px-2.5 py-1.5 font-bold rounded-[3px] text-xs flex items-center gap-1.5 transition cursor-pointer border shadow-2xs"
                        :class="server.status === 'maintenance' ? 'bg-amber-500 hover:bg-amber-600 text-white border-amber-600' : 'bg-white hover:bg-amber-50 text-amber-700 border-amber-200'"
                        @click="showMaintenanceModal = true"
                    >
                        <WrenchScrewdriverIcon class="w-3.5 h-3.5" />
                        <span>{{ server.status === 'maintenance' ? 'Exit Maintenance' : 'Maintenance' }}</span>
                    </button>

                    <Link 
                        :href="route('admin.servers.edit', server.id)"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] text-xs flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <PencilSquareIcon class="w-3.5 h-3.5" />
                        <span>Edit Config</span>
                    </Link>
                </template>
            </PageHeader>

            <!-- 2. Ultra-Compact Standard KPI Info Cards (56px) -->
            <InfoCardsGrid :cols="4">
                <InfoCard
                    title="Node Status"
                    :value="server.status ? server.status.toUpperCase() : 'UNKNOWN'"
                    :badge="server.is_master ? 'Master' : 'Worker'"
                    badgeType="success"
                    color="emerald"
                    :icon="ServerIcon"
                />

                <InfoCard
                    title="CPU Core Load"
                    :value="`${getCpuPercentage()}%`"
                    :badge="`${server.cpu_cores || 1} Cores`"
                    badgeType="info"
                    color="sky"
                    :icon="CpuChipIcon"
                />

                <InfoCard
                    title="System RAM"
                    :value="`${getRamPercentage()}%`"
                    :badge="formatMb(server.total_ram)"
                    badgeType="success"
                    color="emerald"
                    :icon="CircleStackIcon"
                />

                <InfoCard
                    title="Disk Storage"
                    :value="`${getDiskPercentage()}%`"
                    :badge="formatMb(server.total_disk)"
                    badgeType="info"
                    color="purple"
                    :icon="FolderIcon"
                />
            </InfoCardsGrid>

            <!-- Maintenance Mode Banner -->
            <div 
                v-if="server.status === 'maintenance'" 
                class="p-3 bg-amber-50 border border-amber-200 rounded-[4px] flex items-center justify-between gap-4 shadow-2xs"
            >
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-[3px] bg-amber-100 text-amber-600 flex items-center justify-center shrink-0">
                        <WrenchScrewdriverIcon class="w-4 h-4" />
                    </div>
                    <div>
                        <strong class="text-xs font-bold text-amber-900 block">
                            MAINTENANCE MODE ACTIVE
                        </strong>
                        <span class="text-[11px] text-amber-800">
                            {{ server.maintenance_reason ? `Reason: ${server.maintenance_reason}` : 'Server node operations are currently suspended for maintenance.' }}
                        </span>
                    </div>
                </div>
                <button 
                    type="button" 
                    class="px-2.5 py-1 bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold rounded-[3px] transition cursor-pointer"
                    @click="showMaintenanceModal = true"
                >
                    Disable Mode
                </button>
            </div>

            <!-- Host Key Security Warning Banner -->
            <div 
                v-if="hasHostKeyMismatch" 
                class="p-3 bg-rose-50 border border-rose-200 rounded-[4px] flex items-center justify-between gap-4 shadow-2xs"
            >
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-[3px] bg-rose-100 text-rose-600 flex items-center justify-center shrink-0">
                        <ExclamationTriangleIcon class="w-4 h-4" />
                    </div>
                    <div>
                        <strong class="text-xs font-bold text-rose-900 block">
                            SECURITY ALERT: SSH HOST KEY MISMATCH DETECTED
                        </strong>
                        <span class="text-[11px] text-rose-800">
                            The remote server host key fingerprint does not match the pinned trusted fingerprint. SSH commands are blocked for security.
                        </span>
                    </div>
                </div>
                <button 
                    type="button" 
                    class="px-2.5 py-1 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-[3px] transition cursor-pointer"
                    @click="showHostKeyModal = true"
                >
                    Review & Pin Host Key
                </button>
            </div>

            <!-- 3. Sleek Tab Navigation Bar -->
            <div class="bg-white p-1 rounded-[4px] border border-[#E2E8F0] shadow-2xs flex items-center gap-1 overflow-x-auto">
                <button
                    v-for="tab in [
                        { id: 'overview', label: 'Overview & Hardware', count: null },
                        { id: 'monitoring', label: 'Live Monitoring & Charts', count: null },
                        { id: 'services', label: 'Services', count: systemServices.length },
                        { id: 'php', label: 'Multi-PHP', count: phpServices.length },
                        { id: 'events', label: 'Event Audit', count: server.events?.length || 0 },
                        { id: 'logs', label: 'Logs', count: server.logs?.length || 0 },
                        { id: 'security', label: 'SSH & Security', count: null },
                    ]"
                    :key="tab.id"
                    type="button"
                    :class="[
                        'px-3 py-1.5 text-xs font-bold rounded-[3px] transition-all whitespace-nowrap flex items-center gap-1.5 cursor-pointer',
                        currentTab === tab.id
                            ? 'bg-blue-600 text-white shadow-2xs'
                            : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
                    ]"
                    @click="currentTab = tab.id"
                >
                    <span>{{ tab.label }}</span>
                    <span 
                        v-if="tab.count !== null"
                        :class="[
                            'px-1.5 py-0.2 rounded text-[10px] font-mono',
                            currentTab === tab.id ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600'
                        ]"
                    >
                        {{ tab.count }}
                    </span>
                </button>
            </div>

            <!-- TAB 1: OVERVIEW & HARDWARE -->
            <div v-if="currentTab === 'overview'" class="space-y-3.5">
                <!-- Hardware Specifications & Cluster Placement Cards -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-3.5">
                    <!-- Discovered Hardware -->
                    <div class="bg-white p-4 rounded-[4px] border border-[#E2E8F0] shadow-2xs space-y-3.5">
                        <div class="flex items-center gap-2 pb-2.5 border-b border-slate-100">
                            <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center border border-blue-100">
                                <ServerIcon class="w-3.5 h-3.5" />
                            </div>
                            <div>
                                <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">Discovered Hardware & Kernel</h3>
                                <p class="text-[10.5px] text-slate-400">Host topology probed via SSH and systemd</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2.5 text-xs">
                            <div class="bg-slate-50/70 p-2.5 rounded-[3px] border border-slate-100">
                                <span class="text-slate-400 block text-[9.5px] font-bold uppercase tracking-wider">Operating System</span>
                                <strong class="text-slate-900 font-bold mt-0.5 block">{{ server.os_name || server.os || 'Linux' }} {{ server.os_version }}</strong>
                            </div>

                            <div class="bg-slate-50/70 p-2.5 rounded-[3px] border border-slate-100">
                                <span class="text-slate-400 block text-[9.5px] font-bold uppercase tracking-wider">Kernel Release</span>
                                <strong class="font-mono text-slate-900 font-bold mt-0.5 block truncate">{{ server.kernel_version || 'Linux x86_64' }}</strong>
                            </div>

                            <div class="bg-slate-50/70 p-2.5 rounded-[3px] border border-slate-100">
                                <span class="text-slate-400 block text-[9.5px] font-bold uppercase tracking-wider">Architecture</span>
                                <strong class="text-slate-900 font-bold uppercase mt-0.5 block">{{ server.architecture || 'x86_64' }}</strong>
                            </div>

                            <div class="bg-slate-50/70 p-2.5 rounded-[3px] border border-slate-100">
                                <span class="text-slate-400 block text-[9.5px] font-bold uppercase tracking-wider">CPU Physical Cores</span>
                                <strong class="text-slate-900 font-bold mt-0.5 block">{{ server.cpu_cores || 1 }} Cores</strong>
                            </div>

                            <div class="bg-slate-50/70 p-2.5 rounded-[3px] border border-slate-100">
                                <span class="text-slate-400 block text-[9.5px] font-bold uppercase tracking-wider">Total RAM</span>
                                <strong class="text-slate-900 font-bold mt-0.5 block">{{ formatMb(server.total_ram) }}</strong>
                            </div>

                            <div class="bg-slate-50/70 p-2.5 rounded-[3px] border border-slate-100">
                                <span class="text-slate-400 block text-[9.5px] font-bold uppercase tracking-wider">Total Disk Capacity</span>
                                <strong class="text-slate-900 font-bold mt-0.5 block">{{ formatMb(server.total_disk) }}</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Cluster Placement -->
                    <div class="bg-white p-4 rounded-[4px] border border-[#E2E8F0] shadow-2xs space-y-3.5">
                        <div class="flex items-center gap-2 pb-2.5 border-b border-slate-100">
                            <div class="w-6 h-6 rounded-[3px] bg-emerald-50 text-emerald-600 flex items-center justify-center border border-emerald-100">
                                <ShieldCheckIcon class="w-3.5 h-3.5" />
                            </div>
                            <div>
                                <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">Cluster Placement & Verification</h3>
                                <p class="text-[10.5px] text-slate-400">Node membership and cryptographic verification</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2.5 text-xs">
                            <div class="bg-slate-50/70 p-2.5 rounded-[3px] border border-slate-100">
                                <span class="text-slate-400 block text-[9.5px] font-bold uppercase tracking-wider">Server Group</span>
                                <strong class="text-slate-900 font-bold mt-0.5 block">{{ server.server_group?.name || 'Default Cluster' }}</strong>
                            </div>

                            <div class="bg-slate-50/70 p-2.5 rounded-[3px] border border-slate-100">
                                <span class="text-slate-400 block text-[9.5px] font-bold uppercase tracking-wider">Active Hosting Accounts</span>
                                <strong class="text-slate-900 font-bold mt-0.5 block">{{ server.subscriptions_count || 0 }} Accounts</strong>
                            </div>

                            <div class="bg-slate-50/70 p-2.5 rounded-[3px] border border-slate-100">
                                <span class="text-slate-400 block text-[9.5px] font-bold uppercase tracking-wider">Last Health Ping</span>
                                <strong class="text-slate-900 font-bold mt-0.5 block font-mono text-[11px]">{{ formatDate(server.last_ping_at) }}</strong>
                            </div>

                            <div class="bg-slate-50/70 p-2.5 rounded-[3px] border border-slate-100">
                                <span class="text-slate-400 block text-[9.5px] font-bold uppercase tracking-wider">Last Seen Online</span>
                                <strong class="text-slate-900 font-bold mt-0.5 block font-mono text-[11px]">{{ formatDate(server.last_seen_at) }}</strong>
                            </div>

                            <div class="bg-slate-50/70 p-2.5 rounded-[3px] border border-slate-100">
                                <span class="text-slate-400 block text-[9.5px] font-bold uppercase tracking-wider">Agent Telemetry Protocol</span>
                                <strong class="font-mono text-slate-900 font-bold mt-0.5 block">{{ server.agent_version || 'Builtin SSH' }}</strong>
                            </div>

                            <div class="bg-slate-50/70 p-2.5 rounded-[3px] border border-slate-100">
                                <span class="text-slate-400 block text-[9.5px] font-bold uppercase tracking-wider">Host Security Policy</span>
                                <strong class="text-blue-600 uppercase font-bold mt-0.5 block">{{ server.ssh_host_key_policy || 'STRICT' }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: MONITORING & TIMESERIES CHARTS -->
            <div v-if="currentTab === 'monitoring'" class="space-y-3.5">
                <!-- Alerts summary -->
                <MonitoringAlertsList :server-id="server.id" />

                <!-- Historical Timeseries Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-3.5">
                    <ServerMetricChart
                        :server-id="server.id"
                        metric-type="cpu"
                        title="CPU Utilization Over Time"
                        unit="%"
                    />

                    <ServerMetricChart
                        :server-id="server.id"
                        metric-type="ram"
                        title="Memory (RAM) Usage Over Time"
                        unit="MB"
                    />

                    <ServerMetricChart
                        :server-id="server.id"
                        metric-type="disk"
                        title="Disk Space Usage Over Time"
                        unit="%"
                    />

                    <ServerMetricChart
                        :server-id="server.id"
                        metric-type="load_1m"
                        title="System Load Average (1-min) History"
                        unit=""
                    />
                </div>
            </div>

            <!-- TAB 2: SERVICES SWITCHBOARD TABLE -->
            <div v-if="currentTab === 'services'" class="space-y-3">
                <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                            <thead>
                                <tr class="border-b border-slate-200 bg-slate-50/90 text-[10.5px] font-bold text-slate-600 uppercase tracking-wider whitespace-nowrap">
                                    <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                    <th class="py-2.5 px-3 border-r border-slate-200 text-left">Service Name</th>
                                    <th class="py-2.5 px-3 border-r border-slate-200 font-mono">Daemon Unit</th>
                                    <th class="py-2.5 px-3 border-r border-slate-200">Status</th>
                                    <th class="py-2.5 px-3 border-r border-slate-200">Last Inspected</th>
                                    <th class="py-2.5 px-3 text-right">Quick Controls</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 font-medium whitespace-nowrap">
                                <tr v-for="(svc, index) in systemServices" :key="svc.id" class="hover:bg-blue-50/30 transition">
                                    <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                        {{ index + 1 }}
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-slate-100 text-left font-bold text-slate-900 whitespace-nowrap">
                                        {{ svc.display_name || svc.service_name }}
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-slate-100 font-mono text-slate-600 text-xs whitespace-nowrap">
                                        {{ svc.service_name }}.service
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap">
                                        <ServerStatusBadge :status="svc.status" size="xs" />
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 text-xs font-mono whitespace-nowrap">
                                        {{ formatDate(svc.last_checked_at) }}
                                    </td>
                                    <td class="py-2.5 px-3 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button 
                                                type="button" 
                                                class="px-2 py-1 bg-white hover:bg-slate-50 text-slate-700 rounded-[3px] text-xs font-bold transition flex items-center gap-1 border border-slate-200 shadow-2xs cursor-pointer"
                                                @click="openServiceAction(svc.service_name, 'restart')"
                                            >
                                                <ArrowPathIcon class="w-3.5 h-3.5 text-slate-500" />
                                                <span>Restart</span>
                                            </button>
                                            <button 
                                                v-if="svc.status === 'running'" 
                                                type="button" 
                                                class="px-2 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-[3px] text-xs font-bold transition flex items-center gap-1 border border-rose-200 shadow-2xs cursor-pointer"
                                                title="Stop Service"
                                                @click="openServiceAction(svc.service_name, 'stop')"
                                            >
                                                <PauseIcon class="w-3.5 h-3.5 text-rose-600" />
                                                <span>Stop</span>
                                            </button>
                                            <button 
                                                v-else 
                                                type="button" 
                                                class="px-2 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-[3px] text-xs font-bold transition flex items-center gap-1 border border-emerald-200 shadow-2xs cursor-pointer"
                                                title="Start Service"
                                                @click="openServiceAction(svc.service_name, 'start')"
                                            >
                                                <PlayIcon class="w-3.5 h-3.5 text-emerald-600" />
                                                <span>Start</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <tr v-if="systemServices.length === 0">
                                    <td colspan="6" class="py-10 text-center text-slate-400">
                                        No services discovered yet. Click "Discover" in toolbar to probe active systemd services.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 3: MULTI-PHP RUNTIME TABLE -->
            <div v-if="currentTab === 'php'" class="space-y-3">
                <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                            <thead>
                                <tr class="border-b border-slate-200 bg-slate-50/90 text-[10.5px] font-bold text-slate-600 uppercase tracking-wider whitespace-nowrap">
                                    <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                    <th class="py-2.5 px-3 border-r border-slate-200 text-left">PHP Engine</th>
                                    <th class="py-2.5 px-3 border-r border-slate-200 font-mono">Daemon Service</th>
                                    <th class="py-2.5 px-3 border-r border-slate-200 font-mono">FastCGI UNIX Socket</th>
                                    <th class="py-2.5 px-3 border-r border-slate-200 font-mono">Binary Version</th>
                                    <th class="py-2.5 px-3 border-r border-slate-200">Status</th>
                                    <th class="py-2.5 px-3 border-r border-slate-200">Last Inspected</th>
                                    <th class="py-2.5 px-3 text-right">Quick Controls</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 font-medium">
                                <tr v-for="(php, index) in phpServices" :key="php.id" class="hover:bg-blue-50/30 transition">
                                    <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                        {{ index + 1 }}
                                    </td>

                                    <!-- PHP Engine -->
                                    <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-black text-[10px] border border-blue-100 shrink-0">
                                                {{ php.service_name.replace('php', '').replace('-fpm', '') }}
                                            </div>
                                            <span class="font-bold text-slate-900">
                                                {{ php.display_name || php.service_name }}
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Daemon Unit -->
                                    <td class="py-2.5 px-3 border-r border-slate-100 font-mono text-slate-600 text-xs whitespace-nowrap">
                                        {{ php.service_name }}.service
                                    </td>

                                    <!-- UNIX Socket -->
                                    <td class="py-2.5 px-3 border-r border-slate-100 font-mono text-slate-700 text-xs whitespace-nowrap">
                                        <span class="bg-slate-100 px-1.5 py-0.5 rounded-[3px] text-[10.5px] border border-slate-200">
                                            /run/php/{{ php.service_name }}.sock
                                        </span>
                                    </td>

                                    <!-- Binary Version -->
                                    <td class="py-2.5 px-3 border-r border-slate-100 font-mono text-slate-700 text-xs whitespace-nowrap">
                                        {{ php.version || 'PHP ' + php.service_name.replace('php', '').replace('-fpm', '') }}
                                    </td>

                                    <!-- Status -->
                                    <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap">
                                        <ServerStatusBadge :status="php.status" size="xs" />
                                    </td>

                                    <!-- Last Inspected -->
                                    <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 text-xs font-mono whitespace-nowrap">
                                        {{ formatDate(php.last_checked_at) }}
                                    </td>

                                    <!-- Quick Controls -->
                                    <td class="py-2.5 px-3 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button 
                                                type="button" 
                                                class="px-2 py-1 bg-white hover:bg-slate-50 text-slate-700 rounded-[3px] text-xs font-bold transition flex items-center gap-1 border border-slate-200 shadow-2xs cursor-pointer"
                                                @click="openServiceAction(php.service_name, 'restart')"
                                            >
                                                <ArrowPathIcon class="w-3.5 h-3.5 text-slate-500" />
                                                <span>Restart</span>
                                            </button>

                                            <button 
                                                v-if="php.status === 'running'" 
                                                type="button" 
                                                class="px-2 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-[3px] text-xs font-bold transition flex items-center gap-1 border border-rose-200 shadow-2xs cursor-pointer"
                                                title="Stop FPM Pool"
                                                @click="openServiceAction(php.service_name, 'stop')"
                                            >
                                                <PauseIcon class="w-3.5 h-3.5 text-rose-600" />
                                                <span>Stop</span>
                                            </button>

                                            <button 
                                                v-else 
                                                type="button" 
                                                class="px-2 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-[3px] text-xs font-bold transition flex items-center gap-1 border border-emerald-200 shadow-2xs cursor-pointer"
                                                title="Start FPM Pool"
                                                @click="openServiceAction(php.service_name, 'start')"
                                            >
                                                <PlayIcon class="w-3.5 h-3.5 text-emerald-600" />
                                                <span>Start</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <tr v-if="phpServices.length === 0">
                                    <td colspan="8" class="py-10 text-center text-slate-400">
                                        No PHP-FPM pools discovered yet. Click "Probe Installed PHP" to scan FastCGI engines.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 4: EVENTS AUDIT -->
            <div v-if="currentTab === 'events'" class="space-y-3">
                <div class="bg-white rounded-[4px] border border-[#E2E8F0] divide-y divide-slate-100 shadow-2xs overflow-hidden">
                    <div 
                        v-for="evt in server.events" 
                        :key="evt.id"
                        class="p-3 flex items-start gap-2.5 hover:bg-slate-50 transition-colors"
                    >
                        <div 
                            :class="[
                                'w-6 h-6 rounded-[3px] flex items-center justify-center shrink-0 mt-0.5 border',
                                evt.severity === 'critical' || evt.severity === 'emergency' ? 'bg-rose-50 text-rose-600 border-rose-100' : evt.severity === 'warning' ? 'bg-amber-50 text-amber-600 border-amber-100' : 'bg-blue-50 text-blue-600 border-blue-100'
                            ]"
                        >
                            <ShieldCheckIcon class="w-3.5 h-3.5" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-bold text-xs text-slate-900 font-mono">{{ evt.event_type }}</span>
                                <span class="text-[10.5px] text-slate-400 font-mono">{{ formatDate(evt.occurred_at) }}</span>
                            </div>
                            <p class="text-xs text-slate-600 mt-0.5">{{ evt.message }}</p>
                        </div>
                    </div>

                    <div v-if="!server.events || server.events.length === 0" class="p-10 text-center text-xs text-slate-400">
                        No security or operational events recorded yet.
                    </div>
                </div>
            </div>

            <!-- TAB 5: LOGS -->
            <div v-if="currentTab === 'logs'" class="space-y-3">
                <div class="bg-white rounded-[4px] border border-[#E2E8F0] divide-y divide-slate-100 font-mono text-xs shadow-2xs overflow-hidden">
                    <div v-for="log in server.logs" :key="log.id" class="p-2.5 flex items-start gap-2 hover:bg-slate-50">
                        <span class="text-slate-400 text-[10.5px] shrink-0 font-mono">{{ formatDate(log.occurred_at) }}</span>
                        <span class="px-1.5 py-0.2 rounded-[2px] text-[9.5px] font-bold uppercase bg-slate-100 text-slate-700 shrink-0">{{ log.level }}</span>
                        <span class="text-slate-800 flex-1 font-sans">{{ log.message }}</span>
                    </div>

                    <div v-if="!server.logs || server.logs.length === 0" class="p-10 text-center text-xs text-slate-400 font-sans">
                        No technical logs recorded for this node.
                    </div>
                </div>
            </div>

            <!-- TAB 6: SECURITY & SSH -->
            <div v-if="currentTab === 'security'" class="space-y-3.5">
                <!-- OpenSSH Host Key Cryptographic Pinning Card -->
                <div class="bg-white p-4 rounded-[4px] border border-[#E2E8F0] shadow-2xs space-y-3.5">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 pb-2.5 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center border border-blue-100 shrink-0">
                                <ShieldCheckIcon class="w-3.5 h-3.5" />
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">OpenSSH Host Key Cryptographic Pinning</h3>
                                    <span 
                                        v-if="server.trusted_ssh_host_key_fingerprint && server.trusted_ssh_host_key_fingerprint === server.ssh_host_key_fingerprint"
                                        class="px-1.5 py-0.2 rounded-[3px] text-[10px] font-bold uppercase bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center gap-1"
                                    >
                                        <CheckCircleIcon class="w-3 h-3" />
                                        Pinned & Verified
                                    </span>
                                    <span 
                                        v-else-if="hasHostKeyMismatch"
                                        class="px-1.5 py-0.2 rounded-[3px] text-[10px] font-bold uppercase bg-rose-50 text-rose-700 border border-rose-200"
                                    >
                                        Mismatch Alert
                                    </span>
                                </div>
                                <p class="text-[10.5px] text-slate-400">Strict cryptographic pinning prevents Man-in-the-Middle (MitM) SSH spoofing</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <button 
                                type="button" 
                                class="px-2.5 py-1 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                                @click="showHostKeyModal = true"
                            >
                                <KeyIcon class="w-3.5 h-3.5 text-slate-500" />
                                <span>Review & Rotate Pin</span>
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
                        <!-- Trusted Fingerprint -->
                        <div class="p-3 rounded-[3px] bg-slate-50/70 border border-slate-200 space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Trusted Pinned Fingerprint (SHA256)</span>
                                <button 
                                    v-if="server.trusted_ssh_host_key_fingerprint"
                                    type="button"
                                    class="text-slate-400 hover:text-slate-700 transition"
                                    @click="copyFingerprint(server.trusted_ssh_host_key_fingerprint)"
                                >
                                    <ClipboardDocumentCheckIcon v-if="copiedFingerprint" class="w-3.5 h-3.5 text-emerald-600" />
                                    <ClipboardDocumentIcon v-else class="w-3.5 h-3.5" />
                                </button>
                            </div>
                            <div class="font-mono text-xs font-bold text-slate-900 break-all">
                                {{ server.trusted_ssh_host_key_fingerprint || 'Not yet pinned' }}
                            </div>
                        </div>

                        <!-- Observed Fingerprint -->
                        <div class="p-3 rounded-[3px] bg-slate-50/70 border border-slate-200 space-y-1">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Latest Live Observed Fingerprint</span>
                            <div class="font-mono text-xs font-bold text-slate-900 break-all">
                                {{ server.ssh_host_key_fingerprint || 'Pending initial connection' }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Credential Security Card -->
                <div class="bg-white p-4 rounded-[4px] border border-[#E2E8F0] shadow-2xs space-y-3.5">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 pb-2.5 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-[3px] bg-emerald-50 text-emerald-600 flex items-center justify-center border border-emerald-100 shrink-0">
                                <LockClosedIcon class="w-3.5 h-3.5" />
                            </div>
                            <div>
                                <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">Hardware Encrypted SSH Credentials</h3>
                                <p class="text-[10.5px] text-slate-400">Encrypted via AES-256-GCM. Secret values are masked and never exposed to browser context.</p>
                            </div>
                        </div>

                        <button 
                            type="button" 
                            class="px-2.5 py-1 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                            @click="showCredentialsModal = true"
                        >
                            <KeyIcon class="w-3.5 h-3.5 text-slate-500" />
                            <span>Rotate Secret Credentials</span>
                        </button>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 text-xs">
                        <div class="p-2.5 bg-slate-50/70 rounded-[3px] border border-slate-100">
                            <span class="text-slate-400 block text-[9.5px] font-bold uppercase tracking-wider">Authentication Type</span>
                            <strong class="text-slate-900 font-bold uppercase mt-0.5 block">{{ server.auth_type || 'PASSWORD' }}</strong>
                        </div>

                        <div class="p-2.5 bg-slate-50/70 rounded-[3px] border border-slate-100">
                            <span class="text-slate-400 block text-[9.5px] font-bold uppercase tracking-wider">SSH Daemon User</span>
                            <strong class="font-mono text-slate-900 font-bold mt-0.5 block">{{ server.ssh_user }}</strong>
                        </div>

                        <div class="p-2.5 bg-slate-50/70 rounded-[3px] border border-slate-100">
                            <span class="text-slate-400 block text-[9.5px] font-bold uppercase tracking-wider">SSH Network Port</span>
                            <strong class="font-mono text-slate-900 font-bold mt-0.5 block">{{ server.ssh_port }}</strong>
                        </div>

                        <div class="p-2.5 bg-slate-50/70 rounded-[3px] border border-slate-100">
                            <span class="text-slate-400 block text-[9.5px] font-bold uppercase tracking-wider">Last Rotated</span>
                            <strong class="text-slate-900 font-bold mt-0.5 block">{{ formatDate(server.credentials_rotated_at) }}</strong>
                        </div>
                    </div>
                </div>

                <!-- Danger Zone -->
                <div class="bg-rose-50/50 p-4 rounded-[4px] border border-rose-200 shadow-2xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <strong class="text-xs font-bold text-rose-900 block">Decommission Node from Infrastructure</strong>
                        <p class="text-[11px] text-rose-700 mt-0.5">Permanently unbind this node from the HostingOS control plane. Active websites must be migrated first.</p>
                    </div>

                    <button 
                        type="button" 
                        class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-[3px] text-xs shadow-2xs transition cursor-pointer shrink-0"
                        @click="showDeleteModal = true"
                    >
                        Decommission Node
                    </button>
                </div>
            </div>
        </div>

        <!-- Modals -->
        <MaintenanceModal
            v-if="showMaintenanceModal"
            :show="showMaintenanceModal"
            :server="server"
            @close="showMaintenanceModal = false"
        />

        <HostKeyApprovalModal
            v-if="showHostKeyModal"
            :show="showHostKeyModal"
            :server="server"
            @close="showHostKeyModal = false"
        />

        <ServiceOperationModal
            v-if="showServiceModal"
            :show="showServiceModal"
            :server-id="server.id"
            :service-name="selectedService"
            :action="selectedServiceAction"
            @close="showServiceModal = false"
        />

        <CredentialRotationModal
            v-if="showCredentialsModal"
            :show="showCredentialsModal"
            :server="server"
            :auth-types="['password', 'key']"
            @close="showCredentialsModal = false"
        />

        <DeleteServerModal
            v-if="showDeleteModal"
            :show="showDeleteModal"
            :server="server"
            @close="showDeleteModal = false"
        />
    </AdminLayout>
</template>
