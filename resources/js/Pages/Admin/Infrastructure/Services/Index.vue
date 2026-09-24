<script setup>
import { ref } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'
import DataTableFilter from '@/Components/UI/DataTableFilter.vue'
import FilterSelect from '@/Components/UI/FilterSelect.vue'
import RowActionDropdown from '@/Components/UI/RowActionDropdown.vue'
import ServerStatusBadge from '@/Components/Admin/Servers/ServerStatusBadge.vue'

import {
    WrenchScrewdriverIcon,
    ServerIcon,
    ArrowPathIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    XCircleIcon,
    PlayIcon,
    PauseIcon,
    GlobeAltIcon,
    SparklesIcon,
    Square2StackIcon,
    BoltIcon,
    XMarkIcon,
    ClockIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    services: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            total_services: 0,
            running_services: 0,
            stopped_services: 0,
            failed_services: 0,
            types: {},
            last_inspected_at: null,
        }),
    },
    servers: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({ search: '', type: '', status: '', server_id: '' }),
    },
})

// Search & Filter State
const search = ref(props.filters?.search || '')
const type = ref(props.filters?.type || '')
const status = ref(props.filters?.status || '')
const serverId = ref(props.filters?.server_id || '')

const applyFilters = () => {
    router.get(route('admin.infrastructure.services'), {
        search: search.value || undefined,
        type: type.value || undefined,
        status: status.value || undefined,
        server_id: serverId.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const resetFilters = () => {
    search.value = ''
    type.value = ''
    status.value = ''
    serverId.value = ''
    applyFilters()
}

// Sync State
const isSyncing = ref(false)
const syncFeedback = ref('')

const triggerSyncAll = () => {
    isSyncing.value = true
    router.post(route('admin.infrastructure.services.sync-all'), {}, {
        preserveScroll: true,
        onSuccess: () => {
            isSyncing.value = false
            syncFeedback.value = 'All background daemons and FastCGI sockets verified across cluster.'
            setTimeout(() => {
                syncFeedback.value = ''
            }, 4000)
        },
        onError: () => {
            isSyncing.value = false
        },
        onFinish: () => {
            isSyncing.value = false
        }
    })
}

// Service Action Modal State
const showActionModal = ref(false)
const selectedService = ref(null)
const selectedAction = ref('')

const actionForm = useForm({
    action: '',
})

const openActionModal = (service, action) => {
    selectedService.value = service
    selectedAction.value = action
    showActionModal.value = true
}

const submitServiceAction = () => {
    if (!selectedService.value || !selectedAction.value) return
    actionForm.action = selectedAction.value
    actionForm.post(route('admin.infrastructure.services.action', selectedService.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showActionModal.value = false
            syncFeedback.value = `Service ${selectedService.value.service_name} ${selectedAction.value}ed successfully.`
            setTimeout(() => {
                syncFeedback.value = ''
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

const getCategoryBadgeClass = (category) => {
    switch (category) {
        case 'webserver':
            return 'bg-sky-50 text-sky-700 border-sky-200'
        case 'database':
            return 'bg-emerald-50 text-emerald-700 border-emerald-200'
        case 'cache':
            return 'bg-amber-50 text-amber-700 border-amber-200'
        case 'runtime':
            return 'bg-purple-50 text-purple-700 border-purple-200'
        case 'security':
            return 'bg-rose-50 text-rose-700 border-rose-200'
        default:
            return 'bg-slate-100 text-slate-700 border-slate-200'
    }
}

const getCategoryLabel = (category) => {
    switch (category) {
        case 'webserver':
            return 'Web Engine'
        case 'database':
            return 'Database'
        case 'cache':
            return 'Memory Store'
        case 'runtime':
            return 'FastCGI Pool'
        case 'security':
            return 'Security Shield'
        default:
            return 'Core System'
    }
}
</script>

<template>
    <Head title="Cluster Background Services - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Infrastructure', href: route('admin.servers.index') },
                    { label: 'Service Control' }
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
                        @click="triggerSyncAll"
                        :disabled="isSyncing"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer disabled:opacity-60"
                    >
                        <ArrowPathIcon v-if="isSyncing" class="w-3.5 h-3.5 animate-spin" />
                        <SparklesIcon v-else class="w-3.5 h-3.5" />
                        <span>{{ isSyncing ? 'Verifying Daemons...' : 'Verify All Daemons' }}</span>
                    </button>
                </template>
            </PageHeader>

            <!-- Feedback Banner -->
            <div v-if="syncFeedback" class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-3 py-2 rounded-[3px] text-xs font-bold flex items-center justify-between shadow-2xs">
                <div class="flex items-center gap-2">
                    <CheckCircleIcon class="w-4 h-4 text-emerald-600" />
                    <span>{{ syncFeedback }}</span>
                </div>
                <button @click="syncFeedback = ''" class="text-emerald-600 hover:text-emerald-800 text-xs cursor-pointer">✕</button>
            </div>

            <!-- 2. Ultra-Compact KPI Info Cards (56px) -->
            <InfoCardsGrid :cols="4">
                <InfoCard
                    title="Total Services"
                    :value="String(stats.total_services || 0)"
                    badge="Units"
                    badgeType="info"
                    color="blue"
                    :icon="WrenchScrewdriverIcon"
                />

                <InfoCard
                    title="Running Daemons"
                    :value="String(stats.running_services || 0)"
                    :badge="`${stats.total_services || 0} Active`"
                    badgeType="success"
                    color="emerald"
                    :icon="CheckCircleIcon"
                />

                <InfoCard
                    title="Service Tiers"
                    :value="'6'"
                    badge="Categories"
                    badgeType="purple"
                    color="purple"
                    :icon="Square2StackIcon"
                />

                <InfoCard
                    title="Daemon SLA"
                    :value="'100%'"
                    badge="Available"
                    badgeType="success"
                    color="sky"
                    :icon="BoltIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Standard Unified Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search daemons by name, socket, or port..."
                @search="applyFilters"
                @filter="applyFilters"
                @reset="resetFilters"
            >
                <FilterSelect
                    v-model="type"
                    label="Service Tier"
                    :options="[
                        { label: 'Web Engine (Nginx)', value: 'webserver' },
                        { label: 'Database (MySQL)', value: 'database' },
                        { label: 'Memory Store (Redis)', value: 'cache' },
                        { label: 'FastCGI Runtime (PHP)', value: 'runtime' },
                        { label: 'Security (Fail2Ban)', value: 'security' },
                        { label: 'Core System (SSH, Cron)', value: 'system' }
                    ]"
                    placeholder="All Service Tiers"
                    @change="applyFilters"
                />

                <FilterSelect
                    v-if="servers && servers.length > 1"
                    v-model="serverId"
                    label="Host Node"
                    :options="servers.map(srv => ({ label: `${srv.name} (${srv.ip_address})`, value: srv.id }))"
                    placeholder="All Server Nodes"
                    @change="applyFilters"
                />

                <FilterSelect
                    v-model="status"
                    label="Status"
                    :options="[
                        { label: 'Running', value: 'running' },
                        { label: 'Stopped', value: 'stopped' },
                        { label: 'Failed', value: 'failed' }
                    ]"
                    placeholder="All Statuses"
                    @change="applyFilters"
                />
            </DataTableFilter>

            <!-- 4. Services Matrix Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Service Daemon</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Category Tier</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Host Node</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Port / Socket</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Version</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Status</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Last Inspected</th>
                                <th class="py-2.5 px-3 w-16">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                            <tr v-for="(srv, index) in services" :key="srv.id" class="hover:bg-blue-50/30 transition">
                                <!-- # -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>

                                <!-- Service Name -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-[3px] bg-slate-100 text-slate-700 flex items-center justify-center font-bold text-[10px] border border-slate-200 font-mono shrink-0">
                                            {{ srv.service_name.substring(0, 3).toUpperCase() }}
                                        </div>
                                        <div>
                                            <strong class="text-slate-900 font-bold block">{{ srv.display_name }}</strong>
                                            <span class="text-[10px] font-mono text-slate-400 block">{{ srv.service_name }}.service</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Category -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap">
                                    <span 
                                        :class="getCategoryBadgeClass(srv.service_type)"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border"
                                    >
                                        {{ getCategoryLabel(srv.service_type) }}
                                    </span>
                                </td>

                                <!-- Host Node -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap">
                                    <Link 
                                        v-if="srv.server"
                                        :href="route('admin.servers.show', srv.server.id)"
                                        class="font-bold text-slate-900 hover:text-blue-600 block"
                                    >
                                        {{ srv.server.name }}
                                        <span class="text-[10px] font-mono text-slate-400 block font-normal">{{ srv.server.ip_address }}</span>
                                    </Link>
                                    <span v-else class="text-slate-400">Cluster Host</span>
                                </td>

                                <!-- Port / Socket -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap font-mono text-[11px]">
                                    <span v-if="srv.port" class="text-slate-800 font-bold bg-slate-100 px-1.5 py-0.5 rounded-[2px] border border-slate-200">
                                        Port: {{ srv.port }}
                                    </span>
                                    <span v-else-if="srv.metadata && srv.metadata.socket" class="text-slate-600 bg-slate-50 px-1.5 py-0.5 rounded-[2px] border border-slate-100 text-[10px]">
                                        {{ srv.metadata.socket }}
                                    </span>
                                    <span v-else class="text-slate-400">Internal Daemon</span>
                                </td>

                                <!-- Version -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap font-mono text-[11px] text-slate-600">
                                    {{ srv.version || 'System Default' }}
                                </td>

                                <!-- Status -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap">
                                    <ServerStatusBadge :status="srv.status || 'running'" />
                                </td>

                                <!-- Last Inspected -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-slate-400 font-mono text-[10.5px]">
                                    {{ formatDate(srv.last_checked_at) }}
                                </td>

                                <!-- Controls Dropdown -->
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <RowActionDropdown>
                                        <button 
                                            type="button" 
                                            @click="openActionModal(srv, 'restart')"
                                            class="w-full text-left px-3 py-1.5 text-xs text-blue-600 hover:bg-blue-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <ArrowPathIcon class="w-3.5 h-3.5" />
                                            <span>Restart Service</span>
                                        </button>

                                        <button 
                                            v-if="srv.status === 'running' || srv.status === 'active'"
                                            type="button" 
                                            @click="openActionModal(srv, 'stop')"
                                            class="w-full text-left px-3 py-1.5 text-xs text-rose-600 hover:bg-rose-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <PauseIcon class="w-3.5 h-3.5" />
                                            <span>Stop Service</span>
                                        </button>

                                        <button 
                                            v-else
                                            type="button" 
                                            @click="openActionModal(srv, 'start')"
                                            class="w-full text-left px-3 py-1.5 text-xs text-emerald-600 hover:bg-emerald-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <PlayIcon class="w-3.5 h-3.5" />
                                            <span>Start Service</span>
                                        </button>
                                    </RowActionDropdown>
                                </td>
                            </tr>

                            <tr v-if="!services || services.length === 0">
                                <td colspan="9" class="py-12 text-center text-slate-400">
                                    No services matching the selected filters found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- SERVICE ACTION CONFIRMATION MODAL -->
        <div v-if="showActionModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <WrenchScrewdriverIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Confirm Service {{ selectedAction.toUpperCase() }}
                        </h3>
                    </div>
                    <button @click="showActionModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-3 text-xs">
                    <p class="text-slate-600">
                        Are you sure you want to execute <strong class="text-slate-900 uppercase font-bold">{{ selectedAction }}</strong> on the following background daemon?
                    </p>

                    <div class="bg-slate-50/70 p-3 rounded-[3px] border border-slate-200/80 space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400 font-bold text-[10px] uppercase">Daemon Unit:</span>
                            <span class="font-mono text-slate-900 font-bold">{{ selectedService?.service_name }}.service</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400 font-bold text-[10px] uppercase">Service Name:</span>
                            <span class="text-slate-900 font-semibold">{{ selectedService?.display_name }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400 font-bold text-[10px] uppercase">Target Node:</span>
                            <span class="text-slate-900 font-semibold">{{ selectedService?.server?.name }} ({{ selectedService?.server?.ip_address }})</span>
                        </div>
                    </div>
                </div>

                <div class="px-4 py-2.5 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                    <button
                        type="button"
                        @click="showActionModal = false"
                        class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="submitServiceAction"
                        :disabled="actionForm.processing"
                        :class="[
                            'px-3.5 py-1.5 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50',
                            selectedAction === 'stop' ? 'bg-rose-600 hover:bg-rose-700' : 'bg-blue-600 hover:bg-blue-700'
                        ]"
                    >
                        {{ actionForm.processing ? 'Executing...' : `Confirm ${selectedAction.toUpperCase()}` }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
