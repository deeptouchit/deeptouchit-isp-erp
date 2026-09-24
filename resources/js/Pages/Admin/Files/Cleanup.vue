<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import RefreshButton from '@/Components/UI/RefreshButton.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'

import {
    TrashIcon,
    ArrowPathIcon,
    SparklesIcon,
    DocumentTextIcon,
    UserGroupIcon,
    ArchiveBoxIcon,
    CheckIcon,
    XMarkIcon,
    ShieldCheckIcon,
    ExclamationTriangleIcon,
    FolderIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    categories: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            total_cleanable_formatted: '0 B',
            total_cleanable_bytes: 0,
            temp_files_count: 0,
            temp_size_formatted: '0 B',
            logs_size_formatted: '0 B',
            health_score: 95,
        }),
    },
})

// Flash feedback
const feedbackMsg = ref('')
const isCleaningAll = ref(false)
const cleaningCategory = ref(null)

const cleanSingleCategory = (cat) => {
    cleaningCategory.value = cat.key
    router.post(route('admin.files.cleanup.clean', cat.key), {}, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = `${cat.name} cleaned successfully.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        },
        onFinish: () => {
            cleaningCategory.value = null
        }
    })
}

const cleanAll = () => {
    isCleaningAll.value = true
    router.post(route('admin.files.cleanup.all'), {}, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = `Universal deep clean completed successfully.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        },
        onFinish: () => {
            isCleaningAll.value = false
        }
    })
}

const getCategoryIcon = (iconName) => {
    switch (iconName) {
        case 'trash': return TrashIcon
        case 'document-text': return DocumentTextIcon
        case 'sparkles': return SparklesIcon
        case 'user-group': return UserGroupIcon
        case 'archive-box': return ArchiveBoxIcon
        default: return FolderIcon
    }
}
</script>

<template>
    <Head title="Storage & Cache Cleanup - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Services', href: '#' },
                    { label: 'Files & Storage', href: route('admin.files.manager') },
                    { label: 'Storage & Cache Cleanup' }
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
                        @click="cleanAll"
                        :disabled="isCleaningAll"
                        class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer disabled:opacity-50"
                    >
                        <TrashIcon class="w-3.5 h-3.5" :class="{ 'animate-spin': isCleaningAll }" />
                        <span>{{ isCleaningAll ? 'Cleaning All...' : '1-Click Clean Everything' }}</span>
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
                    title="Total Cleanable Data"
                    :value="stats.total_cleanable_formatted || '0 B'"
                    badge="Reclaimable"
                    badgeType="warning"
                    color="blue"
                    :icon="TrashIcon"
                />

                <InfoCard
                    title="Transient Temp Files"
                    :value="`${stats.temp_size_formatted || '0 B'} (${stats.temp_files_count || 0})`"
                    badge="/tmp"
                    badgeType="info"
                    color="purple"
                    :icon="ArchiveBoxIcon"
                />

                <InfoCard
                    title="Rotated Log Buffers"
                    :value="stats.logs_size_formatted || '0 B'"
                    badge="/var/log"
                    badgeType="info"
                    color="sky"
                    :icon="DocumentTextIcon"
                />

                <InfoCard
                    title="Storage Health Index"
                    :value="`${stats.health_score || 95} / 100`"
                    :badge="stats.health_score >= 85 ? 'Optimal' : 'Needs Clean'"
                    :badgeType="stats.health_score >= 85 ? 'success' : 'warning'"
                    color="emerald"
                    :icon="ShieldCheckIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Category Cleanup Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3.5">
                <div 
                    v-for="(cat, idx) in categories" 
                    :key="cat.key || idx" 
                    class="bg-white rounded-[4px] border border-[#E2E8F0] p-4 shadow-2xs space-y-3 flex flex-col justify-between"
                >
                    <div class="space-y-2">
                        <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs border border-blue-100 shrink-0">
                                    <component :is="getCategoryIcon(cat.icon)" class="w-3.5 h-3.5" />
                                </div>
                                <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">{{ cat.name }}</h3>
                            </div>
                            <span class="font-mono text-xs font-bold text-rose-600 bg-rose-50 px-2 py-0.5 rounded-[2px] border border-rose-200">
                                {{ cat.size_formatted }}
                            </span>
                        </div>

                        <p class="text-xs text-slate-600 leading-relaxed">{{ cat.description }}</p>

                        <div class="bg-slate-50 p-2 rounded-[3px] border border-slate-200 text-[11px] font-mono text-slate-500 flex items-center justify-between">
                            <span>Path: {{ cat.path }}</span>
                            <span>{{ cat.items_count || 0 }} items</span>
                        </div>
                    </div>

                    <div class="pt-2 border-t border-slate-100 flex items-center justify-end">
                        <button
                            type="button"
                            @click="cleanSingleCategory(cat)"
                            :disabled="cleaningCategory === cat.key || isCleaningAll"
                            class="px-3 py-1.5 bg-white hover:bg-rose-50 text-rose-600 font-bold rounded-[3px] text-xs border border-rose-200 shadow-2xs transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
                        >
                            <TrashIcon class="w-3.5 h-3.5" :class="{ 'animate-spin': cleaningCategory === cat.key }" />
                            <span>{{ cleaningCategory === cat.key ? 'Purging...' : 'Purge Category' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
