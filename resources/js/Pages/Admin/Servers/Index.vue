<script setup>
import { ref, computed, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

// Global Standard UI Components
import PageHeader from '@/Components/UI/PageHeader.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'
import DataTableFilter from '@/Components/UI/DataTableFilter.vue'
import FilterSelect from '@/Components/UI/FilterSelect.vue'
import DataTable from '@/Components/UI/DataTable.vue'
import RowActionDropdown from '@/Components/UI/RowActionDropdown.vue'
import Pagination from '@/Components/UI/Pagination.vue'

// Server Specific Badges & Modals
import ServerStatusBadge from '@/Components/Admin/Servers/ServerStatusBadge.vue'
import ServerHealthBadge from '@/Components/Admin/Servers/ServerHealthBadge.vue'
import MaintenanceModal from '@/Components/Admin/Servers/MaintenanceModal.vue'
import HostKeyApprovalModal from '@/Components/Admin/Servers/HostKeyApprovalModal.vue'
import CredentialRotationModal from '@/Components/Admin/Servers/CredentialRotationModal.vue'
import DeleteServerModal from '@/Components/Admin/Servers/DeleteServerModal.vue'

import {
    ServerIcon,
    PlusIcon,
    ShieldCheckIcon,
    ExclamationTriangleIcon,
    WrenchScrewdriverIcon,
    CheckCircleIcon,
    ArrowPathIcon,
    EyeIcon,
    PencilSquareIcon,
    KeyIcon,
    TrashIcon,
    DocumentDuplicateIcon,
    CheckIcon,
    CpuChipIcon,
    ChevronUpIcon,
    ChevronDownIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    servers: {
        type: Object,
        required: true,
    },
    stats: {
        type: Object,
        default: () => ({}),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    serverGroups: {
        type: Array,
        default: () => [],
    },
    serverTypes: {
        type: Array,
        default: () => [],
    },
    serverStatuses: {
        type: Array,
        default: () => [],
    },
    healthStatuses: {
        type: Array,
        default: () => [],
    },
})

// Search & Filter state
const search = ref(props.filters?.search || '')
const status = ref(props.filters?.status || '')
const healthStatus = ref(props.filters?.health_status || '')
const serverGroupId = ref(props.filters?.server_group_id || '')
const sortBy = ref(props.filters?.sort || 'created_at')
const sortDirection = ref(props.filters?.direction || 'desc')

// Modals state
const activeServer = ref(null)
const showMaintenanceModal = ref(false)
const showHostKeyModal = ref(false)
const showCredentialsModal = ref(false)
const showDeleteModal = ref(false)

// Copy to clipboard state
const copiedIp = ref(null)
const copyToClipboard = (ip) => {
    navigator.clipboard.writeText(ip)
    copiedIp.value = ip
    setTimeout(() => {
        if (copiedIp.value === ip) {
            copiedIp.value = null
        }
    }, 2000)
}

// Debounced search
let searchTimer = null
watch(search, () => {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(() => {
        applyFilters()
    }, 300)
})

const applyFilters = () => {
    router.get(route('admin.servers.index'), {
        search: search.value || undefined,
        status: status.value || undefined,
        health_status: healthStatus.value || undefined,
        server_group_id: serverGroupId.value || undefined,
        sort: sortBy.value || undefined,
        direction: sortDirection.value || undefined,
        per_page: props.filters?.per_page || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    })
}

const clearFilters = () => {
    search.value = ''
    status.value = ''
    healthStatus.value = ''
    serverGroupId.value = ''
    sortBy.value = 'created_at'
    sortDirection.value = 'desc'
    router.get(route('admin.servers.index'), {}, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    })
}

const toggleSort = (column) => {
    if (sortBy.value === column) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
    } else {
        sortBy.value = column
        sortDirection.value = 'asc'
    }
    applyFilters()
}

const hasActiveFilters = computed(() => {
    return !!(search.value || status.value || healthStatus.value || serverGroupId.value)
})

const getCpuPercentage = (server) => {
    if (server.latest_metric && server.latest_metric.cpu_usage !== undefined && server.latest_metric.cpu_usage > 0) {
        return Math.round(server.latest_metric.cpu_usage)
    }
    if (server.load_avg_1min !== undefined && server.load_avg_1min > 0) {
        const cores = server.cpu_cores || 1
        return Math.min(100, Math.round((server.load_avg_1min / cores) * 100))
    }
    return 0
}

const getRamPercentage = (server) => {
    if (server.latest_metric && server.latest_metric.ram_usage !== undefined && server.latest_metric.ram_usage > 0) {
        return Math.round(server.latest_metric.ram_usage)
    }
    if (server.ram_total && server.ram_used) {
        return Math.round((server.ram_used / server.ram_total) * 100)
    }
    return 0
}

const getDiskPercentage = (server) => {
    if (server.latest_metric && server.latest_metric.disk_usage !== undefined && server.latest_metric.disk_usage > 0) {
        return Math.round(server.latest_metric.disk_usage)
    }
    if (server.disk_total && server.disk_used) {
        return Math.round((server.disk_used / server.disk_total) * 100)
    }
    return 0
}

// Modal triggers
const openMaintenance = (server) => {
    activeServer.value = server
    showMaintenanceModal.value = true
}

const openHostKey = (server) => {
    activeServer.value = server
    showHostKeyModal.value = true
}

const openCredentials = (server) => {
    activeServer.value = server
    showCredentialsModal.value = true
}

const openDelete = (server) => {
    activeServer.value = server
    showDeleteModal.value = true
}

// Statistics computed
const totalCount = computed(() => props.stats?.total ?? props.servers?.total ?? props.servers?.data?.length ?? 0)
const onlineCount = computed(() => props.stats?.online ?? props.servers?.data?.filter(s => s.status === 'active' || s.status === 'online').length ?? 0)
const maintenanceCount = computed(() => props.stats?.maintenance ?? props.servers?.data?.filter(s => s.status === 'maintenance').length ?? 0)
const warningCount = computed(() => props.stats?.warning ?? props.servers?.data?.filter(s => s.health_status === 'warning' || s.health_status === 'critical').length ?? 0)
</script>

<template>
    <Head title="Servers & Nodes Management - DeepTouchHost" />

    <AdminLayout>
        <div class="max-w-7xl mx-auto space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header (Only Breadcrumbs & Actions) -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Infrastructure', href: '#' },
                    { label: 'Servers & Nodes' }
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

                    <Link 
                        :href="route('admin.servers.create')" 
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <PlusIcon class="w-3.5 h-3.5" />
                        <span>Add Server Node</span>
                    </Link>
                </template>
            </PageHeader>

            <!-- 2. Smart & Compact Info Cards Row (Height: ~76px, Modern SaaS Look) -->
            <InfoCardsGrid :cols="4">
                <InfoCard
                    title="Total Server Nodes"
                    :value="totalCount"
                    :icon="ServerIcon"
                    color="indigo"
                    badge="Infrastructure"
                    badgeType="info"
                />
                <InfoCard
                    title="Online & Verified"
                    :value="onlineCount"
                    :icon="CheckCircleIcon"
                    color="emerald"
                    badge="Operational"
                    badgeType="success"
                />
                <InfoCard
                    title="In Maintenance"
                    :value="maintenanceCount"
                    :icon="WrenchScrewdriverIcon"
                    color="amber"
                    badge="Scheduled"
                    badgeType="warning"
                />
                <InfoCard
                    title="Degraded / Alerts"
                    :value="warningCount"
                    :icon="ExclamationTriangleIcon"
                    color="rose"
                    :badge="warningCount > 0 ? `${warningCount} Issues` : 'Healthy'"
                    :badgeType="warningCount > 0 ? 'danger' : 'success'"
                />
            </InfoCardsGrid>

            <!-- 3. Slim Unified Filter Bar with Custom Selects, Filter & Reset Buttons -->
            <DataTableFilter
                v-model:search="search"
                placeholder="Search server name, hostname, IP..."
                :hasActiveFilters="hasActiveFilters"
                @apply="applyFilters"
                @clear="clearFilters"
            >
                <template #filters>
                    <!-- Status Filter -->
                    <FilterSelect
                        v-model="status"
                        placeholder="All Statuses"
                        :options="serverStatuses"
                    />

                    <!-- Health Status Filter -->
                    <FilterSelect
                        v-model="healthStatus"
                        placeholder="All Health"
                        :options="healthStatuses"
                    />

                    <!-- Server Group Filter -->
                    <FilterSelect
                        v-model="serverGroupId"
                        placeholder="All Clusters"
                    >
                        <option v-for="g in serverGroups" :key="g.id" :value="g.id">{{ g.name }}</option>
                    </FilterSelect>
                </template>
            </DataTableFilter>

            <!-- 4. SM Bordered, Centered Header, Sortable Columns & Whitespace-Nowrap Data Table -->
            <DataTable :items="servers.data || []" emptyMessage="No server nodes found matching your query.">
                <!-- Centered Table Header Row with Clickable Sorting Chevrons -->
                <template #header>
                    <!-- # Index Column -->
                    <th 
                        @click="toggleSort('id')"
                        class="py-2.5 px-2.5 text-center w-12 border-r border-slate-200 cursor-pointer hover:bg-slate-100 transition-colors"
                        title="Sort by ID"
                    >
                        <div class="flex items-center justify-center gap-1">
                            <span>#</span>
                            <span v-if="sortBy === 'id'" class="text-blue-600">
                                <ChevronUpIcon v-if="sortDirection === 'asc'" class="w-3 h-3" />
                                <ChevronDownIcon v-else class="w-3 h-3" />
                            </span>
                        </div>
                    </th>

                    <!-- Status Column -->
                    <th 
                        @click="toggleSort('status')"
                        class="py-2.5 px-3 text-center w-24 border-r border-slate-200 cursor-pointer hover:bg-slate-100 transition-colors"
                    >
                        <div class="flex items-center justify-center gap-1">
                            <span>Status</span>
                            <span v-if="sortBy === 'status'" class="text-blue-600">
                                <ChevronUpIcon v-if="sortDirection === 'asc'" class="w-3 h-3" />
                                <ChevronDownIcon v-else class="w-3 h-3" />
                            </span>
                        </div>
                    </th>

                    <!-- Server Node Column -->
                    <th 
                        @click="toggleSort('name')"
                        class="py-2.5 px-3 text-center min-w-[200px] border-r border-slate-200 cursor-pointer hover:bg-slate-100 transition-colors"
                    >
                        <div class="flex items-center justify-center gap-1">
                            <span>Server Node</span>
                            <span v-if="sortBy === 'name'" class="text-blue-600">
                                <ChevronUpIcon v-if="sortDirection === 'asc'" class="w-3 h-3" />
                                <ChevronDownIcon v-else class="w-3 h-3" />
                            </span>
                        </div>
                    </th>

                    <!-- IP Address Column -->
                    <th 
                        @click="toggleSort('ip_address')"
                        class="py-2.5 px-3 text-center w-40 border-r border-slate-200 cursor-pointer hover:bg-slate-100 transition-colors"
                    >
                        <div class="flex items-center justify-center gap-1">
                            <span>IP & Port</span>
                            <span v-if="sortBy === 'ip_address'" class="text-blue-600">
                                <ChevronUpIcon v-if="sortDirection === 'asc'" class="w-3 h-3" />
                                <ChevronDownIcon v-else class="w-3 h-3" />
                            </span>
                        </div>
                    </th>

                    <!-- Cluster Column -->
                    <th class="py-2.5 px-3 text-center w-28 border-r border-slate-200">
                        <span>Cluster</span>
                    </th>

                    <!-- CPU Column -->
                    <th class="py-2.5 px-3 text-center w-24 border-r border-slate-200">
                        <span>CPU</span>
                    </th>

                    <!-- RAM Column -->
                    <th class="py-2.5 px-3 text-center w-24 border-r border-slate-200">
                        <span>RAM</span>
                    </th>

                    <!-- Disk Column -->
                    <th class="py-2.5 px-3 text-center w-24 border-r border-slate-200">
                        <span>Disk</span>
                    </th>

                    <!-- Actions Column -->
                    <th class="py-2.5 px-3 text-center w-16">
                        <span>Action</span>
                    </th>
                </template>

                <!-- Data Rows with Serial Index & Crisp Row and Column Borders -->
                <template #default>
                    <tr 
                        v-for="(server, index) in servers.data" 
                        :key="server.id"
                        class="hover:bg-slate-50/80 transition-colors text-xs"
                    >
                        <!-- # Index (Centered Serial Number) -->
                        <td class="py-2.5 px-2.5 text-center font-mono font-bold text-slate-400 border-r border-slate-100 whitespace-nowrap align-middle">
                            {{ ((servers.current_page || 1) - 1) * (servers.per_page || 15) + index + 1 }}
                        </td>

                        <!-- Status Badge (Centered Single Clean Pill) -->
                        <td class="py-2.5 px-3 text-center border-r border-slate-100 whitespace-nowrap align-middle">
                            <ServerStatusBadge :status="server.status" />
                        </td>

                        <!-- Server Node & Hostname (Left Aligned with Icon) -->
                        <td class="py-2.5 px-3 text-left border-r border-slate-100 whitespace-nowrap align-middle">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-[3px] bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-600 shrink-0">
                                    <CpuChipIcon class="w-4 h-4" />
                                </div>
                                <div class="min-w-0">
                                    <Link 
                                        :href="route('admin.servers.show', server.id)"
                                        class="font-bold text-slate-900 hover:text-blue-600 transition-colors block truncate"
                                    >
                                        {{ server.name }}
                                    </Link>
                                    <span class="text-[10px] text-slate-400 font-mono block truncate">
                                        {{ server.hostname || 'node.local' }}
                                    </span>
                                </div>
                            </div>
                        </td>

                        <!-- IP Address & SSH Port (Centered Mono) -->
                        <td class="py-2.5 px-3 text-center border-r border-slate-100 whitespace-nowrap align-middle">
                            <div class="inline-flex items-center gap-1.5">
                                <span class="font-mono font-bold text-slate-800 text-[11px]">{{ server.ip_address }}</span>
                                <button 
                                    type="button" 
                                    @click="copyToClipboard(server.ip_address)" 
                                    class="text-slate-400 hover:text-slate-700 p-0.5 rounded cursor-pointer"
                                    title="Copy IP"
                                >
                                    <CheckIcon v-if="copiedIp === server.ip_address" class="w-3.5 h-3.5 text-emerald-600" />
                                    <DocumentDuplicateIcon v-else class="w-3.5 h-3.5" />
                                </button>
                            </div>
                            <div class="text-[9.5px] text-slate-400 font-mono">SSH Port: {{ server.ssh_port || 22 }}</div>
                        </td>

                        <!-- Cluster / Group (Centered) -->
                        <td class="py-2.5 px-3 text-center border-r border-slate-100 whitespace-nowrap align-middle">
                            <span v-if="server.server_group" class="inline-block px-1.5 py-0.5 rounded-[2px] text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                {{ server.server_group.name }}
                            </span>
                            <span v-else class="text-[10px] text-slate-400 font-medium">Default Node</span>
                        </td>

                        <!-- CPU Utilization (Centered Mini Bar) -->
                        <td class="py-2.5 px-3 text-center border-r border-slate-100 whitespace-nowrap align-middle">
                            <div class="inline-block w-16">
                                <div class="flex items-center justify-between text-[9.5px] font-mono font-bold text-slate-700 mb-0.5">
                                    <span>{{ getCpuPercentage(server) }}%</span>
                                </div>
                                <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                                    <div 
                                        :class="getCpuPercentage(server) > 85 ? 'bg-rose-500' : (getCpuPercentage(server) > 60 ? 'bg-amber-500' : 'bg-blue-600')"
                                        class="h-full rounded-full transition-all"
                                        :style="{ width: getCpuPercentage(server) + '%' }"
                                    />
                                </div>
                            </div>
                        </td>

                        <!-- RAM Utilization (Centered Mini Bar) -->
                        <td class="py-2.5 px-3 text-center border-r border-slate-100 whitespace-nowrap align-middle">
                            <div class="inline-block w-16">
                                <div class="flex items-center justify-between text-[9.5px] font-mono font-bold text-slate-700 mb-0.5">
                                    <span>{{ getRamPercentage(server) }}%</span>
                                </div>
                                <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                                    <div 
                                        :class="getRamPercentage(server) > 85 ? 'bg-rose-500' : (getRamPercentage(server) > 60 ? 'bg-amber-500' : 'bg-emerald-500')"
                                        class="h-full rounded-full transition-all"
                                        :style="{ width: getRamPercentage(server) + '%' }"
                                    />
                                </div>
                            </div>
                        </td>

                        <!-- Disk Utilization (Centered Mini Bar) -->
                        <td class="py-2.5 px-3 text-center border-r border-slate-100 whitespace-nowrap align-middle">
                            <div class="inline-block w-16">
                                <div class="flex items-center justify-between text-[9.5px] font-mono font-bold text-slate-700 mb-0.5">
                                    <span>{{ getDiskPercentage(server) }}%</span>
                                </div>
                                <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                                    <div 
                                        :class="getDiskPercentage(server) > 90 ? 'bg-rose-500' : (getDiskPercentage(server) > 75 ? 'bg-amber-500' : 'bg-purple-600')"
                                        class="h-full rounded-full transition-all"
                                        :style="{ width: getDiskPercentage(server) + '%' }"
                                    />
                                </div>
                            </div>
                        </td>

                        <!-- Actions Column: Three-Dot Floating Dropdown with High Z-Index -->
                        <td class="py-2.5 px-3 text-center whitespace-nowrap align-middle">
                            <RowActionDropdown>
                                <!-- View Details -->
                                <Link 
                                    :href="route('admin.servers.show', server.id)"
                                    class="flex items-center gap-2 px-3 py-2 text-slate-700 hover:bg-slate-50 hover:text-blue-600 transition-colors"
                                >
                                    <EyeIcon class="w-3.5 h-3.5 text-slate-400" />
                                    <span>View Details</span>
                                </Link>

                                <!-- Edit Config -->
                                <Link 
                                    :href="route('admin.servers.edit', server.id)"
                                    class="flex items-center gap-2 px-3 py-2 text-slate-700 hover:bg-slate-50 hover:text-blue-600 transition-colors"
                                >
                                    <PencilSquareIcon class="w-3.5 h-3.5 text-slate-400" />
                                    <span>Edit Config</span>
                                </Link>

                                <!-- Maintenance Mode Toggle -->
                                <button 
                                    type="button" 
                                    @click="openMaintenance(server)"
                                    class="w-full text-left flex items-center gap-2 px-3 py-2 text-amber-700 hover:bg-amber-50 transition-colors cursor-pointer"
                                >
                                    <WrenchScrewdriverIcon class="w-3.5 h-3.5 text-amber-500" />
                                    <span>Maintenance</span>
                                </button>

                                <!-- Host Key Approval -->
                                <button 
                                    type="button" 
                                    @click="openHostKey(server)"
                                    class="w-full text-left flex items-center gap-2 px-3 py-2 text-slate-700 hover:bg-slate-50 hover:text-blue-600 transition-colors cursor-pointer"
                                >
                                    <ShieldCheckIcon class="w-3.5 h-3.5 text-slate-400" />
                                    <span>Host Key</span>
                                </button>

                                <!-- Rotate Credentials -->
                                <button 
                                    type="button" 
                                    @click="openCredentials(server)"
                                    class="w-full text-left flex items-center gap-2 px-3 py-2 text-slate-700 hover:bg-slate-50 hover:text-blue-600 transition-colors cursor-pointer"
                                >
                                    <KeyIcon class="w-3.5 h-3.5 text-slate-400" />
                                    <span>Rotate Keys</span>
                                </button>

                                <!-- Delete Server Node -->
                                <button 
                                    type="button" 
                                    @click="openDelete(server)"
                                    class="w-full text-left flex items-center gap-2 px-3 py-2 text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer"
                                >
                                    <TrashIcon class="w-3.5 h-3.5 text-rose-500" />
                                    <span>Delete Node</span>
                                </button>
                            </RowActionDropdown>
                        </td>
                    </tr>
                </template>

                <!-- Pagination Footer -->
                <template #footer>
                    <Pagination :links="servers.links || []" :meta="servers" />
                </template>
            </DataTable>
        </div>

        <!-- Modals -->
        <MaintenanceModal
            v-if="showMaintenanceModal"
            :show="showMaintenanceModal"
            :server="activeServer"
            @close="showMaintenanceModal = false"
        />

        <HostKeyApprovalModal
            v-if="showHostKeyModal"
            :show="showHostKeyModal"
            :server="activeServer"
            @close="showHostKeyModal = false"
        />

        <CredentialRotationModal
            v-if="showCredentialsModal"
            :show="showCredentialsModal"
            :server="activeServer"
            @close="showCredentialsModal = false"
        />

        <DeleteServerModal
            v-if="showDeleteModal"
            :show="showDeleteModal"
            :server="activeServer"
            @close="showDeleteModal = false"
        />
    </AdminLayout>
</template>
