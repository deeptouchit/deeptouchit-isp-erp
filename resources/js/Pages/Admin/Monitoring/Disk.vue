<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import RefreshButton from '@/Components/UI/RefreshButton.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'

import {
    CircleStackIcon,
    ServerIcon,
    BoltIcon,
    ClockIcon,
    FolderIcon,
    ArchiveBoxIcon,
    DocumentTextIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({}),
    },
    partitions: {
        type: Array,
        default: () => [],
    },
    inodes: {
        type: Object,
        default: () => ({}),
    },
    iops: {
        type: Object,
        default: () => ({}),
    },
    directories: {
        type: Array,
        default: () => [],
    },
})

// Reactive Live State
const liveStats = ref(props.stats || {})
const livePartitions = ref(props.partitions || [])
const liveInodes = ref(props.inodes || {})
const liveIops = ref(props.iops || {})
const liveDirectories = ref(props.directories || [])

// Live Telemetry Engine
const refreshInterval = ref(3000)
const isLiveActive = ref(true)
const isPolling = ref(false)
let timer = null

const fetchMetrics = async () => {
    if (isPolling.value) return
    isPolling.value = true

    try {
        const response = await fetch(route('admin.monitoring.api.disk'), {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        if (response.ok) {
            const data = await response.json()
            liveStats.value = data.stats || liveStats.value
            livePartitions.value = data.partitions || livePartitions.value
            liveInodes.value = data.inodes || liveInodes.value
            liveIops.value = data.iops || liveIops.value
            liveDirectories.value = data.directories || liveDirectories.value
        }
    } catch (e) {
        // background fetch error
    } finally {
        isPolling.value = false
    }
}

const toggleLive = () => {
    isLiveActive.value = !isLiveActive.value
    if (isLiveActive.value) {
        startTimer()
    } else {
        stopTimer()
    }
}

const startTimer = () => {
    stopTimer()
    if (isLiveActive.value) {
        timer = setInterval(fetchMetrics, refreshInterval.value)
    }
}

const stopTimer = () => {
    if (timer) {
        clearInterval(timer)
        timer = null
    }
}

onMounted(() => {
    startTimer()
})

onUnmounted(() => {
    stopTimer()
})
</script>

<template>
    <Head title="Disk & Storage Telemetry - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Monitoring', href: '#' },
                    { label: 'Server Monitoring', href: route('admin.monitoring.overview') },
                    { label: 'Disk & Storage Telemetry' }
                ]"
            >
                <template #actions>
                    <Link
                        :href="route('admin.files.storage')"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowLeftIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Storage Pools</span>
                    </Link>

                    <button
                        type="button"
                        @click="toggleLive"
                        :class="isLiveActive ? 'bg-emerald-50 text-emerald-700 border-emerald-300' : 'bg-slate-100 text-slate-600 border-slate-200'"
                        class="px-2.5 py-1.5 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border shadow-2xs transition cursor-pointer"
                    >
                        <span class="w-2 h-2 rounded-full" :class="isLiveActive ? 'bg-emerald-500 animate-pulse' : 'bg-slate-400'"></span>
                        <span>{{ isLiveActive ? 'Live: 3s' : 'Paused' }}</span>
                    </button>

                    <RefreshButton />
                </template>
            </PageHeader>

            <!-- 2. Ultra-Compact KPI Info Cards (56px) -->
            <InfoCardsGrid :cols="4">
                <InfoCard
                    title="Total Block Storage"
                    :value="liveStats.total_formatted || '0 B'"
                    badge="NVMe/SSD"
                    badgeType="info"
                    color="blue"
                    :icon="CircleStackIcon"
                />

                <InfoCard
                    title="Space Utilized"
                    :value="`${liveStats.used_formatted || '0 B'} (${liveStats.usage_percent || 0}%)`"
                    badge="Allocated"
                    badgeType="info"
                    :color="(liveStats.usage_percent || 0) > 85 ? 'rose' : 'purple'"
                    :icon="ServerIcon"
                />

                <InfoCard
                    title="Available Free Space"
                    :value="liveStats.free_formatted || '0 B'"
                    badge="Free Space"
                    badgeType="success"
                    color="emerald"
                    :icon="ArchiveBoxIcon"
                />

                <InfoCard
                    title="Inodes File Allocation"
                    :value="`${liveInodes.used_formatted || '0'} (${liveInodes.usage_percent || 0}%)`"
                    badge="Inodes"
                    badgeType="info"
                    color="sky"
                    :icon="DocumentTextIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Mounted Partitions Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="px-3.5 py-2.5 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <span class="text-xs font-bold text-slate-900 uppercase tracking-wide">Mounted Storage Partitions</span>
                    <span class="text-[11px] text-slate-400 font-mono">Filesystem Mounts</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Filesystem Device</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Mountpoint</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Format</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Total Size</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Used Space</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Free Space</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left w-48">Usage Bar</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(part, index) in livePartitions" :key="index" class="hover:bg-blue-50/30 transition">
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono font-bold text-blue-700">
                                    {{ part.filesystem || '/dev/nvme0n1p1' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono font-bold text-slate-900">
                                    {{ part.mount || '/' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono uppercase text-[10.5px] text-slate-500">
                                    {{ part.type || 'ext4' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[11px] text-slate-700">
                                    {{ part.size_formatted || '0 B' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono font-bold text-slate-900 text-[11px]">
                                    {{ part.used_formatted || '0 B' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[11px] text-emerald-700 font-bold">
                                    {{ part.free_formatted || '0 B' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1 bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                            <div 
                                                class="h-1.5 rounded-full transition-all"
                                                :class="part.percent > 85 ? 'bg-rose-500' : 'bg-blue-600'"
                                                :style="{ width: `${Math.min(part.percent || 0, 100)}%` }"
                                            ></div>
                                        </div>
                                        <span class="font-mono text-[10.5px] text-slate-500 w-8 text-right">{{ part.percent || 0 }}%</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 4. Top Storage Directory Consumers -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] p-3.5 shadow-2xs space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <span class="text-xs font-bold text-slate-900 uppercase tracking-wide">Major Directory Allocations</span>
                    <span class="text-[11px] text-slate-400 font-mono">du analyzer</span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                    <div 
                        v-for="(dir, di) in liveDirectories" 
                        :key="di" 
                        class="bg-slate-50 p-2 rounded-[3px] border border-slate-200 flex items-center justify-between font-mono text-xs"
                    >
                        <span class="text-slate-700 font-bold">{{ dir.path }}</span>
                        <span class="text-blue-700 font-bold">{{ dir.size_formatted }}</span>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
