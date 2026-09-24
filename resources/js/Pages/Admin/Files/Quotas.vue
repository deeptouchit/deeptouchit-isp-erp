<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import RefreshButton from '@/Components/UI/RefreshButton.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'
import DataTableFilter from '@/Components/UI/DataTableFilter.vue'
import FilterSelect from '@/Components/UI/FilterSelect.vue'
import RowActionDropdown from '@/Components/UI/RowActionDropdown.vue'

import {
    CircleStackIcon,
    FolderIcon,
    ArrowPathIcon,
    GlobeAltIcon,
    XMarkIcon,
    PencilSquareIcon,
    ExclamationTriangleIcon,
    CheckIcon,
    CubeTransparentIcon,
    ShieldExclamationIcon,
    ChartPieIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    quotas: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            total_accounts: 0,
            total_allocated_formatted: '0 B',
            total_used_formatted: '0 B',
            critical_accounts: 0,
            warning_accounts: 0,
            average_usage_percent: 0,
        }),
    },
    filters: {
        type: Object,
        default: () => ({ search: '', status: 'all' }),
    },
})

// Search & Filter
const search = ref(props.filters?.search || '')
const status = ref(props.filters?.status || 'all')
const feedbackMsg = ref('')

const filteredQuotas = computed(() => {
    let list = props.quotas || []

    if (search.value.trim()) {
        const q = search.value.toLowerCase()
        list = list.filter(item =>
            item.domain.toLowerCase().includes(q) ||
            item.username.toLowerCase().includes(q) ||
            (item.plan_name || '').toLowerCase().includes(q)
        )
    }

    if (status.value !== 'all') {
        list = list.filter(item => item.health === status.value)
    }

    return list
})

const resetFilters = () => {
    search.value = ''
    status.value = 'all'
}

// Edit Quota Modal
const showEditModal = ref(false)
const selectedQuota = ref(null)

const editForm = useForm({
    custom_disk_space: '',
    custom_inodes: '',
})

const openEditModal = (item) => {
    selectedQuota.value = item
    editForm.custom_disk_space = item.custom_disk_space !== null ? item.custom_disk_space : ''
    editForm.custom_inodes = item.custom_inodes !== null ? item.custom_inodes : ''
    showEditModal.value = true
}

const setPresetDisk = (mb) => {
    editForm.custom_disk_space = mb
}

const submitEdit = () => {
    if (!selectedQuota.value) return
    editForm.post(route('admin.files.quotas.update', selectedQuota.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false
            feedbackMsg.value = `Quota limits updated for '${selectedQuota.value.domain}'.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

const getProgressBarClass = (health) => {
    if (health === 'critical') return 'bg-rose-500'
    if (health === 'warning') return 'bg-amber-500'
    return 'bg-blue-600'
}
</script>

<template>
    <Head title="Disk Quotas Management - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Services', href: '#' },
                    { label: 'Files & Storage', href: route('admin.files.manager') },
                    { label: 'Disk Quotas Management' }
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
                    title="Tenant Accounts"
                    :value="String(stats.total_accounts || quotas.length || 0)"
                    badge="Managed"
                    badgeType="info"
                    color="blue"
                    :icon="CircleStackIcon"
                />

                <InfoCard
                    title="Allocated Quota Pool"
                    :value="stats.total_allocated_formatted || '0 B'"
                    badge="Limit Pool"
                    badgeType="info"
                    color="purple"
                    :icon="CubeTransparentIcon"
                />

                <InfoCard
                    title="Storage Utilized"
                    :value="`${stats.total_used_formatted || '0 B'} (${stats.average_usage_percent || 0}%)`"
                    badge="Aggregate"
                    badgeType="success"
                    color="emerald"
                    :icon="ChartPieIcon"
                />

                <InfoCard
                    title="Critical Quotas (>90%)"
                    :value="String(stats.critical_accounts || 0)"
                    :badge="stats.critical_accounts > 0 ? 'Alert' : 'Clean'"
                    :badgeType="stats.critical_accounts > 0 ? 'danger' : 'success'"
                    color="sky"
                    :icon="ShieldExclamationIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search domain, username, or hosting plan..."
                @search="() => {}"
                @filter="() => {}"
                @reset="resetFilters"
            >
                <FilterSelect
                    v-model="status"
                    label="Health Threshold"
                    :options="[
                        { label: 'All Thresholds', value: 'all' },
                        { label: 'Healthy (< 75%)', value: 'normal' },
                        { label: 'Warning (75% - 90%)', value: 'warning' },
                        { label: 'Critical (> 90%)', value: 'critical' }
                    ]"
                    placeholder="All Thresholds"
                />
            </DataTableFilter>

            <!-- 4. Standard Data Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Domain / Account</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Owner Username</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Hosting Plan</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Disk Quota Limit</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Disk Used</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left w-48">Usage Bar</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Inodes Limit</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Health</th>
                                <th class="py-2.5 px-3 w-28">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(item, index) in filteredQuotas" :key="item.id || index" class="hover:bg-blue-50/30 transition">
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
                                        <span class="font-bold text-slate-900 font-mono leading-tight">{{ item.domain }}</span>
                                    </div>
                                </td>

                                <!-- Username -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono text-[11px] text-slate-600">
                                    @{{ item.username }}
                                </td>

                                <!-- Plan -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap text-slate-700">
                                    {{ item.plan_name || 'Custom Plan' }}
                                </td>

                                <!-- Limit -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[11px] text-slate-700">
                                    <span v-if="item.has_custom_disk" class="text-blue-700 font-bold" title="Custom Override">
                                        {{ item.quota_formatted }} (Custom)
                                    </span>
                                    <span v-else>
                                        {{ item.quota_formatted }}
                                    </span>
                                </td>

                                <!-- Used -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono font-bold text-blue-700 text-[11px]">
                                    {{ item.disk_usage_formatted }}
                                </td>

                                <!-- Bar -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1 bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                            <div 
                                                :class="getProgressBarClass(item.health)"
                                                class="h-1.5 rounded-full transition-all"
                                                :style="{ width: `${Math.min(item.percent || 0, 100)}%` }"
                                            ></div>
                                        </div>
                                        <span class="font-mono text-[10.5px] text-slate-500 w-8 text-right">{{ item.percent }}%</span>
                                    </div>
                                </td>

                                <!-- Inodes -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[10.5px] text-slate-500">
                                    {{ item.inodes_formatted }}
                                </td>

                                <!-- Health -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="[
                                            item.health === 'critical' ? 'bg-rose-50 text-rose-700 border-rose-200' :
                                            item.health === 'warning' ? 'bg-amber-50 text-amber-700 border-amber-200' :
                                            'bg-emerald-50 text-emerald-700 border-emerald-200'
                                        ]"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ item.health }}
                                    </span>
                                </td>

                                <!-- Action -->
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1">
                                        <button 
                                            type="button" 
                                            @click="openEditModal(item)"
                                            class="px-2 py-1 bg-white hover:bg-slate-50 text-blue-700 font-bold rounded-[3px] text-xs border border-slate-200 shadow-2xs transition cursor-pointer"
                                            title="Edit Quota"
                                        >
                                            Edit ✏️
                                        </button>

                                        <RowActionDropdown>
                                            <Link
                                                :href="route('admin.files.manager', { scope: 'vhosts', path: item.domain })"
                                                class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <FolderIcon class="w-3.5 h-3.5 text-slate-500" />
                                                <span>Browse File Manager</span>
                                            </Link>
                                        </RowActionDropdown>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!filteredQuotas || filteredQuotas.length === 0">
                                <td colspan="10" class="py-12 text-center text-slate-400">
                                    No disk quotas found matching search.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- EDIT QUOTA MODAL -->
        <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <PencilSquareIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Edit Quota: {{ selectedQuota?.domain }}
                        </h3>
                    </div>
                    <button @click="showEditModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitEdit" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Custom Disk Space Limit (MB)</label>
                        <input v-model.number="editForm.custom_disk_space" type="number" placeholder="Leave empty for package default" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        
                        <!-- Quick Presets -->
                        <div class="flex items-center gap-1.5 pt-1">
                            <span class="text-[10.5px] text-slate-400">Presets:</span>
                            <button type="button" @click="setPresetDisk(1024)" class="px-2 py-0.5 rounded-[2px] bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10.5px] font-bold cursor-pointer">1 GB</button>
                            <button type="button" @click="setPresetDisk(5120)" class="px-2 py-0.5 rounded-[2px] bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10.5px] font-bold cursor-pointer">5 GB</button>
                            <button type="button" @click="setPresetDisk(10240)" class="px-2 py-0.5 rounded-[2px] bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10.5px] font-bold cursor-pointer">10 GB</button>
                            <button type="button" @click="setPresetDisk(0)" class="px-2 py-0.5 rounded-[2px] bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10.5px] font-bold cursor-pointer">Unlimited (0)</button>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Custom Inodes Limit (Files Count)</label>
                        <input v-model.number="editForm.custom_inodes" type="number" placeholder="Leave empty for package default" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="px-4 py-2.5 -mx-4 -mb-4 mt-4 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                        <button
                            type="button"
                            @click="showEditModal = false"
                            class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="editForm.processing"
                            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ editForm.processing ? 'Saving...' : 'Save Quota Limits' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
