<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import RefreshButton from '@/Components/UI/RefreshButton.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'

import {
    WrenchScrewdriverIcon,
    ServerIcon,
    ShieldCheckIcon,
    ArrowPathIcon,
    CheckIcon,
    BoltIcon,
    CircleStackIcon,
    CommandLineIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({}),
    },
    downData: {
        type: Object,
        default: () => ({}),
    },
    servers: {
        type: Array,
        default: () => [],
    },
    routines: {
        type: Array,
        default: () => [],
    },
})

// Reactive Live State
const liveStats = ref(props.stats || {})
const liveDownData = ref(props.downData || {})
const liveServers = ref(props.servers || [])
const liveRoutines = ref(props.routines || [])
const feedbackMsg = ref('')

const runRoutine = (routineKey) => {
    router.post(route('admin.automation.maintenance.run-routine'), { routine: routineKey }, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = `Maintenance routine '${routineKey}' executed successfully.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

const toggleMaintenanceMode = () => {
    router.post(route('admin.infrastructure.maintenance.toggle'), {}, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = 'Server maintenance mode updated.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}
</script>

<template>
    <Head title="Maintenance Routines - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Automation', href: '#' },
                    { label: 'Automation & Schedulers', href: route('admin.automation.cron') },
                    { label: 'Automated Maintenance Routines' }
                ]"
            >
                <template #actions>
                    <Link
                        :href="route('admin.infrastructure.maintenance')"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowLeftIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>System Maintenance</span>
                    </Link>

                    <button
                        type="button"
                        @click="toggleMaintenanceMode"
                        :class="liveDownData.is_down ? 'bg-rose-50 text-rose-700 border-rose-300' : 'bg-white text-slate-700 border-slate-200'"
                        class="px-2.5 py-1.5 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border shadow-2xs transition cursor-pointer"
                    >
                        <WrenchScrewdriverIcon class="w-3.5 h-3.5" />
                        <span>{{ liveDownData.is_down ? 'Maintenance: Active' : 'Maintenance: Disabled' }}</span>
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
                    title="Maintenance Mode"
                    :value="liveDownData.is_down ? 'Live Maintenance' : 'Production Live'"
                    :badge="liveDownData.is_down ? 'Down' : 'Serving'"
                    :badgeType="liveDownData.is_down ? 'warning' : 'success'"
                    color="blue"
                    :icon="WrenchScrewdriverIcon"
                />

                <InfoCard
                    title="Automated Routines"
                    :value="String(liveRoutines.length || 6)"
                    badge="Tasks"
                    badgeType="info"
                    color="purple"
                    :icon="CommandLineIcon"
                />

                <InfoCard
                    title="Storage Cleanups"
                    value="Nightly 03:00"
                    badge="Active"
                    badgeType="success"
                    color="emerald"
                    :icon="CircleStackIcon"
                />

                <InfoCard
                    title="Last Optimization"
                    value="Today (03:00)"
                    badge="Optimal"
                    badgeType="info"
                    color="sky"
                    :icon="ShieldCheckIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Automated Maintenance Routines Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="px-3.5 py-2.5 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <span class="text-xs font-bold text-slate-900 uppercase tracking-wide">Automated System Optimization Tasks</span>
                    <span class="text-[11px] text-slate-400 font-mono">Kernel & Storage housekeeping</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Routine Name</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Description & Scope</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Execution Frequency</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Last Status</th>
                                <th class="py-2.5 px-3 w-28">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(r, index) in liveRoutines" :key="index" class="hover:bg-blue-50/30 transition">
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-bold text-slate-900">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs border border-blue-100 shrink-0">
                                            <WrenchScrewdriverIcon class="w-3.5 h-3.5" />
                                        </div>
                                        <span>{{ r.name }}</span>
                                    </div>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap text-slate-600 max-w-sm truncate" :title="r.description">
                                    {{ r.description }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[11px] text-blue-700 font-bold">
                                    {{ r.frequency || 'Daily 03:00' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase bg-emerald-50 text-emerald-700 border border-emerald-200 font-mono">
                                        Success
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <button 
                                        type="button" 
                                        @click="runRoutine(r.key)"
                                        class="px-2 py-1 bg-white hover:bg-slate-50 text-blue-700 font-bold rounded-[3px] text-xs border border-slate-200 shadow-2xs transition cursor-pointer"
                                        title="Execute Routine"
                                    >
                                        Execute ⚡
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="!liveRoutines || liveRoutines.length === 0">
                                <td colspan="6" class="py-12 text-center text-slate-400 font-sans">
                                    No maintenance routines configured.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
