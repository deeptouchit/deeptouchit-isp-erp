<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import RefreshButton from '@/Components/UI/RefreshButton.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'

import {
    CircleStackIcon,
    FolderIcon,
    DocumentTextIcon,
    TrashIcon,
    ArrowPathIcon,
    ServerIcon,
    GlobeAltIcon,
    CheckIcon,
    XMarkIcon,
    ShieldCheckIcon,
    ArrowRightIcon,
    CubeTransparentIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    primaryDisk: {
        type: Object,
        default: () => ({
            total: 0,
            total_formatted: '0 B',
            used: 0,
            used_formatted: '0 B',
            free: 0,
            free_formatted: '0 B',
            percent_used: 0,
        }),
    },
    inodes: {
        type: Object,
        default: () => ({
            total_formatted: '0',
            used_formatted: '0',
            free_formatted: '0',
            percent_used: 0,
        }),
    },
    categories: {
        type: Array,
        default: () => [],
    },
    subscriptions: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            vhosts_size_formatted: '0 B',
            logs_size_formatted: '0 B',
        }),
    },
})

const feedbackMsg = ref('')
const isCleaningLogs = ref(false)
const isFlushingCache = ref(false)

const vacuumLogs = () => {
    isCleaningLogs.value = true
    router.post(route('admin.files.storage.vacuum-logs'), {}, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = `System logs vacuumed successfully.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        },
        onFinish: () => {
            isCleaningLogs.value = false
        }
    })
}

const flushCache = () => {
    isFlushingCache.value = true
    router.post(route('admin.files.storage.flush-cache'), {}, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = `Application cache and temp buffers cleared.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        },
        onFinish: () => {
            isFlushingCache.value = false
        }
    })
}

const getQuotaBarClass = (percent) => {
    if (percent > 90) return 'bg-rose-500'
    if (percent > 75) return 'bg-amber-500'
    return 'bg-blue-600'
}
</script>

<template>
    <Head title="Storage Pools & Disks - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Services', href: '#' },
                    { label: 'Files & Storage', href: route('admin.files.manager') },
                    { label: 'Storage Pools & Disks' }
                ]"
            >
                <template #actions>
                    <Link
                        :href="route('admin.files.manager')"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowLeftIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>File Manager</span>
                    </Link>

                    <button
                        type="button"
                        @click="vacuumLogs"
                        :disabled="isCleaningLogs"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer disabled:opacity-50"
                    >
                        <TrashIcon class="w-3.5 h-3.5 text-slate-500" :class="{ 'animate-spin': isCleaningLogs }" />
                        <span>Vacuum Logs</span>
                    </button>

                    <button
                        type="button"
                        @click="flushCache"
                        :disabled="isFlushingCache"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer disabled:opacity-50"
                    >
                        <ArrowPathIcon class="w-3.5 h-3.5" :class="{ 'animate-spin': isFlushingCache }" />
                        <span>Flush Temp Cache</span>
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
                    title="Total Block Storage"
                    :value="primaryDisk.total_formatted || '100 GB'"
                    badge="Root SSD"
                    badgeType="info"
                    color="blue"
                    :icon="CircleStackIcon"
                />

                <InfoCard
                    title="Storage Utilized"
                    :value="`${primaryDisk.used_formatted || '0 B'} (${primaryDisk.percent_used || 0}%)`"
                    :badge="primaryDisk.percent_used > 80 ? 'High' : 'Normal'"
                    :badgeType="primaryDisk.percent_used > 80 ? 'warning' : 'success'"
                    color="emerald"
                    :icon="ServerIcon"
                />

                <InfoCard
                    title="Free Available"
                    :value="primaryDisk.free_formatted || '0 B'"
                    badge="Available"
                    badgeType="success"
                    color="sky"
                    :icon="CubeTransparentIcon"
                />

                <InfoCard
                    title="Inodes File Allocation"
                    :value="`${inodes.percent_used || 0}% used`"
                    badge="Inodes"
                    badgeType="info"
                    color="purple"
                    :icon="DocumentTextIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Category Breakdown Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3.5">
                <div 
                    v-for="(cat, idx) in categories" 
                    :key="idx" 
                    class="bg-white rounded-[4px] border border-[#E2E8F0] p-3.5 shadow-2xs space-y-2.5"
                >
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-slate-800 text-xs">{{ cat.name }}</span>
                        <span class="font-mono text-blue-700 font-bold text-xs">{{ cat.size_formatted }}</span>
                    </div>

                    <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                        <div 
                            class="bg-blue-600 h-1.5 rounded-full transition-all duration-500"
                            :style="{ width: `${Math.min(cat.percent || 10, 100)}%` }"
                        ></div>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-400 font-mono">
                        <span>{{ cat.path }}</span>
                        <span>{{ cat.percent }}%</span>
                    </div>
                </div>
            </div>

            <!-- 4. Top Storage Consumers Data Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">Top Account Storage Consumers</h3>
                        <p class="text-[11px] text-slate-400">Hosting accounts and their disk quota allocations.</p>
                    </div>
                    <Link
                        :href="route('admin.files.quotas')"
                        class="text-xs font-bold text-blue-600 hover:text-blue-800 hover:underline cursor-pointer"
                    >
                        Manage Quotas →
                    </Link>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Hosting Account / Domain</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Customer Username</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Disk Quota Limit</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Actual Usage</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left w-48">Usage Bar</th>
                                <th class="py-2.5 px-3 w-28">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(sub, index) in subscriptions" :key="sub.id || index" class="hover:bg-blue-50/30 transition">
                                <!-- # -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>

                                <!-- Domain -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs border border-blue-100 shrink-0">
                                            <GlobeAltIcon class="w-3.5 h-3.5" />
                                        </div>
                                        <span class="font-bold text-slate-900 font-mono leading-tight">{{ sub.domain }}</span>
                                    </div>
                                </td>

                                <!-- Customer -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono text-[11px] text-slate-600">
                                    @{{ sub.username }}
                                </td>

                                <!-- Limit -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[11px] text-slate-700">
                                    {{ sub.quota_formatted || (sub.plan?.disk_space ? `${sub.plan.disk_space} MB` : 'Unlimited') }}
                                </td>

                                <!-- Actual -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono font-bold text-blue-700 text-[11px]">
                                    {{ sub.disk_usage_formatted || '0 B' }}
                                </td>

                                <!-- Bar -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1 bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                            <div 
                                                :class="getQuotaBarClass(sub.disk_percent || 0)"
                                                class="h-1.5 rounded-full transition-all"
                                                :style="{ width: `${Math.min(sub.disk_percent || 0, 100)}%` }"
                                            ></div>
                                        </div>
                                        <span class="font-mono text-[10.5px] text-slate-500 w-8 text-right">{{ sub.disk_percent || 0 }}%</span>
                                    </div>
                                </td>

                                <!-- Action -->
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <Link
                                        :href="route('admin.files.manager', { scope: 'vhosts', path: sub.domain })"
                                        class="px-2 py-1 bg-white hover:bg-slate-50 text-blue-700 font-bold rounded-[3px] text-xs border border-slate-200 shadow-2xs transition cursor-pointer"
                                    >
                                        Browse Files 📂
                                    </Link>
                                </td>
                            </tr>

                            <tr v-if="!subscriptions || subscriptions.length === 0">
                                <td colspan="7" class="py-12 text-center text-slate-400">
                                    No customer accounts found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
