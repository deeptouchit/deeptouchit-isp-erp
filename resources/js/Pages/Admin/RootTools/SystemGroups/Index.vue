<script setup>
import { ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import RefreshButton from '@/Components/UI/RefreshButton.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'
import DataTableFilter from '@/Components/UI/DataTableFilter.vue'
import RowActionDropdown from '@/Components/UI/RowActionDropdown.vue'
import axios from 'axios'
import {
    UserGroupIcon,
    ShieldCheckIcon,
    UsersIcon,
    ServerIcon,
    KeyIcon,
    EyeIcon,
    XMarkIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    groups: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            total_groups: 0,
            privileged_count: 0,
            daemon_count: 0,
            user_private_count: 0,
        }),
    },
    filters: {
        type: Object,
        default: () => ({
            type: 'all',
            search: '',
        }),
    },
})

// Filters
const search = ref(props.filters?.search || '')
const currentType = ref(props.filters?.type || 'all')

const applyFilters = () => {
    router.get(route('admin.root-tools.system-groups'), {
        type: currentType.value !== 'all' ? currentType.value : undefined,
        search: search.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const filterByType = (type) => {
    currentType.value = type
    applyFilters()
}

const resetFilters = () => {
    search.value = ''
    currentType.value = 'all'
    applyFilters()
}

// Group Details Modal
const showDetailsModal = ref(false)
const selectedGroup = ref(null)
const groupDetails = ref(null)
const loadingDetails = ref(false)

const openDetailsModal = async (g) => {
    selectedGroup.value = g
    showDetailsModal.value = true
    loadingDetails.value = true
    groupDetails.value = null

    try {
        const res = await axios.get(route('admin.root-tools.system-groups.details', g.name))
        groupDetails.value = res.data
    } catch (err) {
        groupDetails.value = {
            error: err.response?.data?.error || 'Could not fetch group details.',
        }
    } finally {
        loadingDetails.value = false
    }
}
</script>

<template>
    <Head title="System POSIX Groups - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Root Tools', href: '#' },
                    { label: 'System Groups & POSIX GID Directory' }
                ]"
            >
                <template #actions>
                    <RefreshButton />
                </template>
            </PageHeader>

            <!-- 2. Ultra-Compact KPI Info Cards (56px) -->
            <InfoCardsGrid :cols="4">
                <InfoCard
                    title="Total POSIX Groups"
                    :value="stats.total_groups || 0"
                    badge="Registered"
                    badgeType="info"
                    color="blue"
                    :icon="UserGroupIcon"
                />

                <InfoCard
                    title="Privileged (Sudo / Admin)"
                    :value="stats.privileged_count || 0"
                    badge="Root Access"
                    badgeType="danger"
                    color="rose"
                    :icon="ShieldCheckIcon"
                />

                <InfoCard
                    title="Daemon Service Groups"
                    :value="stats.daemon_count || 0"
                    badge="Services"
                    badgeType="info"
                    color="emerald"
                    :icon="ServerIcon"
                />

                <InfoCard
                    title="User-Private Groups (UPG)"
                    :value="stats.user_private_count || 0"
                    badge="UPG"
                    badgeType="success"
                    color="purple"
                    :icon="UsersIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Type Filter Tabs -->
            <div class="flex items-center gap-1.5 border-b border-slate-200 pb-1 text-xs">
                <button
                    @click="filterByType('all')"
                    :class="currentType === 'all' ? 'bg-blue-600 text-white font-bold' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200'"
                    class="px-3 py-1 rounded-[3px] transition cursor-pointer shadow-2xs"
                >
                    All Groups ({{ stats.total_groups || 0 }})
                </button>
                <button
                    @click="filterByType('privileged')"
                    :class="currentType === 'privileged' ? 'bg-rose-600 text-white font-bold' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200'"
                    class="px-3 py-1 rounded-[3px] transition cursor-pointer shadow-2xs"
                >
                    Privileged ({{ stats.privileged_count || 0 }})
                </button>
                <button
                    @click="filterByType('daemon')"
                    :class="currentType === 'daemon' ? 'bg-blue-600 text-white font-bold' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200'"
                    class="px-3 py-1 rounded-[3px] transition cursor-pointer shadow-2xs"
                >
                    Daemons ({{ stats.daemon_count || 0 }})
                </button>
                <button
                    @click="filterByType('user_private')"
                    :class="currentType === 'user_private' ? 'bg-blue-600 text-white font-bold' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200'"
                    class="px-3 py-1 rounded-[3px] transition cursor-pointer shadow-2xs"
                >
                    User Private ({{ stats.user_private_count || 0 }})
                </button>
            </div>

            <!-- 4. DataTable Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                placeholder="Search group name, GID, or member usernames..."
                @search="applyFilters"
                @reset="resetFilters"
            />

            <!-- 5. POSIX Groups Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 text-center w-10">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Group Identifier</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-center">POSIX GID</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-center">Group Category</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-center">Members Count</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Associated POSIX Members</th>
                                <th class="py-2.5 px-3 text-center w-16">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(g, index) in groups" :key="g.name" class="hover:bg-blue-50/30 transition">
                                <td class="py-2 px-3 border-r border-slate-100 font-mono text-slate-400 text-center whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>
                                <td class="py-2 px-3 border-r border-slate-100 text-left whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div 
                                            :class="g.is_privileged ? 'bg-rose-50 text-rose-600 border-rose-200' : (g.is_daemon ? 'bg-emerald-50 text-emerald-600 border-emerald-200' : 'bg-slate-100 text-slate-600 border-slate-200')"
                                            class="w-6 h-6 rounded-[3px] border flex items-center justify-center font-bold"
                                        >
                                            <ShieldCheckIcon v-if="g.is_privileged" class="w-3.5 h-3.5" />
                                            <ServerIcon v-else-if="g.is_daemon" class="w-3.5 h-3.5" />
                                            <UserGroupIcon v-else class="w-3.5 h-3.5" />
                                        </div>
                                        <span class="font-bold text-slate-900 font-mono">{{ g.name }}</span>
                                    </div>
                                </td>
                                <td class="py-2 px-3 border-r border-slate-100 font-mono text-slate-600 text-center whitespace-nowrap">
                                    {{ g.gid }}
                                </td>
                                <td class="py-2 px-3 border-r border-slate-100 text-center whitespace-nowrap">
                                    <span 
                                        v-if="g.is_privileged"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border bg-rose-50 text-rose-700 border-rose-200 font-mono"
                                    >
                                        Privileged
                                    </span>
                                    <span 
                                        v-else-if="g.is_daemon"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border bg-emerald-50 text-emerald-700 border-emerald-200 font-mono"
                                    >
                                        Daemon
                                    </span>
                                    <span 
                                        v-else
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border bg-slate-50 text-slate-700 border-slate-200 font-mono"
                                    >
                                        User Private
                                    </span>
                                </td>
                                <td class="py-2 px-3 border-r border-slate-100 font-mono font-bold text-slate-900 text-center whitespace-nowrap">
                                    {{ g.members?.length || 0 }}
                                </td>
                                <td class="py-2 px-3 border-r border-slate-100 text-left whitespace-nowrap max-w-xs truncate">
                                    <div v-if="g.members && g.members.length > 0" class="flex flex-wrap gap-1">
                                        <span 
                                            v-for="m in g.members.slice(0, 4)" 
                                            :key="m"
                                            class="px-1.5 py-0.5 rounded-[2px] text-[10px] bg-slate-100 text-slate-700 font-mono border border-slate-200"
                                        >
                                            {{ m }}
                                        </span>
                                        <span v-if="g.members.length > 4" class="text-[10px] text-slate-400 font-mono">
                                            +{{ g.members.length - 4 }} more
                                        </span>
                                    </div>
                                    <span v-else class="text-slate-400 italic text-[11px]">No secondary members</span>
                                </td>
                                <td class="py-2 px-3 text-center whitespace-nowrap">
                                    <RowActionDropdown :items="[
                                        { label: 'Inspect Group Members', icon: EyeIcon, onClick: () => openDetailsModal(g) }
                                    ]" />
                                </td>
                            </tr>
                            <tr v-if="groups.length === 0">
                                <td colspan="7" class="py-8 text-center text-slate-400">
                                    No POSIX system groups found matching the current query.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- GROUP DETAILS MODAL -->
        <div v-if="showDetailsModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-lg overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <UserGroupIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide font-mono">
                            POSIX Group: {{ selectedGroup?.name }} (GID: {{ selectedGroup?.gid }})
                        </h3>
                    </div>
                    <button @click="showDetailsModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-3 text-xs">
                    <div v-if="loadingDetails" class="py-8 text-center text-slate-400">
                        Loading group membership details...
                    </div>
                    <div v-else-if="groupDetails?.error" class="p-3 bg-rose-50 border border-rose-200 text-rose-700 rounded-[3px]">
                        {{ groupDetails.error }}
                    </div>
                    <div v-else class="space-y-3">
                        <div class="grid grid-cols-2 gap-2 p-2.5 bg-slate-50 border border-slate-100 rounded-[3px] font-mono">
                            <div><span class="text-slate-400">Group Name:</span> <span class="font-bold text-slate-800">{{ selectedGroup?.name }}</span></div>
                            <div><span class="text-slate-400">POSIX GID:</span> <span class="font-bold text-slate-800">{{ selectedGroup?.gid }}</span></div>
                            <div><span class="text-slate-400">Category:</span> <span class="font-bold text-slate-800">{{ selectedGroup?.is_privileged ? 'Privileged' : (selectedGroup?.is_daemon ? 'Daemon' : 'User Private') }}</span></div>
                            <div><span class="text-slate-400">Total Members:</span> <span class="font-bold text-slate-800">{{ selectedGroup?.members?.length || 0 }}</span></div>
                        </div>

                        <div>
                            <h4 class="font-bold text-slate-800 mb-1.5 uppercase text-[11px]">Enrolled System User Accounts</h4>
                            <div v-if="selectedGroup?.members?.length > 0" class="flex flex-wrap gap-1.5 max-h-48 overflow-y-auto p-2 bg-slate-50 border border-slate-200 rounded-[3px]">
                                <span 
                                    v-for="u in selectedGroup.members" 
                                    :key="u"
                                    class="px-2 py-1 rounded-[2px] bg-white border border-slate-200 text-slate-800 font-mono text-xs shadow-2xs"
                                >
                                    {{ u }}
                                </span>
                            </div>
                            <div v-else class="p-3 text-slate-400 italic bg-slate-50 rounded-[3px] border border-slate-100">
                                No secondary users are currently enrolled in this POSIX group.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-4 py-2.5 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end">
                    <button
                        type="button"
                        @click="showDetailsModal = false"
                        class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer text-xs"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
