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

import {
    ArrowPathIcon,
    ArrowDownTrayIcon,
    XMarkIcon,
    CheckIcon,
    ExclamationTriangleIcon,
    ShieldCheckIcon,
    CircleStackIcon,
    ServerIcon,
    ClockIcon,
    FolderIcon,
    BoltIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    snapshots: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            available_snapshots: 0,
            total_recoverable_size: '0 B',
            last_restored_at: 'Never',
            safety_mode: 'Rollback Protection Active',
        }),
    },
    recent_restores: {
        type: Array,
        default: () => [],
    },
    subscriptions: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({ search: '', type: '' }),
    },
})

// Search & Filter
const search = ref(props.filters?.search || '')
const type = ref(props.filters?.type || '')
const feedbackMsg = ref('')

const filteredSnapshots = computed(() => {
    let list = props.snapshots || []
    if (search.value.trim()) {
        const q = search.value.toLowerCase()
        list = list.filter(s =>
            (s.name || '').toLowerCase().includes(q) ||
            (s.filename || '').toLowerCase().includes(q) ||
            (s.subscription?.domain || '').toLowerCase().includes(q)
        )
    }
    if (type.value) {
        list = list.filter(s => (s.type || '').toLowerCase() === type.value.toLowerCase())
    }
    return list
})

// RESTORE MODAL
const showRestoreModal = ref(false)
const selectedSnapshot = ref(null)
const createSafetySnapshot = ref(true)
const isRestoring = ref(false)

const openRestoreModal = (item) => {
    selectedSnapshot.value = item
    createSafetySnapshot.value = true
    showRestoreModal.value = true
}

const confirmRestore = () => {
    if (!selectedSnapshot.value) return
    isRestoring.value = true

    router.post(route('admin.backups.restore.run', selectedSnapshot.value.id), {
        create_safety_snapshot: createSafetySnapshot.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showRestoreModal.value = false
            feedbackMsg.value = `Disaster recovery restore completed for '${selectedSnapshot.value.name || selectedSnapshot.value.filename}'.`
            setTimeout(() => { feedbackMsg.value = '' }, 5000)
        },
        onError: () => {
            feedbackMsg.value = 'Failed to execute restore task.'
        },
        onFinish: () => {
            isRestoring.value = false
        }
    })
}
</script>

<template>
    <Head title="Disaster Recovery & Restore - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Security', href: '#' },
                    { label: 'Backups & Snapshots', href: route('admin.backups.index') },
                    { label: 'Disaster Recovery & Snapshot Restore Points' }
                ]"
            >
                <template #actions>
                    <Link
                        :href="route('admin.backups.index')"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowLeftIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>All Snapshots</span>
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
                    title="Available Restore Points"
                    :value="String(stats.available_snapshots || snapshots.length || 0)"
                    badge="Ready"
                    badgeType="success"
                    color="blue"
                    :icon="ShieldCheckIcon"
                />

                <InfoCard
                    title="Recoverable Data Size"
                    :value="stats.total_recoverable_size || '0 B'"
                    badge="Storage"
                    badgeType="info"
                    color="purple"
                    :icon="CircleStackIcon"
                />

                <InfoCard
                    title="Last System Restore"
                    :value="stats.last_restored_at || 'Never'"
                    badge="Audit Trail"
                    badgeType="info"
                    color="emerald"
                    :icon="ClockIcon"
                />

                <InfoCard
                    title="Rollback Protection"
                    value="Pre-Restore Safety Active"
                    badge="Protected"
                    badgeType="success"
                    color="sky"
                    :icon="BoltIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search restore point name, target domain, or filename..."
                @search="() => {}"
                @filter="() => {}"
                @reset="() => { search = ''; type = ''; }"
            >
                <FilterSelect
                    v-model="type"
                    label="Snapshot Scope"
                    :options="[
                        { label: 'All Snapshot Types', value: '' },
                        { label: 'Full System Snapshot', value: 'full' },
                        { label: 'Web Files Only', value: 'files' },
                        { label: 'Databases Only', value: 'database' }
                    ]"
                    placeholder="All Types"
                />
            </DataTableFilter>

            <!-- 4. Standard Data Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Restore Point Name</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Scope Type</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Domain Account</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Archive Size</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Storage Target</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Created At</th>
                                <th class="py-2.5 px-3 w-28">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(s, index) in filteredSnapshots" :key="s.id || index" class="hover:bg-blue-50/30 transition">
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-bold text-slate-900">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs border border-blue-100 shrink-0">
                                            <ShieldCheckIcon class="w-3.5 h-3.5" />
                                        </div>
                                        <div>
                                            <span class="block leading-tight font-mono text-xs">{{ s.name || s.filename }}</span>
                                            <span class="text-[10px] text-slate-400 font-mono block">{{ s.filename }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase bg-slate-100 text-slate-700 border border-slate-200 font-mono">
                                        {{ s.type || 'Full' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono text-[11px] text-slate-600">
                                    {{ s.subscription?.domain || 'Global Node' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[11px] font-bold text-slate-800">
                                    {{ s.size_formatted || '0 B' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[11px] text-slate-600">
                                    {{ s.storage?.name || 'Local Storage' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[10.5px] text-slate-500">
                                    {{ s.created_at ? new Date(s.created_at).toLocaleString() : 'Recent' }}
                                </td>
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <button 
                                        type="button" 
                                        @click="openRestoreModal(s)"
                                        class="px-2.5 py-1 bg-white hover:bg-emerald-50 text-emerald-700 font-bold rounded-[3px] text-xs border border-emerald-200 shadow-2xs transition cursor-pointer"
                                        title="Restore Point"
                                    >
                                        Restore 🔄
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="!filteredSnapshots || filteredSnapshots.length === 0">
                                <td colspan="8" class="py-12 text-center text-slate-400 font-sans">
                                    No restore points found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- RESTORE CONFIRMATION MODAL -->
        <div v-if="showRestoreModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold border border-emerald-100">
                            <ArrowPathIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Initiate Snapshot Restore
                        </h3>
                    </div>
                    <button @click="showRestoreModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-3 text-xs">
                    <div class="bg-amber-50 border border-amber-200 rounded-[3px] p-3 flex items-start gap-2.5 text-amber-900">
                        <ExclamationTriangleIcon class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" />
                        <div>
                            <p class="font-bold">Overwriting Data Warning</p>
                            <p class="text-[11px] text-amber-700 mt-0.5">Restoring this snapshot will replace current production files and databases with the archived state from <strong class="font-mono">{{ selectedSnapshot?.created_at ? new Date(selectedSnapshot.created_at).toLocaleString() : 'Selected Date' }}</strong>.</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 pt-1 bg-slate-50 p-2.5 rounded-[3px] border border-slate-200">
                        <input v-model="createSafetySnapshot" id="safety" type="checkbox" class="rounded-[2px] text-blue-600 focus:ring-blue-500 cursor-pointer" />
                        <label for="safety" class="text-xs text-slate-800 font-bold cursor-pointer">Generate safety pre-rollback snapshot before restoring</label>
                    </div>
                </div>

                <div class="px-4 py-2.5 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                    <button
                        type="button"
                        @click="showRestoreModal = false"
                        class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="confirmRestore"
                        :disabled="isRestoring"
                        class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                    >
                        {{ isRestoring ? 'Restoring Snapshot...' : 'Confirm & Restore' }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
