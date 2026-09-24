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
    ShieldCheckIcon,
    CircleStackIcon,
    ExclamationTriangleIcon,
    CommandLineIcon,
    BoltIcon,
    PlayIcon,
    StopIcon,
    CheckIcon,
    CpuChipIcon,
    ArrowLeftIcon
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
            active_services: 0,
            failed_services: 0,
            enabled_services: 0,
        }),
    },
    categories: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({
            search: '',
            category: 'all',
            status: 'all',
        }),
    },
})

// Flash Feedback Toast
const feedbackMsg = ref('')

// Filter State
const search = ref(props.filters?.search || '')
const currentCategory = ref(props.filters?.category || 'all')
const currentStatus = ref(props.filters?.status || 'all')

const applyFilters = () => {
    router.get(route('admin.root-tools.services'), {
        search: search.value || undefined,
        category: currentCategory.value !== 'all' ? currentCategory.value : undefined,
        status: currentStatus.value !== 'all' ? currentStatus.value : undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const selectCategory = (cat) => {
    currentCategory.value = cat
    applyFilters()
}

const resetFilters = () => {
    search.value = ''
    currentCategory.value = 'all'
    currentStatus.value = 'all'
    applyFilters()
}

// Service Action Dispatch
const dispatchAction = (unit, action, serviceName) => {
    const actionLabel = action.toUpperCase()
    if (confirm(`Execute '${actionLabel}' on daemon ${serviceName || unit}?`)) {
        router.post(route('admin.root-tools.services.action'), {
            unit: unit,
            action: action,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                feedbackMsg.value = `Action '${action}' executed on ${serviceName || unit}.`
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    }
}
</script>

<template>
    <Head title="System Services - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'System', href: '#' },
                    { label: 'Root Tools', href: route('admin.root-tools.terminal') },
                    { label: 'Systemd Daemons & Infrastructure Units' }
                ]"
            >
                <template #actions>
                    <Link
                        :href="route('admin.root-tools.packages')"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <CpuChipIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Packages</span>
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
                    title="Tracked Daemons"
                    :value="String(stats.total_services || services.length || 0)"
                    badge="Units"
                    badgeType="info"
                    color="blue"
                    :icon="BoltIcon"
                />

                <InfoCard
                    title="Active & Running"
                    :value="String(stats.active_services || services.filter(s => s.status === 'active' || s.is_running).length || 0)"
                    badge="Online"
                    badgeType="success"
                    color="emerald"
                    :icon="CheckIcon"
                />

                <InfoCard
                    title="Failed Daemons"
                    :value="String(stats.failed_services || 0)"
                    badge="Alerts"
                    badgeType="danger"
                    color="rose"
                    :icon="ExclamationTriangleIcon"
                />

                <InfoCard
                    title="Auto-Start Enabled"
                    :value="String(stats.enabled_services || services.filter(s => s.is_enabled).length || 0)"
                    badge="Boot"
                    badgeType="info"
                    color="purple"
                    :icon="ShieldCheckIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Category Tabs -->
            <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        v-for="cat in ['all', 'web', 'database', 'mail', 'dns', 'system', 'security']"
                        :key="cat"
                        type="button"
                        @click="selectCategory(cat)"
                        :class="currentCategory === cat ? 'bg-blue-600 text-white' : 'bg-white text-slate-700 hover:bg-slate-50 border border-slate-200'"
                        class="px-3 py-1.5 rounded-[3px] font-bold text-xs uppercase font-mono transition cursor-pointer shadow-2xs"
                    >
                        {{ cat }}
                    </button>
                </div>
            </div>

            <!-- 4. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search daemon unit name, service label, description..."
                @search="applyFilters"
                @filter="applyFilters"
                @reset="resetFilters"
            >
                <FilterSelect
                    v-model="currentStatus"
                    label="Status"
                    :options="[
                        { label: 'All Statuses', value: 'all' },
                        { label: 'Active (Running)', value: 'active' },
                        { label: 'Inactive (Stopped)', value: 'inactive' },
                        { label: 'Failed Unit', value: 'failed' }
                    ]"
                    placeholder="All Statuses"
                />
            </DataTableFilter>

            <!-- 5. Standard Data Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Service Daemon</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Systemd Unit File</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Category</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Sub-State / Status</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Boot Startup</th>
                                <th class="py-2.5 px-3 w-40">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(svc, index) in services" :key="svc.unit || index" class="hover:bg-blue-50/30 transition">
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-bold text-slate-900">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs border border-blue-100 shrink-0">
                                            <BoltIcon class="w-3.5 h-3.5" />
                                        </div>
                                        <div>
                                            <span class="block leading-tight font-bold">{{ svc.name }}</span>
                                            <span class="text-[10px] text-slate-400 font-mono block">{{ svc.description || svc.unit }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 font-mono text-[11px] text-slate-600 whitespace-nowrap text-center">
                                    {{ svc.unit }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase bg-slate-100 text-slate-700 border border-slate-200 font-mono">
                                        {{ svc.category || 'System' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="svc.status === 'active' || svc.is_running ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ svc.status === 'active' || svc.is_running ? 'Active (Running)' : 'Stopped' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="svc.is_enabled ? 'bg-purple-50 text-purple-700 border-purple-200' : 'bg-slate-100 text-slate-500 border-slate-200'"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ svc.is_enabled ? 'Enabled' : 'Disabled' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button 
                                            type="button" 
                                            @click="dispatchAction(svc.unit, 'restart', svc.name)"
                                            class="px-2 py-1 bg-white hover:bg-slate-50 text-blue-700 font-bold rounded-[3px] text-xs border border-slate-200 shadow-2xs transition cursor-pointer"
                                            title="Restart Unit"
                                        >
                                            Restart 🔄
                                        </button>

                                        <RowActionDropdown>
                                            <button
                                                v-if="!svc.is_running && svc.status !== 'active'"
                                                type="button"
                                                @click="dispatchAction(svc.unit, 'start', svc.name)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-emerald-700 hover:bg-emerald-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <PlayIcon class="w-3.5 h-3.5" />
                                                <span>Start Unit</span>
                                            </button>

                                            <button
                                                v-if="svc.is_running || svc.status === 'active'"
                                                type="button"
                                                @click="dispatchAction(svc.unit, 'stop', svc.name)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-rose-600 hover:bg-rose-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <StopIcon class="w-3.5 h-3.5" />
                                                <span>Stop Unit</span>
                                            </button>

                                            <button
                                                type="button"
                                                @click="dispatchAction(svc.unit, 'reload', svc.name)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <ArrowPathIcon class="w-3.5 h-3.5 text-slate-500" />
                                                <span>Reload Configuration</span>
                                            </button>

                                            <button
                                                type="button"
                                                @click="dispatchAction(svc.unit, svc.is_enabled ? 'disable' : 'enable', svc.name)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <ShieldCheckIcon class="w-3.5 h-3.5 text-slate-500" />
                                                <span>{{ svc.is_enabled ? 'Disable Auto-Start' : 'Enable Auto-Start' }}</span>
                                            </button>
                                        </RowActionDropdown>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!services || services.length === 0">
                                <td colspan="7" class="py-12 text-center text-slate-400 font-sans">
                                    No systemd daemons found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
