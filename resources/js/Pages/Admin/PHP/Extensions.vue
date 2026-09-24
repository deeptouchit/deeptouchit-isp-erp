<script setup>
import { ref, computed } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import RefreshButton from '@/Components/UI/RefreshButton.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'
import DataTableFilter from '@/Components/UI/DataTableFilter.vue'
import FilterSelect from '@/Components/UI/FilterSelect.vue'

import {
    CpuChipIcon,
    ShieldCheckIcon,
    ArrowLeftIcon,
    CommandLineIcon,
    BoltIcon,
    PuzzlePieceIcon,
    CheckIcon,
    LockClosedIcon,
    PlusIcon,
    TrashIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    catalog: {
        type: Object,
        default: () => ({}),
    },
    categories: {
        type: Object,
        default: () => ({}),
    },
    stats: {
        type: Object,
        default: () => ({
            total_catalog: 0,
            loaded_count: 0,
            core_count: 0,
            dynamic_loaded: 0,
            opcache_loaded: false,
        }),
    },
    selectedVersion: {
        type: String,
        default: '8.3',
    },
    installedVersions: {
        type: Array,
        default: () => [],
    },
    defaultVersion: {
        type: String,
        default: '8.3',
    },
})

// Toast Feedback
const feedbackMsg = ref('')

// Filters
const search = ref('')
const selectedCategory = ref('all')
const statusFilter = ref('all')

// Processing tracking for individual extensions
const activeActionExtension = ref(null)

const switchVersion = (version) => {
    router.get(route('admin.php.extensions'), { version }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const filteredCategories = computed(() => {
    const q = search.value.trim().toLowerCase()
    const result = {}

    for (const [catName, extensions] of Object.entries(props.categories)) {
        if (selectedCategory.value !== 'all' && selectedCategory.value !== catName) {
            continue
        }

        const filteredList = extensions.filter(ext => {
            const matchesSearch = !q || 
                ext.name.toLowerCase().includes(q) ||
                ext.title.toLowerCase().includes(q) ||
                (ext.package || '').toLowerCase().includes(q) ||
                (ext.description || '').toLowerCase().includes(q)

            if (!matchesSearch) return false

            if (statusFilter.value === 'loaded') {
                return ext.is_loaded
            } else if (statusFilter.value === 'available') {
                return !ext.is_loaded
            }

            return true
        })

        if (filteredList.length > 0) {
            result[catName] = filteredList
        }
    }

    return result
})

const resetFilters = () => {
    search.value = ''
    selectedCategory.value = 'all'
    statusFilter.value = 'all'
}

// Toggle or Install Extension
const handleToggleExtension = (ext) => {
    if (ext.is_core) return
    
    activeActionExtension.value = ext.name
    const isEnabling = !ext.is_loaded

    useForm({
        version: props.selectedVersion,
        extension: ext.name,
        action: isEnabling ? 'enable' : 'disable',
        package: ext.package,
    }).post(route('admin.php.toggle-extension'), {
        preserveScroll: true,
        onSuccess: () => {
            activeActionExtension.value = null
            feedbackMsg.value = `Module '${ext.title}' for PHP ${props.selectedVersion} ${isEnabling ? 'activated' : 'disabled'}.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        },
        onError: () => {
            activeActionExtension.value = null
        }
    })
}
</script>

<template>
    <Head :title="`PHP ${selectedVersion} Extensions - DeepTouchHost`" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'PHP Manager', href: route('admin.php.index') },
                    { label: 'Extensions' }
                ]"
            >
                <template #actions>
                    <Link
                        :href="route('admin.php.index')"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowLeftIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>PHP Overview</span>
                    </Link>

                    <!-- Version Switcher -->
                    <div class="flex items-center gap-1.5 bg-white px-2.5 py-1.5 rounded-[3px] border border-slate-200 shadow-2xs text-xs">
                        <span class="font-bold text-slate-500">PHP Version:</span>
                        <select 
                            :value="selectedVersion"
                            @change="switchVersion($event.target.value)"
                            class="bg-blue-50 border border-blue-200 text-xs font-bold text-blue-700 rounded-[2px] py-0.5 px-2 focus:ring-1 focus:ring-blue-500 cursor-pointer outline-none"
                        >
                            <option v-for="ver in installedVersions" :key="ver" :value="ver">
                                PHP {{ ver }}
                            </option>
                        </select>
                    </div>

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
                    title="Loaded Extensions"
                    :value="String(stats.loaded_count || 0)"
                    badge="Active"
                    badgeType="success"
                    color="emerald"
                    :icon="PuzzlePieceIcon"
                />

                <InfoCard
                    title="Catalog Modules"
                    :value="String(stats.total_catalog || 0)"
                    badge="Supported"
                    badgeType="info"
                    color="blue"
                    :icon="CommandLineIcon"
                />

                <InfoCard
                    title="Core Built-in"
                    :value="String(stats.core_count || 0)"
                    badge="Native"
                    badgeType="info"
                    color="sky"
                    :icon="CpuChipIcon"
                />

                <InfoCard
                    title="Zend OPcache"
                    :value="stats.opcache_loaded ? 'Active' : 'Offline'"
                    :badge="stats.opcache_loaded ? 'Accelerated' : 'Disabled'"
                    :badgeType="stats.opcache_loaded ? 'success' : 'warning'"
                    color="purple"
                    :icon="BoltIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search extension name, package identifier, or description..."
                @search="() => {}"
                @filter="() => {}"
                @reset="resetFilters"
            >
                <FilterSelect
                    v-model="selectedCategory"
                    label="Module Category"
                    :options="[
                        { label: 'All Categories', value: 'all' },
                        ...Object.keys(categories).map(cat => ({ label: cat, value: cat }))
                    ]"
                    placeholder="All Categories"
                />

                <FilterSelect
                    v-model="statusFilter"
                    label="Module State"
                    :options="[
                        { label: 'All Module States', value: 'all' },
                        { label: 'Loaded / Active Only', value: 'loaded' },
                        { label: 'Available to Install', value: 'available' }
                    ]"
                    placeholder="All Module States"
                />
            </DataTableFilter>

            <!-- 4. Categorized Module Cards Container -->
            <div class="space-y-4">
                <div 
                    v-for="(extensions, catName) in filteredCategories" 
                    :key="catName"
                    class="bg-white rounded-[4px] border border-[#E2E8F0] p-4 shadow-2xs space-y-3"
                >
                    <!-- Category Header -->
                    <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">{{ catName }}</h3>
                        </div>
                        <span class="text-[10px] font-mono text-slate-400 font-bold">
                            {{ extensions.filter(e => e.is_loaded).length }} / {{ extensions.length }} Enabled
                        </span>
                    </div>

                    <!-- Extensions Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2.5">
                        <div
                            v-for="ext in extensions"
                            :key="ext.name"
                            :class="[
                                ext.is_loaded ? 'bg-blue-50/30 border-blue-200' : 'bg-slate-50/50 border-slate-200',
                                'p-3 rounded-[3px] border transition flex flex-col justify-between space-y-2'
                            ]"
                        >
                            <div>
                                <div class="flex items-start justify-between gap-1.5">
                                    <div>
                                        <h4 class="font-bold text-slate-900 text-xs flex items-center gap-1">
                                            <span>{{ ext.title }}</span>
                                            <span v-if="ext.is_core" class="text-[9px] text-slate-400 font-normal">(Core)</span>
                                        </h4>
                                        <span class="font-mono text-[10px] text-slate-400 block">{{ ext.name }}</span>
                                    </div>

                                    <span 
                                        :class="ext.is_loaded ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-500 border-slate-200'"
                                        class="inline-flex items-center gap-1 px-1.5 py-0.2 rounded-[2px] text-[9.5px] font-bold border capitalize shrink-0"
                                    >
                                        <span class="w-1 h-1 rounded-full" :class="ext.is_loaded ? 'bg-emerald-500' : 'bg-slate-400'"></span>
                                        {{ ext.is_loaded ? 'Loaded' : 'Disabled' }}
                                    </span>
                                </div>
                                <p class="text-[10.5px] text-slate-500 mt-1 line-clamp-2">{{ ext.description }}</p>
                            </div>

                            <!-- Action Button -->
                            <div class="pt-2 border-t border-slate-100/80 flex items-center justify-between text-xs">
                                <span class="font-mono text-[9.5px] text-slate-400 truncate max-w-[120px]">{{ ext.package || 'compiled-in' }}</span>

                                <button
                                    v-if="!ext.is_core"
                                    type="button"
                                    @click="handleToggleExtension(ext)"
                                    :disabled="activeActionExtension === ext.name"
                                    :class="[
                                        ext.is_loaded ? 'bg-white hover:bg-rose-50 text-rose-600 border-rose-200' : 'bg-blue-600 hover:bg-blue-700 text-white',
                                        'px-2 py-0.5 rounded-[2px] text-[10px] font-bold transition shadow-2xs border cursor-pointer disabled:opacity-50'
                                    ]"
                                >
                                    {{ activeActionExtension === ext.name ? 'Updating...' : (ext.is_loaded ? 'Disable' : 'Enable') }}
                                </button>
                                <span v-else class="text-[10px] font-bold text-slate-400 flex items-center gap-0.5">
                                    <LockClosedIcon class="w-3 h-3" />
                                    <span>Built-in</span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="Object.keys(filteredCategories).length === 0" class="bg-white rounded-[4px] border border-[#E2E8F0] p-12 text-center text-slate-400 text-xs">
                    No PHP extensions found matching the search criteria.
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
