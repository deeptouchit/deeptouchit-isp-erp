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
    CpuChipIcon,
    ExclamationTriangleIcon,
    CommandLineIcon,
    ArchiveBoxIcon,
    ShieldExclamationIcon,
    CheckIcon,
    GlobeAltIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    packages: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    stats: {
        type: Object,
        default: () => ({
            total_installed: 0,
            upgradable_count: 0,
            security_count: 0,
            active_repositories: 0,
            architecture: 'x86_64',
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
        }),
    },
})

// Flash Feedback Toast
const feedbackMsg = ref('')
const isUpdatingIndex = ref(false)

// Filter State
const search = ref(props.filters?.search || '')
const currentCategory = ref(props.filters?.category || 'all')

const applyFilters = () => {
    router.get(route('admin.root-tools.packages'), {
        search: search.value || undefined,
        category: currentCategory.value !== 'all' ? currentCategory.value : undefined,
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
    applyFilters()
}

// Update APT Index
const triggerUpdateIndex = () => {
    isUpdatingIndex.value = true
    router.post(route('admin.root-tools.packages.update-index'), {}, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = 'APT package repository index synchronized.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        },
        onFinish: () => {
            isUpdatingIndex.value = false
        }
    })
}

// Upgrade Single Package
const upgradePackage = (pkg) => {
    if (confirm(`Upgrade package ${pkg.name} to version ${pkg.candidate_version || 'latest'}?`)) {
        router.post(route('admin.root-tools.packages.upgrade'), {
            package: pkg.name,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                feedbackMsg.value = `Package ${pkg.name} upgrade dispatched.`
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    }
}
</script>

<template>
    <Head title="Package Manager - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'System', href: '#' },
                    { label: 'Root Tools', href: route('admin.root-tools.terminal') },
                    { label: 'OS Package Manager & Binary Ecosystem' }
                ]"
            >
                <template #actions>
                    <Link
                        :href="route('admin.root-tools.network')"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <GlobeAltIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Network & Ports</span>
                    </Link>

                    <button 
                        type="button" 
                        @click="triggerUpdateIndex"
                        :disabled="isUpdatingIndex"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-blue-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer disabled:opacity-50"
                    >
                        <ArrowPathIcon :class="['w-3.5 h-3.5', { 'animate-spin': isUpdatingIndex }]" />
                        <span>Update APT Index</span>
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
                    title="Installed Packages"
                    :value="String(stats.total_installed || packages.data?.length || 0)"
                    badge="Binaries"
                    badgeType="info"
                    color="blue"
                    :icon="ArchiveBoxIcon"
                />

                <InfoCard
                    title="Upgrades Available"
                    :value="String(stats.upgradable_count || 0)"
                    badge="Updates"
                    badgeType="warning"
                    color="amber"
                    :icon="ArrowPathIcon"
                />

                <InfoCard
                    title="Security Patches"
                    :value="String(stats.security_count || 0)"
                    badge="Security"
                    badgeType="danger"
                    color="rose"
                    :icon="ShieldExclamationIcon"
                />

                <InfoCard
                    title="Architecture"
                    :value="stats.architecture || 'x86_64'"
                    badge="Linux"
                    badgeType="info"
                    color="purple"
                    :icon="CpuChipIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Category Tabs -->
            <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        v-for="cat in ['all', 'web_servers', 'databases', 'interpreters', 'system_utilities', 'security_tools']"
                        :key="cat"
                        type="button"
                        @click="selectCategory(cat)"
                        :class="currentCategory === cat ? 'bg-blue-600 text-white' : 'bg-white text-slate-700 hover:bg-slate-50 border border-slate-200'"
                        class="px-3 py-1.5 rounded-[3px] font-bold text-xs uppercase font-mono transition cursor-pointer shadow-2xs"
                    >
                        {{ cat.replace(/_/g, ' ') }}
                    </button>
                </div>
            </div>

            <!-- 4. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search package name, installed version, description..."
                @search="applyFilters"
                @filter="applyFilters"
                @reset="resetFilters"
            />

            <!-- 5. Standard Data Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Package Name</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Installed Version</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Candidate / Latest</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Description</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Status</th>
                                <th class="py-2.5 px-3 w-28">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(pkg, index) in packages.data" :key="pkg.name || index" class="hover:bg-blue-50/30 transition">
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-bold text-slate-900">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs border border-blue-100 shrink-0">
                                            <ArchiveBoxIcon class="w-3.5 h-3.5" />
                                        </div>
                                        <div>
                                            <span class="block leading-tight font-bold font-mono">{{ pkg.name }}</span>
                                            <span class="text-[10px] text-slate-400 font-mono block">{{ pkg.section || 'universe' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 font-mono text-[11px] text-slate-700 whitespace-nowrap text-center">
                                    {{ pkg.version || '1.0.0' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 font-mono text-[11px] text-slate-700 whitespace-nowrap text-center">
                                    <span :class="pkg.has_upgrade ? 'text-amber-700 font-bold' : 'text-slate-500'">
                                        {{ pkg.candidate_version || pkg.version || 'Latest' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-medium text-slate-700 max-w-sm truncate" :title="pkg.description">
                                    {{ pkg.description || 'System software package' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="pkg.has_upgrade ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200'"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ pkg.has_upgrade ? 'Update Available' : 'Up to Date' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1">
                                        <button 
                                            v-if="pkg.has_upgrade"
                                            type="button" 
                                            @click="upgradePackage(pkg)"
                                            class="px-2 py-1 bg-white hover:bg-slate-50 text-blue-700 font-bold rounded-[3px] text-xs border border-slate-200 shadow-2xs transition cursor-pointer"
                                            title="Upgrade Package"
                                        >
                                            Upgrade ⬆️
                                        </button>
                                        <span v-else class="text-[11px] text-slate-400 font-medium">Synced</span>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!packages.data || packages.data.length === 0">
                                <td colspan="7" class="py-12 text-center text-slate-400 font-sans">
                                    No installed packages found matching criteria.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
